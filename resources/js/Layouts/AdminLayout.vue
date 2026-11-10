<script setup>
import { ref } from 'vue';
import { Link, usePage } from '@inertiajs/vue3';

const sidebarOpen = ref(false);
const page = usePage();

const navigation = [
    { name: 'Dashboard', href: 'admin.dashboard', icon: 'grid' },
    { name: 'Users', href: 'admin.users.index', icon: 'users' },
    { name: 'Rides', href: 'admin.rides.index', icon: 'car' },
    { name: 'SMS & OTP', href: 'admin.settings.edit', icon: 'settings' },
];

const isActive = (name) => {
    try {
        return route().current(name);
    } catch {
        return false;
    }
};
</script>

<template>
    <div class="min-h-screen bg-slate-950 text-slate-100">
        <div
            v-show="sidebarOpen"
            class="fixed inset-0 z-40 bg-slate-950/70 backdrop-blur-sm lg:hidden"
            @click="sidebarOpen = false"
        />

        <aside
            :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                'fixed inset-y-0 left-0 z-50 w-72 transform border-r border-white/10 bg-slate-900/95 transition-transform duration-200 ease-out lg:translate-x-0',
            ]"
        >
            <div class="flex h-16 items-center gap-3 border-b border-white/10 px-6">
                <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-500/15 ring-1 ring-emerald-400/30">
                    <svg class="h-5 w-5 text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.5V11a2 2 0 012-2h1.28a2 2 0 001.789-1.106l.342-.684A2 2 0 0110.2 6h3.6a2 2 0 011.789 1.106l.342.684A2 2 0 0117.72 9H19a2 2 0 012 2v2.5M5 13.5h14M6.5 16.5a1.5 1.5 0 100 3 1.5 1.5 0 000-3zm11 0a1.5 1.5 0 100 3 1.5 1.5 0 000-3zM5 16.5h.5m13 0H19" />
                    </svg>
                </div>
                <div>
                    <p class="text-sm font-semibold tracking-wide text-white">MkRide</p>
                    <p class="text-xs text-slate-400">Admin Panel</p>
                </div>
            </div>

            <nav class="space-y-1 p-4">
                <Link
                    v-for="item in navigation"
                    :key="item.name"
                    :href="route(item.href)"
                    :class="[
                        isActive(item.href)
                            ? 'bg-emerald-500/15 text-emerald-300 ring-1 ring-emerald-400/20'
                            : 'text-slate-300 hover:bg-white/5 hover:text-white',
                        'flex items-center gap-3 rounded-xl px-4 py-3 text-sm font-medium transition',
                    ]"
                    @click="sidebarOpen = false"
                >
                    <svg v-if="item.icon === 'grid'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z" />
                    </svg>
                    <svg v-else-if="item.icon === 'users'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17 20v-1a4 4 0 00-4-4H6a4 4 0 00-4 4v1" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.5 11a4 4 0 100-8 4 4 0 000 8zM23 20v-1a4 4 0 00-3-3.87M16 3.13a4 4 0 010 7.75" />
                    </svg>
                    <svg v-else-if="item.icon === 'car'" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8 17h.01M16 17h.01M3 11l1.5-4.5A2 2 0 016.4 5h11.2a2 2 0 011.9 1.5L21 11m-18 0h18m-18 0v5a1 1 0 001 1h1m16-6v5a1 1 0 01-1 1h-1m-11 0h8" />
                    </svg>
                    <svg v-else class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M10.325 4.317a1 1 0 011.35-.436l.34.17a1 1 0 00.894 0l.34-.17a1 1 0 011.35.436l.17.34a1 1 0 00.76.55l.378.054a1 1 0 01.848.985v.38a1 1 0 00.276.69l.252.27a1 1 0 010 1.32l-.252.27a1 1 0 00-.276.69v.38a1 1 0 01-.848.985l-.378.054a1 1 0 00-.76.55l-.17.34a1 1 0 01-1.35.436l-.34-.17a1 1 0 00-.894 0l-.34.17a1 1 0 01-1.35-.436l-.17-.34a1 1 0 00-.76-.55l-.378-.054a1 1 0 01-.848-.985v-.38a1 1 0 00-.276-.69l-.252-.27a1 1 0 010-1.32l.252-.27a1 1 0 00.276-.69v-.38a1 1 0 01.848-.985l.378-.054a1 1 0 00.76-.55l.17-.34z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                    </svg>
                    {{ item.name }}
                </Link>
            </nav>

            <div class="absolute inset-x-0 bottom-0 border-t border-white/10 p-4">
                <div class="rounded-xl bg-white/5 p-3">
                    <p class="truncate text-sm font-medium text-white">{{ page.props.auth?.user?.name }}</p>
                    <p class="truncate text-xs text-slate-400">{{ page.props.auth?.user?.email }}</p>
                </div>
            </div>
        </aside>

        <div class="lg:pl-72">
            <header class="sticky top-0 z-30 border-b border-white/10 bg-slate-950/80 backdrop-blur">
                <div class="flex h-16 items-center justify-between px-4 sm:px-6 lg:px-8">
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            class="inline-flex items-center justify-center rounded-lg p-2 text-slate-300 hover:bg-white/5 hover:text-white lg:hidden"
                            @click="sidebarOpen = true"
                        >
                            <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                            </svg>
                        </button>
                        <div v-if="$slots.header" class="text-sm font-semibold text-slate-200 sm:text-base">
                            <slot name="header" />
                        </div>
                    </div>

                    <div class="flex items-center gap-2 sm:gap-3">
                        <Link
                            :href="route('admin.profile.edit')"
                            class="hidden rounded-lg px-3 py-2 text-sm text-slate-300 hover:bg-white/5 hover:text-white sm:inline-flex"
                        >
                            Profile
                        </Link>
                        <Link
                            :href="route('logout')"
                            method="post"
                            as="button"
                            class="inline-flex items-center rounded-lg bg-white/5 px-3 py-2 text-sm font-medium text-slate-200 ring-1 ring-white/10 hover:bg-white/10"
                        >
                            Log out
                        </Link>
                    </div>
                </div>
            </header>

            <main class="p-4 sm:p-6 lg:p-8">
                <slot />
            </main>
        </div>
    </div>
</template>
