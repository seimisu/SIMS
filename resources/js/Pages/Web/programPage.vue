<template>
    <Head title="Programs" />
    <AuthLayout>
        <div class="programs-page flex h-full min-h-0 w-full flex-col gap-4 overflow-hidden text-slate-800 dark:text-gray-100">
            <div class="shrink-0 border-b border-slate-200 pb-4 dark:border-gray-700">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="flex min-w-0 items-start gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-md bg-blue-600 text-white"><IconCertificate :size="21" /></div>
                        <HeaderModule title="Programs" description="Manage scholarship programs, types, and program classifications." />
                    </div>
                    <DefaultButton size="small" label="Create Program" severity="secondary" outlined :icon="IconCirclePlusFilled" class="self-start xl:self-auto" @click="toggleModal({ type: 'create' })" />
                </div>
            </div>
            <div class="flex min-h-0 flex-1 flex-col gap-3 overflow-auto">
                <ToolbarModule
                    v-model="searchInput"
                    @deleteSearch="clearSearch"
                    @saveForm="submitForm"
                    button-label="Create"
                    :dialog-title="
                        !programForm.id ? 'Create Program' : 'Edit Program'
                    "
                    dialog-description="Define the program name, scholarship, type, and classification."
                    :dialog-button-loading="programForm.processing"
                    :dialog-icon="IconCertificate"
                    dialog-button-label="Save"
                    :message-has-errors="programForm.hasErrors"
                    :message-errors="programForm.errors"
                    @buttonOpenModal="toggleModal({ type: 'create' })"
                    message-type="error"
                    :button-visible="false"
                    ref="toolbarRef"
                >
                    <template #form>
                        <div class="flex flex-col gap-3 mt-5">
                            <TextInput
                                v-model="programForm.name"
                                label="Name"
                            ></TextInput>
                            <TextInput
                                v-model="programForm.description"
                                label="Description"
                            ></TextInput>
                            <SelectInput
                                label="Scholarship"
                                v-model="programForm.scholarship"
                                :options="page.props.scholarshipOptions"
                                :clearable="true"
                                capitalize
                            ></SelectInput>
                            <SelectInput
                                label="Type"
                                v-model="programForm.type"
                                :options="page.props.typeOptions"
                                :clearable="true"
                                capitalize
                            ></SelectInput>
                            <div>
                                <Divider type="dashed" />
                                <div class="flex justify-between items-center">
                                    <div class="text-sm">
                                        Make this Sub Program?
                                    </div>
                                    <DefaultToggle
                                        v-model="programForm.isSub"
                                        :check-icon="IconCheck"
                                        :un-check-icon="IconX"
                                    />
                                </div>
                            </div>
                        </div>
                    </template>
                </ToolbarModule>
                <DefaultTable
                    :items="page.props.programs.data"
                    :pagination="{
                        total: page.props.programs.total,
                        perPage: page.props.programs.per_page,
                        currentPage: page.props.programs.current_page,
                    }"
                    @paginate="loadPage"
                >
                    <Column
                        field="name"
                        header="Name"
                        class="font-semibold"
                    ></Column>
                    <Column field="others" header="Description"></Column>
                    <Column
                        field="program_array.name"
                        header="Scholarship"
                    ></Column>
                    <Column field="type_array.name" header="Type"></Column>
                    <Column field="is_sub" class="!text-center">
                        <template #header>
                            <div
                                class="flex justify-center w-full font-semibold"
                            >
                                <div>Is Sub</div>
                            </div>
                        </template>
                        <template #body="slotProps">
                            <span
                                :class="[
                                    slotProps.data.is_sub
                                        ? 'bg-blue-100 text-blue-600 '
                                        : 'bg-orange-100 text-orange-600',
                                    'text-xs font-semibold px-4 py-1 rounded-full',
                                ]"
                            >
                                {{
                                    slotProps.data.is_sub
                                        ? "Subprogram"
                                        : "Main"
                                }}
                            </span>
                        </template>
                    </Column>
                    <Column field="status" class="w-[5%]">
                        <template #header>
                            <div class="w-full flex justify-center">
                                <p class="font-semibold">Status</p>
                            </div>
                        </template>
                        <template #body="props">
                            <div
                                class="flex items-center justify-center w-full"
                            >
                                <DefaultToggle
                                    :check-icon="IconCheck"
                                    :un-check-icon="IconX"
                                    v-model="props.data.is_active"
                                    @update-value="updateStatus(props.data)"
                                />
                            </div>
                        </template>
                    </Column>
                    <Column field="options" class="w-[5%]">
                        <template #body="prop">
                            <div class="flex w-full justify-end">
                                <Button
                                    text
                                    v-tooltip.top="'Options'"
                                    rounded
                                    size="small"
                                    severity="secondary"
                                    icon="pi pi-ellipsis-v"
                                    @click="(e) => toggleOption(e, prop.data)"
                                />
                                <Menu
                                    ref="menu"
                                    :model="menuItems"
                                    :popup="true"
                                >
                                    <template #item="{ item, props }">
                                        <a
                                            v-ripple
                                            class="flex items-center"
                                            v-bind="props.action"
                                        >
                                            <div>
                                                <component
                                                    :is="item.icon"
                                                    :class="item.class"
                                                    size="20"
                                                    stroke-width="1.5"
                                                ></component>
                                            </div>
                                            <span class="ml-2 text-xs">{{
                                                item.label
                                            }}</span>
                                        </a>
                                    </template>
                                </Menu>
                            </div>
                        </template>
                    </Column>
                </DefaultTable>
            </div>
        </div>
        <DefaultToast ref="toastRef" />
        <DefaultConfirmDialog ref="confirmRef" />
    </AuthLayout>
