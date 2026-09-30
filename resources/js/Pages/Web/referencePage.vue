<template>
    <Head title="References" />
    <AuthLayout>
        <div class="references-page flex h-full min-h-0 w-full flex-col gap-4 overflow-hidden text-slate-800 dark:text-gray-100">
            <div class="shrink-0 border-b border-slate-200 pb-4 dark:border-gray-700">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="flex min-w-0 items-start gap-3"><div class="flex size-10 shrink-0 items-center justify-center rounded-md bg-blue-600 text-white"><IconListDetails :size="21" /></div><HeaderModule title="References" description="Manage reusable classifications, types, labels, and colors." /></div>
                    <DefaultButton size="small" label="Create Reference" severity="secondary" outlined :icon="IconCirclePlusFilled" class="self-start xl:self-auto" @click="toggleModal({ type: 'create' })" />
                </div>
            </div>
            <div class="flex min-h-0 flex-1 flex-col gap-3 overflow-auto">
                <ToolbarModule
                    v-model="searchInput"
                    @deleteSearch="clearSearch"
                    @saveForm="submitForm"
                    button-label="Create"
                    :dialog-title="
                        !referenceForm.id
                            ? 'Create Reference'
                            : 'Edit Reference'
                    "
                    dialog-description="Define a reusable reference value and its classification."
                    :dialog-button-loading="referenceForm.processing"
                    :dialog-icon="IconListDetails"
                    dialog-button-label="Save"
                    :message-has-errors="referenceForm.hasErrors"
                    :message-errors="referenceForm.errors"
                    @buttonOpenModal="toggleModal({ type: 'create' })"
                    message-type="error"
                    :button-visible="false"
                    ref="toolbarRef"
                >
                    <template #form>
                        <div class="flex flex-col gap-3 mt-5">
                            <TextInput
                                v-model="referenceForm.name"
                                label="Name"
                            ></TextInput>
                            <SelectInput
                                label="Classification"
                                v-model="referenceForm.class"
                                :options="page.props.classOption"
                                :clearable="true"
                                capitalize
                            ></SelectInput>
                            <SelectInput
                                label="Type"
                                v-model="referenceForm.typeReference"
                                :options="page.props.typeOption"
                                :clearable="true"
                                capitalize
                            ></SelectInput>
                            <TextInput
                                v-model="referenceForm.others"
                                label="Other"
                            ></TextInput>
                            <SelectInput
                                label="Color"
                                v-model="referenceForm.color"
                                :options="colors"
                                :clearable="true"
                                capitalize
                            ></SelectInput>
                        </div>
                    </template>
                </ToolbarModule>
                <DefaultTable
                    :items="page.props.references.data"
                    :pagination="{
                        total: page.props.references.total,
                        perPage: page.props.references.per_page,
                        currentPage: page.props.references.current_page,
                    }"
                    @paginate="loadPage"
                >
                    <Column header="Name" class="capitalize w-[30%]">
                        <template #body="props">
                            <div class="flex items-center w-full">
                                <IconPointFilled
                                    v-show="props.data.color"
                                    size="15"
                                    :class="[
                                        ` text-${props.data.color}-500`,
                                        ' mr-1  ',
                                    ]"
                                />
                                <p class="flex-1">
                                    {{ props.data.name }}
                                </p>
                            </div>
                        </template>
                    </Column>
                    <Column field="classification" header="Classification">
                    </Column>
                    <Column field="type" header="Type"> </Column>
                    <Column field="others" header="Others"> </Column>
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
                                            @click="item.command"
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
import AuthLayout from "../../Layouts/AuthLayout.vue";
import HeaderModule from "../../Modules/Others/HeaderModule.vue";
import DefaultTable from "../../Components/tables/DefaultTable.vue";
import ToolbarModule from "../../Modules/Others/ToolbarModule.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import TextInput from "../../Components/inputs/TextInput.vue";
import DefaultToggle from "../../Components/toggleswitches/DefaultToggle.vue";
import DefaultToast from "../../Components/messages/DefaultToast.vue";
import DefaultConfirmDialog from "../../Components/dialogs/DefaultConfirmDialog.vue";
import { computed, ref, watch } from "vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import {
    IconCheck,
    IconX,
    IconPencilCog,
    IconTrash,
    IconPointFilled,
    IconCirclePlusFilled,
    IconListDetails,
} from "@tabler/icons-vue";
import SelectInput from "../../Components/inputs/SelectInput.vue";

const page = usePage();
const searchInput = ref(null);
const timerBounce = ref(null);
const selectedRow = ref(null);
const toolbarRef = ref(null);
const toastRef = ref(null);
const confirmRef = ref(null);
const menu = ref(null);
const colors = [
    { id: "blue", name: "Blue" },
    { id: "green", name: "Green" },
    { id: "red", name: "Red" },
    { id: "orange", name: "Orange" },
    { id: "purple", name: "Purple" },
    { id: "teal", name: "Teal" },
    { id: "cyan", name: "Cyan" },
    { id: "pink", name: "Pink" },
    { id: "yellow", name: "Yellow" },
    { id: "indigo", name: "Indigo" },
    { id: "lime", name: "Lime" },
    { id: "amber", name: "Amber" },
    { id: "rose", name: "Rose" },
    { id: "slate", name: "Slate" },
    { id: "stone", name: "Stone" },
    { id: "gray", name: "Gray" },
    { id: "fuchsia", name: "Fuchsia" },
    { id: "violet", name: "Violet" },
    { id: "emerald", name: "Emerald" },
];
const referenceForm = useForm({
    id: null,
    name: null,
    class: null,
    typeReference: null,
    others: null,
    color: null,
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
    referenceForm.resetAndClearErrors();

    if (res.type === "edit") {
        referenceForm.id = res.data.id;
        referenceForm.name = res.data.name;
        referenceForm.class = res.data.classification_array;
        referenceForm.others = res.data.others;
        referenceForm.color = res.data.color_array;
        referenceForm.typeReference = res.data.type_array;
    }

    toolbarRef.value.openModal();
};

const deleteRow = (id) => {
    confirmRef.value.popupDialog(() => {
        referenceForm.delete(
            route("academic.references.destroy", { id: id, type: "delete" }),
            {
                onSuccess: () => {
                    referenceForm.resetAndClearErrors();
                    toastRef.value.show(page.props.flash);
                },
            }
        );
    });
};

const submitForm = () => {
    if (!referenceForm.id) {
        referenceForm.post(route("academic.references.store"), {
            onSuccess: () => {
                referenceForm.resetAndClearErrors();
                toastRef.value.show(page.props.flash);
            },
        });
    } else {
        referenceForm.put(
            route("academic.references.update", {
                id: referenceForm.id,
                type: "form",
            }),
            {
                onSuccess: () => {
                    toolbarRef.value.closeModal();
                    referenceForm.resetAndClearErrors();
                    toastRef.value.show(page.props.flash);
                },
            }
        );
    }
};
const updateStatus = (result) => {
    referenceForm.isActive = result.is_active;
    referenceForm.put(
        route("academic.references.update", { id: result.id, type: "status" }),
        {
            onSuccess: () => {
                toastRef.value.show(page.props.flash);
            },
        }
    );
};

const clearSearch = () => {
    searchInput.value = null;
};

const loadPage = (page) => {
    router.get(
        route("academic.references"),
        {
            page,
            search: searchInput.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        }
    );
};

watch(
    () => searchInput.value,
    () => {
        clearTimeout(timerBounce.value);
        timerBounce.value = setTimeout(() => {
            loadPage(1);
        }, 300);
    }
);
</script>
