<template>
    <Head title="Cashier Crediting" />
    <AuthLayout>
        <div class="flex h-full min-h-0 w-full flex-col gap-5 overflow-hidden">
            <div class="shrink-0">
                <HeaderModule
                    title="Cashier Crediting"
                    description="Credit approved financial assistance batches by monthly release."
                />
            </div>

            <DefaultSelectionTable
                class="min-h-0 flex-1"
                :items="page.props.batches.data"
                :pagination="{
                    total: page.props.batches.total,
                    perPage: page.props.batches.per_page,
                    currentPage: page.props.batches.current_page,
                }"
                scrollable
                scroll-height="flex"
                @paginate="loadPage"
            >
                <template #header>
                    <div class="flex flex-wrap items-center gap-2">
                        <InputText
                            v-model="searchInput"
                            placeholder="Search batch, region, term, or year"
                            class="!text-sm w-full md:w-80"
                        />
                        <SelectInput
                            v-model="filterRegion"
                            :options="page.props.filterOptions?.regions ?? []"
                            placeholder="Region"
                            clearable
                            filter
                            class="w-full md:w-48"
                        />
                        <SelectInput
                            v-model="filterTerm"
                            :options="page.props.filterOptions?.terms ?? []"
                            placeholder="Term"
                            clearable
                            class="w-full md:w-44"
                        />
                        <SelectInput
                            v-model="filterSchoolYear"
                            :options="page.props.filterOptions?.schoolYears ?? []"
                            placeholder="Academic Year"
                            clearable
                            class="w-full md:w-44"
                        />
                        <SelectInput
                            v-model="filterCreditStatus"
                            :options="page.props.filterOptions?.creditStatuses ?? []"
                            placeholder="Credit Status"
                            clearable
                            class="w-full md:w-52"
                        />
                        <DefaultButton
                            size="small"
                            severity="secondary"
                            :icon="IconFilterOff"
                            tooltip="Clear filters"
                            :disabled="!hasFilters"
                            @click="clearFilters"
                        />
                    </div>
                </template>

                <Column header="Batch">
                    <template #body="props">
                        <div class="min-w-64">
                            <div class="truncate text-sm font-semibold text-slate-700 dark:text-gray-100">
                                {{ props.data.name }}
                            </div>
                            <div class="text-xs text-slate-500 dark:text-gray-400">
                                {{ props.data.region }}
                            </div>
                        </div>
                    </template>
                </Column>
                <Column header="Term / AY">
                    <template #body="props">
                        <div class="min-w-44 text-sm text-slate-700 dark:text-gray-200">
                            {{ props.data.term }} / {{ props.data.school_year }}
                        </div>
                    </template>
                </Column>
                <Column header="Scholars">
                    <template #body="props">
                        <div class="text-sm font-semibold text-slate-700 dark:text-gray-200">
                            {{ props.data.scholars_count ?? 0 }}
                        </div>
                    </template>
                </Column>
                <Column header="Monthly Releases">
                    <template #body="props">
                        <div class="grid min-w-[680px] grid-cols-5 gap-2 py-1">
                            <div
                                v-for="credit in props.data.credits"
                                :key="credit.month_no"
                                class="rounded border border-slate-200 bg-white p-2 dark:border-gray-600 dark:bg-gray-900"
                            >
                                <div class="flex items-center justify-between gap-2">
                                    <div class="text-xs font-semibold text-slate-700 dark:text-gray-100">
                                        {{ credit.label }}
                                    </div>
                                    <span
                                        :class="[
                                            creditStatusClass(credit.status),
                                            'rounded border px-2 py-0.5 text-[10px] font-semibold',
                                        ]"
                                    >
                                        {{ statusLabel(credit.status) }}
                                    </span>
                                </div>
                                <div
                                    v-if="credit.status === 'credited'"
                                    class="mt-2 text-[11px] leading-4 text-slate-500 dark:text-gray-400"
                                >
                                    {{ credit.credited_by || "Cashier" }}
                                    <span v-if="credit.credited_at"> | {{ credit.credited_at }}</span>
                                </div>
                                <DefaultButton
                                    v-else
                                    size="small"
                                    label="Credit"
                                    severity="success"
                                    :icon="IconCashBanknote"
                                    class="mt-2 w-full"
                                    :loading="creditForm.processing && creditingKey === creditKey(props.data, credit)"
                                    @click.stop="openCreditDialog(props.data, credit)"
                                />
                            </div>
                        </div>
                    </template>
                </Column>
            </DefaultSelectionTable>
        </div>

        <Dialog
            v-model:visible="creditDialog"
            modal
            header="Confirm Crediting"
            :style="{ width: '30rem' }"
            :pt="{
                root: 'dark:!border-gray-700 dark:!bg-gray-900 dark:!text-gray-100',
                header: 'dark:!border-gray-700 dark:!bg-gray-900 dark:!text-gray-100',
                content: 'dark:!bg-gray-900 dark:!text-gray-100',
                footer: 'dark:!border-gray-700 dark:!bg-gray-900',
            }"
        >
            <div class="flex flex-col gap-3 text-sm text-slate-700 dark:text-gray-200">
                <div>
                    Mark this monthly release as credited?
                </div>
                <div class="rounded border border-slate-200 bg-slate-50 p-3 dark:border-gray-600 dark:bg-gray-800">
                    <div class="font-semibold text-slate-800 dark:text-gray-100">
                        {{ selectedCreditBatch?.name }}
                    </div>
                    <div class="mt-1 text-xs text-slate-500 dark:text-gray-400">
                        {{ selectedCreditBatch?.region }} | {{ selectedCreditBatch?.term }} / {{ selectedCreditBatch?.school_year }}
                    </div>
                    <div class="mt-2 text-sm font-semibold text-emerald-700 dark:text-emerald-300">
                        {{ selectedCredit?.label }}
                    </div>
                </div>
            </div>

            <template #footer>
                <DefaultButton
                    size="small"
                    label="Cancel"
                    severity="secondary"
                    outlined
                    :disabled="creditForm.processing"
                    @click="closeCreditDialog"
                />
                <DefaultButton
                    size="small"
                    label="Confirm Credit"
                    severity="success"
                    :icon="IconCashBanknote"
                    :loading="creditForm.processing"
                    @click="confirmCreditMonth"
                />
            </template>
        </Dialog>
        <DefaultToast ref="toastRef" />
    </AuthLayout>
