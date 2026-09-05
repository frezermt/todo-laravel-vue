<script setup lang="ts">
import { ref, computed } from 'vue';
import type { Todo } from './TaskCard.vue';
import TaskCard from './TaskCard.vue';

const props = withDefaults(defineProps<{
    todos?: Todo[];
}>(), {
    todos: () => [],
});

const emit = defineEmits<{
    (e: 'toggle', todo: Todo): void;
    (e: 'edit', todo: Todo): void;
    (e: 'delete', todo: Todo): void;
    (e: 'create-for-date', dateStr: string): void;
}>();

const today = new Date();
const currentYear = ref(today.getFullYear());
const currentMonth = ref(today.getMonth()); // 0-indexed: 0 = Jan, 8 = Sep
const selectedDate = ref<string | null>(
    `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`
);

const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
];

const weekdays = ['Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat', 'Sun'];

function prevMonth() {
    if (currentMonth.value === 0) {
        currentMonth.value = 11;
        currentYear.value--;
    } else {
        currentMonth.value--;
    }
}

function nextMonth() {
    if (currentMonth.value === 11) {
        currentMonth.value = 0;
        currentYear.value++;
    } else {
        currentMonth.value++;
    }
}

function goToToday() {
    currentYear.value = today.getFullYear();
    currentMonth.value = today.getMonth();
    selectedDate.value = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
}

// Map todos by due_date
const todosByDate = computed(() => {
    const map = new Map<string, Todo[]>();
    for (const todo of props.todos) {
        if (!todo.due_date) continue;
        const list = map.get(todo.due_date) || [];
        list.push(todo);
        map.set(todo.due_date, list);
    }
    return map;
});

interface CalendarCell {
    dateStr: string;
    dayNumber: number;
    isCurrentMonth: boolean;
    isToday: boolean;
    isSelected: boolean;
    todos: Todo[];
}

