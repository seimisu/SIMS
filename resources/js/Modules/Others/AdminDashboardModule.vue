<template>
    <div
        class="min-h-full bg-slate-50 px-3 py-3 text-slate-800 dark:bg-gray-800 dark:text-gray-100"
    >
        <div class="mx-auto flex max-w-[1500px] flex-col gap-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <div
                        class="text-[11px] font-semibold uppercase text-slate-500 dark:text-gray-400"
                    >
                        System administration
                    </div>
                    <h1
                        class="text-lg font-semibold text-slate-900 dark:text-white"
                    >
                        Admin Dashboard
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-gray-400">
                        Keep the scholarship information system healthy and up
                        to date.
                    </p>
                </div>
                <div
                    class="rounded-md border border-slate-200 bg-white px-3 py-2 text-xs text-slate-500 shadow-sm dark:border-gray-700 dark:bg-gray-700 dark:text-gray-300"
                >
                    <span
                        class="font-semibold text-slate-800 dark:text-white"
                        >{{ formatNumber(summary.active_users) }}</span
                    >
                    active users
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <section
                    v-for="metric in metrics"
                    :key="metric.label"
                    class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm dark:border-gray-700 dark:bg-gray-700"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div
                                class="text-[11px] font-medium text-slate-500 dark:text-gray-400"
                            >
                                {{ metric.label }}
                            </div>
                            <div
                                class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white"
                            >
                                {{ formatNumber(metric.value) }}
                            </div>
                        </div>
                        <div :class="['rounded p-2', metric.color]">
                            <component :is="metric.icon" :size="20" />
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-slate-500 dark:text-gray-400">
                        {{ metric.caption }}
                    </div>
                </section>
            </div>

            <div class="grid gap-4 lg:grid-cols-[1.4fr_1fr]">
                <section
                    class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-700"
                >
                    <div class="mb-4 flex items-center gap-3">
                        <div
                            class="rounded bg-blue-50 p-2 text-blue-600 dark:bg-blue-900/30 dark:text-blue-300"
                        >
                            <IconLayoutDashboard :size="20" />
                        </div>
                        <div>
                            <h2
                                class="text-sm font-semibold text-slate-900 dark:text-white"
                            >
                                Administration shortcuts
                            </h2>
                            <p
                                class="text-[11px] text-slate-500 dark:text-gray-400"
                            >
                                Jump directly to commonly used system areas.
                            </p>
                        </div>
                    </div>
                    <div class="grid gap-2 sm:grid-cols-2">
                        <button
                            v-for="action in actions"
                            :key="action.label"
                            type="button"
                            class="flex cursor-pointer items-center gap-3 rounded-md border border-slate-200 px-3 py-3 text-left transition hover:border-blue-300 hover:bg-blue-50/60 dark:border-gray-600 dark:hover:border-blue-700 dark:hover:bg-blue-900/20"
                            @click="goTo(action.routeName)"
                        >
                            <component
                                :is="action.icon"
                                :size="18"
                                :class="action.color"
                            />
                            <span>
                                <span
                                    class="block text-xs font-semibold text-slate-800 dark:text-gray-100"
                                    >{{ action.label }}</span
                                >
                                <span
                                    class="mt-0.5 block text-[11px] text-slate-500 dark:text-gray-400"
                                    >{{ action.caption }}</span
                                >
                            </span>
                        </button>
                    </div>
                </section>

                <section
                    class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-700"
                >
                    <div class="mb-4 flex items-center gap-3">
                        <div
                            class="rounded bg-amber-50 p-2 text-amber-600 dark:bg-amber-900/30 dark:text-amber-300"
                        >
                            <IconShieldCheck :size="20" />
                        </div>
                        <div>
                            <h2
                                class="text-sm font-semibold text-slate-900 dark:text-white"
                            >
                                System status
                            </h2>
                            <p
                                class="text-[11px] text-slate-500 dark:text-gray-400"
                            >
                                A quick view of core records.
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col gap-3 text-xs">
                        <div
                            v-for="status in statuses"
                            :key="status.label"
                            class="flex items-center justify-between border-b border-slate-100 pb-3 last:border-0 last:pb-0 dark:border-gray-600"
                        >
                            <div
                                class="flex items-center gap-2 text-slate-600 dark:text-gray-300"
                            >
                                <span
                                    :class="[
                                        'h-2 w-2 rounded-full',
                                        status.dot,
                                    ]"
                                />
                                {{ status.label }}
                            </div>
                            <span
                                class="font-semibold text-slate-900 dark:text-white"
                                >{{ formatNumber(status.value) }}</span
                            >
                        </div>
                    </div>
                </section>
            </div>

            <div class="grid gap-4 lg:grid-cols-[1fr_1.4fr]">
                <section
                    class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-700"
                >
                    <div class="mb-3 flex items-center gap-3">
                        <div
                            class="rounded bg-rose-50 p-2 text-rose-600 dark:bg-rose-900/30 dark:text-rose-300"
                        >
                            <IconCalendarWeek :size="20" />
                        </div>
                        <div>
                            <h2
                                class="text-sm font-semibold text-slate-900 dark:text-white"
                            >
                                Administration calendar
                            </h2>
                            <p
                                class="text-[11px] text-slate-500 dark:text-gray-400"
                            >
                                Registration activity and important dates.
                            </p>
                        </div>
                    </div>
                    <VCalendar
                        v-model="selectedDate"
                        transparent
                        borderless
                        expanded
                        :attributes="calendarAttributes"
                        title-position="left"
                    />
                    <div
                        class="mt-3 flex items-center justify-between rounded-md bg-slate-50 px-3 py-2 text-xs dark:bg-gray-800"
                    >
                        <span class="text-slate-500 dark:text-gray-400"
                            >Selected date</span
                        >
                        <span
                            class="font-semibold text-slate-800 dark:text-gray-100"
                            >{{ formatDate(selectedDate) }}</span
                        >
                    </div>
                </section>

                <section
                    class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-700"
                >
                    <div class="mb-4 flex items-center gap-3">
                        <div
                            class="rounded bg-cyan-50 p-2 text-cyan-600 dark:bg-cyan-900/30 dark:text-cyan-300"
                        >
                            <IconBell :size="20" />
                        </div>
                        <div>
                            <h2
                                class="text-sm font-semibold text-slate-900 dark:text-white"
                            >
                                Calendar activity
                            </h2>
                            <p
                                class="text-[11px] text-slate-500 dark:text-gray-400"
                            >
                                Recent account events in the system.
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col gap-2">
                        <div
                            v-for="event in calendarEvents"
                            :key="event.id"
                            class="flex items-center gap-3 rounded-md border border-slate-100 px-3 py-3 dark:border-gray-600"
                        >
                            <div
                                class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-cyan-50 text-cyan-600 dark:bg-cyan-900/30 dark:text-cyan-300"
                            >
                                <IconUserPlus :size="16" />
                            </div>
                            <div class="min-w-0 flex-1">
                                <div
                                    class="truncate text-xs font-semibold text-slate-800 dark:text-gray-100"
                                >
                                    {{ event.title }}
                                </div>
                                <div
                                    class="mt-0.5 text-[11px] text-slate-500 dark:text-gray-400"
                                >
                                    {{ event.date }}
                                </div>
                            </div>
                            <span
                                class="text-[11px] font-medium text-cyan-600 dark:text-cyan-300"
                                >New</span
                            >
                        </div>
                        <p
                            v-if="!calendarEvents.length"
                            class="py-8 text-center text-xs text-slate-500 dark:text-gray-400"
                        >
                            No recent calendar activity.
                        </p>
                    </div>
                </section>
            </div>

            <div class="grid gap-4 lg:grid-cols-[1fr_1.4fr]">
                <section
                    class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-700"
                >
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div
                                class="rounded bg-violet-50 p-2 text-violet-600 dark:bg-violet-900/30 dark:text-violet-300"
                            >
                                <IconChartBar :size="20" />
                            </div>
                            <div>
                                <h2
                                    class="text-sm font-semibold text-slate-900 dark:text-white"
                                >
                                    Active users by role
                                </h2>
                                <p
                                    class="text-[11px] text-slate-500 dark:text-gray-400"
                                >
                                    Current account distribution.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col gap-3 text-xs">
                        <div
                            v-for="role in summary.role_distribution ?? []"
                            :key="role.name"
                            class="flex items-center gap-3"
                        >
                            <span
                                class="w-32 shrink-0 truncate text-slate-600 dark:text-gray-300"
                                >{{ role.name }}</span
                            >
                            <div
                                class="h-2 min-w-0 flex-1 overflow-hidden rounded-full bg-slate-100 dark:bg-gray-600"
                            >
                                <div
                                    class="h-full rounded-full bg-violet-500"
                                    :style="{
                                        width: `${rolePercent(role.count)}%`,
                                    }"
                                />
                            </div>
                            <span
                                class="w-8 text-right font-semibold text-slate-900 dark:text-white"
                                >{{ formatNumber(role.count) }}</span
                            >
                        </div>
                        <p
                            v-if="!summary.role_distribution?.length"
                            class="text-slate-500 dark:text-gray-400"
                        >
                            No role data available.
                        </p>
                    </div>
                </section>

                <section
                    class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-700"
                >
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div
                                class="rounded bg-sky-50 p-2 text-sky-600 dark:bg-sky-900/30 dark:text-sky-300"
                            >
                                <IconUserPlus :size="20" />
                            </div>
                            <div>
                                <h2
                                    class="text-sm font-semibold text-slate-900 dark:text-white"
                                >
                                    Recently registered users
                                </h2>
                                <p
                                    class="text-[11px] text-slate-500 dark:text-gray-400"
                                >
                                    Latest accounts added to the system.
                                </p>
                            </div>
                        </div>
                        <span
                            class="rounded bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-700 dark:bg-amber-900/30 dark:text-amber-300"
                        >
                            {{ formatNumber(summary.pending_users) }} pending
                            verification
                        </span>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full text-left text-xs">
                            <thead
                                class="border-b border-slate-100 text-[11px] text-slate-500 dark:border-gray-600 dark:text-gray-400"
                            >
                                <tr>
                                    <th class="pb-2 font-medium">Account</th>
                                    <th class="pb-2 font-medium">Role</th>
                                    <th class="pb-2 font-medium">Registered</th>
                                    <th class="pb-2 text-right font-medium">
                                        Status
                                    </th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="account in summary.recent_users ??
                                    []"
                                    :key="account.email"
                                    class="border-b border-slate-100 last:border-0 dark:border-gray-600"
                                >
                                    <td
                                        class="max-w-[180px] truncate py-3 font-medium text-slate-800 dark:text-gray-100"
                                    >
                                        {{ account.email }}
                                    </td>
                                    <td
                                        class="py-3 text-slate-500 dark:text-gray-300"
                                    >
                                        {{ account.role }}
                                    </td>
                                    <td
                                        class="py-3 text-slate-500 dark:text-gray-300"
                                    >
                                        {{ account.created_at }}
                                    </td>
                                    <td class="py-3 text-right">
                                        <span
                                            :class="
                                                account.is_active &&
                                                account.is_verified
                                                    ? 'text-emerald-600 dark:text-emerald-400'
                                                    : 'text-amber-600 dark:text-amber-400'
                                            "
                                        >
                                            {{
                                                account.is_active &&
                                                account.is_verified
                                                    ? "Ready"
                                                    : "Pending"
                                            }}
                                        </span>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <p
                            v-if="!summary.recent_users?.length"
                            class="py-4 text-xs text-slate-500 dark:text-gray-400"
                        >
                            No recent registrations.
                        </p>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script setup>
