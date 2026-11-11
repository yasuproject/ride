<script setup>
import AdminLayout from '@/Layouts/AdminLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({
    settings: Object,
    status: String,
});

const form = useForm({
    app_name: props.settings?.app_name ?? 'MkRide',
    otp_login_enabled: !!props.settings?.otp_login_enabled,
    otp_length: props.settings?.otp_length ?? 6,
    otp_expiry_seconds: props.settings?.otp_expiry_seconds ?? 300,
    resend_cooldown_seconds: props.settings?.resend_cooldown_seconds ?? 60,
    max_verify_attempts: props.settings?.max_verify_attempts ?? 5,
    sms_provider: props.settings?.sms_provider ?? 'smsethiopia',
    sms_sender_id: props.settings?.sms_sender_id ?? '',
    sms_message_template: props.settings?.sms_message_template ?? 'Your {app_name} verification code is {code}. It expires in {minutes} minutes.',
    sms_api_key: '',
});

const submit = () => {
    form.transform((data) => ({
        ...data,
        otp_login_enabled: !!data.otp_login_enabled,
    })).put(route('admin.settings.update'), {
        preserveScroll: true,
        onSuccess: () => form.reset('sms_api_key'),
    });
};
</script>

<template>
    <Head title="SMS & OTP Settings" />

    <AdminLayout>
        <template #header>SMS & OTP Settings</template>

        <div class="mx-auto max-w-4xl space-y-6">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight text-white">SMS & OTP configuration</h1>
                <p class="mt-1 text-sm text-slate-400">
                    Manage OTP login behavior and SMSEthiopia provider settings. Sensitive credentials are never exposed to public clients.
                </p>
            </div>

            <div v-if="status" class="rounded-xl border border-emerald-400/20 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-200">
                {{ status }}
            </div>

            <form @submit.prevent="submit" class="space-y-6">
                <section class="rounded-2xl border border-white/10 bg-slate-900/60 p-5 sm:p-6">
                    <h2 class="text-base font-semibold text-white">Public app configuration</h2>
                    <p class="mt-1 text-sm text-slate-400">These values are returned by the public app-config API.</p>

                    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-200">App name</label>
                            <input v-model="form.app_name" type="text" class="mt-1 w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400" />
                            <p v-if="form.errors.app_name" class="mt-1 text-sm text-rose-400">{{ form.errors.app_name }}</p>
                        </div>

                        <div class="sm:col-span-2 flex items-center justify-between gap-4 rounded-xl border border-white/10 bg-white/5 p-4">
                            <div>
                                <p class="text-sm font-medium text-white">OTP login enabled</p>
                                <p class="text-xs text-slate-400">When disabled, mobile clients will show OTP login as unavailable.</p>
                            </div>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input v-model="form.otp_login_enabled" type="checkbox" class="peer sr-only" />
                                <div class="h-6 w-11 rounded-full bg-slate-700 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:bg-white after:transition peer-checked:bg-emerald-500 peer-checked:after:translate-x-full"></div>
                            </label>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-200">OTP length (digits)</label>
                            <input v-model.number="form.otp_length" type="number" min="4" max="8" class="mt-1 w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400" />
                            <p v-if="form.errors.otp_length" class="mt-1 text-sm text-rose-400">{{ form.errors.otp_length }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-200">OTP expiry (seconds)</label>
                            <input v-model.number="form.otp_expiry_seconds" type="number" min="30" max="3600" class="mt-1 w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400" />
                            <p v-if="form.errors.otp_expiry_seconds" class="mt-1 text-sm text-rose-400">{{ form.errors.otp_expiry_seconds }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-200">Resend cooldown (seconds)</label>
                            <input v-model.number="form.resend_cooldown_seconds" type="number" min="10" max="600" class="mt-1 w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400" />
                            <p v-if="form.errors.resend_cooldown_seconds" class="mt-1 text-sm text-rose-400">{{ form.errors.resend_cooldown_seconds }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-slate-200">Max verify attempts</label>
                            <input v-model.number="form.max_verify_attempts" type="number" min="1" max="10" class="mt-1 w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400" />
                            <p v-if="form.errors.max_verify_attempts" class="mt-1 text-sm text-rose-400">{{ form.errors.max_verify_attempts }}</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-2xl border border-white/10 bg-slate-900/60 p-5 sm:p-6">
                    <h2 class="text-base font-semibold text-white">SMSEthiopia provider</h2>
                    <p class="mt-1 text-sm text-slate-400">
                        Provider credentials are stored encrypted and never returned by public APIs.
                    </p>

                    <div class="mt-5 grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label class="block text-sm font-medium text-slate-200">Provider</label>
                            <input v-model="form.sms_provider" type="text" class="mt-1 w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400" />
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-slate-200">Sender ID (optional)</label>
                            <input v-model="form.sms_sender_id" type="text" class="mt-1 w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400" />
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-200">SMSEthiopia API key</label>
                            <input v-model="form.sms_api_key" type="password" autocomplete="new-password" class="mt-1 w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400" placeholder="Enter new key to update" />
                            <p class="mt-1 text-xs text-slate-400">
                                <span v-if="settings?.sms_api_key_set" class="text-emerald-300">An API key is currently configured.</span>
                                <span v-else class="text-amber-300">No API key configured yet.</span>
                                Leave blank to keep the existing key.
                            </p>
                            <p v-if="form.errors.sms_api_key" class="mt-1 text-sm text-rose-400">{{ form.errors.sms_api_key }}</p>
                        </div>
                        <div class="sm:col-span-2">
                            <label class="block text-sm font-medium text-slate-200">SMS message template</label>
                            <textarea v-model="form.sms_message_template" rows="4" class="mt-1 w-full rounded-xl border-white/10 bg-slate-950/60 text-slate-100 focus:border-emerald-400 focus:ring-emerald-400"></textarea>
                            <p class="mt-1 text-xs text-slate-400">Supported placeholders: <code class="text-slate-300">{code}</code>, <code class="text-slate-300">{app_name}</code>, <code class="text-slate-300">{minutes}</code>.</p>
                        </div>
                    </div>
                </section>

                <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                    <button type="submit" :disabled="form.processing" class="inline-flex items-center justify-center rounded-xl bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-slate-950 hover:bg-emerald-400 disabled:opacity-60">
                        Save settings
                    </button>
                </div>
            </form>
        </div>
    </AdminLayout>
</template>
