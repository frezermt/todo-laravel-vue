<script setup lang="ts">
import { computed } from 'vue';

export interface Todo {
    id: number;
    title: string;
    description: string | null;
    category: string | null;
    color: string | null;
    due_date: string | null;
    recurrence: string | null;
    reminder_at: string | null;
    completed: boolean;
    created_at: string;
    updated_at: string;
}

const props = defineProps<{
    todo: Todo;
}>();

const emit = defineEmits<{
    (e: 'toggle', todo: Todo): void;
    (e: 'edit', todo: Todo): void;
    (e: 'delete', todo: Todo): void;
}>();

// Due date formatting and status
const dueDateInfo = computed(() => {
    if (!props.todo.due_date) return null;
    
    const [year, month, day] = props.todo.due_date.split('-').map(Number);
    const dueDate = new Date(year, month - 1, day);
    const today = new Date();
    today.setHours(0, 0, 0, 0);
    
    const diffTime = dueDate.getTime() - today.getTime();
    const diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));
    
    let label = '';
    let isOverdue = false;
    let isToday = false;
    
    if (diffDays < 0 && !props.todo.completed) {
        isOverdue = true;
        label = diffDays === -1 ? 'Overdue (yesterday)' : `Overdue (${Math.abs(diffDays)}d ago)`;
    } else if (diffDays === 0) {
        isToday = true;
        label = 'Due today';
    } else if (diffDays === 1) {
        label = 'Due tomorrow';
    } else {
        label = dueDate.toLocaleDateString('en-US', { month: 'short', day: 'numeric' });
    }
    
    return { label, isOverdue, isToday };
});

const reminderInfo = computed(() => {
    if (!props.todo.reminder_at) return null;
    const date = new Date(props.todo.reminder_at);
    return date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' });
});

const recurrenceLabel = computed(() => {
    if (!props.todo.recurrence || props.todo.recurrence === 'none') return null;
    const r = props.todo.recurrence;
    if (r === 'daily') return 'Daily';
    if (r === 'weekdays') return 'Weekdays';
    if (r === 'weekly') return 'Weekly';
    if (r === 'biweekly') return 'Every 2 wks';
    if (r === 'monthly') return 'Monthly';
    if (r.startsWith('custom:')) {
        const parts = r.split(':');
        const count = parts[1] || '1';
        const unit = parts[2] || 'days';
        return `Every ${count} ${unit}`;
    }
    return r;
});

const accentColor = computed(() => props.todo.color || '#b85c38');
</script>

