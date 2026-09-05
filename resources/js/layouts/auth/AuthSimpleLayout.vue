<script setup lang="ts">
import { Link, router } from '@inertiajs/vue3';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();

function goBack() {
    if (window.history.length > 1) {
        window.history.back();
    } else {
        router.visit('/');
    }
}
</script>

<template>
    <div
        class="bg-muted/30 relative flex min-h-svh flex-col items-center justify-center p-4 sm:p-6 md:p-10"
    >
        <!-- Top-left Back Arrow Button -->
        <button
            type="button"
            @click="goBack"
            aria-label="Go back"
            class="absolute top-5 left-5 inline-flex items-center gap-1.5 rounded-full border border-border bg-card px-3.5 py-1.5 text-xs font-medium text-muted-foreground shadow-2xs transition-all duration-200 hover:bg-secondary hover:text-foreground active:scale-95"
        >
            <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="19" y1="12" x2="5" y2="12"/>
                <polyline points="12 19 5 12 12 5"/>
            </svg>
            <span>Back</span>
        </button>

        <div class="w-full max-w-md space-y-6">
            <!-- Top Center Branding & Heading (Matches Screenshot) -->
            <div class="flex flex-col items-center text-center">
                <!-- App Icon Badge with Glow -->
                <Link :href="home()" class="group mb-3 flex flex-col items-center">
                    <div class="flex aspect-square h-12 w-12 items-center justify-center rounded-2xl bg-[#b85c38] text-white shadow-md shadow-[#b85c38]/20 transition-transform group-hover:scale-105">
                        <svg class="h-6 w-6 stroke-white" viewBox="0 0 24 24" fill="none" stroke-width="3" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="20 6 9 17 4 12"/>
                        </svg>
                    </div>
                </Link>

                <div class="mb-1.5">
                    <span class="text-sm font-semibold tracking-tight text-foreground/80">Passo</span>
                </div>

                <h1 class="text-2xl font-bold tracking-tight text-foreground sm:text-3xl">
                    {{ title }}
                </h1>
                <p v-if="description" class="mt-1 text-xs sm:text-sm text-muted-foreground max-w-xs leading-relaxed">
                    {{ description }}
                </p>
            </div>

            <!-- Form Card Container (Centered Card matching Screenshot) -->
            <div class="rounded-2xl border border-border/80 bg-card p-6 sm:p-8 shadow-xs">
                <slot />
            </div>

            <!-- Bottom Security & Privacy Badge (Matches Screenshot) -->
            
        </div>
    </div>
</template>
