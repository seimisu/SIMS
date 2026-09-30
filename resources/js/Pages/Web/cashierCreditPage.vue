<template>
    <Head title="Cashier Deposit" />
    <AuthLayout>
        <div class="deposit-page flex h-full min-h-0 w-full flex-col gap-4 overflow-hidden text-slate-800 dark:text-gray-100">
            <div class="shrink-0 border-b border-slate-200 pb-4 dark:border-gray-700">
                <div class="flex min-w-0 items-start gap-3">
                    <div class="flex size-10 shrink-0 items-center justify-center rounded-md bg-blue-600 text-white shadow-sm dark:bg-blue-500">
                        <IconBuildingBank :size="21" stroke-width="1.8" />
                    </div>
                    <HeaderModule
                        title="Cashier Deposit"
                        description="Process approved monthly assistance deposits and track recipient releases."
                    />
                </div>
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
                    <ManagementFilterBar v-model="searchInput" search-placeholder="Search batch, region, term, or year">
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
                            placeholder="Deposit Status"
                            clearable
                            class="w-full md:w-52"
                        />
                        <DefaultButton
                            size="small"
                            severity="secondary"
                            :icon="IconFilterOff"
                            tooltip="Clear filters"
                            outlined
                            :disabled="!hasFilters"
                            @click="clearFilters"
                        />
                    </ManagementFilterBar>
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
                                class="rounded-md border border-slate-200 bg-slate-50 p-2.5 dark:border-gray-700 dark:bg-gray-800/70"
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
                                    v-if="credit.recipient_count > 0"
                                    class="mt-2 text-[11px] leading-4 text-slate-500 dark:text-gray-300"
                                >
                                    {{ credit.progress_label }} deposited
                                </div>
                                <DefaultButton
                                    v-if="credit.status !== 'credited'"
                                    size="small"
                                    label="Deposit"
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
            header="Select Scholars for Deposit"
            :style="{ width: 'min(72rem, 96vw)' }"
            :pt="{
                root: 'dark:!border-gray-700 dark:!bg-gray-900 dark:!text-gray-100',
                header: 'dark:!border-gray-700 dark:!bg-gray-900 dark:!text-gray-100',
                content: 'dark:!bg-gray-900 dark:!text-gray-100',
                footer: 'dark:!border-gray-700 dark:!bg-gray-900',
            }"
        >
            <div class="flex max-h-[70vh] flex-col gap-3 text-sm text-slate-700 dark:text-gray-200">
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
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <InputText v-model="recipientSearch" placeholder="Search scholar or account number" class="w-full !text-sm md:w-80" />
                    <div class="flex items-center gap-2">
                        <DefaultButton size="small" label="Select Pending" severity="secondary" outlined @click="selectAllPending" />
                        <DefaultButton size="small" label="Clear" severity="secondary" outlined @click="clearPendingSelection" />
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-2 rounded border border-slate-200 bg-white p-3 dark:border-gray-600 dark:bg-gray-900 md:grid-cols-4">
                    <div><div class="text-xs text-slate-500 dark:text-gray-400">Selected</div><div class="font-semibold">{{ selectedPendingCount }} / {{ pendingRecipientCount }}</div></div>
                    <div><div class="text-xs text-slate-500 dark:text-gray-400">Already deposited</div><div class="font-semibold">{{ creditedRecipientCount }}</div></div>
                    <div><div class="text-xs text-slate-500 dark:text-gray-400">Deposit amount</div><div class="font-semibold text-emerald-700 dark:text-emerald-300">{{ formatMoney(selectedAmount) }}</div></div>
                    <div><div class="text-xs text-slate-500 dark:text-gray-400">Remaining after deposit</div><div class="font-semibold">{{ remainingAfterDeposit }}</div></div>
                </div>
                <div v-if="recipientLoading" class="py-10 text-center text-slate-500 dark:text-gray-400">Loading scholars...</div>
                <div v-else class="min-h-0 overflow-auto rounded border border-slate-200 dark:border-gray-700">
                    <table class="w-full min-w-[780px] text-left text-xs">
                        <thead class="sticky top-0 bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-200">
                            <tr><th class="w-12 p-2"></th><th class="p-2">Scholar</th><th class="p-2">Account</th><th class="p-2 text-right">Amount</th><th class="p-2">Status / Remarks</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                            <tr v-for="recipient in filteredRecipients" :key="recipient.id" class="bg-white dark:bg-gray-900">
                                <td class="p-2 text-center"><input v-if="recipient.status !== 'credited'" v-model="recipient.selected" type="checkbox" class="h-4 w-4 accent-blue-600" /></td>
                                <td class="p-2"><div class="font-semibold text-slate-800 dark:text-gray-100">{{ recipient.name }}</div><div class="text-slate-500 dark:text-gray-400">{{ recipient.spas_no }}</div></td>
                                <td class="p-2 text-slate-600 dark:text-gray-300">{{ recipient.account_no || '-' }}</td>
                                <td class="p-2 text-right font-semibold">{{ formatMoney(recipient.amount) }}</td>
                                <td class="p-2">
                                    <span v-if="recipient.status === 'credited'" class="text-emerald-700 dark:text-emerald-300">Deposited<span v-if="recipient.credited_at"> | {{ recipient.credited_at }}</span></span>
                                    <Textarea v-else-if="!recipient.selected" v-model="recipient.remarks" rows="2" autoResize fluid placeholder="Required reason for not depositing" class="!text-xs" />
                                    <span v-else class="text-blue-600 dark:text-blue-300">Selected for deposit</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div v-if="selectionError" class="text-xs font-medium text-red-600 dark:text-red-300">{{ selectionError }}</div>
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
                    label="Confirm Deposit"
                    severity="success"
                    :icon="IconCashBanknote"
                    :loading="creditForm.processing"
                    :disabled="recipientLoading || creditForm.processing"
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
import ManagementFilterBar from "../../Components/inputs/ManagementFilterBar.vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import axios from "axios";
import { IconBuildingBank, IconCashBanknote, IconFilterOff } from "@tabler/icons-vue";
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
const depositRecipients = ref([]);
const recipientSearch = ref("");
const recipientLoading = ref(false);
const selectionError = ref("");
const creditForm = useForm({
    recipients: [],
});

