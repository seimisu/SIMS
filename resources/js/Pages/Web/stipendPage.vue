<template>
    <Head title="Financial Assistance" />
    <AuthLayout>
        <div class="financial-assistance-page flex h-full min-h-0 w-full flex-col gap-4 overflow-hidden text-slate-800 dark:text-gray-100">
            <div class="shrink-0 border-b border-slate-200 pb-4 dark:border-gray-700">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="flex min-w-0 items-start gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-md bg-blue-600 text-white shadow-sm dark:bg-blue-500">
                            <IconBuildingBank :size="21" stroke-width="1.8" />
                        </div>
                        <HeaderModule
                            class="dark:text-gray-100"
                            title="Financial Assistance"
                            description="Manage payroll preparation, review, approval, and deposit progress."
                        />
                    </div>

                    <DefaultButton
                        v-if="canImportHistoricalPayroll"
                        size="small"
                        label="Import Historical"
                        severity="secondary"
                        outlined
                        :icon="IconFileImport"
                        class="self-start xl:self-auto"
                        @click="openHistoricalImportDialog"
                    />
                </div>
            </div>

            <div class="flex min-h-0 flex-1 flex-col gap-3">
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
                    @selected="openReview"
                    @paginate="loadPage"
                >
                    <template #header>
                        <ManagementFilterBar v-model="searchInput" search-placeholder="Search payroll or region">
                            <SelectInput
                                v-model="filterRegion"
                                :options="page.props.agencyOption ?? []"
                                placeholder="Region"
                                clearable
                                filter
                                capitalize
                                :disable="isRegionLocked"
                                class="w-full sm:w-44"
                            />
                            <SelectInput
                                v-model="filterTerm"
                                :options="page.props.termOptions ?? []"
                                placeholder="Semester"
                                clearable
                                class="w-full sm:w-40"
                            />
                            <SelectInput
                                v-model="filterAcademicYear"
                                :options="page.props.academicYearOptions ?? []"
                                placeholder="Academic Year"
                                clearable
                                class="w-full sm:w-44"
                            />
                            <SelectInput
                                v-model="filterStatus"
                                :options="page.props.statusOptions ?? []"
                                placeholder="Status"
                                clearable
                                class="w-full sm:w-40"
                            />
                            <DefaultButton
                                size="small"
                                severity="secondary"
                                :icon="IconFilterOff"
                                tooltip="Clear filters"
                                :disabled="!hasBatchFilters"
                                class="justify-self-start xl:justify-self-end"
                                @click="clearBatchFilters"
                            />
                        </ManagementFilterBar>
                    </template>

                    <Column header="File Name">
                        <template #body="props">
                            <div class="flex min-w-64 items-center gap-3 py-1">
                                <div class="flex size-8 shrink-0 items-center justify-center rounded border border-slate-200 bg-slate-50 text-slate-500 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-300">
                                    <IconFileInvoice size="18" stroke-width="1.6" />
                                </div>
                                <div class="min-w-0">
                                    <div class="truncate text-sm font-semibold text-slate-800 dark:text-gray-100">
                                        {{ props.data.name }}
                                    </div>
                                    <div v-if="props.data.is_historical" class="mt-0.5 text-[11px] font-medium text-violet-600 dark:text-violet-300">
                                        Historical record
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column header="Region" field="region" />
                    <Column header="Sem & AY">
                        <template #body="props">
                            <div class="min-w-44 text-sm">
                                <span class="font-medium">{{ props.data.term }}</span>
                                <span class="text-slate-400 dark:text-gray-500"> / </span>
                                <span>{{ props.data.sy }}</span>
                            </div>
                        </template>
                    </Column>
                    <Column header="No. of Scholars">
                        <template #body="props">
                            <div class="text-left font-semibold text-slate-700 dark:text-gray-200">
                                {{ props.data.scholars_count ?? 0 }}/{{ props.data.scholars_limit ?? 300 }}
                            </div>
                        </template>
                    </Column>
                    <Column header="Status">
                        <template #body="props">
                            <div class="flex justify-start">
                                <div
                                    :class="[
                                        batchStatusMeta(props.data.status).class,
                                        'inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 font-medium',
                                    ]"
                                >
                                    <span class="size-1.5 rounded-full bg-current opacity-80"></span>
                                    <div class="text-[11px]">
                                        {{ batchStatusMeta(props.data.status).label }}
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column header="Deposit">
                        <template #body="props">
                            <button
                                type="button"
                                class="inline-flex min-w-16 items-center justify-center gap-1.5 rounded border border-slate-200 bg-white px-2.5 py-1.5 text-xs font-semibold text-slate-700 transition hover:border-blue-300 hover:bg-blue-50 hover:text-blue-700 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-200 dark:hover:border-blue-800 dark:hover:bg-blue-900/30 dark:hover:text-blue-300"
                                :class="{ 'cursor-not-allowed opacity-60': !hasMonthlyCredits(props.data) }"
                                :disabled="!hasMonthlyCredits(props.data)"
                                v-tooltip.top="hasMonthlyCredits(props.data) ? 'View monthly deposit' : 'No monthly deposit yet'"
                                @click.stop="openCreditDialog(props.data)"
                            >
                                <IconChecks size="16" stroke-width="1.7" />
                                {{ props.data.credit_summary?.credited ?? 0 }}/{{ props.data.credit_summary?.total ?? 5 }}
                            </button>
                        </template>
                    </Column>
                    <Column header="Actions">
                        <template #body="props">
                            <div class="flex justify-start">
                                <Button
                                    text
                                    rounded
                                    size="small"
                                    severity="secondary"
                                    v-tooltip.top="'Actions'"
                                    @click.stop="toggleActionMenu($event, props.data)"
                                >
                                    <IconDotsVertical size="20" />
                                </Button>
                            </div>
                        </template>
                    </Column>
                </DefaultSelectionTable>
                <Menu
                    ref="actionMenu"
                    :model="actionMenuItems"
                    :popup="true"
                    :pt="{
                        root: 'dark:!border-gray-700 dark:!bg-gray-800 dark:!text-gray-100',
                        list: 'dark:!bg-gray-800',
                        itemContent: 'dark:hover:!bg-gray-700',
                        itemLink: 'dark:!text-gray-100',
                        itemLabel: 'dark:!text-gray-100',
                    }"
                >
                    <template #item="{ item, props: itemProps }">
                        <a
                            v-ripple
                            class="flex items-center gap-2"
                            :class="{ 'pointer-events-none opacity-50': item.disabled }"
                            v-bind="itemProps.action"
                        >
                            <component
                                :is="item.icon"
                                :class="item.class"
                                size="18"
                                stroke-width="1.5"
                            />
                            <span class="text-xs">{{ item.label }}</span>
                        </a>
                    </template>
                </Menu>
            </div>
        </div>
    </AuthLayout>

    <DrawerStipendModule v-model:visible="stipendDrawer" />

    <Drawer
        v-model:visible="historicalPreviewDrawer"
        position="full"
        :pt="{
            root: '!bg-white !text-slate-800 dark:!bg-gray-900 dark:!text-gray-100',
            header: 'border-b-1 !bg-white border-gray-300 border-dashed !text-slate-800 dark:!border-gray-700 dark:!bg-gray-900 dark:!text-gray-100',
            content: '!p-3 !bg-white !text-slate-800 dark:!bg-gray-900 dark:!text-gray-100',
            footer: '!bg-white dark:!bg-gray-900',
        }"
    >
        <template #header>
            <div class="flex min-w-0 items-center gap-3">
                <IconFileSpreadsheet :size="18" class="shrink-0 text-slate-500 dark:text-gray-400" />
                <div class="min-w-0 truncate text-sm font-semibold uppercase text-slate-700 dark:text-gray-100">
                    {{ historicalImportPreview?.file_name ?? "Historical Payroll Preview" }}
                </div>
                <div class="shrink-0 rounded border border-amber-300 bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-700 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200">
                    Preview Only
                </div>
            </div>
        </template>

        <template #default>
            <div class="flex h-full w-full flex-col gap-3">
                <Tabs value="payroll" class="compact-payroll-tabs flex min-h-0 flex-1 flex-col">
                    <TabList class="!mb-2 !bg-white dark:!bg-gray-900">
                        <Tab value="payroll">
                            <span class="inline-flex items-center gap-1.5">
                                <IconFileSpreadsheet :size="15" />
                                Payroll
                            </span>
                        </Tab>
                    </TabList>

                    <TabPanels class="min-h-0 flex-1 !bg-white !px-0 dark:!bg-gray-900">
                        <TabPanel value="payroll" class="h-full">
                            <div class="flex h-full flex-col gap-2">
                                <div class="flex flex-wrap items-center justify-between gap-2">
                                    <div class="min-w-0 text-sm font-semibold text-slate-700 dark:text-gray-100">
                                        Payroll Recipients
                                    </div>
                                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-gray-400">
                                        <span>{{ historicalImportPreview?.row_count ?? 0 }} recipient(s)</span>
                                        <span>{{ historicalImportPreview?.batch_count ?? 0 }} batch(es)</span>
                                        <span>Grand Total: ₱{{ historicalImportPreview?.grand_total ?? "0.00" }}</span>
                                    </div>
                                </div>

                                <div class="flex-1 overflow-auto rounded-lg border bg-white dark:border-gray-600 dark:bg-gray-900">
                                    <table class="min-w-[1600px] w-full text-xs dark:text-gray-200">
                                        <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-gray-800 dark:text-gray-300">
                                            <tr>
                                                <th class="border px-2 py-2 text-left">Account No</th>
                                                <th class="border px-2 py-2 text-left">Name</th>
                                                <th class="border px-2 py-2 text-left">Program</th>
                                                <th class="border px-2 py-2 text-left">University</th>
                                                <th class="border px-2 py-2 text-left">Status</th>
                                                <th class="border px-2 py-2 text-left">Period</th>
                                                <th
                                                    v-for="month in 5"
                                                    :key="`preview-head-${month}`"
                                                    class="border px-2 py-2 text-right"
                                                >
                                                    Month {{ month }}
                                                </th>
                                                <th class="border px-2 py-2 text-right">Withheld</th>
                                                <th class="border px-2 py-2 text-left">Remarks</th>
                                                <th class="border px-2 py-2 text-right">LMA/Connectivity</th>
                                                <th class="border px-2 py-2 text-right">Clothing</th>
                                                <th class="border px-2 py-2 text-right">Total</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <template
                                                v-for="group in historicalPreviewGroups"
                                                :key="group.program"
                                            >
                                                <tr
                                                    v-for="row in group.rows"
                                                    :key="`preview-row-${row.id}`"
                                                >
                                                    <td class="border px-2 py-1 min-w-36">{{ row.account_no }}</td>
                                                    <td class="border px-2 py-1 uppercase min-w-56">{{ row.name }}</td>
                                                    <td class="border px-2 py-1">{{ row.program }}</td>
                                                    <td class="border px-2 py-1 min-w-56">{{ row.university }}</td>
                                                    <td class="border px-2 py-1 min-w-44">{{ row.scholarship_status }}</td>
                                                    <td class="border px-2 py-1 min-w-44">{{ row.period }}</td>
                                                    <td
                                                        v-for="month in 5"
                                                        :key="`preview-row-${row.id}-${month}`"
                                                        class="border px-2 py-1 text-right"
                                                    >
                                                        {{ formatMoney(row[`month_${month}`]) }}
                                                    </td>
                                                    <td class="border px-2 py-1 text-right">{{ formatMoney(row.total_withheld) }}</td>
                                                    <td class="border px-2 py-1 min-w-56">{{ row.remarks }}</td>
                                                    <td class="border px-2 py-1 text-right">{{ formatMoney(row.learning_materials_amount) }}</td>
                                                    <td class="border px-2 py-1 text-right">{{ formatMoney(row.clothing_amount) }}</td>
                                                    <td class="border px-2 py-1 text-right font-semibold">{{ formatMoney(historicalPreviewRowTotal(row)) }}</td>
                                                </tr>
                                                <tr class="bg-slate-50 font-semibold dark:bg-gray-800">
                                                    <td colspan="6" class="border px-2 py-1 text-right">Sub-Total</td>
                                                    <td
                                                        v-for="month in 5"
                                                        :key="`preview-subtotal-${group.program}-${month}`"
                                                        class="border px-2 py-1 text-right"
                                                    >
                                                        {{ formatMoney(group.totals[`month_${month}`]) }}
                                                    </td>
                                                    <td class="border px-2 py-1 text-right">{{ formatMoney(group.totals.total_withheld) }}</td>
                                                    <td class="border px-2 py-1"></td>
                                                    <td class="border px-2 py-1 text-right">{{ formatMoney(group.totals.learning_materials_amount) }}</td>
                                                    <td class="border px-2 py-1 text-right">{{ formatMoney(group.totals.clothing_amount) }}</td>
                                                    <td class="border px-2 py-1 text-right">{{ formatMoney(group.totals.grand_total) }}</td>
                                                </tr>
                                            </template>
                                            <tr
                                                v-if="historicalPreviewRows.length"
                                                class="bg-slate-100 font-bold dark:bg-gray-900"
                                            >
                                                <td colspan="6" class="border px-2 py-1 text-right">TOTAL</td>
                                                <td
                                                    v-for="month in 5"
                                                    :key="`preview-grand-${month}`"
                                                    class="border px-2 py-1 text-right"
                                                >
                                                    {{ formatMoney(historicalPreviewTotals[`month_${month}`]) }}
                                                </td>
                                                <td class="border px-2 py-1 text-right">{{ formatMoney(historicalPreviewTotals.total_withheld) }}</td>
                                                <td class="border px-2 py-1"></td>
                                                <td class="border px-2 py-1 text-right">{{ formatMoney(historicalPreviewTotals.learning_materials_amount) }}</td>
                                                <td class="border px-2 py-1 text-right">{{ formatMoney(historicalPreviewTotals.clothing_amount) }}</td>
                                                <td class="border px-2 py-1 text-right">{{ formatMoney(historicalPreviewTotals.grand_total) }}</td>
                                            </tr>
                                            <tr v-if="!historicalPreviewRows.length">
                                                <td colspan="17" class="py-8 text-center text-gray-500">
                                                    No payroll recipients found.
                                                </td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>

                                <div class="flex justify-end gap-2 border-t border-slate-100 pt-2 dark:border-gray-700">
                                    <DefaultButton
                                        size="small"
                                        label="Back"
                                        severity="secondary"
                                        outlined
                                        @click="historicalPreviewDrawer = false; historicalImportDialog = true"
                                    />
                                    <DefaultButton
                                        size="small"
                                        label="Import Historical Payroll"
                                        severity="info"
                                        :icon="IconFileImport"
                                        :loading="historicalImportForm.processing"
                                        @click="submitHistoricalImport"
                                    />
                                </div>
                            </div>
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </div>
        </template>
    </Drawer>

    <Dialog
        v-model:visible="submitDialog"
        modal
        header="Submit Payroll"
        :style="{ width: '30rem' }"
        :pt="{
            root: 'dark:!border-gray-700 dark:!bg-gray-900 dark:!text-gray-100',
            header: 'dark:!border-gray-700 dark:!bg-gray-900 dark:!text-gray-100',
            title: 'dark:!text-gray-100',
            content: 'dark:!bg-gray-900 dark:!text-gray-100',
            footer: 'dark:!border-gray-700 dark:!bg-gray-900',
            closeButton: 'dark:!text-gray-300 dark:hover:!bg-gray-800 dark:hover:!text-white',
        }"
    >
        <div class="flex flex-col gap-3">
            <p class="text-sm text-slate-600 dark:text-gray-300">
                Upload the signed payroll PDF before submitting this batch for review.
            </p>
            <label
                class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-600 hover:bg-slate-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-gray-500 dark:hover:bg-gray-700"
            >
                <IconFileTypePdf :size="28" class="text-slate-500 dark:text-gray-400" />
                <span class="font-medium text-slate-700 dark:text-gray-100">{{ submitPdf?.name ?? "Choose PDF file" }}</span>
                <span class="text-xs text-slate-500 dark:text-gray-400">PDF only</span>
                <input
                    ref="submitPdfInput"
                    type="file"
                    accept="application/pdf,.pdf"
                    class="hidden"
                    @change="selectSubmitPdf"
                />
            </label>
            <small v-if="submitPdfError || statusForm.errors.payroll_file" class="text-red-600 dark:text-red-300">
                {{ submitPdfError || statusForm.errors.payroll_file }}
            </small>
        </div>

        <template #footer>
            <DefaultButton
                size="small"
                label="Cancel"
                severity="secondary"
                outlined
                @click="closeSubmitDialog"
            />
            <DefaultButton
                size="small"
                label="Submit Payroll"
                severity="success"
                :icon="IconSend"
                :loading="statusForm.processing"
                @click="submitPayroll"
            />
        </template>
    </Dialog>

    <Dialog
        v-model:visible="remarksDialog"
        modal
        header="Payroll Remarks"
        :style="{ width: '28rem' }"
        :pt="{
            root: 'dark:!border-gray-700 dark:!bg-gray-900 dark:!text-gray-100',
            header: 'dark:!border-gray-700 dark:!bg-gray-900 dark:!text-gray-100',
            title: 'dark:!text-gray-100',
            content: 'dark:!bg-gray-900 dark:!text-gray-100',
            footer: 'dark:!border-gray-700 dark:!bg-gray-900',
            closeButton: 'dark:!text-gray-300 dark:hover:!bg-gray-800 dark:hover:!text-white',
        }"
    >
        <div class="flex flex-col gap-2">
            <div class="text-sm font-semibold text-slate-700 dark:text-gray-200">
                {{ selectedActionBatch?.name ?? "Payroll batch" }}
            </div>
            <div class="min-h-24 rounded border border-slate-200 bg-slate-50 p-3 text-sm leading-relaxed text-slate-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                {{ selectedActionBatch?.remarks || "No remarks available." }}
            </div>
        </div>

        <template #footer>
            <DefaultButton
                size="small"
                label="Close"
                severity="secondary"
                outlined
                @click="remarksDialog = false"
            />
        </template>
    </Dialog>

    <Dialog
        v-model:visible="creditDialog"
        modal
        header="Payroll Deposit"
        :style="{ width: '52rem', maxWidth: '95vw' }"
        :pt="{
            root: 'dark:!border-gray-700 dark:!bg-gray-900 dark:!text-gray-100',
            header: 'dark:!border-gray-700 dark:!bg-gray-900 dark:!text-gray-100',
            title: 'dark:!text-gray-100',
            content: 'dark:!bg-gray-900 dark:!text-gray-100',
            footer: 'dark:!border-gray-700 dark:!bg-gray-900',
            closeButton: 'dark:!text-gray-300 dark:hover:!bg-gray-800 dark:hover:!text-white',
        }"
    >
        <div class="flex flex-col gap-3">
            <div class="min-w-0">
                <div class="truncate text-sm font-semibold text-slate-800 dark:text-gray-100">
                    {{ selectedCreditBatch?.name }}
                </div>
                <div class="text-xs text-slate-500 dark:text-gray-400">
                    {{ selectedCreditBatch?.region }} | {{ selectedCreditBatch?.term }} / {{ selectedCreditBatch?.sy }}
                </div>
            </div>

            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-5">
                <div
                    v-for="credit in selectedMonthlyCredits"
                    :key="credit.month_no"
                    @click="openCreditRecipients(credit)"
                    :class="[
                        credit.status === 'credited'
                            ? 'border-emerald-200 bg-emerald-50 dark:border-emerald-800 dark:bg-emerald-900/30'
                            : 'border-slate-200 bg-slate-50 dark:border-gray-600 dark:bg-gray-800',
                        'flex min-h-32 min-w-0 cursor-pointer flex-col gap-3 rounded border p-3 transition hover:border-blue-300 dark:hover:border-blue-700',
                    ]"
                >
                    <div class="flex items-start justify-between gap-2">
                        <div class="min-w-0">
                            <div class="text-xs font-semibold text-slate-700 dark:text-gray-100">
                                {{ credit.label }}
                            </div>
                        </div>
                        <span
                            :class="[
                                creditStatusClass(credit.status),
                                'shrink-0 rounded border px-2 py-0.5 text-[10px] font-semibold',
                            ]"
                        >
                            {{ creditStatusLabel(credit.status) }}
                        </span>
                    </div>
                    <div
                        v-if="credit.recipient_count > 0"
                        class="mt-auto flex flex-col gap-1 text-[11px] leading-4 text-slate-500 dark:text-gray-400"
                    >
                        <span class="font-medium text-slate-600 dark:text-gray-300">{{ credit.progress_label }} deposited</span>
                    </div>
                </div>
            </div>
        </div>

        <template #footer>
            <DefaultButton
                size="small"
                label="Close"
                severity="secondary"
                outlined
                @click="creditDialog = false"
            />
        </template>
    </Dialog>

    <Dialog
        v-model:visible="creditRecipientsDialog"
        modal
        :header="`${selectedCreditMonth?.label ?? 'Month'} Deposit Information`"
        :style="{ width: 'min(68rem, 96vw)' }"
        :pt="{
            root: 'dark:!border-gray-700 dark:!bg-gray-900 dark:!text-gray-100',
            header: 'dark:!border-gray-700 dark:!bg-gray-900 dark:!text-gray-100',
            title: 'dark:!text-gray-100',
            content: 'dark:!bg-gray-900 dark:!text-gray-100',
            closeButton: 'dark:!text-gray-300 dark:hover:!bg-gray-800 dark:hover:!text-white',
        }"
    >
        <div class="space-y-3 text-sm text-slate-700 dark:text-gray-200">
            <div class="grid grid-cols-2 gap-2 rounded border border-slate-200 bg-slate-50 p-3 dark:border-gray-700 dark:bg-gray-800 md:grid-cols-4">
                <div><div class="text-xs text-slate-500 dark:text-gray-400">Deposited</div><div class="font-semibold">{{ creditRecipientSummary.credited }} / {{ creditRecipientSummary.total }}</div></div>
                <div><div class="text-xs text-slate-500 dark:text-gray-400">Pending</div><div class="font-semibold">{{ creditRecipientSummary.pending }}</div></div>
                <div><div class="text-xs text-slate-500 dark:text-gray-400">Deposited amount</div><div class="font-semibold">PHP {{ formatMoney(creditRecipientSummary.credited_amount) }}</div></div>
                <div><div class="text-xs text-slate-500 dark:text-gray-400">Payroll amount</div><div class="font-semibold">PHP {{ formatMoney(creditRecipientSummary.total_amount) }}</div></div>
            </div>
            <div v-if="creditRecipientsLoading" class="py-10 text-center text-slate-500 dark:text-gray-400">Loading deposit information...</div>
            <div v-else class="max-h-[55vh] overflow-auto rounded border border-slate-200 dark:border-gray-700">
                <table class="w-full min-w-[760px] text-left text-xs">
                    <thead class="sticky top-0 bg-slate-100 text-slate-600 dark:bg-gray-800 dark:text-gray-200"><tr><th class="p-2">Scholar</th><th class="p-2">Account</th><th class="p-2 text-right">Amount</th><th class="p-2">Status</th><th class="p-2">Remarks / Deposit Info</th></tr></thead>
                    <tbody class="divide-y divide-slate-200 dark:divide-gray-700">
                        <tr v-for="recipient in creditRecipientRows" :key="recipient.id" class="bg-white dark:bg-gray-900">
                            <td class="p-2"><div class="font-semibold text-slate-800 dark:text-gray-100">{{ recipient.name }}</div><div class="text-slate-500 dark:text-gray-400">{{ recipient.spas_no }}</div></td>
                            <td class="p-2">{{ recipient.account_no || '-' }}</td><td class="p-2 text-right font-semibold">{{ formatMoney(recipient.amount) }}</td>
                            <td class="p-2"><span :class="recipient.status === 'credited' ? 'text-emerald-700 dark:text-emerald-300' : 'text-amber-700 dark:text-amber-300'">{{ recipient.status === 'credited' ? 'Deposited' : 'Pending' }}</span></td>
                            <td class="p-2 text-slate-500 dark:text-gray-400"><template v-if="recipient.status === 'credited'">{{ recipient.credited_by || 'Cashier' }}<span v-if="recipient.credited_at"> | {{ recipient.credited_at }}</span></template><template v-else>{{ recipient.remarks || 'No remarks provided' }}</template></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </Dialog>

    <Dialog
        v-model:visible="historicalImportDialog"
        modal
        header="Import Historical Payroll"
        :style="{ width: '32rem' }"
        :pt="{
            root: 'dark:!border-gray-700 dark:!bg-gray-900 dark:!text-gray-100',
            header: 'dark:!border-gray-700 dark:!bg-gray-900 dark:!text-gray-100',
            title: 'dark:!text-gray-100',
            content: 'dark:!bg-gray-900 dark:!text-gray-100',
            footer: 'dark:!border-gray-700 dark:!bg-gray-900',
            closeButton: 'dark:!text-gray-300 dark:hover:!bg-gray-800 dark:hover:!text-white',
        }"
    >
        <div class="flex flex-col gap-3">
            <p class="text-sm text-slate-600 dark:text-gray-300">
                Upload an Excel file that uses the same layout as the exported payroll file. Imported records are saved as approved historical payrolls and will reflect in scholar financial assistance records.
            </p>
            <label
                class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-600 hover:bg-slate-100 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300 dark:hover:border-gray-500 dark:hover:bg-gray-700"
            >
                <IconFileSpreadsheet :size="30" class="text-slate-500 dark:text-gray-400" />
                <span class="font-medium text-slate-700 dark:text-gray-100">{{ historicalImportFile?.name ?? "Choose Excel file" }}</span>
                <span class="text-xs text-slate-500 dark:text-gray-400">XLSX or XLS, exported payroll layout</span>
                <input
                    ref="historicalImportInput"
                    type="file"
                    accept=".xlsx,.xls,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet,application/vnd.ms-excel"
                    class="hidden"
                    @change="selectHistoricalImportFile"
                />
            </label>
            <small
                v-if="historicalImportError || historicalImportForm.errors.payroll_file"
                class="text-red-600 dark:text-red-300"
            >
                {{ historicalImportError || historicalImportForm.errors.payroll_file }}
            </small>
        </div>

        <template #footer>
            <DefaultButton
                size="small"
                label="Cancel"
                severity="secondary"
                outlined
                @click="closeHistoricalImportDialog"
            />
            <DefaultButton
                size="small"
                label="Import Historical"
                severity="info"
                :icon="IconFileImport"
                :loading="historicalImportForm.processing || previewingHistoricalImport"
                @click="previewHistoricalImport"
            />
        </template>
    </Dialog>

    <DefaultToast ref="toastRef" />
