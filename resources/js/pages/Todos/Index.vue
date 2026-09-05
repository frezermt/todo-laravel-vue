<script setup lang="ts">
import { Head, useForm, router, usePage } from '@inertiajs/vue3';
import { ref, computed, watch, onMounted, onUnmounted } from 'vue';
import { toast } from 'vue-sonner';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import TaskCard, { type Todo } from '@/components/todos/TaskCard.vue';
import TaskCalendar from '@/components/todos/TaskCalendar.vue';
import TaskOverview from '@/components/todos/TaskOverview.vue';
import TaskDetailsModal from '@/components/todos/TaskDetailsModal.vue';
import TodayView from '@/components/todos/TodayView.vue';
import UpcomingView from '@/components/todos/UpcomingView.vue';
import CategoriesView from '@/components/todos/CategoriesView.vue';

const props = withDefaults(defineProps<{
    todos?: Todo[];
}>(), {
    todos: () => [],
});

const page = usePage();
const safeTodos = computed(() => props.todos || []);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Passo',
                href: '/todos',
            },
        ],
    },
});

// Screen View State ('dashboard' | 'today' | 'upcoming' | 'categories')
type ScreenView = 'dashboard' | 'today' | 'upcoming' | 'categories';
const currentScreen = ref<ScreenView>('dashboard');

function syncScreenFromUrl() {
    try {
        const searchParams = new URL(page.url, 'http://localhost').searchParams;
        const v = searchParams.get('view') as ScreenView;
        if (v && ['dashboard', 'today', 'upcoming', 'categories'].includes(v)) {
            currentScreen.value = v;
        } else {
            currentScreen.value = 'dashboard';
        }
    } catch {
        currentScreen.value = 'dashboard';
    }
}

function setScreen(screen: ScreenView) {
    currentScreen.value = screen;
    const newUrl = screen === 'dashboard' ? '/todos' : `/todos?view=${screen}`;
    window.history.pushState(null, '', newUrl);
}

watch(() => page.url, () => {
    syncScreenFromUrl();
});

// Dynamic Greeting based on time of day & user name
const userFirstName = computed(() => {
    const user = (page.props.auth as any)?.user;
    return user?.name ? user.name.split(' ')[0] : 'there';
});

const userGreeting = computed(() => {
    const hour = new Date().getHours();
    let greeting = 'Good morning';
    if (hour >= 12 && hour < 17) {
        greeting = 'Good afternoon';
    } else if (hour >= 17) {
        greeting = 'Good evening';
    }
    return `${greeting}, ${userFirstName.value}`;
});

// Local Timezone name
const localTimezone = computed(() => {
    try {
        const tz = Intl.DateTimeFormat().resolvedOptions().timeZone;
        const short = new Date().toLocaleTimeString('en-us', { timeZoneName: 'short' }).split(' ')[2];
        return short ? `${short}` : tz;
    } catch {
        return 'Local';
    }
});

// Curated V1 Palette (Earth/editorial tones matching design constraints)
const COLOR_PALETTE = [
    { name: 'Terracotta', value: '#b85c38' },
    { name: 'Slate', value: '#475569' },
    { name: 'Sage', value: '#4e6b56' },
    { name: 'Ochre', value: '#b8860b' },
    { name: 'Plum', value: '#7a4960' },
    { name: 'Indigo', value: '#395273' },
    { name: 'Umber', value: '#785642' },
    { name: 'Charcoal', value: '#2c302e' },
];

const DEFAULT_CATEGORIES = ['Work', 'Personal', 'Study', 'Finance', 'Health', 'Projects', 'Shopping', 'Home'];

const availableCategories = computed(() => {
    const set = new Set(DEFAULT_CATEGORIES);
    safeTodos.value.forEach(t => {
        if (t.category && t.category.trim()) {
            set.add(t.category.trim());
        }
    });
    return Array.from(set);
});

// Recurrence Presets matching screenshot
const RECURRENCE_PRESETS = [
    { label: 'Every day', value: 'daily' },
    { label: 'Every 2 days', value: 'custom:2:days' },
    { label: 'Every 3 days', value: 'custom:3:days' },
    { label: 'Every week', value: 'weekly' },
    { label: 'Every month', value: 'monthly' },
];

// View mode in Dashboard: Cards vs Calendar
type ViewMode = 'cards' | 'calendar';
const viewMode = ref<ViewMode>('cards');

// Status & Category Filters
type FilterType = 'all' | 'active' | 'completed';
const activeFilter = ref<FilterType>('all');
const activeCategory = ref<string | null>(null);

const filteredTodos = computed(() => {
    return safeTodos.value.filter(t => {
        if (activeFilter.value === 'active' && t.completed) return false;
        if (activeFilter.value === 'completed' && !t.completed) return false;
        if (activeCategory.value !== null && t.category !== activeCategory.value) return false;
        return true;
    });
});

const stats = computed(() => ({
    total: safeTodos.value.length,
    completed: safeTodos.value.filter(t => t.completed).length,
    active: safeTodos.value.filter(t => !t.completed).length,
}));

// Task Details Modal
const selectedTaskForDetails = ref<Todo | null>(null);
const showDetailsDialog = ref(false);

function openTaskDetails(todo: Todo) {
    selectedTaskForDetails.value = todo;
    showDetailsDialog.value = true;
}

// Create Form & Dialog
const showCreateDialog = ref(false);
const customCategoryInput = ref('');
const isAddingCustomCategory = ref(false);
const enableCreateReminder = ref(false);
const enableCreateRecurrence = ref(false);

const isCreateCustomRecurrence = ref(false);
const createCustomInterval = ref(3);
const createCustomUnit = ref('days');

const createForm = useForm({
    title: '',
    description: '',
    category: 'Work',
    color: '#b85c38',
    due_date: '',
    recurrence: 'none',
    reminder_at: '',
});

function openCreateModal(defaultDueDate?: string, defaultCategory?: string) {
    createForm.reset();
    createForm.category = defaultCategory || 'Work';
    createForm.color = '#b85c38';
    createForm.recurrence = 'none';
    createForm.reminder_at = '';
    enableCreateRecurrence.value = false;
    isCreateCustomRecurrence.value = false;
    createCustomInterval.value = 3;
    createCustomUnit.value = 'days';
    enableCreateReminder.value = false;
    if (defaultDueDate) {
        createForm.due_date = defaultDueDate;
    }
    isAddingCustomCategory.value = false;
    customCategoryInput.value = '';
    showCreateDialog.value = true;
}

