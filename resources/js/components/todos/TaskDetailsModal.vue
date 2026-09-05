<script setup lang="ts">
import { computed } from 'vue';
import type { Todo } from '@/components/todos/TaskCard.vue';
import {
    Dialog,
    DialogContent,
    DialogHeader,
    DialogTitle,
    DialogDescription,
} from '@/components/ui/dialog';

const props = defineProps<{
    open: boolean;
    todo: Todo | null;
}>();

const emit = defineEmits<{
    (e: 'update:open', val: boolean): void;
    (e: 'toggle', todo: Todo): void;
    (e: 'edit', todo: Todo): void;
    (e: 'delete', todo: Todo): void;
}>();

const accentColor = computed(() => props.todo?.color || '#b85c38');

const recurrenceLabel = computed(() => {
    if (!props.todo?.recurrence || props.todo.recurrence === 'none') return null;
    const r = props.todo.recurrence;
    if (r === 'daily') return 'Repeats every day';
    if (r === 'weekdays') return 'Repeats on weekdays';
    if (r === 'weekly') return 'Repeats every week';
    if (r === 'biweekly') return 'Repeats every 2 weeks';
    if (r === 'monthly') return 'Repeats every month';
    if (r.startsWith('custom:')) {
        const parts = r.split(':');
        const count = parts[1] || '1';
        const unit = parts[2] || 'days';
        return `Repeats every ${count} ${unit}`;
    }
    return `Repeats (${r})`;
});

const formattedDueDate = computed(() => {
    if (!props.todo?.due_date) return 'No deadline';
    const [year, month, day] = props.todo.due_date.split('-').map(Number);
    const date = new Date(year, month - 1, day);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    const diffDays = Math.round((date.getTime() - today.getTime()) / (1000 * 60 * 60 * 24));
    if (diffDays === 0) return 'Today';
    if (diffDays === 1) return 'Tomorrow';
    if (diffDays === -1) return 'Yesterday (Overdue)';
    if (diffDays < -1) return `Overdue by ${Math.abs(diffDays)} days`;

    return date.toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
    });
});

const formattedReminder = computed(() => {
    if (!props.todo?.reminder_at) return 'No alarm set';
    const date = new Date(props.todo.reminder_at);
    return `Set for ${date.toLocaleTimeString([], { hour: '2-digit', minute: '2-digit' })}`;
});

