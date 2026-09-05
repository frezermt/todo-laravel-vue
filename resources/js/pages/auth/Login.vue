<script setup lang="ts">
import { Form, Head } from '@inertiajs/vue3';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import TextLink from '@/components/TextLink.vue';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';
import PasskeyVerify from '@/components/PasskeyVerify.vue';

defineOptions({
    layout: {
        title: 'Welcome back',
        description: 'Enter your credentials to access your daily tasks and cadence.',
    },
});

defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();
</script>

<template>
    <Head title="Log in - Passo" />

    <div
        v-if="status"
        class="mb-4 rounded-xl bg-emerald-500/10 p-3 text-center text-xs font-medium text-emerald-700 dark:text-emerald-300 border border-emerald-500/20"
    >
        {{ status }}
    </div>

    <Form
        v-bind="store.form()"
        :reset-on-success="['password']"
        v-slot="{ errors, processing }"
        class="flex flex-col gap-4"
    >
        <!-- Email Field with Icon -->
        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <Label for="email" class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                    Email address
                </Label>
            </div>
            <div class="relative">
                <Input
                    id="email"
                    type="email"
                    name="email"
                    required
                    autofocus
                    :tabindex="1"
                    autocomplete="email"
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

        <!-- Password Field with Forgot Password Link -->
        <div class="space-y-1.5">
            <div class="flex items-center justify-between">
                <Label for="password" class="text-[11px] font-semibold uppercase tracking-wider text-muted-foreground">
                    Password
                </Label>
                <TextLink
                    v-if="canResetPassword"
                    :href="request()"
                    class="text-xs text-[#b85c38] hover:underline"
                    :tabindex="5"
                >
                    Forgot password?
                </TextLink>
            </div>
            <PasswordInput
                id="password"
                name="password"
                required
                :tabindex="2"
                autocomplete="current-password"
                placeholder="••••••••••••"
                class="rounded-xl text-sm"
            />
            <InputError :message="errors.password" />
        </div>

        <!-- Remember Me Checkbox -->
        <div class="flex items-center space-x-2 pt-1">
            <Checkbox id="remember" name="remember" :tabindex="3" class="rounded" />
            <Label for="remember" class="text-xs text-muted-foreground cursor-pointer select-none">
                Remember me for 30 days
            </Label>
        </div>

        <!-- Primary Sign In Pill Button -->
        <Button
            type="submit"
            class="mt-2 w-full rounded-full bg-[#b85c38] hover:bg-[#a34f2f] text-white text-xs h-10 shadow-xs font-semibold active:scale-98 transition-all"
            :tabindex="4"
            :disabled="processing"
            data-test="login-button"
        >
            <Spinner v-if="processing" />
            <span v-else class="flex items-center justify-center gap-1.5">
                <span>Sign in to Passo</span>
                <svg class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="5" y1="12" x2="19" y2="12"/>
                    <polyline points="12 5 19 12 12 19"/>
                </svg>
            </span>
        </Button>

        <!-- Passkey Verification -->
        <div class="pt-1">
            <PasskeyVerify separator="Or sign in with passkey" />
        </div>

        <!-- Sign Up Link -->
        <div class="text-center text-xs text-muted-foreground pt-1 border-t border-border/50 mt-1">
            Don't have an account?
            <TextLink :href="register()" class="font-semibold text-[#b85c38] hover:underline ml-1" :tabindex="5">
                Sign up
            </TextLink>
        </div>
    </Form>
</template>