function onCreateCategorySelect(e: Event) {
    const val = (e.target as HTMLSelectElement).value;
    if (val === '__custom__') {
        isAddingCustomCategory.value = true;
        customCategoryInput.value = '';
    } else {
        createForm.category = val;
        isAddingCustomCategory.value = false;
    }
}

function onEditCategorySelect(e: Event) {
    const val = (e.target as HTMLSelectElement).value;
    if (val === '__custom__') {
        isEditCustomCategory.value = true;
        editCustomCategoryInput.value = '';
    } else {
        editForm.category = val;
        isEditCustomCategory.value = false;
    }
}

function setCreateRecurrence(val: string) {
    enableCreateRecurrence.value = true;
    if (val === 'custom') {
        isCreateCustomRecurrence.value = true;
        createForm.recurrence = `custom:${createCustomInterval.value}:${createCustomUnit.value}`;
    } else {
        isCreateCustomRecurrence.value = false;
        createForm.recurrence = val;
    }
}

function updateCreateCustomRecurrence() {
    createForm.recurrence = `custom:${createCustomInterval.value || 1}:${createCustomUnit.value}`;
}

const createRecurrenceExplainer = computed(() => {
    if (!enableCreateRecurrence.value || createForm.recurrence === 'none') {
        return 'Task will not repeat.';
    }
    if (createForm.recurrence === 'daily') return 'Repeats every day';
    if (createForm.recurrence === 'custom:2:days') return 'Repeats every 2 days';
    if (createForm.recurrence === 'custom:3:days') return 'Repeats every 3 days';
    if (createForm.recurrence === 'weekly') return 'Repeats every week';
    if (createForm.recurrence === 'monthly') return 'Repeats every month';
    if (createForm.recurrence.startsWith('custom:')) {
        return `Repeats every ${createCustomInterval.value} ${createCustomUnit.value}`;
    }
    return `Repeats (${createForm.recurrence})`;
});

function submitCreate() {
    if (isAddingCustomCategory.value && customCategoryInput.value.trim()) {
        createForm.category = customCategoryInput.value.trim();
    }
    if (!enableCreateReminder.value) {
        createForm.reminder_at = '';
    }
    if (!enableCreateRecurrence.value) {
        createForm.recurrence = 'none';
    } else if (isCreateCustomRecurrence.value) {
        createForm.recurrence = `custom:${createCustomInterval.value || 1}:${createCustomUnit.value}`;
    }

    const taskTitle = createForm.title;
    createForm.post('/todos', {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Task created successfully', {
                description: `"${taskTitle}" has been added to your tasks.`,
            });
            createForm.reset();
            isAddingCustomCategory.value = false;
            customCategoryInput.value = '';
            showCreateDialog.value = false;
        },
    });
}

// Edit Form & Dialog
const showEditDialog = ref(false);
const editingTodo = ref<Todo | null>(null);
const editCustomCategoryInput = ref('');
const isEditCustomCategory = ref(false);
const enableEditReminder = ref(false);
const enableEditRecurrence = ref(false);

const isEditCustomRecurrence = ref(false);
const editCustomInterval = ref(3);
const editCustomUnit = ref('days');

const editForm = useForm({
    title: '',
    description: '',
    category: '',
    color: '#b85c38',
    due_date: '',
    recurrence: 'none',
    reminder_at: '',
});

function openEdit(todo: Todo) {
    editingTodo.value = todo;
    editForm.title = todo.title;
    editForm.description = todo.description ?? '';
    editForm.category = todo.category ?? 'Work';
    editForm.color = todo.color ?? '#b85c38';
    editForm.due_date = todo.due_date ?? '';

    // Recurrence parsing
    const rec = todo.recurrence ?? 'none';
    if (rec && rec !== 'none') {
        enableEditRecurrence.value = true;
        if (rec.startsWith('custom:')) {
            const parts = rec.split(':');
            const count = parseInt(parts[1]) || 3;
            const unit = parts[2] || 'days';
            if ((count === 2 || count === 3) && unit === 'days') {
                isEditCustomRecurrence.value = false;
            } else {
                isEditCustomRecurrence.value = true;
            }
            editCustomInterval.value = count;
            editCustomUnit.value = unit;
            editForm.recurrence = rec;
        } else {
            isEditCustomRecurrence.value = false;
            editForm.recurrence = rec;
        }
    } else {
        enableEditRecurrence.value = false;
        isEditCustomRecurrence.value = false;
        editForm.recurrence = 'none';
    }

    editForm.reminder_at = todo.reminder_at ? todo.reminder_at.substring(0, 16) : '';
    enableEditReminder.value = !!todo.reminder_at;
    isEditCustomCategory.value = false;
    editCustomCategoryInput.value = '';
    showEditDialog.value = true;
}

function setEditRecurrence(val: string) {
    enableEditRecurrence.value = true;
    if (val === 'custom') {
        isEditCustomRecurrence.value = true;
        editForm.recurrence = `custom:${editCustomInterval.value}:${editCustomUnit.value}`;
    } else {
        isEditCustomRecurrence.value = false;
        editForm.recurrence = val;
    }
}

function updateEditCustomRecurrence() {
    editForm.recurrence = `custom:${editCustomInterval.value || 1}:${editCustomUnit.value}`;
}

const editRecurrenceExplainer = computed(() => {
    if (!enableEditRecurrence.value || editForm.recurrence === 'none') {
        return 'Task will not repeat.';
    }
    if (editForm.recurrence === 'daily') return 'Repeats every day';
    if (editForm.recurrence === 'custom:2:days') return 'Repeats every 2 days';
    if (editForm.recurrence === 'custom:3:days') return 'Repeats every 3 days';
    if (editForm.recurrence === 'weekly') return 'Repeats every week';
    if (editForm.recurrence === 'monthly') return 'Repeats every month';
    if (editForm.recurrence.startsWith('custom:')) {
        return `Repeats every ${editCustomInterval.value} ${editCustomUnit.value}`;
    }
    return `Repeats (${editForm.recurrence})`;
});

