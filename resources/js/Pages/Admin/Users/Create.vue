<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const form = useForm({
    name: '',
    email: '',
    phone: '',
    password: '',
    password_confirmation: '',
    is_admin: false,
});

const submit = () => {
    form.post(route('admin.users.store'), {
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
                    <h1 class="text-2xl font-semibold tracking-tight text-white">Add a user</h1>
                    <p class="mt-1 text-sm text-slate-400">Create a passenger or administrator account. Phone or email is required.</p>
                </div>
                <Link :href="route('admin.users.index')" class="text-sm text-slate-300 hover:text-white">← Back to users</Link>
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
                        <div>
                            <label class="block text-sm font-medium text-slate-200">Phone number</label>
                            <input v-model="form.phone" type="text" class="mt-1 w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400" placeholder="+2519..." autocomplete="tel" />
                            <p v-if="form.errors.phone" class="mt-1 text-sm text-rose-400">{{ form.errors.phone }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-200">Email address</label>
                            <input v-model="form.email" type="email" class="mt-1 w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400" autocomplete="email" />
                            <p v-if="form.errors.email" class="mt-1 text-sm text-rose-400">{{ form.errors.email }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-white/10 bg-slate-900/60 p-5 sm:p-6">
                    <h2 class="text-base font-semibold text-white">Security & access</h2>
                    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-200">Password <span class="font-normal text-slate-500">(optional for OTP-only users)</span></label>
                            <input v-model="form.password" type="password" class="mt-1 w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400" autocomplete="new-password" />
                            <p v-if="form.errors.password" class="mt-1 text-sm text-rose-400">{{ form.errors.password }}</p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-200">Confirm password</label>
                            <input v-model="form.password_confirmation" type="password" class="mt-1 w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400" autocomplete="new-password" />
                        </div>
                        <div class="sm:col-span-2 flex items-center justify-between gap-4 rounded-xl border border-white/10 bg-white/5 p-4">
                            <div>
                                <p class="text-sm font-medium text-white">Administrator access</p>
                                <p class="text-xs text-slate-400">Admins can access this control panel. Grant carefully.</p>
                            </div>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input v-model="form.is_admin" type="checkbox" class="peer sr-only" />
                                <div class="h-6 w-11 rounded-full bg-slate-700 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:bg-emerald-500 peer-checked:after:translate-x-full"></div>
                            </label>
                        </div>
                        <p v-if="form.errors.is_admin" class="sm:col-span-2 text-sm text-rose-400">{{ form.errors.is_admin }}</p>
                    </div>
                </section>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <Link :href="route('admin.users.index')" class="inline-flex items-center justify-center rounded-xl px-5 py-2.5 text-sm font-medium text-slate-300 ring-1 ring-white/10 hover:bg-white/5">Cancel</Link>
                    <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-slate-950 hover:bg-emerald-400 disabled:opacity-60">
                        Create user
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
