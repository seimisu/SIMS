<template>
    <div class="min-h-full bg-slate-50 px-3 py-3 text-slate-800">
        <div class="mx-auto flex max-w-[1500px] flex-col gap-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <div class="text-[11px] font-semibold uppercase text-slate-500">
                        Scholarship operations
                    </div>
                    <h1 class="text-lg font-semibold text-slate-900">
                        Scholarship Staff Dashboard
                    </h1>
                    <p class="text-xs text-slate-500">
                        Monitor scholar status, distributions, and review workload signals.
                    </p>
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
                <section class="flex flex-col rounded-lg border border-slate-200 bg-white p-5 shadow-sm md:col-span-2 xl:row-span-2">
                    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="rounded bg-blue-50 p-2 text-blue-600">
                                <IconAward :size="20" />
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-900">
                                    Scholarship Awards by Year
                                </h2>
                                <p class="text-[11px] text-slate-500">
                                    Annual awards grouped by scholarship program.
                                </p>
                            </div>
                        </div>
                        <select
                            v-if="rangeOptions.length"
                            v-model="filterRangeDate.value"
                            class="h-8 rounded border border-slate-200 bg-white px-2 text-xs font-medium text-slate-700 outline-none focus:border-blue-400"
                        >
                            <option
                                v-for="range in rangeOptions"
                                :key="rangeKey(range)"
                                :value="rangeKey(range)"
                            >
                                {{ rangeLabel(range) }}
                            </option>
                        </select>
                    </div>
                    <div class="min-h-0 flex-1 rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                        <ApexChart
                            type="bar"
                            height="420"
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
                                Program Distribution
                            </h2>
                            <p class="text-[11px] text-slate-500">
                                Current scholarship program mix.
                            </p>
                        </div>
                    </div>
                    <div class="flex flex-col gap-4">
                        <div class="flex justify-center">
                            <ApexChart
                                type="donut"
                                height="190"
                                :options="chartOptionsProgram"
                                :series="programSeries"
                            />
                        </div>
                        <div class="grid gap-2 text-xs">
                            <div
                                v-for="program in programDistribution"
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
                            <IconRainbow :size="20" />
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">
                                Sex Distribution
                            </h2>
                            <p class="text-[11px] text-slate-500">
                                National scholar profile composition.
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
                                    <span class="font-medium text-slate-400">{{ item.percent }}%</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm md:col-span-2 xl:col-span-3">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="rounded bg-purple-50 p-2 text-purple-600">
                            <IconRainbow :size="20" />
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900">
                                Sex Breakdown by Region
                            </h2>
                            <p class="text-[11px] text-slate-500">
                                Male and female counts compared per region.
                            </p>
                        </div>
                    </div>
                    <div class="rounded-lg border border-slate-100 bg-slate-50 px-4 py-3">
                        <ApexChart
                            type="bar"
                            height="450"
                            :options="chartOptionsSexByRegion"
                            :series="genderSeries"
                        />
                    </div>
                </section>

                <section class="flex flex-col overflow-hidden rounded-lg border border-slate-200 bg-white p-5 shadow-sm md:col-span-1 xl:col-span-1" style="height: 360px">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="flex items-center gap-3">
                            <div class="rounded bg-rose-50 p-2 text-rose-600">
                                <IconBuilding :size="20" />
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-900">
                                    Registered Scholars by Region
                                </h2>
                                <p class="text-[11px] text-slate-500">
                                    Regional scholar registration totals.
                                </p>
                            </div>
                        </div>
                    </div>
                    <div class="min-h-0 flex-1 overflow-auto pr-1">
                        <RankedDistribution
                            :items="registeredScholarsByRegion"
                            color="bg-rose-500"
                        />
                    </div>
                </section>

                <section class="flex flex-col overflow-hidden rounded-lg border border-slate-200 bg-white p-5 shadow-sm md:col-span-1 xl:col-span-2" style="height: 360px">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div class="flex items-center gap-3">
                            <div class="rounded bg-sky-50 p-2 text-sky-600">
                                <IconBook2 :size="20" />
                            </div>
                            <div>
                                <h2 class="text-sm font-semibold text-slate-900">
                                    Course Scholar Distribution
                                </h2>
                                <p class="text-[11px] text-slate-500">
                                    Ranked by scholar count.
                                </p>
                            </div>
                        </div>
                        <Button
                            label="Details"
                            size="small"
                            severity="secondary"
                            @click="toggleDialogs('courseTable')"
                        />
                    </div>
                    <div class="min-h-0 flex-1 overflow-auto pr-1">
                        <RankedDistribution
                            :items="courseDistribution"
                            color="bg-sky-500"
                        />
                    </div>
                </section>
            </div>
        </div>
    </div>

    <Dialog
        v-model:visible="dialogDetails.dialog"
        modal
        style="width: 70rem"
        class="m-2"
    >
        <template #header>
            <div class="flex items-center gap-2">
                <IconTable :size="20" />
                <div class="flex flex-col">
                    <div class="text-sm font-medium">
                        {{ dialogDetails.data === "schoolTable" ? "School Scholar Distribution" : "Course Scholar Distribution" }}
                    </div>
                    <div class="text-xs text-slate-500">
                        Detailed distribution table
                    </div>
                </div>
            </div>
        </template>
        <DataTable
            :value="dialogTableRows"
            stripedRows
            tableStyle="min-width: 50rem"
            class="p-datatable-sm"
        >
            <Column field="name">
                <template #header>
                    <div class="w-full text-left text-sm font-medium">
                        {{ dialogDetails.data === "schoolTable" ? "School Campus" : "Course" }}
                    </div>
                </template>
                <template #body="{ data }">
                    <div class="flex flex-col">
                        <span v-if="data.region" class="text-xs text-slate-500">
                            {{ data.region }}
                        </span>
                        <span class="text-sm font-medium text-slate-900">
                            {{ data.name }}
                        </span>
                    </div>
                </template>
            </Column>
            <Column field="percent" class="w-50">
                <template #header>
                    <div class="w-full text-left text-sm font-medium">
                        Distribution
                    </div>
                </template>
                <template #body="{ data }">
                    <div class="flex w-full items-center gap-2">
                        <ProgressBar
                            :value="data.percent"
                            :showValue="false"
                            class="h-2 flex-1"
                            :pt="{
                                root: 'h-1.5! rounded-full!',
                                value: 'bg-blue-600! rounded-full!',
                            }"
                        />
                        <span class="min-w-14 text-right text-sm text-blue-600">
                            {{ Number(data.percent ?? 0).toFixed(1) }}%
                        </span>
                    </div>
                </template>
            </Column>
            <Column field="total" class="w-30">
                <template #header>
                    <div class="w-full text-center text-sm font-medium">
                        Scholars
                    </div>
                </template>
                <template #body="{ data }">
                    <div class="w-full text-center font-semibold">
                        {{ formatNumber(data.total) }}
                    </div>
                </template>
            </Column>
        </DataTable>
    </Dialog>
