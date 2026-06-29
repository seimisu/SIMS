<template>
    <Head title="Stipend Management" />
    <AuthLayout>
        <div class="flex flex-col w-full h-full gap-5">
            <div class="flex">
                <HeaderModule
                    title="Stipend Management"
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
                    :dialog-icon="IconUserPlus"
                    dialog-button-label="Save"
                    :message-has-errors="form.hasErrors"
                    :message-errors="form.errors"
                    @buttonOpenModal="toggleModal({ type: 'create' })"
                    :button-visible="can('payroll.create')"
                    message-type="error"
                    ref="toolbarRef"
                >
                    <template #form>
                        <div class="mt-5 flex flex-col gap-3">
                            <SelectInput
                                label="Region"
                                v-model="form.region"
                                :options="page.props.agencyOption"
                                :clearable="true"
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
                            <TextInput
                                v-model="form.academic_year"
                                label="Academic year"
                                placeholder="e.g. 2025-2026"
                                :errorMark="v$.academic_year.$error"
                                :tooltip="v$.academic_year.$errors[0]?.$message"
                            />

                            <TextInput
                                v-model="form.batch"
                                label="Batch Number"
                                capitalize
                                placeholder="Ex. 1"
                                :errorMark="v$.batch.$error"
                                :tooltip="v$.batch.$errors[0]?.$message"
                            />
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
                            <div class="capitalize">
                                {{ props.data.remarks }}
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
                    <Column>
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
                                        !props.data.permissions?.canDelete
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
import TextInput from "../../Components/inputs/TextInput.vue";
import SelectInput from "../../Components/inputs/SelectInput.vue";

import DefaultToast from "../../Components/messages/DefaultToast.vue";
import DefaultSelectionTable from "../../Components/tables/DefaultSelectionTable.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";

import { Head, useForm, usePage, router } from "@inertiajs/vue3";
import {
    IconDotsCircleHorizontal,
    IconFileInvoice,
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
const stipendDrawer = ref(null);
const searchInput = ref(null);
const deletingId = ref(null);
const lastFlashKey = ref(null);

const form = useForm({
    id: null,
    region: page.props.user.profile.agency_array ?? null,
    academic_year: null,
    term: null,
    batch: null,
});
const deleteForm = useForm({});

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
            label: "Rejected Payroll",
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

const rules = computed(() => ({
    region: { required: helpers.withMessage("Region is required", required) },
    academic_year: {
        required: helpers.withMessage("Academic year is required", required),
        format: helpers.withMessage(
            "Format must be YYYY-YYYY (e.g., 2025-2026)",
            helpers.regex(/^\d{4}-\d{4}$/),
        ),
    },
    term: {
        required: helpers.withMessage("Term is required", required),
    },
    batch: { required: helpers.withMessage("Batch is required", required) },
}));

const v$ = useVuelidate(rules, form);

const toggleModal = (res) => {
    toolbarRef.value.openModal();
};

const clearSearch = () => {
    searchInput.value = null;
};

const submitForm = () => {
    v$.value.$validate();

    if (!v$.value.$error) {
        form.post(route("stipends.store"));
    }
};

const openModal = (event) => {
    const payloads = ["details", "payrollRecipients", "allowanceOptions"];

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
    if (!batch?.id) return;

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
            search: searchInput.value,
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