</template>

<script setup>
import AuthLayout from "../../Layouts/AuthLayout.vue";
import HeaderModule from "../../Modules/Others/HeaderModule.vue";
import SelectInput from "../../Components/inputs/SelectInput.vue";
import ManagementFilterBar from "../../Components/inputs/ManagementFilterBar.vue";
import DefaultToast from "../../Components/messages/DefaultToast.vue";
import DefaultSelectionTable from "../../Components/tables/DefaultSelectionTable.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import DrawerStipendModule from "../../Modules/Others/DrawerStipendModule.vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import axios from "axios";
import {
    IconBuildingBank,
    IconDotsVertical,
    IconEye,
    IconFileImport,
    IconFileInvoice,
    IconFileSpreadsheet,
    IconFileTypePdf,
    IconFilterOff,
    IconMessageCircle,
    IconSend,
    IconChecks,
} from "@tabler/icons-vue";
import { computed, ref, watch } from "vue";
import { route } from "ziggy-js";

const page = usePage();
const toastRef = ref(null);
const timerBounce = ref(null);
const DEBOUNCE_MS = 600;
const stipendDrawer = ref(null);
const searchInput = ref(null);
const lastFlashKey = ref(null);
const submitDialog = ref(false);
const submitBatch = ref(null);
const submitPdf = ref(null);
const submitPdfInput = ref(null);
const submitPdfError = ref("");
const actionMenu = ref(null);
const selectedActionBatch = ref(null);
const remarksDialog = ref(false);
const creditDialog = ref(false);
const selectedCreditBatch = ref(null);
const creditRecipientsDialog = ref(false);
const creditRecipientsLoading = ref(false);
const selectedCreditMonth = ref(null);
const creditRecipientRows = ref([]);
const creditRecipientSummary = ref({ total: 0, credited: 0, pending: 0, total_amount: 0, credited_amount: 0 });
const historicalImportDialog = ref(false);
const historicalImportFile = ref(null);
const historicalImportInput = ref(null);
const historicalImportError = ref("");
const historicalImportPreview = ref(null);
const historicalPreviewDrawer = ref(false);
const previewingHistoricalImport = ref(false);