const formattedCreatedAt = computed(() => {
    if (!props.todo?.created_at) return '';
    const date = new Date(props.todo.created_at);
    return date.toLocaleDateString('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
});

function handleToggle() {
    if (props.todo) {
        emit('toggle', props.todo);
    }
}

function handleEdit() {
    if (props.todo) {
        emit('edit', props.todo);
        emit('update:open', false);
    }
}

function handleDelete() {
    if (props.todo) {
        emit('delete', props.todo);
        emit('update:open', false);
    }
}
</script>

<template>
    <Dialog :open="open" @update:open="emit('update:open', $event)">
        <DialogContent
            v-if="todo"
            class="sm:max-w-lg rounded-2xl border-border bg-card p-6 shadow-xl overflow-hidden"
        >
            <DialogHeader class="sr-only">
                <DialogTitle>{{ todo.title }}</DialogTitle>
                <DialogDescription>Task detail breakdown and actions</DialogDescription>
            </DialogHeader>

            <!-- Top Header Row: Status Badge & Task #ID -->
            <div class="flex items-center justify-between gap-3 border-b border-border/50 pb-3">
                <div class="flex items-center gap-2">
                    <!-- Status Badge -->
                    <span
                        :class="[
                            'inline-flex items-center gap-1.5 rounded-full px-2.5 py-0.5 text-xs font-semibold uppercase tracking-wider',
                            todo.completed
                                ? 'bg-emerald-500/10 text-emerald-700 dark:text-emerald-400 border border-emerald-500/30'
                                : 'bg-amber-500/10 text-amber-700 dark:text-amber-400 border border-amber-500/30'
                        ]"
                    >
                        <span
                            :class="[
                                'h-1.5 w-1.5 rounded-full',
                                todo.completed ? 'bg-emerald-500' : 'bg-amber-500'
                            ]"
                        />
                        {{ todo.completed ? 'Completed' : 'In Progress' }}
                    </span>

                    <span class="text-xs font-mono text-muted-foreground/80">
                        Task #{{ String(todo.id).padStart(2, '0') }}
                    </span>
                </div>
            </div>

            <!-- Task Title & Description -->
            <div class="mt-4 space-y-1.5">
                <h2
                    :class="[
                        'text-xl font-semibold tracking-tight text-foreground',
                        todo.completed ? 'line-through text-muted-foreground' : ''
                    ]"
                >
                    {{ todo.title }}
                </h2>
                <p
                    v-if="todo.description"
                    :class="[
                        'text-sm leading-relaxed text-muted-foreground',
                        todo.completed ? 'line-through text-muted-foreground/60' : ''
                    ]"
                >
                    {{ todo.description }}
                </p>
            </div>

            <!-- Recurrence Banner (If Active) -->
            <div
                v-if="recurrenceLabel"
                class="mt-4 rounded-xl border border-[#b85c38]/20 bg-[#b85c38]/5 p-3.5 transition-colors"
            >
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-2 text-sm font-medium text-[#b85c38]">
                        <svg class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m17 2 4 4-4 4"/>
                            <path d="M3 11v-1a4 4 0 0 1 4-4h14"/>
                            <path d="m7 22-4-4 4-4"/>
                            <path d="M21 13v1a4 4 0 0 1-4 4H3"/>
                        </svg>
                        <span>{{ recurrenceLabel }}</span>
                    </div>
                    <span class="rounded-full bg-[#b85c38]/15 px-2 py-0.5 text-[11px] font-semibold text-[#b85c38]">
                        Recurrence Active
                    </span>
                </div>
                <p class="mt-1.5 text-xs text-muted-foreground">
                    Next occurrence automatically schedules upon marking complete.
                </p>
            </div>

            <!-- 2x2 Grid of Detail Cards -->
            <div class="mt-4 grid grid-cols-1 gap-2.5 sm:grid-cols-2">
                <!-- Card 1: Category -->
                <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                        Category
                    </div>
                    <div class="mt-1.5 flex items-center gap-2">
                        <span
                            class="h-2 w-2 rounded-full"
                            :style="{ backgroundColor: accentColor }"
                        />
                        <span class="text-xs font-medium text-foreground">
                            {{ todo.category || 'General' }}
                        </span>
                    </div>
                </div>

                <!-- Card 2: Deadline -->
                <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                        Deadline
                    </div>
                    <div class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-foreground">
                        <svg class="h-3.5 w-3.5 text-muted-foreground" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="18" height="18" x="3" y="4" rx="1"/>
                            <line x1="16" x2="16" y1="2" y2="6"/>
                            <line x1="8" x2="8" y1="2" y2="6"/>
                            <line x1="3" x2="21" y1="10" y2="10"/>
                        </svg>
                        <span>{{ formattedDueDate }}</span>
                    </div>
                </div>

                <!-- Card 3: Alarm & Alert -->
                <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                        Alarm & Alert
                    </div>
                    <div class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-foreground">
                        <svg class="h-3.5 w-3.5 text-muted-foreground" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/>
                            <path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/>
                        </svg>
                        <span>{{ formattedReminder }}</span>
                    </div>
                </div>

                <!-- Card 4: Workflow State -->
                <div class="rounded-xl border border-border/70 bg-muted/20 p-3">
                    <div class="text-[10px] font-semibold uppercase tracking-wider text-muted-foreground">
                        Workflow State
                    </div>
                    <div class="mt-1.5 flex items-center gap-1.5 text-xs font-medium text-foreground">
                        <span
                            :class="[
                                'inline-block h-2 w-2 rounded-full',
                                todo.completed ? 'bg-emerald-500' : 'bg-amber-500'
                            ]"
                        />
                        <span>{{ todo.completed ? 'Finished & Recorded' : 'Pending completion' }}</span>
                    </div>
                </div>
            </div>

            <!-- Creation Metadata -->
            <div class="mt-3 flex items-center justify-between text-[11px] text-muted-foreground/70">
                <span>Created {{ formattedCreatedAt }}</span>
                <span class="inline-flex items-center gap-1">
                    <span class="h-1.5 w-1.5 rounded-full" :style="{ backgroundColor: accentColor }" />
                    Color tag active
                </span>
            </div>

            <!-- Footer Actions -->
            <div class="mt-6 flex flex-wrap items-center justify-between gap-2 border-t border-border/60 pt-4">
                <!-- Delete Button -->
                <button
                    type="button"
                    @click="handleDelete"
                    class="inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-xs font-medium text-red-600 transition-colors hover:bg-red-50 dark:text-red-400 dark:hover:bg-red-950/40 active:scale-95"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M3 6h18"/>
                        <path d="M19 6v14c0 1-1 2-2 2H7c-1 0-2-1-2-2V6"/>
                        <path d="M8 6V4c0-1 1-2 2-2h4c1 0 2 1 2 2v2"/>
                    </svg>
                    <span>Delete Task</span>
                </button>

                <!-- Right Actions: Edit & Complete -->
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="handleEdit"
                        class="inline-flex items-center gap-1.5 rounded-full border border-border bg-background px-4 py-1.5 text-xs font-medium text-foreground shadow-2xs transition-colors hover:bg-secondary active:scale-95"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 3a2.85 2.83 0 1 1 4 4L7.5 20.5 2 22l1.5-5.5Z"/>
                            <path d="m15 5 4 4"/>
                        </svg>
                        <span>Edit Task</span>
                    </button>

                    <button
                        type="button"
                        @click="handleToggle"
                        class="inline-flex items-center gap-1.5 rounded-full bg-[#b85c38] px-4 py-1.5 text-xs font-medium text-white shadow-xs transition-colors hover:bg-[#a34f2f] active:scale-95"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                        <span>{{ todo.completed ? 'Mark Incomplete' : 'Mark Complete' }}</span>
                    </button>
                </div>
            </div>
        </DialogContent>
    </Dialog>
</template>
