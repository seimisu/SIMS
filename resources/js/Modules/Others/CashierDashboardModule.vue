<template>
    <div class="min-h-full bg-slate-50 px-3 py-3 text-slate-800 dark:bg-gray-800 dark:text-gray-100">
        <div class="mx-auto flex max-w-[1500px] flex-col gap-4">
            <div class="flex flex-wrap items-end justify-between gap-3">
                <div>
                    <div class="text-[11px] font-semibold uppercase text-slate-500 dark:text-gray-400">
                        Cashier operations
                    </div>
                    <h1 class="text-lg font-semibold text-slate-900 dark:text-white">
                        Cashier Dashboard
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-gray-400">
                        Monitor approved batches and monthly crediting progress.
                    </p>
                </div>
                <button
                    type="button"
                    class="inline-flex cursor-pointer items-center gap-1 rounded bg-blue-50 px-3 py-2 text-xs font-semibold text-blue-700 transition hover:bg-blue-100 dark:bg-blue-900/40 dark:text-blue-300 dark:hover:bg-blue-900/60"
                    @click="goToCredits"
                >
                    <IconEye :size="14" />
                    Open Crediting
                </button>
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

            <div class="grid auto-rows-[minmax(160px,auto)] grid-cols-1 gap-4 lg:grid-cols-3">
                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-700 lg:col-span-2">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
                                Monthly Release Progress
                            </h2>
                            <p class="text-[11px] text-slate-500 dark:text-gray-400">
                                Credited and pending release counts by month.
                            </p>
                        </div>
                    </div>
                    <div class="grid gap-3 sm:grid-cols-5">
                        <div
                            v-for="month in monthSummary"
                            :key="month.month_no"
                            class="rounded-lg border border-slate-100 bg-slate-50 p-4 dark:border-gray-600 dark:bg-gray-800"
                        >
                            <div class="text-xs font-semibold text-slate-700 dark:text-gray-200">
                                {{ month.label }}
                            </div>
                            <div class="mt-3 flex items-end justify-between gap-2">
                                <div>
                                    <div class="text-[11px] text-slate-500 dark:text-gray-400">Credited</div>
                                    <div class="text-xl font-semibold text-emerald-600 dark:text-emerald-300">
                                        {{ formatNumber(month.credited) }}
                                    </div>
                                </div>
                                <div class="text-right">
                                    <div class="text-[11px] text-slate-500 dark:text-gray-400">Pending</div>
                                    <div class="text-xl font-semibold text-amber-600 dark:text-amber-300">
                                        {{ formatNumber(month.pending) }}
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-700">
                    <div class="mb-4 flex items-center gap-3">
                        <div class="rounded bg-emerald-50 p-2 text-emerald-600">
                            <IconCircleCheck :size="20" />
                        </div>
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
                                Crediting Completion
                            </h2>
                            <p class="text-[11px] text-slate-500 dark:text-gray-400">
                                Overall monthly release status.
                            </p>
                        </div>
                    </div>
                    <div class="rounded-lg border border-slate-100 bg-slate-50 p-4 dark:border-gray-600 dark:bg-gray-800">
                        <div class="mb-3 flex items-end justify-between">
                            <span class="text-xs text-slate-500 dark:text-gray-400">Progress</span>
                            <span class="text-2xl font-semibold text-slate-900 dark:text-white">
                                {{ completionPercent }}%
                            </span>
                        </div>
                        <div class="h-3 overflow-hidden rounded bg-slate-200 dark:bg-gray-700">
                            <div
                                class="h-full rounded bg-emerald-500"
                                :style="{ width: `${completionPercent}%` }"
                            />
                        </div>
                        <div class="mt-4 grid gap-2 text-xs">
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-600 dark:text-gray-300">Credited releases</span>
                                <span class="font-semibold text-slate-900 dark:text-white">{{ formatNumber(summary.credited_releases) }}</span>
                            </div>
                            <div class="flex items-center justify-between gap-3">
                                <span class="text-slate-600 dark:text-gray-300">Pending releases</span>
                                <span class="font-semibold text-slate-900 dark:text-white">{{ formatNumber(summary.pending_releases) }}</span>
                            </div>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg border border-slate-200 bg-white p-5 shadow-sm dark:border-gray-700 dark:bg-gray-700 lg:col-span-3">
                    <div class="mb-4 flex items-center justify-between gap-3">
                        <div>
                            <h2 class="text-sm font-semibold text-slate-900 dark:text-white">
                                Recent Approved Batches
                            </h2>
                            <p class="text-[11px] text-slate-500 dark:text-gray-400">
                                Latest approved batches queued for crediting.
                            </p>
                        </div>
                    </div>
                    <div class="overflow-auto rounded border border-slate-200 dark:border-gray-600">
                        <table class="w-full min-w-[820px] text-left text-xs">
                            <thead class="bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-300">
                                <tr>
                                    <th class="px-3 py-2 font-semibold">Batch</th>
                                    <th class="px-3 py-2 font-semibold">Region</th>
                                    <th class="px-3 py-2 font-semibold">Term / AY</th>
                                    <th class="px-3 py-2 font-semibold">Scholars</th>
                                    <th class="px-3 py-2 font-semibold">Credited</th>
                                    <th class="px-3 py-2 font-semibold">Approved</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="batch in recentBatches"
                                    :key="batch.id"
                                    class="border-t border-slate-100 dark:border-gray-600"
                                >
                                    <td class="px-3 py-2 font-medium text-slate-800 dark:text-gray-100">{{ batch.name }}</td>
                                    <td class="px-3 py-2 text-slate-600 dark:text-gray-300">{{ batch.region }}</td>
                                    <td class="px-3 py-2 text-slate-600 dark:text-gray-300">{{ batch.term }} / {{ batch.school_year }}</td>
                                    <td class="px-3 py-2 text-slate-600 dark:text-gray-300">{{ formatNumber(batch.scholars_count) }}</td>
                                    <td class="px-3 py-2">
                                        <span class="rounded bg-emerald-50 px-2 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                            {{ batch.credited }}/{{ batch.total }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-slate-600 dark:text-gray-300">{{ batch.approved_at || "-" }}</td>
                                </tr>
                                <tr v-if="!recentBatches.length">
                                    <td colspan="6" class="px-3 py-6 text-center text-xs text-slate-500 dark:text-gray-400">
                                        No approved batches are ready for crediting.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </div>
