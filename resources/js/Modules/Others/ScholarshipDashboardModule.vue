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
                                ● Regional Staff
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
                                class="!rounded-xl border border-green-500 shadow shadow-green-300 !text-green-600 !bg-green-100"
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
                <Card class="flex-1 w-full !h-full">
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
                                class="!rounded-xl border border-blue-500 shadow shadow-blue-300 !text-blue-600 !bg-blue-100"
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
                                class="!rounded-xl border border-yellow-500 shadow shadow-yellow-300 !text-yellow-600 !bg-yellow-100"
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
                                class="!rounded-xl border border-red-500 shadow shadow-red-300 !text-red-600 !bg-red-100"
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
            <div class="flex flex-col lg:flex-row w-full items-start gap-4">
                <Card
                    class="flex-1 w-full"
                    :pt="{
                        body: '!p-0',
                        content: 'border-t border-gray-200 ',
                    }"
                >
                    <template #title>
                        <div class="flex items-center gap-3 pt-4 px-4">
                            <Avatar
                                class="!bg-indigo-100 !text-indigo-600 !rounded-lg shadow"
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
                    </template>
                    <template #content>
                        <div class="flex py-4 w-full">
                            <div class="flex-3">
                                <ApexChart
                                    type="bar"
                                    class=""
                                    :options="apexOptionsTimeline"
                                    :series="page.props?.timeline.series"
                                />
                            </div>
                            <Divider layout="vertical" />
                            <div class="flex-1 flex flex-col gap-3">
                                <div class="text-sm font-semibold">
                                    Statistics
                                </div>
                                <div
                                    class="flex-1 justify-center flex flex-col gap-5"
                                >
                                    <div class="flex items-center gap-3">
                                        <Avatar
                                            class="!h-3 !w-3"
                                            shape="circle"
                                        />
                                        <div class="flex flex-col">
                                            <div
                                                class="font-medium text-sm text-gray-500"
                                            >
                                                MERIT
                                            </div>
                                            <div class="text-xl">
                                                {{
                                                    page.props.timeline
                                                        ?.programs[0]?.data ?? 0
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <Avatar
                                            class="!h-3 !w-3"
                                            shape="circle"
                                        />
                                        <div class="flex flex-col">
                                            <div
                                                class="font-medium text-sm text-gray-500"
                                            >
                                                RA 7687
                                            </div>
                                            <div class="text-xl">
                                                {{
                                                    page.props.timeline
                                                        ?.programs[1]?.data ?? 0
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                    <div class="flex items-center gap-3">
                                        <Avatar
                                            class="!h-3 !w-3"
                                            shape="circle"
                                        />
                                        <div class="flex flex-col">
                                            <div
                                                class="font-medium text-sm text-gray-500"
                                            >
                                                RA 10612
                                            </div>
                                            <div class="text-xl">
                                                {{
                                                    page.props.timeline
                                                        ?.programs[2]?.data ?? 0
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
                <Card class="flex-1 w-full">
                    <template #title>
                        <div class="text-sm">
                            Scholarship Program Awarded Through the Years
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
const page = usePage();

const FilterDate = ref({
    value: "all",
    options: [
        { label: "All Time", value: "all" },
        { label: "This Year", value: "year" },
        { label: "This Month", value: "month" },
    ],
});

const loading = ref({
    count: false,
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