</template>
<script setup>
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import AuthLayout from "../../Layouts/AuthLayout.vue";
import HeaderModule from "../../Modules/Others/HeaderModule.vue";
import DefaultTable from "../../Components/tables/DefaultTable.vue";
import ToolbarModule from "../../Modules/Others/ToolbarModule.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import TextInput from "../../Components/inputs/TextInput.vue";
import DefaultToggle from "../../Components/toggleswitches/DefaultToggle.vue";
import { computed, ref, watch } from "vue";
import {
    IconCheck,
    IconLock,
    IconX,
    IconPencilCog,
    IconTrash,
    IconCertificate,
    IconCirclePlusFilled,
} from "@tabler/icons-vue";
import DefaultToast from "../../Components/messages/DefaultToast.vue";
import DefaultConfirmDialog from "../../Components/dialogs/DefaultConfirmDialog.vue";
import SelectInput from "../../Components/inputs/SelectInput.vue";

const page = usePage();
const searchInput = ref(null);
const timerBounce = ref(null);
const selectedRow = ref(null);
const toolbarRef = ref(null);
const toastRef = ref(null);
const confirmRef = ref(null);
const menu = ref(null);
const programForm = useForm({
    id: null,
    name: null,
    description: null,
    scholarship: null,
    type: null,
    isSub: false,
    isActive: false,
});

const toggleOption = (event, rowData) => {
    selectedRow.value = rowData;
    menu.value.toggle(event);
};

const menuItems = computed(() => {
    if (!selectedRow.value) return [];

    return [
        {
            label: "Edit",
            icon: IconPencilCog,
            class: "text-blue-500",
            command: () => {
                toggleModal({
                    type: "edit",
                    data: selectedRow.value,
                });
            },
        },
        {
            label: "Delete",
            icon: IconTrash,
            class: "text-red-500",
            command: () => {
                deleteRow(selectedRow.value.id);
            },
        },
    ];
});

const toggleModal = (res) => {
    programForm.resetAndClearErrors();

    if (res.type == "edit") {
        programForm.id = res.data.id;
        programForm.name = res.data.name;
        programForm.description = res.data.others;
        programForm.scholarship = res.data.program_array;
        programForm.type = res.data.type_array;
        programForm.isSub = Boolean(res.data.is_sub);
    }

    toolbarRef.value.openModal();
};

const deleteRow = (id) => {
    confirmRef.value.popupDialog(() => {
        programForm.delete(
            route("programs.destroy", { id: id, type: "delete" }),
            {
                onSuccess: () => {
                    programForm.resetAndClearErrors();
                    toastRef.value.show(page.props.flash);
                },
            },
        );
    });
};

const submitForm = () => {
    if (!programForm.id) {
        programForm.post(route("programs.store"), {
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                programForm.resetAndClearErrors();
                toastRef.value.show(page.props.flash);
            },
        });
    } else {
        programForm.put(
            route("programs.update", {
                id: programForm.id,
                type: "form",
            }),
            {
                onSuccess: () => {
                    toolbarRef.value.closeModal();
                    programForm.resetAndClearErrors();
                    toastRef.value.show(page.props.flash);
                },
            },
        );
    }
};
const updateStatus = (result) => {
    programForm.isActive = result.is_active;
    programForm.put(
        route("programs.update", { id: result.id, type: "status" }),
        {
            onSuccess: () => {
                programForm.clearErrors();
                toastRef.value.show(page.props.flash);
            },
        },
    );
};

const clearSearch = () => {
    searchInput.value = null;
};

const loadPage = (page) => {
    router.get(
        route("programs"),
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
</script>

<style scoped>
:global(.dark .programs-page .p-datatable-header-cell),
:global(.dark .programs-page .p-datatable-column-header-content) { color: #e5e7eb !important; }
:global(.programs-page .p-datatable-tbody > tr > td) { padding-top: 0.65rem; padding-bottom: 0.65rem; }
</style>
