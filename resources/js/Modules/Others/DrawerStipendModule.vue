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
            <div class="flex min-w-0 items-center gap-3">
                <IconFileSpreadsheet :size="18" class="shrink-0 text-slate-500" />
                <div class="min-w-0 truncate text-sm font-semibold uppercase text-slate-700">
                    {{ details?.name ?? "Financial Assistance Batch" }}
                </div>
                <div
                    :class="[
                        statusMeta.class,
                        'shrink-0 rounded border px-2 py-1 text-[11px] font-semibold',
                    ]"
                >
                    {{ statusMeta.label }}
                </div>
            </div>
        </template>

        <template #default>
            <div class="flex flex-col w-full h-full gap-3">
                <Tabs v-model:value="activeTab" class="flex-1 flex flex-col min-h-0 compact-payroll-tabs">
                    <TabList class="!mb-2">
                        <Tab v-if="canBuildPayroll" value="eligible">
                            <span class="inline-flex items-center gap-1.5">
                                <IconCircleCheck :size="15" />
                                Validated
                            </span>
                        </Tab>
                        <Tab value="payroll">
                            <span class="inline-flex items-center gap-1.5">
                                <IconFileSpreadsheet :size="15" />
                                Payroll
                            </span>
                        </Tab>
                        <Tab value="activity">
                            <span class="inline-flex items-center gap-1.5">
                                <IconHistory :size="15" />
                                Activity
                            </span>
                        </Tab>
                    </TabList>

                    <TabPanels class="flex-1 min-h-0 !px-0">
                        <TabPanel v-if="canBuildPayroll" value="eligible" class="h-full">
                            <div class="flex flex-col h-full gap-3">
                                <div class="flex flex-col lg:flex-row lg:items-center justify-between gap-2">
                                    <div class="flex items-center gap-2 text-sm">
                                        <IconCircleCheck :size="18" class="text-green-600" />
                                        <div class="font-semibold">
                                            Validated Scholars
                                            <span class="ml-1 text-xs font-normal text-gray-500">
                                                Not yet in this payroll
                                            </span>
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
                                            :disabled="!selectedEligible.length || !batchPermissions.canEdit"
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
                                    <Column header="University" field="university">
                                        <template #body="props">
                                            <div class="min-w-56">
                                                {{ props.data.university || "N/A" }}
                                            </div>
                                        </template>
                                    </Column>
                                    <Column header="Scholarship Status" field="status" />

                                    <template #empty>
                                        <div class="py-6 text-center text-sm text-gray-500">
                                            No eligible scholars found.
                                        </div>
                                    </template>
                                </DataTable>
                            </div>
                        </TabPanel>

                        <TabPanel value="payroll" class="h-full">
                            <div class="flex flex-col h-full gap-2">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="min-w-0 text-sm font-semibold text-slate-700">
                                        Payroll Recipients
                                    </div>

                                    <div class="ml-auto flex flex-col xl:flex-row gap-1.5 xl:items-center">
                                        <InputText
                                            v-model="payrollSearch"
                                            placeholder="Search"
                                            class="!text-sm min-w-56"
                                        />
                                        <Select
                                            v-model="payrollProgram"
                                            :options="payrollProgramOptions"
                                            placeholder="Program"
                                            showClear
                                            class="!text-sm min-w-40"
                                        />
                                        <Select
                                            v-model="payrollUniversity"
                                            :options="payrollUniversityOptions"
                                            placeholder="University"
                                            showClear
                                            class="!text-sm min-w-48"
                                        />
                                        <Select
                                            v-model="payrollStatus"
                                            :options="payrollStatusOptions"
                                            placeholder="Status"
                                            showClear
                                            class="!text-sm min-w-36"
                                        />
                                        <DefaultButton
                                            v-if="canBuildPayroll"
                                            size="small"
                                            tooltip="Download Excel"
                                            severity="secondary"
                                            outlined
                                            :icon="IconFileSpreadsheet"
                                            :icon-size="17"
                                            class-name="!h-9 !w-9 !p-0"
                                            :loading="exportingPayroll === 'excel'"
                                            :disabled="!payrollRows.length || payrollForm.processing"
                                            @click="openExportDialog('excel')"
                                        />
                                        <DefaultButton
                                            v-if="canBuildPayroll"
                                            size="small"
                                            tooltip="Download PDF"
                                            severity="secondary"
                                            outlined
                                            :icon="IconFileTypePdf"
                                            :icon-size="17"
                                            class-name="!h-9 !w-9 !p-0"
                                            :loading="exportingPayroll === 'pdf'"
                                            :disabled="!payrollRows.length || payrollForm.processing"
                                            @click="openExportDialog('pdf')"
                                        />
                                        <DefaultButton
                                            v-if="canBuildPayroll"
                                            size="small"
                                            tooltip="Save"
                                            :icon="IconDeviceFloppy"
                                            :icon-size="17"
                                            class-name="!h-9 !w-9 !p-0"
                                            :loading="payrollForm.processing"
                                            :disabled="!payrollRows.length || !batchPermissions.canEdit"
                                            @click="savePayroll"
                                        />
                                    </div>
                                </div>

                                <div
                                    v-if="canMarkRecipientsForRemoval && forRemovalPayrollRows.length"
                                    class="rounded border border-amber-200 bg-amber-50/60 px-3 py-1.5 text-xs text-amber-800"
                                >
                                    {{ forRemovalPayrollRows.length }} scholar(s) marked for removal. Remove before submitting.
                                </div>

                                <div
                                    v-if="details?.showing_submitted_snapshot"
                                    class="rounded border border-slate-200 bg-slate-50 px-3 py-1.5 text-xs text-slate-600"
                                >
                                    Showing the last submitted payroll. Regional edits will appear after resubmission.
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
                                                <th
                                                    v-if="showRecipientActionColumn"
                                                    class="border px-2 py-2 text-center"
                                                >
                                                    Action
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template
                                                v-for="group in groupedPayrollRows"
                                                :key="group.program"
                                            >
                                                <tr
                                                    v-for="row in group.rows"
                                                    :key="row.id"
                                                    :class="row.is_for_removal ? 'bg-amber-50/50 text-slate-500' : ''"
                                                >
                                                    <td class="border px-2 py-1 min-w-36">
                                                        {{ row.account_no }}
                                                    </td>
                                                    <td class="border px-2 py-1 uppercase min-w-56">
                                                        {{ row.name }}
                                                        <div
                                                            v-if="row.is_for_removal"
                                                            class="mt-1 text-[10px] font-semibold normal-case text-amber-700"
                                                        >
                                                            For Removal
                                                        </div>
                                                    </td>
                                                    <td class="border px-2 py-1">{{ row.program }}</td>
                                                    <td class="border px-2 py-1 min-w-56">
                                                        {{ row.university }}
                                                    </td>
                                                    <td class="border px-2 py-1 min-w-44">
                                                        {{ row.scholarship_status }}
                                                        <div
                                                            v-if="!row.scholarship_status && !row.is_for_removal"
                                                            class="mt-1 max-w-44 text-[10px] leading-3 text-amber-600"
                                                        >
                                                            Assign a scholar standing before submitting payroll.
                                                        </div>
                                                    </td>
                                                    <td class="border px-2 py-1 min-w-44">
                                                        {{ row.period }}
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
                                                            :disabled="!batchPermissions.canEdit || row.is_for_removal"
                                                        />
                                                    </td>
                                                    <td class="border px-2 py-1">
                                                        <InputNumber
                                                            v-model="row.total_withheld"
                                                            inputClass="!text-xs !text-right w-28"
                                                            :min="0"
                                                            :minFractionDigits="2"
                                                            :maxFractionDigits="2"
                                                            :disabled="!batchPermissions.canEdit || row.is_for_removal"
                                                        />
                                                    </td>
                                                    <td class="border px-2 py-1">
                                                        <InputText
                                                            v-model="row.remarks"
                                                            class="!text-xs w-56"
                                                            :disabled="!batchPermissions.canEdit || row.is_for_removal"
                                                        />
                                                    </td>
                                                    <td class="border px-2 py-1">
                                                        <InputNumber
                                                            v-model="row.learning_materials_amount"
                                                            inputClass="!text-xs !text-right w-28"
                                                            :min="0"
                                                            :max="fixedAllowanceLimits.connectivity?.max_amount ?? undefined"
                                                            :minFractionDigits="2"
                                                            :maxFractionDigits="2"
                                                            :disabled="!batchPermissions.canEdit || row.is_for_removal"
                                                        />
                                                    </td>
                                                    <td class="border px-2 py-1">
                                                        <InputNumber
                                                            v-model="row.clothing_amount"
                                                            inputClass="!text-xs !text-right w-28"
                                                            :min="0"
                                                            :max="fixedAllowanceLimits.clothing?.max_amount ?? undefined"
                                                            :minFractionDigits="2"
                                                            :maxFractionDigits="2"
                                                            :disabled="!batchPermissions.canEdit || row.is_for_removal"
                                                        />
                                                    </td>
                                                    <td class="border px-2 py-1 text-right font-semibold">
                                                        {{ formatMoney(rowTotal(row)) }}
                                                    </td>
                                                    <td v-if="showRecipientActionColumn" class="border px-2 py-1 text-center">
                                                        <div v-if="canBuildPayroll" class="flex items-center justify-center gap-1">
                                                            <DefaultButton
                                                                v-if="row.is_for_removal"
                                                                size="small"
                                                                severity="secondary"
                                                                text
                                                                rounded
                                                                :icon="IconInfoCircle"
                                                                tooltip="View reason"
                                                                class-name="!text-amber-600 hover:!bg-amber-50"
                                                                @click="openRemovalReasonDialog(row)"
                                                            />
                                                            <DefaultButton
                                                                size="small"
                                                                severity="danger"
                                                                text
                                                                rounded
                                                                :icon="IconTrash"
                                                                tooltip="Remove from payroll"
                                                                :disabled="!batchPermissions.canEdit"
                                                                :loading="removeForm.processing && removingId === row.id"
                                                                @click="removeRecipient(row)"
                                                            />
                                                        </div>
                                                        <DefaultButton
                                                            v-else-if="canMarkRecipientsForRemoval"
                                                            size="small"
                                                            severity="secondary"
                                                            text
                                                            rounded
                                                            :icon="row.is_for_removal ? IconX : IconUserMinus"
                                                            :icon-size="18"
                                                            class-name="!text-slate-500 hover:!bg-slate-100 hover:!text-slate-700"
                                                            :tooltip="row.is_for_removal ? 'Cancel for removal' : 'Mark for removal'"
                                                            :loading="
                                                                row.is_for_removal
                                                                    ? cancelRemovalForm.processing && cancellingRemovalId === row.id
                                                                    : forRemovalForm.processing && markingForRemovalId === row.id
                                                            "
                                                            @click="row.is_for_removal ? cancelForRemoval(row) : openForRemovalDialog(row)"
                                                        />
                                                    </td>
                                                </tr>
                                                <tr class="bg-slate-50 font-semibold">
                                                    <td colspan="6" class="border px-2 py-1 text-right">
                                                        Sub-Total
                                                    </td>
                                                    <td
                                                        v-for="month in 5"
                                                        :key="`subtotal-${group.program}-${month}`"
                                                        class="border px-2 py-1 text-right"
                                                    >
                                                        {{ formatMoney(group.totals[`month_${month}`]) }}
                                                    </td>
                                                    <td class="border px-2 py-1 text-right">
                                                        {{ formatMoney(group.totals.total_withheld) }}
                                                    </td>
                                                    <td class="border px-2 py-1"></td>
                                                    <td class="border px-2 py-1 text-right">
                                                        {{ formatMoney(group.totals.learning_materials_amount) }}
                                                    </td>
                                                    <td class="border px-2 py-1 text-right">
                                                        {{ formatMoney(group.totals.clothing_amount) }}
                                                    </td>
                                                    <td class="border px-2 py-1 text-right">
                                                        {{ formatMoney(group.totals.grand_total) }}
                                                    </td>
                                                    <td v-if="showRecipientActionColumn" class="border px-2 py-1"></td>
                                                </tr>
                                            </template>
                                            <tr
                                                v-if="filteredPayrollRows.length"
                                                class="bg-slate-100 font-bold"
                                            >
                                                <td colspan="6" class="border px-2 py-1 text-right">
                                                    TOTAL
                                                </td>
                                                <td
                                                    v-for="month in 5"
                                                    :key="`grand-${month}`"
                                                    class="border px-2 py-1 text-right"
                                                >
                                                    {{ formatMoney(payrollGrandTotals[`month_${month}`]) }}
                                                </td>
                                                <td class="border px-2 py-1 text-right">
                                                    {{ formatMoney(payrollGrandTotals.total_withheld) }}
                                                </td>
                                                <td class="border px-2 py-1"></td>
                                                <td class="border px-2 py-1 text-right">
                                                    {{ formatMoney(payrollGrandTotals.learning_materials_amount) }}
                                                </td>
                                                <td class="border px-2 py-1 text-right">
                                                    {{ formatMoney(payrollGrandTotals.clothing_amount) }}
                                                </td>
                                                <td class="border px-2 py-1 text-right">
                                                    {{ formatMoney(payrollGrandTotals.grand_total) }}
                                                </td>
                                                <td v-if="showRecipientActionColumn" class="border px-2 py-1"></td>
                                            </tr>
                                            <tr v-if="!filteredPayrollRows.length">
                                                <td
                                                    :colspan="payrollColumnCount"
                                                    class="py-8 text-center text-gray-500"
                                                >
                                                    No payroll recipients found.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div
                                    v-if="hasPayrollFooter"
                                    class="grid gap-2 border-t border-slate-100 pt-2 xl:grid-cols-[minmax(18rem,0.8fr)_minmax(18rem,1fr)_auto] xl:items-end"
                                >
                                    <div
                                        v-if="showPayrollAttachment"
                                        class="min-w-0 rounded border border-slate-200 bg-white px-3 py-2 text-sm text-slate-700"
                                    >
                                        <div class="flex flex-wrap items-center justify-between gap-2">
                                            <div class="flex min-w-0 items-center gap-2">
                                                <IconFileTypePdf :size="17" class="shrink-0 text-slate-500" />
                                                <div class="min-w-0">
                                                    <div class="truncate text-sm text-slate-700">
                                                        {{ attachmentName }}
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="ml-auto flex min-w-0 items-center justify-end gap-2">
                                                <button
                                                    v-if="submissionPdf"
                                                    type="button"
                                                    class="inline-flex h-7 w-7 shrink-0 items-center justify-center rounded border border-slate-200 text-slate-500 hover:bg-slate-50 hover:text-slate-700"
                                                    @click="clearSubmissionPdf"
                                                >
                                                    <IconX :size="14" />
                                                </button>
                                                <DefaultButton
                                                    v-if="details?.payroll_file"
                                                    size="small"
                                                    label="Preview"
                                                    severity="secondary"
                                                    outlined
                                                    @click="openSubmittedPayrollPdf"
                                                />
                                                <label
                                                    v-if="batchPermissions.canSubmit"
                                                    class="inline-flex shrink-0 cursor-pointer items-center justify-center rounded border border-slate-300 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 hover:bg-slate-50"
                                                >
                                                    {{ details?.payroll_file ? "Replace" : "Choose" }}
                                                    <input
                                                        ref="submissionPdfInput"
                                                        type="file"
                                                        accept="application/pdf,.pdf"
                                                        class="hidden"
                                                        @change="selectSubmissionPdf"
                                                    />
                                                </label>
                                            </div>
                                        </div>

                                        <div
                                            v-if="submissionPdfError || statusForm.errors.payroll_file"
                                            class="mt-1 text-xs font-medium text-red-600"
                                        >
                                            {{ submissionPdfError || statusForm.errors.payroll_file }}
                                        </div>
                                    </div>
                                    <div v-else class="hidden xl:block"></div>
                                    <div
                                        v-if="hasReturnRemarks"
                                        class="min-w-0 rounded border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 lg:max-w-5xl"
                                    >
                                        <div class="flex min-w-0 items-start gap-2">
                                            <IconMessageReport
                                                :size="17"
                                                class="mt-0.5 shrink-0 text-slate-500"
                                            />
                                            <div class="min-w-0">
                                                <div class="flex flex-wrap items-center gap-x-2 gap-y-1">
                                                    <span class="shrink-0 font-semibold text-slate-700">
                                                        Return remarks
                                                    </span>
                                                    <span
                                                        v-if="details?.remarks_by || details?.remarks_at"
                                                        class="text-[11px] text-slate-500"
                                                    >
                                                        {{ details.remarks_by || "Scholarship staff" }}
                                                        <span v-if="details?.remarks_at">
                                                            | {{ details.remarks_at }}
                                                        </span>
                                                    </span>
                                                </div>
                                                <div class="mt-0.5 line-clamp-2 whitespace-pre-line text-xs leading-5 text-slate-600">
                                                    {{ details.remarks }}
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div v-else class="hidden xl:block"></div>
                                    <div
                                        v-if="batchPermissions.canSubmit || batchPermissions.canReject || batchPermissions.canApprove"
                                        class="flex flex-wrap justify-end gap-2 lg:pl-3"
                                    >
                                        <DefaultButton
                                            v-if="batchPermissions.canReject"
                                            size="small"
                                            label="Return Payroll"
                                            severity="danger"
                                            outlined
                                            :icon="IconX"
                                            :loading="statusForm.processing"
                                            @click="openRejectDialog"
                                        />
                                        <DefaultButton
                                            v-if="batchPermissions.canApprove"
                                            size="small"
                                            label="Approve Payroll"
                                            severity="success"
                                            :icon="IconChecks"
                                            :loading="statusForm.processing"
                                            @click="updateBatchStatus('approved_payroll')"
                                        />
                                        <DefaultButton
                                            v-if="batchPermissions.canSubmit"
                                            size="small"
                                            label="Submit Payroll"
                                            severity="success"
                                            :icon="IconSend"
                                            :loading="statusForm.processing"
                                            :disabled="!payrollRows.length || !submissionPdf"
                                            @click="openSubmitConfirmDialog"
                                        />
                                    </div>
                                </div>
                            </div>
                        </TabPanel>
                        <TabPanel value="activity" class="h-full">
                            <div class="flex h-full flex-col gap-2">
                                <div class="flex items-center justify-between gap-2">
                                    <div class="text-sm font-semibold text-slate-700">Activity Log</div>
                                    <div class="text-xs text-slate-500">{{ activityLogs.length }} event(s)</div>
                                </div>
                                <div class="flex-1 overflow-auto rounded border border-slate-200 bg-white">
                                    <table class="w-full min-w-[900px] text-xs">
                                        <thead class="sticky top-0 bg-slate-50 text-left text-[11px] uppercase text-slate-500">
                                            <tr>
                                                <th class="border-b px-3 py-2 font-semibold">Date</th>
                                                <th class="border-b px-3 py-2 font-semibold">Activity</th>
                                                <th class="border-b px-3 py-2 font-semibold">Scholar</th>
                                                <th class="border-b px-3 py-2 font-semibold">By</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr
                                                v-for="log in activityLogs"
                                                :key="log.id"
                                                class="border-b border-slate-100 last:border-b-0"
                                            >
                                                <td class="whitespace-nowrap px-3 py-2 text-slate-500">
                                                    {{ log.created_at || "-" }}
                                                </td>
                                                <td class="px-3 py-2">
                                                    <div class="font-medium text-slate-700">{{ log.label }}</div>
                                                    <div
                                                        v-if="log.remarks"
                                                        class="mt-0.5 max-w-xl truncate text-[11px] text-slate-500"
                                                        :title="log.remarks"
                                                    >
                                                        {{ log.remarks }}
                                                    </div>
                                                </td>
                                                <td class="px-3 py-2 uppercase text-slate-600">
                                                    {{ log.scholar_name || "-" }}
                                                </td>
                                                <td class="whitespace-nowrap px-3 py-2 text-slate-500">
                                                    {{ log.created_by || "System" }}
                                                </td>
                                            </tr>
                                            <tr v-if="!activityLogs.length">
                                                <td colspan="5" class="py-8 text-center text-sm text-gray-500">
                                                    No payroll activity recorded yet.
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

    <Dialog
        v-model:visible="rejectDialog"
        modal
        header="Return Payroll"
        :style="{ width: '32rem' }"
    >
        <div class="flex flex-col gap-2">
            <label class="text-sm font-medium" for="reject-remarks">Remarks</label>
            <Textarea
                id="reject-remarks"
                v-model="rejectRemarks"
                rows="5"
                class="w-full !text-sm"
                placeholder="Explain why this payroll is returned."
                autoResize
            />
            <small v-if="rejectRemarksError" class="text-red-500">
                Remarks are required when returning a payroll.
            </small>
        </div>

        <template #footer>
            <DefaultButton
                size="small"
                label="Cancel"
                severity="secondary"
                outlined
                @click="rejectDialog = false"
            />
            <DefaultButton
                size="small"
                label="Return"
                severity="danger"
                :icon="IconX"
                :loading="statusForm.processing"
                @click="submitReject"
            />
        </template>
    </Dialog>

    <Dialog
        v-model:visible="submitConfirmDialog"
        modal
        header="Submit Payroll"
        :style="{ width: '32rem' }"
    >
        <div class="space-y-2 text-sm text-slate-600">
            <p>
                Submit this payroll for review? Please confirm that the uploaded PDF is the scanned payroll with complete signatories.
            </p>
            <div
                v-if="submissionPdf"
                class="flex items-center justify-between gap-2 rounded-md border border-red-100 bg-red-50 px-3 py-2 text-xs"
            >
                <span class="min-w-0 truncate font-medium text-slate-700">{{ submissionPdf.name }}</span>
                <span class="shrink-0 text-slate-500">{{ formatFileSize(submissionPdf.size) }}</span>
            </div>
        </div>

        <template #footer>
            <DefaultButton
                size="small"
                label="Cancel"
                severity="secondary"
                outlined
                @click="submitConfirmDialog = false"
            />
            <DefaultButton
                size="small"
                label="Submit"
                severity="success"
                :icon="IconSend"
                :loading="statusForm.processing"
                @click="confirmSubmitPayroll"
            />
        </template>
    </Dialog>

    <Dialog
        v-model:visible="forRemovalSubmitDialog"
        modal
        header="Remove Marked Scholars"
        :style="{ width: '28rem' }"
    >
        <div class="space-y-2 text-sm text-slate-600">
            <p>
                Remove scholars marked for removal from the payroll before submitting.
            </p>
            <div class="rounded border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                {{ forRemovalPayrollRows.length }} scholar(s) marked for removal still on the list.
            </div>
        </div>

        <template #footer>
            <DefaultButton
                size="small"
                label="OK"
                severity="secondary"
                @click="forRemovalSubmitDialog = false"
            />
        </template>
    </Dialog>

    <Dialog
        v-model:visible="removalReasonDialog"
        modal
        header="For Removal Reason"
        class="w-[92vw] max-w-[480px]"
        :pt="{
            header: '!px-5 !pt-4 !pb-2',
            content: '!px-5 !py-2',
            footer: '!px-5 !pt-2 !pb-4',
        }"
    >
        <div v-if="removalReasonTarget" class="space-y-3 text-sm text-slate-700">
            <div class="flex items-center gap-2 text-base font-bold uppercase text-slate-800">
                <IconUserMinus :size="18" class="shrink-0 text-amber-600" />
                <span>{{ removalReasonTarget.name }}</span>
            </div>
            <div class="rounded border border-amber-200 bg-amber-50 px-3 py-2 text-sm text-amber-900">
                {{ removalReasonTarget.for_removal_reason || "No reason provided." }}
            </div>
            <div class="grid gap-1 text-xs text-slate-500">
                <div v-if="removalReasonTarget.for_removal_by">
                    Marked by {{ removalReasonTarget.for_removal_by }}
                </div>
                <div v-if="removalReasonTarget.for_removal_at">
                    {{ removalReasonTarget.for_removal_at }}
                </div>
            </div>
        </div>

        <template #footer>
            <DefaultButton
                size="small"
                label="Close"
                severity="secondary"
                outlined
                @click="removalReasonDialog = false"
            />
        </template>
    </Dialog>

    <Dialog
        v-model:visible="forRemovalDialog"
        modal
        header="For Removal"
        :style="{ width: '32rem' }"
        :pt="{
            header: '!px-5 !pt-4 !pb-2',
            content: '!px-5 !py-2',
            footer: '!px-5 !pt-2 !pb-4',
        }"
    >
        <div class="flex flex-col gap-3">
            <div v-if="forRemovalTarget" class="flex items-center gap-2 text-base font-bold uppercase text-slate-800">
                <IconUserMinus :size="18" class="shrink-0 text-amber-600" />
                <span>{{ forRemovalTarget.name }}</span>
            </div>
            <div class="flex flex-col gap-1.5">
                <label class="text-sm font-medium text-slate-700" for="for-removal-remarks">Reason</label>
                <Textarea
                    id="for-removal-remarks"
                    v-model="forRemovalRemarks"
                    rows="5"
                    class="w-full !text-sm"
                    placeholder="Reason"
                    autoResize
                />
            </div>
            <small v-if="forRemovalRemarksError" class="text-red-500">
                Reason is required when marking a scholar for removal.
            </small>
        </div>

        <template #footer>
            <DefaultButton
                size="small"
                label="Cancel"
                severity="secondary"
                outlined
                @click="forRemovalDialog = false"
            />
            <DefaultButton
                size="small"
                label="Mark for Removal"
                severity="warning"
                :icon="IconUserMinus"
                :icon-size="18"
                class-name="!bg-amber-500 !border-amber-500 hover:!bg-amber-400 hover:!border-amber-400 active:!bg-amber-600 active:!border-amber-600 !text-white"
                :loading="forRemovalForm.processing"
                @click="submitForRemoval"
            />
        </template>
    </Dialog>

    <Dialog
        v-model:visible="exportDialog"
        modal
        header="Payroll Signatories"
        :style="{ width: '36rem' }"
    >
        <div class="flex flex-col gap-3">
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium">Prepared by</label>
                <MultiSelect
                    v-model="preparedBy"
                    :options="signatoryOptions"
                    optionLabel="name"
                    placeholder="Select prepared by"
                    filter
                    display="chip"
                    :maxSelectedLabels="3"
                    class="w-full !text-sm"
                >
                    <template #option="slotProps">
                        <div class="flex flex-col">
                            <span class="text-sm font-medium">{{ slotProps.option.name }}</span>
                            <span class="text-xs text-gray-500">
                                {{ slotProps.option.designation || "No designation" }}
                            </span>
                        </div>
                    </template>
                </MultiSelect>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium">Noted by</label>
                <Select
                    v-model="notedBy"
                    :options="signatoryOptions"
                    optionLabel="name"
                    placeholder="Select noted by"
                    filter
                    class="w-full !text-sm"
                >
                    <template #option="slotProps">
                        <div class="flex flex-col">
                            <span class="text-sm font-medium">{{ slotProps.option.name }}</span>
                            <span class="text-xs text-gray-500">
                                {{ slotProps.option.designation || "No designation" }}
                            </span>
                        </div>
                    </template>
                </Select>
            </div>
            <div class="flex flex-col gap-1">
                <label class="text-sm font-medium">Certified correct</label>
                <Select
                    v-model="certifiedBy"
                    :options="signatoryOptions"
                    optionLabel="name"
                    placeholder="Select certified correct"
                    filter
                    class="w-full !text-sm"
                >
                    <template #option="slotProps">
                        <div class="flex flex-col">
                            <span class="text-sm font-medium">{{ slotProps.option.name }}</span>
                            <span class="text-xs text-gray-500">
                                {{ slotProps.option.designation || "No designation" }}
                            </span>
                        </div>
                    </template>
                </Select>
            </div>
            <small v-if="signatoryError" class="text-red-500">
                Select prepared by, noted by, and certified correct before exporting.
            </small>
        </div>

        <template #footer>
            <DefaultButton
                size="small"
                label="Cancel"
                severity="secondary"
                outlined
                @click="exportDialog = false"
            />
            <DefaultButton
                size="small"
                label="Export"
                :icon="pendingExportFormat === 'pdf' ? IconFileTypePdf : IconFileSpreadsheet"
                :loading="Boolean(exportingPayroll)"
                @click="confirmExport"
            />
        </template>
    </Dialog>