function submitEdit() {
    if (!editingTodo.value) return;
    if (isEditCustomCategory.value && editCustomCategoryInput.value.trim()) {
        editForm.category = editCustomCategoryInput.value.trim();
    }
    if (!enableEditReminder.value) {
        editForm.reminder_at = '';
    }
    if (!enableEditRecurrence.value) {
        editForm.recurrence = 'none';
    } else if (isEditCustomRecurrence.value) {
        editForm.recurrence = `custom:${editCustomInterval.value || 1}:${editCustomUnit.value}`;
    }

    const taskTitle = editForm.title;
    editForm.put(`/todos/${editingTodo.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Task updated successfully', {
                description: `Changes to "${taskTitle}" have been saved.`,
            });
            if (selectedTaskForDetails.value && selectedTaskForDetails.value.id === editingTodo.value?.id) {
                selectedTaskForDetails.value = {
                    ...selectedTaskForDetails.value,
                    title: editForm.title,
                    description: editForm.description,
                    category: editForm.category,
                    color: editForm.color,
                    due_date: editForm.due_date,
                    recurrence: editForm.recurrence,
                    reminder_at: editForm.reminder_at,
                };
            }
            editForm.reset();
            editingTodo.value = null;
            showEditDialog.value = false;
        },
    });
}

// Toggle Complete
function toggleCompleted(todo: Todo) {
    const isNowCompleting = !todo.completed;
    router.put(
        `/todos/${todo.id}`,
        { completed: isNowCompleting },
        {
            preserveScroll: true,
            onSuccess: () => {
                if (selectedTaskForDetails.value && selectedTaskForDetails.value.id === todo.id) {
                    selectedTaskForDetails.value.completed = isNowCompleting;
                }
                if (isNowCompleting) {
                    if (todo.recurrence && todo.recurrence !== 'none') {
                        toast.success('Task completed! Next occurrence scheduled', {
                            description: `"${todo.title}" will repeat automatically.`,
                        });
                    } else {
                        toast.success('Task completed!', {
                            description: `Great job finishing "${todo.title}".`,
                        });
                    }
                } else {
                    toast.info('Task reopened', {
                        description: `"${todo.title}" is back in active tasks.`,
                    });
                }
            },
        }
    );
}

// Delete Dialog
const showDeleteDialog = ref(false);
const deletingTodo = ref<Todo | null>(null);

function openDelete(todo: Todo) {
    deletingTodo.value = todo;
    showDeleteDialog.value = true;
}

function submitDelete() {
    if (!deletingTodo.value) return;
    const taskTitle = deletingTodo.value.title;
    const id = deletingTodo.value.id;
    router.delete(`/todos/${id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.info('Task deleted', {
                description: `"${taskTitle}" was removed.`,
            });
            if (selectedTaskForDetails.value?.id === id) {
                showDetailsDialog.value = false;
                selectedTaskForDetails.value = null;
            }
            deletingTodo.value = null;
            showDeleteDialog.value = false;
        },
        onError: () => {
            deletingTodo.value = null;
            showDeleteDialog.value = false;
        },
    });
}

// Desktop & Audible Alarm Notification System
const alertedReminders = ref<Set<number>>(new Set());
let reminderInterval: number | null = null;
const notificationPermission = ref<NotificationPermission | 'unsupported'>('default');

function updateNotificationPermission() {
    if ('Notification' in window) {
        notificationPermission.value = Notification.permission;
    } else {
        notificationPermission.value = 'unsupported';
    }
}

function playAlarmChime() {
    try {
        const AudioCtx = window.AudioContext || (window as any).webkitAudioContext;
        if (!AudioCtx) return;
        const ctx = new AudioCtx();
        const osc = ctx.createOscillator();
        const gain = ctx.createGain();
        osc.type = 'sine';
        osc.frequency.setValueAtTime(587.33, ctx.currentTime);
        osc.frequency.exponentialRampToValueAtTime(880, ctx.currentTime + 0.15);
        gain.gain.setValueAtTime(0.3, ctx.currentTime);
        gain.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.8);
        osc.connect(gain);
        gain.connect(ctx.destination);
        osc.start();
        osc.stop(ctx.currentTime + 0.8);
    } catch {
        // audio context could not start
    }
}

function requestNotificationAccess() {
    if ('Notification' in window) {
        Notification.requestPermission().then(permission => {
            notificationPermission.value = permission;
            if (permission === 'granted') {
                playAlarmChime();
                try {
                    new Notification('🔔 Alarms Activated!', {
                        body: 'You will receive desktop notifications when deadlines arrive, even outside this tab.',
                        requireInteraction: false,
                    });
                } catch {
                    // notification fallback
                }
                toast.success('Desktop alarms enabled!', {
                    description: 'Notifications and chimes will alert you outside the app.',
                });
            } else if (permission === 'denied') {
                toast.error('Desktop notifications blocked', {
                    description: 'Please enable notifications in your browser URL bar to receive alarms outside the tab.',
                });
            }
        });
    } else {
        toast.info('Desktop notifications unsupported', {
            description: 'Your current browser does not support HTML5 desktop notifications.',
        });
    }
}

function checkReminders() {
    const now = new Date().getTime();
    safeTodos.value.forEach(todo => {
        if (todo.completed) return;
        if (!todo.reminder_at) return;

        const reminderTime = new Date(todo.reminder_at).getTime();
        if (reminderTime <= now && now - reminderTime < 15 * 60 * 1000) {
            if (!alertedReminders.value.has(todo.id)) {
                alertedReminders.value.add(todo.id);

                playAlarmChime();

                toast.warning(`Deadline Alarm: ${todo.title}`, {
                    description: todo.description ? `${todo.description}` : 'Scheduled deadline / reminder has arrived!',
                    duration: 10000,
                });

                if ('Notification' in window && Notification.permission === 'granted') {
                    try {
                        const notif = new Notification(`⏰ Task Deadline: ${todo.title}`, {
                            body: todo.description || 'Your scheduled task deadline has arrived!',
                            requireInteraction: true,
                        });
                        notif.onclick = () => {
                            window.focus();
                        };
                    } catch {
                        // ignore notification exceptions
                    }
                }
            }
        }
    });
}

// Global Keyboard Shortcut [C] to create task
function handleKeydown(e: KeyboardEvent) {
    if (e.key === 'c' || e.key === 'C') {
        const target = e.target as HTMLElement | null;
        if (target && (target.tagName === 'INPUT' || target.tagName === 'TEXTAREA' || target.isContentEditable)) {
            return;
        }
        if (!showCreateDialog.value && !showEditDialog.value && !showDetailsDialog.value && !showDeleteDialog.value) {
            e.preventDefault();
            openCreateModal();
        }
    }
}

onMounted(() => {
    syncScreenFromUrl();
    updateNotificationPermission();
    checkReminders();
    reminderInterval = window.setInterval(checkReminders, 25000);
    window.addEventListener('keydown', handleKeydown);
});

