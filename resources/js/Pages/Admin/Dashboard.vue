<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    stats: Object,
    customerStats: Object,
    recentRides: Array,
    recentCustomers: Array,
});

const statusStyles = {
    requested: 'bg-amber-500/15 text-amber-300 ring-amber-400/20',
    assigned: 'bg-sky-500/15 text-sky-300 ring-sky-400/20',
    in_progress: 'bg-violet-500/15 text-violet-300 ring-violet-400/20',
    completed: 'bg-emerald-500/15 text-emerald-300 ring-emerald-400/20',
    cancelled: 'bg-rose-500/15 text-rose-300 ring-rose-400/20',
};

const formatStatus = (s) => (s ? s.replace('_', ' ') : '—');
const formatDate = (d) => (d ? new Date(d).toLocaleString() : '—');
</script>

<template>
    <Head title="Admin Dashboard" />

    <AdminLayout>
        <template #header>Dashboard</template>

        <div class="space-y-6">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-white">Operations overview</h1>
                <p class="mt-1 text-sm text-slate-400">Monitor rides, customer signups, and recent activity.</p>
            </div>

            <section class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-5 shadow-sm">
                    <p class="text-sm text-slate-400">Total rides</p>
                    <p class="mt-2 text-3xl font-semibold text-white">{{ stats?.total ?? 0 }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-5 shadow-sm">
                    <p class="text-sm text-slate-400">Active rides</p>
                    <p class="mt-2 text-3xl font-semibold text-sky-300">{{ stats?.active ?? 0 }}</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-5 shadow-sm">
                    <p class="text-sm text-slate-400">Total customers</p>
                    <p class="mt-2 text-3xl font-semibold text-emerald-300">{{ customerStats?.total ?? 0 }}</p>
                    <p class="mt-1 text-xs text-slate-500">Registered in the app</p>
                </div>
                <div class="rounded-2xl border border-white/10 bg-slate-900/60 p-5 shadow-sm">
                    <p class="text-sm text-slate-400">New signups</p>
                    <p class="mt-2 text-3xl font-semibold text-violet-300">{{ customerStats?.today ?? 0 }}</p>
                    <p class="mt-1 text-xs text-slate-500">Today · {{ customerStats?.week ?? 0 }} in last 7 days</p>
                </div>
            </section>

            <section class="rounded-2xl border border-white/10 bg-slate-900/60 shadow-sm">
                <div class="flex flex-col gap-3 border-b border-white/10 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-white">Recent customers (signups)</h2>
                        <p class="text-sm text-slate-400">Accounts created from the Flutter app and admin panel.</p>
                    </div>
                    <Link
                        :href="route('admin.registered-users.index')"
                        class="inline-flex items-center justify-center rounded-xl bg-emerald-500 px-4 py-2 text-sm font-semibold text-slate-950 hover:bg-emerald-400"
                    >
                        View registered users
                    </Link>
                </div>

                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-white/5 text-xs uppercase tracking-wide text-slate-400">
                            <tr>
                                <th class="px-5 py-3 font-medium">Customer</th>
                                <th class="px-5 py-3 font-medium">Phone</th>
                                <th class="px-5 py-3 font-medium">Email</th>
                                <th class="px-5 py-3 font-medium">Signed up</th>
                                <th class="px-5 py-3 font-medium text-right">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-for="user in recentCustomers" :key="user.id" class="hover:bg-white/[0.02]">
                                <td class="px-5 py-4">
                                    <p class="font-medium text-white">{{ user.name || '—' }}</p>
                                    <p class="text-xs text-slate-500">ID #{{ user.id }}</p>
                                </td>
                                <td class="px-5 py-4 text-slate-200">{{ user.phone || '—' }}</td>
                                <td class="px-5 py-4 text-slate-300">{{ user.email || '—' }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-slate-300">{{ formatDate(user.created_at) }}</td>
                                <td class="px-5 py-4 text-right">
                                    <Link :href="route('admin.registered-users.show', user.id)" class="text-sm font-medium text-emerald-300 hover:text-emerald-200">View</Link>
                                </td>
                            </tr>
                            <tr v-if="!recentCustomers?.length">
                                <td colspan="5" class="px-5 py-10 text-center text-slate-500">No customer signups yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="divide-y divide-white/5 md:hidden">
                    <article v-for="user in recentCustomers" :key="user.id" class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-white">{{ user.name || '—' }}</p>
                                <p class="text-sm text-slate-300">{{ user.phone || user.email || 'No contact' }}</p>
                            </div>
                            <Link :href="route('admin.registered-users.show', user.id)" class="text-sm font-medium text-emerald-300">View</Link>
                        </div>
                        <p class="mt-2 text-xs text-slate-500">Signed up {{ formatDate(user.created_at) }}</p>
                    </article>
                    <p v-if="!recentCustomers?.length" class="p-8 text-center text-sm text-slate-500">No customer signups yet.</p>
                </div>
            </section>

            <section class="rounded-2xl border border-white/10 bg-slate-900/60 shadow-sm">
                <div class="flex flex-col gap-3 border-b border-white/10 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <h2 class="text-base font-semibold text-white">Recent rides</h2>
                        <p class="text-sm text-slate-400">Latest requests and their status.</p>
                    </div>
                    <Link
                        :href="route('admin.rides.index')"
                        class="inline-flex items-center justify-center rounded-xl px-4 py-2 text-sm font-semibold text-slate-200 ring-1 ring-white/10 hover:bg-white/5"
                    >
                        View all rides
                    </Link>
                </div>

                <div class="hidden overflow-x-auto md:block">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-white/5 text-xs uppercase tracking-wide text-slate-400">
                            <tr>
                                <th class="px-5 py-3 font-medium">Reference</th>
                                <th class="px-5 py-3 font-medium">Passenger</th>
                                <th class="px-5 py-3 font-medium">Route</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                                <th class="px-5 py-3 font-medium text-right">Fare</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-for="ride in recentRides" :key="ride.id" class="hover:bg-white/[0.02]">
                                <td class="px-5 py-4 font-medium text-white">{{ ride.reference }}</td>
                                <td class="px-5 py-4">
                                    <p class="text-slate-200">{{ ride.passenger_name }}</p>
                                    <p class="text-xs text-slate-500">{{ ride.passenger_phone }}</p>
                                </td>
                                <td class="px-5 py-4 text-slate-300">
                                    <span class="block max-w-xs truncate">{{ ride.pickup_address }}</span>
                                    <span class="block max-w-xs truncate text-slate-500">→ {{ ride.dropoff_address }}</span>
                                </td>
                                <td class="px-5 py-4">
                                    <span
                                        :class="[
                                            statusStyles[ride.status] || 'bg-slate-500/15 text-slate-300 ring-slate-400/20',
                                            'inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1 ring-inset',
                                        ]"
                                    >
                                        {{ formatStatus(ride.status) }}
                                    </span>
                                </td>
                                <td class="px-5 py-4 text-right text-slate-200">
                                    {{ ride.fare != null ? `${Number(ride.fare).toFixed(2)} ETB` : '—' }}
                                </td>
                            </tr>
                            <tr v-if="!recentRides?.length">
                                <td colspan="5" class="px-5 py-10 text-center text-slate-500">No rides found yet.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="divide-y divide-white/5 md:hidden">
                    <article v-for="ride in recentRides" :key="ride.id" class="p-4">
                        <div class="flex items-start justify-between gap-3">
                            <div>
                                <p class="font-semibold text-white">{{ ride.reference }}</p>
                                <p class="text-sm text-slate-300">{{ ride.passenger_name }}</p>
                            </div>
                            <span
                                :class="[
                                    statusStyles[ride.status] || 'bg-slate-500/15 text-slate-300 ring-slate-400/20',
                                    'inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1 ring-inset',
                                ]"
                            >
                                {{ formatStatus(ride.status) }}
                            </span>
                        </div>
                        <p class="mt-3 text-sm text-slate-300">{{ ride.pickup_address }}</p>
                        <p class="text-sm text-slate-500">→ {{ ride.dropoff_address }}</p>
                    </article>
                    <p v-if="!recentRides?.length" class="p-8 text-center text-sm text-slate-500">No rides found yet.</p>
                </div>
            </section>
        </div>
    </AdminLayout>
</template>
