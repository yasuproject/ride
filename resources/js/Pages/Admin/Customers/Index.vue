<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    users: Object,
    filters: Object,
    stats: Object,
});

const form = reactive({
    search: props.filters?.search || '',
    group: props.filters?.group || 'all',
});

let timeout = null;
const applyFilters = () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(route('admin.customers.index'), { ...form }, { preserveState: true, replace: true });
    }, 300);
};

const setGroup = (group) => {
    form.group = group;
    router.get(route('admin.customers.index'), { ...form }, { preserveState: true, replace: true });
};

const rows = (paginator) => paginator?.data ?? [];

const destroyUser = (user) => {
    if (!confirm(`Delete ${user.name || user.email || user.phone || 'this customer'}? This cannot be undone.`)) return;
    router.delete(route('admin.customers.destroy', user.id));
};

const blockUser = (user) => {
    if (!confirm(`Block ${user.name || user.phone || 'this customer'}? They will be signed out and unable to log in.`)) return;
    router.post(route('admin.customers.block', user.id), {}, { preserveScroll: true });
};

const unblockUser = (user) => {
    router.post(route('admin.customers.unblock', user.id), {}, { preserveScroll: true });
};

const groups = [
    { key: 'all', label: 'All customers' },
    { key: 'active', label: 'Active customers' },
    { key: 'blocked', label: 'Blocked customers' },
];
</script>

