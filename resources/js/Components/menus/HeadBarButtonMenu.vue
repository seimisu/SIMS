<template>
    <Button
        type="button"
        @click="toggle"
        size="small"
        variant="text"
        class="cursor-pointer rounded-md px-2 py-1 text-white transition-colors hover:bg-white/10"
        unstyled
    >
        <div class="flex items-center gap-2">
            <Avatar
                v-if="page.props.user.profile.avatar === null"
                :label="page.props.user.email.charAt(0).toUpperCase()"
                class="!h-9 !w-9 !bg-white/90 !text-blue-700"
                shape="circle"
            />

            <Avatar
                v-else
                class="!h-9 !w-9 !bg-white/90 !text-blue-700"
                shape="circle"
                :image="page.props.user.profile.avatar_url"
            />

            <div class="hidden flex-1 text-left leading-none sm:block">
                <div class="text-xs font-semibold leading-none text-white">
                    {{
                        page.props.user.profile.fullname ??
                        page.props.user.email
                    }}
                </div>
                <span class="mt-1 block text-[10px] leading-none capitalize text-blue-100">
                    {{ page.props.user.role_array.name }} (<span
                        class="uppercase"
                        >{{ page.props.user.profile.agency.slug }}</span
                    >)</span
                >
            </div>
        </div>
    </Button>
    <div class="flex-col justify-center">
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
                            (item.label === 'Logout'
                                ? 'text-red-500 dark:text-red-500'
                                : 'text-gray-600',
                            'dark:text-white!')
                        "
                        :stroke-width="1.5"
                    />
                    <span
                        :class="
                            item.label === 'Logout'
                                ? 'text-red-500 '
                                : 'text-gray-600 dark:text-white'
                        "
                        >{{ item.label }}</span
                    >
                </a>
            </template>
        </Menu>
    </div>
    <ChangePasswordDialog v-model:visible="passDialog" />
</template>

<script setup>
import { ref } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import {
    IconLogout2,
    IconPasswordUser,
    IconUserCircle,
} from "@tabler/icons-vue";
import ChangePasswordDialog from "../dialogs/ChangePasswordDialog.vue";

const passDialog = ref(false);
const page = usePage();

const menu = ref();
const items = ref([
    {
        label: "Options",
        items: [
            {
                label: "User Profile",
                icons: IconUserCircle,
                action: () => {
                    router.get("/profile");
                },
            },
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
};

const logout = () => {
    router.post(route("logout"));
};
</script>
<style>
.p-menu {
    min-width: 150px !important;
}
.dark .p-popover:before,
.dark .p-popover:after {
    border-top-color: transparent !important;
}
</style>