import { computed, ref } from "vue";
import { router } from "@inertiajs/vue3";
import {
    IconBell,
    IconBook2,
    IconBuilding,
    IconCalendarWeek,
    IconChartBar,
    IconLayoutDashboard,
    IconSchool,
    IconShieldCheck,
    IconUserPlus,
    IconUsers,
} from "@tabler/icons-vue";

const props = defineProps({
    summary: {
        type: Object,
        default: () => ({}),
    },
});

const selectedDate = ref(new Date());

const calendarEvents = computed(() =>
    (props.summary.recent_users ?? []).map((account, index) => ({
        id: `${account.email}-${index}`,
        title: `New account: ${account.email}`,
        date: account.created_at,
        dateValue: new Date(account.created_at),
    })),
);

const calendarAttributes = computed(() => [
    {
        key: "today",
        highlight: {
            color: "blue",
            fillMode: "light",
        },
        dates: new Date(),
    },
    ...calendarEvents.value.map((event) => ({
        key: event.id,
        dot: "cyan",
        dates: event.dateValue,
        popover: {
            label: event.title,
        },
    })),
]);

const metrics = [
    {
        label: "Users",
        value: props.summary.total_users,
        caption: "Registered system users",
        icon: IconUsers,
        color: "bg-blue-50 text-blue-600",
    },
    {
        label: "Scholars",
        value: props.summary.total_scholars,
        caption: "Scholar records",
        icon: IconSchool,
        color: "bg-emerald-50 text-emerald-600",
    },
    {
        label: "Schools",
        value: props.summary.total_schools,
        caption: "Registered campuses",
        icon: IconBuilding,
        color: "bg-violet-50 text-violet-600",
    },
    {
        label: "Programs",
        value: props.summary.total_programs,
        caption: "Configured scholarship programs",
        icon: IconBook2,
        color: "bg-amber-50 text-amber-600",
    },
];

