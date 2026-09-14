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
                            <div
                                class="flex w-[min(25rem,calc(100vw-2rem))] max-h-[30rem] flex-col"
                            >
                                <div
                                    class="flex items-start justify-between gap-4"
                                >
                                    <div>
                                        <div
                                            class="text-sm font-semibold text-slate-900 dark:text-gray-100"
                                        >
                                            Notifications
                                        </div>
                                        <div
                                            class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400"
                                        >
                                            {{
                                                unreadNotifications.length
                                                    ? `${unreadNotifications.length} unread updates`
                                                    : "You're all caught up"
                                            }}
                                        </div>
                                    </div>
                                    <Button
                                        v-if="unreadNotifications.length"
                                        label="Mark all read"
                                        text
                                        size="small"
                                        class="!p-0 !text-xs"
                                        @click="markAllAsRead"
                                    />
                                </div>
                                <Divider class="!my-3" />
                                <div
                                    v-if="page.props?.notif?.length"
                                    class="flex-1 overflow-auto pr-1"
                                >
                                    <div
                                        v-for="(item, index) in page.props
                                            .notif"
                                        :key="item.id ?? index"
                                        class="group flex items-start gap-3 rounded-xl px-2 py-3 transition-colors hover:bg-slate-50 dark:hover:bg-gray-700/60"
                                        :class="
                                            item.data.url
                                                ? 'cursor-pointer'
                                                : ''
                                        "
                                        @click="openNotification(item)"
                                    >
                                        <div
                                            class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl"
                                            :class="
                                                notificationMeta(item).surface
                                            "
                                        >
                                            <component
                                                :is="
                                                    notificationMeta(item).icon
                                                "
                                                :size="18"
                                                :class="
                                                    notificationMeta(item).color
                                                "
                                            />
                                        </div>
                                        <div class="min-w-0 flex-1">
                                            <div
                                                class="flex items-start justify-between gap-3"
                                            >
                                                <div class="min-w-0">
                                                    <div
                                                        class="truncate text-xs font-semibold"
                                                        :class="
                                                            item.read_at
                                                                ? 'text-slate-700 dark:text-gray-200'
                                                                : 'text-slate-900 dark:text-white'
                                                        "
                                                    >
                                                        {{ item.data.title }}
                                                    </div>
                                                    <div
                                                        class="mt-1 line-clamp-2 text-xs leading-4 text-slate-500 dark:text-gray-400"
                                                    >
                                                        {{ item.data.message }}
                                                    </div>
                                                </div>
                                                <span
                                                    class="shrink-0 text-[10px] text-slate-400"
                                                    >{{ item.diff_time }}</span
                                                >
                                            </div>
                                            <div
                                                class="mt-2 flex items-center justify-between"
                                            >
                                                <span
                                                    v-if="!item.read_at"
                                                    class="inline-flex items-center gap-1 text-[10px] font-medium text-blue-600 dark:text-blue-400"
                                                >
                                                    <span
                                                        class="h-1.5 w-1.5 rounded-full bg-blue-500"
                                                    />
                                                    New
                                                </span>
                                                <span
                                                    v-else
                                                    class="text-[10px] text-slate-400"
                                                    >Read</span
                                                >
                                                <Button
                                                    v-if="!item.read_at"
                                                    type="button"
                                                    label="Mark read"
                                                    text
                                                    size="small"
                                                    class="!p-0 !text-[10px] !font-medium"
                                                    @click.stop="
                                                        markAsRead(item.id)
                                                    "
                                                />
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div
                                    v-else
                                    class="flex flex-col items-center justify-center gap-2 py-12 text-center text-slate-400"
                                >
                                    <div
                                        class="flex h-11 w-11 items-center justify-center rounded-full bg-slate-100 dark:bg-gray-700"
                                    >
                                        <IconBellFilled :size="20" />
                                    </div>
                                    <div
                                        class="text-xs font-medium text-slate-600 dark:text-gray-300"
                                    >
                                        No notifications yet
                                    </div>
                                    <div class="text-[11px]">
                                        New updates will appear here.
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
    IconCheck,
    IconBellExclamation,
    IconDots,
    IconPencilCheck,
    IconCircleCheck,
    IconCircleX,
} from "@tabler/icons-vue";
import { computed, ref, onMounted, Transition, onUnmounted } from "vue";
import SidebarLabelMenu from "../Components/menus/SidebarLabelMenu.vue";
import DefaultToggle from "../Components/toggleswitches/DefaultToggle.vue";
import { router, usePage } from "@inertiajs/vue3";
import { useInactivityGuard } from "@/Composables/useInactivityGuard";

useInactivityGuard({
    inactivityTimeout: 15 * 60 * 1000,
    warningTimeout: 20,
});
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
const unreadNotifications = computed(() =>
    (page.props?.notif ?? []).filter((item) => !item.read_at),
);

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

const markAllAsRead = () => {
    unreadNotifications.value.forEach((item) => markAsRead(item.id));
};

const notificationMeta = (item) => {
    const metadata = {
        scholar_upload: {
            icon: IconFileDownload,
            surface: "bg-blue-100 dark:bg-blue-900/40",
            color: "text-blue-700 dark:text-blue-300",
        },
        upload_accept: {
            icon: IconCircleCheck,
            surface: "bg-emerald-100 dark:bg-emerald-900/40",
            color: "text-emerald-700 dark:text-emerald-300",
        },
        upload_reject: {
            icon: IconCircleX,
            surface: "bg-red-100 dark:bg-red-900/40",
            color: "text-red-700 dark:text-red-300",
        },
        updateInfoSchool: {
            icon: IconPencilCheck,
            surface: "bg-amber-100 dark:bg-amber-900/40",
            color: "text-amber-700 dark:text-amber-300",
        },
    };

    return (
        metadata[item?.data?.type] ?? {
            icon: IconDots,
            surface: "bg-slate-100 dark:bg-gray-700",
            color: "text-slate-600 dark:text-gray-300",
        }
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