</template>

<script setup>
import AuthLayout from "../../Layouts/AuthLayout.vue";
import HeaderModule from "../../Modules/Others/HeaderModule.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import DefaultSelectionTable from "../../Components/tables/DefaultSelectionTable.vue";
import DefaultToast from "../../Components/messages/DefaultToast.vue";
import SelectInput from "../../Components/inputs/SelectInput.vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import { IconCashBanknote, IconFilterOff } from "@tabler/icons-vue";
import { computed, ref, watch } from "vue";
import { route } from "ziggy-js";

const page = usePage();
const toastRef = ref(null);
const timerBounce = ref(null);
const lastFlashKey = ref(null);
const searchInput = ref(page.props.filters?.search ?? "");
const findOption = (options, value) =>
    (options ?? []).find((option) => String(option?.name ?? "") === String(value ?? "")) ?? null;
const filterRegion = ref(findOption(page.props.filterOptions?.regions, page.props.filters?.region));
const filterTerm = ref(findOption(page.props.filterOptions?.terms, page.props.filters?.term));
const filterSchoolYear = ref(findOption(page.props.filterOptions?.schoolYears, page.props.filters?.school_year));
const filterCreditStatus = ref(
    (page.props.filterOptions?.creditStatuses ?? []).find((option) => option.id === page.props.filters?.credit_status) ?? null,
);
const creditingKey = ref(null);
const creditDialog = ref(false);
const selectedCreditBatch = ref(null);
const selectedCredit = ref(null);
const creditForm = useForm({
    remarks: null,
});

const statusLabel = (status) =>
    ({
        pending: "Pending",
        credited: "Credited",
    })[status] ?? status;

const creditStatusClass = (status) =>
    status === "credited"
        ? "border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300"
        : "border-slate-200 bg-slate-50 text-slate-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300";

const creditKey = (batch, credit) => `${batch.id}-${credit.month_no}`;
const hasFilters = computed(() =>
    Boolean(searchInput.value || filterRegion.value || filterTerm.value || filterSchoolYear.value || filterCreditStatus.value),
);

const openCreditDialog = (batch, credit) => {
    if (!batch?.id || !credit?.month_no) return;

    selectedCreditBatch.value = batch;
    selectedCredit.value = credit;
    creditDialog.value = true;
};

const closeCreditDialog = () => {
    if (creditForm.processing) return;

    creditDialog.value = false;
    selectedCreditBatch.value = null;
    selectedCredit.value = null;
};

const confirmCreditMonth = () => {
    const batch = selectedCreditBatch.value;
    const credit = selectedCredit.value;

    if (!batch?.id || !credit?.month_no) return;

    creditingKey.value = creditKey(batch, credit);
    creditForm.remarks = null;
    creditForm.put(
        route("cashier.credits.update", {
            id: batch.id,
            month: credit.month_no,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                creditDialog.value = false;
                selectedCreditBatch.value = null;
                selectedCredit.value = null;
            },
            onFinish: () => {
                creditingKey.value = null;
            },
        },
    );
};

const loadPage = (pageNumber = 1) => {
    router.get(
        route("cashier.credits"),
        {
            page: pageNumber,
            ...(searchInput.value ? { search: searchInput.value } : {}),
            ...(filterRegion.value ? { region: filterRegion.value.name } : {}),
            ...(filterTerm.value ? { term: filterTerm.value.name } : {}),
            ...(filterSchoolYear.value ? { school_year: filterSchoolYear.value.name } : {}),
            ...(filterCreditStatus.value ? { credit_status: filterCreditStatus.value.id } : {}),
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const clearFilters = () => {
    searchInput.value = "";
    filterRegion.value = null;
    filterTerm.value = null;
    filterSchoolYear.value = null;
    filterCreditStatus.value = null;
    loadPage(1);
};

watch(
    () => [
        searchInput.value,
        filterRegion.value,
        filterTerm.value,
        filterSchoolYear.value,
        filterCreditStatus.value,
    ],
    () => {
        clearTimeout(timerBounce.value);
        timerBounce.value = setTimeout(() => loadPage(1), 300);
    },
);

watch(
    () => page.props.flash,
    (flash) => {
        if (!flash?.status) return;

        const key = `${flash.status}-${flash.title}-${flash.message}`;
        if (key === lastFlashKey.value) return;

        lastFlashKey.value = key;
        toastRef.value?.show(flash);
    },
);
</script>

<style scoped>
:deep(.p-datatable-header) {
    border-bottom: 0 !important;
    padding-bottom: 0.75rem;
}

:deep(.p-datatable-table-container) {
    border-top: 0 !important;
}
</style>
