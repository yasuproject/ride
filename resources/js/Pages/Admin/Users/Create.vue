<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    type: {
        type: String,
        default: 'registered',
    },
});

const isAdminCreate = props.type === 'admin';
const routeBase = isAdminCreate ? 'admin.admin-users' : 'admin.customers';
const indexRoute = isAdminCreate ? 'admin.admin-users.index' : 'admin.customers.index';

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
});

const submit = () => {
    form.post(route(`${routeBase}.store`), {
        onFinish: () => form.reset('password', 'password_confirmation'),
    });
};
</script>

<template>
    <Head title="Create User" />

    <AdminLayout>
        <template #header>Create user</template>

        <div class="mx-auto max-w-3xl space-y-6">
            <div class="flex items-center justify-between gap-4">
                <div>
                    <h1 class="text-2xl font-semibold tracking-tight text-white">{{ isAdminCreate ? 'Add admin user' : 'Add customer' }}</h1>
                    <p class="mt-1 text-sm text-slate-400">
                        {{ isAdminCreate
                            ? 'Create an administrator account for admin panel access. Phone or email is required.'
                            : 'Create a mobile app customer account. Phone is required.' }}
                    </p>
                </div>
                <Link :href="route(indexRoute)" class="text-sm text-slate-300 hover:text-white">← Back</Link>
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <section class="rounded-2xl border border-white/10 bg-slate-900/60 p-5 sm:p-6">
                    <h2 class="text-base font-semibold text-white">Account details</h2>
                    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-200">Full name</label>
                            <input v-model="form.name" type="text" class="mt-1 w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400" autocomplete="name" />
                            <p v-if="form.errors.name" class="mt-1 text-sm text-rose-400">{{ form.errors.name }}</p>
                        </div>
                        <div v-if="!isAdminCreate">
                            <label class="block text-sm font-medium text-slate-200">Phone number</label>
                            <input v-model="form.phone" type="text" class="mt-1 w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400" placeholder="+2519..." autocomplete="tel" />
                            <p v-if="form.errors.phone" class="mt-1 text-sm text-rose-400">{{ form.errors.phone }}</p>
                        </div>
                        <div :class="isAdminCreate ? 'sm:col-span-2' : ''">
                            <label class="block text-sm font-medium text-slate-200">Email address <span v-if="!isAdminCreate" class="font-normal text-slate-500">(optional)</span></label>
                            <input v-model="form.email" type="email" class="mt-1 w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400" autocomplete="email" />
                            <p v-if="form.errors.email" class="mt-1 text-sm text-rose-400">{{ form.errors.email }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-white/10 bg-slate-900/60 p-5 sm:p-6">
                    <h2 class="text-base font-semibold text-white">Security & access</h2>
                    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-200">Password <span class="font-normal text-slate-500">{{ isAdminCreate ? '(required)' : '(optional for OTP-only users)' }}</span></label>
                            <input v-model="form.password" type="password" class="mt-1 w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400" autocomplete="new-password" />
                            <p v-if="form.errors.password" class="mt-1 text-sm text-rose-400">{{ form.errors.password }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-200">Confirm password</label>
                            <input v-model="form.password_confirmation" type="password" class="mt-1 w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400" autocomplete="new-password" />
                        </div>
                        <div class="sm:col-span-2 rounded-xl border border-white/10 bg-white/5 p-4">
                            <p class="text-sm font-medium text-white">{{ isAdminCreate ? 'Administrator account' : 'Customer account' }}</p>
                            <p class="text-xs text-slate-400">
                                {{ isAdminCreate
                                    ? 'This account will be created in the admin panel users table (separate from mobile app customers).'
                                    : 'This account will be created in the app customers table (separate from admin panel users).' }}
                            </p>
                        </div>
                    </div>
                </section>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Link :href="route(indexRoute)" class="inline-flex items-center justify-center rounded-xl px-5 py-2.5 text-sm font-medium text-slate-300 ring-1 ring-white/10 hover:bg-white/5">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-slate-950 hover:bg-emerald-400 disabled:opacity-60">
                        {{ isAdminCreate ? 'Create admin user' : 'Create customer' }}
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
