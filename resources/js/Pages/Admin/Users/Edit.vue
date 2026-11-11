<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    user: Object,
});

const isAdminAccount = computed(() => props.user?.account_type === 'admin' || props.user?.is_admin === true);
const routeBase = computed(() => (isAdminAccount.value ? 'admin.admin-users' : 'admin.registered-users'));
const indexRoute = computed(() => (isAdminAccount.value ? 'admin.admin-users.index' : 'admin.registered-users.index'));

const form = useForm({
    name: props.user?.name ?? '',
    email: props.user?.email ?? '',
    phone: props.user?.phone ?? '',
    password: '',
    password_confirmation: '',
    remove_password: false,
});

const submit = () => {
    form.put(route(`${routeBase.value}.update`, props.user.id), {
        preserveScroll: true,
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head :title="`Edit ${user?.name || 'User'}`" />

    <AdminLayout>
        <template #header>Edit user</template>

        <div class="mx-auto max-w-3xl space-y-6">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-white">Edit user</h1>
                    <p class="mt-1 text-sm text-slate-400">
                        {{ isAdminAccount ? 'Update admin panel account details and credentials.' : 'Update registered app user details and credentials.' }}
                    </p>
                </div>
                <div class="flex gap-2">
                    <Link :href="route(`${routeBase}.show`, user.id)" class="rounded-lg px-3 py-2 text-sm text-slate-300 ring-1 ring-white/10 hover:bg-white/5">View</Link>
                    <Link :href="route(indexRoute)" class="rounded-lg px-3 py-2 text-sm text-slate-300 hover:text-white">← Back</Link>
                </div>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <section class="rounded-2xl border border-white/10 bg-slate-900/60 p-5 sm:p-6">
                    <h2 class="text-base font-semibold text-white">Account details</h2>
                    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-200">Full name</label>
                            <input v-model="form.name" type="text" class="mt-1 w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400" />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-rose-400">{{ form.errors.name }}</p>
                        </div>
                        <div v-if="!isAdminAccount">
                            <label class="block text-sm font-medium text-slate-200">Phone number</label>
                            <input v-model="form.phone" type="text" class="mt-1 w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400" placeholder="+2519..." />
                            <p v-if="form.errors.phone" class="mt-1 text-sm text-rose-400">{{ form.errors.phone }}</p>
                        </div>
                        <div :class="isAdminAccount ? 'sm:col-span-2' : ''">
                            <label class="block text-sm font-medium text-slate-200">Email address</label>
                            <input v-model="form.email" type="email" class="mt-1 w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400" />
                            <p v-if="form.errors.email" class="mt-1 text-sm text-rose-400">{{ form.errors.email }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-white/10 bg-slate-900/60 p-5 sm:p-6">
                    <h2 class="text-base font-semibold text-white">Security & access</h2>
                    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-200">New password <span class="font-normal text-slate-500">(leave blank to keep)</span></label>
                            <input v-model="form.password" type="password" class="mt-1 w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400" autocomplete="new-password" />
                            <p v-if="form.errors.password" class="mt-1 text-sm text-rose-400">{{ form.errors.password }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-200">Confirm new password</label>
                            <input v-model="form.password_confirmation" type="password" class="mt-1 w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400" autocomplete="new-password" />
                        </div>
                        <div v-if="!isAdminAccount" class="sm:col-span-2">
                            <label class="flex items-center gap-2 text-sm text-slate-300">
                                <input v-model="form.remove_password" type="checkbox" class="rounded border-white/20 bg-slate-950/60 text-emerald-500 focus:ring-emerald-400" />
                                Remove password (OTP-only account, no password login)
                            </label>
                        </div>
                        <div v-if="isAdminAccount" class="sm:col-span-2 rounded-xl border border-emerald-400/20 bg-emerald-500/5 p-4">
                            <p class="text-sm font-medium text-emerald-200">Administrator account</p>
                            <p class="text-xs text-slate-300">This account belongs to the admin panel (separate from mobile app registered users).</p>
                        </div>
                    </div>
                </section>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Link :href="route(`${routeBase}.show`, user.id)" class="inline-flex items-center justify-center rounded-xl px-5 py-2.5 text-sm font-medium text-slate-300 ring-1 ring-white/10 hover:bg-white/5">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-slate-950 hover:bg-emerald-400 disabled:opacity-60">
                        Save changes
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