<template>
    <Head title="Customers" />

    <AdminLayout>
        <template #header>Customers</template>

        <div class="space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-white">Customers</h1>
                    <p class="mt-1 text-sm text-slate-400">
                        Mobile app customers are stored separately from dashboard/admin users. Manage all, active, and blocked customers.
                    </p>
                </div>
                <Link
                    :href="route('admin.customers.create')"
                    class="inline-flex items-center justify-center rounded-xl bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-slate-950 shadow-sm transition hover:bg-emerald-400"
                >
                    + Add customer
                </Link>
            </div>

            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <button type="button" @click="setGroup('all')" class="rounded-2xl border p-5 text-left transition"
                    :class="form.group === 'all' ? 'border-emerald-400/40 bg-emerald-500/10' : 'border-white/10 bg-slate-900/60 hover:bg-white/[0.03]'">
                    <p class="text-sm text-slate-400">All customers</p>
                    <p class="mt-2 text-3xl font-semibold text-white">{{ stats?.all ?? 0 }}</p>
                    <p class="mt-1 text-xs text-slate-500">Total app customers</p>
                </button>
                <button type="button" @click="setGroup('active')" class="rounded-2xl border p-5 text-left transition"
                    :class="form.group === 'active' ? 'border-emerald-400/40 bg-emerald-500/10' : 'border-white/10 bg-slate-900/60 hover:bg-white/[0.03]'">
                    <p class="text-sm text-slate-400">Active customers</p>
                    <p class="mt-2 text-3xl font-semibold text-emerald-300">{{ stats?.active ?? 0 }}</p>
                    <p class="mt-1 text-xs text-slate-500">Can sign in and use the app</p>
                </button>
                <button type="button" @click="setGroup('blocked')" class="rounded-2xl border p-5 text-left transition"
                    :class="form.group === 'blocked' ? 'border-rose-400/40 bg-rose-500/10' : 'border-white/10 bg-slate-900/60 hover:bg-white/[0.03]'">
                    <p class="text-sm text-slate-400">Blocked customers</p>
                    <p class="mt-2 text-3xl font-semibold text-rose-300">{{ stats?.blocked ?? 0 }}</p>
                    <p class="mt-1 text-xs text-slate-500">Blocked from accessing the app</p>
                </button>
                <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-5">
                    <p class="text-sm text-slate-400">New signups</p>
                    <p class="mt-2 text-3xl font-semibold text-sky-300">{{ stats?.today ?? 0 }}</p>
                    <p class="mt-1 text-xs text-slate-500">Today · {{ stats?.week ?? 0 }} in last 7 days</p>
                </div>
            </section>

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

            <section class="rounded-2xl border border-white/10 bg-slate-900/60 shadow-sm">
                <div class="border-b border-white/10 p-4">
                    <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="g in groups"
                                :key="g.key"
                                type="button"
                                @click="setGroup(g.key)"
                                :class="[
                                    form.group === g.key
                                        ? 'bg-white/10 text-white ring-1 ring-white/20'
                                        : 'text-slate-400 hover:text-slate-200 hover:bg-white/5',
                                    'rounded-xl px-4 py-2 text-sm font-medium transition'
                                ]"
                            >
                                {{ g.label }}
                                <span class="ml-1.5 rounded-md bg-white/10 px-1.5 py-0.5 text-xs text-slate-300">
                                    {{ g.key === 'all' ? (stats?.all ?? 0) : g.key === 'active' ? (stats?.active ?? 0) : (stats?.blocked ?? 0) }}
                                </span>
                            </button>
                        </div>
                        <div class="flex flex-1 flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                            <input
                                v-model="form.search"
                                type="text"
                                placeholder="Search by name, phone, or email..."
                                class="w-full rounded-xl border-white/10 bg-slate-950/60 text-sm text-slate-100 placeholder:text-slate-500 focus:border-emerald-400 focus:ring-emerald-400 sm:max-w-sm"
                                @input="applyFilters"
                            />
                            <p class="text-sm text-slate-400 whitespace-nowrap">Showing <span class="font-medium text-slate-200">{{ users?.total ?? 0 }}</span></p>
                        </div>
                    </div>
                </div>

                <div class="hidden overflow-x-auto lg:block">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-white/5 text-xs uppercase tracking-wide text-slate-400">
                            <tr>
                                <th class="px-5 py-3 font-medium">Customer</th>
                                <th class="px-5 py-3 font-medium">Contact</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                                <th class="px-5 py-3 font-medium">Registered</th>
                                <th class="px-5 py-3 font-medium text-right">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-for="user in rows(users)" :key="user.id" class="hover:bg-white/[0.02]">
                                <td class="px-5 py-4">
                                    <p class="font-medium text-white">{{ user.name || '—' }}</p>
                                    <p class="text-xs text-slate-500">ID #{{ user.id }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <p class="text-slate-200">{{ user.phone || '—' }}</p>
                                    <p class="text-xs text-slate-500">{{ user.email || 'No email' }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        :class="[
                                            user.is_blocked
                                                ? 'bg-rose-500/15 text-rose-300 ring-rose-400/20'
                                                : 'bg-emerald-500/15 text-emerald-300 ring-emerald-400/20',
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset',
                                        ]"
                                    >
                                        {{ user.is_blocked ? 'Blocked' : 'Active' }}
                                    </span>
                                    <p v-if="user.is_blocked && user.blocked_at" class="mt-1 text-xs text-slate-500">
                                        {{ new Date(user.blocked_at).toLocaleString() }}
                                    </p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-300">
                                    {{ user.created_at ? new Date(user.created_at).toLocaleString() : '—' }}
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2 flex-wrap">
                                        <Link :href="route('admin.customers.show', user.id)" class="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-200 ring-1 ring-white/10 hover:bg-white/5">View</Link>
                                        <Link :href="route('admin.customers.edit', user.id)" class="rounded-lg px-3 py-1.5 text-xs font-medium text-emerald-300 ring-1 ring-emerald-400/20 hover:bg-emerald-500/10">Edit</Link>
                                        <button
                                            v-if="!user.is_blocked"
                                            type="button"
                                            class="rounded-lg px-3 py-1.5 text-xs font-medium text-amber-300 ring-1 ring-amber-400/20 hover:bg-amber-500/10"
                                            @click="blockUser(user)"
                                        >Block</button>
                                        <button
                                            v-else
                                            type="button"
                                            class="rounded-lg px-3 py-1.5 text-xs font-medium text-sky-300 ring-1 ring-sky-400/20 hover:bg-sky-500/10"
                                            @click="unblockUser(user)"
                                        >Unblock</button>
                                        <button type="button" class="rounded-lg px-3 py-1.5 text-xs font-medium text-rose-300 ring-1 ring-rose-400/20 hover:bg-rose-500/10" @click="destroyUser(user)">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!rows(users).length">
                                <td colspan="5" class="px-5 py-12 text-center text-slate-500">
                                    {{ form.group === 'blocked' ? 'No blocked customers.' : form.group === 'active' ? 'No active customers.' : 'No customers found.' }}
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="divide-y divide-white/5 lg:hidden">
                    <article v-for="user in rows(users)" :key="user.id" class="p-4 sm:p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-white">{{ user.name || '—' }}</p>
                                <p class="mt-0.5 text-sm text-slate-300">{{ user.phone || user.email || 'No contact' }}</p>
                                <p class="text-xs text-slate-500">Registered {{ user.created_at ? new Date(user.created_at).toLocaleString() : '—' }}</p>
                            </div>
                            <span
                                :class="[
                                    user.is_blocked
                                        ? 'bg-rose-500/15 text-rose-300 ring-rose-400/20'
                                        : 'bg-emerald-500/15 text-emerald-300 ring-emerald-400/20',
                                    'shrink-0 inline-flex rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset',
                                ]"
                            >
                                {{ user.is_blocked ? 'Blocked' : 'Active' }}
                            </span>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Link :href="route('admin.customers.show', user.id)" class="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-200 ring-1 ring-white/10">View</Link>
                            <Link :href="route('admin.customers.edit', user.id)" class="rounded-lg px-3 py-1.5 text-xs font-medium text-emerald-300 ring-1 ring-emerald-400/20">Edit</Link>
                            <button v-if="!user.is_blocked" type="button" class="rounded-lg px-3 py-1.5 text-xs font-medium text-amber-300 ring-1 ring-amber-400/20" @click="blockUser(user)">Block</button>
                            <button v-else type="button" class="rounded-lg px-3 py-1.5 text-xs font-medium text-sky-300 ring-1 ring-sky-400/20" @click="unblockUser(user)">Unblock</button>
                            <button type="button" class="rounded-lg px-3 py-1.5 text-xs font-medium text-rose-300 ring-1 ring-rose-400/20" @click="destroyUser(user)">Delete</button>
                        </div>
                    </article>
                    <p v-if="!rows(users).length" class="p-8 text-center text-sm text-slate-500">
                        {{ form.group === 'blocked' ? 'No blocked customers.' : form.group === 'active' ? 'No active customers.' : 'No customers found.' }}
                    </p>
                </div>

                <nav
                    v-if="users?.links?.length > 3"
                    class="flex flex-wrap items-center justify-center gap-1 border-t border-white/10 px-4 py-4"
                    aria-label="Pagination"
                >
                    <template v-for="(link, i) in users.links" :key="i">
                        <span v-if="!link.url" class="rounded-lg px-3 py-2 text-sm text-slate-600" v-html="link.label" />
                        <Link
                            v-else
                            :href="link.url"
                            :class="[
                                link.active ? 'bg-emerald-500 text-slate-950' : 'text-slate-300 hover:bg-white/5 hover:text-white',
                                'rounded-lg px-3 py-2 text-sm font-medium transition',
                            ]"
                            preserve-scroll
                            v-html="link.label"
                        />
                    </template>
                </nav>
            </section>
        </div>
    </AdminLayout>
</template>
