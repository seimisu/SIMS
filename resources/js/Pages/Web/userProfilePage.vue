<template>
    <Head title="My Profile" />

    <AuthLayout>
        <div class="flex flex-col w-full h-full gap-6">
            <!-- Header -->
            <HeaderModule
                title="My Profile"
                description="Manage your personal information, account details, and security settings."
            />

            <!-- Profile Content -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
                <!-- ================================================= -->
                <!-- PROFILE CARD -->
                <!-- ================================================= -->

                <Card class="xl:col-span-1 dark:bg-slate-700! dark:text-white!">
                    <template #content>
                        <div class="flex flex-col items-center text-center">
                            <!-- Avatar -->
                            <div class="relative mb-4">
                                <Avatar
                                    v-if="user.profile.avatar === null"
                                    :label="user.email.charAt(0).toUpperCase()"
                                    class="!w-28 !h-28 !text-3xl bg-primary text-white"
                                    shape="circle"
                                />

                                <Avatar
                                    v-else
                                    style="
                                        background-color: #dee9fc;
                                        color: #1a2551;
                                    "
                                    class="!w-28 !h-28"
                                    shape="circle"
                                    :image="user.profile.avatar_url"
                                />

                                <Button
                                    icon="pi pi-camera"
                                    rounded
                                    severity="secondary"
                                    size="small"
                                    class="!absolute bottom-0 right-0 !w-9 !h-9"
                                    @click="openPhotoDialog"
                                />
                            </div>

                            <!-- Name -->
                            <h2 class="text-xl font-semibold text-surface-900">
                                {{ user.profile.fullname }}
                            </h2>

                            <p class="text-sm text-surface-500 mt-1">
                                {{ user.email }}
                            </p>

                            <!-- Role -->
                            <Tag
                                :value="user.role_array?.name"
                                severity="info"
                                class="mt-3"
                            />

                            <Divider />

                            <!-- Profile Summary -->
                            <div class="w-full space-y-4 flex justify-between">
                                <div>
                                    <span class="text-xs text-surface-500">
                                        Account Status
                                    </span>

                                    <div class="mt-1">
                                        <Tag
                                            value="Active"
                                            severity="success"
                                        />
                                    </div>
                                </div>

                                <div>
                                    <span class="text-xs text-surface-500">
                                        Member Since
                                    </span>

                                    <p class="font-medium mt-1">
                                        {{ profile.memberSince }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- ================================================= -->
                <!-- ACCOUNT INFORMATION -->
                <!-- ================================================= -->

                <Card class="xl:col-span-2 dark:bg-slate-700! dark:text-white!">
                    <template #title>
                        <div class="flex items-center gap-3">
                            <div
                                class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center"
                            >
                                <i class="pi pi-user text-primary"></i>
                            </div>

                            <div>
                                <h3 class="text-lg font-semibold">
                                    Personal Information
                                </h3>

                                <p class="text-sm text-surface-500 font-normal">
                                    Update your personal account information.
                                </p>
                            </div>
                        </div>
                    </template>

                    <template #content>
                        <form
                            @submit.prevent="updateProfile"
                            class="space-y-5 mt-5"
                        >
                            <!-- Name -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <TextInput
                                    label="First Name"
                                    v-model="form.firstName"
                                    :disabled="disable.userDetails"
                                    uppercase
                                />

                                <TextInput
                                    label="Last Name"
                                    v-model="form.lastName"
                                    :disabled="disable.userDetails"
                                    uppercase
                                />
                            </div>

                            <!-- Email / Phone -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <TextInput
                                    label="Email Address"
                                    v-model="form.email"
                                    type="email"
                                    :disabled="disable.userDetails"
                                />

                                <TextInput
                                    label="Contact Number"
                                    v-model="form.phone"
                                    :disabled="disable.userDetails"
                                />
                            </div>

                            <!-- Position / Office -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                                <TextInput
                                    label="Position"
                                    v-model="form.position"
                                    :disabled="disable.userDetails"
                                />

                                <SelectInput
                                    label="Office / Unit"
                                    :options="agencyOption"
                                    v-model="form.office"
                                    :disable="disable.userDetails"
                                />
                            </div>

                            <!-- Actions -->
                            <div
                                class="flex justify-end gap-2 pt-4 border-t border-surface-200"
                            >
                                <template v-if="!disable.userDetails">
                                    <Button
                                        type="button"
                                        label="Cancel"
                                        severity="secondary"
                                        outlined
                                        @click="resetForm"
                                    />

                                    <Button
                                        type="submit"
                                        label="Save Changes"
                                        icon="pi pi-check"
                                    />
                                </template>
                                <template v-else>
                                    <Button
                                        type="submit"
                                        label="Update Details"
                                        @click="disable.userDetails = false"
                                    />
                                </template>
                            </div>
                        </form>
                    </template>
                </Card>
            </div>

            <!-- ================================================= -->
            <!-- SECURITY -->
            <!-- ================================================= -->

            <Card class="dark:bg-slate-700! dark:text-white!">
                <template #title>
                    <div class="flex items-center gap-3">
                        <div
                            class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center"
                        >
                            <i class="pi pi-shield text-orange-600"></i>
                        </div>

                        <div>
                            <h3 class="text-lg font-semibold">Security</h3>

                            <p class="text-sm text-surface-500 font-normal">
                                Manage your password and account security.
                            </p>
                        </div>
                    </div>
                </template>

                <template #content>
                    <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
                        <!-- Password -->
                        <div class="border border-surface-200 rounded-xl p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <i class="pi pi-lock text-primary"></i>

                                        <h4 class="font-semibold">Password</h4>
                                    </div>

                                    <p class="text-sm text-surface-500 mt-2">
                                        Change your password regularly to keep
                                        your account secure.
                                    </p>
                                </div>

                                <Button
                                    label="Change"
                                    severity="secondary"
                                    outlined
                                    size="small"
                                    @click="passwordDialog = true"
                                />
                            </div>
                        </div>

                        <!-- Two Factor -->
                        <div class="border border-surface-200 rounded-xl p-5">
                            <div class="flex items-start justify-between gap-4">
                                <div>
                                    <div class="flex items-center gap-2">
                                        <i class="pi pi-key text-primary"></i>

                                        <h4 class="font-semibold">
                                            Two-Factor Authentication
                                        </h4>
                                    </div>

                                    <p class="text-sm text-surface-500 mt-2">
                                        Add an additional layer of security to
                                        your account.
                                    </p>
                                </div>
                                <div class="flex flex-col items-end gap-3">
                                    <ToggleSwitch
                                        v-model="twoFactorEnabled"
                                        @update:modelValue="toggleTwoFactor"
                                    />

                                    <Button
                                        size="small"
                                        class="p-0!"
                                        v-if="twoFactorEnabled"
                                        @click="showQRcodeDialog = true"
                                        link
                                        >Show 2FA QR Code</Button
                                    >
                                </div>
                            </div>
                        </div>

                        <!-- Passkeys -->
                        <div
                            class="border border-surface-200 rounded-xl p-5 lg:col-span-2"
                        >
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <i
                                            class="pi pi-shield text-primary"
                                        ></i>

                                        <h4 class="font-semibold">Passkeys</h4>
                                    </div>

                                    <p class="text-sm text-surface-500 mt-2">
                                        Sign in securely with your device or
                                        password manager.
                                    </p>

                                    <div class="mt-4 space-y-2">
                                        <div
                                            v-for="passkey in props.passkeys"
                                            :key="passkey.id"
                                            class="flex items-center justify-between gap-4 rounded-lg border border-surface-200 px-3 py-2"
                                        >
                                            <div class="min-w-0">
                                                <p
                                                    class="truncate text-sm font-medium"
                                                >
                                                    {{ passkey.name }}
                                                </p>
                                                <p
                                                    class="text-xs text-surface-500"
                                                >
                                                    Added
                                                    {{
                                                        formatPasskeyDate(
                                                            passkey.created_at,
                                                        )
                                                    }}
                                                    <span
                                                        v-if="
                                                            passkey.last_used_at
                                                        "
                                                    >
                                                        · Last used
                                                        {{
                                                            formatPasskeyDate(
                                                                passkey.last_used_at,
                                                            )
                                                        }}
                                                    </span>
                                                </p>
                                            </div>

                                            <Button
                                                icon="pi pi-trash"
                                                text
                                                rounded
                                                severity="danger"
                                                size="small"
                                                aria-label="Remove passkey"
                                                @click="deletePasskey(passkey)"
                                            />
                                        </div>

                                        <p
                                            v-if="!props.passkeys?.length"
                                            class="text-sm text-surface-500"
                                        >
                                            No passkeys registered.
                                        </p>
                                    </div>
                                </div>

                                <Button
                                    label="Add"
                                    severity="secondary"
                                    outlined
                                    size="small"
                                    :disabled="!passkeySupported"
                                    @click="passkeyDialog = true"
                                />
                            </div>
                        </div>
                    </div>
                </template>
            </Card>

            <!-- ================================================= -->
            <!-- LOGIN ACTIVITY -->
            <!-- ================================================= -->

            <Card class="dark:bg-slate-700! dark:text-white!">
                <template #title>
                    <div class="flex items-center justify-between">
                        <div>
                            <h3 class="text-lg font-semibold">
                                Recent Login Activity
                            </h3>

                            <p class="text-sm text-surface-500 font-normal">
                                Review recent activity on your account.
                            </p>
                        </div>

                        <Button label="View All" text size="small" />
                    </div>
                </template>

                <template #content>
                    <div class="overflow-x-auto">
                        <DataTable :value="logs" responsiveLayout="scroll">
                            <!-- Device -->
                            <Column field="device" header="Device">
                                <template #body="{ data }">
                                    <div class="flex items-center gap-3">
                                        <div
                                            class="w-9 h-9 rounded-lg bg-surface-100 flex items-center justify-center"
                                        >
                                            <i
                                                :class="
                                                    data.device === 'iPhone' ||
                                                    data.device === 'iPad'
                                                        ? 'pi pi-mobile'
                                                        : data.device ===
                                                            'Android'
                                                          ? 'pi pi-mobile'
                                                          : 'pi pi-desktop'
                                                "
                                            ></i>
                                        </div>

                                        <div>
                                            <p class="font-medium">
                                                {{ data.device }}
                                            </p>

                                            <p class="text-xs text-surface-500">
                                                {{
                                                    data.browser ??
                                                    "Unknown Browser"
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </template>
                            </Column>

                            <!-- IP Address -->
                            <Column field="ip_address" header="IP Address">
                                <template #body="{ data }">
                                    <span class="font-mono text-sm">
                                        {{ data.ip_address }}
                                    </span>
                                </template>
                            </Column>

                            <!-- Last Activity -->
                            <Column
                                field="last_activity"
                                header="Last Activity"
                            >
                                <template #body="{ data }">
                                    <span
                                        class="text-sm text-surface-600 dark:text-surface-300"
                                    >
                                        {{ data.last_activity }}
                                    </span>
                                </template>
                            </Column>

                            <!-- Status -->
                            <Column field="status" header="Status">
                                <template #body="{ data }">
                                    <Tag
                                        :value="data.status"
                                        :severity="
                                            data.status === 'Current'
                                                ? 'success'
                                                : 'secondary'
                                        "
                                    />
                                </template>
                            </Column>
                        </DataTable>
                    </div>
                </template>
            </Card>
        </div>

        <DefaultDialog
            v-model:visible="passwordDialog"
            :icon="IconPasswordUser"
            width-set="lg:!w-[35%]"
            :loading="passwordForm.processing"
            @submit-form="changePassword"
            title="Update Your Password"
            description="To ensure your account remains protected at all times, please choose a password that is strong, unique, and difficult for others to guess."
        >
            <template #message>
                <DefaultMessages
                    v-if="passwordForm.hasErrors"
                    message-type="error"
                    :message="passwordForm.errors"
                />
            </template>
            <template #forms>
                <div class="pt-5 flex flex-col gap-5">
                    <PasswordInput
                        label="Current Password"
                        v-model="passwordForm.current"
                        :feedback="false"
                        toggle-icon
                    />
                    <PasswordInput
                        label="New Password"
                        v-model="passwordForm.new"
                        :feedback="true"
                        toggle-icon
                    />
                    <PasswordInput
                        label="Confirm Password"
                        v-model="passwordForm.confirm"
                        :feedback="false"
                    />
                </div>
            </template>
        </DefaultDialog>

        <DefaultDialog
            v-model:visible="passkeyDialog"
            :loading="passkeyLoading"
            @submit-form="registerPasskey"
            title="Add a Passkey"
            description="Name this passkey so you can recognize it later. Your device will ask you to confirm its use."
        >
            <template #message>
                <DefaultMessages
                    v-if="passkeyError"
                    message-type="error"
                    :message="{ passkey: [passkeyError] }"
                />
            </template>
            <template #forms>
                <div class="pt-5">
                    <TextInput
                        label="Passkey Name"
                        v-model="passkeyName"
                        placeholder="My laptop"
                    />
                </div>
            </template>
        </DefaultDialog>

        <!-- ================================================= -->
        <!-- PROFILE PHOTO DIALOG -->
        <!-- ================================================= -->

        <Dialog
            v-model:visible="photoDialog"
            modal
            header="Update Profile Photo"
            :style="{ width: '400px' }"
        >
            <div class="flex flex-col items-center gap-4 py-5">
                <!-- Profile Preview -->
                <Avatar
                    v-if="photoPreview"
                    :image="photoPreview"
                    size="xlarge"
                    shape="circle"
                    class="!w-28 !h-28"
                />

                <Avatar
                    v-else
                    :label="initials"
                    size="xlarge"
                    shape="circle"
                    class="!w-28 !h-28 !text-3xl bg-primary text-white"
                />

                <!-- File Upload -->
                <FileUpload
                    mode="basic"
                    name="profilePhoto"
                    accept="image/jpeg,image/png,image/webp"
                    :maxFileSize="2000000"
                    chooseLabel="Choose Photo"
                    :auto="false"
                    @select="onPhotoSelect"
                />

                <p class="text-xs text-surface-500">
                    JPG, PNG or WEBP. Maximum file size: 2MB.
                </p>

                <!-- Upload Button -->
                <Button
                    label="Save Photo"
                    icon="pi pi-upload"
                    :loading="uploadingPhoto"
                    :disabled="!selectedPhoto"
                    @click="uploadPhoto"
                />
            </div>
        </Dialog>
    </AuthLayout>
    <DefaultDialog
        v-model:visible="confirmPasswordDialog"
        :icon="IconPasswordUser"
        width-set="lg:!w-[35%]"
        :loading="confirmPasswordForm.processing"
        @submit-form="submitConfirmPassword"
        title="Confirm Your Password"
        description="Enter your current password to continue enabling two-factor authentication."
    >
        <template #message>
            <DefaultMessages
                v-if="confirmPasswordForm.hasErrors"
                message-type="error"
                :message="confirmPasswordForm.errors"
            />
        </template>
        <template #forms>
            <div class="pt-5 flex flex-col gap-5">
                <PasswordInput
                    label="Password"
                    v-model="confirmPasswordForm.password"
                    :feedback="false"
                    toggle-icon
                />
            </div>
        </template>
    </DefaultDialog>
    <Dialog
        v-model:visible="showQRcodeDialog"
        modal
        style="width: 50rem"
        class="m-2"
    >
        <template #header>
            <div class="flex items-center gap-3">
                <Avatar class="shrink-0">
                    <IconCircleKey />
                </Avatar>

                <div class="flex flex-col">
                    <div class="text-sm font-semibold text-surface-900">
                        Set Up Two-Factor Authentication
                    </div>

                    <div class="text-xs leading-5 text-surface-500">
                        Secure your account with an authenticator app
                    </div>
                </div>
            </div>
        </template>
        <template #default>
            <div class="space-y-6">
                <!-- Header / Instructions -->
                <div class="text-center">
                    <h3 class="text-lg font-semibold text-surface-900">
                        Set Up Two-Factor Authentication
                    </h3>

                    <p class="mt-1 text-sm text-surface-500">
                        Scan the QR code with your authenticator app to secure
                        your account.
                    </p>
                </div>

                <!-- Main Content -->
                <div class="grid grid-cols-1 gap-6 md:grid-cols-2">
                    <!-- QR Code -->
                    <div
                        class="flex flex-col items-center rounded-xl border border-surface-200 bg-surface-50 p-5"
                    >
                        <h4 class="font-semibold text-surface-900">
                            Scan QR Code
                        </h4>

                        <p class="mt-1 text-center text-sm text-surface-500">
                            Open your authenticator app and scan the code below.
                        </p>

                        <div
                            v-if="QrCodeResult"
                            v-html="QrCodeResult"
                            class="mt-5 rounded-xl border border-surface-200 bg-white p-4 shadow-sm"
                        ></div>
                    </div>

                    <!-- Recovery Codes -->
                    <div
                        class="rounded-xl border border-surface-200 bg-white p-5"
                    >
                        <h4 class="font-semibold text-center text-surface-900">
                            Save Your Recovery Codes
                        </h4>

                        <p class="mt-1 text-sm leading-6 text-surface-500">
                            Store these codes somewhere safe. You can use them
                            to access your account if you lose your
                            authenticator device.
                        </p>

                        <!-- Recovery Codes -->
                        <div class="mt-4 rounded-lg bg-surface-50 p-3">
                            <div class="grid grid-cols-2 gap-2">
                                <div
                                    v-for="(code, index) in recoveryCodes"
                                    :key="index"
                                    class="rounded-md bg-white px-3 py-2 text-center font-mono text-sm text-surface-700 shadow-sm"
                                >
                                    {{ code }}
                                </div>
                            </div>
                        </div>

                        <Button
                            label="Generate New Recovery Codes"
                            class="mt-4 w-full"
                            size="small"
                            severity="secondary"
                            outlined
                            :loading="loading.generateRecoveryCodes"
                            @click="generateRecoveryCodes"
                        >
                            <div class="flex items-center gap-2">
                                <IconRefresh
                                    class="h-4 w-4"
                                    v-if="!loading.generateRecoveryCodes"
                                />
                                <IconLoader
                                    class="h-4 w-4 animate-spin"
                                    v-else
                                />
                                <span>Generate New Recovery Codes</span>
                            </div>
                        </Button>
                    </div>
                </div>

                <!-- Security Notice -->
                <div
                    class="flex gap-3 rounded-lg border border-surface-200 bg-surface-50 p-4"
                >
                    <i class="pi pi-shield mt-0.5 text-primary"></i>

                    <div>
                        <p class="text-sm font-medium text-surface-900">
                            Keep your recovery codes private
                        </p>

                        <p class="mt-1 text-xs leading-5 text-surface-500">
                            Anyone with these codes may be able to access your
                            account. Do not share them with anyone.
                        </p>
                    </div>
                </div>
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import { computed, ref, watch } from "vue";

import { Head, router, useForm } from "@inertiajs/vue3";
import axios from "axios";

import AuthLayout from "../../Layouts/AuthLayout.vue";

import HeaderModule from "../../Modules/Others/HeaderModule.vue";
import SelectInput from "../../Components/inputs/SelectInput.vue";
import TextInput from "../../Components/inputs/TextInput.vue";
import ChangePasswordDialog from "../../Components/dialogs/ChangePasswordDialog.vue";
import Card from "primevue/card";
import Avatar from "primevue/avatar";
import Button from "primevue/button";
import Tag from "primevue/tag";
import Divider from "primevue/divider";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Dialog from "primevue/dialog";
import FileUpload from "primevue/fileupload";
import { useToast } from "primevue";
import { usePasskeyRegister } from "@laravel/passkeys/vue";
import PasswordInput from "../../Components/inputs/PasswordInput.vue";
import DefaultDialog from "../../Components/dialogs/DefaultDialog.vue";
import DefaultMessages from "../../Components/messages/DefaultMessages.vue";
import {
    IconPasswordUser,
    IconCircleKey,
    IconRefresh,
    IconLoader,
} from "@tabler/icons-vue";

const toast = useToast();

const disable = ref({
    userDetails: true,
});
const loading = ref({
    generateRecoveryCodes: false,
});
const props = defineProps({
    isTwoFactorEnabled: Boolean,
    QrCodeResult: String,
    recoveryCodes: Array,
    user: Object,
    agencyOption: Object,
    logs: Object,
    passkeys: Array,
});

const profile = ref({
    name: "John Rey Dalit",
    firstName: "John Rey",
    lastName: "Dalit",
    email: "johnrey@example.com",
    phone: "0912 345 6789",
    employeeId: "DOST-SEI-001",
    role: "Administrator",
    position: "Project Technical Specialist I",
    office: "DOST-SEI",
    memberSince: "January 2024",
});

const selectedPhoto = ref(null);
const photoPreview = ref(null);
const uploadingPhoto = ref(false);
const passwordDialog = ref(false);
const passkeyDialog = ref(false);
const passkeyName = ref("");
const showQRcodeDialog = ref(false);
const confirmPasswordDialog = ref(false);

const form = useForm({
    firstName: props.user?.profile.fname,
    lastName: props.user?.profile.lname,
    email: props.user?.email,
    phone: props.user?.profile.contact_no,
    position: props.user?.profile.designation,
    office: props.user?.profile.agency_array,
});

const confirmPasswordForm = useForm({
    password: "",
});

const initials = computed(() => {
    return props.user?.profile.fullname
        .split(" ")
        .map((name) => name.charAt(0))
        .slice(0, 2)
        .join("")
        .toUpperCase();
});

const passwordForm = useForm({
    current: "",
    new: "",
    confirm: "",
});

const twoFactorEnabled = ref(props.isTwoFactorEnabled ?? false);
const pendingTwoFactorState = ref(twoFactorEnabled.value);
const twoFactorChangeConfirmed = ref(false);

const {
    register: registerPasskeyWithBrowser,
    isLoading: passkeyLoading,
    error: passkeyError,
    isSupported: passkeySupported,
} = usePasskeyRegister({
    onSuccess: () => {
        passkeyDialog.value = false;
        passkeyName.value = "";
        toast.add({
            severity: "success",
            summary: "Passkey Added",
            detail: "Your passkey has been registered successfully.",
            life: 3000,
        });
        router.reload({ only: ["passkeys"], preserveScroll: true });
    },
});

const photoDialog = ref(false);

const openPhotoDialog = () => {
    photoDialog.value = true;
};

const onPhotoSelect = (event) => {
    const file = event.files[0];

    if (!file) {
        return;
    }

    selectedPhoto.value = file;

    // Create preview
    photoPreview.value = URL.createObjectURL(file);
};

const uploadPhoto = () => {
    if (!selectedPhoto.value) {
        return;
    }

    uploadingPhoto.value = true;

    router.post(
        route("profile.photo.update"),
        {
            profilePhoto: selectedPhoto.value,
        },
        {
            forceFormData: true,

            onSuccess: () => {
                photoDialog.value = false;

                selectedPhoto.value = null;

                if (photoPreview.value) {
                    URL.revokeObjectURL(photoPreview.value);
                }

                photoPreview.value = null;
            },

            onFinish: () => {
                uploadingPhoto.value = false;
            },
        },
    );
};

const updateProfile = () => {
    form.transform((data) => ({
        email: data.email,
        firstName: data.firstName,
        lastName: data.lastName,
        phone: data.phone,
        position: data.position,
        office_id: data.office?.id ?? null,
    })).put(route("profile.update"), {
        preserveScroll: true,

        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Profile Updated",
                detail: "Your profile has been successfully updated.",
                life: 3000,
            });
        },

        onError: (errors) => {
            console.log("Validation errors:", errors);
        },
        onFinish: () => {
            disable.value.userDetails = true;
        },
    });
};

