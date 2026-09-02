<template>
    <div class="flex h-screen overflow-hidden dark:bg-gray-800 dark:text-white">
        <Transition name="slide-fade">
            <aside
                v-if="!isMobile"
                :class="[
                    'z-30 fixed md:block md:relative md:flex-shrink-0 flex flex-col transition-all duration-300 bg-slate-100 dark:bg-gray-700 m-3 h-min-screen rounded-[15px] shadow-sm py-4 ',
                    sidebar ? 'w-60 ' : 'w-20 ',
                ]"
            >
                <div
                    :class="[
                        'h-full w-full flex flex-col justify-between px-[1.2rem] ',
                    ]"
                >
                    <div class="flex gap-2 items-center top-0 sticky">
                        <div class="flex items-center gap-2">
                            <!-- <Image src="/images/seilogo.png" alt="Logo" v-show="sidebar" width="39" height="39" /> -->
                            <Image
                                src="/images/dostlogo.svg"
                                alt="Logo"
                                width="39"
                                height="39"
                            />
                        </div>

                        <div
                            v-show="sidebar"
                            class="font-semibold flex-1 text-blue-600 dark:!text-blue-400 leading-none"
                        >
                            SIMS
                            <span
                                class="block text-xs text-gray-500 uppercase"
                                >{{ page.props.user.profile.agency.slug }}</span
                            >
                        </div>
                    </div>

                    <nav
                        :class="[
                            'flex flex-col h-[80%] ',
                            !sidebar ? 'items-center' : '',
                        ]"
                    >
                        <SidebarIconMenu
                            :list="page.props.menu"
                            v-if="!sidebar"
                        />
                        <SidebarLabelMenu
                            :list="page.props.menu"
                            class="overflow-x-hidden"
                            v-else
                        />
                    </nav>
                    <div
                        :class="[
                            sidebar ? 'flex justify-center' : 'flex w-full',
                        ]"
                    ></div>
                </div>
            </aside>
            <Drawer v-model:visible="drawerMobile" v-else header="Drawer">
                <template #header>
                    <div class="flex gap-2 items-center top-0 sticky">
                        <div class="flex items-center gap-2">
                            <!-- <Image src="/images/seilogo.png" alt="Logo" v-show="sidebar" width="39" height="39" /> -->
                            <Image
                                src="/images/dostlogo.svg"
                                alt="Logo"
                                width="39"
                                height="39"
                            />
                        </div>

                        <div
                            class="font-semibold flex-1 text-blue-600 dark:!text-blue-400 leading-none"
                        >
                            SIMS
                            <span
                                class="block text-xs text-gray-500 uppercase"
                                >{{ page.props.user.profile.agency.slug }}</span
                            >
                        </div>
                    </div>
                </template>
                <Divider class="!m-0" align="right">
                    <p class="text-xs font-semibold">Menu</p>
                </Divider>
                <SidebarLabelMenu :list="page.props.menu" />
            </Drawer>
        </Transition>

        <div class="flex-1 flex flex-col w-full md:py-3 md:pr-3">
            <header
                class="bg-blue-600 h-16 p-3 md:rounded-[15px] text-white w-full"
            >
                <div class="flex justify-between items-center w-full h-full">
                    <div class="flex-1">
                        <DefaultButton
                            size="small"
                            v-if="!isMobile"
                            variant="text"
                            class="!text-white hover:!bg-transparent"
                            @click="toggleSidebar"
                            :icon="sidebar ? IconChevronLeft : IconChevronRight"
                            rounded
                        />
                        <DefaultButton
                            size="small"
                            v-else
                            variant="text"
                            class="!text-white hover:!bg-transparent"
                            @click="drawerMobile = !drawerMobile"
                            :icon="IconMenu2"
                            rounded
                        />
                    </div>
                    <div class="flex items-center gap-2">
                        <DefaultToggle
                            v-model="isDark"
                            @update-value="toggleDark"
                            un-check-icon-class="text-yellow-500 "
                            :un-check-icon="IconSunHighFilled"
                            :check-icon="IconMoon"
                        />

                        <div></div>
                        <!-- <OverlayBadge
                            severity="danger"
                            class="inline-flex"
                            v-if="
                                page.props?.notif.filter((e) => !e.read_at)
                                    .length != 0
                            "
                        >
                            <DefaultButton
                                variant="text"
                                class="!text-white hover:!bg-transparent"
                                :icon="IconBell"
                                size="lg"
                                :icon-size="20"
                                class-name="!w-6 !h-6"
                                @click="toggleNotif"
                                rounded
                            />
                        </OverlayBadge> -->
                        <Button
                            size="small"
                            class="!w-10 !h-10 rounded-full! text-white! hover:bg-transparent! !p-0"
                            @click="toggleNotif"
                            text
                        >
                            <template #default>
                                <div class="relative inline-flex">
                                    <IconBell size="20" />

                                    <span
                                        class="absolute -top-0.5 -right-0.5 flex size-2.5"
                                        v-if="
                                            page.props?.notif.filter(
                                                (e) => !e.read_at,
                                            ).length != 0
                                        "
                                    >
                                        <span
                                            class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-95"
                                        ></span>
                                        <span
                                            class="relative inline-flex size-2.5 rounded-full bg-red-500"
                                        ></span>
                                    </span>
                                </div>
                            </template>
                        </Button>
                        <Popover
                            ref="popNotif"
                            :pt="{
                                root: 'lg:popover-notification !ml-5 !rounded-xl',
                            }"
                        >
                            <div class="w-[25rem] max-h-[20rem] flex flex-col">
                                <div class="flex items-center justify-between">
                                    <div class="font-semibold text-sm">
                                        Notifications
                                        <span
                                            class="font-bold text-xs"
                                            v-show="
                                                page.props?.notif.length != 0
                                            "
                                            >({{
                                                page.props?.notif.filter(
                                                    (e) => !e.read_at,
                                                ).length
                                            }})</span
                                        >
                                    </div>
                                </div>
                                <Divider class="!my-3"></Divider>
                                <div
                                    class="flex-1 overflow-auto"
                                    v-if="page.props?.notif.length != 0"
                                >
                                    <div
                                        v-for="(item, index) in page.props
                                            ?.notif"
                                        :key="index"
                                        class="flex flex-col"
                                    >
                                        <div
                                            class="flex items-start justify-between"
                                            :class="
                                                item.data.url
                                                    ? 'cursor-pointer'
                                                    : ''
                                            "
                                            @click="openNotification(item)"
                                        >
                                            <div
                                                class="flex flex-1 items-start gap-2"
                                            >
                                                <div
                                                    class="p-1"
                                                    v-if="
                                                        item.data.type ==
                                                        'scholar_upload'
                                                    "
                                                >
                                                    <OverlayBadge
                                                        severity="danger"
                                                        v-if="!item.read_at"
                                                    >
                                                        <Avatar
                                                            class="rounded-2xl"
                                                            style="
                                                                background-color: #dee9fc;
                                                                color: #1a2551;
                                                            "
                                                        >
                                                            <IconFileDownload
                                                                class="text-slate-600"
                                                            />
                                                        </Avatar>
                                                    </OverlayBadge>
                                                    <Avatar
                                                        v-else
                                                        class="rounded-2xl"
                                                        style="
                                                            background-color: #dee9fc;
                                                            color: #1a2551;
                                                        "
                                                    >
                                                        <IconFileDownload
                                                            class="text-slate-600"
                                                        />
                                                    </Avatar>
                                                </div>
                                                <div
                                                    class="p-1"
                                                    v-if="
                                                        item.data.type ==
                                                        'upload_accept'
                                                    "
                                                >
                                                    <OverlayBadge
                                                        severity="danger"
                                                        v-if="!item.read_at"
                                                    >
                                                        <Avatar
                                                            class="rounded-2xl !bg-green-100 !text-green-700"
                                                        >
                                                            <IconCircleCheck />
                                                        </Avatar>
                                                    </OverlayBadge>
                                                    <Avatar
                                                        v-else
                                                        class="rounded-2xl !bg-green-100 !text-green-700"
                                                    >
                                                        <IconCircleCheck />
                                                    </Avatar>
                                                </div>
                                                <div
                                                    class="p-1"
                                                    v-if="
                                                        item.data.type ==
                                                        'upload_reject'
                                                    "
                                                >
                                                    <OverlayBadge
                                                        severity="danger"
                                                        v-if="!item.read_at"
                                                    >
                                                        <Avatar
                                                            class="rounded-2xl !bg-red-100 !text-red-700"
                                                        >
                                                            <IconCircleX />
                                                        </Avatar>
                                                    </OverlayBadge>
                                                    <Avatar
                                                        v-else
                                                        class="rounded-2xl !bg-red-100 !text-red-700"
                                                    >
                                                        <IconCircleX />
                                                    </Avatar>
                                                </div>
                                                <div
                                                    class="p-1"
                                                    v-if="
                                                        item.data.type ==
                                                        'updateInfoSchool'
                                                    "
                                                >
                                                    <OverlayBadge
                                                        severity="danger"
                                                        v-if="!item.read_at"
                                                    >
                                                        <Avatar
                                                            class="rounded-2xl !bg-green-100 !text-green-700"
                                                        >
                                                            <IconPencilCheck />
                                                        </Avatar>
                                                    </OverlayBadge>
                                                    <Avatar
                                                        v-else
                                                        class="rounded-2xl !bg-green-100 !text-green-700"
                                                    >
                                                        <IconPencilCheck />
                                                    </Avatar>
                                                </div>
                                                <!-- <div class="p-1" v-else>
                                                    <OverlayBadge
                                                        severity="danger"
                                                        v-if="!item.read_at"
                                                    >
                                                        <Avatar
                                                            class="rounded-2xl !bg-blue-100 !text-blue-700"
                                                        >
                                                            <IconDots />
                                                        </Avatar>
                                                    </OverlayBadge>
                                                    <Avatar
                                                        v-else
                                                        class="rounded-2xl !bg-blue-100 !text-blue-700"
                                                    >
                                                        <IconDots />
                                                    </Avatar>
                                                </div> -->
                                                <div class="flex flex-col">
                                                    <div
                                                        class="text-xs font-semibold"
                                                    >
                                                        {{ item.data.title }}
                                                    </div>
                                                    <div
                                                        class="text-xs text-gray-500"
                                                    >
                                                        {{ item.data.message }}
                                                    </div>
                                                </div>
                                            </div>

                                            <div
                                                class="flex flex-col items-end"
                                            >
                                                <div
                                                    class="text-xs font-medium"
                                                >
                                                    {{ item.diff_time }}
                                                </div>

                                                <DefaultButton
                                                    size="small"
                                                    severity="secondary"
                                                    rounded
                                                    tooltip="Mark as read"
                                                    text
                                                    v-if="!item.read_at"
                                                    @click.stop="
                                                        markAsRead(item.id)
                                                    "
                                                    :icon="IconCheck"
                                                    :icon-size="15"
                                                />
                                                <div v-else class="p-2">
                                                    <IconCheckbox size="15" />
                                                </div>
                                            </div>
                                        </div>
                                        <Divider
                                            class="!my-2"
                                            type="dashed"
                                        ></Divider>
                                    </div>
                                </div>
                                <div v-else>
                                    <div class="flex justify-center py-10">
                                        <div
                                            class="flex flex-col gap-1 items-center text-gray-500"
                                        >
                                            <IconBellFilled />
                                            <div>No notifications</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </Popover>
                        <HeadBarButtonMenu />
                    </div>
                </div>
            </header>
            <main class="overflow-auto flex-1 p-2">
                <slot />
            </main>
        </div>
    </div>
    <Toast />
    <DefaultConfirmDialog group="global" />
    <ConfirmDialog />