const statusLabel = (status) =>
    ({
        pending: "Pending",
        partial: "Partial",
        credited: "Deposit",
    })[status] ?? status;

const creditStatusClass = (status) =>
    status === "credited"
        ? "border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300"
        : status === "partial"
            ? "border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-900/40 dark:text-amber-300"
            : "border-slate-200 bg-slate-50 text-slate-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300";

const creditKey = (batch, credit) => `${batch.id}-${credit.month_no}`;
const hasFilters = computed(() =>
    Boolean(searchInput.value || filterRegion.value || filterTerm.value || filterSchoolYear.value || filterCreditStatus.value),
);

const openCreditDialog = async (batch, credit) => {
    if (!batch?.id || !credit?.month_no) return;

    selectedCreditBatch.value = batch;
    selectedCredit.value = credit;
    creditDialog.value = true;
    recipientLoading.value = true;
    selectionError.value = "";
    recipientSearch.value = "";

    try {
        const response = await axios.get(route("cashier.credits.recipients", { id: batch.id, month: credit.month_no }));
        depositRecipients.value = response.data.recipients.map((recipient) => ({
            ...recipient,
            selected: recipient.status !== "credited",
            remarks: recipient.remarks ?? "",
        }));
    } catch (error) {
        selectionError.value = error.response?.data?.message ?? "Unable to load payroll recipients.";
    } finally {
        recipientLoading.value = false;
    }
};

const closeCreditDialog = () => {
    if (creditForm.processing) return;

    creditDialog.value = false;
    selectedCreditBatch.value = null;
    selectedCredit.value = null;
    depositRecipients.value = [];
};

const filteredRecipients = computed(() => {
    const search = recipientSearch.value.trim().toLowerCase();
    if (!search) return depositRecipients.value;
    return depositRecipients.value.filter((recipient) =>
        [recipient.name, recipient.spas_no, recipient.account_no].some((value) => String(value ?? "").toLowerCase().includes(search)),
    );
});
const pendingRecipientCount = computed(() => depositRecipients.value.filter((recipient) => recipient.status !== "credited").length);
const creditedRecipientCount = computed(() => depositRecipients.value.filter((recipient) => recipient.status === "credited").length);
const selectedPendingCount = computed(() => depositRecipients.value.filter((recipient) => recipient.status !== "credited" && recipient.selected).length);
const selectedAmount = computed(() => depositRecipients.value.filter((recipient) => recipient.status !== "credited" && recipient.selected).reduce((sum, recipient) => sum + Number(recipient.amount || 0), 0));
const remainingAfterDeposit = computed(() => pendingRecipientCount.value - selectedPendingCount.value);
const formatMoney = (value) => new Intl.NumberFormat("en-PH", { style: "currency", currency: "PHP" }).format(Number(value || 0));
const selectAllPending = () => depositRecipients.value.forEach((recipient) => { if (recipient.status !== "credited") recipient.selected = true; });
const clearPendingSelection = () => depositRecipients.value.forEach((recipient) => { if (recipient.status !== "credited") recipient.selected = false; });

const confirmCreditMonth = () => {
    const batch = selectedCreditBatch.value;
    const credit = selectedCredit.value;

    if (!batch?.id || !credit?.month_no) return;

    const pending = depositRecipients.value.filter((recipient) => recipient.status !== "credited");
    if (!pending.some((recipient) => recipient.selected)) {
        selectionError.value = "Select at least one scholar to deposit.";
        return;
    }
    const missingRemark = pending.find((recipient) => !recipient.selected && !recipient.remarks?.trim());
    if (missingRemark) {
        selectionError.value = `Enter remarks for ${missingRemark.name}.`;
        return;
    }

    selectionError.value = "";
    creditingKey.value = creditKey(batch, credit);
    creditForm.recipients = pending.map(({ id, selected, remarks }) => ({ id, selected, remarks }));
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
    padding: 0 0 0.75rem !important;
    background: transparent !important;
}

:deep(.p-datatable-table-container) {
    border-top: 0 !important;
}

:global(.dark .deposit-page .p-datatable-header-cell),
:global(.dark .deposit-page .p-datatable-column-header-content) {
    color: #e5e7eb !important;
}

:global(.deposit-page .p-datatable-tbody > tr > td) {
    padding-top: 0.65rem;
    padding-bottom: 0.65rem;
}
</style>
