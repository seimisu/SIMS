<template>
    <Head title="Log in" />
    <GuestLayout>
        <div class="w-full max-w-md mx-auto">
            <div class="flex flex-col gap-4">
                <div
                    class="flex flex-col items-center mb-10 w-full text-slate-800"
                >
                    <div class="flex items-center gap-2">
                        <Avatar size="large" image="/images/seilogo.png" />
                        <Avatar size="large" image="/images/dostlogo.svg" />
                    </div>

                    <div
                        class="font-semibold text-2xl md:text-[30px] text-center antialiased dark:!text-gray-300 p-2"
                    >
                        Welcome to
                        <span class="text-blue-600">SIMS!</span>
                        🎓
                    </div>
                    <div class="w-full text-center text-[14px] text-gray-400">
                        Manage applications, track progress, and streamline
                        scholarship data — all in one place.
                    </div>
                </div>
                <div class="">
                    <DefaultButton
                        label="Login OTP"
                        outlined
                        class-name="w-full"
                        severity="secondary"
                        :icon="IconPasswordUser"
                        @click="openModal"
                    />
                    <Divider
                        align="center"
                        :pt="{
                            content: {
                                class: 'dark:!bg-gray-800 dark:!text-gray-200',
                            },
                        }"
                    >
                        <span class="text-xs text-gray-400"
                            >Or continue with email</span
                        >
                    </Divider>
                </div>
                <DefaultMessages
                    v-if="loginForm.hasErrors"
                    :message="loginForm.errors"
                    message-type="error"
                    :icon="IconAlertCircle"
                ></DefaultMessages>
                <form @submit.prevent="submitForm">
                    <div class="w-full flex-1 flex flex-col gap-4">
                        <TextInput
                            label="Email"
                            type="email"
                            v-model="loginForm.email"
                        />
                        <div class="flex flex-col">
                            <PasswordInput
                                label="Password"
                                v-model="loginForm.password"
                                :feedback="false"
                                toggle-icon
                            />
                            <div class="flex justify-between py-2">
                                <DefaultCheckbox
                                    label="Remember Me"
                                    v-model="loginForm.remember"
                                    binary
                                >
                                </DefaultCheckbox>
                                <Button
                                    variant="link"
                                    class="p-0! text-sm!"
                                    @click="openResetPasswordDialog"
                                    >Forgot Password</Button
                                >
                            </div>
                        </div>

                        <DefaultButton
                            label="Login"
                            class="mt-5"
                            :loading="loginForm.processing"
                            :disabled="loginForm.processing"
                            raised
                        />
                    </div>
                </form>
                <!-- <span class="text-center mt-5 text-sm text-gray-400">@SEI - 2025</span> -->
            </div>
        </div>
        <OtpDialog
            v-model:visible="otpModal"
            :icon="IconPasswordUser"
            button-label="Send OTP"
        />
        <Dialog
            v-model:visible="twoFactorModal"
            modal
            :closable="false"
            :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
            :style="{ width: '34rem' }"
            :pt="{
                root: 'overflow-hidden dark:!bg-gray-800 dark:!border-gray-700',
                header: 'border-b-0 pb-0 dark:!bg-gray-800',
                content: 'pt-4 dark:!bg-gray-800',
            }"
        >
            <template #header>
                <div class="flex items-center gap-3 px-1">
                    <div
                        class="flex h-10 w-10 items-center justify-center rounded-xl bg-primary/10 text-primary"
                    >
                        <IconShieldLockFilled :size="24" :stroke-width="2" />
                    </div>
                    <div class="min-w-0">
                        <h3
                            class="text-base font-semibold text-gray-900 dark:text-gray-100"
                        >
                            Two-Factor Authentication
                        </h3>
                        <p
                            class="mt-0.5 text-xs text-gray-500 dark:text-gray-400"
                        >
                            A second layer of protection for your account
                        </p>
                    </div>
                </div>
            </template>
            <div class="flex flex-col gap-5 px-1 pb-1">
                <div
                    v-if="!useRecoveryCode"
                    class="flex items-start gap-3 rounded-xl border border-blue-100 bg-blue-50/70 px-4 py-3 dark:border-blue-400/20 dark:bg-blue-400/10"
                >
                    <IconShieldLockFilled
                        :size="18"
                        :stroke-width="2"
                        class="mt-0.5 shrink-0 text-blue-600 dark:text-blue-300"
                    />
                    <p
                        class="text-xs leading-5 text-blue-900 dark:text-blue-100"
                    >
                        Open your authenticator app and enter the 6-digit code
                        to securely continue.
                    </p>
                </div>

                <div
                    v-if="!useRecoveryCode"
                    class="flex flex-col items-center gap-3"
                >
                    <span
                        class="text-[11px] font-semibold uppercase tracking-[0.12em] text-gray-500 dark:text-gray-400"
                    >
                        Verification code
                    </span>
                    <InputOtp
                        v-model="twoFactorForm.code"
                        :length="6"
                        integer-only
                        aria-label="Six-digit verification code"
                        :pt="{
                            pcInputText: {
                                root: 'h-11 w-10 text-center text-lg font-semibold sm:h-12 sm:w-11 dark:!bg-gray-700 dark:!border-gray-600 dark:!text-white',
                            },
                        }"
                    />
                </div>

                <div v-else class="flex flex-col gap-3">
                    <div>
                        <h4
                            class="text-sm font-semibold text-gray-900 dark:text-gray-100"
                        >
                            Use a recovery code
                        </h4>
                        <p
                            class="mt-1 text-xs leading-5 text-gray-500 dark:text-gray-400"
                        >
                            Enter one of the recovery codes saved when
                            two-factor authentication was enabled.
                        </p>
                    </div>
                    <TextInput
                        v-model="twoFactorForm.recovery_code"
                        label="Recovery code"
                        placeholder="e.g. 1234-5678"
                        autocomplete="one-time-code"
                        autofocus
                    />
                </div>

                <div v-if="twoFactorForm.hasErrors" class="-mt-2">
                    <DefaultMessages
                        :message="twoFactorForm.errors"
                        message-type="error"
                    />
                </div>

                <div
                    class="flex w-full flex-col-reverse items-stretch gap-3 border-t border-gray-100 pt-4 sm:flex-row sm:items-center sm:justify-between dark:border-gray-700"
                >
                    <Button
                        type="button"
                        size="small"
                        class="justify-center text-xs! sm:justify-start"
                        link
                        @click="toggleRecoveryCode"
                    >
                        {{
                            useRecoveryCode
                                ? "Use authenticator code instead"
                                : "Can't access your authenticator?"
                        }}
                    </Button>
                    <DefaultButton
                        size="small"
                        class="w-full sm:w-auto"
                        raised
                        label="Verify & Continue"
                        :loading="twoFactorForm.processing"
                        :disabled="twoFactorForm.processing"
                        @click="submitTwoFactor"
                    />
                </div>
            </div>
        </Dialog>
        <DialogResetPassword
            v-model:visible="resetPasswordDialog"
            v-if="resetPasswordDialog"
        />
    </GuestLayout>
