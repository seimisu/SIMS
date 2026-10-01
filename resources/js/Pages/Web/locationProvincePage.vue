<template>
    <Head title="Provinces" />
    <AuthLayout>
        <div class="locations-page flex h-full min-h-0 w-full flex-col gap-4 overflow-hidden text-slate-800 dark:text-gray-100">
            <div class="shrink-0 border-b border-slate-200 pb-4 dark:border-gray-700">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="flex min-w-0 items-start gap-3"><div class="flex size-10 shrink-0 items-center justify-center rounded-md bg-blue-600 text-white"><IconMapPin :size="21" /></div><HeaderModule title="Provinces" description="Manage provinces and their regional assignments." /></div>
                    <DefaultButton size="small" label="Create Province" severity="secondary" outlined :icon="IconCirclePlusFilled" class="self-start xl:self-auto" @click="toggleModal({ type: 'create' })" />
                </div>
            </div>
            <div class="flex min-h-0 flex-1 flex-col gap-3 overflow-auto">
                <ToolbarModule
                    v-model="searchInput"
                    @deleteSearch="clearSearch"
                    @saveForm="submitForm"
                    button-label="Create"
                    :dialog-title="
                        !provinceForm.id ? 'Create Province' : 'Edit Province'
                    "
                    dialog-description="Enter the province details and assign its region."
                    :dialog-button-loading="provinceForm.processing"
                    :dialog-icon="IconMapPin"
                    dialog-button-label="Save"
                    :message-has-errors="provinceForm.hasErrors"
                    :message-errors="provinceForm.errors"
                    @buttonOpenModal="toggleModal({ type: 'create' })"
                    message-type="error"
                    :button-visible="false"
                    ref="toolbarRef"
                >
                    <template #form>
                        <div class="flex flex-col gap-3 mt-5">
                            <TextInput
                                v-model="provinceForm.name"
                                label="Name"
                            ></TextInput>
                            <TextInput
                                v-model="provinceForm.oldName"
                                label="Old Name"
                            ></TextInput>
                            <TextInput
                                v-model="provinceForm.code"
                                label="Code"
                            ></TextInput>
                            <SelectInput
                                label="Region"
                                v-model="provinceForm.region"
                                :options="page.props.provinceOption"
                                :clearable="true"
                                capitalize
                                filter
                            ></SelectInput>
                        </div>
                    </template>
                </ToolbarModule>
                <DefaultTable
                    :items="page.props.provinces.data"
                    :pagination="{
                        total: page.props.provinces.total,
                        perPage: page.props.provinces.per_page,
                        currentPage: page.props.provinces.current_page,
                    }"
                    @paginate="loadPage"
                >
                    <Column field="name" header="Name"> </Column>
                    <Column field="old_name">
                        <template #header>
                            <div class="w-full flex justify-start">
                                <p class="font-semibold">Old Name</p>
                            </div>
                        </template>
                        <template #body="props">
                            <div class="flex justify-start w-full">
                                <div v-if="props.data.old_name">
                                    {{ props.data.old_name }}
                                </div>
                                <div
                                    class="text-gray-400 text-xs font-light"
                                    v-else
                                >
                                    not set yet
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column field="code">
                        <template #header>
                            <div class="w-full flex justify-center">
                                <p class="font-semibold">Province Code</p>
                            </div>
                        </template>
                        <template #body="props">
                            <div
                                class="flex items-center justify-center w-full"
                            >
                                <div class="text-xs font-semibold">
                                    {{ props.data.code }}
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column field="region_code">
                        <template #header>
                            <div class="w-full flex justify-start">
                                <p class="font-semibold">Region</p>
                            </div>
                        </template>
                        <template #body="props">
                            <div class="flex justify-start w-full">
                                <div>{{ props.data.region_code.region }}</div>
                            </div>
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
                                    :disabled="props.data.is_lock"
                                    v-if="!props.data.is_lock"
                                    @update-value="updateStatus(props.data)"
                                />
                                <div
                                    v-else
                                    v-tooltip.top="'Cant deactive this role.'"
                                >
                                    <IconLock size="18" />
                                </div>
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
    IconCirclePlusFilled,
    IconMapPin,
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
const provinceForm = useForm({
    id: null,
    name: null,
    oldName: null,
    code: null,
    region: null,
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
    provinceForm.resetAndClearErrors();

    if (res.type == "edit") {
        provinceForm.id = res.data.id;
        provinceForm.name = res.data.name;
        provinceForm.oldName = res.data.old_name;
        provinceForm.code = res.data.code;
        provinceForm.region = res.data.region_array;
    }

    toolbarRef.value.openModal();
};

const deleteRow = (id) => {
    confirmRef.value.popupDialog(() => {
        provinceForm.delete(
            route("location.provinces.destroy", { id: id, type: "delete" }),
            {
                onSuccess: () => {
                    provinceForm.resetAndClearErrors();
                    toastRef.value.show(page.props.flash);
                },
            }
        );
    });
};

const submitForm = () => {
    if (!provinceForm.id) {
        provinceForm.post(route("location.provinces.store"), {
            onSuccess: () => {
                provinceForm.resetAndClearErrors();
                toastRef.value.show(page.props.flash);
            },
        });
    } else {
        provinceForm.put(
            route("location.provinces.update", {
                id: provinceForm.id,
                type: "form",
            }),
            {
                onSuccess: () => {
                    toolbarRef.value.closeModal();
                    provinceForm.resetAndClearErrors();
                    toastRef.value.show(page.props.flash);
                },
            }
        );
    }
};
const updateStatus = (result) => {
    provinceForm.isActive = result.is_active;
    provinceForm.put(
        route("location.provinces.update", { id: result.id, type: "status" }),
        {
            onSuccess: () => {
                provinceForm.clearErrors();
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
        route("location.provinces"),
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
