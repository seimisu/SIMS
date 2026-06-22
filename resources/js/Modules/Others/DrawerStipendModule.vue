<template>
    <Drawer
        v-model:visible="modelValue"
        position="full"
        :pt="{
            header: 'border-b-1 border-gray-300 border-dashed',
            content: '!p-3',
        }"
    >
        <template #header>
            <div class="flex items-center gap-2">
                <div
                    class="bg-green-50 border text-green-600 px-5 py-1.5 shadow rounded-lg flex items-center gap-2"
                >
                    <IconFileSpreadsheet :size="20" />
                    <div class="text-sm uppercase font-medium">
                        {{ details?.name ?? "Stipend Batch" }}
                    </div>
                </div>
                <div
                    :class="[
                        statusMeta.class,
                        'border px-4 py-1.5 rounded-lg text-xs font-semibold',
                    ]"
                >
                    {{ statusMeta.label }}
                </div>
            </div>
        </template>

        <template #default>
            <div class="flex flex-col w-full h-full gap-3">
                <Tabs v-model:value="activeTab" class="flex-1 flex flex-col min-h-0">
                    <TabList>
                        <Tab value="eligible">Eligible Scholars</Tab>
                        <Tab value="payroll">Payroll</Tab>
                    </TabList>

                    <TabPanels class="flex-1 min-h-0 !px-0">
                        <TabPanel value="eligible" class="h-full">
                            <div class="flex flex-col h-full gap-3">
                                <div
                                    class="flex flex-col lg:flex-row lg:items-center justify-between gap-3"
                                >
                                    <div class="flex items-center gap-2">
                                        <IconCircleCheck :size="20" class="text-green-600" />
                                        <div>
                                            <div class="text-sm font-semibold">
                                                Validated Periodic Reports
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                Scholars shown here are not yet part of this payroll.
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex flex-col xl:flex-row gap-2 xl:items-center">
                                        <InputText
                                            v-model="eligibleSearch"
                                            placeholder="Search SPAS or name"
                                            class="!text-sm min-w-64"
                                        />
                                        <Select
                                            v-model="eligibleProgram"
                                            :options="eligibleProgramOptions"
                                            placeholder="Program"
                                            showClear
                                            class="!text-sm min-w-44"
                                        />
                                        <Select
                                            v-model="eligibleUniversity"
                                            :options="eligibleUniversityOptions"
                                            placeholder="University"
                                            showClear
                                            class="!text-sm min-w-56"
                                        />
                                        <Select
                                            v-model="eligibleStatus"
                                            :options="eligibleStatusOptions"
                                            placeholder="Status"
                                            showClear
                                            class="!text-sm min-w-44"
                                        />
                                        <DefaultButton
                                            size="small"
                                            label="Add to Payroll"
                                            :icon="IconUserPlus"
                                            :loading="addForm.processing"
                                            :disabled="!selectedEligible.length || !isEditable"
                                            @click="addSelectedScholars"
                                        />
                                    </div>
                                </div>

                                <DataTable
                                    v-model:selection="selectedEligible"
                                    :value="eligibleScholars.data"
                                    dataKey="id"
                                    paginator
                                    lazy
                                    :rows="eligibleScholars.per_page"
                                    :totalRecords="eligibleScholars.total"
                                    :first="
                                        (eligibleScholars.current_page - 1) *
                                        eligibleScholars.per_page
                                    "
                                    responsiveLayout="scroll"
                                    size="small"
                                    class="text-sm"
                                    @page="loadEligiblePage"
                                >
                                    <Column selectionMode="multiple" headerStyle="width: 3rem" />
                                    <Column header="SPAS No" field="spas_no" />
                                    <Column header="Name">
                                        <template #body="props">
                                            <div class="font-medium uppercase">
                                                {{ props.data.name }}
                                            </div>
                                            <div class="text-xs text-gray-500">
                                                {{ props.data.email }}
                                            </div>
                                        </template>
                                    </Column>
                                    <Column header="Account No" field="account_no" />
                                    <Column header="Program" field="program" />
                                    <Column header="University" field="university" />
                                    <Column header="Status" field="status" />

                                    <template #empty>
                                        <div class="py-6 text-center text-sm text-gray-500">
                                            No eligible scholars found.
                                        </div>
                                    </template>
                                </DataTable>
                            </div>
                        </TabPanel>

                        <TabPanel value="payroll" class="h-full">
                            <div class="flex flex-col h-full gap-3">
                                <div class="flex items-center justify-between gap-3">
                                    <div>
                                        <div class="text-sm font-semibold">
                                            Payroll Recipients
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            Edit month amounts, withheld amounts, allowances, and remarks.
                                        </div>
                                    </div>

                                    <div class="flex flex-col xl:flex-row gap-2 xl:items-center">
                                        <InputText
                                            v-model="payrollSearch"
                                            placeholder="Search SPAS or name"
                                            class="!text-sm min-w-64"
                                        />
                                        <Select
                                            v-model="payrollProgram"
                                            :options="payrollProgramOptions"
                                            placeholder="Program"
                                            showClear
                                            class="!text-sm min-w-44"
                                        />
                                        <Select
                                            v-model="payrollUniversity"
                                            :options="payrollUniversityOptions"
                                            placeholder="University"
                                            showClear
                                            class="!text-sm min-w-56"
                                        />
                                        <Select
                                            v-model="payrollStatus"
                                            :options="payrollStatusOptions"
                                            placeholder="Status"
                                            showClear
                                            class="!text-sm min-w-44"
                                        />
                                        <DefaultButton
                                            size="small"
                                            label="Save Payroll"
                                            :icon="IconDeviceFloppy"
                                            :loading="payrollForm.processing"
                                            :disabled="!payrollRows.length || !isEditable"
                                            @click="savePayroll"
                                        />
                                        <DefaultButton
                                            v-if="isEditable"
                                            size="small"
                                            label="Submit"
                                            severity="success"
                                            :icon="IconSend"
                                            :loading="statusForm.processing"
                                            :disabled="!payrollRows.length"
                                            @click="updateBatchStatus('submitted_payroll')"
                                        />
                                        <DefaultButton
                                            v-if="details?.status === 'submitted_payroll'"
                                            size="small"
                                            label="Reject"
                                            severity="danger"
                                            outlined
                                            :icon="IconX"
                                            :loading="statusForm.processing"
                                            @click="updateBatchStatus('rejected_payroll')"
                                        />
                                        <DefaultButton
                                            v-if="details?.status === 'submitted_payroll'"
                                            size="small"
                                            label="Approve"
                                            severity="success"
                                            outlined
                                            :icon="IconChecks"
                                            :loading="statusForm.processing"
                                            @click="updateBatchStatus('approved_payroll')"
                                        />
                                    </div>
                                </div>

                                <div class="flex-1 overflow-auto border rounded-lg">
                                    <table class="min-w-[1600px] w-full text-xs">
                                        <thead class="bg-slate-50 sticky top-0 z-10">
                                            <tr>
                                                <th class="border px-2 py-2 text-left">Account No</th>
                                                <th class="border px-2 py-2 text-left">Name</th>
                                                <th class="border px-2 py-2 text-left">Program</th>
                                                <th class="border px-2 py-2 text-left">University</th>
                                                <th class="border px-2 py-2 text-left">Status</th>
                                                <th class="border px-2 py-2 text-left">Period</th>
                                                <th
                                                    v-for="month in 5"
                                                    :key="month"
                                                    class="border px-2 py-2 text-right"
                                                >
                                                    Month {{ month }}
                                                </th>
                                                <th class="border px-2 py-2 text-right">Withheld</th>
                                                <th class="border px-2 py-2 text-left">Remarks</th>
                                                <th class="border px-2 py-2 text-right">LMA/Connectivity</th>
                                                <th class="border px-2 py-2 text-right">Clothing</th>
                                                <th class="border px-2 py-2 text-right">Total</th>
                                                <th class="border px-2 py-2 text-center">Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr v-for="row in filteredPayrollRows" :key="row.id">
                                                <td class="border px-2 py-1">
                                                    <InputText
                                                        v-model="row.account_no"
                                                        class="!text-xs w-36"
                                                        :disabled="!isEditable"
                                                    />
                                                </td>
                                                <td class="border px-2 py-1 uppercase min-w-56">
                                                    {{ row.name }}
                                                </td>
                                                <td class="border px-2 py-1">{{ row.program }}</td>
                                                <td class="border px-2 py-1 min-w-56">
                                                    {{ row.university }}
                                                </td>
                                                <td class="border px-2 py-1">
                                                    <InputText
                                                        v-model="row.scholarship_status"
                                                        class="!text-xs w-44"
                                                        :disabled="!isEditable"
                                                    />
                                                </td>
                                                <td class="border px-2 py-1">
                                                    <InputText
                                                        v-model="row.period"
                                                        class="!text-xs w-44"
                                                        :disabled="!isEditable"
                                                    />
                                                </td>
                                                <td
                                                    v-for="month in 5"
                                                    :key="month"
                                                    class="border px-2 py-1"
                                                >
                                                    <InputNumber
                                                        v-model="row[`month_${month}`]"
                                                        inputClass="!text-xs !text-right w-28"
                                                        :min="0"
                                                        :minFractionDigits="2"
                                                        :maxFractionDigits="2"
                                                        :disabled="!isEditable"
                                                    />
                                                </td>
                                                <td class="border px-2 py-1">
                                                    <InputNumber
                                                        v-model="row.total_withheld"
                                                        inputClass="!text-xs !text-right w-28"
                                                        :min="0"
                                                        :minFractionDigits="2"
                                                        :maxFractionDigits="2"
                                                        :disabled="!isEditable"
                                                    />
                                                </td>
                                                <td class="border px-2 py-1">
                                                    <InputText
                                                        v-model="row.remarks"
                                                        class="!text-xs w-56"
                                                        :disabled="!isEditable"
                                                    />
                                                </td>
                                                <td class="border px-2 py-1">
                                                    <InputNumber
                                                        v-model="row.learning_materials_amount"
                                                        inputClass="!text-xs !text-right w-28"
                                                        :min="0"
                                                        :minFractionDigits="2"
                                                        :maxFractionDigits="2"
                                                        :disabled="!isEditable"
                                                    />
                                                </td>
                                                <td class="border px-2 py-1">
                                                    <InputNumber
                                                        v-model="row.clothing_amount"
                                                        inputClass="!text-xs !text-right w-28"
                                                        :min="0"
                                                        :minFractionDigits="2"
                                                        :maxFractionDigits="2"
                                                        :disabled="!isEditable"
                                                    />
                                                </td>
                                                <td class="border px-2 py-1 text-right font-semibold">
                                                    {{ formatMoney(rowTotal(row)) }}
                                                </td>
                                                <td class="border px-2 py-1 text-center">
                                                    <DefaultButton
                                                        size="small"
                                                        severity="danger"
                                                        text
                                                        rounded
                                                        :icon="IconTrash"
                                                        tooltip="Remove from payroll"
                                                        :disabled="!isEditable"
                                                        :loading="removeForm.processing && removingId === row.id"
                                                        @click="removeRecipient(row)"
                                                    />
                                                </td>
                                            </tr>
                                            <tr v-if="!filteredPayrollRows.length">
                                                <td colspan="16" class="py-8 text-center text-gray-500">
                                                    No payroll recipients found.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </div>
        </template>
    </Drawer>
