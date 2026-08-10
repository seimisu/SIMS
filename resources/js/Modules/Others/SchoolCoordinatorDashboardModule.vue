<template>
    <div class="flex flex-col w-full gap-5">
        <div class="flex-1 flex flex-col gap-5">
            <div class="flex justify-between items-center">
                <div class="flex flex-col">
                    <div class="text-sm capitalize text-gray-500 flex items-center gap-1">
                        <div class="capitalize">Dashboard</div>
                        <div class="text-xs bg-green-50 text-green-600 py-1 px-4 rounded-2xl">
                            <p class="font-medium animate-pulse">
                                ● School Coordinator
                            </p>
                        </div>
                    </div>
                    <div class="w-full flex items-center justify-between">
                        <div class="flex items-center gap-2">
                            <Avatar size="large" class="bg-blue-200! rounded-xl!">
                                <IconSchool size="30" />
                            </Avatar>
                            <div class="">
                                <h1 class="text-xl font-semibold">
                                    {{
                                        page.props.schoolDetails?.generated_name
                                    }}
                                </h1>
                                <div class="text-sm text-gray-600 flex items-center gap-1">
                                    <IconMapPin size="16" />
                                    <span>
                                        {{
                                            page.props.schoolDetails?.address
                                                ?.full_address?.name
                                        }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex items-center justify-between">
                <SelectButton size="small" v-model="FilterDate.value" :options="FilterDate.options" optionLabel="label"
                    optionValue="value" :allow-empty="false" aria-labelledby="basic" />
            </div>
        </div>
        <div class="grid grid-cols-1 lg:grid-cols-4 gap-3">
            <Card class="dark:bg-slate-700! dark:text-white!">
                <template #content>
                    <div class="flex justify-between">
                        <div class="flex flex-col">
                            <div class="text-sm">Graduated Scholars</div>
                            <div class="text-4xl font-semibold">
                                <CountTo :start-val="0" :end-val="page.props.card?.graduated ?? 0" v-if="!loading.count"
                                    :duration="1000" class="text-4xl font-semibold" />
                                <Skeleton width="5rem" height="2.5rem" v-else />
                            </div>
                        </div>
                        <Avatar
                            class="rounded-xl! border border-green-500 shadow shadow-green-300 text-green-600! bg-green-100!"
                            size="large">
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
            <Card class="dark:bg-slate-700! dark:text-white!">
                <template #content>
                    <div class="flex justify-between">
                        <div class="flex flex-col">
                            <div class="text-sm">Active Scholars</div>
                            <div class="text-4xl font-semibold">
                                <CountTo :start-val="0" :end-val="page.props.card?.active ?? 0" v-if="!loading.count"
                                    :duration="1000" class="text-4xl font-semibold" />
                                <Skeleton width="5rem" height="2.5rem" v-else />
                            </div>
                        </div>
                        <Avatar
                            class="rounded-xl! border! border-blue-500 shadow shadow-blue-300 text-blue-600! bg-blue-100!"
                            size="large">
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
            <Card class="dark:bg-slate-700! dark:text-white!">
                <template #content>
                    <div class="flex justify-between">
                        <div class="flex flex-col">
                            <div class="text-sm">
                                Scholars with Compliance Issues
                            </div>
                            <div class="text-4xl font-semibold">
                                <CountTo :start-val="0" :end-val="page.props.card?.issue ?? 0" v-if="!loading.count"
                                    :duration="1000" class="text-4xl font-semibold" />
                                <Skeleton width="5rem" height="2.5rem" v-else />
                            </div>
                        </div>
                        <Avatar
                            class="rounded-xl! border border-yellow-500 shadow shadow-yellow-300 text-yellow-600! bg-yellow-100!"
                            size="large">
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
            <Card class="dark:bg-slate-700! dark:text-white!">
                <template #content>
                    <div class="flex justify-between">
                        <div class="flex flex-col">
                            <div class="text-sm">Terminated Scholars</div>
                            <div class="text-4xl font-semibold">
                                <CountTo :start-val="0" :end-val="page.props.card?.terminated ?? 0"
                                    v-if="!loading.count" :duration="1000" class="text-4xl font-semibold" />
                                <Skeleton width="5rem" height="2.5rem" v-else />
                            </div>
                        </div>
                        <Avatar
                            class="rounded-xl! border border-red-500 shadow shadow-red-300 text-red-600! bg-red-100!"
                            size="large">
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
            <Card class="col-span-2 dark:bg-slate-700! dark:text-white!" :pt="{
                body: '!p-0',
                content: 'border-t border-gray-200 ',
            }">
                <template #title>
                    <div class="flex justify-between">
                        <div class="flex flex-1 items-center gap-3 pt-4 px-4">
                            <Avatar class="bg-indigo-100! text-indigo-600! rounded-lg! shadow">
                                <IconAward :size="20" :stroke="2" />
                            </Avatar>
                            <div>
                                <div class="text-sm font-semibold">
                                    Scholarship Awards by Year
                                </div>
                                <div class="text-xs text-surface-500 font-normal">
                                    Annual scholarship awards
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center px-4 pt-3">
                            <SelectInput :disable="page.props?.options?.dateRange != null
                                ? false
                                : true
                                " v-model="FilterRangeDate.value" :options="page.props?.options?.dateRange">
                            </SelectInput>
                        </div>
                    </div>
                </template>
                <template #content>
                    <div class="flex flex-col lg:flex-row py-4 w-full">
                        <div class="flex-3 flex flex-col">
                            <div>
                                <ApexChart type="bar" class="" :options="apexOptionsTimeline"
                                    :series="page.props?.timeline.series" />
                            </div>

                            <div class="flex justify-evenly">
                                <div class="flex items-center gap-3">
                                    <Avatar class="h-2.5! w-2.5! bg-blue-500!" shape="circle" />
                                    <div class="flex flex-col">
                                        <div class="font-medium text-sm text-gray-500">
                                            MERIT
                                        </div>
                                        <div class="text-lg">
                                            {{
                                                page.props.timeline?.programs[0]
                                                    ?.data ?? 0
                                            }}
                                        </div>
                                    </div>
                                </div>

                                <div class="flex items-center gap-3">
                                    <Avatar class="h-2.5! w-2.5! bg-green-500!" shape="circle" />
                                    <div class="flex flex-col">
                                        <div class="font-medium text-sm text-gray-500">
                                            RA 7687
                                        </div>
                                        <div class="text-lg">
                                            {{
                                                page.props.timeline?.programs[1]
                                                    ?.data ?? 0
                                            }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-3">
                                    <Avatar class="h-2.5! w-2.5! bg-red-500!" shape="circle" />
                                    <div class="flex flex-col">
                                        <div class="font-medium text-sm text-gray-500">
                                            RA 10612
                                        </div>
                                        <div class="text-lg">
                                            {{
                                                page.props.timeline?.programs[2]
                                                    ?.data ?? 0
                                            }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <Divider layout="vertical" class="hidden lg:flex m-0!" />
                        <div class="flex-1 flex flex-col gap-3 p-3">
                            <div class="text-sm font-semibold">
                                Program Distribution
                            </div>
                            <div class="flex-1 justify-center flex flex-col gap-4">
                                <div class="flex justify-center">
                                    <ApexChart type="donut" :options="chartOptionsProgram" :series="page.props?.timeline?.programSeries
                                        " class="w-50" />
                                </div>
                            </div>
                            <p class="text-xs text-center text-surface-500">
                                Distribution of scholars across each scholarship
                                program.
                            </p>
                        </div>
                    </div>
                </template>
            </Card>
            <Card class="col-span-2 lg:col-span-1 dark:bg-slate-700! dark:text-white!" :pt="{
                body: '!p-0',
                content: 'border-t border-gray-200 ',
            }">
                <template #title>
                    <div class="flex justify-between">
                        <div class="flex flex-1 items-center gap-3 pt-4 px-4">
                            <Avatar class="bg-purple-100! text-purple-600! rounded-lg! shadow">
                                <IconRainbow :size="20" :stroke="2" />
                            </Avatar>
                            <div>
                                <div class="text-sm font-semibold">
                                    Sex Chart
                                </div>

                                <div class="text-xs text-surface-500">
                                    Distribution of scholars by
                                    sex.
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center px-4 pt-3"></div>
                    </div>
                </template>
                <template #content>
                    <div class="flex flex-col items-center justify-evenly w-full  p-4">
                        <div class="flex justify-evenly h-full w-full gap-5 ">
                            <div class="flex-1 lg:w-40 ">
                                <ApexChart type="donut" :options="chartSexOptions" :series="page.props?.gender?.donut"
                                    class="w-50" />
                            </div>
                            <div class="flex-1 lg:w-60 flex flex-col justify-evenly">
                                <div class="flex items-center gap-2">
                                    <IconManFilled size="30" class="text-blue-400" />
                                    <div class="flex flex-col">
                                        <div class="text-xs text-gray-500 font-medium leading-3.5">
                                            Male
                                        </div>
                                        <div class="text-lg font-semibold leading-3.5">
                                            {{
                                                page.props?.gender?.pMale ??
                                                0
                                            }}%
                                        </div>
                                    </div>
                                </div>
                                <div class="flex items-center gap-2">
                                    <IconWomanFilled size="30" class="text-pink-400" />
                                    <div class="flex flex-col">
                                        <div class="text-xs text-gray-500 font-medium leading-3.5">
                                            Female
                                        </div>
                                        <div class="text-lg font-semibold leading-3.5">
                                            {{
                                                page.props?.gender
                                                    ?.pFemale ?? 0
                                            }}%
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <Divider class="my-2" />
                        <div class="flex items-start gap-2 rounded-lg bg-amber-50 border border-amber-200 p-3 text-sm">
                            <IconBulb size="18" class="mt-0.5 text-amber-600 shrink-0" />

                            <p class="text-amber-800 leading-5">
                                <span class="font-medium">Insight:</span>
                                The current scholar population is
                                predominantly

                                <span :class="[
                                    page.props?.gender?.majority ===
                                        'Male'
                                        ? 'text-blue-600'
                                        : 'text-pink-600',
                                    'font-semibold',
                                ]">
                                    {{ page.props?.gender?.majority }}
                                </span>

                                scholars.
                            </p>
                        </div>
                    </div>

                </template>
            </Card>
            <Card class="col-span-2 lg:col-span-1 dark:bg-slate-700! dark:text-white" :pt="{
                body: '!p-0',
                content: 'border-t border-gray-200 ',
            }">
                <template #title>
                    <VCalendar transparent borderless class="py-1" expanded view="weekly" :attributes="attrs"
                        title-position="left"></VCalendar>
                </template>
                <template #content>
                    <div class="p-2 flex flex-col gap-2">
                        <div class="flex items-center justify-between">
                            <div class="text-[16px] font-semibold">Events</div>
                            <Avatar size="normal">
                                <IconBellFilled size="16" />
                            </Avatar>
                        </div>
                        <div class="flex-1 flex flex-col gap-2 overflow-y-auto max-h-72">
                            <div
                                class="bg-green-50 text-green-600 p-2 border border-green-200 dark:border-green-600 rounded-lg flex flex-col gap-2">
                                <div class="flex gap-2">
                                    <IconCalendarWeek size="16" />
                                    <div class="text-xs">Active {{ page.props.semesterDate?.name }}</div>
                                </div>
                                <span class="text-sm font-medium text-center">
                                    {{ page.props.semesterDate?.startDate }} -
                                    {{ page.props.semesterDate?.endDate }}
                                </span>

                            </div>
                            <div v-for="(event, index) in page.props?.events" :key="index"
                                class="flex items-center gap-2 p-2 rounded-lg border border-gray-200 dark:border-gray-600">
                                <Avatar size="small" class="bg-blue-100! text-blue-600! rounded-lg! shadow">
                                    <IconCalendarWeek size="16" />
                                </Avatar>
                                <div class="flex flex-col">
                                    <div class="text-sm font-medium">
                                        {{ event.title }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ event.date }}
                                    </div>
                                </div>
                            </div>
                            <div v-if="page.props?.events.length === 0"
                                class="flex items-center justify-center h-full text-gray-500 text-sm">
                                No events found.
                            </div>

                        </div>
                    </div>
                </template>
            </Card>
            <Card class="col-span-2 dark:bg-slate-700! dark:text-white" :pt="{
                body: '!p-0',
                content: 'border-t border-gray-200 ',
            }">
                <template #title>
                    <div class="flex justify-between">
                        <div class="flex flex-1 items-center gap-3 pt-4 px-4">
                            <Avatar class="bg-sky-100! text-sky-600! rounded-lg! shadow">
                                <IconBook2 :size="20" :stroke="2" />
                            </Avatar>
                            <div>
                                <div class="text-sm font-semibold">
                                    School Course Distribution
                                </div>
                                <div class="text-xs text-surface-300">
                                    Total number of scholars enrolled in each
                                    course.
                                </div>
                            </div>
                        </div>
                        <div class="flex items-center px-4 pt-3">
                            <Button label="Details" size="small" severity="secondary"
                                @click="toggleDialogs('courseTable')" />
                        </div>
                    </div>
                </template>
                <template #content>
                    <div class="px-4">
                        <ApexChart type="treemap" height="350" :options="chartOptionsCourseTreemap"
                            :series="page.props.course.series" />
                    </div>
                </template>
            </Card>
        </div>
    </div>
    <Dialog v-model:visible="dialogDetails.dialog" modal style="width: 70rem" class="m-2">
        <template #header>
            <div class="flex items-center gap-2" v-if="dialogDetails.data == 'schoolTable'">
                <Avatar>
                    <IconTable />
                </Avatar>
                <div class="flex flex-col">
                    <div class="text-sm font-medium">
                        School Scholar Distribution
                    </div>
                    <div class="text-xs">
                        A summary of scholar distribution by school campus
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2" v-else>
                <Avatar>
                    <IconTable />
                </Avatar>
                <div class="flex flex-col">
                    <div class="text-sm font-medium">
                        Course Scholar Distribution
                    </div>
                    <div class="text-xs">
                        A summary of scholar distribution by Course
                    </div>
                </div>
            </div>
        </template>
        <template #default>
            <DataTable :value="dialogDetails.data == 'schoolTable'
                ? page.props?.school?.table
                : page.props?.course?.table
                " stripedRows tableStyle="min-width: 50rem" class="p-datatable-sm">
                <Column field="name">
                    <template #header>
                        <div class="text-sm w-full text-left font-medium">
                            School Courses
                        </div>
                    </template>
                    <template #body="{ data }">
                        <div class="flex flex-col">
                            <span class="text-xs text-surface-500">
                                {{ data.region }}
                            </span>
                            <span class="font-medium text-sm text-surface-900 dark:text-surface-0">
                                {{ data.name }}
                            </span>
                        </div>
                    </template>
                </Column>

                <Column field="percent" class="w-50">
                    <template #header>
                        <div class="text-sm w-full text-left font-medium">
                            Distribution
                        </div>
                    </template>
                    <template #body="{ data }">
                        <div class="flex items-center gap-1 w-full">
                            <ProgressBar :value="data.percent" :showValue="false" class="flex-1 h-2" :pt="{
                                root: 'h-1.5! rounded-full!',
                                value: 'bg-blue-600! rounded-full!',
                            }" />
                            <span class="min-w-14 text-right text-sm text-primary-600">
                                {{ data.percent.toFixed(1) }}%
                            </span>
                        </div>
                    </template>
                </Column>

                <Column field="total" class="w-30">
                    <template #header>
                        <div class="text-sm w-full text-center font-medium">
                            Scholars
                        </div>
                    </template>
                    <template #body="{ data }">
                        <div class="text-center w-full font-semibold">
                            {{ data.total.toLocaleString() }}
                        </div>
                    </template>
                </Column>
            </DataTable>
        </template>
    </Dialog>
