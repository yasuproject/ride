<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    rides: Object,
    filters: Object,
});

const statusStyles = {
    requested: 'bg-amber-500/15 text-amber-300 ring-amber-400/20',
    assigned: 'bg-sky-500/15 text-sky-300 ring-sky-400/20',
    in_progress: 'bg-violet-500/15 text-violet-300 ring-violet-400/20',
    completed: 'bg-emerald-500/15 text-emerald-300 ring-emerald-400/20',
    cancelled: 'bg-rose-500/15 text-rose-300 ring-rose-400/20',
};

const formatStatus = (s) => (s ? s.replace('_', ' ') : '—');
const rows = (rides) => rides?.data ?? [];
</script>

<template>
    <Head title="Rides" />

    <AdminLayout>
        <template #header>Rides</template>

        <div class="space-y-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-white">Ride management</h1>
                    <p class="mt-1 text-sm text-slate-400">Review requests, assignments, and trip outcomes.</p>
                </div>
                <div class="rounded-xl border border-white/10 bg-slate-900/60 px-4 py-3 text-sm text-slate-300">
                    Total: <span class="font-semibold text-white">{{ rides?.total ?? 0 }}</span>
                </div>
            </div>

            <section class="rounded-2xl border border-white/10 bg-slate-900/60 shadow-sm">
                <div class="hidden overflow-x-auto lg:block">
                    <table class="min-w-full text-left text-sm">
                        <thead class="bg-white/5 text-xs uppercase tracking-wide text-slate-400">
                            <tr>
                                <th class="px-5 py-3 font-medium">Reference</th>
                                <th class="px-5 py-3 font-medium">Passenger</th>
                                <th class="px-5 py-3 font-medium">Pickup</th>
                                <th class="px-5 py-3 font-medium">Dropoff</th>
                                <th class="px-5 py-3 font-medium">Driver</th>
                                <th class="px-5 py-3 font-medium">Status</th>
                                <th class="px-5 py-3 font-medium text-right">Fare</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-white/5">
                            <tr v-for="ride in rows(rides)" :key="ride.id" class="hover:bg-white/[0.02]">
                                <td class="whitespace-nowrap px-5 py-4 font-medium text-white">{{ ride.reference }}</td>
                                <td class="px-5 py-4">
                                    <p class="text-slate-200">{{ ride.passenger_name }}</p>
                                    <p class="text-xs text-slate-500">{{ ride.passenger_phone }}</p>
                                </td>
                                <td class="max-w-[14rem] truncate px-5 py-4 text-slate-300" :title="ride.pickup_address">{{ ride.pickup_address }}</td>
                                <td class="max-w-[14rem] truncate px-5 py-4 text-slate-300" :title="ride.dropoff_address">{{ ride.dropoff_address }}</td>
                                <td class="px-5 py-4 text-slate-300">{{ ride.driver_name || '—' }}</td>
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
                                <td class="whitespace-nowrap px-5 py-4 text-right text-slate-200">
                                    {{ ride.fare != null ? `${Number(ride.fare).toFixed(2)} ETB` : '—' }}
                                </td>
                            </tr>
                            <tr v-if="!rows(rides).length">
                                <td colspan="7" class="px-5 py-12 text-center text-slate-500">No rides found.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="divide-y divide-white/5 lg:hidden">
                    <article v-for="ride in rows(rides)" :key="ride.id" class="p-4 sm:p-5">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-white">{{ ride.reference }}</p>
                                <p class="mt-0.5 text-sm text-slate-300">{{ ride.passenger_name }} · {{ ride.passenger_phone }}</p>
                            </div>
                            <span
                                :class="[
                                    statusStyles[ride.status] || 'bg-slate-500/15 text-slate-300 ring-slate-400/20',
                                    'shrink-0 inline-flex rounded-full px-2.5 py-1 text-xs font-medium capitalize ring-1 ring-inset',
                                ]"
                            >
                                {{ formatStatus(ride.status) }}
                            </span>
                        </div>
                        <dl class="mt-4 grid grid-cols-1 gap-2 text-sm sm:grid-cols-2">
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">Pickup</dt>
                                <dd class="text-slate-200">{{ ride.pickup_address }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">Dropoff</dt>
                                <dd class="text-slate-200">{{ ride.dropoff_address }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">Driver</dt>
                                <dd class="text-slate-200">{{ ride.driver_name || '—' }}</dd>
                            </div>
                            <div>
                                <dt class="text-xs uppercase tracking-wide text-slate-500">Fare</dt>
                                <dd class="text-slate-200">{{ ride.fare != null ? `${Number(ride.fare).toFixed(2)} ETB` : '—' }}</dd>
                            </div>
                        </dl>
                    </article>
                    <p v-if="!rows(rides).length" class="p-8 text-center text-sm text-slate-500">No rides found.</p>
                </div>

                <nav
                    v-if="rides?.links?.length > 3"
                    class="flex flex-wrap items-center justify-center gap-1 border-t border-white/10 px-4 py-4"
                    aria-label="Pagination"
                >
                    <template v-for="(link, i) in rides.links" :key="i">
                        <span
                            v-if="!link.url"
                            class="rounded-lg px-3 py-2 text-sm text-slate-600"
                            v-html="link.label"
                        />
                        <Link
                            v-else
                            :href="link.url"
                            :class="[
                                link.active
                                    ? 'bg-emerald-500 text-slate-950'
                                    : 'text-slate-300 hover:bg-white/5 hover:text-white',
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