</template>

<script setup>
import {
    IconChecks,
    IconCircleCheck,
    IconDeviceFloppy,
    IconFileSpreadsheet,
    IconSend,
    IconTrash,
    IconUserPlus,
    IconX,
} from "@tabler/icons-vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { computed, nextTick, ref, watch } from "vue";
import { route } from "ziggy-js";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";

const modelValue = defineModel("visible");
const page = usePage();
const activeTab = ref("eligible");
const selectedEligible = ref([]);
const eligibleSearch = ref(null);
const eligibleProgram = ref(null);
const eligibleUniversity = ref(null);
const eligibleStatus = ref(null);
const searchTimer = ref(null);
const payrollRows = ref([]);
const payrollSearch = ref(null);
const payrollProgram = ref(null);
const payrollUniversity = ref(null);
const payrollStatus = ref(null);

const details = computed(() => page.props.details);
const eligibleScholars = computed(
    () =>
        page.props.eligibleScholars ?? {
            data: [],
            total: 0,
            per_page: 10,
            current_page: 1,
        },
);

const addForm = useForm({
    scholar_ids: [],
});

const payrollForm = useForm({
    recipients: [],
});
const removeForm = useForm({});
const statusForm = useForm({
    status: null,
    remarks: null,
});
const removingId = ref(null);