const resetForm = () => {
    disable.value.userDetails = true;

    form.value = {
        firstName: props.user?.profile.fname,
        lastName: props.user?.profile.lname,
        email: props.user?.email,
        phone: props.user?.profile.contact_no,
        position: props.user?.profile.designation,
        office: props.user?.profile.agency_array,
    };
};

const changePassword = () => {
    passwordForm.post(route("user.changePassword"), {
        fresh: true,
        replace: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Profile Updated",
                detail: "Your profile has been successfully updated.",
                life: 3000,
            });
            passwordDialog.value = false;
            passwordForm.resetAndClearErrors();
            nextTick(() => {
                window.location.reload();
            });
        },
    });
};

const registerPasskey = () => {
    if (passkeyName.value.trim()) {
        registerPasskeyWithBrowser(passkeyName.value.trim());
    }
};

const formatPasskeyDate = (date) => {
    if (!date) {
        return "Never";
    }

    return new Intl.DateTimeFormat(undefined, {
        dateStyle: "medium",
        timeStyle: "short",
    }).format(new Date(date));
};

const deletePasskey = (passkey) => {
    if (!window.confirm(`Remove the passkey "${passkey.name}"?`)) {
        return;
    }

    router.delete(route("passkey.destroy", passkey.id), {
        preserveScroll: true,
        onSuccess: () => {
            toast.add({
                severity: "success",
                summary: "Passkey Removed",
                detail: "The passkey has been removed from your account.",
                life: 3000,
            });
        },
    });
};