<template>
    <div
        :class="[
            'group relative flex flex-col justify-between overflow-hidden rounded-xl border bg-card p-4 transition-all duration-200 ease-out',
            todo.completed
                ? 'border-border/50 bg-muted/20 opacity-75'
                : 'border-border/80 hover:border-border hover:shadow-sm hover:-translate-y-0.5',
        ]"
    >
        <!-- Color Accent Strip (Left Bar) -->
        <div
            class="absolute top-0 bottom-0 left-0 w-1 transition-colors duration-200"
            :style="{ backgroundColor: accentColor }"
        />

        <!-- Card Top: Tags & Actions -->
        <div>
            <div class="flex items-center justify-between gap-2 pl-1.5">
                <!-- Badges: Category, Due Date, Recurrence, Reminder -->
                <div class="flex flex-wrap items-center gap-1.5">
                    <span
                        v-if="todo.category"
                        class="inline-flex items-center rounded-full border border-border/70 bg-secondary/50 px-2.5 py-0.5 text-[11px] font-medium tracking-wide uppercase text-foreground/80 transition-colors"
                    >
                        {{ todo.category }}
                    </span>

                    <span
                        v-if="dueDateInfo"
                        :class="[
                            'inline-flex items-center gap-1 rounded-full border px-2.5 py-0.5 text-[11px] font-medium transition-colors',
                            dueDateInfo.isOverdue
                                ? 'border-red-300 bg-red-50 text-red-700 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300'
                                : dueDateInfo.isToday
                                ? 'border-[#b85c38]/40 bg-[#b85c38]/10 text-[#b85c38]'
                                : 'border-border/70 bg-secondary/30 text-muted-foreground'
                        ]"
                    >
                        <svg class="h-3 w-3 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="4" rx="1"/>
                            <line x1="16" x2="16" y1="2" y2="6"/>
                            <line x1="8" x2="8" y1="2" y2="6"/>
                            <line x1="3" x2="21" y1="10" y2="10"/>
                        </svg>
                        {{ dueDateInfo.label }}
                    </span>

                    <!-- Recurring Badge -->
                    <span
                        v-if="recurrenceLabel"
                        class="inline-flex items-center gap-1 rounded-full border border-border/70 bg-secondary/40 px-2.5 py-0.5 text-[11px] font-medium text-muted-foreground"
                        :title="`Recurring: ${recurrenceLabel}`"
                    >
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m17 2 4 4-4 4"/>
                            <path d="M3 11v-1a4 4 0 0 1 4-4h14"/>
                            <path d="m7 22-4-4 4-4"/>
                            <path d="M21 13v1a4 4 0 0 1-4 4H3"/>
                        </svg>
                        {{ recurrenceLabel }}
                    </span>

                    <!-- Alarm / Reminder Badge -->
                    <span
                        v-if="reminderInfo"
                        class="inline-flex items-center gap-1 rounded-full border border-amber-300 bg-amber-50 dark:border-amber-900/60 dark:bg-amber-950/40 px-2 py-0.5 text-[11px] font-medium text-amber-800 dark:text-amber-300"
                        title="Alarm reminder set"
                    >
                        <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                        </svg>
                        {{ reminderInfo }}
                    </span>
                </div>

                <!-- Pill-shaped Action Buttons -->
                <div class="flex items-center gap-1">
                    <button
                        type="button"
                        @click="emit('edit', todo)"
                        aria-label="Edit task"
                        title="Edit task"
                        class="inline-flex h-7 w-7 items-center justify-center rounded-full text-muted-foreground transition-all duration-200 ease-out hover:bg-secondary hover:text-foreground active:scale-90"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                            <path d="m15 5 4 4"/>
                        </svg>
                    </button>
                    <button
                        type="button"
                        @click="emit('delete', todo)"
                        aria-label="Delete task"
                        title="Delete task"
                        class="inline-flex h-7 w-7 items-center justify-center rounded-full text-muted-foreground/70 transition-all duration-200 ease-out hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-950/40 dark:hover:text-red-400 active:scale-90"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M3 6h18"/>
                            <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                            <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                            <line x1="10" x2="10" y1="11" y2="17"/>
                            <line x1="14" x2="14" y1="11" y2="17"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Task Title & Checkbox Area -->
            <div class="mt-3 flex items-start gap-3 pl-1.5">
                <!-- Smooth Pill / Circular Checkbox Button -->
                <button
                    type="button"
                    @click="emit('toggle', todo)"
                    :aria-label="todo.completed ? 'Mark incomplete' : 'Mark complete'"
                    :class="[
                        'mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full border transition-all duration-200 ease-out active:scale-85 shadow-2xs',
                        todo.completed
                            ? 'border-transparent text-white'
                            : 'border-[#ccc6bb] hover:border-[#b85c38] hover:bg-muted/50 dark:border-[#423d37]',
                    ]"
                    :style="todo.completed ? { backgroundColor: accentColor } : {}"
                >
                    <svg
                        v-if="todo.completed"
                        class="h-3 w-3 stroke-white"
                        viewBox="0 0 24 24"
                        fill="none"
                        stroke-width="3"
                        stroke-linecap="round"
                        stroke-linejoin="round"
                    >
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </button>

                <!-- Title and Description -->
                <div class="min-w-0 flex-1">
                    <h3
                        :class="[
                            'text-sm font-medium leading-snug tracking-tight transition-colors duration-200',
                            todo.completed ? 'text-muted-foreground line-through' : 'text-foreground'
                        ]"
                    >
                        {{ todo.title }}
                    </h3>
                    <p
                        v-if="todo.description"
                        :class="[
                            'mt-1.5 text-xs leading-relaxed transition-colors duration-200',
                            todo.completed ? 'text-muted-foreground/50 line-through' : 'text-muted-foreground'
                        ]"
                    >
                        {{ todo.description }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Card Footer: Metadata & Color Dot -->
        <div class="mt-4 flex items-center justify-between border-t border-border/40 pt-2.5 pl-1.5 text-[11px] text-muted-foreground/60">
            <span class="inline-flex items-center gap-1.5">
                <span
                    class="h-2 w-2 rounded-full ring-1 ring-border/50"
                    :style="{ backgroundColor: accentColor }"
                />
                <span>{{ todo.color ? (todo.category || 'Task') : 'Standard' }}</span>
            </span>
            <time :datetime="todo.created_at">
                {{ new Date(todo.created_at).toLocaleDateString('en-US', { month: 'short', day: 'numeric' }) }}
            </time>
        </div>
    </div>
</template>
