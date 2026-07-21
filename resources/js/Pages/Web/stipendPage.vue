<template>
    <Head title="Financial Assistance" />
    <AuthLayout>
        <div class="flex flex-col w-full h-full gap-5">
            <div class="flex">
                <HeaderModule
                    title="Financial Assistance"
                    description="Manage and monitor stipend records, track financial assistance details, and ensure accurate processing of student support."
                />
            </div>
            <div class="flex-1 flex flex-col gap-2">
                <ToolbarModule
                    v-model="searchInput"
                    @deleteSearch="clearSearch"
                    @saveForm="submitForm"
                    button-label="Create"
                    :dialog-title="!form.id ? 'Create Batch' : 'Edit User'"
                    dialog-description="Fill in the required information to create or update this user."
                    :dialog-button-loading="form.processing"
                    :dialog-button-disabled="!nextBatchNumber"
                    :dialog-icon="IconUserPlus"
                    dialog-button-label="Save"
                    :message-has-errors="form.hasErrors"
                    :message-errors="form.errors"
                    @buttonOpenModal="toggleModal({ type: 'create' })"
                    :button-visible="can('payroll.create')"
                    message-type="error"
                    ref="toolbarRef"
                >
                    <template #add1>
                        <div class="flex items-center gap-2">
                            <SelectInput
                                v-model="filterRegion"
                                :options="page.props.agencyOption ?? []"
                                placeholder="Region"
                                clearable
                                filter
                                capitalize
                                :disable="isRegionLocked"
                                class="w-44"
                            />
                            <SelectInput
                                v-model="filterTerm"
                                :options="page.props.termOptions ?? []"
                                placeholder="Semester"
                                clearable
                                class="w-40"
                            />
                            <SelectInput
                                v-model="filterAcademicYear"
                                :options="page.props.academicYearOptions ?? []"
                                placeholder="Academic Year"
                                clearable
                                class="w-40"
                            />
                            <SelectInput
                                v-model="filterStatus"
                                :options="page.props.statusOptions ?? []"
                                placeholder="Status"
                                clearable
                                class="w-44"
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
                    <template #form>
                        <div class="mt-5 flex flex-col gap-3">
                            <SelectInput
                                label="Region"
                                v-model="form.region"
                                :options="page.props.agencyOption"
                                :clearable="!isRegionLocked"
                                :disable="isRegionLocked"
                                capitalize
                            ></SelectInput>
                            <SelectInput
                                v-model="form.term"
                                label="Academic Term"
                                :options="page.props.termOptions"
                                :clearable="true"
                                :errorMark="v$.term.$error"
                                :tooltip="v$.term.$errors[0]?.$message"
                            />
                            <SelectInput
                                v-model="form.academic_year"
                                label="Academic year"
                                placeholder="Select academic year"
                                :options="page.props.academicYearOptions ?? []"
                                :clearable="true"
                                :errorMark="v$.academic_year.$error"
                                :tooltip="v$.academic_year.$errors[0]?.$message"
                            />

                            <div class="w-full flex flex-col">
                                <div class="text-sm font-medium">
                                    Batch Number
                                    <span class="text-red-600 font-semibold" v-if="v$.batch.$error">*</span>
                                </div>
                                <div
                                    :class="[
                                        'min-h-10 flex items-center rounded-md border border-gray-300 px-3 text-sm text-gray-700 opacity-60',
                                        'dark:border-gray-700 dark:bg-gray-700 dark:text-gray-100',
                                    ]"
                                >
                                    {{ nextBatchNumber ?? "" }}
                                </div>
                                <small v-if="v$.batch.$error" class="mt-1 text-xs text-red-600">
                                    {{ v$.batch.$errors[0]?.$message }}
                                </small>
                            </div>
                        </div>
                    </template>
                </ToolbarModule>
                <DefaultSelectionTable
                    :items="page.props.batches.data"
                    :pagination="{
                        total: page.props.batches.total,
                        perPage: page.props.batches.per_page,
                        currentPage: page.props.batches.current_page,
                    }"
                    @selected="openModal"
                    @paginate="loadPage"
                >
                    <Column header="Name">
                        <template #body="props">
                            <div class="flex items-center gap-2">
                                <div class="text-slate-500">
                                    <IconFileInvoice
                                        size="25"
                                        stroke-width="1.5"
                                    />
                                </div>

                                <div class="text-sm font-semilight">
                                    {{ props.data.name }}
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column header="Region" field="region"></Column>
                    <Column header="Sem & AY">
                        <template #body="props">
                            <div
                                class="flex items-center justify-start gap-2 px-2 py-1 rounded-md"
                            >
                                <span class="font-medium">{{
                                    props.data.term
                                }}</span>
                                <span v-if="props.data.level" class="text-gray-400">/</span>
                                <span v-if="props.data.level" class="text-gray-600">{{
                                    props.data.level
                                }}</span>
                                <span v-if="props.data.level" class="text-gray-400">/</span>
                                <span class="text-gray-600">{{
                                    props.data.sy
                                }}</span>
                            </div>
                        </template>
                    </Column>

                    <Column header="Remarks">
                        <template #body="props">
                            <div
                                class="max-w-56 truncate capitalize"
                                :title="props.data.remarks"
                            >
                                {{ truncateRemarks(props.data.remarks) }}
                            </div>
                        </template>
                    </Column>
                    <Column>
                        <template #header>
                            <div
                                class="flex justify-center w-full font-semibold"
                            >
                                <div class="font-semibold">Status</div>
                            </div>
                        </template>
                        <template #body="props">
                            <div class="flex justify-center">
                                <div
                                    :class="[
                                        batchStatusMeta(props.data.status).class,
                                        'flex items-center gap-1 px-4 py-0.5 rounded-2xl border',
                                    ]"
                                >
                                    <IconDotsCircleHorizontal size="20" />
                                    <div class="capitalize text-xs">
                                        {{ batchStatusMeta(props.data.status).label }}
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column v-if="showBatchActionColumn">
                        <template #header>
                            <div class="flex justify-center w-full font-semibold">
                                Action
                            </div>
                        </template>
                        <template #body="props">
                            <div class="flex justify-center">
                                <DefaultButton
                                    size="small"
                                    severity="danger"
                                    text
                                    rounded
                                    :icon="IconTrash"
                                    tooltip="Delete batch"
                                    :disabled="
                                        !canDeleteBatch(props.data)
                                    "
                                    :loading="
                                        deleteForm.processing &&
                                        deletingId === props.data.id
                                    "
                                    @click="deleteBatch($event, props.data)"
                                />
                            </div>
                        </template>
                    </Column>
                </DefaultSelectionTable>
            </div>
        </div>
    </AuthLayout>
    <DrawerStipendModule v-model:visible="stipendDrawer" />
    <DefaultToast ref="toastRef" />