</template>
<script setup>
import {
    IconAward,
    IconBook2,
    IconBuilding,
    IconRainbow,
    IconSchool,
    IconTable,
    IconUser,
    IconBulb,
    IconUserExclamation,
    IconMapPin,
    IconUserX,
    IconManFilled,
    IconWomanFilled,
    IconFriends,
    IconCalendarWeek,
    IconBellFilled,
} from "@tabler/icons-vue";
import { computed, onMounted, ref, watch } from "vue";
import { router, usePage } from "@inertiajs/vue3";
import { CountTo } from "vue3-count-to";
import ApexChart from "vue3-apexcharts";
import SelectInput from "../../Components/inputs/SelectInput.vue";
const page = usePage();
const dialogDetails = ref({
    dialog: false,
    data: "schoolTable",
});
const FilterDate = ref({
    value: "all",
    options: [
        { label: "All Time", value: "all" },
        { label: "This Year", value: "year" },
        { label: "This Month", value: "month" },
    ],
});

const value = ref([
    { label: "Apps", value: 100, color: "var(--p-violet-500)" },
    { label: "Messages", value: 16, color: "var(--p-emerald-500)" },
]);

const FilterRangeDate = ref({
    value: page.props?.options?.dateRange?.at(-1) ?? null,
});

const loading = ref({
    count: false,
});

