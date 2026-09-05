<script setup lang="ts">
import { computed } from 'vue';
import TaskCard, { type Todo } from '@/components/todos/TaskCard.vue';

const props = defineProps<{
    todos: Todo[];
}>();

const emit = defineEmits<{
    (e: 'create', defaultDueDate?: string): void;
    (e: 'select', todo: Todo): void;
    (e: 'toggle', todo: Todo): void;
    (e: 'edit', todo: Todo): void;
    (e: 'delete', todo: Todo): void;
}>();

// Helper for today's midnight timestamp
const todayMidnight = computed(() => {
    const now = new Date();
    now.setHours(0, 0, 0, 0);
    return now.getTime();
});

// Group upcoming active tasks by chronological periods
interface DateGroup {
    key: string;
    title: string;
    dateStr: string;
    count: number;
    tasks: Todo[];
}

const activeTodos = computed(() => props.todos.filter(t => !t.completed));

const groupedUpcoming = computed<DateGroup[]>(() => {
    const groups: Record<string, { title: string; dateStr: string; tasks: Todo[] }> = {};

    const todayDate = new Date();
    todayDate.setHours(0, 0, 0, 0);

    const tomorrowDate = new Date(todayDate);
    tomorrowDate.setDate(tomorrowDate.getDate() + 1);

    const nextWeekDate = new Date(todayDate);
    nextWeekDate.setDate(nextWeekDate.getDate() + 7);

    // Sort todos by due_date ascending
    const sorted = [...activeTodos.value].sort((a, b) => {
        if (!a.due_date) return 1;
        if (!b.due_date) return -1;
        return a.due_date.localeCompare(b.due_date);
    });

    sorted.forEach(todo => {
        if (!todo.due_date) {
            const key = 'no_date';
            if (!groups[key]) {
                groups[key] = {
                    title: 'NO DUE DATE · SOMEDAY',
                    dateStr: '',
                    tasks: [],
                };
            }
            groups[key].tasks.push(todo);
            return;
        }

        const [y, m, d] = todo.due_date.split('-').map(Number);
        const taskDate = new Date(y, m - 1, d);
        taskDate.setHours(0, 0, 0, 0);

        const diffDays = Math.round((taskDate.getTime() - todayDate.getTime()) / (1000 * 60 * 60 * 24));

        let groupKey = '';
        let groupTitle = '';

        if (diffDays <= 0) {
            groupKey = 'today';
            const formatted = taskDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }).toUpperCase();
            groupTitle = `TODAY — ${formatted}`;
        } else if (diffDays === 1) {
            groupKey = 'tomorrow';
            const dayName = taskDate.toLocaleDateString('en-US', { weekday: 'long' }).toUpperCase();
            const formatted = taskDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }).toUpperCase();
            groupTitle = `TOMORROW — ${dayName}, ${formatted}`;
        } else if (diffDays <= 7) {
            groupKey = `day_${todo.due_date}`;
            const dayName = taskDate.toLocaleDateString('en-US', { weekday: 'long' }).toUpperCase();
            const formatted = taskDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }).toUpperCase();
            groupTitle = `${dayName} — ${formatted}`;
        } else if (diffDays <= 30) {
            groupKey = `week_${todo.due_date}`;
            const formatted = taskDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' }).toUpperCase();
            groupTitle = `UPCOMING — ${formatted}`;
        } else {
            groupKey = 'later';
            const formatted = taskDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' }).toUpperCase();
            groupTitle = `LATER — ${formatted}`;
        }

        if (!groups[groupKey]) {
            groups[groupKey] = {
                title: groupTitle,
                dateStr: todo.due_date,
                tasks: [],
            };
        }
        groups[groupKey].tasks.push(todo);
    });

    return Object.keys(groups).map(k => ({
        key: k,
        title: groups[k].title,
        dateStr: groups[k].dateStr,
        count: groups[k].tasks.length,
        tasks: groups[k].tasks,
    }));
});

// Summary metrics for bottom cards
const recurringCount = computed(() => {
    return props.todos.filter(t => t.recurrence && t.recurrence !== 'none').length;
});

const overdueCount = computed(() => {
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    return props.todos.filter(t => {
        if (t.completed || !t.due_date) return false;
        const [y, m, d] = t.due_date.split('-').map(Number);
        const taskDate = new Date(y, m - 1, d);
        return taskDate.getTime() < today.getTime();
    }).length;
});

const pacePercent = computed(() => {
    if (activeTodos.value.length === 0) return 100;
    const onTime = activeTodos.value.length - overdueCount.value;
    return Math.max(0, Math.round((onTime / activeTodos.value.length) * 100));
});

const activeWeeksCount = computed(() => {
    const weeks = new Set<string>();
    activeTodos.value.forEach(t => {
        if (t.due_date) {
            const [y, m, d] = t.due_date.split('-').map(Number);
            const date = new Date(y, m - 1, d);
            const weekNum = Math.ceil(date.getDate() / 7);
            weeks.add(`${date.getFullYear()}-${date.getMonth()}-${weekNum}`);
        }
    });
    return Math.max(1, weeks.size);
});
</script>