</template>

<script setup>
import {
    IconChecks,
    IconCircleCheck,
    IconDeviceFloppy,
    IconFileSpreadsheet,
    IconFileTypePdf,
    IconHistory,
    IconInfoCircle,
    IconMessageReport,
    IconSend,
    IconTrash,
    IconUserMinus,
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
const rejectDialog = ref(false);
const rejectRemarks = ref("");
const rejectRemarksError = ref(false);
const submitConfirmDialog = ref(false);
const forRemovalSubmitDialog = ref(false);
const exportingPayroll = ref(null);
const submissionPdf = ref(null);
const submissionPdfError = ref("");
const submissionPdfInput = ref(null);
const exportDialog = ref(false);
const pendingExportFormat = ref("excel");
const preparedBy = ref([]);
const notedBy = ref(null);
const certifiedBy = ref(null);
const signatoryError = ref(false);
const forRemovalDialog = ref(false);
const forRemovalTarget = ref(null);
const forRemovalRemarks = ref("");
const forRemovalRemarksError = ref(false);
const removalReasonDialog = ref(false);
const removalReasonTarget = ref(null);
const markingForRemovalId = ref(null);

const details = computed(() => page.props.details);
const activityLogs = computed(() => details.value?.activity_logs ?? []);
const hasReturnRemarks = computed(
    () => details.value?.status === "rejected_payroll" && Boolean(details.value?.remarks),
);
const shouldShowPayrollAttachment = computed(() =>
    ["submitted_payroll", "resubmitted_payroll", "rejected_payroll", "approved_payroll"].includes(
        details.value?.status,
    ),
);
const showPayrollAttachment = computed(() =>
    batchPermissions.value.canSubmit ||
    Boolean(details.value?.payroll_file) ||
    shouldShowPayrollAttachment.value,
);
const hasPayrollFooter = computed(() =>
    showPayrollAttachment.value ||
    hasReturnRemarks.value ||
    batchPermissions.value.canReject ||
    batchPermissions.value.canApprove,
);
const attachmentName = computed(() => {
    if (submissionPdf.value) {
        return submissionPdf.value.name;
    }

    if (details.value?.payroll_file?.name) {
        return details.value.payroll_file.name;
    }

    return "No file selected";
});
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
const forRemovalForm = useForm({
    remarks: null,
});
const cancelRemovalForm = useForm({});
const statusForm = useForm({
    status: null,
    remarks: null,
    payroll_file: null,
});
const removingId = ref(null);
const cancellingRemovalId = ref(null);

const batchPermissions = computed(
    () =>
        details.value?.permissions ?? {
            canEdit: false,
            canSubmit: false,
            canApprove: false,
            canReject: false,
            canDelete: false,
        },
);
const canBuildPayroll = computed(() => batchPermissions.value.canEdit);
const canMarkRecipientsForRemoval = computed(
    () =>
        batchPermissions.value.canReject &&
        ["submitted_payroll", "resubmitted_payroll"].includes(details.value?.status),
);
const showRecipientActionColumn = computed(
    () => canBuildPayroll.value || canMarkRecipientsForRemoval.value,
);
const payrollDescription = computed(() =>
    canBuildPayroll.value
        ? "Edit, export, and submit payroll details ."
        : "Review the submitted payroll details.",
);
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
        resubmitted_payroll: {
            label: "Resubmitted Payroll",
            class: "bg-cyan-50 text-cyan-600",
        },
        rejected_payroll: {
            label: "Returned Payroll",
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

const statusLabel = (status) =>
    ({
        draft: "Draft",
        pending: "Pending",
        submitted: "Submitted",
        resubmitted_payroll: "Resubmitted Payroll",
        approved: "Approved",
        rejected: "Rejected",
        for_removal: "For Removal",
        submitted_payroll: "Submitted Payroll",
        rejected_payroll: "Returned Payroll",
        approved_payroll: "Approved Payroll",
        for_removal_from_payroll: "For Removal",
    })[status] ?? status;

const fixedAllowanceLimits = computed(() => page.props.allowanceLimits ?? {});
const signatoryOptions = computed(() => page.props.signatoryOptions ?? []);

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

const payrollColumnCount = computed(() => 16 + (showRecipientActionColumn.value ? 1 : 0));

const emptyPayrollTotals = () => ({
    month_1: 0,
    month_2: 0,
    month_3: 0,
    month_4: 0,
    month_5: 0,
    total_withheld: 0,
    learning_materials_amount: 0,
    clothing_amount: 0,
    grand_total: 0,
});

const addRowToTotals = (totals, row) => {
    [1, 2, 3, 4, 5].forEach((month) => {
        totals[`month_${month}`] += Number(row[`month_${month}`] ?? 0);
    });

    totals.total_withheld += Number(row.total_withheld ?? 0);
    totals.learning_materials_amount += Number(row.learning_materials_amount ?? 0);
    totals.clothing_amount += Number(row.clothing_amount ?? 0);

    totals.grand_total += rowTotal(row);

    return totals;
};

const groupedPayrollRows = computed(() => {
    const groups = new Map();

    filteredPayrollRows.value.forEach((row) => {
        const program = row.program || "NO PROGRAM";

        if (!groups.has(program)) {
            groups.set(program, {
                program,
                rows: [],
                totals: emptyPayrollTotals(),
            });
        }

        const group = groups.get(program);
        group.rows.push(row);

        if (!row.is_for_removal) {
            addRowToTotals(group.totals, row);
        }
    });

    return [...groups.values()];
});

const payrollGrandTotals = computed(() =>
    filteredPayrollRows.value.filter((row) => !row.is_for_removal).reduce(
        (totals, row) => addRowToTotals(totals, row),
        emptyPayrollTotals(),
    ),
);

const forRemovalPayrollRows = computed(() =>
    payrollRows.value.filter((row) => row.is_for_removal),
);

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
        only: ["details", "eligibleScholars", "payrollRecipients", "allowanceLimits", "signatoryOptions"],
        preserveScroll: true,
        onSuccess: () => {
            selectedEligible.value = [];
            syncPayrollRows();
        },
    });
};