</template>

<script setup>
import { usePage, router } from "@inertiajs/vue3";
import {
    IconAward,
    IconBook2,
    IconBuilding,
    IconChartDonut,
    IconFileCheck,
    IconSchool,
    IconTable,
    IconUser,
    IconRainbow,
} from "@tabler/icons-vue";
import { computed, defineComponent, h, ref, watch } from "vue";
import ApexChart from "vue3-apexcharts";

const page = usePage();
const card = computed(() => page.props.card ?? {});
const timeline = computed(() => page.props.timeline ?? {});
const rangeOptions = computed(() => page.props.options?.dateRange ?? []);

const dialogDetails = ref({
    dialog: false,
    data: "schoolTable",
});
const filterRangeDate = ref({
    value: rangeKey(rangeOptions.value.at(-1) ?? null),
});

const metrics = computed(() => [
    {
        label: "Active Scholars",
        value: card.value.active,
        caption: "Currently active scholars",
        icon: IconUser,
        color: "bg-blue-50 text-blue-600",
    },
    {
        label: "Undergraduate",
        value: card.value.undergraduate,
        caption: "Undergraduate scholarship records",
        icon: IconSchool,
        color: "bg-emerald-50 text-emerald-600",
    },
    {
        label: "JLSS",
        value: card.value.jlss,
        caption: "JLSS scholarship records",
        icon: IconFileCheck,
        color: "bg-cyan-50 text-cyan-600",
    },
    {
        label: "Graduated",
        value: card.value.graduated,
        caption: "Completed scholarship records",
        icon: IconAward,
        color: "bg-violet-50 text-violet-600",
    },
]);

const programDots = ["bg-blue-500", "bg-emerald-500", "bg-rose-500", "bg-amber-500", "bg-cyan-500"];
const programColors = ["#2563EB", "#10B981", "#E11D48", "#F59E0B", "#06B6D4"];
const programDistribution = computed(() => (timeline.value.programs ?? []).map((program, index) => ({
    name: program.name,
    count: Number(program.data ?? 0),
    dot: programDots[index % programDots.length],
})));
const programSeries = computed(() => programDistribution.value.map((program) => program.count));

const genderSeries = computed(() => page.props.gender?.series ?? []);
const genderTotals = computed(() => {
    const female = Number(page.props.gender?.bar?.series?.[0]?.data?.[0] ?? 0);
    const male = Number(page.props.gender?.bar?.series?.[0]?.data?.[1] ?? 0);

    return { female, male };
});
const genderTotal = computed(() => genderTotals.value.female + genderTotals.value.male);
const genderDetails = computed(() => {
    const total = Math.max(genderTotal.value, 1);

    return [
        {
            label: "Female",
            value: genderTotals.value.female,
            percent: Math.round((genderTotals.value.female / total) * 100),
            dot: "bg-pink-500",
        },
        {
            label: "Male",
            value: genderTotals.value.male,
            percent: Math.round((genderTotals.value.male / total) * 100),
            dot: "bg-blue-500",
        },
    ];
});
const sexSegments = computed(() => genderDetails.value.map((item) => ({
    label: item.label,
    percent: genderTotal.value === 0 ? 0 : item.percent,
    color: item.label === "Female" ? "bg-pink-500" : "bg-blue-500",
})));