const attrs = computed(() => [
    {
        key: "today",
        highlight: true,
        dates: new Date(),
    },
    {
        key: "submission",
        dot: true,
        color: 'red',
        dates: new Date(page.props.semesterDate?.submissionDate),
        popover: {
            label: "Submission Date",
        },
    },
]);



const chartOptionsCourseTreemap = ref({
    chart: {
        type: "treemap",
        height: 400,
        toolbar: {
            show: false,
        },
        fontFamily: "Inter, sans-serif",
        animations: {
            enabled: true,
            easing: "easeinout",
            speed: 700,
        },
    },

    legend: {
        show: false,
    },

    plotOptions: {
        treemap: {
            distributed: true,
            enableShades: true,
            shadeIntensity: 0.35,
            reverseNegativeShade: false,
            borderRadius: 8,
        },
    },

    colors: [
        "#2563EB",
        "#3B82F6",
        "#60A5FA",
        "#93C5FD",
        "#06B6D4",
        "#0EA5E9",
        "#14B8A6",
        "#10B981",
        "#84CC16",
        "#F59E0B",
    ],

    dataLabels: {
        enabled: true,
        style: {
            fontSize: "12px",
            fontWeight: 500,
        },
        formatter(text, opts) {
            const value = opts.value;

            const total = page.props?.school?.total ?? 0;

            const percent =
                total > 0 ? ((value / total) * 100).toFixed(1) : "0.0";

            return [text, `${value.toLocaleString()} (${percent}%)`];
        },
        offsetY: -2,
    },

    tooltip: {
        theme: "light",
        y: {
            formatter: (value) => `${value.toLocaleString()} scholars`,
        },
    },

    states: {
        hover: {
            filter: {
                type: "lighten",
                value: 0.15,
            },
        },
    },

    grid: {
        show: false,
    },
});