</template>
<script setup>
import SidebarIconMenu from "../Components/menus/SidebarIconMenu.vue";
import DefaultButton from "../Components/buttons/DefaultButton.vue";
import DefaultConfirmDialog from "../Components/dialogs/DefaultConfirmDialog.vue";
import HeadBarButtonMenu from "../Components/menus/HeadBarButtonMenu.vue";
import {
    IconDashboard,
    IconCategory,
    IconUsers,
    IconServerSpark,
    IconUserShield,
    IconMap,
    IconCornerDownRight,
    IconSchool,
    IconMenu2,
    IconBuildings,
    IconBooks,
    IconListDetails,
    IconChevronLeft,
    IconChevronRight,
    IconSunHighFilled,
    IconMoon,
    IconBell,
    IconFileDownload,
    IconBellFilled,
    IconChecks,
    IconCheck,
    IconBellExclamation,
    IconCheckbox,
    IconDots,
    IconPencilCheck,
    IconCircle,
    IconCircleCheck,
    IconCircleX,
    IconMenu,
} from "@tabler/icons-vue";
import { ref, onMounted, Transition, onUnmounted } from "vue";
import SidebarLabelMenu from "../Components/menus/SidebarLabelMenu.vue";
import DefaultToggle from "../Components/toggleswitches/DefaultToggle.vue";
import { router, usePage } from "@inertiajs/vue3";

