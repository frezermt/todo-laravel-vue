<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { login } from '@/routes';
import { register } from '@/routes';
</script>

<template>
    <Head title="TodoApp">
        <link rel="preconnect" href="https://fonts.googleapis.com" />
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin="anonymous" />
        <link href="https://fonts.googleapis.com/css2?family=Newsreader:opsz,wght@6..72,400;6..72,500;6..72,600&display=swap" rel="stylesheet" />
    </Head>
    <div class="min-h-screen bg-[#f8f6f1] text-[#1a1815] dark:bg-[#111110] dark:text-[#e5e0d8]">

        <!-- Nav -->
        <header class="border-b border-[#e0dbd2] dark:border-[#2a2622]">
            <nav class="mx-auto flex max-w-3xl items-center justify-between px-6 py-4">
                <span class="text-base font-semibold tracking-tight">TodoApp</span>
                <div class="flex items-center gap-4">
                    <Link
                        v-if="$page.props.auth.user"
                        href="/todos"
                        class="rounded-[3px] bg-[#b85c38] px-4 py-1.5 text-sm font-medium text-white"
                    >
                        Open app
                    </Link>
                    <template v-else>
                        <Link
                            :href="login()"
                            class="text-sm font-medium text-[#6b655b] dark:text-[#a09a90]"
                        >
                            Log in
                        </Link>
                        <Link
                            :href="register()"
                            class="rounded-[3px] bg-[#1a1815] px-4 py-1.5 text-sm font-medium text-[#f8f6f1] dark:bg-[#e5e0d8] dark:text-[#111110]"
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
            <div class="mt-8">
                <Link
                    :href="$page.props.auth.user ? '/todos' : register()"
                    class="inline-block rounded-[3px] bg-[#1a1815] px-6 py-2.5 text-sm font-medium text-[#f8f6f1] dark:bg-[#e5e0d8] dark:text-[#111110]"
                >
                    {{ $page.props.auth.user ? 'Open your tasks' : 'Create an account' }}
                </Link>
            </div>
        </section>

        <!-- Product preview: real mockup of the actual app -->
        <section class="mx-auto max-w-3xl px-6 pb-20">
            <div class="overflow-hidden rounded-[3px] border border-[#ddd7cc] bg-[#fffefa] dark:border-[#2a2622] dark:bg-[#1a1815]">
                <div class="border-b border-[#e8e3da] px-5 py-3 dark:border-[#2a2622]">
                    <span class="text-sm font-medium">My tasks</span>
                    <span class="ml-2 text-sm text-[#a09a90]">4 remaining</span>
                </div>
                <ul>
                    <li class="flex items-center gap-3 border-b border-[#f0ece4] px-5 py-3 dark:border-[#1f1d1a]">
                        <span class="flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded-[2px] border border-[#b85c38] bg-[#b85c38]">
                            <svg class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        </span>
                        <span class="text-sm text-[#a09a90] line-through">Set up project repository</span>
                    </li>
                    <li class="flex items-center justify-between border-b border-[#f0ece4] px-5 py-3 dark:border-[#1f1d1a]">
                        <div class="flex items-center gap-3">
                            <span class="flex h-[18px] w-[18px] shrink-0 items-center justify-center rounded-[2px] border border-[#b85c38] bg-[#b85c38]">
                                <svg class="h-3 w-3 text-white" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                            </span>
                            <span class="text-sm text-[#a09a90] line-through">Write database migrations</span>
                        </div>
                        <span class="inline-flex items-center gap-1.5 text-[11px] text-[#a09a90]">
                            <span class="h-1.5 w-1.5 rounded-full bg-[#b85c38]"></span>
                            Engineering
                        </span>
                    </li>
                    <li class="flex items-center justify-between border-b border-[#f0ece4] px-5 py-3 dark:border-[#1f1d1a]">
                        <div class="flex items-center gap-3">
                            <span class="h-[18px] w-[18px] shrink-0 rounded-[2px] border border-[#ccc6bb] dark:border-[#3d3832]" />
                            <span class="text-sm">Design the task cards & calendar</span>
                        </div>
                        <span class="inline-flex items-center gap-1.5 text-[11px] text-[#6b655b] dark:text-[#a09a90]">
                            <span class="h-1.5 w-1.5 rounded-full bg-[#4e6b56]"></span>
                            Design
                        </span>
                    </li>
                    <li class="flex items-center justify-between border-b border-[#f0ece4] px-5 py-3 dark:border-[#1f1d1a]">
                        <div class="flex items-center gap-3">
                            <span class="h-[18px] w-[18px] shrink-0 rounded-[2px] border border-[#ccc6bb] dark:border-[#3d3832]" />
                            <span class="text-sm">Review weekly roadmap</span>
                        </div>
                        <span class="inline-flex items-center gap-1.5 text-[11px] text-[#6b655b] dark:text-[#a09a90]">
                            <span class="h-1.5 w-1.5 rounded-full bg-[#395273]"></span>
                            Work
                        </span>
                    </li>
                    <li class="flex items-center justify-between px-5 py-3">
                        <div class="flex items-center gap-3">
                            <span class="h-[18px] w-[18px] shrink-0 rounded-[2px] border border-[#ccc6bb] dark:border-[#3d3832]" />
                            <span class="text-sm">Deploy to production</span>
                        </div>
                        <span class="inline-flex items-center gap-1.5 text-[11px] text-[#6b655b] dark:text-[#a09a90]">
                            <span class="h-1.5 w-1.5 rounded-full bg-[#b8860b]"></span>
                            Projects
                        </span>
                    </li>
                </ul>
            </div>
        </section>

        <!-- Value props: 2-column, text only -->
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

        <!-- Footer -->
        <footer class="border-t border-[#e0dbd2] py-6 dark:border-[#2a2622]">
            <div class="mx-auto max-w-3xl px-6 text-sm text-[#a09a90]">
                TodoApp
            </div>
        </footer>
    </div>
</template>
