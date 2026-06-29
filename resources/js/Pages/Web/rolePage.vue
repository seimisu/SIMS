<template>
    <Head title="Roles" />
    <AuthLayout>
        <div class="flex flex-col w-full h-full gap-10">
            <div class="flex">
                <HeaderModule
                    title="Role Management"
                    description="Manage user roles, permissions, and access levels."
                />
            </div>
            <div class="flex-1 flex flex-col gap-2">
                <ToolbarModule
                    v-model="searchInput"
                    @deleteSearch="clearSearch"
                    @saveForm="submitForm"
                    button-label="Create"
                    :dialog-title="!roleForm.id ? 'Create Role' : 'Edit Role'"
                    dialog-description="Define a new role and configure its access permissions."
                    :dialog-button-loading="roleForm.processing"
                    :dialog-icon="IconUserCog"
                    dialog-button-label="Save"
                    :message-has-errors="roleForm.hasErrors"
                    :message-errors="roleForm.errors"
                    @buttonOpenModal="toggleModal({ type: 'create' })"
                    message-type="error"
                    ref="toolbarRef"
                >
                    <template #form>
                        <div class="flex flex-col gap-3 mt-5">
                            <TextInput
                                v-model="roleForm.name"
                                label="Name"
                                capitalize
                            ></TextInput>
                            <TextInput
                                v-model="roleForm.slug"
                                label="Slug"
                            ></TextInput>
                            <TextInput
                                v-model="roleForm.description"
                                label="Description"
                            ></TextInput>
                            <div>
                                <Divider type="dashed" />
                                <div class="flex justify-between items-center">
                                    <div class="text-sm">
                                        Make this role undeletable?
                                    </div>
                                    <DefaultToggle
                                        v-model="roleForm.isLock"
                                        :check-icon="IconCheck"
                                        :un-check-icon="IconX"
                                    />
                                </div>
                            </div>
                        </div>
                    </template>
                </ToolbarModule>
                <DefaultTable
                    :items="page.props.roles.data"
                    :pagination="{
                        total: page.props.roles.total,
                        perPage: page.props.roles.per_page,
                        currentPage: page.props.roles.current_page,
                    }"
                    @paginate="loadPage"
                >
                    <Column field="name" header="Name">
                        <template #body="props">
                            <div class="flex flex-col">
                                <div class="font-semibold capitalize">
                                    {{ props.data.name }}
                                </div>
                                <div class="text-gray-500 text-xs">
                                    {{ props.data.description }}
                                </div>
                            </div>
                        </template>
                    </Column>

                    <Column header="Users">
                        <template #body="props">
                            <div v-if="props.data.users?.length != 0">
                                <AvatarGroup>
                                    <Avatar
                                        v-for="user in props.data.users.slice(
                                            0,
                                            5
                                        )"
                                        :key="user.id"
                                        :label="
                                            user.email.charAt(0).toUpperCase()
                                        "
                                        v-tooltip.top="user.email"
                                        shape="circle"
                                        size="small"
                                        :style="{
                                            backgroundColor: '#ece9fc',
                                            color: '#2a1261',
                                        }"
                                    />

                                    <Avatar
                                        v-if="props.data.users.length > 5"
                                        :label="`+${
                                            props.data.users.length - 5
                                        }`"
                                        shape="circle"
                                        size="small"
                                        :style="{
                                            backgroundColor: '#d3d3d3',
                                            color: '#333',
                                            fontWeight: 'bold',
                                        }"
                                    />
                                </AvatarGroup>
                            </div>
                            <div v-else>
                                <span
                                    class="text-gray-400 font-light text-[12px] italic"
                                    >No users available</span
                                >
                            </div>
                        </template>
                    </Column>
                    <Column field="formatted_date" header="Created Date">
                    </Column>
                    <Column field="created_by" header="Created By" />
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
        <DefaultDialog
            v-model:visible="permissionModal"
            :icon="IconShieldCog"
            width-set="lg:!w-[55%]"
            title="Role Permissions"
            :description="permissionDialogDescription"
            button-label="Save"
            :loading="permissionForm.processing"
            :hide-footer="isSelectedAdministrator"
            absolute-div
            @submit-form="submitPermissions"
        >
            <template #forms>
                <div class="mt-5 flex flex-col gap-5">
                    <div
                        v-if="isSelectedAdministrator"
                        class="rounded-md border border-blue-200 bg-blue-50 px-4 py-3 text-xs text-blue-700"
                    >
                        Administrator always has full system access. The checks
                        below are shown for reference.
                    </div>
                    <div
                        v-for="(permissions, groupName) in page.props.permissionGroups"
                        :key="groupName"
                        class="rounded-md border border-gray-200 p-4"
                    >
                        <div class="mb-3 flex items-center justify-between">
                            <div>
                                <div class="text-sm font-semibold capitalize">
                                    {{ groupName }}
                                </div>
                                <div class="text-xs text-gray-400">
                                    {{ permissions.length }} permissions
                                </div>
                            </div>
                        </div>
                        <div class="grid gap-3 md:grid-cols-2">
                            <label
                                v-for="permission in permissions"
                                :key="permission.id"
                                class="flex min-h-18 cursor-pointer gap-3 rounded-md border border-gray-100 p-3 text-sm hover:bg-gray-50"
                                :class="{
                                    'cursor-default opacity-70':
                                        isSelectedAdministrator,
                                }"
                            >
                                <input
                                    v-model="permissionForm.permissions"
                                    type="checkbox"
                                    :value="permission.id"
                                    :disabled="isSelectedAdministrator"
                                    class="mt-1 h-4 w-4"
                                />
                                <span class="flex flex-col gap-1">
                                    <span class="font-medium">
                                        {{ permission.label }}
                                    </span>
                                    <span class="text-xs text-gray-400">
                                        {{ permission.description }}
                                    </span>
                                </span>
                            </label>
                        </div>
                    </div>
                </div>
            </template>
        </DefaultDialog>
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
import DefaultDialog from "../../Components/dialogs/DefaultDialog.vue";
import { computed, ref, watch } from "vue";
import {
    IconCheck,
    IconLock,
    IconShieldCog,
    IconUserCog,
    IconX,
    IconPencilCog,
    IconTrash,
} from "@tabler/icons-vue";
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
const permissionModal = ref(false);
const roleForm = useForm({
    id: null,
    name: null,
    slug: null,
    description: null,
    isLock: false,
    isActive: false,
});
const permissionForm = useForm({
    permissions: [],
});

const allPermissionIds = computed(() => {
    return Object.values(page.props.permissionGroups ?? {})
        .flat()
        .map((permission) => permission.id);
});

const isSelectedAdministrator = computed(() => {
    return (
        selectedRow.value?.slug === "admin" ||
        selectedRow.value?.name?.toLowerCase() === "administrator"
    );
});

const permissionDialogDescription = computed(() => {
    if (!selectedRow.value) return "Assign the permissions for this role.";

    return `Assign access rules for ${selectedRow.value.name}. Users with this role will inherit these permissions.`;
});

const toggleOption = (event, rowData) => {
    selectedRow.value = rowData;
    menu.value.toggle(event);
};

const menuItems = computed(() => {
    if (!selectedRow.value) return [];

    return [
        {
            label: "Permissions",
            icon: IconShieldCog,
            class: "text-emerald-600",
            command: () => {
                openPermissionModal(selectedRow.value);
            },
        },
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
    roleForm.resetAndClearErrors();

    if (res.type == "edit") {
        roleForm.id = res.data.id;
        roleForm.name = res.data.name;
        roleForm.slug = res.data.slug;
        roleForm.description = res.data.description;
        roleForm.isLock = res.data.is_lock;
    }

    toolbarRef.value.openModal();
};

const openPermissionModal = (role) => {
    permissionForm.resetAndClearErrors();
    selectedRow.value = role;
    permissionForm.permissions = isSelectedAdministrator.value
        ? [...allPermissionIds.value]
        : (role.permissions ?? []).map((permission) => permission.id);
    permissionModal.value = true;
};

const deleteRow = (id) => {
    confirmRef.value.popupDialog(() => {
        roleForm.delete(route("roles.destroy", id), {
            onSuccess: () => {
                roleForm.resetAndClearErrors();
                toastRef.value.show(page.props.flash);
            },
        });
    });
};

const submitForm = () => {
    if (!roleForm.id) {
        roleForm.post(route("roles.store"), {
            onSuccess: () => {
                roleForm.resetAndClearErrors();
                toastRef.value.show(page.props.flash);
            },
        });
    } else {
        roleForm.put(route("roles.update", { id: roleForm.id, type: "form" }), {
            onSuccess: () => {
                toolbarRef.value.closeModal();
                roleForm.resetAndClearErrors();
                toastRef.value.show(page.props.flash);
            },
        });
    }
};
const submitPermissions = () => {
    if (!selectedRow.value || isSelectedAdministrator.value) return;

    permissionForm.put(
        route("roles.update", {
            id: selectedRow.value.id,
            type: "permissions",
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                permissionModal.value = false;
                permissionForm.resetAndClearErrors();
                toastRef.value.show(page.props.flash);
            },
        }
    );
};
const updateStatus = (result) => {
    roleForm.isActive = result.is_active;
    roleForm.put(route("roles.update", { id: result.id, type: "status" }), {
        onSuccess: () => {
            toastRef.value.show(page.props.flash);
        },
    });
};

const clearSearch = () => {
    searchInput.value = null;
};

const loadPage = (page) => {
    router.get(
        route("roles"),
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
