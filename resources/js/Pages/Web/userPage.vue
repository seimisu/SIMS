<template>
    <Head title="Users" />
    <AuthLayout>
        <div class="flex flex-col w-full h-full gap-10">
            <div class="flex">
                <HeaderModule
                    title="User Management"
                    description="Manage system users, roles, and access permissions."
                />
            </div>
            <div class="flex-1 flex flex-col gap-2">
                <ToolbarModule
                    v-model="searchInput"
                    @deleteSearch="clearSearch"
                    @saveForm="submitForm"
                    button-label="Create"
                    :dialog-title="!userForm.id ? 'Create User' : 'Edit User'"
                    dialog-description="Fill in the required information to create or update this user."
                    :dialog-button-loading="userForm.processing"
                    :dialog-icon="IconUserPlus"
                    dialog-button-label="Save"
                    :message-has-errors="userForm.hasErrors"
                    :message-errors="userForm.errors"
                    @buttonOpenModal="toggleModal({ type: 'create' })"
                    message-type="error"
                    ref="toolbarRef"
                >
                    <template #form>
                        <div class="flex flex-col gap-3 mt-5">
                            <div class="flex gap-3">
                                <TextInput
                                    v-model="userForm.fname"
                                    capitalize
                                    label="First Name"
                                ></TextInput>
                                <TextInput
                                    v-model="userForm.lname"
                                    label="Last Name"
                                    capitalize
                                ></TextInput>
                            </div>
                            <TextInput
                                v-model="userForm.email"
                                label="Email"
                                type="email"
                            ></TextInput>

                            <SelectInput
                                label="Role"
                                v-model="userForm.role"
                                :options="page.props.roleOption"
                                :clearable="true"
                                capitalize
                            ></SelectInput>
                        </div>
                    </template>
                </ToolbarModule>
                <DefaultTable
                    :items="page.props.users.data"
                    :pagination="{
                        total: page.props.users.total,
                        perPage: page.props.users.per_page,
                        currentPage: page.props.users.current_page,
                    }"
                    @paginate="loadPage"
                >
                    <Column field="fullname" header="Name" style="width: 30%">
                        <template #body="props">
                            <div class="flex items-center gap-2">
                                <Avatar
                                    v-if="!props.data.profile.avatar"
                                    :label="
                                        props.data.email.charAt(0).toUpperCase()
                                    "
                                    shape="circle"
                                    size="medium"
                                    style="
                                        background-color: #ece9fc;
                                        color: #2a1261;
                                    "
                                />
                                <Avatar
                                    v-else
                                    :image="props.data.avatar_url"
                                    shape="circle"
                                    size="medium"
                                    style="
                                        background-color: #ece9fc;
                                        color: #2a1261;
                                    "
                                />
                                <div
                                    class="flex flex-col flex-1"
                                    v-if="props.data.profile?.fullname"
                                >
                                    <div
                                        class="flex items-center justify-start gap-1"
                                    >
                                        <div
                                            class="text-[14px]"
                                            v-tooltip.top="
                                                props.data.is_verified
                                                    ? 'Account verified'
                                                    : 'Account not yet verified'
                                            "
                                        >
                                            {{ props.data.profile?.fullname }}
                                        </div>
                                        <IconCircleDashedCheck
                                            size="18"
                                            class="text-gray-400"
                                            v-if="!props.data.is_verified"
                                        />
                                        <IconRosetteDiscountCheckFilled
                                            v-else
                                            size="18"
                                            class="text-green-600"
                                        />
                                    </div>

                                    <span class="text-gray-400 text-[12px]">{{
                                        props.data.email
                                    }}</span>
                                </div>
                                <div v-else>{{ props.data.email }}</div>
                            </div>
                        </template>
                    </Column>

                    <Column field="formatted_date" header="Created Date">
                    </Column>
                    <Column>
                        <template #header>
                            <div class="w-full flex justify-center">
                                <p class="font-semibold">Role</p>
                            </div>
                        </template>
                        <template #body="props">
                            <div class="flex justify-center">
                                <p
                                    class="capitalize font-semibold bg-blue-100 px-4 text-xs py-1 rounded-xl text-blue-800"
                                >
                                    {{ props.data.role_array.name }}
                                </p>
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
                                    @update-value="
                                        updateStatus(
                                            props.data.is_active,
                                            props.data.id,
                                        )
                                    "
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
            <div class="flex justify-center" v-show="userForm.errors?.invalid">
                <DefaultMessages
                    class="w-fit"
                    message-type="error"
                    closable
                    :message="userForm.errors"
                />
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
import TextInput from "../../Components/inputs/TextInput.vue";
import DefaultToggle from "../../Components/toggleswitches/DefaultToggle.vue";
import DefaultToast from "../../Components/messages/DefaultToast.vue";
import DefaultConfirmDialog from "../../Components/dialogs/DefaultConfirmDialog.vue";
import SelectInput from "../../Components/inputs/SelectInput.vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";

