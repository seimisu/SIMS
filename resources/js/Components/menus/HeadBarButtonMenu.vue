<template>
    <Button
        type="button"
        @click="toggle"
        size="small"
        variant="text"
        class="text-white py-1 px-2 rounded-[20px] cursor-pointer"
        unstyled
    >
        <div class="flex items-center gap-2">
            <Avatar
                v-if="page.props.user.profile.avatar === null"
                :label="page.props.user.email.charAt(0).toUpperCase()"
                style="background-color: #dee9fc; color: #1a2551"
                shape="circle"
            />

            <Avatar
                v-else
                style="background-color: #dee9fc; color: #1a2551"
                shape="circle"
                :image="page.props.user.profile.avatar_url"
            />

            <div class="flex-1 text-left leading-none">
                <div class="text-[12px] font-semibold leading-none">
                    {{
                        page.props.user.profile.fullname ??
                        page.props.user.email
                    }}
                </div>
                <span class="text-[10px] leading-none capitalize">
                    {{ page.props.user.role_array.name }} (<span
                        class="uppercase"
                        >{{ page.props.user.profile.agency.slug }}</span
                    >)</span
                >
            </div>
        </div>
    </Button>
    <div class="flex-col justify-center text-white">
        <Menu ref="menu" :model="items" class="!mt-2" :popup="true">
            <template #submenulabel="{ item }">
                <span class="text-sm">{{ item.label }}</span>
            </template>
            <template #item="{ item, props }">
                <a
                    v-ripple
                    class="flex items-center p-2 cursor-pointer !text-xs gap-2"
                    type="button"
                    @click="item.action"
                >
                    <component
                        :is="item.icons"
                        size="25px"
                        :class="
                            item.label === 'Logout'
                                ? 'text-red-500'
                                : 'text-gray-600'
                        "
                        :stroke-width="1.5"
                    />
                    <span
                        class="text-gray-500"
                        :class="
                            item.label === 'Logout'
                                ? 'text-red-500'
                                : 'text-gray-600'
                        "
                        >{{ item.label }}</span
                    >
                </a>
            </template>
        </Menu>
    </div>
    <DefaultDialog
        v-model:visible="passDialog"
        :icon="IconPasswordUser"
        width-set="lg:!w-[35%]"
        :loading="passwordForm.processing"
        @submit-form="submitForm"
        title="Update Your Password"
        description="To ensure your account remains protected at all times, please choose a password that is strong, unique, and difficult for others to guess."
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
import { nextTick, ref } from "vue";
import { router, usePage, useForm, useRemember } from "@inertiajs/vue3";
import {
    IconLogout2,
    IconPasswordUser,
} from "@tabler/icons-vue";
import DefaultDialog from "../dialogs/DefaultDialog.vue";
import PasswordInput from "../inputs/PasswordInput.vue";
import DefaultMessages from "../messages/DefaultMessages.vue";
import DefaultToast from "../messages/DefaultToast.vue";

const passDialog = ref(false);
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

const menu = ref();
const items = ref([
    {
        label: "Options",
        items: [
            {
                label: "Change Password",
                icons: IconPasswordUser,
                action: () => {
                    openDialog();
                },
            },
            {
                separator: true,
            },
            {
                label: "Logout",
                icons: IconLogout2,
                action: () => {
                    logout();
                },
            },
        ],
    },
]);

const toggle = (event) => {
    menu.value.toggle(event);
};

const openDialog = () => {
    passDialog.value = true;
    passwordForm.resetAndClearErrors();
};

const submitForm = () => {
    passwordForm.post(route("user.changePassword"), {
        fresh: true,
        replace: true,
        onSuccess: () => {
            toastRef.value.show(page.props.flash);
            passDialog.value = false;
            passwordForm.resetAndClearErrors();
            nextTick(() => {
                window.location.reload();
            });
        },
    });
};

const logout = () => {
    router.post(route("logout"));
};
</script>
<style>
.p-menu {
    min-width: 150px !important;
}
</style>