</template>

<script setup>
import { router, usePage } from "@inertiajs/vue3";
import {
    IconCashBanknote,
    IconCircleCheck,
    IconClipboardList,
    IconClock,
    IconEye,
} from "@tabler/icons-vue";
import { computed } from "vue";
import { route } from "ziggy-js";

const page = usePage();
const dashboard = computed(() => page.props.cashierDashboard ?? {});
const summary = computed(() => dashboard.value.summary ?? {});
const monthSummary = computed(() => dashboard.value.monthSummary ?? []);
const recentBatches = computed(() => dashboard.value.recentBatches ?? []);
const completionPercent = computed(() => {
    const total = Number(summary.value.monthly_releases ?? 0);
    if (!total) return 0;

    return Math.round((Number(summary.value.credited_releases ?? 0) / total) * 100);
});

const metrics = computed(() => [
    {
        label: "Approved Batches",
        value: summary.value.approved_batches,
        caption: "Ready for monthly release crediting",
        icon: IconClipboardList,
        color: "bg-blue-50 text-blue-600",
    },
    {
        label: "Monthly Releases",
        value: summary.value.monthly_releases,
        caption: "Total release slots across approved batches",
        icon: IconCashBanknote,
        color: "bg-cyan-50 text-cyan-600",
    },
    {
        label: "Credited",
        value: summary.value.credited_releases,
        caption: "Monthly releases already credited",
        icon: IconCircleCheck,
        color: "bg-emerald-50 text-emerald-600",
    },
    {
        label: "Pending",
        value: summary.value.pending_releases,
        caption: "Monthly releases awaiting cashier action",
        icon: IconClock,
        color: "bg-amber-50 text-amber-600",
    },
]);

function formatNumber(num) {
    return Number(num ?? 0).toLocaleString("en-US");
}

function goToCredits() {
    router.visit(route("cashier.credits"));
}
</script>
