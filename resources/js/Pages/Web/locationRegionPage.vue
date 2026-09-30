<template>
    <Head title="Regions" />
    <AuthLayout>
        <div class="locations-page flex h-full min-h-0 w-full flex-col gap-4 overflow-hidden text-slate-800 dark:text-gray-100">
            <div class="shrink-0 border-b border-slate-200 pb-4 dark:border-gray-700">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="flex min-w-0 items-start gap-3"><div class="flex size-10 shrink-0 items-center justify-center rounded-md bg-blue-600 text-white"><IconMapPin :size="21" /></div><HeaderModule title="Regions" description="Manage regional names, codes, and island groups." /></div>
                    <DefaultButton size="small" label="Create Region" severity="secondary" outlined :icon="IconCirclePlusFilled" class="self-start xl:self-auto" @click="toggleModal({ type: 'create' })" />
                </div>
            </div>
            <div class="flex min-h-0 flex-1 flex-col gap-3 overflow-auto">
                <ToolbarModule
                    v-model="searchInput"
                    @deleteSearch="clearSearch"
                    @saveForm="submitForm"
                    button-label="Create"
                    :dialog-title="!regionForm.id ? 'Create Region' : 'Edit Region'"
                    dialog-description="Enter the region name, code, and island group."
                    :dialog-button-loading="regionForm.processing"
                    :dialog-icon="IconMapPin"
                    dialog-button-label="Save"
                    :message-has-errors="regionForm.hasErrors"
                    :message-errors="regionForm.errors"
                    @buttonOpenModal="toggleModal({ type: 'create' })"
                    message-type="error"
                    :button-visible="false"
                    ref="toolbarRef"
                >
                    <template #form>
                        <div class="flex flex-col gap-3 mt-5">
                            <TextInput
                                v-model="regionForm.name"
                                label="Name"
                            ></TextInput>
                            <TextInput
                                v-model="regionForm.region"
                                label="Region"
                            ></TextInput>
                            <TextInput
                                v-model="regionForm.code"
                                label="Code"
                            ></TextInput>
                            <SelectInput
                                label="Island"
                                v-model="regionForm.island"
                                :options="islandOption"
                                :clearable="true"
                                capitalize
                            ></SelectInput>
                        </div>
                    </template>
                </ToolbarModule>
                <DefaultTable
                    :items="page.props.region.data"
                    :pagination="{
                        total: page.props.region.total,
                        perPage: page.props.region.per_page,
                        currentPage: page.props.region.current_page,
                    }"
                    @paginate="loadPage"
                >
                    <Column field="name" header="Name"> </Column>
                    <Column field="region" header="Region"> </Column>

                    <Column field="code">
                        <template #header>
                            <div class="w-full flex justify-center">
                                <p class="font-semibold">Region Code</p>
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
                    <Column field="island">
                        <template #header>
                            <div class="w-full flex justify-center">
                                <p class="font-semibold">Island</p>
                            </div>
                        </template>
                        <template #body="props">
                            <div
                                class="flex items-center justify-center w-full"
                            >
                                <div
                                    :class="[
                                        props.data.island == 'luzon'
                                            ? 'text-blue-600 bg-blue-100'
                                            : props.data.island == 'visayas'
                                            ? 'text-yellow-600 bg-yellow-100'
                                            : 'text-red-600 bg-red-100',
                                        'text-xs px-4 rounded-lg font-semibold py-1 capitalize',
                                    ]"
                                >
                                    {{ props.data.island }}
                                </div>
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
const regionForm = useForm({
    id: null,
    name: null,
    region: null,
    code: null,
    island: null,
    isActive: false,
});

const islandOption = [
    { id: 1, name: "luzon" },
    { id: 1, name: "visayas" },
    { id: 1, name: "mindanao" },
];

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
            disabled: selectedRow.value.is_lock ? true : false,
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
            disabled: selectedRow.value.is_lock ? true : false,
            command: () => {
                deleteRow(selectedRow.value.id);
            },
        },
    ];
});

const toggleModal = (res) => {
    regionForm.resetAndClearErrors();

    if (res.type === "edit") {
        regionForm.id = res.data.id;
        regionForm.name = res.data.name;
        regionForm.region = res.data.region;
        regionForm.code = res.data.code;
        regionForm.island = res.data.island_array;
    }

    toolbarRef.value.openModal();
};

const deleteRow = (id) => {
    confirmRef.value.popupDialog(() => {
        regionForm.delete(
            route("location.regions.destroy", { id: id, type: "delete" }),
            {
                onSuccess: () => {
                    regionForm.resetAndClearErrors();
                    toastRef.value.show(page.props.flash);
                },
            }
        );
    });
};

const submitForm = () => {
    if (!regionForm.id) {
        regionForm.post(route("location.regions.store"), {
            onSuccess: () => {
                regionForm.resetAndClearErrors();
                toastRef.value.show(page.props.flash);
            },
        });
    } else {
        regionForm.put(
            route("location.regions.update", {
                id: regionForm.id,
                type: "form",
            }),
            {
                onSuccess: () => {
                    toolbarRef.value.closeModal();
                    regionForm.resetAndClearErrors();
                    toastRef.value.show(page.props.flash);
                },
            }
        );
    }
};
const updateStatus = (result) => {
    regionForm.isActive = result.is_active;
    regionForm.put(
        route("location.regions.update", { id: result.id, type: "status" }),
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
        route("location.regions"),
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
