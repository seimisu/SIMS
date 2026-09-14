<template>
    <Head title="Scholar Status" />
    <AuthLayout>
        <div class="flex flex-col w-full h-full gap-10">
            <div class="flex">
                <HeaderModule
                    title="List of status"
                    description="Cities information and management"
                />
            </div>
            <div class="flex-1 flex flex-col gap-2">
                <ToolbarModule
                    v-model="searchInput"
                    @deleteSearch="clearSearch"
                    @saveForm="submitForm"
                    button-label="Create"
                    :dialog-title="!statusForm.id ? 'Create Role' : 'Edit Role'"
                    dialog-description="Define a new role and configure its access permissions."
                    :dialog-button-loading="statusForm.processing"
                    :dialog-icon="IconUserCog"
                    dialog-button-label="Save"
                    :message-has-errors="statusForm.hasErrors"
                    :message-errors="statusForm.errors"
                    @buttonOpenModal="toggleModal({ type: 'create' })"
                    message-type="error"
                    ref="toolbarRef"
                >
                    <template #form>
                        <div class="flex flex-col gap-3 mt-5">
                            <TextInput
                                v-model="statusForm.name"
                                label="Name"
                            ></TextInput>
                            <TextInput
                                v-model="statusForm.type"
                                label="Type"
                            ></TextInput>
                            <TextInput
                                v-model="statusForm.icon"
                                label="Icon"
                            ></TextInput>

                            <SelectInput
                                v-model="statusForm.color"
                                :options="page.props.colorOption"
                                label="Color"
                                clearable
                            >
                                <template #option="slot">
                                    <div
                                        :class="[
                                            slot.option.name,
                                            'px-5 py-1 rounded-lg',
                                        ]"
                                    >
                                        Sample Color
                                    </div>
                                </template>
                            </SelectInput>
                        </div>
                    </template>
                </ToolbarModule>
                <DefaultTable
                    :items="page.props.status?.data ?? []"
                    :pagination="{
                        total: page.props.status?.total ?? 0,
                        perPage: page.props.status?.per_page ?? 10,
                        currentPage: page.props.status?.current_page ?? 1,
                    }"
                    @paginate="loadPage"
                >
                    <Column header="Name">
                        <template #body="props">
                            <div
                                :class="[
                                    'flex items-center gap-1 w-fit',
                                    props.data.color_array?.textColor ??
                                        'text-slate-600',
                                ]"
                            >
                                <component
                                    :is="TablerIcons[props.data.icon] ?? IconLock"
                                    size="20"
                                />
                                <div class="capitalize">
                                    {{ props.data.name }}
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column field="type" header="Type" class="capitalize">
                    </Column>

                    <Column
                        field="created_by"
                        header="Created By"
                        class="!text-xs"
                    >
                    </Column>
                    <Column
                        field="updated_by"
                        header="Updated By"
                        class="!text-xs"
                    >
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
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import AuthLayout from "../../Layouts/AuthLayout.vue";
import HeaderModule from "../../Modules/Others/HeaderModule.vue";
import DefaultTable from "../../Components/tables/DefaultTable.vue";
import ToolbarModule from "../../Modules/Others/ToolbarModule.vue";
import TextInput from "../../Components/inputs/TextInput.vue";
import DefaultToggle from "../../Components/toggleswitches/DefaultToggle.vue";
import { computed, ref, watch } from "vue";
import {
    IconCheck,
    IconLock,
    IconUserCog,
    IconX,
    IconPencilCog,
    IconTrash,
} from "@tabler/icons-vue";
import * as TablerIcons from "@tabler/icons-vue";
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
const statusForm = useForm({
    id: null,
    name: null,
    icon: null,
    type: null,
    color: null,
    isActive: true,
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
    statusForm.resetAndClearErrors();

    if (res.type == "edit") {
        statusForm.id = res.data.id;
        statusForm.name = res.data.name;
        statusForm.type = res.data.type;
        statusForm.icon = res.data.icon;
        statusForm.color = res.data.color_array;
    }

    toolbarRef.value.openModal();
};

const deleteRow = (id) => {
    confirmRef.value.popupDialog(() => {
        statusForm.delete(route("status.destroy", { id: id, type: "delete" }), {
            onSuccess: () => {
                statusForm.resetAndClearErrors();
                toastRef.value.show(page.props.flash);
            },
        });
    });
};

const submitForm = () => {
    if (!statusForm.id) {
        statusForm.post(route("status.store"), {
            onSuccess: () => {
                statusForm.resetAndClearErrors();
                toastRef.value.show(page.props.flash);
            },
        });
    } else {
        statusForm.put(
            route("status.update", {
                id: statusForm.id,
                type: "form",
            }),
            {
                onSuccess: () => {
                    toolbarRef.value.closeModal();
                    statusForm.resetAndClearErrors();
                    toastRef.value.show(page.props.flash);
                },
            }
        );
    }
};
const updateStatus = (result) => {
    statusForm.isActive = result.is_active;
    statusForm.put(route("status.update", { id: result.id, type: "status" }), {
        onSuccess: () => {
            statusForm.clearErrors();
            toastRef.value.show(page.props.flash);
        },
    });
};

const clearSearch = () => {
    searchInput.value = null;
};

const loadPage = (page) => {
    router.get(
        route("statuses"),
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
