<template>
    <div class="min-h-full bg-slate-50 px-3 py-3 text-slate-800 dark:bg-gray-800 dark:text-gray-100">
        <div class="mx-auto flex max-w-[1500px] flex-col gap-4">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <div class="text-[11px] font-semibold uppercase text-slate-500 dark:text-gray-400">
                        {{ regionName }} regional office
                    </div>
                    <h1 class="text-lg font-semibold text-slate-900 dark:text-white">
                        Regional Operations Dashboard
                    </h1>
                </div>
                <div class="text-xs text-slate-500 dark:text-gray-400">
                    {{ formatNumber(card.total) }} scholars across
                    {{ formatNumber(insights.activeCampuses) }} campuses
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
                            <div class="text-[11px] font-medium text-slate-500 dark:text-gray-400">
                                {{ metric.label }}
                            </div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900 dark:text-white">
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

            <div class="grid auto-rows-[minmax(160px,auto)] grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-700 md:col-span-2">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
                                Payroll Work Queue
                            </h2>
                            <p class="text-[11px] text-slate-500 dark:text-gray-400">
                                Draft and returned payrolls that regional staff can update or resubmit.
                            </p>
                        </div>
                        <div class="flex flex-wrap justify-end gap-1 text-[11px]">
                            <button
                                type="button"
                                class="inline-flex items-center gap-1 rounded bg-blue-50 px-2 py-1 font-semibold text-blue-700 transition hover:bg-blue-100 dark:bg-blue-900/40 dark:text-blue-300 dark:hover:bg-blue-900/60"
                                @click="goToPayroll"
                            >
                                <IconEye :size="13" />
                                View
                            </button>
                            <span class="rounded bg-slate-100 px-2 py-1 text-slate-700 dark:bg-gray-800 dark:text-gray-200">
                                Draft {{ formatNumber(payrollSummary.draft) }}
                            </span>
                            <span class="rounded bg-red-50 px-2 py-1 text-red-700 dark:bg-red-900/40 dark:text-red-300">
                                Returned {{ formatNumber(payrollSummary.rejected_payroll) }}
                            </span>
                        </div>
                    </div>

                    <div class="max-h-[210px] overflow-auto rounded border border-slate-200 dark:border-gray-600">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-100 text-xs text-slate-600 dark:bg-gray-800 dark:text-gray-300">
                                <tr>
                                    <th class="sticky top-0 bg-slate-100 px-2 py-1.5 font-semibold dark:bg-gray-800">Payroll</th>
                                    <th class="sticky top-0 bg-slate-100 px-2 py-1.5 font-semibold dark:bg-gray-800">Term</th>
                                    <th class="sticky top-0 bg-slate-100 px-2 py-1.5 font-semibold dark:bg-gray-800">Recipients</th>
                                    <th class="sticky top-0 bg-slate-100 px-2 py-1.5 font-semibold dark:bg-gray-800">Status</th>
                                    <th class="sticky top-0 bg-slate-100 px-2 py-1.5 font-semibold dark:bg-gray-800">Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="batch in payrollQueue"
                                    :key="batch.id"
                                    class="border-t border-slate-100 dark:border-gray-600"
                                >
                                    <td class="px-2 py-1.5">
                                        <div class="font-medium text-slate-800 dark:text-gray-100">{{ batch.name }}</div>
                                        <div v-if="batch.remarks" class="truncate text-xs text-slate-500 dark:text-gray-400">
                                            {{ batch.remarks }}
                                        </div>
                                    </td>
                                    <td class="px-2 py-1.5 text-slate-600 dark:text-gray-300">
                                        {{ batch.term }} {{ batch.school_year }}
                                    </td>
                                    <td class="px-2 py-1.5 text-slate-600 dark:text-gray-300">
                                        {{ formatNumber(batch.recipients) }}
                                    </td>
                                    <td class="px-2 py-1.5">
                                        <span :class="statusClass(batch.status)">
                                            {{ batch.status_label }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-1.5 text-slate-600 dark:text-gray-300">
                                        {{ batch.updated_at || "-" }}
                                    </td>
                                </tr>
                                <tr v-if="!payrollQueue.length">
                                    <td colspan="5" class="px-3 py-6 text-center text-xs text-slate-500 dark:text-gray-400">
                                        No draft or returned payrolls need attention.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-700">
                    <div class="mb-4 flex items-start justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
                                Pending Submissions
                            </h2>
                            <p class="text-[11px] text-slate-500 dark:text-gray-400">
                                Requests waiting for regional review.
                            </p>
                        </div>
                        <button
                            type="button"
                            class="inline-flex shrink-0 items-center gap-1 rounded bg-blue-50 px-2 py-1 text-[11px] font-semibold text-blue-700 transition hover:bg-blue-100 dark:bg-blue-900/40 dark:text-blue-300 dark:hover:bg-blue-900/60"
                            @click="goToSubmissions()"
                        >
                            <IconEye :size="13" />
                            View
                        </button>
                    </div>
                    <div class="grid gap-3 text-xs">
                        <div
                            v-for="item in pendingSubmissionCards"
                            :key="item.label"
                            class="rounded-lg border border-slate-100 px-4 py-3 transition hover:border-blue-200 hover:bg-blue-50/40 dark:border-gray-600 dark:hover:border-blue-800 dark:hover:bg-blue-900/20"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div>
                                    <div class="text-slate-500 dark:text-gray-400">{{ item.label }}</div>
                                    <button
                                        type="button"
                                        class="mt-1 inline-flex items-center gap-1 text-[11px] font-semibold text-blue-600 hover:text-blue-500 dark:text-blue-300 dark:hover:text-blue-200"
                                        @click="goToSubmissions(item.tab)"
                                    >
                                        View
                                        <IconArrowRight :size="12" />
                                    </button>
                                </div>
                                <div class="text-right text-lg font-semibold text-slate-900 dark:text-white">
                                    {{ formatNumber(item.value) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <section class="flex flex-col rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-700 md:col-span-2 xl:row-span-2">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="rounded bg-blue-50 p-2 text-blue-600">
                            <IconDeviceDesktopAnalytics :size="20" />
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
                                Awarded Scholars By Year
                            </h2>
                            <p class="text-[11px] text-slate-500 dark:text-gray-400">
                                Program distribution in {{ regionName }}.
                            </p>
                        </div>
                    </div>
                    <div class="min-h-0 flex-1 rounded-lg border border-slate-100 bg-slate-50 px-4 py-3 dark:border-gray-600 dark:bg-gray-800">
                        <ApexChart
                            type="bar"
                            height="520"
                            :options="apexOptionsTimeline"
                            :series="timeline.series"
                        />
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-700">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="rounded bg-emerald-50 p-2 text-emerald-600">
                            <IconChartDonut :size="20" />
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
                                Scholarship Type Distribution
                            </h2>
                            <p class="text-[11px] text-slate-500 dark:text-gray-400">
                                Scholars grouped by scholarship type.
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col gap-4">
                        <div class="flex items-center justify-center">
                            <ApexChart
                                type="donut"
                                height="190"
                                :options="apexOptionsScholarshipTypes"
                                :series="scholarshipTypeSeries"
                            />
                        </div>
                        <div class="grid gap-2 text-xs">
                            <div
                                v-for="program in scholarshipTypeDistribution"
                                :key="program.name"
                                class="flex items-center justify-between gap-3 rounded border border-slate-100 px-3 py-2 dark:border-gray-600"
                            >
                                <div class="flex min-w-0 items-center gap-2">
                                    <span :class="['h-2.5 w-2.5 shrink-0 rounded-full', program.dot]" />
                                    <span class="truncate font-medium text-slate-700 dark:text-gray-200">{{ program.name }}</span>
                                </div>
                                <div class="shrink-0 font-semibold text-slate-900 dark:text-white">
                                    {{ formatNumber(program.count) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-700">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="rounded bg-violet-50 p-2 text-violet-600">
                            <IconUsersGroup :size="20" />
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
                                Sex Distribution
                            </h2>
                            <p class="text-[11px] text-slate-500 dark:text-gray-400">
                                Profile composition across regional scholars.
                            </p>
                        </div>
                    </div>
                    <div class="rounded-lg border border-slate-100 bg-slate-50 p-4 dark:border-gray-600 dark:bg-gray-800">
                        <div class="mb-3 flex items-end justify-between">
                            <span class="text-xs text-slate-500 dark:text-gray-400">Total with profile sex</span>
                            <span class="text-2xl font-semibold text-slate-900 dark:text-white">
                                {{ formatNumber(genderTotal) }}
                            </span>
                        </div>
                        <div class="flex h-8 overflow-hidden rounded bg-slate-200 dark:bg-gray-700">
                            <div
                                v-for="segment in sexSegments"
                                :key="segment.label"
                                :class="segment.color"
                                :style="{ width: `${segment.percent}%` }"
                                class="min-w-0 transition-all"
                            />
                        </div>
                        <div class="mt-4 grid gap-2 text-xs">
                            <div
                                v-for="item in genderDetails"
                                :key="item.label"
                                class="flex items-center justify-between gap-3"
                            >
                                <div class="flex items-center gap-2 text-slate-600 dark:text-gray-300">
                                    <span :class="['h-2.5 w-2.5 rounded-full', item.dot]" />
                                    {{ item.label }}
                                </div>
                                <div class="font-semibold text-slate-900 dark:text-white">
                                    {{ formatNumber(item.value) }}
                                    <span class="font-medium text-slate-400">
                                        {{ item.percent }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <section
                    class="flex flex-col overflow-hidden rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-700 md:col-span-2 xl:col-span-2"
                    style="height: 340px"
                >
                    <div class="mb-4 flex items-center gap-3">
                        <IconBuildingEstate :size="18" class="text-rose-600" />
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
                                Scholar Count by School
                            </h2>
                            <p class="hidden text-[11px] text-slate-500 dark:text-gray-400 sm:block">
                                Schools with the highest scholar count in the region.
                            </p>
                        </div>
                    </div>
                    <div class="min-h-0 flex-1 overflow-auto pr-1">
                        <div class="grid gap-3">
                            <div
                                v-for="school in schoolDistribution"
                                :key="school.name"
                                class="grid grid-cols-[1fr_auto] items-center gap-3"
                            >
                                <div class="min-w-0">
                                    <div class="truncate text-xs font-medium text-slate-700 dark:text-gray-200">
                                        {{ school.name }}
                                    </div>
                                    <div class="mt-1 h-2 rounded bg-slate-100 dark:bg-gray-800">
                                        <div
                                            class="h-2 rounded bg-rose-500"
                                            :style="{ width: `${school.percent}%` }"
                                        />
                                    </div>
                                </div>
                                <div class="text-xs font-semibold text-slate-900 dark:text-white">
                                    {{ formatNumber(school.count) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script setup>
import { router, usePage } from "@inertiajs/vue3";
import {
    IconArrowRight,
    IconBuildingEstate,
    IconChartDonut,
    IconClipboardList,
    IconDeviceDesktopAnalytics,
    IconEye,
    IconFileCheck,
    IconSchool,
    IconUsersGroup,
} from "@tabler/icons-vue";
import { computed, onMounted, onUnmounted, ref } from "vue";
import ApexChart from "vue3-apexcharts";
import { route } from "ziggy-js";

const page = usePage();
const { card, timeline, user, gender } = page.props;
const isDark = ref(false);
const insights = computed(() => page.props.regionalInsights ?? {});
const regionName = computed(() => user.profile.agency.slug?.toUpperCase() ?? "Regional");
const payrollSummary = computed(() => insights.value.payrollSummary ?? {});
const payrollQueue = computed(() => insights.value.payrollQueue ?? []);

const metrics = computed(() => [
    {
        label: "Total Scholars",
        value: card.total,
        caption: "Current scholar records",
        icon: IconUsersGroup,
        color: "bg-blue-50 text-blue-600",
    },
    {
        label: "Undergraduate",
        value: card.Ucnt,
        caption: "Current undergraduate scholars",
        icon: IconSchool,
        color: "bg-emerald-50 text-emerald-600",
    },
    {
        label: "JLSS",
        value: card.Jcnt,
        caption: "Current JLSS scholars",
        icon: IconFileCheck,
        color: "bg-cyan-50 text-cyan-600",
    },
    {
        label: "Payroll Needs Action",
        value: Number(payrollSummary.value.draft ?? 0) + Number(payrollSummary.value.rejected_payroll ?? 0),
        caption: "Draft and returned payrolls",
        icon: IconClipboardList,
        color: "bg-red-50 text-red-600",
    },
]);

const scholarshipTypeDots = ["bg-blue-500", "bg-emerald-500", "bg-rose-500", "bg-amber-500", "bg-cyan-500"];
const scholarshipTypeColors = ["#2563EB", "#10B981", "#E11D48", "#F59E0B", "#06B6D4"];
const scholarshipTypeDistribution = computed(() => {
    const rows = timeline.timelineTotal ?? [];

    return rows.map((item, index) => ({
        name: item.name,
        count: item.data,
        dot: scholarshipTypeDots[index % scholarshipTypeDots.length],
    }));
});
const scholarshipTypeSeries = computed(() => scholarshipTypeDistribution.value.map((item) => Number(item.count ?? 0)));
const genderDetails = computed(() => {
    const rows = gender?.result ?? [];
    const female = Number(rows.find((item) => item.sex === "F")?.total ?? 0);
    const male = Number(rows.find((item) => item.sex === "M")?.total ?? 0);
    const total = Math.max(female + male, 1);

    return [
        {
            label: "Female",
            value: female,
            percent: Math.round((female / total) * 100),
            dot: "bg-pink-500",
        },
        {
            label: "Male",
            value: male,
            percent: Math.round((male / total) * 100),
            dot: "bg-blue-500",
        },
    ];
});
const genderTotal = computed(() => genderDetails.value.reduce((sum, item) => sum + item.value, 0));
const sexSegments = computed(() => genderDetails.value.map((item) => ({
    label: item.label,
    percent: genderTotal.value === 0 ? 0 : item.percent,
    color: item.label === "Female" ? "bg-pink-500" : "bg-blue-500",
})));
const schoolDistribution = computed(() => {
    const rows = insights.value.schoolDistribution ?? [];
    const max = Math.max(...rows.map((item) => Number(item.count ?? 0)), 1);

    return rows.map((item) => ({
        ...item,
        percent: Number(item.count ?? 0) === 0
            ? 0
            : Math.max(4, Math.round((Number(item.count ?? 0) / max) * 100)),
    }));
});
const pendingSubmissionCards = computed(() => [
    { label: "Grade Submissions", value: insights.value.pendingSubmissions?.grades ?? 0, tab: "grades" },
    { label: "Academic History", value: insights.value.pendingSubmissions?.history ?? 0, tab: "history" },
    { label: "Profile Updates", value: insights.value.pendingSubmissions?.profile ?? 0, tab: "profile" },
    { label: "Landbank Requests", value: insights.value.pendingSubmissions?.landbank ?? 0, tab: "landbank" },
]);
const chartTextColor = computed(() => (isDark.value ? "#d1d5db" : "#64748b"));
const chartGridColor = computed(() => (isDark.value ? "rgba(75,85,99,0.9)" : "rgba(148,163,184,0.35)"));
const chartStrokeColor = computed(() => (isDark.value ? "#374151" : "#fff"));
const chartTooltipTheme = computed(() => (isDark.value ? "dark" : "light"));

const apexOptionsTimeline = computed(() => ({
    chart: {
        type: "bar",
        stacked: true,
        toolbar: { show: false },
        fontFamily: "inherit",
        foreColor: chartTextColor.value,
    },
    plotOptions: {
        bar: {
            horizontal: false,
            borderRadius: 4,
            columnWidth: "35%",
        },
    },
    colors: ["#2563EB", "#16A34A", "#E11D48"],
    dataLabels: { enabled: false },
    stroke: { show: true, width: 1, colors: [chartStrokeColor.value] },
    legend: { show: true, position: "bottom" },
    grid: {
        borderColor: chartGridColor.value,
        strokeDashArray: 3,
        padding: {
            top: 8,
            right: 12,
            bottom: 0,
            left: 8,
        },
    },
    xaxis: {
        categories: timeline.categories,
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    tooltip: { theme: chartTooltipTheme.value },
}));

const apexOptionsScholarshipTypes = computed(() => ({
    chart: {
        type: "donut",
        toolbar: { show: false },
        fontFamily: "inherit",
        foreColor: chartTextColor.value,
    },
    labels: scholarshipTypeDistribution.value.map((item) => item.name),
    colors: scholarshipTypeColors,
    dataLabels: { enabled: false },
    legend: { show: false },
    stroke: { show: false },
    plotOptions: {
        pie: {
            donut: {
                size: "72%",
                labels: {
                    show: true,
                    total: {
                        show: true,
                        label: "Scholars",
                        formatter: () => formatNumber(scholarshipTypeSeries.value.reduce((sum, value) => sum + value, 0)),
                    },
                },
            },
        },
    },
    tooltip: { theme: chartTooltipTheme.value },
}));

function statusClass(status) {
    const classes = {
        draft: "rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 dark:bg-gray-800 dark:text-gray-200",
        submitted_payroll: "rounded bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700 dark:bg-blue-900/40 dark:text-blue-300",
        rejected_payroll: "rounded bg-red-50 px-2 py-1 text-xs font-semibold text-red-700 dark:bg-red-900/40 dark:text-red-300",
        approved_payroll: "rounded bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300",
    };

    return classes[status] ?? "rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700 dark:bg-gray-800 dark:text-gray-200";
}

function formatNumber(num) {
    return Number(num ?? 0).toLocaleString("en-US");
}

function goToPayroll() {
    router.visit(route("stipends"));
}

function goToSubmissions(tab = null) {
    router.visit(route("scholar-submissions", tab ? { tab } : undefined));
}

let themeObserver;

function syncTheme() {
    isDark.value = document.documentElement.classList.contains("dark");
}

onMounted(() => {
    syncTheme();
    themeObserver = new MutationObserver(syncTheme);
    themeObserver.observe(document.documentElement, {
        attributes: true,
        attributeFilter: ["class"],
    });
});

onUnmounted(() => {
    themeObserver?.disconnect();
});
</script>
