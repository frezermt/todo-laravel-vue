<script setup lang="ts">
import { ref, computed } from 'vue';
import TaskCard, { type Todo } from '@/components/todos/TaskCard.vue';

const props = defineProps<{
    todos: Todo[];
    availableCategories: string[];
}>();

const emit = defineEmits<{
    (e: 'create', defaultDueDate?: string, defaultCategory?: string): void;
    (e: 'select', todo: Todo): void;
    (e: 'toggle', todo: Todo): void;
    (e: 'edit', todo: Todo): void;
    (e: 'delete', todo: Todo): void;
}>();

// Selected category state (defaults to first available category)
const selectedCategory = ref<string>(props.availableCategories[0] || 'Work');

// Category color maps
const categoryColors: Record<string, string> = {
    Work: '#395273',
    Personal: '#b85c38',
    Health: '#4e6b56',
    Study: '#b8860b',
    Finance: '#785642',
    Projects: '#7a4960',
    Shopping: '#475569',
    Home: '#5e503f',
};

const PALETTE = ['#395273', '#b85c38', '#4e6b56', '#b8860b', '#785642', '#7a4960', '#475569', '#5e503f', '#2b580c', '#685d79'];

function getCategoryColor(cat: string) {
    if (categoryColors[cat]) return categoryColors[cat];
    let hash = 0;
    for (let i = 0; i < cat.length; i++) hash = cat.charCodeAt(i) + ((hash << 5) - hash);
    return PALETTE[Math.abs(hash) % PALETTE.length];
}

interface CategorySummary {
    name: string;
    total: number;
    active: number;
    completed: number;
    color: string;
}

const categoriesSummary = computed<CategorySummary[]>(() => {
    return props.availableCategories.map(cat => {
        const catTodos = props.todos.filter(t => t.category === cat);
        const active = catTodos.filter(t => !t.completed).length;
        const completed = catTodos.filter(t => t.completed).length;
        return {
            name: cat,
            total: catTodos.length,
            active,
            completed,
            color: getCategoryColor(cat),
        };
    });
});

// Tasks for current selected category
const selectedCatTodos = computed(() => {
    return props.todos.filter(t => t.category === selectedCategory.value);
});

const activeCategoryTasks = computed(() => {
    return selectedCatTodos.value.filter(t => !t.completed);
});

const completedCategoryTasks = computed(() => {
    return selectedCatTodos.value.filter(t => t.completed);
});

const completionRate = computed(() => {
    const total = selectedCatTodos.value.length;
    if (total === 0) return 100;
    return Math.round((completedCategoryTasks.value.length / total) * 100);
});

const showCompleted = ref(false);
</script>

