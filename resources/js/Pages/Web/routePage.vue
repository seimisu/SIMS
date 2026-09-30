<template>
    <Head title="Routes" />
    <AuthLayout>
        <div class="routes-page flex h-full min-h-0 w-full flex-col gap-4 overflow-hidden text-slate-800 dark:text-gray-100">
            <div class="shrink-0 border-b border-slate-200 pb-4 dark:border-gray-700">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="flex min-w-0 items-start gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-md bg-blue-600 text-white shadow-sm dark:bg-blue-500"><IconRoute :size="21" stroke-width="1.8" /></div>
                        <HeaderModule title="Navigation Settings" description="Create, organize, and manage system navigation menus." />
                    </div>
                    <DefaultButton size="small" label="Create Navigation" severity="secondary" outlined :icon="IconCirclePlusFilled" class="self-start xl:self-auto" @click="toggleModal({ type: 'create' })" />
                </div>
            </div>

            <div class="flex min-h-0 flex-1 flex-col gap-3 overflow-auto">
                <ToolbarModule
                    v-model="searchInput"
                    @deleteSearch="clearSearch"
                    @saveForm="submitForm"
                    button-label="Create"
                    :dialog-title="
                        !routeForm.id ? 'Create Route' : 'Edit Route'
                    "
                    dialog-description="Fill out the fields below to create a new route in the system."
                    :dialog-button-loading="routeForm.processing"
                    :dialog-icon="IconCategory"
                    dialog-button-label="Save"
                    :message-has-errors="routeForm.hasErrors"
                    :message-errors="routeForm.errors"
                    @buttonOpenModal="toggleModal({ type: 'create' })"
                    message-type="error"
                    :button-visible="false"
                    ref="toolbarRef"
                >
                    <template #form>
                        <div class="flex flex-col gap-2 mt-5">
                            <SelectMultiInput
                                label="Role"
                                v-model="routeForm.role"
                                :options="page.props.roleOption"
                                :clearable="true"
                                filter
                                capitalize
                            ></SelectMultiInput>
                            <TextInput
                                v-model="routeForm.label"
                                label="Label"
                                capitalize
                            ></TextInput>
                            <TextInput
                                v-model="routeForm.slug"
                                label="Slug"
                            ></TextInput>
                            <TextInput
                                v-model="routeForm.icon"
                                label="Icon"
                                message="Check TablerIcon website for the list of available icons."
                            ></TextInput>

                            <TextInput
                                v-model="routeForm.route"
                                label="Route"
                                message="Check the route path inside your routes folder."
                            ></TextInput>

                            <TextInput
                                v-model="routeForm.component"
                                label="Component"
                                message="Specify the Vue component that should render for this route."
                            ></TextInput>
                            <div class="">
                                <Divider type="dashed" />
                                <div class="flex justify-between items-center">
                                    <div class="text-sm">
                                        Is this a sub-menu?
                                    </div>
                                    <DefaultToggle
                                        v-model="routeForm.isSubmenu"
                                        :disabled="!routeForm.route && route.id"
                                        :check-icon="IconCheck"
                                        :un-check-icon="IconX"
                                    />
                                </div>
                                <SelectInput
                                    class="mt-3"
                                    v-show="routeForm.isSubmenu"
                                    v-model="routeForm.submenu"
                                    :options="page.props.routeOption"
                                ></SelectInput>
                            </div>
                        </div>
                    </template>
                </ToolbarModule>

                <DefaultTreeTable :items="page.props.routes">
                    <Column header="Name" expander style="width: 40%">
                        <template #body="slotProps">
                            <div class="flex items-center gap-2">
                                <component
                                    :is="TablerIcons[slotProps.node.icon]"
                                />
                                <div class="capitalize">
                                    {{ slotProps.node.name }}
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column
                        header="Route"
                        field="route"
                        class="text-xs"
                    ></Column>
                    <Column header="Roles" class="text-xs">
                        <template #body="slotProps">
                            <div class="flex gap-1">
                                <div
                                    v-for="(item, index) in slotProps.node
                                        .roles"
                                    :key="index"
                                    class="flex"
                                >
                                    <div
                                        class="bg-blue-100 px-3 dark:text-gray-700 no-wrap capitalize py-1 rounded-xl"
                                    >
                                        {{ item.name }}
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column
                        header="Created By"
                        field="created_by"
                        class="text-xs"
                    ></Column>
                    <Column>
                        <template #header>
                            <div class="w-full flex justify-center">
                                <p class="font-semibold">Status</p>
                            </div>
                        </template>
                        <template #body="props">
                            <div
                                class="flex items-center justify-center w-full"
                            >
                                <div
                                    v-if="
                                        props.node.slug == 'routes' ||
                                        props.node.slug == 'admin-setting'
                                    "
                                    v-tooltip.top="'Cant deactive this route'"
                                >
                                    <TablerIcons.IconLock size="18" />
                                </div>
                                <DefaultToggle
                                    :check-icon="IconCheck"
                                    :un-check-icon="IconX"
                                    v-else
                                    v-model="props.node.is_active"
                                    @update-value="updateStatus(props.node)"
                                />
                            </div>
                        </template>
                    </Column>
                    <Column>
                        <template #body="prop">
                            <div class="flex w-full justify-end">
                                <Button
                                    text
                                    v-tooltip.top="'Options'"
                                    rounded
                                    size="small"
                                    severity="secondary"
                                    icon="pi pi-ellipsis-v"
                                    @click="(e) => toggleOption(e, prop.node)"
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
                </DefaultTreeTable>
            </div>
        </div>
        <DefaultToast ref="toastRef" />
        <DefaultConfirmDialog ref="confirmRef" />
    </AuthLayout>