const chartOptionsSchoolTreemap = ref({
    chart: {
        type: "treemap",
        height: 400,
        toolbar: {
            show: false,
        },
        fontFamily: "Inter, sans-serif",
        animations: {
            enabled: true,
            easing: "easeinout",
            speed: 700,
        },
    },

    legend: {
        show: false,
    },

    plotOptions: {
        treemap: {
            distributed: true,
            enableShades: true,
            shadeIntensity: 0.35,
            reverseNegativeShade: false,
            borderRadius: 8,
        },
    },

    colors: [
        "#2563EB",
        "#3B82F6",
        "#60A5FA",
        "#93C5FD",
        "#06B6D4",
        "#0EA5E9",
        "#14B8A6",
        "#10B981",
        "#84CC16",
        "#F59E0B",
    ],

    dataLabels: {
        enabled: true,
        style: {
            fontSize: "12px",
            fontWeight: 500,
        },
        formatter(text, opts) {
            const value = opts.value;

            const total = page.props?.school?.total ?? 0;

            const percent =
                total > 0 ? ((value / total) * 100).toFixed(1) : "0.0";

            return [text, `${value.toLocaleString()} (${percent}%)`];
        },
        offsetY: -2,
    },

    tooltip: {
        theme: "light",
        y: {
            formatter: (value) => `${value.toLocaleString()} scholars`,
        },
    },

    states: {
        hover: {
            filter: {
                type: "lighten",
                value: 0.15,
            },
        },
    },

    grid: {
        show: false,
    },
});

const chartSexOptions = ref({
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

    labels: ["Male", "Female"],

    legend: {
        show: false,
    },
    colors: ["#51A2FF", "#FC64B6"],
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
                size: "50%",
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

                },
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

const toggleDialogs = (tab) => {
    dialogDetails.value.dialog = true;
    dialogDetails.value.data = tab;
};




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