const loadEligiblePage = (event) => {
    if (!canBuildPayroll.value) return;

    reloadBatch({ eligible_page: event.page + 1 });
};

const addSelectedScholars = () => {
    if (!details.value?.id || !canBuildPayroll.value) return;

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

const buildPayrollRecipients = async () => {
    await nextTick();

    return payrollRows.value.map((row) => ({
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
};

const savePayroll = async (onSuccess = null) => {
    if (!details.value?.id || !canBuildPayroll.value) return;

    const successCallback = typeof onSuccess === "function" ? onSuccess : null;

    payrollForm.recipients = await buildPayrollRecipients();
    payrollForm.clearErrors();
    payrollForm.put(route("stipends.payroll.update", details.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            if (successCallback) {
                successCallback();
                return;
            }

            reloadBatch();
        },
        onError: () => {
            exportingPayroll.value = null;
        },
    });
};

const downloadPayroll = (format = "excel") => {
    if (!details.value?.id) return;

    exportingPayroll.value = null;
    exportDialog.value = false;
    const url = route("stipends.export", details.value.id);
    const params = new URLSearchParams();

    if (format === "pdf") {
        params.set("format", "pdf");
    }

    preparedBy.value.forEach((signatory) => {
        params.append("prepared_by[]", signatory.id);
    });
    params.set("noted_by", notedBy.value.id);
    params.set("certified_by", certifiedBy.value.id);

    const query = params.toString();
    window.location.href = query ? `${url}?${query}` : url;
};

const openExportDialog = (format = "excel") => {
    if (!details.value?.id || !payrollRows.value.length) return;

    pendingExportFormat.value = format;
    signatoryError.value = false;
    exportDialog.value = true;
};

const confirmExport = () => {
    if (!preparedBy.value.length || !notedBy.value || !certifiedBy.value) {
        signatoryError.value = true;
        return;
    }

    const format = pendingExportFormat.value;
    exportingPayroll.value = format;

    if (canBuildPayroll.value && batchPermissions.value.canEdit) {
        savePayroll(() => downloadPayroll(format));
        return;
    }

    downloadPayroll(format);
};

const removeRecipient = (row) => {
    if (!row?.id || !canBuildPayroll.value) return;

    removingId.value = row.id;
    removeForm.delete(route("stipends.destroy", { id: row.id, type: "recipient" }), {
        preserveScroll: true,
        onSuccess: () => reloadBatch({ eligible_page: 1 }),
        onFinish: () => {
            removingId.value = null;
        },
    });
};

const openForRemovalDialog = (row) => {
    if (!row?.id || !canMarkRecipientsForRemoval.value || row.is_for_removal) return;

    forRemovalTarget.value = row;
    forRemovalRemarks.value = "";
    forRemovalRemarksError.value = false;
    forRemovalDialog.value = true;
};

const openRemovalReasonDialog = (row) => {
    if (!row?.is_for_removal) return;

    removalReasonTarget.value = row;
    removalReasonDialog.value = true;
};

const submitForRemoval = () => {
    if (!forRemovalTarget.value?.id) return;

    if (!forRemovalRemarks.value?.trim()) {
        forRemovalRemarksError.value = true;
        return;
    }

    forRemovalRemarksError.value = false;
    markingForRemovalId.value = forRemovalTarget.value.id;
    forRemovalForm.remarks = forRemovalRemarks.value.trim();
    forRemovalForm.put(route("stipends.recipients.mark-for-removal", forRemovalTarget.value.id), {
        preserveScroll: true,
        onSuccess: () => {
            forRemovalDialog.value = false;
            forRemovalTarget.value = null;
            forRemovalRemarks.value = "";
            activeTab.value = "payroll";
            reloadBatch({ eligible_page: 1 });
        },
        onFinish: () => {
            markingForRemovalId.value = null;
        },
    });
};

const cancelForRemoval = (row) => {
    if (!row?.id || !canMarkRecipientsForRemoval.value || !row.is_for_removal) return;

    cancellingRemovalId.value = row.id;
    cancelRemovalForm.put(route("stipends.recipients.cancel-removal", row.id), {
        preserveScroll: true,
        onSuccess: () => {
            activeTab.value = "payroll";
            reloadBatch({ eligible_page: 1 });
        },
        onFinish: () => {
            cancellingRemovalId.value = null;
        },
    });
};

const openRejectDialog = () => {
    rejectRemarks.value = "";
    rejectRemarksError.value = false;
    rejectDialog.value = true;
};

const submitReject = () => {
    if (!rejectRemarks.value?.trim()) {
        rejectRemarksError.value = true;
        return;
    }

    rejectRemarksError.value = false;
    updateBatchStatus("rejected_payroll", false, rejectRemarks.value.trim());
};

const openSubmittedPayrollPdf = () => {
    if (!details.value?.payroll_file?.url) return;

    window.open(details.value.payroll_file.url, "_blank", "noopener,noreferrer");
};

const openSubmitConfirmDialog = () => {
    if (forRemovalPayrollRows.value.length) {
        forRemovalSubmitDialog.value = true;
        activeTab.value = "payroll";
        return;
    }

    if (!submissionPdf.value) {
        submissionPdfError.value = "Upload a PDF file before submitting payroll.";
        return;
    }

    submissionPdfError.value = "";
    submitConfirmDialog.value = true;
};

const confirmSubmitPayroll = () => {
    updateBatchStatus(
        details.value?.status === "rejected_payroll"
            ? "resubmitted_payroll"
            : "submitted_payroll",
    );
};

const selectSubmissionPdf = (event) => {
    const file = event.target.files?.[0] ?? null;
    submissionPdf.value = null;
    submissionPdfError.value = "";

    if (!file) {
        return;
    }

    if (file.type !== "application/pdf" && !file.name.toLowerCase().endsWith(".pdf")) {
        submissionPdfError.value = "Only PDF files are allowed.";
        event.target.value = "";
        return;
    }

    submissionPdf.value = file;
};

const clearSubmissionPdf = () => {
    submissionPdf.value = null;
    submissionPdfError.value = "";

    if (submissionPdfInput.value) {
        submissionPdfInput.value.value = "";
    }
};

const formatFileSize = (size) => {
    if (!size) return "0 Bytes";

    const units = ["Bytes", "KB", "MB", "GB"];
    const index = Math.min(Math.floor(Math.log(size) / Math.log(1024)), units.length - 1);

    return `${(size / Math.pow(1024, index)).toFixed(2)} ${units[index]}`;
};

const updateBatchStatus = async (status, shouldSaveFirst = true, remarks = null) => {
    if (!details.value?.id) return;

    if (["submitted_payroll", "resubmitted_payroll"].includes(status) && !submissionPdf.value) {
        submissionPdfError.value = "Upload a PDF file before submitting payroll.";
        return;
    }

    if (
        ["submitted_payroll", "resubmitted_payroll"].includes(status) &&
        batchPermissions.value.canEdit &&
        shouldSaveFirst
    ) {
        savePayroll(() => updateBatchStatus(status, false));
        return;
    }

    statusForm.status = status;
    statusForm.remarks = remarks;
    statusForm.payroll_file = ["submitted_payroll", "resubmitted_payroll"].includes(status)
        ? submissionPdf.value
        : null;
    statusForm
        .transform((data) => ({
            ...data,
            _method: "put",
        }))
        .post(route("stipends.update", { id: details.value.id, type: "status" }), {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            rejectDialog.value = false;
            submitConfirmDialog.value = false;
            rejectRemarks.value = "";
            submissionPdf.value = null;
            submissionPdfError.value = "";
            reloadBatch();
        },
        onError: (errors) => {
            submissionPdfError.value = errors.payroll_file ?? "";
        },
    });
};

const rowTotal = (row) => {
    const stipend = [1, 2, 3, 4, 5].reduce(
        (sum, month) => sum + Number(row[`month_${month}`] ?? 0),
        0,
    );

    return (
        stipend +
        Number(row.total_withheld ?? 0) +
        Number(row.learning_materials_amount ?? 0) +
        Number(row.clothing_amount ?? 0)
    );
};

const formatMoney = (value) =>
    new Intl.NumberFormat("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    }).format(value ?? 0);

watch(
    () => page.props.payrollRecipients,
    () => {
        syncPayrollRows();

        if (!canBuildPayroll.value) {
            activeTab.value = "payroll";
        }
    },
    { immediate: true },
);

watch(
    canBuildPayroll,
    (canEdit) => {
        if (!canEdit) {
            activeTab.value = "payroll";
        }
    },
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
        if (!canBuildPayroll.value) return;

        clearTimeout(searchTimer.value);
        searchTimer.value = setTimeout(() => reloadBatch({ eligible_page: 1 }), 350);
    },
);
</script>

<style scoped>
:deep(.compact-payroll-tabs .p-tablist-tab-list) {
    gap: 0.25rem;
}

:deep(.compact-payroll-tabs .p-tab) {
    padding: 0.45rem 0.8rem;
    font-size: 0.8125rem;
}

:deep(.compact-payroll-tabs .p-tabpanels) {
    padding-top: 0.25rem;
}

:deep(.compact-payroll-tabs .p-inputtext),
:deep(.compact-payroll-tabs .p-select) {
    min-height: 2.25rem;
}

:deep(.compact-payroll-tabs .p-inputnumber-input) {
    padding-block: 0.35rem;
}
</style>