const actions = [
    {
        label: "Manage users",
        caption: "Accounts and access",
        routeName: "users",
        icon: IconUsers,
        color: "text-blue-600",
    },
    {
        label: "Manage roles",
        caption: "Permissions and roles",
        routeName: "roles",
        icon: IconShieldCheck,
        color: "text-emerald-600",
    },
    {
        label: "Review scholars",
        caption: "Scholar records",
        routeName: "scholars",
        icon: IconSchool,
        color: "text-violet-600",
    },
    {
        label: "Manage schools",
        caption: "Campus information",
        routeName: "academic.universities",
        icon: IconBuilding,
        color: "text-amber-600",
    },
];

const statuses = [
    {
        label: "Active users",
        value: props.summary.active_users,
        dot: "bg-emerald-500",
    },
    {
        label: "Inactive users",
        value: props.summary.inactive_users,
        dot: "bg-slate-400",
    },
    {
        label: "Active programs",
        value: props.summary.active_programs,
        dot: "bg-blue-500",
    },
];

function formatNumber(value) {
    return Number(value ?? 0).toLocaleString("en-US");
}

function formatDate(value) {
    return new Date(value).toLocaleDateString("en-US", {
        month: "short",
        day: "numeric",
        year: "numeric",
    });
}

function rolePercent(value) {
    const total = (props.summary.role_distribution ?? []).reduce(
        (sum, role) => sum + Number(role.count ?? 0),
        0,
    );

    return total ? Math.max((Number(value ?? 0) / total) * 100, 3) : 0;
}

function goTo(routeName) {
    router.visit(route(routeName));
}
</script>
