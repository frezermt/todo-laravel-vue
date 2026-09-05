<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineProps<{
    passwordRules: string;
}>();

defineOptions({
    layout: {
        title: 'Get started with Passo',
        description: 'Create your account to organize your tasks and daily cadence.',
    },
});
</script>

<template>
    <Head title="Get Started - Passo" />

    <Form
        v-bind="store.form()"
        :reset-on-success="['password', 'password_confirmation']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-4"
    >
        <!-- Name Field -->
        <div class="space-y-1.5">
            <Label for="name" class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                Full Name
            </Label>
            <div class="relative">
                <Input
                    id="name"
                    type="text"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="name"
                    name="name"
                    placeholder="Alex Morgan"
                    class="rounded-xl pr-9 text-sm"
                />
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground/60">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                        <circle cx="12" cy="7" r="4"/>
                    </svg>
                </div>
            </div>
            <InputError :message="errors.name" />
        </div>

        <!-- Email Field -->
        <div class="space-y-1.5">
            <Label for="email" class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                Email address
            </Label>
            <div class="relative">
                <Input
                    id="email"
                    type="email"
                    required
                    :tabindex="2"
                    autocomplete="email"
                    name="email"
                    placeholder="alex@example.com"
                    class="rounded-xl pr-9 text-sm"
                />
                <div class="pointer-events-none absolute inset-y-0 right-0 flex items-center pr-3 text-muted-foreground/60">
                    <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <rect width="20" height="16" x="2" y="4" rx="2"/>
                        <path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/>
                    </svg>
                </div>
            </div>
            <InputError :message="errors.email" />
        </div>

        <!-- Password Field -->
        <div class="space-y-1.5">
            <Label for="password" class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                Password
            </Label>
            <PasswordInput
                id="password"
                required
                :tabindex="3"
                autocomplete="new-password"
                name="password"
                placeholder="Choose a secure password"
                :passwordrules="passwordRules"
                class="rounded-xl text-sm"
            />
            <InputError :message="errors.password" />
        </div>

        <!-- Confirm Password Field -->
        <div class="space-y-1.5">
            <Label for="password_confirmation" class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                Confirm password
            </Label>
            <PasswordInput
                id="password_confirmation"
                required
                :tabindex="4"
                autocomplete="new-password"
                name="password_confirmation"
                placeholder="Confirm your password"
                :passwordrules="passwordRules"
                class="rounded-xl text-sm"
            />
            <InputError :message="errors.password_confirmation" />
        </div>

        <!-- Submit Button -->
        <Button
            type="submit"
            class="mt-2 w-full rounded-full bg-[#b85c38] hover:bg-[#a34f2f] text-white text-xs h-10 shadow-xs font-semibold active:scale-98 transition-all"
            :tabindex="5"
            :disabled="processing"
            data-test="register-user-button"
        >
            <Spinner v-if="processing" />
            <span v-else class="flex items-center justify-center gap-1.5">
                <span>Create your account</span>
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </Button>

        <!-- Log In Link -->
        <div class="text-center text-xs text-muted-foreground pt-2 border-t border-border/50 mt-1">
            Already have an account?
            <TextLink
                :href="login()"
                class="font-semibold text-[#b85c38] hover:underline ml-1"
                :tabindex="6"
            >
                Log in
            </TextLink>
        </div>
    </Form>
</template>
