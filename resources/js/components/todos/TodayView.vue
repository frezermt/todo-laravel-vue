<script setup lang="ts">
import { ref, computed } from 'vue';
import TaskCard, { type Todo } from '@/components/todos/TaskCard.vue';

const props = defineProps<{
    todos: Todo[];
    userName?: string;
}>();

const emit = defineEmits<{
    (e: 'create', defaultDueDate?: string): void;
    (e: 'select', todo: Todo): void;
    (e: 'toggle', todo: Todo): void;
    (e: 'edit', todo: Todo): void;
    (e: 'delete', todo: Todo): void;
}>();

// Today date string in YYYY-MM-DD
const todayDateString = computed(() => {
    const now = new Date();
    const year = now.getFullYear();
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const day = String(now.getDate()).padStart(2, '0');
    return `${year}-${month}-${day}`;
});

// Formatted today string
const formattedTodayTitle = computed(() => {
    const formatted = new Intl.DateTimeFormat('en-US', {
        weekday: 'long',
        month: 'long',
        day: 'numeric',
    }).format(new Date());
    return `${formatted}`;
});

// Friendly time greeting
const greeting = computed(() => {
    const hour = new Date().getHours();
    let text = 'Good morning';
    if (hour >= 12 && hour < 17) text = 'Good afternoon';
    else if (hour >= 17) text = 'Good evening';
    return `${text}, ${props.userName || 'there'}`;
});

// Tasks scheduled for Today (due today or overdue active)
const todayTasks = computed(() => {
    return props.todos.filter(t => {
        if (!t.due_date) return false;
        return t.due_date <= todayDateString.value;
    });
});

const activeTodayTasks = computed(() => todayTasks.value.filter(t => !t.completed));
const completedTodayTasks = computed(() => todayTasks.value.filter(t => t.completed));

// Morning vs Afternoon/Evening split for today
const morningTasks = computed(() => {
    return activeTodayTasks.value.filter(t => {
        if (!t.reminder_at) return true;
        const hour = new Date(t.reminder_at).getHours();
        return hour < 12;
    });
});

const afternoonTasks = computed(() => {
    return activeTodayTasks.value.filter(t => {
        if (!t.reminder_at) return false;
        const hour = new Date(t.reminder_at).getHours();
        return hour >= 12;
    });
});

// Progress metrics
const totalToday = computed(() => todayTasks.value.length);
const completedCount = computed(() => completedTodayTasks.value.length);
const progressPercent = computed(() => {
    if (totalToday.value === 0) return 0;
    return Math.round((completedCount.value / totalToday.value) * 100);
});

const showCompleted = ref(true);
</script>

