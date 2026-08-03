<template>
    <Head title="Financial Assistance" />
    <AuthLayout>
        <div class="flex h-full min-h-0 w-full flex-col gap-5 overflow-hidden">
            <div class="shrink-0">
                <HeaderModule
                    title="Financial Assistance"
                    description="Review auto-created payroll batches, submit signed payroll files, and monitor processing remarks."
                />
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
                        <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-start">
                            <InputText
                                v-model="searchInput"
                                placeholder="Search file name or region"
                                class="!text-sm lg:w-72"
                            />
                            <SelectInput
                                v-model="filterRegion"
                                :options="page.props.agencyOption ?? []"
                                placeholder="Region"
                                clearable
                                filter
                                capitalize
                                :disable="isRegionLocked"
                                class="lg:w-48"
                            />
                            <SelectInput
                                v-model="filterTerm"
                                :options="page.props.termOptions ?? []"
                                placeholder="Semester"
                                clearable
                                class="lg:w-44"
                            />
                            <SelectInput
                                v-model="filterAcademicYear"
                                :options="page.props.academicYearOptions ?? []"
                                placeholder="Academic Year"
                                clearable
                                class="lg:w-44"
                            />
                            <SelectInput
                                v-model="filterStatus"
                                :options="page.props.statusOptions ?? []"
                                placeholder="Status"
                                clearable
                                class="lg:w-48"
                            />
                            <DefaultButton
                                size="small"
                                severity="secondary"
                                :icon="IconFilterOff"
                                tooltip="Clear filters"
                                :disabled="!hasBatchFilters"
                                @click="clearBatchFilters"
                            />
                        </div>
                    </template>

                    <Column header="File Name">
                        <template #body="props">
                            <div class="flex min-w-64 items-center gap-2">
                                <IconFileInvoice size="23" stroke-width="1.5" class="shrink-0 text-slate-500" />
                                <div class="truncate text-sm font-medium text-slate-700">
                                    {{ props.data.name }}
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column header="Region" field="region" />
                    <Column header="Sem & AY">
                        <template #body="props">
                            <div class="min-w-44 text-sm">
                                <span class="font-medium">{{ props.data.term }}</span>
                                <span class="text-slate-400"> / </span>
                                <span>{{ props.data.sy }}</span>
                            </div>
                        </template>
                    </Column>
                    <Column header="No. of Scholars">
                        <template #body="props">
                            <div class="text-left font-semibold text-slate-700">
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
                                        'flex items-center gap-1 rounded-2xl border px-3 py-0.5',
                                    ]"
                                >
                                    <IconDotsCircleHorizontal size="18" />
                                    <div class="text-xs">
                                        {{ batchStatusMeta(props.data.status).label }}
                                    </div>
                                </div>
                            </div>
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
                <Menu ref="actionMenu" :model="actionMenuItems" :popup="true">
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

    <Dialog v-model:visible="submitDialog" modal header="Submit Payroll" :style="{ width: '30rem' }">
        <div class="flex flex-col gap-3">
            <p class="text-sm text-slate-600">
                Upload the signed payroll PDF before submitting this batch for review.
            </p>
            <label
                class="flex cursor-pointer flex-col items-center justify-center gap-2 rounded border border-dashed border-slate-300 bg-slate-50 px-4 py-6 text-center text-sm text-slate-600 hover:bg-slate-100"
            >
                <IconFileTypePdf :size="28" class="text-slate-500" />
                <span class="font-medium">{{ submitPdf?.name ?? "Choose PDF file" }}</span>
                <span class="text-xs text-slate-500">PDF only</span>
                <input
                    ref="submitPdfInput"
                    type="file"
                    accept="application/pdf,.pdf"
                    class="hidden"
                    @change="selectSubmitPdf"
                />
            </label>
            <small v-if="submitPdfError || statusForm.errors.payroll_file" class="text-red-600">
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

    <Dialog v-model:visible="remarksDialog" modal header="Payroll Remarks" :style="{ width: '28rem' }">
        <div class="flex flex-col gap-2">
            <div class="text-sm font-semibold text-slate-700">
                {{ selectedActionBatch?.name ?? "Payroll batch" }}
            </div>
            <div class="min-h-24 rounded border border-slate-200 bg-slate-50 p-3 text-sm leading-relaxed text-slate-600">
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

    <DefaultToast ref="toastRef" />
</template>

<script setup>
import AuthLayout from "../../Layouts/AuthLayout.vue";
import HeaderModule from "../../Modules/Others/HeaderModule.vue";
import SelectInput from "../../Components/inputs/SelectInput.vue";
import DefaultToast from "../../Components/messages/DefaultToast.vue";
import DefaultSelectionTable from "../../Components/tables/DefaultSelectionTable.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import DrawerStipendModule from "../../Modules/Others/DrawerStipendModule.vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import {
    IconDotsCircleHorizontal,
    IconDotsVertical,
    IconEye,
    IconFileInvoice,
    IconFileTypePdf,
    IconFilterOff,
    IconMessageCircle,
    IconSend,
} from "@tabler/icons-vue";
import { computed, ref, watch } from "vue";
import { route } from "ziggy-js";

const page = usePage();
const toastRef = ref(null);
const timerBounce = ref(null);
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

const statusForm = useForm({
    status: null,
    remarks: null,
    payroll_file: null,
});

const isRegionLocked = computed(() => Boolean(page.props.payrollPermissions.regionLocked));

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

const selectedAcademicYear = (value) => value?.name ?? value;
const canSubmitBatch = (batch) =>
    Boolean(batch?.permissions?.canSubmit) && Number(batch?.scholars_count ?? 0) > 0;
const batchStatusMeta = (status) =>
    ({
        draft: {
            label: "Draft",
            class: "bg-slate-50 text-slate-500",
        },
        submitted_payroll: {
            label: "Submitted Payroll",
            class: "bg-blue-50 text-blue-500",
        },
        rejected_payroll: {
            label: "Returned Payroll",
            class: "bg-red-50 text-red-500",
        },
        approved_payroll: {
            label: "Approved Payroll",
            class: "bg-green-50 text-green-600",
        },
    })[status] ?? {
        label: status ?? "Draft",
        class: "bg-slate-50 text-slate-500",
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

const actionMenuItems = computed(() => {
    const batch = selectedActionBatch.value;

    return [
        {
            label: "Review",
            icon: IconEye,
            class: "text-blue-500",
            command: () => openReview(batch),
        },
        canSubmitBatch(batch)
            ? {
                  label: "Submit",
                  icon: IconSend,
                  class: "text-green-500",
                  command: () => openSubmitDialog(batch),
              }
            : null,
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
    () => {
        clearTimeout(timerBounce.value);
        timerBounce.value = setTimeout(() => loadPage(1), 300);
    },
);

watch(
    () => [
        filterRegion.value,
        filterTerm.value,
        filterAcademicYear.value,
        filterStatus.value,
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
    border-bottom: 0;
    padding: 0.5rem 0 0.75rem;
}
</style>