</template>
<script setup>
import {
    IconPasswordUser,
    IconAlertCircle,
    IconShieldLockFilled,
} from "@tabler/icons-vue";
import { Head, useForm } from "@inertiajs/vue3";
import GuestLayout from "../../Layouts/GuestLayout.vue";
import TextInput from "../../Components/inputs/TextInput.vue";
import PasswordInput from "../../Components/inputs/PasswordInput.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import DefaultCheckbox from "../../Components/checkboxs/DefaultCheckbox.vue";
import OtpDialog from "../../Components/dialogs/OtpDialog.vue";
import { ref } from "vue";
import DefaultMessages from "../../Components/messages/DefaultMessages.vue";
import DialogResetPassword from "../../Modules/Others/DialogResetPassword.vue";

const loginForm = useForm({
    email: "",
    password: "",
    remember: false,
    otpRequest: false,
});
const otpModal = ref(false);
const twoFactorModal = ref(false);
const resetPasswordDialog = ref(false);
const useRecoveryCode = ref(false);
const twoFactorForm = useForm({
    code: "",
    recovery_code: "",
});

const openModal = () => {
    loginForm.reset();
    loginForm.clearErrors();
    otpModal.value = true;
};

const openResetPasswordDialog = () => {
    resetPasswordDialog.value = true;
};

const toggleRecoveryCode = () => {
    useRecoveryCode.value = !useRecoveryCode.value;
    twoFactorForm.reset("code", "recovery_code");
    twoFactorForm.clearErrors();
};

const submitForm = () => {
    loginForm.post(route("login.store"), {
        onSuccess: (page) => {
            if (page.props.twoFactorRequired) {
                twoFactorModal.value = true;
                loginForm.clearErrors();
                return;
            }

            loginForm.reset();
        },
    });
};

const submitTwoFactor = () => {
    twoFactorForm.post(route("two-factor.login.store"), {
        onSuccess: () => {
            twoFactorForm.reset();
            useRecoveryCode.value = false;
            twoFactorModal.value = false;
        },
    });
};
</script>