const isEditable = computed(() => details.value?.is_editable ?? true);
const statusMeta = computed(() => {
    const status = details.value?.status ?? "draft";

    return {
        draft: {
            label: "Draft",
            class: "bg-slate-50 text-slate-600",
        },
        submitted_payroll: {
            label: "Submitted Payroll",
            class: "bg-blue-50 text-blue-600",
        },
        rejected_payroll: {
            label: "Rejected Payroll",
            class: "bg-red-50 text-red-600",
        },
        approved_payroll: {
            label: "Approved Payroll",
            class: "bg-green-50 text-green-600",
        },
    }[status] ?? {
        label: status,
        class: "bg-slate-50 text-slate-600",
    };
});

const syncPayrollRows = () => {
    payrollRows.value = (page.props.payrollRecipients ?? []).map((row) => ({
        ...row,
        month_1: row.months?.month_1 ?? 0,
        month_2: row.months?.month_2 ?? 0,
        month_3: row.months?.month_3 ?? 0,
        month_4: row.months?.month_4 ?? 0,
        month_5: row.months?.month_5 ?? 0,
    }));
};

const uniqueOptions = (items, key) =>
    [...new Set(items.map((item) => item?.[key]).filter(Boolean))].sort((a, b) =>
        a.localeCompare(b),
    );