onUnmounted(() => {
    if (reminderInterval) {
        clearInterval(reminderInterval);
    }
    window.removeEventListener('keydown', handleKeydown);
});
</script>

<template>
    <Head title="Passo - Tasks" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8 max-w-7xl mx-auto w-full">

        <!-- VIEW 1: TODAY VIEW (Middle Screenshot) -->
        <TodayView
            v-if="currentScreen === 'today'"
            :todos="safeTodos"
            :user-name="userFirstName"
            @create="openCreateModal"
            @select="openTaskDetails"
            @toggle="toggleCompleted"
            @edit="openEdit"
            @delete="openDelete"
        />

        <!-- VIEW 2: UPCOMING VIEW (Right Screenshot) -->
        <UpcomingView
            v-else-if="currentScreen === 'upcoming'"
            :todos="safeTodos"
            @create="openCreateModal"
            @select="openTaskDetails"
            @toggle="toggleCompleted"
            @edit="openEdit"
            @delete="openDelete"
        />

        <!-- VIEW 3: CATEGORIES VIEW (Left Screenshot) -->
        <CategoriesView
            v-else-if="currentScreen === 'categories'"
            :todos="safeTodos"
            :available-categories="availableCategories"
            @create="openCreateModal"
            @select="openTaskDetails"
            @toggle="toggleCompleted"
            @edit="openEdit"
            @delete="openDelete"
        />

        <!-- VIEW 4: MAIN TASK DASHBOARD -->
        <div v-else class="flex flex-col gap-6">
            <!-- Top Greeting Header & Quick Actions -->
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between border-b border-border/50 pb-5">
                <div>
                    <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                        {{ userGreeting }}
                    </h1>
                    <p class="mt-1 text-xs sm:text-sm text-muted-foreground">
                        Here are the things you need to take care of.
                    </p>
                </div>

                <!-- Header Controls: Alarms Toggle, View Switcher, + Add Task -->
                <div class="flex flex-wrap items-center gap-2.5 sm:pt-1">
                    <!-- Desktop Alarms Toggle Button -->
                    <button
                        type="button"
                        @click="requestNotificationAccess"
                        :title="notificationPermission === 'granted' ? 'Desktop alarms active' : 'Click to enable desktop alarms'"
                        :class="[
                            'inline-flex items-center gap-1.5 rounded-full border px-3.5 py-1.5 text-xs font-medium transition-all duration-200 ease-out active:scale-95 shadow-2xs',
                            notificationPermission === 'granted'
                                ? 'border-emerald-500/40 bg-emerald-500/10 text-emerald-700 dark:text-emerald-300'
                                : 'border-amber-500/40 bg-amber-500/10 text-amber-800 dark:text-amber-300'
                        ]"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                        </svg>
                        <span>{{ notificationPermission === 'granted' ? 'Alarms On' : 'Enable Alarms' }}</span>
                    </button>

                    <!-- View Switcher Segmented Control (Cards / Calendar) -->
                    <div class="inline-flex rounded-full border border-border bg-muted/30 p-1 shadow-2xs">
                        <button
                            type="button"
                            @click="viewMode = 'cards'"
                            :class="[
                                'inline-flex items-center gap-1.5 rounded-full px-3.5 py-1 text-xs font-medium transition-all duration-200 ease-out active:scale-95',
                                viewMode === 'cards'
                                    ? 'bg-card text-foreground shadow-xs font-semibold'
                                    : 'text-muted-foreground hover:text-foreground'
                            ]"
                        >
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="7" height="7" x="3" y="3" rx="1"/>
                                <rect width="7" height="7" x="14" y="3" rx="1"/>
                                <rect width="7" height="7" x="14" y="14" rx="1"/>
                                <rect width="7" height="7" x="3" y="14" rx="1"/>
                            </svg>
                            Cards
                        </button>
                        <button
                            type="button"
                            @click="viewMode = 'calendar'"
                            :class="[
                                'inline-flex items-center gap-1.5 rounded-full px-3.5 py-1 text-xs font-medium transition-all duration-200 ease-out active:scale-95',
                                viewMode === 'calendar'
                                    ? 'bg-card text-foreground shadow-xs font-semibold'
                                    : 'text-muted-foreground hover:text-foreground'
                            ]"
                        >
                            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect width="18" height="18" x="3" y="4" rx="1"/>
                                <line x1="16" x2="16" y1="2" y2="6"/>
                                <line x1="8" x2="8" y1="2" y2="6"/>
                                <line x1="3" x2="21" y1="10" y2="10"/>
                            </svg>
                            Calendar
                        </button>
                    </div>

                    <!-- Primary "+ Add Task" Pill Button -->
                    <button
                        type="button"
                        @click="openCreateModal()"
                        class="inline-flex items-center gap-1.5 rounded-full bg-[#b85c38] px-4 py-2 text-xs font-semibold text-white transition-all duration-200 ease-out hover:bg-[#a34f2f] active:scale-95 shadow-xs"
                    >
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <line x1="12" y1="5" x2="12" y2="19"/>
                            <line x1="5" y1="12" x2="19" y2="12"/>
                        </svg>
                        <span>Add Task</span>
                    </button>
                </div>
            </div>

            <!-- Task Overview (Pie Charts for Category Distribution & Completion) -->
            <TaskOverview :todos="props.todos" />

            <!-- Filter & Section Header Row -->
            <div class="flex flex-col gap-3.5 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-2.5">
                    <h2 class="text-base font-bold tracking-tight text-foreground">
                        My Tasks
                    </h2>
                    <span class="inline-flex items-center rounded-full bg-secondary/80 px-2.5 py-0.5 text-xs font-semibold text-muted-foreground">
                        {{ stats.active }} active
                    </span>
                </div>

                <!-- Status Tabs & Category Filter Pills -->
                <div class="flex flex-wrap items-center gap-2">
                    <div class="inline-flex items-center rounded-full border border-border/70 bg-secondary/30 p-1">
                        <button
                            v-for="filter in (['all', 'active', 'completed'] as const)"
                            :key="filter"
                            type="button"
                            @click="activeFilter = filter"
                            :class="[
                                'rounded-full px-3 py-1 text-xs font-medium transition-all duration-200 ease-out active:scale-95',
                                activeFilter === filter
                                    ? 'bg-card text-foreground shadow-2xs font-semibold'
                                    : 'text-muted-foreground hover:text-foreground'
                            ]"
                        >
                            {{ filter.charAt(0).toUpperCase() + filter.slice(1) }}
                            <span class="ml-1 text-[10px] opacity-70">
                                {{ filter === 'all' ? stats.total : filter === 'active' ? stats.active : stats.completed }}
                            </span>
                        </button>
                    </div>

                    <div class="flex flex-wrap items-center gap-1">
                        <button
                            type="button"
                            @click="activeCategory = null"
                            :class="[
                                'rounded-full border px-2.5 py-0.5 text-[11px] font-medium transition-all duration-200',
                                activeCategory === null
                                    ? 'border-[#b85c38] bg-[#b85c38]/10 text-[#b85c38] font-semibold'
                                    : 'border-border/70 text-muted-foreground hover:text-foreground'
                            ]"
                        >
                            All
                        </button>
                        <button
                            v-for="cat in availableCategories"
                            :key="cat"
                            type="button"
                            @click="activeCategory = activeCategory === cat ? null : cat"
                            :class="[
                                'rounded-full border px-2.5 py-0.5 text-[11px] font-medium transition-all duration-200',
                                activeCategory === cat
                                    ? 'border-[#b85c38] bg-[#b85c38]/10 text-[#b85c38] font-semibold'
                                    : 'border-border/70 text-muted-foreground hover:text-foreground'
                            ]"
                        >
                            {{ cat }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Cards View (Horizontal Stacked Cards) -->
            <div v-if="viewMode === 'cards'" class="flex-1">
                <div
                    v-if="filteredTodos.length === 0"
                    class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border py-16 text-center"
                >
                    <div class="flex h-12 w-12 items-center justify-center rounded-full border border-border bg-secondary/50 text-muted-foreground">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 20h9"/>
                            <path d="M16.5 3.5a2.12 2.12 0 0 1 3 3L7 19l-4 1 1-4Z"/>
                        </svg>
                    </div>
                    <h3 class="mt-3 text-sm font-semibold text-foreground">
                        {{ activeCategory ? `No tasks found in "${activeCategory}"` : 'No tasks to display' }}
                    </h3>
                    <p class="mt-1 text-xs text-muted-foreground">
                        {{ activeFilter === 'all' ? 'Organize your day with a clear plan.' : `No ${activeFilter} tasks in this filter.` }}
                    </p>
                    <button
                        type="button"
                        @click="openCreateModal()"
                        class="mt-4 rounded-full bg-[#b85c38] px-5 py-2 text-xs font-medium text-white transition-all duration-200 ease-out hover:bg-[#a34f2f] active:scale-95 shadow-xs"
                    >
                        Add Task
                    </button>
                </div>

                <div v-else class="flex flex-col gap-3">
                    <TaskCard
                        v-for="todo in filteredTodos"
                        :key="todo.id"
                        :todo="todo"
                        @select="openTaskDetails"
                        @toggle="toggleCompleted"
                        @edit="openEdit"
                        @delete="openDelete"
                    />
                </div>
            </div>

            <!-- Calendar View -->
            <div v-else class="flex-1">
                <TaskCalendar
                    :todos="props.todos"
                    @toggle="toggleCompleted"
                    @edit="openEdit"
                    @delete="openDelete"
                    @create-for-date="openCreateModal"
                />
            </div>
        </div>

        <!-- Bottom Footer Bar (Shortcut Hint) -->
        <div class="mt-auto flex items-center justify-between border-t border-border/50 pt-4 text-xs text-muted-foreground">
            <div class="flex items-center gap-2">
                <span class="inline-flex items-center rounded border border-border bg-muted/60 px-1.5 py-0.5 font-mono text-[10px] text-foreground">C</span>
                <span>Press <strong class="text-foreground">C</strong> anywhere to quickly create a task.</span>
            </div>
        </div>

        <!-- TASK DETAILS MODAL -->
        <TaskDetailsModal
            :open="showDetailsDialog"
            :todo="selectedTaskForDetails"
            @update:open="showDetailsDialog = $event"
            @toggle="toggleCompleted"
            @edit="openEdit"
            @delete="openDelete"
        />

        <!-- CREATE TASK MODAL -->
        <Dialog v-model:open="showCreateDialog">
            <DialogContent class="sm:max-w-xl rounded-2xl border-border bg-card p-6 shadow-xl max-h-[90vh] overflow-y-auto">
                <form @submit.prevent="submitCreate" class="space-y-4">
                    <!-- Modal Header -->
                    <div class="flex items-start gap-3 border-b border-border/50 pb-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#b85c38]/10 text-[#b85c38]">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M12 5v14"/>
                                <path d="M5 12h14"/>
                            </svg>
                        </div>
                        <div>
                            <DialogTitle class="text-base font-bold text-foreground">
                                Create New Task
                            </DialogTitle>
                            <DialogDescription class="text-xs text-muted-foreground mt-0.5">
                                Plan, schedule and set recurring rules
                            </DialogDescription>
                        </div>
                    </div>

                    <!-- Field: Task Title -->
                    <div class="space-y-1.5">
                        <label for="create-title" class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                            Task Title <span class="text-red-500">*</span>
                        </label>
                        <Input
                            id="create-title"
                            v-model="createForm.title"
                            placeholder="e.g. Finalise marketing landing page & launch copy"
                            autofocus
                            class="rounded-xl text-sm font-medium"
                        />
                        <InputError :message="createForm.errors.title" />
                    </div>

                    <!-- Field: Description -->
                    <div class="space-y-1.5">
                        <label for="create-description" class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                            Description
                        </label>
                        <textarea
                            id="create-description"
                            v-model="createForm.description"
                            placeholder="Coordinate hero asset export, requirements, or notes..."
                            rows="2"
                            class="border-input bg-background placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-xl border px-3 py-2 text-xs focus-visible:outline-none focus-visible:ring-1"
                        />
                        <InputError :message="createForm.errors.description" />
                    </div>

                    <!-- Row: Category & Accent Color Side-by-Side -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Category Selector (All categories in dropdown + custom option) -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label for="create-category" class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                                    Category
                                </label>
                                <button
                                    type="button"
                                    @click="isAddingCustomCategory = !isAddingCustomCategory; if (isAddingCustomCategory) customCategoryInput = ''"
                                    class="text-[11px] font-medium text-[#b85c38] hover:underline"
                                >
                                    {{ isAddingCustomCategory ? '← Choose existing' : '+ Add custom' }}
                                </button>
                            </div>

                            <!-- Dropdown with ALL categories -->
                            <div v-if="!isAddingCustomCategory" class="relative">
                                <select
                                    id="create-category"
                                    v-model="createForm.category"
                                    @change="onCreateCategorySelect"
                                    class="border-input bg-background text-foreground h-9 w-full rounded-xl border px-3 pr-8 text-xs font-medium focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-[#b85c38] appearance-none cursor-pointer shadow-2xs"
                                >
                                    <option v-for="cat in availableCategories" :key="cat" :value="cat">
                                        {{ cat }}
                                    </option>
                                    <option value="__custom__">+ Add new custom category...</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-muted-foreground">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m6 9 6 6 6-6"/>
                                    </svg>
                                </div>
                            </div>

                            <!-- Input for adding a new custom category -->
                            <div v-else class="space-y-1">
                                <Input
                                    id="create-custom-category"
                                    v-model="customCategoryInput"
                                    placeholder="Type new category name..."
                                    class="rounded-xl text-xs h-9 px-3"
                                    autofocus
                                />
                            </div>
                            <InputError :message="createForm.errors.category" />
                        </div>

                        <!-- Accent Color Swatches -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                                Accent Color
                            </label>
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <button
                                    v-for="c in COLOR_PALETTE"
                                    :key="c.value"
                                    type="button"
                                    @click="createForm.color = c.value"
                                    :title="c.name"
                                    class="relative flex h-6 w-6 items-center justify-center rounded-full transition-transform hover:scale-110 active:scale-95"
                                    :style="{ backgroundColor: c.value }"
                                >
                                    <span
                                        v-if="createForm.color === c.value"
                                        class="h-2 w-2 rounded-full bg-white ring-1 ring-black/20"
                                    />
                                </button>
                            </div>
                            <InputError :message="createForm.errors.color" />
                        </div>
                    </div>

                    <!-- Section: SCHEDULE & NOTIFICATIONS Card -->
                    <div class="rounded-xl border border-border/70 bg-muted/20 p-3.5 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-foreground">
                                Schedule & Notifications
                            </span>
                            <span class="rounded-full bg-secondary/80 px-2 py-0.5 text-[10px] font-mono text-muted-foreground">
                                {{ localTimezone }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <!-- Due Date Picker -->
                            <div class="space-y-1">
                                <label for="create-due-date" class="text-xs text-muted-foreground">
                                    Due Date
                                </label>
                                <input
                                    id="create-due-date"
                                    type="date"
                                    v-model="createForm.due_date"
                                    class="border-input bg-background text-foreground h-9 w-full rounded-xl border px-3 text-xs focus-visible:outline-none focus-visible:ring-1"
                                />
                                <InputError :message="createForm.errors.due_date" />
                            </div>

                            <!-- Alarm Reminder Input -->
                            <div class="space-y-1">
                                <div class="flex items-center justify-between">
                                    <label for="create-reminder" class="text-xs text-muted-foreground">
                                        Remind Me
                                    </label>
                                    <button
                                        type="button"
                                        @click="enableCreateReminder = !enableCreateReminder"
                                        :class="[
                                            'text-[10px] rounded-full px-2 py-0.5 border transition-all duration-200',
                                            enableCreateReminder
                                                ? 'border-[#b85c38] text-[#b85c38] bg-[#b85c38]/10 font-semibold'
                                                : 'border-border text-muted-foreground'
                                        ]"
                                    >
                                        {{ enableCreateReminder ? 'Alarm On' : 'No Alarm' }}
                                    </button>
                                </div>
                                <input
                                    v-if="enableCreateReminder"
                                    id="create-reminder"
                                    type="datetime-local"
                                    v-model="createForm.reminder_at"
                                    class="border-input bg-background text-foreground h-9 w-full rounded-xl border px-3 text-xs focus-visible:outline-none focus-visible:ring-1"
                                />
                                <div v-else class="flex h-9 items-center rounded-xl border border-dashed border-border px-3 text-xs text-muted-foreground/60">
                                    No reminder set
                                </div>
                                <InputError :message="createForm.errors.reminder_at" />
                            </div>
                        </div>
                    </div>

                    <!-- Section: REPEAT TASK Card -->
                    <div class="rounded-xl border border-border/70 bg-muted/20 p-3.5 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-foreground">
                                Repeat Task
                            </span>
                            <button
                                type="button"
                                @click="enableCreateRecurrence = !enableCreateRecurrence; if (enableCreateRecurrence && createForm.recurrence === 'none') setCreateRecurrence('daily')"
                                :class="[
                                    'text-[10px] rounded-full px-2.5 py-0.5 border transition-all duration-200',
                                    enableCreateRecurrence
                                        ? 'border-[#b85c38] text-[#b85c38] bg-[#b85c38]/10 font-semibold'
                                        : 'border-border text-muted-foreground'
                                ]"
                            >
                                {{ enableCreateRecurrence ? 'Repeat Active' : 'Off' }}
                            </button>
                        </div>

                        <div v-if="enableCreateRecurrence" class="space-y-2.5 pt-1">
                            <!-- Preset Pills -->
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="rec in RECURRENCE_PRESETS"
                                    :key="rec.value"
                                    type="button"
                                    @click="setCreateRecurrence(rec.value)"
                                    :class="[
                                        'rounded-full border px-3 py-1 text-xs transition-all duration-200 active:scale-95',
                                        createForm.recurrence === rec.value && !isCreateCustomRecurrence
                                            ? 'border-[#b85c38] bg-[#b85c38] text-white font-semibold shadow-2xs'
                                            : 'border-border/80 bg-background text-foreground hover:border-border'
                                    ]"
                                >
                                    {{ rec.label }}
                                </button>
                                <button
                                    type="button"
                                    @click="setCreateRecurrence('custom')"
                                    :class="[
                                        'rounded-full border px-3 py-1 text-xs transition-all duration-200 active:scale-95',
                                        isCreateCustomRecurrence
                                            ? 'border-[#b85c38] bg-[#b85c38] text-white font-semibold shadow-2xs'
                                            : 'border-border/80 bg-background text-foreground hover:border-border'
                                    ]"
                                >
                                    Custom +
                                </button>
                            </div>

                            <!-- Custom Interval Picker -->
                            <div v-if="isCreateCustomRecurrence" class="flex items-center gap-2 rounded-xl border border-border/80 bg-background p-2 text-xs">
                                <span class="text-muted-foreground">Every</span>
                                <input
                                    type="number"
                                    min="1"
                                    max="99"
                                    v-model.number="createCustomInterval"
                                    @input="updateCreateCustomRecurrence"
                                    class="w-16 rounded-full border border-border bg-muted/30 px-2 py-1 text-center text-xs font-medium text-foreground focus-visible:outline-none focus-visible:ring-1"
                                />
                                <select
                                    v-model="createCustomUnit"
                                    @change="updateCreateCustomRecurrence"
                                    class="rounded-full border border-border bg-muted/30 px-3 py-1 text-xs font-medium text-foreground focus-visible:outline-none focus-visible:ring-1"
                                >
                                    <option value="days">Days</option>
                                    <option value="weeks">Weeks</option>
                                    <option value="months">Months</option>
                                </select>
                            </div>

                            <!-- Informative recurrence banner -->
                            <div class="rounded-lg bg-[#b85c38]/10 px-3 py-2 text-[11px] text-[#b85c38]">
                                <span class="font-semibold">{{ createRecurrenceExplainer }} (Active Rule)</span>
                                <span class="block text-muted-foreground/80 mt-0.5">Next occurrence will automatically schedule after completion.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Action Buttons -->
                    <DialogFooter class="gap-2 pt-2 border-t border-border/50">
                        <DialogClose as-child>
                            <Button type="button" variant="secondary" class="rounded-full text-xs h-9 px-5">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            :disabled="createForm.processing"
                            class="rounded-full bg-[#b85c38] text-white hover:bg-[#a34f2f] text-xs h-9 px-6 shadow-xs font-semibold"
                        >
                            Create Task
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- EDIT TASK MODAL -->
        <Dialog v-model:open="showEditDialog">
            <DialogContent class="sm:max-w-xl rounded-2xl border-border bg-card p-6 shadow-xl max-h-[90vh] overflow-y-auto">
                <form @submit.prevent="submitEdit" class="space-y-4">
                    <!-- Modal Header -->
                    <div class="flex items-start gap-3 border-b border-border/50 pb-3">
                        <div class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-[#b85c38]/10 text-[#b85c38]">
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                                <path d="m15 5 4 4"/>
                            </svg>
                        </div>
                        <div>
                            <DialogTitle class="text-base font-bold text-foreground">
                                Edit Task
                            </DialogTitle>
                            <DialogDescription class="text-xs text-muted-foreground mt-0.5">
                                Update task details, custom schedule, color, or alarm
                            </DialogDescription>
                        </div>
                    </div>

                    <!-- Field: Task Title -->
                    <div class="space-y-1.5">
                        <label for="edit-title" class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                            Task Title <span class="text-red-500">*</span>
                        </label>
                        <Input
                            id="edit-title"
                            v-model="editForm.title"
                            class="rounded-xl text-sm font-medium"
                        />
                        <InputError :message="editForm.errors.title" />
                    </div>

                    <!-- Field: Description -->
                    <div class="space-y-1.5">
                        <label for="edit-description" class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                            Description
                        </label>
                        <textarea
                            id="edit-description"
                            v-model="editForm.description"
                            rows="2"
                            class="border-input bg-background placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-xl border px-3 py-2 text-xs focus-visible:outline-none focus-visible:ring-1"
                        />
                        <InputError :message="editForm.errors.description" />
                    </div>

                    <!-- Row: Category & Accent Color -->
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <!-- Category Selector (All categories in dropdown + custom option) -->
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between">
                                <label for="edit-category" class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                                    Category
                                </label>
                                <button
                                    type="button"
                                    @click="isEditCustomCategory = !isEditCustomCategory; if (isEditCustomCategory) editCustomCategoryInput = ''"
                                    class="text-[11px] font-medium text-[#b85c38] hover:underline"
                                >
                                    {{ isEditCustomCategory ? '← Choose existing' : '+ Add custom' }}
                                </button>
                            </div>

                            <!-- Dropdown with ALL categories -->
                            <div v-if="!isEditCustomCategory" class="relative">
                                <select
                                    id="edit-category"
                                    v-model="editForm.category"
                                    @change="onEditCategorySelect"
                                    class="border-input bg-background text-foreground h-9 w-full rounded-xl border px-3 pr-8 text-xs font-medium focus-visible:outline-none focus-visible:ring-1 focus-visible:ring-[#b85c38] appearance-none cursor-pointer shadow-2xs"
                                >
                                    <option v-for="cat in availableCategories" :key="cat" :value="cat">
                                        {{ cat }}
                                    </option>
                                    <option value="__custom__">+ Add new custom category...</option>
                                </select>
                                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-2.5 text-muted-foreground">
                                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="m6 9 6 6 6-6"/>
                                    </svg>
                                </div>
                            </div>

                            <!-- Input for adding a new custom category -->
                            <div v-else class="space-y-1">
                                <Input
                                    id="edit-custom-category"
                                    v-model="editCustomCategoryInput"
                                    placeholder="Type new category name..."
                                    class="rounded-xl text-xs h-9 px-3"
                                    autofocus
                                />
                            </div>
                            <InputError :message="editForm.errors.category" />
                        </div>

                        <!-- Accent Color Swatches -->
                        <div class="space-y-1.5">
                            <label class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                                Accent Color
                            </label>
                            <div class="flex flex-wrap items-center gap-2 pt-1">
                                <button
                                    v-for="c in COLOR_PALETTE"
                                    :key="c.value"
                                    type="button"
                                    @click="editForm.color = c.value"
                                    :title="c.name"
                                    class="relative flex h-6 w-6 items-center justify-center rounded-full transition-transform hover:scale-110 active:scale-95"
                                    :style="{ backgroundColor: c.value }"
                                >
                                    <span
                                        v-if="editForm.color === c.value"
                                        class="h-2 w-2 rounded-full bg-white ring-1 ring-black/20"
                                    />
                                </button>
                            </div>
                            <InputError :message="editForm.errors.color" />
                        </div>
                    </div>

                    <!-- Section: SCHEDULE & NOTIFICATIONS Card -->
                    <div class="rounded-xl border border-border/70 bg-muted/20 p-3.5 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-foreground">
                                Schedule & Notifications
                            </span>
                            <span class="rounded-full bg-secondary/80 px-2 py-0.5 text-[10px] font-mono text-muted-foreground">
                                {{ localTimezone }}
                            </span>
                        </div>

                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <!-- Due Date Picker -->
                            <div class="space-y-1">
                                <label for="edit-due-date" class="text-xs text-muted-foreground">
                                    Due Date
                                </label>
                                <input
                                    id="edit-due-date"
                                    type="date"
                                    v-model="editForm.due_date"
                                    class="border-input bg-background text-foreground h-9 w-full rounded-xl border px-3 text-xs focus-visible:outline-none focus-visible:ring-1"
                                />
                                <InputError :message="editForm.errors.due_date" />
                            </div>

                            <!-- Alarm Reminder Input -->
                            <div class="space-y-1">
                                <div class="flex items-center justify-between">
                                    <label for="edit-reminder" class="text-xs text-muted-foreground">
                                        Remind Me
                                    </label>
                                    <button
                                        type="button"
                                        @click="enableEditReminder = !enableEditReminder"
                                        :class="[
                                            'text-[10px] rounded-full px-2 py-0.5 border transition-all duration-200',
                                            enableEditReminder
                                                ? 'border-[#b85c38] text-[#b85c38] bg-[#b85c38]/10 font-semibold'
                                                : 'border-border text-muted-foreground'
                                        ]"
                                    >
                                        {{ enableEditReminder ? 'Alarm On' : 'No Alarm' }}
                                    </button>
                                </div>
                                <input
                                    v-if="enableEditReminder"
                                    id="edit-reminder"
                                    type="datetime-local"
                                    v-model="editForm.reminder_at"
                                    class="border-input bg-background text-foreground h-9 w-full rounded-xl border px-3 text-xs focus-visible:outline-none focus-visible:ring-1"
                                />
                                <div v-else class="flex h-9 items-center rounded-xl border border-dashed border-border px-3 text-xs text-muted-foreground/60">
                                    No reminder set
                                </div>
                                <InputError :message="editForm.errors.reminder_at" />
                            </div>
                        </div>
                    </div>

                    <!-- Section: REPEAT TASK Card -->
                    <div class="rounded-xl border border-border/70 bg-muted/20 p-3.5 space-y-3">
                        <div class="flex items-center justify-between">
                            <span class="text-[11px] font-semibold uppercase tracking-wider text-foreground">
                                Repeat Task
                            </span>
                            <button
                                type="button"
                                @click="enableEditRecurrence = !enableEditRecurrence; if (enableEditRecurrence && editForm.recurrence === 'none') setEditRecurrence('daily')"
                                :class="[
                                    'text-[10px] rounded-full px-2.5 py-0.5 border transition-all duration-200',
                                    enableEditRecurrence
                                        ? 'border-[#b85c38] text-[#b85c38] bg-[#b85c38]/10 font-semibold'
                                        : 'border-border text-muted-foreground'
                                ]"
                            >
                                {{ enableEditRecurrence ? 'Repeat Active' : 'Off' }}
                            </button>
                        </div>

                        <div v-if="enableEditRecurrence" class="space-y-2.5 pt-1">
                            <!-- Preset Pills -->
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="rec in RECURRENCE_PRESETS"
                                    :key="rec.value"
                                    type="button"
                                    @click="setEditRecurrence(rec.value)"
                                    :class="[
                                        'rounded-full border px-3 py-1 text-xs transition-all duration-200 active:scale-95',
                                        editForm.recurrence === rec.value && !isEditCustomRecurrence
                                            ? 'border-[#b85c38] bg-[#b85c38] text-white font-semibold shadow-2xs'
                                            : 'border-border/80 bg-background text-foreground hover:border-border'
                                    ]"
                                >
                                    {{ rec.label }}
                                </button>
                                <button
                                    type="button"
                                    @click="setEditRecurrence('custom')"
                                    :class="[
                                        'rounded-full border px-3 py-1 text-xs transition-all duration-200 active:scale-95',
                                        isEditCustomRecurrence
                                            ? 'border-[#b85c38] bg-[#b85c38] text-white font-semibold shadow-2xs'
                                            : 'border-border/80 bg-background text-foreground hover:border-border'
                                    ]"
                                >
                                    Custom +
                                </button>
                            </div>

                            <!-- Custom Interval Picker -->
                            <div v-if="isEditCustomRecurrence" class="flex items-center gap-2 rounded-xl border border-border/80 bg-background p-2 text-xs">
                                <span class="text-muted-foreground">Every</span>
                                <input
                                    type="number"
                                    min="1"
                                    max="99"
                                    v-model.number="editCustomInterval"
                                    @input="updateEditCustomRecurrence"
                                    class="w-16 rounded-full border border-border bg-muted/30 px-2 py-1 text-center text-xs font-medium text-foreground focus-visible:outline-none focus-visible:ring-1"
                                />
                                <select
                                    v-model="editCustomUnit"
                                    @change="updateEditCustomRecurrence"
                                    class="rounded-full border border-border bg-muted/30 px-3 py-1 text-xs font-medium text-foreground focus-visible:outline-none focus-visible:ring-1"
                                >
                                    <option value="days">Days</option>
                                    <option value="weeks">Weeks</option>
                                    <option value="months">Months</option>
                                </select>
                            </div>

                            <!-- Explainer banner -->
                            <div class="rounded-lg bg-[#b85c38]/10 px-3 py-2 text-[11px] text-[#b85c38]">
                                <span class="font-semibold">{{ editRecurrenceExplainer }} (Active Rule)</span>
                                <span class="block text-muted-foreground/80 mt-0.5">Next occurrence will automatically schedule after completion.</span>
                            </div>
                        </div>
                    </div>

                    <!-- Footer Action Buttons -->
                    <DialogFooter class="gap-2 pt-2 border-t border-border/50">
                        <DialogClose as-child>
                            <Button type="button" variant="secondary" class="rounded-full text-xs h-9 px-5">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            :disabled="editForm.processing"
                            class="rounded-full bg-[#b85c38] text-white hover:bg-[#a34f2f] text-xs h-9 px-6 shadow-xs font-semibold"
                        >
                            Save Changes
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- DELETE CONFIRMATION MODAL -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent class="sm:max-w-md rounded-2xl border-border bg-card p-6 shadow-xl">
                <DialogHeader>
                    <DialogTitle class="text-base font-bold text-foreground">Delete Task</DialogTitle>
                    <DialogDescription class="text-xs text-muted-foreground mt-1">
                        Are you sure you want to delete <span class="font-semibold text-foreground">"{{ deletingTodo?.title }}"</span>? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 pt-4">
                    <DialogClose as-child>
                        <Button variant="secondary" class="rounded-full text-xs h-9 px-5">Cancel</Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        class="rounded-full text-xs h-9 px-6 shadow-xs font-semibold"
                        @click="submitDelete"
                    >
                        Delete Task
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </div>
</template>
