<script setup lang="ts">
import { computed, ref } from 'vue';
import type { Todo } from './TaskCard.vue';

const props = withDefaults(defineProps<{
    todos?: Todo[];
}>(), {
    todos: () => [],
});

const isExpanded = ref(true);

const safeTodos = computed(() => props.todos || []);
const total = computed(() => safeTodos.value.length);
const completedCount = computed(() => safeTodos.value.filter(t => t.completed).length);
const activeCount = computed(() => safeTodos.value.filter(t => !t.completed).length);
const recurringCount = computed(() => safeTodos.value.filter(t => t.recurrence && t.recurrence !== 'none').length);

const completionRate = computed(() => {
    if (total.value === 0) return 0;
    return Math.round((completedCount.value / total.value) * 100);
});

// Category Distribution
const categoryPalette = [
    '#b85c38', '#4e6b56', '#395273', '#b8860b', '#7a4960', '#475569', '#785642', '#334155'
];

interface CategorySlice {
    name: string;
    count: number;
    percent: number;
    color: string;
}

const categorySlices = computed<CategorySlice[]>(() => {
    if (total.value === 0) return [];
    const counts = new Map<string, number>();

    for (const todo of safeTodos.value) {
        const cat = todo.category?.trim() || 'General';
        counts.set(cat, (counts.get(cat) || 0) + 1);
    }

    const entries = Array.from(counts.entries()).sort((a, b) => b[1] - a[1]);
    return entries.map(([name, count], index) => ({
        name,
        count,
        percent: Math.round((count / total.value) * 100),
        color: categoryPalette[index % categoryPalette.length],
    }));
});

// SVG Donut helpers
function getCoordinatesForPercent(percent: number) {
    const x = Math.cos(2 * Math.PI * percent);
    const y = Math.sin(2 * Math.PI * percent);
    return [x, y];
}

interface SvgPathSlice {
    name: string;
    count: number;
    percent: number;
    color: string;
    path: string;
}

const categorySvgPaths = computed<SvgPathSlice[]>(() => {
    const slices = categorySlices.value;
    if (slices.length === 0) return [];

    if (slices.length === 1) {
        return [{
            ...slices[0],
            path: 'M 0 -1 A 1 1 0 1 1 -0.0001 -1 L 0 0 Z'
        }];
    }

    let cumulativePercent = 0;
    return slices.map(slice => {
        const startPercent = cumulativePercent;
        const sliceFraction = slice.count / total.value;
        cumulativePercent += sliceFraction;
        const endPercent = cumulativePercent;

        // Offset by -0.25 to start at 12 o'clock
        const [startX, startY] = getCoordinatesForPercent(startPercent - 0.25);
        const [endX, endY] = getCoordinatesForPercent(endPercent - 0.25);
        const largeArcFlag = sliceFraction > 0.5 ? 1 : 0;

        const pathData = [
            `M ${startX} ${startY}`,
            `A 1 1 0 ${largeArcFlag} 1 ${endX} ${endY}`,
            'L 0 0',
            'Z'
        ].join(' ');

        return {
            ...slice,
            path: pathData,
        };
    });
});

// Completion SVG Paths
const completionSvgPaths = computed(() => {
    if (total.value === 0) return [];

    const compFraction = completedCount.value / total.value;
    if (completedCount.value === total.value) {
        return [{ color: '#4e6b56', path: 'M 0 -1 A 1 1 0 1 1 -0.0001 -1 L 0 0 Z' }];
    }
    if (completedCount.value === 0) {
        return [{ color: '#b85c38', path: 'M 0 -1 A 1 1 0 1 1 -0.0001 -1 L 0 0 Z' }];
    }

    const [endX, endY] = getCoordinatesForPercent(compFraction - 0.25);
    const largeArcFlag = compFraction > 0.5 ? 1 : 0;

    const completedPath = [
        'M 0 -1',
        `A 1 1 0 ${largeArcFlag} 1 ${endX} ${endY}`,
        'L 0 0',
        'Z'
    ].join(' ');

    const remainingPath = [
        `M ${endX} ${endY}`,
        `A 1 1 0 ${1 - largeArcFlag} 1 0 -1`,
        'L 0 0',
        'Z'
    ].join(' ');

    return [
        { color: '#4e6b56', path: completedPath, label: 'Done' },
        { color: '#b85c38', path: remainingPath, label: 'Pending' },
    ];
});
</script>