import {
    IconCheck,
    IconX,
    IconPencilCog,
    IconTrash,
    IconUserPlus,
    IconCircleDashedCheck,
    IconRosetteDiscountCheckFilled,
    IconMailForward,
} from "@tabler/icons-vue";
import DefaultMessages from "../../Components/messages/DefaultMessages.vue";
import { route } from "ziggy-js";

const page = usePage();

const searchInput = ref(null);
const timerBounce = ref(null);
const selectedRow = ref(null);
const toolbarRef = ref(null);
const toastRef = ref(null);
const confirmRef = ref(null);
const menu = ref(null);
const userForm = useForm({
    id: null,
    fname: null,
    lname: null,
    email: null,
    role: null,
    school: null,
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
            label: "Resend Email",
            icon: IconMailForward,
            class: "text-cyan-500",

            command: () => {
                toggleModal({
                    type: "resend",
                    data: selectedRow.value,
                });
            },
        },
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
            //for special admin only this delete.
            disabled: page.props.user.id != 1 ? true : false,
            command: () => {
                deleteRow(selectedRow.value.id);
            },
        },
    ];
});

const toggleModal = (res) => {
    userForm.resetAndClearErrors();

    if (res.type == "edit") {
        userForm.id = res.data.id;
        userForm.fname = res.data.profile.fname;
        userForm.lname = res.data.profile.lname;
        userForm.email = res.data.email;
        userForm.role = res.data.role_array;
        userForm.school = res.data.school_array;
    }

    if (res.type == "resend") {
        router.post(
            route("users.resend", { id: res.data.id }),
            {},
            {
                onStart: () => {
                    console.log("Resending...");
                    toastRef.value.show({
                        status: "info",
                        title: "Resending...",
                        message: "Please wait while we resend the email.",
                    });
                },
                onSuccess: () => {
                    toastRef.value.show(page.props.flash);
                },
            },
        );

        return false;
    }

    toolbarRef.value.openModal();
};

const deleteRow = (id) => {
    confirmRef.value.popupDialog(() => {
        userForm.delete(route("users.destroy", { id: id, type: "delete" }), {
            onSuccess: () => {
                userForm.resetAndClearErrors();
                toastRef.value.show(page.props.flash);
            },
        });
    });
};

const submitForm = () => {
    if (!userForm.id) {
        userForm.post(route("users.store"), {
            onSuccess: () => {
                userForm.resetAndClearErrors();
                toastRef.value.show(page.props.flash);
            },
        });
    } else {
        userForm.put(route("users.update", { id: userForm.id, type: "form" }), {
            onSuccess: () => {
                toolbarRef.value.closeModal();
                userForm.resetAndClearErrors();
                toastRef.value.show(page.props.flash);
            },
        });
    }
};
const updateStatus = (data, id) => {
    userForm.isActive = data;
    userForm.put(route("users.update", { id: id, type: "status" }), {
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
        route("users"),
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