<template>
    <div class="flex flex-col gap-6">
        <!-- Header: Temporal Horizon + Title + Commitments Metric + Add Task -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between border-b border-border/50 pb-5">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                    Upcoming
                </h1>
                <p class="mt-1 text-xs sm:text-sm text-muted-foreground">
                    Schedule of future tasks grouped by date.
                </p>
            </div>

            <!-- Top Right: Active Commitments & Add Task -->
            <div class="flex items-center gap-3">
                <div class="flex items-center gap-2 rounded-full border border-border bg-card px-3.5 py-1.5 text-xs text-muted-foreground shadow-2xs">
                    <svg class="h-3.5 w-3.5 text-[#b85c38]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/>
                    </svg>
                    <span class="font-medium text-foreground">
                        {{ activeTodos.length }} Active Commitments
                    </span>
                </div>

                <button
                    type="button"
                    @click="emit('create')"
                    class="inline-flex items-center gap-1.5 rounded-full bg-[#b85c38] px-4 py-2 text-xs font-semibold text-white transition-all duration-200 hover:bg-[#a34f2f] active:scale-95 shadow-xs"
                >
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    <span>Add Task</span>
                </button>
            </div>
        </div>

        <!-- Empty State if no upcoming tasks -->
        <div
            v-if="groupedUpcoming.length === 0"
            class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border py-14 text-center"
        >
            <div class="flex h-12 w-12 items-center justify-center rounded-full border border-border bg-secondary/50 text-muted-foreground">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                    <line x1="16" y1="2" x2="16" y2="6"/>
                    <line x1="8" y1="2" x2="8" y2="6"/>
                    <line x1="3" y1="10" x2="21" y2="10"/>
                </svg>
            </div>
            <h3 class="mt-3 text-sm font-semibold text-foreground">No upcoming commitments</h3>
            <p class="mt-1 text-xs text-muted-foreground">
                Your schedule is wide open. Plan future tasks to stay ahead.
            </p>
            <button
                type="button"
                @click="emit('create')"
                class="mt-4 rounded-full bg-[#b85c38] px-5 py-2 text-xs font-semibold text-white transition-colors hover:bg-[#a34f2f] active:scale-95 shadow-xs"
            >
                Schedule upcoming task
            </button>
        </div>

        <!-- Date-Grouped Streams List -->
        <div v-else class="space-y-6">
            <div
                v-for="group in groupedUpcoming"
                :key="group.key"
                class="space-y-2.5"
            >
                <!-- Group Header: Title & Count Badge -->
                <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    <div class="flex items-center gap-2">
                        <span class="inline-block h-1.5 w-1.5 rounded-full bg-[#b85c38]" />
                        <span>{{ group.title }}</span>
                    </div>
                    <span class="rounded-full bg-secondary px-2 py-0.5 text-[10px] font-medium">
                        {{ group.count }} {{ group.count === 1 ? 'task' : 'tasks' }}
                    </span>
                </div>

                <!-- Group Task Cards -->
                <div class="flex flex-col gap-2.5">
                    <TaskCard
                        v-for="todo in group.tasks"
                        :key="todo.id"
                        :todo="todo"
                        @select="emit('select', todo)"
                        @toggle="emit('toggle', todo)"
                        @edit="emit('edit', todo)"
                        @delete="emit('delete', todo)"
                    />
                </div>
            </div>
        </div>

        <!-- Bottom 3 Metrics Cards Row (Matching Right Screenshot) -->
        <div class="mt-6 grid grid-cols-1 gap-3 sm:grid-cols-3">
            <!-- Card 1: Active Weeks -->
            <div class="rounded-2xl border border-border/70 bg-card p-4 shadow-2xs">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-indigo-500/10 text-indigo-600 dark:text-indigo-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="4" rx="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-foreground">
                            {{ activeWeeksCount }} Active Weeks
                        </div>
                        <div class="text-xs text-muted-foreground">
                            Balanced commitments
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 2: Recurring Habits -->
            <div class="rounded-2xl border border-border/70 bg-card p-4 shadow-2xs">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-emerald-500/10 text-emerald-600 dark:text-emerald-400">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m17 2 4 4-4 4"/>
                            <path d="M3 11v-1a4 4 0 0 1 4-4h14"/>
                            <path d="m7 22-4-4 4-4"/>
                            <path d="M21 13v1a4 4 0 0 1-4 4H3"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-foreground">
                            {{ recurringCount }} Recurring
                        </div>
                        <div class="text-xs text-muted-foreground">
                            Automated habits running
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card 3: Pace & Status -->
            <div class="rounded-2xl border border-border/70 bg-card p-4 shadow-2xs">
                <div class="flex items-center gap-3">
                    <div class="flex h-9 w-9 items-center justify-center rounded-xl bg-[#b85c38]/10 text-[#b85c38]">
                        <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                    <div>
                        <div class="text-sm font-bold text-foreground">
                            {{ pacePercent }}% on Pace
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ overdueCount === 0 ? '0 overdue deadlines' : `${overdueCount} overdue` }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>