const eligibleProgramOptions = computed(() =>
    uniqueOptions(eligibleScholars.value.data ?? [], "program"),
);
const eligibleUniversityOptions = computed(() =>
    uniqueOptions(eligibleScholars.value.data ?? [], "university"),
);
const eligibleStatusOptions = computed(() =>
    uniqueOptions(eligibleScholars.value.data ?? [], "status"),
);
const payrollProgramOptions = computed(() => uniqueOptions(payrollRows.value, "program"));
const payrollUniversityOptions = computed(() =>
    uniqueOptions(payrollRows.value, "university"),
);
const payrollStatusOptions = computed(() =>
    uniqueOptions(payrollRows.value, "scholarship_status"),
);

const filteredPayrollRows = computed(() => {
    const search = (payrollSearch.value ?? "").toLowerCase();

    return payrollRows.value.filter((row) => {
        const matchesSearch =
            !search ||
            row.name?.toLowerCase().includes(search) ||
            row.spas_no?.toLowerCase().includes(search);
        const matchesProgram = !payrollProgram.value || row.program === payrollProgram.value;
        const matchesUniversity =
            !payrollUniversity.value || row.university === payrollUniversity.value;
        const matchesStatus =
            !payrollStatus.value || row.scholarship_status === payrollStatus.value;

        return matchesSearch && matchesProgram && matchesUniversity && matchesStatus;
    });
});

const reloadBatch = (extra = {}) => {
    if (!details.value?.id) return;

    router.reload({
        data: {
            id: details.value.id,
            eligible_search: eligibleSearch.value,
            eligible_program: eligibleProgram.value,
            eligible_university: eligibleUniversity.value,
            eligible_status: eligibleStatus.value,
            ...extra,
        },
        only: ["details", "eligibleScholars", "payrollRecipients"],
        preserveScroll: true,
        onSuccess: () => {
            selectedEligible.value = [];
            syncPayrollRows();
        },
    });
};

const loadEligiblePage = (event) => {
    reloadBatch({ eligible_page: event.page + 1 });
};

const addSelectedScholars = () => {
    if (!details.value?.id) return;

    addForm.scholar_ids = selectedEligible.value.map((item) => item.id);
    addForm.post(route("stipends.recipients.store", details.value.id), {
        preserveScroll: true,
        onSuccess: async () => {
            await nextTick();

            if (page.props.flash?.status === "error") {
                return;
            }

            activeTab.value = "payroll";
            reloadBatch();
        },
    });
};

const savePayroll = () => {
    if (!details.value?.id) return;

    payrollForm.recipients = payrollRows.value.map((row) => ({
        id: row.id,
        account_no: row.account_no,
        scholarship_status: row.scholarship_status,
        period: row.period,
        month_1: row.month_1 ?? 0,
        month_2: row.month_2 ?? 0,
        month_3: row.month_3 ?? 0,
        month_4: row.month_4 ?? 0,
        month_5: row.month_5 ?? 0,
        total_withheld: row.total_withheld ?? 0,
        remarks: row.remarks,
        learning_materials_amount: row.learning_materials_amount ?? 0,
        clothing_amount: row.clothing_amount ?? 0,
    }));

    payrollForm.put(route("stipends.payroll.update", details.value.id), {
        preserveScroll: true,
        onSuccess: () => reloadBatch(),
    });
};

const removeRecipient = (row) => {
    if (!row?.id) return;

    removingId.value = row.id;
    removeForm.delete(route("stipends.destroy", { id: row.id, type: "recipient" }), {
        preserveScroll: true,
        onSuccess: () => reloadBatch(),
        onFinish: () => {
            removingId.value = null;
        },
    });
};

const updateBatchStatus = (status) => {
    if (!details.value?.id) return;

    statusForm.status = status;
    statusForm.put(route("stipends.update", { id: details.value.id, type: "status" }), {
        preserveScroll: true,
        onSuccess: () => reloadBatch(),
    });
};

const rowTotal = (row) => {
    const stipend = [1, 2, 3, 4, 5].reduce(
        (sum, month) => sum + Number(row[`month_${month}`] ?? 0),
        0,
    );

    return (
        stipend +
        Number(row.learning_materials_amount ?? 0) +
        Number(row.clothing_amount ?? 0) -
        Number(row.total_withheld ?? 0)
    );
};

const formatMoney = (value) =>
    new Intl.NumberFormat("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value ?? 0);

watch(
    () => page.props.payrollRecipients,
    () => syncPayrollRows(),
    { immediate: true },
);

watch(
    () => [
        eligibleSearch.value,
        eligibleProgram.value,
        eligibleUniversity.value,
        eligibleStatus.value,
    ],
    () => {
        clearTimeout(searchTimer.value);
        searchTimer.value = setTimeout(() => reloadBatch({ eligible_page: 1 }), 350);
    },
);
</script>
