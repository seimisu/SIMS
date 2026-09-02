<template>
    <DefaultDialog
        v-model:visible="visible"
        :icon="IconPasswordUser"
        width-set="lg:!w-[35%]"
        :loading="passwordForm.processing"
        title="Update Your Password"
        description="To ensure your account remains protected at all times, please choose a password that is strong, unique, and difficult for others to guess."
        @submit-form="submitForm"
    >
        <template #message>
            <DefaultMessages
                v-if="passwordForm.hasErrors"
                message-type="error"
                :message="passwordForm.errors"
            />
        </template>
        <template #forms>
            <div class="pt-5 flex flex-col gap-5">
                <PasswordInput
                    label="Current Password"
                    v-model="passwordForm.current"
                    :feedback="false"
                    toggle-icon
                />
                <PasswordInput
                    label="New Password"
                    v-model="passwordForm.new"
                    :feedback="true"
                    toggle-icon
                />
                <PasswordInput
                    label="Confirm Password"
                    v-model="passwordForm.confirm"
                    :feedback="false"
                />
            </div>
        </template>
    </DefaultDialog>
    <DefaultToast ref="toastRef" />
</template>

<script setup>
import { nextTick, watch, ref } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { IconPasswordUser } from "@tabler/icons-vue";
import DefaultDialog from "./DefaultDialog.vue";
import PasswordInput from "../inputs/PasswordInput.vue";
import DefaultMessages from "../messages/DefaultMessages.vue";
import DefaultToast from "../messages/DefaultToast.vue";

const visible = defineModel("visible", { type: Boolean, default: false });
const page = usePage();
const toastRef = ref(null);
const passwordForm = useForm(
    {
        current: null,
        new: null,
        confirm: null,
    },
    { useRemember: false },
);

watch(
    () => visible.value,
    (isVisible) => {
        if (isVisible) {
            passwordForm.resetAndClearErrors();
        }
    },
);

const submitForm = () => {
    passwordForm.post(route("user.changePassword"), {
        fresh: true,
        replace: true,
        onSuccess: () => {
            toastRef.value.show(page.props.flash);
            visible.value = false;
            passwordForm.resetAndClearErrors();
            nextTick(() => {
                window.location.reload();
            });
        },
    });
};
</script>
