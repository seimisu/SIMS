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