</template>
<script setup>
import AuthLayout from "../../Layouts/AuthLayout.vue";
import HeaderModule from "../../Modules/Others/HeaderModule.vue";
import useVuelidate from "@vuelidate/core";
import ToolbarModule from "../../Modules/Others/ToolbarModule.vue";
import SelectInput from "../../Components/inputs/SelectInput.vue";

import DefaultToast from "../../Components/messages/DefaultToast.vue";
import DefaultSelectionTable from "../../Components/tables/DefaultSelectionTable.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";

import { Head, useForm, usePage, router } from "@inertiajs/vue3";
import {
    IconDotsCircleHorizontal,
    IconFileInvoice,
    IconFilterOff,
    IconTrash,
    IconUserPlus,
} from "@tabler/icons-vue";
import { helpers, required } from "@vuelidate/validators";
import { computed, ref, watch } from "vue";
import { route } from "ziggy-js";
import DrawerStipendModule from "../../Modules/Others/DrawerStipendModule.vue";
import { usePermissions } from "../../Composables/usePermissions";

const page = usePage();
const { can } = usePermissions();
const toolbarRef = ref(null);
const toastRef = ref(null);
const timerBounce = ref(null);
const batchNumberTimer = ref(null);
const stipendDrawer = ref(null);
const searchInput = ref(null);
const deletingId = ref(null);
const lastFlashKey = ref(null);
const displayedBatchNumber = ref(null);

