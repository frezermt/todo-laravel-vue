<script setup lang="ts">
import { Head, useForm, router } from '@inertiajs/vue3';
import { ref, computed, onMounted, onUnmounted } from 'vue';
import { toast } from 'vue-sonner';
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
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

const props = withDefaults(defineProps<{
    todos?: Todo[];
}>(), {
    todos: () => [],
});

const safeTodos = computed(() => props.todos || []);

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Todos',
                href: '/todos',
            },
        ],
    },
});

// Curated Palette (Earth/editorial tones matching design constraints)
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

const DEFAULT_CATEGORIES = ['Work', 'Personal', 'Study', 'Finance', 'Health', 'Projects'];

const availableCategories = computed(() => {
    const set = new Set(DEFAULT_CATEGORIES);
    safeTodos.value.forEach(t => {
        if (t.category && t.category.trim()) {
            set.add(t.category.trim());
        }
    });
    return Array.from(set);
});

const RECURRENCE_PRESETS = [
    { label: 'No repeat', value: 'none' },
    { label: 'Daily', value: 'daily' },
    { label: 'Weekdays', value: 'weekdays' },
    { label: 'Weekly', value: 'weekly' },
    { label: 'Every 2 wks', value: 'biweekly' },
    { label: 'Monthly', value: 'monthly' },
];

// View mode: Cards vs Calendar
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

// Create Form & Dialog
const showCreateDialog = ref(false);
const customCategoryInput = ref('');
const isAddingCustomCategory = ref(false);
const enableCreateReminder = ref(false);

const isCreateCustomRecurrence = ref(false);
const createCustomInterval = ref(3);
const createCustomUnit = ref('days');

const createForm = useForm({
    title: '',
    description: '',
    category: '',
    color: '#b85c38',
    due_date: '',
    recurrence: 'none',
    reminder_at: '',
});

