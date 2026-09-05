<script setup lang="ts">
import { computed, ref } from 'vue';
import { Head, Link } from '@inertiajs/vue3';
import { login } from '@/routes';
import { register } from '@/routes';

interface PreviewTask {
    id: number;
    title: string;
    category: string;
    color: string;
    completed: boolean;
}

const previewTasks = ref<PreviewTask[]>([
    { id: 1, title: 'Set up project repository', category: 'Engineering', color: '#b85c38', completed: true },
    { id: 2, title: 'Write database migrations', category: 'Engineering', color: '#b85c38', completed: true },
    { id: 3, title: 'Design the task cards & calendar', category: 'Design', color: '#4e6b56', completed: false },
    { id: 4, title: 'Review weekly roadmap', category: 'Work', color: '#395273', completed: false },
    { id: 5, title: 'Deploy to production', category: 'Projects', color: '#b8860b', completed: false },
]);

const remainingCount = computed(() => previewTasks.value.filter((t) => !t.completed).length);

function togglePreviewTask(task: PreviewTask) {
    task.completed = !task.completed;
}
</script>

<template>
    <Head title="Passo — A task list that stays out of your way">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,400;6..72,500;6..72,600&display=swap" rel="stylesheet" />
    </Head>
    <div class="min-h-screen bg-[#f8f6f1] text-[#1a1815] dark:bg-[#111110] dark:text-[#e5e0d8] selection:bg-[#b85c38]/20 selection:text-[#b85c38]">

        <!-- Nav -->
        <header class="border-b border-[#e0dbd2] dark:border-[#2a2622]">
            <nav class="mx-auto flex max-w-3xl items-center justify-between px-6 py-4">
                <Link href="/" class="flex items-center gap-2.5 group">
                    <div class="flex aspect-square size-7 items-center justify-center rounded-md bg-[#b85c38] text-white shadow-2xs transition-transform group-hover:scale-105">
                        <svg class="size-4 stroke-white" viewBox="0 0 24 24" fill="none" stroke-width="2.8" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </div>
                    <span class="text-base font-bold tracking-tight text-[#1a1815] dark:text-[#f8f6f1]">Passo</span>
                </Link>

                <div class="flex items-center gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        href="/todos"
                        class="rounded-[3px] bg-[#b85c38] px-4 py-1.5 text-sm font-medium text-white transition-opacity hover:opacity-90 active:scale-98"
                    >
                        Open app
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="text-sm font-medium text-[#6b655b] hover:text-[#1a1815] dark:text-[#a09a90] dark:hover:text-[#f8f6f1] transition-colors"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="register()"
                            class="rounded-[3px] bg-[#1a1815] px-4 py-1.5 text-sm font-medium text-[#f8f6f1] transition-all hover:bg-black active:scale-98 dark:bg-[#e5e0d8] dark:text-[#111110] dark:hover:bg-white"
                        >
                            Sign up
                        </Link>
                    </template>
                </div>
            </nav>
        </header>

        <!-- Hero -->
        <section class="mx-auto max-w-3xl px-6 pt-20 pb-16">
            <h1
                class="text-5xl font-medium leading-[1.12] tracking-tight sm:text-6xl lg:text-[4.25rem]"
                style="font-family: 'Newsreader', serif;"
            >
                A task list that<br />stays out of your way.
            </h1>
            <p class="mt-5 max-w-lg text-lg leading-relaxed text-[#6b655b] dark:text-[#a09a90]">
                Add what you need to do. Check it off when it's done. No complexity, no distractions, no learning curve.
            </p>
            <div class="mt-8 flex items-center gap-3">
                <Link
                    :href="$page.props.auth.user ? '/todos' : register()"
                    class="inline-flex items-center gap-2 rounded-[3px] bg-[#1a1815] px-6 py-2.5 text-sm font-medium text-[#f8f6f1] transition-all hover:bg-black active:scale-98 dark:bg-[#e5e0d8] dark:text-[#111110] dark:hover:bg-white shadow-2xs"
                >
                    <span>{{ $page.props.auth.user ? 'Open your tasks' : 'Create an account' }}</span>
                    <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <polyline points="9 18 15 12 9 6"/>
                    </svg>
                </Link>
            </div>
        </section>

        <!-- Product preview: real interactive mockup of the actual app -->
        <section class="mx-auto max-w-3xl px-6 pb-20">
            <div class="overflow-hidden rounded-[4px] border border-[#ddd7cc] bg-[#fffefa] shadow-2xs dark:border-[#2a2622] dark:bg-[#1a1815]">
                <div class="flex items-center justify-between border-b border-[#e8e3da] px-5 py-3.5 dark:border-[#2a2622]">
                    <div class="flex items-center gap-2">
                        <span class="text-sm font-semibold tracking-tight">My tasks</span>
                        <span class="text-xs text-[#a09a90] font-medium">
                            {{ remainingCount }} remaining
                        </span>
                    </div>
                    <span class="text-[11px] text-[#a09a90] select-none italic">
                        click tasks to try
                    </span>
                </div>

                <ul class="divide-y divide-[#f0ece4] dark:divide-[#1f1d1a]">
                    <li
                        v-for="task in previewTasks"
                        :key="task.id"
                        @click="togglePreviewTask(task)"
                        class="flex items-center justify-between px-5 py-3 cursor-pointer transition-colors duration-150 hover:bg-[#faf7f2] dark:hover:bg-[#201e1a] select-none"
                    >
                        <div class="flex items-center gap-3">
                            <span
                                :class="[
                                    'flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded-[2px] transition-all duration-150',
                                    task.completed
                                        ? 'border border-[#b85c38] bg-[#b85c38]'
                                        : 'border border-[#ccc6bb] dark:border-[#3d3832] bg-transparent'
                                ]"
                            >
                                <svg
                                    v-if="task.completed"
                                    class="h-3 w-3 text-white"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="3"
                                    stroke-linecap="round"
                                    stroke-linejoin="round"
                                >
                                    <polyline points="20 6 9 17 4 12"/>
                                </svg>
                            </span>
                            <span
                                :class="[
                                    'text-sm transition-all duration-150',
                                    task.completed
                                        ? 'text-[#a09a90] line-through'
                                        : 'text-[#1a1815] dark:text-[#e5e0d8]'
                                ]"
                            >
                                {{ task.title }}
                            </span>
                        </div>
                        <span class="inline-flex items-center gap-1.5 text-[11px] text-[#6b655b] dark:text-[#a09a90]">
                            <span
                                class="h-1.5 w-1.5 rounded-full"
                                :style="{ backgroundColor: task.color }"
                            />
                            {{ task.category }}
                        </span>
                    </li>
                </ul>
            </div>
        </section>

        <!-- Principles / Highlights: 2-column minimalist -->
        <section class="border-t border-[#e0dbd2] dark:border-[#2a2622]">
            <div class="mx-auto max-w-3xl px-6 py-16">
                <div class="grid gap-12 sm:grid-cols-2 sm:gap-10">
                    <div>
                        <h3 class="text-lg font-medium" style="font-family: 'Newsreader', serif;">Built for focus</h3>
                        <p class="mt-2 text-sm leading-relaxed text-[#6b655b] dark:text-[#a09a90]">
                            Thoughtful categories, calendar planning, and color choices. Just enough structure to keep you focused and organized without noisy distractions.
                        </p>
                    </div>
                    <div>
                        <h3 class="text-lg font-medium" style="font-family: 'Newsreader', serif;">Yours, private</h3>
                        <p class="mt-2 text-sm leading-relaxed text-[#6b655b] dark:text-[#a09a90]">
                            Your tasks are tied to your account. Nobody else can see them. No sharing, no collaboration features, no noise.
                        </p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Bottom CTA -->
        <section class="border-t border-[#e0dbd2] dark:border-[#2a2622] bg-[#f2efe9]/40 dark:bg-[#151413]/40">
            <div class="mx-auto max-w-3xl px-6 py-14 text-center">
                <h2 class="text-2xl sm:text-3xl font-medium tracking-tight" style="font-family: 'Newsreader', serif;">
                    Ready to focus on what matters?
                </h2>
                <p class="mt-2 text-sm text-[#6b655b] dark:text-[#a09a90] max-w-md mx-auto">
                    No complicated setup or noisy feeds. Just open Passo and get things done.
                </p>
                <div class="mt-6 flex justify-center">
                    <Link
                        :href="$page.props.auth.user ? '/todos' : register()"
                        class="inline-flex items-center gap-2 rounded-[3px] bg-[#1a1815] px-6 py-2.5 text-sm font-medium text-[#f8f6f1] transition-all hover:bg-black active:scale-98 dark:bg-[#e5e0d8] dark:text-[#111110] dark:hover:bg-white shadow-2xs"
                    >
                        <span>{{ $page.props.auth.user ? 'Open your tasks' : 'Create an account' }}</span>
                        <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 18 15 12 9 6"/>
                        </svg>
                    </Link>
                </div>
            </div>
        </section>

        <!-- Footer -->
        <footer class="border-t border-[#e0dbd2] py-8 dark:border-[#2a2622]">
            <div class="mx-auto flex max-w-3xl flex-col sm:flex-row items-center justify-between gap-4 px-6 text-xs text-[#a09a90]">
                <div class="flex items-center gap-2">
                    <div class="flex aspect-square size-4 items-center justify-center rounded-[2px] bg-[#b85c38] text-white">
                        <svg class="size-2.5 stroke-white" viewBox="0 0 24 24" fill="none" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </div>
                    <span class="font-semibold text-[#6b655b] dark:text-[#c4beb3]">Passo</span>
                    <span>— A task list that stays out of your way.</span>
                </div>
                <div>
                    <span>Simple, focused productivity.</span>
                </div>
            </div>
        </footer>
    </div>
</template>