</template>
<script setup>
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import { ref, watch, computed } from "vue";
import {
    IconCategory,
    IconCheck,
    IconX,
    IconPencilCog,
    IconTrash,
    IconCirclePlusFilled,
    IconRoute,
} from "@tabler/icons-vue";
import * as TablerIcons from "@tabler/icons-vue";
import AuthLayout from "../../Layouts/AuthLayout.vue";
import HeaderModule from "../../Modules/Others/HeaderModule.vue";
import SelectInput from "../../Components/inputs/SelectInput.vue";
import ToolbarModule from "../../Modules/Others/ToolbarModule.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import TextInput from "../../Components/inputs/TextInput.vue";
import DefaultToggle from "../../Components/toggleswitches/DefaultToggle.vue";
import DefaultTreeTable from "../../Components/tables/DefaultTreeTable.vue";
import SelectMultiInput from "../../Components/inputs/SelectMultiInput.vue";
import DefaultToast from "../../Components/messages/DefaultToast.vue";
import DefaultConfirmDialog from "../../Components/dialogs/DefaultConfirmDialog.vue";

const page = usePage();
const searchInput = ref(null);
const timerBounce = ref(null);
const selectedRow = ref(null);
const toolbarRef = ref(null);
const toastRef = ref(null);
const confirmRef = ref(null);
const menu = ref(null);

const routeForm = useForm({
    id: null,
    icon: null,
    label: null,
    route: null,
    role: null,
    isSubmenu: false,
    submenu: null,
    component: null,
    slug: null,
    order_no: null,
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
    routeForm.resetAndClearErrors();

    if (res.type == "edit") {
        routeForm.id = res.data.id;
        routeForm.icon = res.data.icon;
        routeForm.label = res.data.name;
        routeForm.role = res.data.roles;
        routeForm.route = res.data.data.route;
        routeForm.isSubmenu = res.data.is_submenu;
        routeForm.component = res.data.component;
        routeForm.slug = res.data.slug;
        routeForm.order_no = res.data;
        if (res.data.is_submenu) {
            routeForm.submenu = res.data.main_id;
        }
    }
    toolbarRef.value.openModal();
};

const deleteRow = (id) => {
    confirmRef.value.popupDialog(() => {
        routeForm.delete(route("routes.destroy", id), {
            onSuccess: () => {
                routeForm.resetAndClearErrors();
                toastRef.value.show(page.props.flash);
            },
        });
    });
};

const submitForm = () => {
    if (!routeForm.id) {
        routeForm.post(route("routes.store"), {
            onSuccess: () => {
                routeForm.resetAndClearErrors();
                toastRef.value.show(page.props.flash);
            },
        });
    } else {
        routeForm.put(
            route("routes.update", { id: routeForm.id, type: "form" }),
            {
                onSuccess: () => {
                    toolbarRef.value.closeModal();
                    routeForm.resetAndClearErrors();
                    toastRef.value.show(page.props.flash);
                },
            }
        );
    }
};
const updateStatus = (result) => {
    routeForm.isActive = result.is_active;
    routeForm.put(route("routes.update", { id: result.id, type: "status" }), {
        onSuccess: () => {
            toastRef.value.show(page.props.flash);
        },
    });
};

const clearSearch = () => {
    searchInput.value = null;
};

const loadPage = () => {
    router.get(
        route("routes"),
        {
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
            loadPage();
        }, 300);
    }
);
</script>

<style scoped>
:global(.dark .routes-page .p-treetable-header-cell),
:global(.dark .routes-page .p-treetable-column-header-content) {
    color: #e5e7eb !important;
}

:global(.routes-page .p-treetable-tbody > tr > td) {
    padding-top: 0.65rem;
    padding-bottom: 0.65rem;
}
</style>
