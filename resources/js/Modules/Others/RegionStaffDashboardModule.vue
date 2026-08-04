<template>
    <div class="min-h-full bg-slate-50 px-3 py-3 text-slate-800">
        <div class="mx-auto flex max-w-[1500px] flex-col gap-4">
            <div class="flex flex-wrap items-end justify-between gap-2">
                <div>
                    <div class="text-[11px] font-semibold uppercase text-slate-500">
                        {{ regionName }} regional office
                    </div>
                    <h1 class="text-lg font-semibold text-slate-900">
                        Regional Operations Dashboard
                    </h1>
                </div>
                <div class="text-xs text-slate-500">
                    {{ formatNumber(card.total) }} scholars across
                    {{ formatNumber(insights.activeCampuses) }} campuses
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
                <section
                    v-for="metric in metrics"
                    :key="metric.label"
                    class="rounded-lg border border-slate-200 bg-white p-4 shadow-sm"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div>
                            <div class="text-[11px] font-medium text-slate-500">
                                {{ metric.label }}
                            </div>
                            <div class="mt-2 text-2xl font-semibold text-slate-900">
                                {{ formatNumber(metric.value) }}
                            </div>
                        </div>
                        <div :class="['rounded p-2', metric.color]">
                            <component :is="metric.icon" :size="20" />
                        </div>
                    </div>
                    <div class="mt-2 text-xs text-slate-500">
                        {{ metric.caption }}
                    </div>
                </section>
            </div>

            <div class="grid auto-rows-[minmax(160px,auto)] grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm md:col-span-2">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">
                                Payroll Work Queue
                            </h2>
                            <p class="text-[11px] text-slate-500">
                                Draft and returned payrolls that regional staff can update or resubmit.
                            </p>
                        </div>
                        <div class="flex gap-1 text-[11px]">
                            <span class="rounded bg-slate-100 px-2 py-1 text-slate-700">
                                Draft {{ formatNumber(payrollSummary.draft) }}
                            </span>
                            <span class="rounded bg-red-50 px-2 py-1 text-red-700">
                                Returned {{ formatNumber(payrollSummary.rejected_payroll) }}
                            </span>
                        </div>
                    </div>

                    <div class="max-h-[210px] overflow-auto rounded border border-slate-200">
                        <table class="w-full text-left text-xs">
                            <thead class="bg-slate-100 text-xs text-slate-600">
                                <tr>
                                    <th class="sticky top-0 bg-slate-100 px-2 py-1.5 font-semibold">Payroll</th>
                                    <th class="sticky top-0 bg-slate-100 px-2 py-1.5 font-semibold">Term</th>
                                    <th class="sticky top-0 bg-slate-100 px-2 py-1.5 font-semibold">Recipients</th>
                                    <th class="sticky top-0 bg-slate-100 px-2 py-1.5 font-semibold">Status</th>
                                    <th class="sticky top-0 bg-slate-100 px-2 py-1.5 font-semibold">Updated</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="batch in payrollQueue"
                                    :key="batch.id"
                                    class="border-t border-slate-100"
                                >
                                    <td class="px-2 py-1.5">
                                        <div class="font-medium text-slate-800">{{ batch.name }}</div>
                                        <div v-if="batch.remarks" class="truncate text-xs text-slate-500">
                                            {{ batch.remarks }}
                                        </div>
                                    </td>
                                    <td class="px-2 py-1.5 text-slate-600">
                                        {{ batch.term }} {{ batch.school_year }}
                                    </td>
                                    <td class="px-2 py-1.5 text-slate-600">
                                        {{ formatNumber(batch.recipients) }}
                                    </td>
                                    <td class="px-2 py-1.5">
                                        <span :class="statusClass(batch.status)">
                                            {{ batch.status_label }}
                                        </span>
                                    </td>
                                    <td class="px-2 py-1.5 text-slate-600">
                                        {{ batch.updated_at || "-" }}
                                    </td>
                                </tr>
                                <tr v-if="!payrollQueue.length">
                                    <td colspan="5" class="px-3 py-6 text-center text-xs text-slate-500">
                                        No draft or returned payrolls need attention.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <h2 class="text-sm font-semibold text-slate-900">
                        Pending Submissions
                    </h2>
                    <p class="mb-4 text-[11px] text-slate-500">
                        Requests waiting for regional review.
                    </p>
                    <div class="grid gap-3 text-xs">
                        <div
                            v-for="item in pendingSubmissionCards"
                            :key="item.label"
                            class="rounded-lg border border-slate-100 px-4 py-3"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div class="text-slate-500">{{ item.label }}</div>
                                <div class="text-lg font-semibold text-slate-900">
                                    {{ formatNumber(item.value) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </section>
                <section class="flex flex-col rounded-lg border border-slate-200 bg-white p-5 shadow-sm md:col-span-2 xl:row-span-2">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="rounded bg-blue-50 p-2 text-blue-600">
                            <IconDeviceDesktopAnalytics :size="20" />
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">
                                Awarded Scholars By Year
                            </h2>
                            <p class="text-[11px] text-slate-500">
                                Program distribution in {{ regionName }}.
                            </p>
                        </div>
                    </div>
                    <div class="min-h-0 flex-1 rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                        <ApexChart
                            type="bar"
                            height="520"
                            :options="apexOptionsTimeline"
                            :series="timeline.series"
                        />
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="rounded bg-emerald-50 p-2 text-emerald-600">
                            <IconChartDonut :size="20" />
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">
                                Scholarship Type Distribution
                            </h2>
                            <p class="text-[11px] text-slate-500">
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
                                class="flex items-center justify-between gap-3 rounded border border-slate-100 px-3 py-2"
                            >
                                <div class="flex min-w-0 items-center gap-2">
                                    <span :class="['h-2.5 w-2.5 shrink-0 rounded-full', program.dot]" />
                                    <span class="truncate font-medium text-slate-700">{{ program.name }}</span>
                                </div>
                                <div class="shrink-0 font-semibold text-slate-900">
                                    {{ formatNumber(program.count) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="rounded bg-violet-50 p-2 text-violet-600">
                            <IconUsersGroup :size="20" />
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">
                                Sex Distribution
                            </h2>
                            <p class="text-[11px] text-slate-500">
                                Profile composition across regional scholars.
                            </p>
                        </div>
                    </div>
                    <div class="rounded-lg border border-slate-100 bg-slate-50 p-4">
                        <div class="mb-3 flex items-end justify-between">
                            <span class="text-xs text-slate-500">Total with profile sex</span>
                            <span class="text-2xl font-semibold text-slate-900">
                                {{ formatNumber(genderTotal) }}
                            </span>
                        </div>
                        <div class="flex h-8 overflow-hidden rounded bg-slate-200">
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
                                <div class="flex items-center gap-2 text-slate-600">
                                    <span :class="['h-2.5 w-2.5 rounded-full', item.dot]" />
                                    {{ item.label }}
                                </div>
                                <div class="font-semibold text-slate-900">
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
                    class="flex flex-col overflow-hidden rounded-lg border border-slate-200 bg-white p-5 shadow-sm md:col-span-2 xl:col-span-2"
                    style="height: 340px"
                >
                    <div class="mb-4 flex items-center gap-3">
                        <IconBuildingEstate :size="18" class="text-rose-600" />
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">
                                Scholar Count by School
                            </h2>
                            <p class="hidden text-[11px] text-slate-500 sm:block">
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
                                    <div class="truncate text-xs font-medium text-slate-700">
                                        {{ school.name }}
                                    </div>
                                    <div class="mt-1 h-2 rounded bg-slate-100">
                                        <div
                                            class="h-2 rounded bg-rose-500"
                                            :style="{ width: `${school.percent}%` }"
                                        />
                                    </div>
                                </div>
                                <div class="text-xs font-semibold text-slate-900">
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
import { usePage } from "@inertiajs/vue3";
import {
    IconBuildingEstate,
    IconChartDonut,
    IconClipboardList,
    IconDeviceDesktopAnalytics,
    IconFileCheck,
    IconSchool,
    IconUsersGroup,
} from "@tabler/icons-vue";
import { computed } from "vue";
import ApexChart from "vue3-apexcharts";

const page = usePage();
const { card, timeline, user, gender } = page.props;
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
    { label: "Grade Submissions", value: insights.value.pendingSubmissions?.grades ?? 0 },
    { label: "Academic History", value: insights.value.pendingSubmissions?.history ?? 0 },
    { label: "Profile Updates", value: insights.value.pendingSubmissions?.profile ?? 0 },
    { label: "Landbank Requests", value: insights.value.pendingSubmissions?.landbank ?? 0 },
]);

const apexOptionsTimeline = computed(() => ({
    chart: {
        type: "bar",
        stacked: true,
        toolbar: { show: false },
        fontFamily: "inherit",
        foreColor: "#64748b",
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
    stroke: { show: true, width: 1, colors: ["#fff"] },
    legend: { show: true, position: "bottom" },
    grid: {
        borderColor: "rgba(148,163,184,0.35)",
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
    tooltip: { theme: "dark" },
}));

const apexOptionsScholarshipTypes = computed(() => ({
    chart: {
        type: "donut",
        toolbar: { show: false },
        fontFamily: "inherit",
        foreColor: "#64748b",
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
    tooltip: { theme: "dark" },
}));

function statusClass(status) {
    const classes = {
        draft: "rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700",
        submitted_payroll: "rounded bg-blue-50 px-2 py-1 text-xs font-semibold text-blue-700",
        rejected_payroll: "rounded bg-red-50 px-2 py-1 text-xs font-semibold text-red-700",
        approved_payroll: "rounded bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700",
    };

    return classes[status] ?? "rounded bg-slate-100 px-2 py-1 text-xs font-semibold text-slate-700";
}

function formatNumber(num) {
    return Number(num ?? 0).toLocaleString("en-US");
}
</script>