const form = useForm({
    id: null,
    region: page.props.payrollPermissions.regionLocked
        ? (page.props.agencyOption?.[0] ?? page.props.user.profile.agency_array ?? null)
        : page.props.user.profile.agency_array ?? null,
    academic_year: null,
    term: null,
    batch: null,
});
const deleteForm = useForm({});
const isRegionLocked = computed(() => Boolean(page.props.payrollPermissions.regionLocked));
const nextBatchNumber = computed(() => displayedBatchNumber.value);

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
        resubmitted_payroll: {
            label: "Resubmitted Payroll",
            class: "bg-cyan-50 text-cyan-600",
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

const truncateRemarks = (remarks, limit = 24) => {
    if (!remarks) return "";

    return remarks.length > limit ? `${remarks.slice(0, limit)}...` : remarks;
};

const canDeleteBatch = (batch) =>
    Boolean(batch?.permissions?.canDelete) && batch?.status !== "rejected_payroll";
const showBatchActionColumn = computed(() =>
    can("payroll.delete") || can("payroll.edit") || can("payroll.create"),
);

const selectedAcademicYear = (value) => value?.name ?? value;

const rules = computed(() => ({
    region: { required: helpers.withMessage("Region is required", required) },
    academic_year: {
        required: helpers.withMessage("Academic year is required", required),
        format: helpers.withMessage(
            "Format must be YYYY-YYYY (e.g., 2025-2026)",
            (value) => /^\d{4}-\d{4}$/.test(selectedAcademicYear(value) ?? ""),
        ),
    },
    term: {
        required: helpers.withMessage("Term is required", required),
    },
    batch: {
        available: helpers.withMessage(
            "Select region, academic term, and academic year to generate the next batch number",
            () => Boolean(nextBatchNumber.value),
        ),
    },
}));

const v$ = useVuelidate(rules, form);

const toggleModal = (res) => {
    displayedBatchNumber.value = null;
    toolbarRef.value.openModal();
};

const clearSearch = () => {
    searchInput.value = null;
};

const submitForm = () => {
    if (!nextBatchNumber.value) return;

    v$.value.$validate();

    if (!v$.value.$error) {
        form
            .transform((data) => ({
                ...data,
                academic_year: selectedAcademicYear(data.academic_year),
                batch: String(nextBatchNumber.value),
            }))
            .post(route("stipends.store"), {
                onSuccess: () => {
                    toolbarRef.value?.closeModal();
                    form.reset();
                    form.region = page.props.payrollPermissions.regionLocked
                        ? (page.props.agencyOption?.[0] ?? page.props.user.profile.agency_array ?? null)
                        : page.props.user.profile.agency_array ?? null;
                    displayedBatchNumber.value = null;
                    v$.value.$reset();
                },
                onFinish: () => form.transform((data) => data),
            });
    }
};

const openModal = (event) => {
    const payloads = ["details", "payrollRecipients", "signatoryOptions"];

    if (event.permissions?.canEdit) {
        payloads.push("eligibleScholars");
    }

    router.reload({
        data: { id: event.id },
        only: payloads,
        onSuccess: () => {
            stipendDrawer.value = true;
        },
    });
};

const deleteBatch = (event, batch) => {
    event?.stopPropagation();
    if (!batch?.id || !canDeleteBatch(batch)) return;

    deletingId.value = batch.id;
    deleteForm.delete(route("stipends.destroy", { id: batch.id, type: "batch" }), {
        preserveScroll: true,
        onSuccess: () => {
            loadPage(page.props.batches.current_page);
        },
        onFinish: () => {
            deletingId.value = null;
        },
    });
};

const loadPage = (page) => {
    router.get(
        route("stipends"),
        {
            page,
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

watch(
    () => searchInput.value,
    () => {
        clearTimeout(timerBounce.value);
        timerBounce.value = setTimeout(() => {
            loadPage(1);
        }, 300);
    },
);

const clearBatchFilters = () => {
    if (isRegionLocked.value) {
        filterRegion.value = lockedRegionOption();
    } else {
        filterRegion.value = null;
    }

    filterTerm.value = null;
    filterAcademicYear.value = null;
    filterStatus.value = null;
    loadPage(1);
};

watch(
    () => [
        filterRegion.value,
        filterTerm.value,
        filterAcademicYear.value,
        filterStatus.value,
    ],
    () => {
        clearTimeout(timerBounce.value);
        timerBounce.value = setTimeout(() => {
            loadPage(1);
        }, 300);
    },
);

watch(
    () => [form.region, form.term, form.academic_year],
    () => {
        clearTimeout(batchNumberTimer.value);

        const academicYear = selectedAcademicYear(form.academic_year);

        if (!form.region || !form.term || !academicYear) {
            displayedBatchNumber.value = null;
            return;
        }

        displayedBatchNumber.value = null;
        batchNumberTimer.value = setTimeout(() => {
            router.reload({
                data: {
                    batch_region: form.region,
                    batch_term: form.term,
                    batch_academic_year: academicYear,
                },
                only: ["nextBatchNumber"],
                preserveState: true,
                preserveScroll: true,
                onSuccess: () => {
                    displayedBatchNumber.value = page.props.nextBatchNumber ?? null;
                },
            });
        }, 250);
    },
    { deep: true },
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