const page = usePage();
const isDark = ref(false);
const savedSidebar =
    typeof window !== "undefined"
        ? localStorage.getItem("sidebar") === "true"
        : false;
const sidebar = ref(savedSidebar);
const popNotif = ref(null);
const isMobile = ref(false);
const drawerMobile = ref(false);

const toggleNotif = (e) => {
    popNotif.value.toggle(e);
};

const markAsRead = (id) => {
    router.patch(
        route("notif.read", id),
        {},
        {
            preserveScroll: true,
            preserveState: true,
        },
    );
};

const openNotification = (item) => {
    const url = item?.data?.url;

    if (!url) {
        return;
    }

    if (item.read_at) {
        router.visit(url);
        return;
    }

    router.patch(
        route("notif.read", item.id),
        {},
        {
            preserveScroll: true,
            preserveState: true,
            onSuccess: () => router.visit(url),
        },
    );
};

const checkIfMobile = () => {
    isMobile.value = window.innerWidth < 768;

    if (isMobile.value) {
        sidebar.value = false;
    }
};

function toggleDark() {
    localStorage.setItem("theme", isDark.value ? "dark" : "light");
    applyTheme();
}

const toggleSidebar = () => {
    localStorage.setItem("sidebar", !sidebar.value);
    sidebar.value = !sidebar.value;
};

function applyTheme() {
    document.documentElement.classList.toggle("dark", isDark.value);
}

onMounted(() => {
    isDark.value = localStorage.getItem("theme") === "dark";
    applyTheme();
});
checkIfMobile();
onMounted(() => {
    window.addEventListener("resize", checkIfMobile);
    checkIfMobile();
});
onUnmounted(() => {
    window.removeEventListener("resize", checkIfMobile);
});
</script>
<style>
.slide-fade {
    transition:
        transform 0.3s ease,
        opacity 0.3s ease;
}

.slide-fade-hide {
    transform: translateX(-100%);
    opacity: 1;
}

.popover-notification {
    --p-popover-arrow-left: 23.4rem !important;
}
</style>