function openCreateModal(defaultDueDate?: string) {
    createForm.reset();
    createForm.color = '#b85c38';
    createForm.recurrence = 'none';
    createForm.reminder_at = '';
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

function setCreateRecurrence(val: string) {
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

function submitCreate() {
    if (isAddingCustomCategory.value && customCategoryInput.value.trim()) {
        createForm.category = customCategoryInput.value.trim();
    }
    if (!enableCreateReminder.value) {
        createForm.reminder_at = '';
    }
    if (isCreateCustomRecurrence.value) {
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
    editForm.category = todo.category ?? '';
    editForm.color = todo.color ?? '#b85c38';
    editForm.due_date = todo.due_date ?? '';
    
    // Recurrence parsing
    const rec = todo.recurrence ?? 'none';
    if (rec.startsWith('custom:')) {
        isEditCustomRecurrence.value = true;
        const parts = rec.split(':');
        editCustomInterval.value = parseInt(parts[1]) || 3;
        editCustomUnit.value = parts[2] || 'days';
        editForm.recurrence = rec;
    } else {
        isEditCustomRecurrence.value = false;
        editForm.recurrence = rec;
    }

    editForm.reminder_at = todo.reminder_at ? todo.reminder_at.substring(0, 16) : '';
    enableEditReminder.value = !!todo.reminder_at;
    isEditCustomCategory.value = false;
    editCustomCategoryInput.value = '';
    showEditDialog.value = true;
}

function setEditRecurrence(val: string) {
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

function submitEdit() {
    if (!editingTodo.value) return;
    if (isEditCustomCategory.value && editCustomCategoryInput.value.trim()) {
        editForm.category = editCustomCategoryInput.value.trim();
    }
    if (!enableEditReminder.value) {
        editForm.reminder_at = '';
    }
    if (isEditCustomRecurrence.value) {
        editForm.recurrence = `custom:${editCustomInterval.value || 1}:${editCustomUnit.value}`;
    }
    const taskTitle = editForm.title;
    editForm.put(`/todos/${editingTodo.value.id}`, {
        preserveScroll: true,
        onSuccess: () => {
            toast.success('Task updated successfully', {
                description: `Changes to "${taskTitle}" have been saved.`,
            });
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
                    // notification error fallback
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
        // Trigger if reminder is due now or within the last 15 minutes and hasn't fired yet
        if (reminderTime <= now && now - reminderTime < 15 * 60 * 1000) {
            if (!alertedReminders.value.has(todo.id)) {
                alertedReminders.value.add(todo.id);

                playAlarmChime();

                // In-app Splash Toast
                toast.warning(`Deadline Alarm: ${todo.title}`, {
                    description: todo.description ? `${todo.description}` : 'Scheduled deadline / reminder has arrived!',
                    duration: 10000,
                });

                // Native Desktop Notification outside the browser
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

onMounted(() => {
    updateNotificationPermission();
    checkReminders();
    reminderInterval = window.setInterval(checkReminders, 25000);
});

onUnmounted(() => {
    if (reminderInterval) {
        clearInterval(reminderInterval);
    }
});
</script>

<template>
    <Head title="Todos" />

    <div class="flex h-full flex-1 flex-col gap-6 p-4 md:p-8">

        <!-- Top Header & Actions -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-serif text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                    Tasks
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    {{ stats.active }} active{{ stats.total > 0 ? ` · ${stats.completed} completed` : '' }}
                </p>
            </div>

            <!-- Pill-shaped Interactive Header Buttons -->
            <div class="flex flex-wrap items-center gap-2.5">
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

                <!-- View Switcher Segmented Control (Smooth Pill) -->
                <div class="inline-flex rounded-full border border-border bg-muted/30 p-1 shadow-2xs">
                    <button
                        type="button"
                        @click="viewMode = 'cards'"
                        :class="[
                            'inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-medium transition-all duration-200 ease-out active:scale-95',
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
                            'inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-medium transition-all duration-200 ease-out active:scale-95',
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

                <!-- Add Task Pill Button -->
                <button
                    type="button"
                    @click="openCreateModal()"
                    class="inline-flex items-center gap-2 rounded-full bg-[#b85c38] px-5 py-2 text-xs font-medium text-white transition-all duration-200 ease-out hover:bg-[#a34f2f] hover:shadow-sm active:scale-95 shadow-xs"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    New Task
                </button>
            </div>
        </div>

        <!-- Task Overview Section (Simple Pie Charts for Category Distribution & Completion) -->
        <TaskOverview :todos="props.todos" />

        <!-- Filters Section (Status Tabs + Category Pills) -->
        <div class="flex flex-col gap-3 border-y border-border/70 py-3 sm:flex-row sm:items-center sm:justify-between">
            <!-- Status Pill Tabs -->
            <div class="flex items-center gap-1.5">
                <button
                    v-for="filter in (['all', 'active', 'completed'] as const)"
                    :key="filter"
                    type="button"
                    @click="activeFilter = filter"
                    :class="[
                        'rounded-full px-3.5 py-1 text-xs font-medium transition-all duration-200 ease-out active:scale-95',
                        activeFilter === filter
                            ? 'bg-secondary font-semibold text-foreground shadow-2xs'
                            : 'text-muted-foreground hover:text-foreground hover:bg-secondary/40'
                    ]"
                >
                    {{ filter.charAt(0).toUpperCase() + filter.slice(1) }}
                    <span class="ml-1 text-[11px] opacity-70">
                        {{ filter === 'all' ? stats.total : filter === 'active' ? stats.active : stats.completed }}
                    </span>
                </button>
            </div>

            <!-- Category Filter Pills -->
            <div class="flex flex-wrap items-center gap-1.5">
                <button
                    type="button"
                    @click="activeCategory = null"
                    :class="[
                        'rounded-full border px-3 py-1 text-xs font-medium transition-all duration-200 ease-out active:scale-95',
                        activeCategory === null
                            ? 'border-[#b85c38] bg-[#b85c38]/10 font-semibold text-[#b85c38]'
                            : 'border-border/80 text-muted-foreground hover:border-border hover:text-foreground'
                    ]"
                >
                    All categories
                </button>

                <button
                    v-for="cat in availableCategories"
                    :key="cat"
                    type="button"
                    @click="activeCategory = activeCategory === cat ? null : cat"
                    :class="[
                        'rounded-full border px-3 py-1 text-xs font-medium transition-all duration-200 ease-out active:scale-95',
                        activeCategory === cat
                            ? 'border-[#b85c38] bg-[#b85c38]/10 font-semibold text-[#b85c38]'
                            : 'border-border/80 text-muted-foreground hover:border-border hover:text-foreground'
                    ]"
                >
                    {{ cat }}
                </button>
            </div>
        </div>

        <!-- MAIN VIEW: CARDS VIEW -->
        <div v-if="viewMode === 'cards'" class="flex-1">
            <!-- Empty state -->
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
                    Create task
                </button>
            </div>

            <!-- Task Cards Grid -->
            <div
                v-else
                class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3"
            >
                <TaskCard
                    v-for="todo in filteredTodos"
                    :key="todo.id"
                    :todo="todo"
                    @toggle="toggleCompleted"
                    @edit="openEdit"
                    @delete="openDelete"
                />
            </div>
        </div>

        <!-- MAIN VIEW: CALENDAR VIEW -->
        <div v-else class="flex-1">
            <TaskCalendar
                :todos="props.todos"
                @toggle="toggleCompleted"
                @edit="openEdit"
                @delete="openDelete"
                @create-for-date="openCreateModal"
            />
        </div>

        <!-- Create Task Dialog -->
        <Dialog v-model:open="showCreateDialog">
            <DialogContent class="sm:max-w-lg rounded-2xl">
                <form @submit.prevent="submitCreate" class="space-y-4">
                    <DialogHeader>
                        <DialogTitle class="font-serif text-lg font-bold">Create New Task</DialogTitle>
                        <DialogDescription class="text-xs">
                            Organize your task with category, custom recurrence, color tag, and deadline alarm.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-3.5">
                        <!-- Title -->
                        <div class="grid gap-1.5">
                            <Label for="create-title" class="text-xs font-medium">Title</Label>
                            <Input
                                id="create-title"
                                v-model="createForm.title"
                                placeholder="What needs to be done?"
                                autofocus
                                class="rounded-xl text-sm"
                            />
                            <InputError :message="createForm.errors.title" />
                        </div>

                        <!-- Description -->
                        <div class="grid gap-1.5">
                            <Label for="create-description" class="text-xs font-medium">
                                Description <span class="text-muted-foreground font-normal">(optional)</span>
                            </Label>
                            <textarea
                                id="create-description"
                                v-model="createForm.description"
                                placeholder="Add notes, checklists, or context..."
                                rows="2"
                                class="border-input bg-background placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-xl border px-3 py-2 text-xs focus-visible:outline-none focus-visible:ring-1 disabled:cursor-not-allowed disabled:opacity-50"
                            />
                            <InputError :message="createForm.errors.description" />
                        </div>

                        <!-- Category Selector (Pill shaped) -->
                        <div class="grid gap-1.5">
                            <Label class="text-xs font-medium">Category</Label>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="cat in DEFAULT_CATEGORIES"
                                    :key="cat"
                                    type="button"
                                    @click="createForm.category = createForm.category === cat ? '' : cat; isAddingCustomCategory = false"
                                    :class="[
                                        'rounded-full border px-3 py-1 text-xs transition-all duration-200 ease-out active:scale-95',
                                        createForm.category === cat && !isAddingCustomCategory
                                            ? 'border-[#b85c38] bg-[#b85c38]/10 font-semibold text-[#b85c38]'
                                            : 'border-border/80 bg-secondary/30 text-muted-foreground hover:text-foreground'
                                    ]"
                                >
                                    {{ cat }}
                                </button>
                                <button
                                    type="button"
                                    @click="isAddingCustomCategory = !isAddingCustomCategory; if (isAddingCustomCategory) createForm.category = ''"
                                    :class="[
                                        'rounded-full border border-dashed px-3 py-1 text-xs transition-all duration-200 ease-out active:scale-95',
                                        isAddingCustomCategory
                                            ? 'border-[#b85c38] bg-[#b85c38]/10 text-[#b85c38]'
                                            : 'border-border text-muted-foreground hover:text-foreground'
                                    ]"
                                >
                                    + Custom
                                </button>
                            </div>
                            <div v-if="isAddingCustomCategory" class="mt-1">
                                <Input
                                    v-model="customCategoryInput"
                                    placeholder="Enter custom category name..."
                                    class="rounded-full text-xs h-8 px-3"
                                />
                            </div>
                            <InputError :message="createForm.errors.category" />
                        </div>

                        <!-- Customizable Recurrence Schedule Selector -->
                        <div class="grid gap-1.5">
                            <Label class="text-xs font-medium">Recurring Schedule</Label>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="rec in RECURRENCE_PRESETS"
                                    :key="rec.value"
                                    type="button"
                                    @click="setCreateRecurrence(rec.value)"
                                    :class="[
                                        'rounded-full border px-3 py-1 text-xs transition-all duration-200 ease-out active:scale-95',
                                        createForm.recurrence === rec.value && !isCreateCustomRecurrence
                                            ? 'border-[#b85c38] bg-[#b85c38]/10 font-semibold text-[#b85c38]'
                                            : 'border-border/80 bg-secondary/30 text-muted-foreground hover:text-foreground'
                                    ]"
                                >
                                    {{ rec.label }}
                                </button>
                                <button
                                    type="button"
                                    @click="setCreateRecurrence('custom')"
                                    :class="[
                                        'rounded-full border px-3 py-1 text-xs transition-all duration-200 ease-out active:scale-95',
                                        isCreateCustomRecurrence
                                            ? 'border-[#b85c38] bg-[#b85c38]/10 font-semibold text-[#b85c38]'
                                            : 'border-border/80 bg-secondary/30 text-muted-foreground hover:text-foreground'
                                    ]"
                                >
                                    ⚙ Custom...
                                </button>
                            </div>

                            <!-- Custom Interval & Unit Picker -->
                            <div v-if="isCreateCustomRecurrence" class="mt-2 flex items-center gap-2 rounded-xl border border-border/80 bg-secondary/20 p-2 text-xs">
                                <span class="text-muted-foreground">Every</span>
                                <input
                                    type="number"
                                    min="1"
                                    max="99"
                                    v-model.number="createCustomInterval"
                                    @input="updateCreateCustomRecurrence"
                                    class="w-16 rounded-full border border-border bg-background px-2 py-1 text-center text-xs font-medium text-foreground focus-visible:outline-none focus-visible:ring-1"
                                />
                                <select
                                    v-model="createCustomUnit"
                                    @change="updateCreateCustomRecurrence"
                                    class="rounded-full border border-border bg-background px-3 py-1 text-xs font-medium text-foreground focus-visible:outline-none focus-visible:ring-1"
                                >
                                    <option value="days">Days</option>
                                    <option value="weeks">Weeks</option>
                                    <option value="months">Months</option>
                                </select>
                                <span class="ml-auto text-[11px] text-muted-foreground font-medium">
                                    Repeats every {{ createCustomInterval }} {{ createCustomUnit }}
                                </span>
                            </div>
                            <InputError :message="createForm.errors.recurrence" />
                        </div>

                        <!-- Color Choice Swatches -->
                        <div class="grid gap-1.5">
                            <Label class="text-xs font-medium">Color Tag</Label>
                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    v-for="c in COLOR_PALETTE"
                                    :key="c.value"
                                    type="button"
                                    @click="createForm.color = c.value"
                                    :aria-label="`Select ${c.name} color`"
                                    :title="c.name"
                                    class="relative flex h-6 w-6 items-center justify-center rounded-full transition-transform duration-200 ease-out hover:scale-110 active:scale-95 ring-offset-background"
                                    :style="{ backgroundColor: c.value }"
                                >
                                    <svg
                                        v-if="createForm.color === c.value"
                                        class="h-3 w-3 stroke-white stroke-[2.5]"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                </button>
                            </div>
                            <InputError :message="createForm.errors.color" />
                        </div>

                        <!-- Due Date & Deadline Alarm / Reminder -->
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="grid gap-1.5">
                                <Label for="create-due-date" class="text-xs font-medium">Due Date</Label>
                                <input
                                    id="create-due-date"
                                    type="date"
                                    v-model="createForm.due_date"
                                    class="border-input bg-background text-foreground h-9 rounded-xl border px-3 text-xs focus-visible:outline-none focus-visible:ring-1"
                                />
                                <InputError :message="createForm.errors.due_date" />
                            </div>

                            <div class="grid gap-1.5">
                                <div class="flex items-center justify-between">
                                    <Label for="create-reminder" class="text-xs font-medium">Alarm Reminder</Label>
                                    <button
                                        type="button"
                                        @click="enableCreateReminder = !enableCreateReminder"
                                        :class="[
                                            'text-[11px] rounded-full px-2.5 py-0.5 border transition-all duration-200',
                                            enableCreateReminder
                                                ? 'border-[#b85c38] text-[#b85c38] bg-[#b85c38]/10 font-medium'
                                                : 'border-border text-muted-foreground'
                                        ]"
                                    >
                                        {{ enableCreateReminder ? 'Enabled' : 'Disabled' }}
                                    </button>
                                </div>
                                <input
                                    v-if="enableCreateReminder"
                                    id="create-reminder"
                                    type="datetime-local"
                                    v-model="createForm.reminder_at"
                                    class="border-input bg-background text-foreground h-9 rounded-xl border px-3 text-xs focus-visible:outline-none focus-visible:ring-1"
                                />
                                <div v-else class="flex h-9 items-center rounded-xl border border-dashed border-border px-3 text-xs text-muted-foreground/60">
                                    No alarm set
                                </div>
                                <InputError :message="createForm.errors.reminder_at" />
                            </div>
                        </div>
                    </div>

                    <DialogFooter class="gap-2 pt-2">
                        <DialogClose as-child>
                            <Button type="button" variant="secondary" class="rounded-full text-xs h-9 px-5">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            :disabled="createForm.processing"
                            class="rounded-full bg-[#b85c38] text-white hover:bg-[#a34f2f] text-xs h-9 px-6 shadow-xs"
                        >
                            Create Task
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Edit Task Dialog -->
        <Dialog v-model:open="showEditDialog">
            <DialogContent class="sm:max-w-lg rounded-2xl">
                <form @submit.prevent="submitEdit" class="space-y-4">
                    <DialogHeader>
                        <DialogTitle class="font-serif text-lg font-bold">Edit Task</DialogTitle>
                        <DialogDescription class="text-xs">
                            Update task details, custom recurrence schedule, color tag, or deadline reminder.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-3.5">
                        <!-- Title -->
                        <div class="grid gap-1.5">
                            <Label for="edit-title" class="text-xs font-medium">Title</Label>
                            <Input
                                id="edit-title"
                                v-model="editForm.title"
                                placeholder="What needs to be done?"
                                class="rounded-xl text-sm"
                            />
                            <InputError :message="editForm.errors.title" />
                        </div>

                        <!-- Description -->
                        <div class="grid gap-1.5">
                            <Label for="edit-description" class="text-xs font-medium">
                                Description <span class="text-muted-foreground font-normal">(optional)</span>
                            </Label>
                            <textarea
                                id="edit-description"
                                v-model="editForm.description"
                                placeholder="Add notes, checklists, or context..."
                                rows="2"
                                class="border-input bg-background placeholder:text-muted-foreground focus-visible:ring-ring flex w-full rounded-xl border px-3 py-2 text-xs focus-visible:outline-none focus-visible:ring-1 disabled:cursor-not-allowed disabled:opacity-50"
                            />
                            <InputError :message="editForm.errors.description" />
                        </div>

                        <!-- Category Selector -->
                        <div class="grid gap-1.5">
                            <Label class="text-xs font-medium">Category</Label>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="cat in DEFAULT_CATEGORIES"
                                    :key="cat"
                                    type="button"
                                    @click="editForm.category = editForm.category === cat ? '' : cat; isEditCustomCategory = false"
                                    :class="[
                                        'rounded-full border px-3 py-1 text-xs transition-all duration-200 ease-out active:scale-95',
                                        editForm.category === cat && !isEditCustomCategory
                                            ? 'border-[#b85c38] bg-[#b85c38]/10 font-semibold text-[#b85c38]'
                                            : 'border-border/80 bg-secondary/30 text-muted-foreground hover:text-foreground'
                                    ]"
                                >
                                    {{ cat }}
                                </button>
                                <button
                                    type="button"
                                    @click="isEditCustomCategory = !isEditCustomCategory; if (isEditCustomCategory) editForm.category = ''"
                                    :class="[
                                        'rounded-full border border-dashed px-3 py-1 text-xs transition-all duration-200 ease-out active:scale-95',
                                        isEditCustomCategory
                                            ? 'border-[#b85c38] bg-[#b85c38]/10 text-[#b85c38]'
                                            : 'border-border text-muted-foreground hover:text-foreground'
                                    ]"
                                >
                                    + Custom
                                </button>
                            </div>
                            <div v-if="isEditCustomCategory" class="mt-1">
                                <Input
                                    v-model="editCustomCategoryInput"
                                    placeholder="Enter custom category name..."
                                    class="rounded-full text-xs h-8 px-3"
                                />
                            </div>
                            <InputError :message="editForm.errors.category" />
                        </div>

                        <!-- Customizable Recurrence Selector -->
                        <div class="grid gap-1.5">
                            <Label class="text-xs font-medium">Recurring Schedule</Label>
                            <div class="flex flex-wrap gap-1.5">
                                <button
                                    v-for="rec in RECURRENCE_PRESETS"
                                    :key="rec.value"
                                    type="button"
                                    @click="setEditRecurrence(rec.value)"
                                    :class="[
                                        'rounded-full border px-3 py-1 text-xs transition-all duration-200 ease-out active:scale-95',
                                        editForm.recurrence === rec.value && !isEditCustomRecurrence
                                            ? 'border-[#b85c38] bg-[#b85c38]/10 font-semibold text-[#b85c38]'
                                            : 'border-border/80 bg-secondary/30 text-muted-foreground hover:text-foreground'
                                    ]"
                                >
                                    {{ rec.label }}
                                </button>
                                <button
                                    type="button"
                                    @click="setEditRecurrence('custom')"
                                    :class="[
                                        'rounded-full border px-3 py-1 text-xs transition-all duration-200 ease-out active:scale-95',
                                        isEditCustomRecurrence
                                            ? 'border-[#b85c38] bg-[#b85c38]/10 font-semibold text-[#b85c38]'
                                            : 'border-border/80 bg-secondary/30 text-muted-foreground hover:text-foreground'
                                    ]"
                                >
                                    ⚙ Custom...
                                </button>
                            </div>

                            <!-- Custom Interval & Unit Picker -->
                            <div v-if="isEditCustomRecurrence" class="mt-2 flex items-center gap-2 rounded-xl border border-border/80 bg-secondary/20 p-2 text-xs">
                                <span class="text-muted-foreground">Every</span>
                                <input
                                    type="number"
                                    min="1"
                                    max="99"
                                    v-model.number="editCustomInterval"
                                    @input="updateEditCustomRecurrence"
                                    class="w-16 rounded-full border border-border bg-background px-2 py-1 text-center text-xs font-medium text-foreground focus-visible:outline-none focus-visible:ring-1"
                                />
                                <select
                                    v-model="editCustomUnit"
                                    @change="updateEditCustomRecurrence"
                                    class="rounded-full border border-border bg-background px-3 py-1 text-xs font-medium text-foreground focus-visible:outline-none focus-visible:ring-1"
                                >
                                    <option value="days">Days</option>
                                    <option value="weeks">Weeks</option>
                                    <option value="months">Months</option>
                                </select>
                                <span class="ml-auto text-[11px] text-muted-foreground font-medium">
                                    Repeats every {{ editCustomInterval }} {{ editCustomUnit }}
                                </span>
                            </div>
                            <InputError :message="editForm.errors.recurrence" />
                        </div>

                        <!-- Color Choice Swatches -->
                        <div class="grid gap-1.5">
                            <Label class="text-xs font-medium">Color Tag</Label>
                            <div class="flex flex-wrap items-center gap-2">
                                <button
                                    v-for="c in COLOR_PALETTE"
                                    :key="c.value"
                                    type="button"
                                    @click="editForm.color = c.value"
                                    :aria-label="`Select ${c.name} color`"
                                    :title="c.name"
                                    class="relative flex h-6 w-6 items-center justify-center rounded-full transition-transform duration-200 ease-out hover:scale-110 active:scale-95 ring-offset-background"
                                    :style="{ backgroundColor: c.value }"
                                >
                                    <svg
                                        v-if="editForm.color === c.value"
                                        class="h-3 w-3 stroke-white stroke-[2.5]"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke-linecap="round"
                                        stroke-linejoin="round"
                                    >
                                        <polyline points="20 6 9 17 4 12"/>
                                    </svg>
                                </button>
                            </div>
                            <InputError :message="editForm.errors.color" />
                        </div>

                        <!-- Due Date & Reminder -->
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                            <div class="grid gap-1.5">
                                <Label for="edit-due-date" class="text-xs font-medium">Due Date</Label>
                                <input
                                    id="edit-due-date"
                                    type="date"
                                    v-model="editForm.due_date"
                                    class="border-input bg-background text-foreground h-9 rounded-xl border px-3 text-xs focus-visible:outline-none focus-visible:ring-1"
                                />
                                <InputError :message="editForm.errors.due_date" />
                            </div>

                            <div class="grid gap-1.5">
                                <div class="flex items-center justify-between">
                                    <Label for="edit-reminder" class="text-xs font-medium">Alarm Reminder</Label>
                                    <button
                                        type="button"
                                        @click="enableEditReminder = !enableEditReminder"
                                        :class="[
                                            'text-[11px] rounded-full px-2.5 py-0.5 border transition-all duration-200',
                                            enableEditReminder
                                                ? 'border-[#b85c38] text-[#b85c38] bg-[#b85c38]/10 font-medium'
                                                : 'border-border text-muted-foreground'
                                        ]"
                                    >
                                        {{ enableEditReminder ? 'Enabled' : 'Disabled' }}
                                    </button>
                                </div>
                                <input
                                    v-if="enableEditReminder"
                                    id="edit-reminder"
                                    type="datetime-local"
                                    v-model="editForm.reminder_at"
                                    class="border-input bg-background text-foreground h-9 rounded-xl border px-3 text-xs focus-visible:outline-none focus-visible:ring-1"
                                />
                                <div v-else class="flex h-9 items-center rounded-xl border border-dashed border-border px-3 text-xs text-muted-foreground/60">
                                    No alarm set
                                </div>
                                <InputError :message="editForm.errors.reminder_at" />
                            </div>
                        </div>
                    </div>

                    <DialogFooter class="gap-2 pt-2">
                        <DialogClose as-child>
                            <Button type="button" variant="secondary" class="rounded-full text-xs h-9 px-5">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button
                            type="submit"
                            :disabled="editForm.processing"
                            class="rounded-full bg-[#b85c38] text-white hover:bg-[#a34f2f] text-xs h-9 px-6 shadow-xs"
                        >
                            Save Changes
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <!-- Delete Confirmation Dialog -->
        <Dialog v-model:open="showDeleteDialog">
            <DialogContent class="sm:max-w-md rounded-2xl">
                <DialogHeader>
                    <DialogTitle class="font-serif text-base font-bold">Delete Task</DialogTitle>
                    <DialogDescription class="text-xs">
                        Are you sure you want to delete <span class="font-medium text-foreground">"{{ deletingTodo?.title }}"</span>? This action cannot be undone.
                    </DialogDescription>
                </DialogHeader>
                <DialogFooter class="gap-2 pt-2">
                    <DialogClose as-child>
                        <Button variant="secondary" class="rounded-full text-xs h-9 px-5">Cancel</Button>
                    </DialogClose>
                    <Button
                        variant="destructive"
                        class="rounded-full text-xs h-9 px-6 shadow-xs"
                        @click="submitDelete"
                    >
                        Delete Task
                    </Button>
                </DialogFooter>
            </DialogContent>
        </Dialog>

    </div>
</template>