const statusForm = useForm({
    status: null,
    remarks: null,
    payroll_file: null,
});

const historicalImportForm = useForm({
    payroll_file: null,
});

const emptyPreviewTotals = () => ({
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

const addPreviewTotals = (totals, row) => {
    for (let month = 1; month <= 5; month++) {
        totals[`month_${month}`] += Number(row[`month_${month}`] ?? 0);
    }

    totals.total_withheld += Number(row.total_withheld ?? 0);
    totals.learning_materials_amount += Number(row.learning_materials_amount ?? 0);
    totals.clothing_amount += Number(row.clothing_amount ?? 0);
    totals.grand_total += historicalPreviewRowTotal(row);

    return totals;
};

const historicalPreviewRowTotal = (row) =>
    [1, 2, 3, 4, 5].reduce(
        (total, month) => total + Number(row[`month_${month}`] ?? 0),
        0,
    ) +
    Number(row.total_withheld ?? 0) +
    Number(row.learning_materials_amount ?? 0) +
    Number(row.clothing_amount ?? 0);

const historicalPreviewRows = computed(() => historicalImportPreview.value?.rows ?? []);
const historicalPreviewGroups = computed(() => {
    const groups = new Map();

    historicalPreviewRows.value.forEach((row) => {
        const key = row.program || "Imported Payroll";
        if (!groups.has(key)) {
            groups.set(key, {
                program: key,
                rows: [],
                totals: emptyPreviewTotals(),
            });
        }

        const group = groups.get(key);
        group.rows.push(row);
        addPreviewTotals(group.totals, row);
    });

    return Array.from(groups.values());
});
const historicalPreviewTotals = computed(() =>
    historicalPreviewRows.value.reduce(
        (totals, row) => addPreviewTotals(totals, row),
        emptyPreviewTotals(),
    ),
);
const formatMoney = (value) =>
    Number(value ?? 0).toLocaleString("en-PH", {
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
    });

const isRegionLocked = computed(() => Boolean(page.props.payrollPermissions.regionLocked));
const canImportHistoricalPayroll = computed(() =>
    Boolean(page.props.payrollPermissions.canImportHistorical),
);

const findOption = (options, value, keys = ["id", "name"]) => {
    if (!value) return null;

    return (options ?? []).find((option) =>
        keys.some((key) => String(option?.[key] ?? "") === String(value)),
    ) ?? null;
};

const lockedRegionOption = () => page.props.agencyOption?.[0] ?? null;
const filterRegion = ref(
    isRegionLocked.value
        ? lockedRegionOption()
        : findOption(page.props.agencyOption, page.props.batchFilters?.region, ["name"]),
);
const filterTerm = ref(findOption(
    page.props.termOptions,
    page.props.batchFilters?.term_id ?? page.props.batchFilters?.term_name,
    ["id", "term_name"],
));
const filterAcademicYear = ref(findOption(
    page.props.academicYearOptions,
    page.props.batchFilters?.academic_year,
));
const filterStatus = ref(findOption(page.props.statusOptions, page.props.batchFilters?.status));
const hasBatchFilters = computed(() =>
    Boolean(
        (!isRegionLocked.value && filterRegion.value) ||
            filterTerm.value ||
            filterAcademicYear.value ||
            filterStatus.value,
    ),
);
const activeFilterCount = computed(() =>
    [
        searchInput.value,
        !isRegionLocked.value && filterRegion.value,
        filterTerm.value,
        filterAcademicYear.value,
        filterStatus.value,
    ].filter(Boolean).length,
);

const selectedAcademicYear = (value) => value?.name ?? value;
const canSubmitBatch = (batch) =>
    Boolean(batch?.permissions?.canSubmit) && Number(batch?.scholars_count ?? 0) > 0;
const hasMonthlyCredits = (batch) =>
    Array.isArray(batch?.monthly_credits) && batch.monthly_credits.length > 0;
const selectedMonthlyCredits = computed(() => selectedCreditBatch.value?.monthly_credits ?? []);
const creditStatusLabel = (status) =>
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
            : "border-slate-200 bg-white text-slate-600 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-300";

const openCreditRecipients = async (credit) => {
    if (!selectedCreditBatch.value?.id || !credit?.month_no) return;
    selectedCreditMonth.value = credit;
    creditRecipientsDialog.value = true;
    creditRecipientsLoading.value = true;
    creditRecipientRows.value = [];

    try {
        const response = await axios.get(route("cashier.credits.recipients", {
            id: selectedCreditBatch.value.id,
            month: credit.month_no,
        }));
        creditRecipientRows.value = response.data.recipients ?? [];
        creditRecipientSummary.value = response.data.summary ?? creditRecipientSummary.value;
    } finally {
        creditRecipientsLoading.value = false;
    }
};
const batchStatusMeta = (status) =>
    ({
        draft: {
            label: "Draft",
            class: "bg-slate-50 text-slate-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600",
        },
        submitted_payroll: {
            label: "Submitted Payroll",
            class: "bg-blue-50 text-blue-500 dark:bg-blue-900/40 dark:text-blue-300 dark:border-blue-800",
        },
        verified_payroll: {
            label: "For Verification",
            class: "bg-amber-50 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300 dark:border-amber-800",
        },
        rejected_payroll: {
            label: "Returned Payroll",
            class: "bg-red-50 text-red-500 dark:bg-red-900/40 dark:text-red-300 dark:border-red-800",
        },
        approved_payroll: {
            label: "Approved Payroll",
            class: "bg-green-50 text-green-600 dark:bg-green-900/40 dark:text-green-300 dark:border-green-800",
        },
    })[status] ?? {
        label: status ?? "Draft",
        class: "bg-slate-50 text-slate-500 dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600",
    };

const openReview = (batch) => {
    if (!batch?.id) return;

    router.reload({
        data: { id: batch.id },
        only: ["details", "payrollRecipients", "signatoryOptions"],
        onSuccess: () => {
            stipendDrawer.value = true;
        },
    });
};

const toggleActionMenu = (event, batch) => {
    selectedActionBatch.value = batch;
    actionMenu.value?.toggle(event);
};

const openRemarksDialog = (batch) => {
    selectedActionBatch.value = batch;
    remarksDialog.value = true;
};

const openCreditDialog = (batch) => {
    if (!hasMonthlyCredits(batch)) return;

    selectedCreditBatch.value = batch;
    creditDialog.value = true;
};

const openHistoricalImportDialog = () => {
    historicalImportFile.value = null;
    historicalImportError.value = "";
    historicalImportPreview.value = null;
    historicalPreviewDrawer.value = false;
    historicalImportForm.clearErrors();
    historicalImportDialog.value = true;
};

const closeHistoricalImportDialog = () => {
    historicalImportDialog.value = false;
    historicalImportFile.value = null;
    historicalImportError.value = "";
    historicalImportPreview.value = null;
    historicalImportForm.reset();

    if (historicalImportInput.value) {
        historicalImportInput.value.value = "";
    }
};

const resetHistoricalImport = () => {
    historicalPreviewDrawer.value = false;
    closeHistoricalImportDialog();
};

const selectHistoricalImportFile = (event) => {
    const file = event.target.files?.[0] ?? null;
    historicalImportFile.value = null;
    historicalImportError.value = "";
    historicalImportPreview.value = null;
    historicalImportForm.clearErrors();

    if (!file) return;

    const isExcel =
        file.name.toLowerCase().endsWith(".xlsx") ||
        file.name.toLowerCase().endsWith(".xls");

    if (!isExcel) {
        historicalImportError.value = "Only Excel files are allowed.";
        event.target.value = "";
        return;
    }

    historicalImportFile.value = file;
};

const historicalImportFormData = () => {
    if (!historicalImportFile.value) {
        historicalImportError.value = "Upload an Excel file before importing.";
        return null;
    }

    const formData = new FormData();
    formData.append("payroll_file", historicalImportFile.value);

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
    if (csrfToken) {
        formData.append("_token", csrfToken);
    }

    return formData;
};

const historicalImportUrl = computed(() => route("stipends.import-historical"));
const historicalImportPreviewUrl = computed(() => `${historicalImportUrl.value}/preview`);

const previewHistoricalImport = async () => {
    const formData = historicalImportFormData();
    if (!formData) return;

    historicalImportError.value = "";
    historicalImportForm.clearErrors();
    previewingHistoricalImport.value = true;

    try {
        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute("content");
        const response = await axios.post(
            historicalImportPreviewUrl.value,
            formData,
            {
                headers: {
                    Accept: "application/json",
                    "Content-Type": "multipart/form-data",
                    ...(csrfToken ? { "X-CSRF-TOKEN": csrfToken } : {}),
                },
            },
        );

        historicalImportPreview.value = response.data;
        historicalImportDialog.value = false;
        historicalPreviewDrawer.value = true;
    } catch (error) {
        historicalImportPreview.value = null;
        historicalImportError.value =
            error.response?.data?.errors?.payroll_file?.[0] ??
            error.response?.data?.detail ??
            error.response?.data?.message ??
            (error.response?.status
                ? `Unable to preview the uploaded payroll file. HTTP ${error.response.status}.`
                : `Unable to preview the uploaded payroll file.${error.message ? ` ${error.message}` : ""}`);
    } finally {
        previewingHistoricalImport.value = false;
    }
};

const submitHistoricalImport = () => {
    if (!historicalImportPreview.value) {
        previewHistoricalImport();
        return;
    }

    if (!historicalImportFile.value) {
        historicalImportError.value = "Upload an Excel file before importing.";
        return;
    }

    historicalImportForm.payroll_file = historicalImportFile.value;
    historicalImportForm.post(historicalImportUrl.value, {
        preserveScroll: true,
        forceFormData: true,
        onSuccess: () => {
            resetHistoricalImport();
            loadPage(1);
        },
        onError: (errors) => {
            historicalImportError.value = errors.payroll_file ?? "";
        },
    });
};

const actionMenuItems = computed(() => {
    const batch = selectedActionBatch.value;

    return [
        canSubmitBatch(batch)
            ? {
                  label: "Submit",
                  icon: IconSend,
                  class: "text-green-500",
                  command: () => openSubmitDialog(batch),
              }
            : null,
        {
            label: "Review",
            icon: IconEye,
            class: "text-blue-500",
            command: () => openReview(batch),
        },
        {
            label: "Remarks",
            icon: IconMessageCircle,
            class: "text-slate-500",
            command: () => openRemarksDialog(batch),
        },
    ].filter(Boolean);
});

const openSubmitDialog = (batch) => {
    if (!canSubmitBatch(batch)) return;

    if (batch.requires_export_before_submit) {
        toastRef.value?.show({
            status: "warn",
            title: "Export payroll first",
            message: "Please export the latest payroll batch before submitting it.",
        });
        return;
    }

    submitBatch.value = batch;
    submitPdf.value = null;
    submitPdfError.value = "";
    statusForm.clearErrors();
    submitDialog.value = true;
};

const closeSubmitDialog = () => {
    submitDialog.value = false;
    submitBatch.value = null;
    submitPdf.value = null;
    submitPdfError.value = "";

    if (submitPdfInput.value) {
        submitPdfInput.value.value = "";
    }
};

const selectSubmitPdf = (event) => {
    const file = event.target.files?.[0] ?? null;
    submitPdf.value = null;
    submitPdfError.value = "";

    if (!file) return;

    if (file.type !== "application/pdf" && !file.name.toLowerCase().endsWith(".pdf")) {
        submitPdfError.value = "Only PDF files are allowed.";
        event.target.value = "";
        return;
    }

    submitPdf.value = file;
};

const submitPayroll = () => {
    if (!submitBatch.value?.id) return;

    if (!submitPdf.value) {
        submitPdfError.value = "Upload a PDF file before submitting payroll.";
        return;
    }

    statusForm.status = "submitted_payroll";
    statusForm.remarks = null;
    statusForm.payroll_file = submitPdf.value;
    statusForm
        .transform((data) => ({
            ...data,
            _method: "put",
        }))
        .post(route("stipends.update", { id: submitBatch.value.id, type: "status" }), {
            preserveScroll: true,
            forceFormData: true,
            onSuccess: () => {
                closeSubmitDialog();
                loadPage(page.props.batches.current_page);
            },
            onError: (errors) => {
                submitPdfError.value = errors.payroll_file ?? "";
            },
        });
};

const scheduleLoadPage = (pageNumber = 1) => {
    clearTimeout(timerBounce.value);
    timerBounce.value = setTimeout(() => loadPage(pageNumber), DEBOUNCE_MS);
};

const loadPage = (pageNumber) => {
    router.get(
        route("stipends"),
        {
            page: pageNumber,
            ...(searchInput.value ? { search: searchInput.value } : {}),
            ...(filterRegion.value ? { region: filterRegion.value.name } : {}),
            ...(filterTerm.value
                ? {
                      term_id: filterTerm.value.id,
                      term_name: filterTerm.value.term_name ?? filterTerm.value.name,
                  }
                : {}),
            ...(filterAcademicYear.value
                ? { academic_year: selectedAcademicYear(filterAcademicYear.value) }
                : {}),
            ...(filterStatus.value ? { status: filterStatus.value.id } : {}),
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ["batches", "batchFilters"],
        },
    );
};

const clearBatchFilters = () => {
    filterRegion.value = isRegionLocked.value ? lockedRegionOption() : null;
    filterTerm.value = null;
    filterAcademicYear.value = null;
    filterStatus.value = null;
    loadPage(1);
};

watch(
    () => searchInput.value,
    (value, oldValue) => {
        if (value === oldValue) return;

        scheduleLoadPage();
    },
);

watch(
    () => [
        filterRegion.value?.id ?? null,
        filterTerm.value?.id ?? null,
        filterAcademicYear.value?.id ?? filterAcademicYear.value?.name ?? null,
        filterStatus.value?.id ?? null,
    ],
    (value, oldValue) => {
        if (JSON.stringify(value) === JSON.stringify(oldValue)) return;

        scheduleLoadPage();
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
    border-bottom: 0;
    padding: 0 0 0.75rem;
    background: transparent;
}

:deep(.p-datatable-table-container) {
    border-radius: 0.5rem !important;
}

:deep(.p-datatable-thead > tr > th) {
    padding-top: 0.75rem;
    padding-bottom: 0.75rem;
    background: #f8fafc !important;
    color: #64748b;
    font-size: 0.6875rem;
    text-transform: uppercase;
}

:deep(.p-datatable-tbody > tr > td) {
    padding-top: 0.65rem;
    padding-bottom: 0.65rem;
}

:global(.dark .financial-assistance-page .p-datatable),
:global(.dark .financial-assistance-page .p-datatable-header),
:global(.dark .financial-assistance-page .p-datatable-table-container),
:global(.dark .financial-assistance-page .p-datatable-table),
:global(.dark .financial-assistance-page .p-datatable-tbody > tr),
:global(.dark .financial-assistance-page .p-datatable-tbody > tr > td) {
    background: #1f2937 !important;
    color: #e5e7eb !important;
    border-color: #374151 !important;
}

:global(.dark .financial-assistance-page .p-datatable-thead > tr > th) {
    background: #111827 !important;
    color: #d1d5db !important;
    border-color: #374151 !important;
}

:global(.dark .financial-assistance-page .p-datatable-tbody > tr:hover > td),
:global(.dark .financial-assistance-page .p-datatable-tbody > tr.p-highlight > td),
:global(.dark .financial-assistance-page .p-datatable-tbody > tr.p-datatable-row-selected > td) {
    background: #374151 !important;
    color: #ffffff !important;
}

:global(.dark .financial-assistance-page .p-paginator),
:global(.dark .financial-assistance-page .p-datatable-paginator-bottom) {
    background: #1f2937 !important;
    color: #d1d5db !important;
    border-color: #374151 !important;
}

:global(.dark .financial-assistance-page .p-paginator button) {
    color: #d1d5db !important;
}

:global(.dark .financial-assistance-page .p-paginator button:hover) {
    background: #374151 !important;
    color: #ffffff !important;
}

:global(.dark .financial-assistance-page .p-paginator-page-selected) {
    background: #2563eb !important;
    color: #ffffff !important;
}

:global(.dark .financial-assistance-page .p-inputtext) {
    background: #374151 !important;
    border-color: #4b5563 !important;
    color: #f3f4f6 !important;
}

:global(.dark .financial-assistance-page .p-inputtext::placeholder) {
    color: #9ca3af !important;
}

:global(.dark .compact-payroll-tabs table th),
:global(.dark .compact-payroll-tabs table td) {
    border-color: #4b5563 !important;
}

:global(.dark .compact-payroll-tabs tbody tr) {
    background: #111827;
    color: #e5e7eb;
}
</style>