const toggleTwoFactor = (newValue) => {
    pendingTwoFactorState.value = newValue;
    confirmPasswordDialog.value = true;
};

const submitConfirmPassword = () => {
    confirmPasswordForm.post(route("password.confirm.store"), {
        fresh: true,
        replace: true,
        onSuccess: () => {
            twoFactorChangeConfirmed.value = true;
            confirmPasswordDialog.value = false;
            confirmPasswordForm.resetAndClearErrors();
            if (pendingTwoFactorState.value) {
                router.post(
                    route("two-factor.enable"),
                    {},
                    {
                        preserveState: true,
                        preserveScroll: true,
                        onSuccess: () => {
                            twoFactorEnabled.value = true;
                        },
                        onError: () => {
                            twoFactorEnabled.value = false;
                        },
                    },
                );
            } else {
                router.delete(route("two-factor.disable"), {
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        twoFactorEnabled.value = false;
                    },
                    onError: () => {
                        twoFactorEnabled.value = true;
                    },
                });
            }
        },
    });
};

const generateRecoveryCodes = () => {
    loading.value.generateRecoveryCodes = true;

    router.post(
        route("two-factor.regenerate-recovery-codes"),
        {},
        {
            preserveState: true,
            preserveScroll: true,
            onFinish: () => {
                loading.value.generateRecoveryCodes = false;
            },
        },
    );
};

watch(
    () => confirmPasswordDialog.value,
    (newValue) => {
        if (!newValue) {
            if (twoFactorChangeConfirmed.value) {
                twoFactorChangeConfirmed.value = false;
                return;
            }

            twoFactorEnabled.value = props.isTwoFactorEnabled;
        }
    },
);
</script>
