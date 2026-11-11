<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    user: Object,
});

const isAdminAccount = computed(() => props.user?.account_type === 'admin' || props.user?.is_admin === true);
const routeBase = computed(() => (isAdminAccount.value ? 'admin.admin-users' : 'admin.customers'));
const indexRoute = computed(() => (isAdminAccount.value ? 'admin.admin-users.index' : 'admin.customers.index'));

const destroyUser = () => {
    if (!confirm(`Delete ${props.user?.name || 'this user'}? This cannot be undone.`)) return;
    router.delete(route(`${routeBase.value}.destroy`, props.user.id));
};
</script>

<template>
    <Head :title="user?.name || 'User details'" />

    <AdminLayout>
        <template #header>User details</template>

        <div class="mx-auto max-w-4xl space-y-6">
            <div
                v-if="$page.props.flash?.status"
                class="rounded-xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200"
            >
                {{ $page.props.flash.status }}
            </div>
            <div
                v-if="$page.props.errors?.user"
                class="rounded-xl border border-rose-400/20 bg-rose-500/10 px-4 py-3 text-sm text-rose-200"
            >
                {{ $page.props.errors.user }}
            </div>

            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="flex items-center gap-4">
                    <div class="flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-500/15 text-xl font-semibold text-emerald-300 ring-1 ring-emerald-400/20">
                        {{ (user?.name || user?.email || user?.phone || 'U').charAt(0).toUpperCase() }}
                    </div>
                    <div>
                        <h1 class="text-2xl font-semibold tracking-tight text-white">{{ user?.name || 'Unnamed user' }}</h1>
                        <div class="mt-1 flex flex-wrap items-center gap-2">
                            <span
                                :class="[
                                    isAdminAccount
                                        ? 'bg-emerald-500/15 text-emerald-300 ring-emerald-400/20'
                                        : 'bg-sky-500/15 text-sky-300 ring-sky-400/20',
                                    'inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset',
                                ]"
                            >
                                {{ isAdminAccount ? 'Administrator' : 'Customer' }}
                            </span>
                            <span class="text-sm text-slate-400">ID #{{ user?.id }}</span>
                        </div>
                    </div>
                </div>
                <div class="flex flex-wrap gap-2">
                    <Link :href="route(`${routeBase}.edit`, user.id)" class="inline-flex items-center rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-400">Edit user</Link>
                    <button type="button" class="inline-flex items-center rounded-xl px-4 py-2 text-sm font-medium text-rose-300 ring-1 ring-rose-400/20 hover:bg-rose-500/10" @click="destroyUser">Delete</button>
                    <Link :href="route(indexRoute)" class="inline-flex items-center rounded-xl px-4 py-2 text-sm font-medium text-slate-300 ring-1 ring-white/10 hover:bg-white/5">Back</Link>
                </div>
            </div>

            <section class="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-5 sm:p-6">
                    <h2 class="text-base font-semibold text-white">Contact information</h2>
                    <dl class="mt-4 space-y-4 text-sm">
                        <div v-if="!isAdminAccount" class="flex flex-col gap-1 sm:flex-row sm:justify-between sm:gap-4">
                            <dt class="text-slate-400">Phone number</dt>
                            <dd class="font-medium text-slate-100">{{ user?.phone || '—' }}</dd>
                        </div>
                        <div v-if="isAdminAccount" class="flex flex-col gap-1 sm:flex-row sm:justify-between sm:gap-4">
                            <dt class="text-slate-400">Username</dt>
                            <dd class="font-medium text-slate-100">{{ user?.username || '—' }}</dd>
                        </div>
                        <div class="flex flex-col gap-1 sm:flex-row sm:justify-between sm:gap-4">
                            <dt class="text-slate-400">Email address</dt>
                            <dd class="font-medium text-slate-100">{{ user?.email || '—' }}</dd>
                        </div>
                    </dl>
                </div>

                <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-5 sm:p-6">
                    <h2 class="text-base font-semibold text-white">Account record</h2>
                    <dl class="mt-4 space-y-4 text-sm">
                        <div class="flex flex-col gap-1 sm:flex-row sm:justify-between sm:gap-4">
                            <dt class="text-slate-400">Account type</dt>
                            <dd class="font-medium text-slate-100">{{ isAdminAccount ? 'Dashboard User' : 'Mobile App Customer' }}</dd>
                        </div>
                        <div v-if="isAdminAccount" class="flex flex-col gap-1 sm:flex-row sm:justify-between sm:gap-4">
                            <dt class="text-slate-400">Role</dt>
                            <dd class="font-medium text-slate-100 capitalize">{{ (user?.role || 'administrator').replace('_', ' ') }}</dd>
                        </div>
                        <div class="flex flex-col gap-1 sm:flex-row sm:justify-between sm:gap-4">
                            <dt class="text-slate-400">Email verified</dt>
                            <dd class="font-medium text-slate-100">{{ user?.email_verified_at ? new Date(user.email_verified_at).toLocaleString() : 'Not verified' }}</dd>
                        </div>
                        <div class="flex flex-col gap-1 sm:flex-row sm:justify-between sm:gap-4">
                            <dt class="text-slate-400">Registered</dt>
                            <dd class="font-medium text-slate-100">{{ user?.created_at ? new Date(user.created_at).toLocaleString() : '—' }}</dd>
                        </div>
                        <div class="flex flex-col gap-1 sm:flex-row sm:justify-between sm:gap-4">
                            <dt class="text-slate-400">Last updated</dt>
                            <dd class="font-medium text-slate-100">{{ user?.updated_at ? new Date(user.updated_at).toLocaleString() : '—' }}</dd>
                        </div>
                    </dl>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