<template>
    <div class="flex flex-col gap-6">
        <!-- Clean Header: Title & Subtitle -->
        <div class="border-b border-border/50 pb-5">
            <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                Categories
            </h1>
            <p class="mt-1 text-xs sm:text-sm text-muted-foreground">
                Organize your tasks by areas of life and work.
            </p>
        </div>

        <!-- Clean Category Cards Row (No random numbers) -->
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
            <button
                v-for="cat in categoriesSummary"
                :key="cat.name"
                type="button"
                @click="selectedCategory = cat.name"
                :class="[
                    'flex flex-col justify-between rounded-2xl border p-4 text-left transition-all duration-200 active:scale-95',
                    selectedCategory === cat.name
                        ? 'border-[#b85c38] bg-[#b85c38] text-white shadow-sm'
                        : 'border-border/70 bg-card hover:border-border hover:shadow-2xs text-foreground'
                ]"
            >
                <div class="flex items-center">
                    <span
                        class="h-2.5 w-2.5 rounded-full"
                        :style="{ backgroundColor: selectedCategory === cat.name ? '#ffffff' : cat.color }"
                    />
                </div>

                <div class="mt-3">
                    <h3 class="text-sm font-bold tracking-tight">
                        {{ cat.name }}
                    </h3>
                    <p
                        :class="[
                            'text-xs mt-0.5',
                            selectedCategory === cat.name ? 'text-white/80' : 'text-muted-foreground'
                        ]"
                    >
                        {{ cat.active }} active
                    </p>
                </div>
            </button>
        </div>

        <!-- Selected Category Banner (No Primary Focus badge) -->
        <div class="rounded-2xl border border-border/70 bg-card p-5 shadow-2xs">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div class="flex items-center gap-3.5">
                    <div
                        class="flex aspect-square h-11 w-11 items-center justify-center rounded-xl text-white shadow-2xs"
                        :style="{ backgroundColor: getCategoryColor(selectedCategory) }"
                    >
                        <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 20h16a2 2 0 0 0 2-2V8a2 2 0 0 0-2-2h-7.93a2 2 0 0 1-1.66-.9l-.82-1.2A2 2 0 0 0 7.93 3H4a2 2 0 0 0-2 2v13c0 1.1.9 2 2 2Z"/>
                        </svg>
                    </div>

                    <div>
                        <h2 class="text-lg font-bold tracking-tight text-foreground">
                            {{ selectedCategory }}
                        </h2>
                        <p class="mt-0.5 text-xs text-muted-foreground">
                            {{ activeCategoryTasks.length }} active tasks · {{ completedCategoryTasks.length }} completed · {{ completionRate }}% completion rate
                        </p>
                    </div>
                </div>

                <!-- Add Task in this category button -->
                <button
                    type="button"
                    @click="emit('create', undefined, selectedCategory)"
                    class="inline-flex items-center gap-1.5 rounded-full bg-[#b85c38] px-4 py-2 text-xs font-semibold text-white transition-colors hover:bg-[#a34f2f] active:scale-95 shadow-xs self-start sm:self-auto"
                >
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"/>
                        <line x1="5" y1="12" x2="19" y2="12"/>
                    </svg>
                    <span>Add {{ selectedCategory }} Task</span>
                </button>
            </div>
        </div>

        <!-- Active Tasks Stream -->
        <div class="space-y-3">
            <div class="flex items-center justify-between text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                <div class="flex items-center gap-2">
                    <span class="inline-block h-1.5 w-1.5 rounded-full bg-[#b85c38]" />
                    <span>Active Tasks ({{ activeCategoryTasks.length }})</span>
                </div>
            </div>

            <!-- Empty State for this category -->
            <div
                v-if="activeCategoryTasks.length === 0"
                class="flex flex-col items-center justify-center rounded-2xl border border-dashed border-border py-12 text-center"
            >
                <div class="flex h-10 w-10 items-center justify-center rounded-full border border-border bg-secondary/50 text-muted-foreground">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="20 6 9 17 4 12"/>
                    </svg>
                </div>
                <h3 class="mt-3 text-sm font-semibold text-foreground">
                    No active tasks in {{ selectedCategory }}
                </h3>
                <p class="mt-1 text-xs text-muted-foreground">
                    All tasks in this category are completed or none have been created yet.
                </p>
                <button
                    type="button"
                    @click="emit('create', undefined, selectedCategory)"
                    class="mt-4 rounded-full bg-[#b85c38] px-4 py-1.5 text-xs font-semibold text-white transition-colors hover:bg-[#a34f2f] active:scale-95 shadow-xs"
                >
                    Create task in {{ selectedCategory }}
                </button>
            </div>

            <!-- Task Cards -->
            <div v-else class="flex flex-col gap-2.5">
                <TaskCard
                    v-for="todo in activeCategoryTasks"
                    :key="todo.id"
                    :todo="todo"
                    @select="emit('select', todo)"
                    @toggle="emit('toggle', todo)"
                    @edit="emit('edit', todo)"
                    @delete="emit('delete', todo)"
                />
            </div>
        </div>

        <!-- Completed Tasks Section -->
        <div v-if="completedCategoryTasks.length > 0" class="space-y-2.5 border-t border-border/50 pt-5">
            <div class="flex items-center justify-between">
                <button
                    type="button"
                    @click="showCompleted = !showCompleted"
                    class="flex items-center gap-2 text-xs font-semibold uppercase tracking-wider text-muted-foreground hover:text-foreground transition-colors"
                >
                    <span class="inline-flex h-4 w-4 items-center justify-center rounded-full bg-emerald-500/10 text-emerald-600 text-[10px]">
                        ✓
                    </span>
                    <span>Completed Tasks ({{ completedCategoryTasks.length }})</span>
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
                    v-for="todo in completedCategoryTasks"
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
</template>