<template>
    <div class="overflow-hidden rounded-2xl border border-border/80 bg-card shadow-xs transition-all duration-300">
        <!-- Banner Header with Toggle -->
        <div class="flex items-center justify-between border-b border-border/60 px-5 py-3.5 bg-muted/20">
            <div class="flex items-center gap-2.5">
                <span class="flex h-6 w-6 items-center justify-center rounded-full bg-[#b85c38]/15 text-[#b85c38]">
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M21.21 15.89A10 10 0 1 1 8 2.83"/>
                        <path d="M22 12A10 10 0 0 0 12 2v10z"/>
                    </svg>
                </span>
                <h2 class="text-sm font-semibold tracking-tight text-foreground">
                    Performance & Distribution Overview
                </h2>
            </div>

            <button
                type="button"
                @click="isExpanded = !isExpanded"
                class="inline-flex items-center gap-1.5 rounded-full border border-border bg-background px-3 py-1 text-xs font-medium text-muted-foreground transition-all duration-200 hover:bg-secondary hover:text-foreground active:scale-95"
            >
                <span>{{ isExpanded ? 'Collapse' : 'Expand' }}</span>
                <svg
                    :class="['h-3.5 w-3.5 transition-transform duration-200', isExpanded ? 'rotate-180' : '']"
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

        <!-- Expanded Content -->
        <div v-show="isExpanded" class="p-5 sm:p-6 transition-all duration-300">
            <!-- Stat Pill Badges -->
            <div class="mb-6 flex flex-wrap items-center gap-2">
                <div class="inline-flex items-center gap-2 rounded-full border border-border/80 bg-secondary/30 px-3.5 py-1 text-xs font-medium">
                    <span class="h-2 w-2 rounded-full bg-[#b85c38]" />
                    <span class="text-muted-foreground">Total:</span>
                    <span class="font-semibold text-foreground">{{ total }}</span>
                </div>

                <div class="inline-flex items-center gap-2 rounded-full border border-border/80 bg-secondary/30 px-3.5 py-1 text-xs font-medium">
                    <span class="h-2 w-2 rounded-full bg-[#4e6b56]" />
                    <span class="text-muted-foreground">Completed:</span>
                    <span class="font-semibold text-foreground">{{ completedCount }}</span>
                </div>

                <div class="inline-flex items-center gap-2 rounded-full border border-border/80 bg-secondary/30 px-3.5 py-1 text-xs font-medium">
                    <span class="h-2 w-2 rounded-full bg-[#395273]" />
                    <span class="text-muted-foreground">Active:</span>
                    <span class="font-semibold text-foreground">{{ activeCount }}</span>
                </div>

                <div v-if="recurringCount > 0" class="inline-flex items-center gap-2 rounded-full border border-border/80 bg-secondary/30 px-3.5 py-1 text-xs font-medium">
                    <svg class="h-3 w-3 text-muted-foreground" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="m17 2 4 4-4 4"/>
                        <path d="M3 11v-1a4 4 0 0 1 4-4h14"/>
                        <path d="m7 22-4-4 4-4"/>
                        <path d="M21 13v1a4 4 0 0 1-4 4H3"/>
                    </svg>
                    <span class="text-muted-foreground">Recurring:</span>
                    <span class="font-semibold text-foreground">{{ recurringCount }}</span>
                </div>
            </div>

            <!-- Two Pie / Donut Charts Side-by-Side -->
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2">

                <!-- 1. Category Distribution Pie Chart -->
                <div class="flex flex-col rounded-xl border border-border/60 bg-muted/10 p-4 sm:p-5">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        Category Distribution
                    </h3>

                    <div v-if="total === 0" class="flex flex-1 items-center justify-center py-8 text-xs text-muted-foreground">
                        Add tasks to see category distribution
                    </div>

                    <div v-else class="mt-4 flex flex-col items-center gap-5 sm:flex-row sm:items-center">
                        <!-- Donut Graphic -->
                        <div class="relative h-32 w-32 shrink-0">
                            <svg viewBox="-1.1 -1.1 2.2 2.2" class="h-full w-full transform -rotate-90">
                                <path
                                    v-for="slice in categorySvgPaths"
                                    :key="slice.name"
                                    :d="slice.path"
                                    :fill="slice.color"
                                    class="transition-opacity hover:opacity-85"
                                />
                                <!-- Inner cutout to make it a modern donut -->
                                <circle cx="0" cy="0" r="0.62" class="fill-card" />
                            </svg>
                            <!-- Center label -->
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                                <span class="font-mono text-xs font-bold text-foreground">{{ categorySlices.length }}</span>
                                <span class="text-[9px] uppercase tracking-wider text-muted-foreground">Cats</span>
                            </div>
                        </div>

                        <!-- Legend List -->
                        <div class="flex-1 space-y-1.5 w-full">
                            <div
                                v-for="slice in categorySlices"
                                :key="slice.name"
                                class="flex items-center justify-between text-xs"
                            >
                                <div class="flex items-center gap-2 truncate">
                                    <span class="h-2 w-2 shrink-0 rounded-full" :style="{ backgroundColor: slice.color }" />
                                    <span class="truncate font-medium text-foreground">{{ slice.name }}</span>
                                </div>
                                <div class="flex items-center gap-2 text-muted-foreground">
                                    <span>{{ slice.count }}</span>
                                    <span class="font-mono text-[10px] opacity-75">({{ slice.percent }}%)</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Completion Status Pie Chart -->
                <div class="flex flex-col rounded-xl border border-border/60 bg-muted/10 p-4 sm:p-5">
                    <h3 class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">
                        Task Completion Status
                    </h3>

                    <div v-if="total === 0" class="flex flex-1 items-center justify-center py-8 text-xs text-muted-foreground">
                        Add tasks to see completion status
                    </div>

                    <div v-else class="mt-4 flex flex-col items-center gap-5 sm:flex-row sm:items-center">
                        <!-- Donut Graphic -->
                        <div class="relative h-32 w-32 shrink-0">
                            <svg viewBox="-1.1 -1.1 2.2 2.2" class="h-full w-full transform -rotate-90">
                                <path
                                    v-for="(slice, i) in completionSvgPaths"
                                    :key="i"
                                    :d="slice.path"
                                    :fill="slice.color"
                                    class="transition-opacity hover:opacity-85"
                                />
                                <circle cx="0" cy="0" r="0.62" class="fill-card" />
                            </svg>
                            <div class="absolute inset-0 flex flex-col items-center justify-center text-center">
                                <span class="font-mono text-sm font-bold text-foreground">{{ completionRate }}%</span>
                                <span class="text-[9px] uppercase tracking-wider text-muted-foreground">Done</span>
                            </div>
                        </div>

                        <!-- Status Details Legend -->
                        <div class="flex-1 space-y-2.5 w-full">
                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-[#4e6b56]" />
                                    <span class="font-medium text-foreground">Completed</span>
                                </div>
                                <div class="flex items-center gap-1.5 text-muted-foreground">
                                    <span class="font-semibold text-foreground">{{ completedCount }}</span>
                                    <span class="font-mono text-[10px] opacity-75">({{ completionRate }}%)</span>
                                </div>
                            </div>

                            <div class="flex items-center justify-between text-xs">
                                <div class="flex items-center gap-2">
                                    <span class="h-2 w-2 rounded-full bg-[#b85c38]" />
                                    <span class="font-medium text-foreground">Incomplete / Active</span>
                                </div>
                                <div class="flex items-center gap-1.5 text-muted-foreground">
                                    <span class="font-semibold text-foreground">{{ activeCount }}</span>
                                    <span class="font-mono text-[10px] opacity-75">({{ 100 - completionRate }}%)</span>
                                </div>
                            </div>

                            <!-- Visual Completion Bar -->
                            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-secondary">
                                <div
                                    class="h-full rounded-full bg-[#4e6b56] transition-all duration-300"
                                    :style="{ width: `${completionRate}%` }"
                                />
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</template>