<template>
    <div class="flex flex-col gap-6">
        <!-- Header: Title + Date + Progress + Add Task -->
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between border-b border-border/50 pb-5">
            <div>
                <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                    Today
                </h1>
                <p class="mt-1 text-xs sm:text-sm text-muted-foreground">
                    {{ formattedTodayTitle }}
                </p>
            </div>

            <!-- Top Right: Progress Pill & Add Task Button -->
            <div class="flex items-center gap-3">
                <div v-if="totalToday > 0" class="flex items-center gap-2 rounded-full border border-border bg-card px-3.5 py-1.5 text-xs text-muted-foreground shadow-2xs">
                    <div class="h-1.5 w-16 overflow-hidden rounded-full bg-secondary">
                        <div
                            class="h-full bg-[#b85c38] transition-all duration-300"
                            :style="{ width: `${progressPercent}%` }"
                        />
                    </div>
                    <span class="font-medium text-foreground">
                        {{ completedCount }} of {{ totalToday }} done · {{ progressPercent }}%
                    </span>
                </div>

                <button
                    type="button"
                    @click="emit('create', todayDateString)"
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

        <!-- Clean Greeting Banner -->
        <div class="rounded-2xl border border-border/70 bg-card p-5 shadow-2xs">
            <h2 class="text-lg font-bold text-foreground">
                {{ greeting }}
            </h2>
            <p class="mt-0.5 text-xs text-muted-foreground">
                {{ activeTodayTasks.length }} tasks scheduled for today.
            </p>
        </div>

        <!-- Empty State if no tasks for today -->
        <div
            v-if="todayTasks.length === 0"
            class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border py-14 text-center"
        >
            <div class="flex h-12 w-12 items-center justify-center rounded-full border border-border bg-secondary/50 text-muted-foreground">
                <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="12" cy="12" r="10"/>
                    <polyline points="12 6 12 12 16 14"/>
                </svg>
            </div>
            <h3 class="mt-3 text-sm font-semibold text-foreground">No tasks scheduled for today</h3>
            <p class="mt-1 text-xs text-muted-foreground">
                Your day is clear. Schedule tasks or take a break.
            </p>
            <button
                type="button"
                @click="emit('create', todayDateString)"
                class="mt-4 rounded-full bg-[#b85c38] px-5 py-2 text-xs font-semibold text-white transition-colors hover:bg-[#a34f2f] active:scale-95 shadow-xs"
            >
                Schedule task for today
            </button>
        </div>

        <!-- Task Streams: MORNING & AFTERNOON/EVENING -->
        <div v-else class="space-y-6">
            <!-- Morning Stream -->
            <div v-if="morningTasks.length > 0" class="space-y-2.5">
                <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    <div class="flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="4"/>
                            <path d="M12 2v2"/>
                            <path d="M12 20v2"/>
                            <path d="m4.93 4.93 1.41 1.41"/>
                            <path d="m17.66 17.66 1.41 1.41"/>
                            <path d="M2 12h2"/>
                            <path d="M20 12h2"/>
                            <path d="m6.34 17.66-1.41 1.41"/>
                            <path d="m19.07 4.93-1.41 1.41"/>
                        </svg>
                        <span>Morning</span>
                    </div>
                    <span class="rounded-full bg-secondary px-2 py-0.5 text-[10px] font-medium">
                        {{ morningTasks.length }} tasks
                    </span>
                </div>

                <div class="flex flex-col gap-2.5">
                    <TaskCard
                        v-for="todo in morningTasks"
                        :key="todo.id"
                        :todo="todo"
                        @select="emit('select', todo)"
                        @toggle="emit('toggle', todo)"
                        @edit="emit('edit', todo)"
                        @delete="emit('delete', todo)"
                    />
                </div>
            </div>

            <!-- Afternoon / Evening Stream -->
            <div v-if="afternoonTasks.length > 0" class="space-y-2.5">
                <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                    <div class="flex items-center gap-1.5">
                        <svg class="h-3.5 w-3.5 text-indigo-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/>
                        </svg>
                        <span>Afternoon / Evening</span>
                    </div>
                    <span class="rounded-full bg-secondary px-2 py-0.5 text-[10px] font-medium">
                        {{ afternoonTasks.length }} tasks
                    </span>
                </div>

                <div class="flex flex-col gap-2.5">
                    <TaskCard
                        v-for="todo in afternoonTasks"
                        :key="todo.id"
                        :todo="todo"
                        @select="emit('select', todo)"
                        @toggle="emit('toggle', todo)"
                        @edit="emit('edit', todo)"
                        @delete="emit('delete', todo)"
                    />
                </div>
            </div>

            <!-- Completed Today Section -->
            <div v-if="completedTodayTasks.length > 0" class="space-y-2.5 border-t border-border/50 pt-5">
                <div class="flex items-center justify-between">
                    <button
                        type="button"
                        @click="showCompleted = !showCompleted"
                        class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground hover:text-foreground transition-colors"
                    >
                        <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600">
                            ✓
                        </span>
                        <span>Completed Today ({{ completedTodayTasks.length }})</span>
                        <svg
                            :class="['h-3.5 w-3.5 transition-transform duration-200', showCompleted ? 'rotate-180' : '']"
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                        >
                            <polyline points="6 9 12 15 18 9"/>
                        </svg>
                    </button>
                </div>

                <div v-if="showCompleted" class="flex flex-col gap-2.5 pt-1">
                    <TaskCard
                        v-for="todo in completedTodayTasks"
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
    </div>
</template>
