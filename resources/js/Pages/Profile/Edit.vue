<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import InputError from '@/Components/InputError.vue';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    mustVerifyEmail: {
        type: Boolean,
    },
    status: {
        type: String,
    },
});

const page = usePage();
const user = page.props.auth.user;

const infoForm = useForm({
    name: user?.name || '',
    email: user?.email || '',
});

const passwordInput = ref(null);
const currentPasswordInput = ref(null);

const passwordForm = useForm({
    current_password: '',
    password: '',
    password_confirmation: '',
});

const updatePassword = () => {
    passwordForm.put(route('password.update'), {
        preserveScroll: true,
        onSuccess: () => passwordForm.reset(),
        onError: () => {
            if (passwordForm.errors.password) {
                passwordForm.reset('password', 'password_confirmation');
                passwordInput.value?.focus();
            }
            if (passwordForm.errors.current_password) {
                passwordForm.reset('current_password');
                currentPasswordInput.value?.focus();
            }
        },
    });
};

const submitInfo = () => {
    infoForm.patch(route('admin.profile.update'), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="My Account" />

    <AdminLayout>
        <template #header>My Account</template>

        <div class="mx-auto max-w-4xl space-y-6">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-white">Account settings</h1>
                <p class="mt-1 text-sm text-slate-400">Manage your administrator profile and update your password.</p>
            </div>

            <div
                v-if="status || page.props.flash?.status"
                class="rounded-xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200"
            >
                {{ status || page.props.flash?.status }}
            </div>

            <section class="rounded-2xl border border-white/10 bg-slate-900/60 p-5 sm:p-6">
                <h2 class="text-base font-semibold text-white">Profile information</h2>
                <p class="mt-1 text-sm text-slate-400">Update your display name and email address.</p>

                <form @submit.prevent="submitInfo" class="mt-5 space-y-4 max-w-xl">
                    <div>
                        <label for="name" class="block text-sm font-medium text-slate-200">Name</label>
                        <input
                            id="name"
                            type="text"
                            v-model="infoForm.name"
                            required
                            autocomplete="name"
                            class="mt-1 block w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400"
                        />
                        <InputError class="mt-2" :message="infoForm.errors.name" />
                    </div>

                    <div>
                        <label for="email" class="block text-sm font-medium text-slate-200">Email</label>
                        <input
                            id="email"
                            type="email"
                            v-model="infoForm.email"
                            required
                            autocomplete="username"
                            class="mt-1 block w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400"
                        />
                        <InputError class="mt-2" :message="infoForm.errors.email" />
                    </div>

                    <div v-if="mustVerifyEmail && user?.email_verified_at === null" class="text-sm text-slate-300">
                        Your email address is unverified.
                        <Link
                            :href="route('verification.send')"
                            method="post"
                            as="button"
                            class="text-emerald-300 underline hover:text-emerald-200"
                        >
                            Re-send verification email
                        </Link>
                    </div>

                    <div class="flex items-center gap-4 pt-1">
                        <button
                            type="submit"
                            :disabled="infoForm.processing"
                            class="inline-flex items-center justify-center rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-slate-950 hover:bg-emerald-400 disabled:opacity-60"
                        >
                            Save profile
                        </button>
                        <p v-if="infoForm.recentlySuccessful" class="text-sm text-emerald-300">Saved.</p>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-white/10 bg-slate-900/60 p-5 sm:p-6">
                <h2 class="text-base font-semibold text-white">Change password</h2>
                <p class="mt-1 text-sm text-slate-400">Use a strong, unique password to protect administrator access.</p>

                <form @submit.prevent="updatePassword" class="mt-5 space-y-4 max-w-xl">
                    <div>
                        <label for="current_password" class="block text-sm font-medium text-slate-200">Current password</label>
                        <input
                            id="current_password"
                            ref="currentPasswordInput"
                            v-model="passwordForm.current_password"
                            type="password"
                            autocomplete="current-password"
                            class="mt-1 block w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400"
                        />
                        <InputError :message="passwordForm.errors.current_password" class="mt-2" />
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-slate-200">New password</label>
                        <input
                            id="password"
                            ref="passwordInput"
                            v-model="passwordForm.password"
                            type="password"
                            autocomplete="new-password"
                            class="mt-1 block w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400"
                        />
                        <InputError :message="passwordForm.errors.password" class="mt-2" />
                    </div>

                    <div>
                        <label for="password_confirmation" class="block text-sm font-medium text-slate-200">Confirm new password</label>
                        <input
                            id="password_confirmation"
                            v-model="passwordForm.password_confirmation"
                            type="password"
                            autocomplete="new-password"
                            class="mt-1 block w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400"
                        />
                        <InputError :message="passwordForm.errors.password_confirmation" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4 pt-1">
                        <button
                            type="submit"
                            :disabled="passwordForm.processing"
                            class="inline-flex items-center justify-center rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-slate-950 hover:bg-emerald-400 disabled:opacity-60"
                        >
                            Update password
                        </button>
                        <p v-if="passwordForm.recentlySuccessful" class="text-sm text-emerald-300">Password updated.</p>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-white/10 bg-slate-900/60 p-5 sm:p-6">
                <h2 class="text-base font-semibold text-white">User & admin management</h2>
                <p class="mt-2 text-sm text-slate-400">
                    Signed in as <span class="font-medium text-slate-200">{{ user?.email || user?.name }}</span>.
                    Manage registered passengers, create accounts, and grant or revoke administrator access from the
                    <Link :href="route('admin.users.index')" class="text-emerald-300 hover:text-emerald-200 underline">Users</Link>
                    section.
                </p>
            </section>
        </div>
    </AdminLayout>
</template>
