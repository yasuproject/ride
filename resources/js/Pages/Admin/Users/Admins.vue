<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { reactive } from 'vue';

const props = defineProps({
    users: Object,
    filters: Object,
    stats: Object,
    roles: Array,
});

const form = reactive({
    search: props.filters?.search || '',
    role: props.filters?.role || '',
});

let timeout = null;
const applyFilters = () => {
    clearTimeout(timeout);
    timeout = setTimeout(() => {
        router.get(route('admin.admin-users.index'), { ...form }, { preserveState: true, replace: true });
    }, 300);
};

const roleLabel = (role) => {
    const found = (props.roles || []).find((r) => r.value === role);
    if (found) return found.label;
    if (!role) return 'Administrator';
    return role.charAt(0).toUpperCase() + role.slice(1);
};

const rows = (paginator) => paginator?.data ?? [];

const destroyUser = (user) => {
    if (!confirm(`Delete admin ${user.name || user.email || user.phone || 'user'}? This cannot be undone.`)) return;
    router.delete(route('admin.admin-users.destroy', user.id));
};
</script>

<template>
    <Head title="User Management" />

    <AdminLayout>
        <template #header>User Management</template>

        <div class="space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-white">User management</h1>
                    <p class="mt-1 text-sm text-slate-400">Manage dashboard accounts and roles (Administrator, Manager, Support). Separate from mobile app customers.</p>
                </div>
                <Link
                    :href="route('admin.admin-users.create')"
                    class="inline-flex items-center justify-center rounded-xl bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-slate-950 shadow-sm transition hover:bg-emerald-400"
                >
                    + Add dashboard user
                </Link>
            </div>

            <section class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-5">
                    <p class="text-sm text-slate-400">Dashboard accounts</p>
                    <p class="mt-2 text-3xl font-semibold text-white">{{ stats?.total ?? 0 }}</p>
                </div>
                <div class="rounded-2xl border border-emerald-400/20 bg-emerald-500/5 p-5 sm:col-span-2">
                    <p class="text-sm font-medium text-emerald-200">Roles & access</p>
                    <p class="mt-1 text-sm text-slate-300">
                        Dashboard login uses <span class="font-medium text-emerald-200">username and password</span>. Assign roles such as Administrator, Manager, or Support. App customers are managed in
                        <Link :href="route('admin.customers.index')" class="text-emerald-300 underline underline-offset-2">Customers</Link>.
                    </p>
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
                <div class="flex flex-col gap-3 border-b border-white/10 p-4 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex flex-1 flex-col gap-3 sm:flex-row">
                        <input
                            v-model="form.search"
                            type="text"
                            placeholder="Search by name, username, email, or role..."
                            class="w-full rounded-xl border-white/10 bg-slate-950/60 text-sm text-slate-100 placeholder:text-slate-500 focus:border-emerald-400 focus:ring-emerald-400 sm:max-w-md"
                            @input="applyFilters"
                        />
                        <select
                            v-model="form.role"
                            class="rounded-xl border-white/10 bg-slate-950/60 text-sm text-slate-100 focus:border-emerald-400 focus:ring-emerald-400"
                            @change="applyFilters()"
                        >
                            <option value="">All roles</option>
                            <option v-for="r in roles" :key="r.value" :value="r.value">{{ r.label }}</option>
                        </select>
                    </div>
                    <p class="text-sm text-slate-400">Showing <span class="font-medium text-slate-200">{{ users?.total ?? 0 }}</span></p>
                </div>

                <div class="hidden overflow-x-auto lg:block">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-white/5 text-xs uppercase tracking-wide text-slate-400">
                            <tr>
                                <th class="px-5 py-3 font-medium">User</th>
                                <th class="px-5 py-3 font-medium">Login / Contact</th>
                                <th class="px-5 py-3 font-medium">Role</th>
                                <th class="px-5 py-3 font-medium">Created</th>
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
                                    <p class="text-slate-200">@{{ user.username || '—' }}</p>
                                    <p class="text-xs text-slate-500">{{ user.email || 'No email' }}</p>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="inline-flex rounded-full bg-emerald-500/15 px-2.5 py-1 text-xs font-medium text-emerald-300 ring-1 ring-inset ring-emerald-400/20">
                                        {{ roleLabel(user.role) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-300">
                                    {{ user.created_at ? new Date(user.created_at).toLocaleString() : '—' }}
                                </td>
                                <td class="px-5 py-4">
                                    <div class="flex items-center justify-end gap-2">
                                        <Link :href="route('admin.admin-users.show', user.id)" class="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-200 ring-1 ring-white/10 hover:bg-white/5">View</Link>
                                        <Link :href="route('admin.admin-users.edit', user.id)" class="rounded-lg px-3 py-1.5 text-xs font-medium text-emerald-300 ring-1 ring-emerald-400/20 hover:bg-emerald-500/10">Edit</Link>
                                        <button type="button" class="rounded-lg px-3 py-1.5 text-xs font-medium text-rose-300 ring-1 ring-rose-400/20 hover:bg-rose-500/10" @click="destroyUser(user)">Delete</button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!rows(users).length">
                                <td colspan="5" class="px-5 py-12 text-center text-slate-500">No dashboard users found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="divide-y divide-white/5 lg:hidden">
                    <article v-for="user in rows(users)" :key="user.id" class="p-4 sm:p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-white">{{ user.name || '—' }}</p>
                                <p class="mt-0.5 text-sm text-slate-300">@{{ user.username || '—' }}</p>
                                <p v-if="user.email" class="text-xs text-slate-500">{{ user.email }}</p>
                            </div>
                            <span class="shrink-0 inline-flex rounded-full bg-emerald-500/15 px-2.5 py-1 text-xs font-medium text-emerald-300 ring-1 ring-inset ring-emerald-400/20">{{ roleLabel(user.role) }}</span>
                        </div>
                        <div class="mt-4 flex flex-wrap gap-2">
                            <Link :href="route('admin.admin-users.show', user.id)" class="rounded-lg px-3 py-1.5 text-xs font-medium text-slate-200 ring-1 ring-white/10">View</Link>
                            <Link :href="route('admin.admin-users.edit', user.id)" class="rounded-lg px-3 py-1.5 text-xs font-medium text-emerald-300 ring-1 ring-emerald-400/20">Edit</Link>
                            <button type="button" class="rounded-lg px-3 py-1.5 text-xs font-medium text-rose-300 ring-1 ring-rose-400/20" @click="destroyUser(user)">Delete</button>
                        </div>
                    </article>
                    <p v-if="!rows(users).length" class="p-8 text-center text-sm text-slate-500">No dashboard users found.</p>
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
