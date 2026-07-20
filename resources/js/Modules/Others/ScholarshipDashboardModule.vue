<template>
    <div class="flex flex-col w-full gap-5">
        <div class="flex-1 flex flex-col gap-5">
            <div class="flex justify-between items-center">
                <div class="flex flex-col">
                    <div
                        class="text-sm capitalize text-gray-500 flex items-center gap-1"
                    >
                        <div class="capitalize">Analytics</div>
                        <div
                            class="text-xs bg-green-50 text-green-600 py-1 px-4 rounded-2xl"
                        >
                            <p class="font-medium animate-pulse">
                                ● Scholarship Staff
                            </p>
                        </div>
                    </div>
                    <div class="text-4xl uppercase font-semibold">
                        Dashboard
                    </div>
                    <div class="text-sm text-gray-500">
                        Monitor scholar requests, regional statistics, and
                        manage daily operations.
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <SelectButton
                    size="small"
                    v-model="FilterDate.value"
                    :options="FilterDate.options"
                    optionLabel="label"
                    optionValue="value"
                    :allow-empty="false"
                    aria-labelledby="basic"
                />
            </div>
        </div>
        <div class="flex flex-col gap-4">
            <div class="flex flex-col lg:flex-row items-start w-full gap-4">
                <Card class="flex-1 w-full h-full">
                    <template #content>
                        <div class="flex justify-between">
                            <div class="flex flex-col">
                                <div class="text-sm">Graduated Scholars</div>
                                <div class="text-4xl font-semibold">
                                    <CountTo
                                        :start-val="0"
                                        :end-val="
                                            page.props.card?.graduated ?? 0
                                        "
                                        v-if="!loading.count"
                                        :duration="1000"
                                        class="text-4xl font-semibold"
                                    />
                                    <Skeleton
                                        width="5rem"
                                        height="2.5rem"
                                        v-else
                                    />
                                </div>
                            </div>
                            <Avatar
                                class="rounded-xl! border border-green-500 shadow shadow-green-300 text-green-600! bg-green-100!"
                                size="large"
                            >
                                <IconSchool size="25" />
                            </Avatar>
                        </div>
                    </template>
                    <template #footer>
                        <div class="text-sm text-green-600 font-medium">
                            ↑ 8.2% from last {{ FilterDate.value }}
                        </div>
                    </template>
                </Card>
                <Card class="flex-1 w-full h-full">
                    <template #content>
                        <div class="flex justify-between">
                            <div class="flex flex-col">
                                <div class="text-sm">Active Scholars</div>
                                <div class="text-4xl font-semibold">
                                    <CountTo
                                        :start-val="0"
                                        :end-val="page.props.card?.active ?? 0"
                                        v-if="!loading.count"
                                        :duration="1000"
                                        class="text-4xl font-semibold"
                                    />
                                    <Skeleton
                                        width="5rem"
                                        height="2.5rem"
                                        v-else
                                    />
                                </div>
                            </div>
                            <Avatar
                                class="rounded-xl! border! border-blue-500 shadow shadow-blue-300 text-blue-600! bg-blue-100!"
                                size="large"
                            >
                                <IconUser size="25" />
                            </Avatar>
                        </div>
                    </template>
                    <template #footer>
                        <div class="text-sm text-green-600 font-medium">
                            ↑ 8.2% from last {{ FilterDate.value }}
                        </div>
                    </template>
                </Card>
                <Card class="flex-1 w-full h-full">
                    <template #content>
                        <div class="flex justify-between">
                            <div class="flex flex-col">
                                <div class="text-sm">
                                    Scholars with Compliance Issues
                                </div>
                                <div class="text-4xl font-semibold">
                                    <CountTo
                                        :start-val="0"
                                        :end-val="page.props.card?.issue ?? 0"
                                        v-if="!loading.count"
                                        :duration="1000"
                                        class="text-4xl font-semibold"
                                    />
                                    <Skeleton
                                        width="5rem"
                                        height="2.5rem"
                                        v-else
                                    />
                                </div>
                            </div>
                            <Avatar
                                class="rounded-xl! border border-yellow-500 shadow shadow-yellow-300 text-yellow-600! bg-yellow-100!"
                                size="large"
                            >
                                <IconUserExclamation size="25" />
                            </Avatar>
                        </div>
                    </template>
                    <template #footer>
                        <div class="text-sm text-green-600 font-medium">
                            ↑ 8.2% from last {{ FilterDate.value }}
                        </div>
                    </template>
                </Card>
                <Card class="flex-1 w-full h-full">
                    <template #content>
                        <div class="flex justify-between">
                            <div class="flex flex-col">
                                <div class="text-sm">Terminated Scholars</div>
                                <div class="text-4xl font-semibold">
                                    <CountTo
                                        :start-val="0"
                                        :end-val="page.props.card?.issue ?? 0"
                                        v-if="!loading.count"
                                        :duration="1000"
                                        class="text-4xl font-semibold"
                                    />
                                    <Skeleton
                                        width="5rem"
                                        height="2.5rem"
                                        v-else
                                    />
                                </div>
                            </div>
                            <Avatar
                                class="rounded-xl! border border-red-500 shadow shadow-red-300 text-red-600! bg-red-100!"
                                size="large"
                            >
                                <IconUserX size="25" />
                            </Avatar>
                        </div>
                    </template>
                    <template #footer>
                        <div class="text-sm text-green-600 font-medium">
                            ↑ 8.2% from last {{ FilterDate.value }}
                        </div>
                    </template>
                </Card>
            </div>
            <div
                class="flex flex-col lg:flex-row w-full items-start gap-4 lg:h-110"
            >
                <Card
                    class="flex-1 w-full h-full"
                    :pt="{
                        body: '!p-0',
                        content: 'border-t border-gray-200 ',
                    }"
                >
                    <template #title>
                        <div class="flex justify-between">
                            <div
                                class="flex flex-1 items-center gap-3 pt-4 px-4"
                            >
                                <Avatar
                                    class="bg-indigo-100! text-indigo-600! rounded-lg! shadow"
                                >
                                    <IconAward :size="20" :stroke="2" />
                                </Avatar>

                                <div>
                                    <div class="text-sm font-semibold">
                                        Scholarship Awards by Year
                                    </div>
                                    <div
                                        class="text-xs text-surface-500 font-normal"
                                    >
                                        Annual scholarship awards
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center px-4 pt-3">
                                <SelectInput
                                    :disable="
                                        page.props?.options?.dateRange != null
                                            ? false
                                            : true
                                    "
                                    v-model="FilterRangeDate.value"
                                    :options="page.props?.options?.dateRange"
                                ></SelectInput>
                            </div>
                        </div>
                    </template>
                    <template #content>
                        <div class="flex flex-col lg:flex-row py-4 w-full">
                            <div class="flex-3 flex flex-col">
                                <div>
                                    <ApexChart
                                        type="bar"
                                        class=""
                                        :options="apexOptionsTimeline"
                                        :series="page.props?.timeline.series"
                                    />
                                </div>

                                <div class="flex justify-evenly">
                                    <div class="flex items-center gap-3">
                                        <Avatar
                                            class="h-2.5! w-2.5! bg-blue-500!"
                                            shape="circle"
                                        />
                                        <div class="flex flex-col">
                                            <div
                                                class="font-medium text-xs text-gray-500"
                                            >
                                                MERIT
                                            </div>
                                            <div class="text-lg">
                                                {{
                                                    page.props.timeline
                                                        ?.programs[0]?.data ?? 0
                                                }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex items-center gap-3">
                                        <Avatar
                                            class="h-2.5! w-2.5! bg-green-500!"
                                            shape="circle"
                                        />
                                        <div class="flex flex-col">
                                            <div
                                                class="font-medium text-xs text-gray-500"
                                            >
                                                RA 7687
                                            </div>
                                            <div class="text-lg">
                                                {{
                                                    page.props.timeline
                                                        ?.programs[1]?.data ?? 0
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <Avatar
                                            class="h-2.5! w-2.5! bg-red-500!"
                                            shape="circle"
                                        />
                                        <div class="flex flex-col">
                                            <div
                                                class="font-medium text-xs text-gray-500"
                                            >
                                                RA 10612
                                            </div>
                                            <div class="text-lg">
                                                {{
                                                    page.props.timeline
                                                        ?.programs[2]?.data ?? 0
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <Divider
                                layout="vertical"
                                class="hidden lg:flex m-0!"
                            />
                            <div class="flex-1 flex flex-col gap-3 p-3">
                                <div class="text-sm font-semibold">
                                    Program Distribution
                                </div>
                                <div
                                    class="flex-1 justify-center flex flex-col gap-4"
                                >
                                    <div class="flex justify-center">
                                        <ApexChart
                                            type="donut"
                                            :options="chartOptionsProgram"
                                            :series="
                                                page.props?.timeline
                                                    ?.programSeries
                                            "
                                            class="w-70"
                                        />
                                    </div>
                                </div>
                                <p class="text-xs text-center text-surface-500">
                                    Distribution of scholars across each
                                    scholarship program.
                                </p>
                            </div>
                        </div>
                    </template>
                </Card>
                <Card
                    class="flex-1 w-full h-full"
                    :pt="{
                        body: '!p-0',
                        content: 'border-t border-gray-200 ',
                    }"
                >
                    <template #title>
                        <div class="flex justify-between">
                            <div
                                class="flex flex-1 items-center gap-3 pt-4 px-4"
                            >
                                <Avatar
                                    class="bg-indigo-100! text-indigo-600! rounded-lg! shadow"
                                >
                                    <IconAward :size="20" :stroke="2" />
                                </Avatar>

                                <div>
                                    <div class="text-sm font-semibold">
                                        Active Scholar Timeline
                                    </div>
                                    <div class="text-xs text-surface-500">
                                        Displays scholarship awards over time.
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center px-4 pt-3"></div>
                        </div>
                    </template>
                    <template #content>
                        <div class="flex flex-col lg:flex-row py-4 w-full">
                            <div class="flex-3 flex flex-col">
                                <ApexChart
                                    type="rangeBar"
                                    height="350"
                                    :options="chartOptions"
                                    :series="series"
                                />
                            </div>
                            <Divider
                                layout="vertical"
                                class="hidden lg:flex m-0!"
                            />
                            <div class="flex-1 flex flex-col gap-3 p-3">
                                <div class="text-sm font-semibold">
                                    Program Distribution
                                </div>
                                <div
                                    class="flex-1 justify-center flex flex-col gap-4"
                                >
                                    <div class="flex justify-center">
                                        <!-- <ApexChart
                                            type="donut"
                                            :options="chartOptionsProgram"
                                            :series="
                                                page.props?.timeline
                                                    ?.programSeries
                                            "
                                            class="w-70"
                                        /> -->
                                    </div>
                                </div>
                                <p class="text-xs text-center text-surface-500">
                                    Distribution of scholars across each
                                    scholarship program.
                                </p>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </div>
</template>
<script setup>
import {
    IconAward,
    IconSchool,
    IconUser,
    IconUserExclamation,
    IconUserX,
} from "@tabler/icons-vue";
import { computed, onMounted, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { CountTo } from "vue3-count-to";
import ApexChart from "vue3-apexcharts";
import SelectInput from "../../Components/inputs/SelectInput.vue";
const page = usePage();

const FilterDate = ref({
    value: "all",
    options: [
        { label: "All Time", value: "all" },
        { label: "This Year", value: "year" },
        { label: "This Month", value: "month" },
    ],
});

const FilterRangeDate = ref({
    value: page.props?.options?.dateRange?.at(-1) ?? null,
});

const loading = ref({
    count: false,
});

const series = ref([
    {
        data: [
            {
                x: "Operations",
                y: [8000, 4500],
            },
            {
                x: "Customer Success",
                y: [3200, 4100],
            },
            {
                x: "Engineering",
                y: [2950, 7800],
            },
            {
                x: "Marketing",
                y: [3000, 4600],
            },
            {
                x: "Product",
                y: [3500, 4100],
            },
            {
                x: "Data Science",
                y: [4500, 6500],
            },
            {
                x: "Sales",
                y: [4100, 5600],
            },
        ],
    },
]);

const chartOptions = ref({
    chart: {
        type: "rangeBar",
        height: 350,
        zoom: {
            enabled: false,
        },
        toolbar: {
            show: false,
        },
    },

    colors: ["#EC7D31", "#36BDCB"],

    plotOptions: {
        bar: {
            horizontal: true,
            isDumbbell: true,
            dumbbellColors: [["#EC7D31", "#36BDCB"]],
        },
    },

    legend: {
        show: true,
        showForSingleSeries: true,
        position: "top",
        horizontalAlign: "left",
        customLegendItems: ["Female", "Male"],
    },

    fill: {
        type: "gradient",
        gradient: {
            gradientToColors: ["#36BDCB"],
            inverseColors: false,
            stops: [0, 100],
        },
    },

    grid: {
        xaxis: {
            lines: {
                show: true,
            },
        },
        yaxis: {
            lines: {
                show: false,
            },
        },
    },
});

const chartOptionsProgram = ref({
    chart: {
        type: "donut",
        toolbar: {
            show: false,
        },
        animations: {
            enabled: true,
            easing: "easeinout",
            speed: 700,
        },
    },

    labels: ["MERIT", "RA 7687", "RA 10612"],

    legend: {
        show: false,
    },
    colors: ["#3B82F6", "#22C55E", "#EF4444"],

    stroke: {
        show: true,
        width: 5,
        colors: ["#FFFFFF"],
    },

    dataLabels: {
        enabled: false,
    },
    plotOptions: {
        pie: {
            expandOnClick: false,

            donut: {
                size: "75%",

                labels: {
                    show: true,

                    name: {
                        show: true,
                        fontSize: "16px",
                    },

                    value: {
                        show: true,
                        fontSize: "24px",
                        fontWeight: 700,
                    },

                    total: {
                        show: true,
                        label: "Scholars",
                        formatter: (w) =>
                            w.globals.seriesTotals
                                .reduce((a, b) => a + b, 0)
                                .toLocaleString(),
                    },
                },
            },
        },
    },
});

const apexOptionsTimeline = computed(() => ({
    chart: {
        type: "bar",
        stacked: true,
        height: 500,
        toolbar: { show: false },
        fontFamily: "inherit",
        foreColor: "#adb0bb",
    },
    plotOptions: {
        bar: {
            horizontal: false,
            borderRadius: 5,
            columnWidth: "20%",
        },
    },
    colors: ["#2563EB", "#22C55E", "#F43F5E"],
    dataLabels: { enabled: true },
    stroke: {
        show: true,
        width: 1,
        colors: ["#fff"],
    },
    legend: { show: false },
    grid: {
        borderColor: "rgba(0,0,0,0.1)",
        strokeDashArray: 3,
        xaxis: { lines: { show: false } },
        padding: { top: 0, right: 0, bottom: 0, left: 0 },
    },
    xaxis: {
        categories: page.props?.timeline.categories,
        axisBorder: { show: false },
        axisTicks: { show: false },
    },
    yaxis: {
        tickAmount: 5,
    },
    tooltip: { theme: "dark" },
    responsive: [
        {
            breakpoint: 2600,
            options: {
                chart: { width: "100%", height: 250 },
                legend: { position: "bottom" },
            },
        },
        {
            breakpoint: 1200,
            options: {
                chart: { width: "100%" },
                legend: { position: "bottom" },
            },
        },
        {
            breakpoint: 992,
            options: {
                chart: { width: "100%" },
                legend: { position: "bottom", fontSize: "12px" },
            },
        },
        {
            breakpoint: 768,
            options: {
                chart: { width: "100%" },
                legend: { show: false },
            },
        },
        {
            breakpoint: 480,
            options: {
                chart: { width: "100%", height: 250 },
                dataLabels: { enabled: false },
            },
        },
    ],
}));

watch(
    () => FilterRangeDate.value.value,
    (value) => {
        router.get(
            route("dashboard"),
            { range: value },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    },
);

watch(
    () => FilterDate.value.value,
    (value) => {
        router.get(
            route("dashboard"),
            { filter: value },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                onBefore: () => {
                    loading.value.count = true;
                },
                onFinish: () => {
                    loading.value.count = false;
                },
            },
        );
    },
);
</script>
