<template>

    <Head title="Log in" />
    <GuestLayout>
        <div class="w-full max-w-md mx-auto">
            <div class="flex flex-col gap-4">
                <div class="flex flex-col items-center mb-10 w-full text-slate-800">
                    <div class="flex items-center  gap-2">
                        <Avatar size="large" image="/images/seilogo.png" />
                        <Avatar size="large" image="/images/dostlogo.svg" />
                    </div>

                    <div class="font-semibold text-2xl md:text-[30px] text-center antialiased dark:!text-gray-300 p-2">
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
                    <DefaultButton label="Login OTP" outlined class-name="w-full" severity="secondary"
                        :icon="IconPasswordUser" @click="openModal" />
                    <Divider align="center" :pt="{
                        content: {
                            class: 'dark:!bg-gray-800 dark:!text-gray-200',
                        },
                    }">
                        <span class="text-xs text-gray-400">Or continue with email</span>
                    </Divider>
                </div>
                <DefaultMessages v-if="loginForm.hasErrors" :message="loginForm.errors" message-type="error"
                    :icon="IconAlertCircle"></DefaultMessages>
                <form @submit.prevent="submitForm">
                    <div class="w-full flex-1 flex flex-col gap-4">
                        <TextInput label="Email" type="email" v-model="loginForm.email" />
                        <div class="flex flex-col">
                            <PasswordInput label="Password" v-model="loginForm.password" :feedback="false"
                                toggle-icon />
                            <div class="flex justify-between py-2">
                                <DefaultCheckbox label="Remember Me" v-model="loginForm.remember" binary>
                                </DefaultCheckbox>
                                <Button  variant="link" class="p-0! text-sm!" @click="openResetPasswordDialog">Forgot Password</Button>
                            </div>
                        </div>

                        <DefaultButton label="Login" class="mt-5" :loading="loginForm.processing"
                            :disabled="loginForm.processing" raised />
                    </div>
                </form>
                <!-- <span class="text-center mt-5 text-sm text-gray-400">@SEI - 2025</span> -->
            </div>
        </div>
        <OtpDialog v-model:visible="otpModal" :icon="IconPasswordUser" button-label="Send OTP" />
         <DialogResetPassword v-model:visible="resetPasswordDialog" v-if="resetPasswordDialog" />
    </GuestLayout>
</template>
<script setup>
import { IconPasswordUser, IconAlertCircle } from "@tabler/icons-vue";
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
const resetPasswordDialog = ref(false)

const openModal = () => {
    loginForm.reset();
    loginForm.clearErrors();
    otpModal.value = true;
};

const openResetPasswordDialog = () => {
    resetPasswordDialog.value = true
}

const submitForm = () => {
    loginForm.post(route("login.store"), {
        onSuccess: () => loginForm.reset(),
    });
};
</script>