const courseDistribution = computed(() => rankedItems(page.props.course?.series?.[0]?.data ?? []));
const registeredScholarsByRegion = computed(() => {
    const regionTotals = new Map();

    genderSeries.value.forEach((series) => {
        (series.data ?? []).forEach((point) => {
            regionTotals.set(point.x, (regionTotals.get(point.x) ?? 0) + Number(point.y ?? 0));
        });
    });

    return rankedItems([...regionTotals.entries()].map(([x, y]) => ({ x, y })));
});
const dialogTableRows = computed(() => (
    dialogDetails.value.data === "schoolTable"
        ? page.props.school?.table ?? []
        : page.props.course?.table ?? []
));

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
    colors: programColors,
    dataLabels: { enabled: false },
    stroke: { show: true, width: 1, colors: ["#fff"] },
    legend: { show: true, position: "bottom" },
    grid: {
        borderColor: "rgba(148,163,184,0.35)",
        strokeDashArray: 3,
    },
    xaxis: {
        categories: timeline.value.categories ?? [],
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    tooltip: { theme: "dark" },
}));

const chartOptionsProgram = computed(() => ({
    chart: {
        type: "donut",
        toolbar: { show: false },
        fontFamily: "inherit",
        foreColor: "#64748b",
    },
    labels: programDistribution.value.map((program) => program.name),
    colors: programColors,
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
                        formatter: () => formatNumber(programSeries.value.reduce((sum, value) => sum + value, 0)),
                    },
                },
            },
        },
    },
    tooltip: { theme: "dark" },
}));

const chartOptionsSexByRegion = computed(() => ({
    chart: {
        type: "bar",
        stacked: false,
        toolbar: { show: false },
        fontFamily: "inherit",
        foreColor: "#64748b",
    },
    plotOptions: {
        bar: {
            horizontal: true,
            borderRadius: 3,
            barHeight: "55%",
        },
    },
    colors: ["#EC4899", "#3B82F6"],
    dataLabels: { enabled: false },
    legend: { show: true, position: "bottom" },
    grid: {
        borderColor: "rgba(148,163,184,0.35)",
        strokeDashArray: 3,
    },
    xaxis: {
        labels: {
            formatter: (value) => formatNumber(value),
        },
    },
    tooltip: {
        theme: "dark",
        y: {
            formatter: (value) => `${formatNumber(value)} scholars`,
        },
    },
}));

const RankedDistribution = defineComponent({
    props: {
        items: {
            type: Array,
            default: () => [],
        },
        color: {
            type: String,
            default: "bg-blue-500",
        },
    },
    setup(props) {
        return () => h("div", { class: "grid gap-3" }, props.items.map((item) => h("div", {
            key: item.name,
            class: "grid grid-cols-[1fr_auto] items-center gap-3",
        }, [
            h("div", { class: "min-w-0" }, [
                h("div", { class: "truncate text-xs font-medium text-slate-700" }, item.name),
                h("div", { class: "mt-1 h-2 rounded bg-slate-100" }, [
                    h("div", {
                        class: `h-2 rounded ${props.color}`,
                        style: { width: `${item.percent}%` },
                    }),
                ]),
            ]),
            h("div", { class: "text-xs font-semibold text-slate-900" }, formatNumber(item.count)),
        ])));
    },
});

function rankedItems(rows) {
    const mapped = rows
        .map((row) => ({
            name: row.x,
            count: Number(row.y ?? 0),
        }))
        .sort((a, b) => b.count - a.count);
    const max = Math.max(...mapped.map((item) => item.count), 1);

    return mapped.map((item) => ({
        ...item,
        percent: item.count === 0 ? 0 : Math.max(4, Math.round((item.count / max) * 100)),
    }));
}

function toggleDialogs(tab) {
    dialogDetails.value.dialog = true;
    dialogDetails.value.data = tab;
}

function rangeKey(range) {
    return range && typeof range === "object" ? JSON.stringify(range) : range;
}

function rangeLabel(range) {
    return typeof range === "object" ? range.name ?? `${range.start}-${range.end}` : range;
}

function selectedRange(value) {
    if (!value) return null;

    try {
        return JSON.parse(value);
    } catch {
        return value;
    }
}

function formatNumber(num) {
    return Number(num ?? 0).toLocaleString("en-US");
}

watch(
    () => filterRangeDate.value.value,
    (value) => {
        router.get(route("dashboard"), { range: selectedRange(value) }, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
        });
    },
);

</script>