const calendarDays = computed<CalendarCell[]>(() => {
    const year = currentYear.value;
    const month = currentMonth.value;

    const firstDayIndex = new Date(year, month, 1).getDay();
    // In JS: Sun = 0, Mon = 1, ... Sat = 6. Let's make Mon = 0, Sun = 6
    const startDayOffset = (firstDayIndex + 6) % 7;

    const daysInMonth = new Date(year, month + 1, 0).getDate();
    const daysInPrevMonth = new Date(year, month, 0).getDate();

    const cells: CalendarCell[] = [];

    const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;

    // Leading padding days (previous month)
    for (let i = startDayOffset - 1; i >= 0; i--) {
        const dayNum = daysInPrevMonth - i;
        const prevMonthVal = month === 0 ? 12 : month;
        const prevYearVal = month === 0 ? year - 1 : year;
        const dateStr = `${prevYearVal}-${String(prevMonthVal).padStart(2, '0')}-${String(dayNum).padStart(2, '0')}`;
        cells.push({
            dateStr,
            dayNumber: dayNum,
            isCurrentMonth: false,
            isToday: dateStr === todayStr,
            isSelected: selectedDate.value === dateStr,
            todos: todosByDate.value.get(dateStr) || [],
        });
    }

    // Days in current month
    for (let d = 1; d <= daysInMonth; d++) {
        const dateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        cells.push({
            dateStr,
            dayNumber: d,
            isCurrentMonth: true,
            isToday: dateStr === todayStr,
            isSelected: selectedDate.value === dateStr,
            todos: todosByDate.value.get(dateStr) || [],
        });
    }

    // Trailing padding days (next month to fill 35 or 42 cells)
    const totalCells = cells.length > 35 ? 42 : 35;
    const remaining = totalCells - cells.length;
    for (let d = 1; d <= remaining; d++) {
        const nextMonthVal = month === 11 ? 1 : month + 2;
        const nextYearVal = month === 11 ? year + 1 : year;
        const dateStr = `${nextYearVal}-${String(nextMonthVal).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
        cells.push({
            dateStr,
            dayNumber: d,
            isCurrentMonth: false,
            isToday: dateStr === todayStr,
            isSelected: selectedDate.value === dateStr,
            todos: todosByDate.value.get(dateStr) || [],
        });
    }

    return cells;
});

function selectCell(cell: CalendarCell) {
    selectedDate.value = cell.dateStr;
}

const selectedDateTodos = computed(() => {
    if (!selectedDate.value) return [];
    return todosByDate.value.get(selectedDate.value) || [];
});

const selectedDateFormatted = computed(() => {
    if (!selectedDate.value) return '';
    const [y, m, d] = selectedDate.value.split('-').map(Number);
    const dateObj = new Date(y, m - 1, d);
    return dateObj.toLocaleDateString('en-US', {
        weekday: 'short',
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    });
});

const unscheduledTodos = computed(() => {
    return props.todos.filter(t => !t.due_date);
});
</script>

<template>
    <div class="grid grid-cols-1 gap-6 lg:grid-cols-12">
        <!-- Calendar Grid Side (7 cols on large screens) -->
        <div class="rounded-2xl border border-border bg-card p-4 sm:p-5 lg:col-span-7 shadow-xs">
            <!-- Calendar Navigation Header -->
            <div class="flex items-center justify-between pb-4">
                <div>
                    <h2 class="text-base font-semibold tracking-tight text-foreground">
                        {{ monthNames[currentMonth] }} {{ currentYear }}
                    </h2>
                    <p class="text-xs text-muted-foreground">Select a date to view or plan tasks</p>
                </div>

                <div class="flex items-center gap-1.5">
                    <button
                        type="button"
                        @click="goToToday"
                        class="rounded-full border border-border px-3 py-1 text-xs font-medium text-foreground transition-all duration-200 ease-out hover:bg-secondary active:scale-95"
                    >
                        Today
                    </button>
                    <button
                        type="button"
                        @click="prevMonth"
                        aria-label="Previous month"
                        class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-border text-muted-foreground transition-all duration-200 ease-out hover:bg-secondary hover:text-foreground active:scale-90"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="15 18 9 12 15 6"/>
                        </svg>
                    </button>
                    <button
                        type="button"
                        @click="nextMonth"
                        aria-label="Next month"
                        class="inline-flex h-7 w-7 items-center justify-center rounded-full border border-border text-muted-foreground transition-all duration-200 ease-out hover:bg-secondary hover:text-foreground active:scale-90"
                    >
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </button>
                </div>
            </div>

            <!-- Weekday Column Headers -->
            <div class="grid grid-cols-7 border-b border-border/60 pb-2 text-center text-[11px] font-medium uppercase tracking-wider text-muted-foreground/70">
                <div v-for="day in weekdays" :key="day">{{ day }}</div>
            </div>

            <!-- Calendar Days Grid -->
            <div class="mt-2 grid grid-cols-7 gap-1">
                <button
                    v-for="cell in calendarDays"
                    :key="cell.dateStr"
                    type="button"
                    @click="selectCell(cell)"
                    :class="[
                        'group relative flex flex-col items-center justify-start rounded-xl p-1.5 text-xs transition-all duration-200 ease-out min-h-[58px] sm:min-h-[66px]',
                        cell.isSelected
                            ? 'border border-[#b85c38] bg-[#b85c38]/10 font-semibold text-foreground'
                            : cell.isCurrentMonth
                            ? 'border border-transparent hover:border-border hover:bg-secondary/40 text-foreground'
                            : 'border border-transparent text-muted-foreground/40 hover:bg-secondary/20',
                    ]"
                >
                    <!-- Day Number Pill -->
                    <span
                        :class="[
                            'flex h-6 w-6 items-center justify-center rounded-full text-[12px] transition-transform duration-150',
                            cell.isToday && !cell.isSelected
                                ? 'bg-[#b85c38] font-bold text-white'
                                : cell.isSelected
                                ? 'bg-[#b85c38] font-bold text-white shadow-xs'
                                : '',
                        ]"
                    >
                        {{ cell.dayNumber }}
                    </span>

                    <!-- Task indicators on this day -->
                    <div v-if="cell.todos.length > 0" class="mt-1 flex flex-wrap justify-center gap-1">
                        <span
                            v-for="(t, idx) in cell.todos.slice(0, 3)"
                            :key="t.id"
                            class="h-1.5 w-1.5 rounded-full"
                            :style="{ backgroundColor: t.color || '#b85c38' }"
                        />
                        <span
                            v-if="cell.todos.length > 3"
                            class="text-[9px] font-semibold text-muted-foreground"
                        >
                            +{{ cell.todos.length - 3 }}
                        </span>
                    </div>
                </button>
            </div>
        </div>

        <!-- Selected Date Tasks Side (5 cols on large screens) -->
        <div class="flex flex-col rounded-2xl border border-border bg-card p-4 sm:p-5 lg:col-span-5 shadow-xs">
            <div class="flex items-center justify-between border-b border-border/60 pb-3">
                <div>
                    <h3 class="text-sm font-semibold tracking-tight text-foreground">
                        {{ selectedDateFormatted }}
                    </h3>
                    <p class="text-xs text-muted-foreground">
                        {{ selectedDateTodos.length }} {{ selectedDateTodos.length === 1 ? 'task' : 'tasks' }} scheduled
                    </p>
                </div>

                <button
                    v-if="selectedDate"
                    type="button"
                    @click="emit('create-for-date', selectedDate)"
                    class="inline-flex items-center gap-1.5 rounded-full bg-[#b85c38] px-3.5 py-1.5 text-xs font-medium text-white transition-all duration-200 ease-out hover:bg-[#a34f2f] active:scale-95 shadow-2xs"
                >
                    <svg class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    Add task
                </button>
            </div>

            <!-- List of tasks for the selected date -->
            <div class="mt-4 flex-1 space-y-3 overflow-y-auto max-h-[500px]">
                <div v-if="selectedDateTodos.length === 0" class="py-12 text-center">
                    <p class="text-xs text-muted-foreground">No tasks scheduled for this day.</p>
                    <button
                        v-if="selectedDate"
                        type="button"
                        @click="emit('create-for-date', selectedDate)"
                        class="mt-2 text-xs font-medium text-[#b85c38] hover:underline"
                    >
                        Schedule a task
                    </button>
                </div>

                <TaskCard
                    v-for="todo in selectedDateTodos"
                    :key="todo.id"
                    :todo="todo"
                    @toggle="emit('toggle', $event)"
                    @edit="emit('edit', $event)"
                    @delete="emit('delete', $event)"
                />
            </div>

            <!-- Unscheduled Tasks Section Footer (Collapsible/preview) -->
            <div v-if="unscheduledTodos.length > 0" class="mt-5 border-t border-border/60 pt-3">
                <div class="flex items-center justify-between text-xs text-muted-foreground">
                    <span>Unscheduled backlog</span>
                    <span class="font-medium text-foreground">{{ unscheduledTodos.length }} tasks</span>
                </div>
            </div>
        </div>
    </div>
</template>
