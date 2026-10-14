<template>
    <Head title="My Profile" />

    <AuthLayout>
        <div class="flex flex-col w-full h-full gap-6">
            <!-- Header -->
            <HeaderModule
                title="My Profile"
                description="Manage your personal information, account details, and security settings."
            />

            <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                <div
                    class="rounded-xl border border-surface-200 bg-white p-5 dark:border-slate-600 dark:bg-slate-700"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300"
                        >
                            <IconShieldCheck class="h-5 w-5" />
                        </div>
                        <p
                            class="text-sm font-medium text-surface-600 dark:text-surface-300"
                        >
                            Two-factor authentication
                        </p>
                    </div>
                    <div class="mt-4 flex items-end justify-between gap-3">
                        <div>
                            <p
                                class="text-2xl font-semibold text-surface-900 dark:text-white"
                            >
                                {{ twoFactorEnabled ? "On" : "Off" }}
                            </p>
                            <p class="mt-1 text-xs text-surface-500">
                                {{
                                    twoFactorEnabled
                                        ? "Authenticator app connected"
                                        : "Add a second sign-in step"
                                }}
                            </p>
                        </div>
                        <Tag
                            :value="
                                twoFactorEnabled ? 'Protected' : 'Recommended'
                            "
                            :severity="twoFactorEnabled ? 'success' : 'warn'"
                        />
                    </div>
                </div>

                <div
                    class="rounded-xl border border-surface-200 bg-white p-5 dark:border-slate-600 dark:bg-slate-700"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300"
                        >
                            <IconFingerprint class="h-5 w-5" />
                        </div>
                        <p
                            class="text-sm font-medium text-surface-600 dark:text-surface-300"
                        >
                            Registered passkeys
                        </p>
                    </div>
                    <div class="mt-4">
                        <p
                            class="text-2xl font-semibold text-surface-900 dark:text-white"
                        >
                            {{ props.passkeys?.length ?? 0 }}
                        </p>
                        <p class="mt-1 text-xs text-surface-500">
                            {{
                                props.passkeys?.length
                                    ? "Ready for passwordless sign-in"
                                    : "Add a device for faster sign-in"
                            }}
                        </p>
                    </div>
                </div>

                <div
                    class="rounded-xl border border-surface-200 bg-white p-5 dark:border-slate-600 dark:bg-slate-700"
                >
                    <div class="flex items-center gap-3">
                        <div
                            class="flex h-10 w-10 items-center justify-center rounded-lg bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300"
                        >
                            <IconHistory class="h-5 w-5" />
                        </div>
                        <p
                            class="text-sm font-medium text-surface-600 dark:text-surface-300"
                        >
                            Recent sign-ins
                        </p>
                    </div>
                    <div class="mt-4">
                        <p
                            class="text-2xl font-semibold text-surface-900 dark:text-white"
                        >
                            {{ props.logs?.length ?? 0 }}
                        </p>
                        <p class="mt-1 text-xs text-surface-500">
                            Session records shown below
                        </p>
                    </div>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-6 items-stretch">
                <!-- ================================================= -->
                <!-- PROFILE CARD -->
                <!-- ================================================= -->

                <Card
                    class="xl:col-span-1 h-full dark:bg-slate-700! dark:text-white!"
                >
                    <template #content>
                        <div
                            class="flex h-full flex-col items-center text-center"
                        >
                            <!-- Avatar -->
                            <div class="relative mb-5">
                                <Avatar
                                    v-if="user.profile.avatar === null"
                                    :label="user.email.charAt(0).toUpperCase()"
                                    class="!w-28 !h-28 !text-3xl bg-primary text-white ring-4 ring-primary/10"
                                    shape="circle"
                                />

                                <Avatar
                                    v-else
                                    style="
                                        background-color: #dee9fc;
                                        color: #1a2551;
                                    "
                                    class="!w-28 !h-28 ring-4 ring-primary/10"
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
                            <h2
                                class="text-xl font-semibold text-surface-900 dark:text-white"
                            >
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

                            <div
                                class="mt-6 w-full rounded-lg bg-surface-50 p-4 text-left dark:bg-slate-800"
                            >
                                <p
                                    class="text-xs font-semibold uppercase tracking-wide text-surface-500"
                                >
                                    Work details
                                </p>
                                <div class="mt-3 space-y-3">
                                    <div class="flex items-start gap-3">
                                        <IconBriefcase
                                            class="mt-0.5 h-4 w-4 shrink-0 text-primary"
                                        />
                                        <div class="min-w-0">
                                            <p class="text-xs text-surface-500">
                                                Position
                                            </p>
                                            <p
                                                class="break-words text-sm font-medium"
                                            >
                                                {{
                                                    user.profile.designation ||
                                                    "Not set"
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="flex items-start gap-3">
                                        <IconBuilding
                                            class="mt-0.5 h-4 w-4 shrink-0 text-primary"
                                        />
                                        <div class="min-w-0">
                                            <p class="text-xs text-surface-500">
                                                Office / Unit
                                            </p>
                                            <p
                                                class="break-words text-sm font-medium"
                                            >
                                                {{
                                                    user.profile.agency_array
                                                        ?.name || "Not set"
                                                }}
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-5 w-full text-left">
                                <div
                                    class="mb-2 flex items-center justify-between gap-3"
                                >
                                    <span class="text-sm font-medium"
                                        >Profile completeness</span
                                    >
                                    <span
                                        class="text-sm font-semibold text-primary"
                                        >{{ profileCompleteness }}%</span
                                    >
                                </div>
                                <div
                                    class="h-2 overflow-hidden rounded-full bg-surface-200 dark:bg-slate-600"
                                    role="progressbar"
                                    :aria-valuenow="profileCompleteness"
                                    aria-valuemin="0"
                                    aria-valuemax="100"
                                    aria-label="Profile completeness"
                                >
                                    <div
                                        class="h-full rounded-full bg-primary transition-[width] duration-500"
                                        :style="{
                                            width: `${profileCompleteness}%`,
                                        }"
                                    ></div>
                                </div>
                                <p class="mt-2 text-xs text-surface-500">
                                    Add your contact and work details to
                                    complete your profile.
                                </p>
                            </div>
                        </div>
                    </template>
                </Card>

                <!-- ================================================= -->
                <!-- ACCOUNT INFORMATION -->
                <!-- ================================================= -->

                <Card
                    class="xl:col-span-2 h-full overflow-hidden dark:bg-slate-700! dark:text-white!"
                >
                    <template #title>
                        <div
                            class="flex flex-wrap items-center justify-between gap-4"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-10 w-10 items-center justify-center rounded-lg bg-sky-100 text-sky-700 dark:bg-sky-900/40 dark:text-sky-300"
                                >
                                    <IconUser class="h-5 w-5" />
                                </div>

                                <div>
                                    <h3 class="text-lg font-semibold">
                                        Personal Information
                                    </h3>

                                    <p
                                        class="text-sm text-surface-500 font-normal"
                                    >
                                        Update your personal account
                                        information.
                                    </p>
                                </div>
                            </div>

                            <span
                                class="inline-flex items-center gap-2 rounded-full bg-surface-100 px-3 py-1.5 text-xs font-medium text-surface-600 dark:bg-slate-600 dark:text-surface-200"
                            >
                                <span
                                    class="h-2 w-2 rounded-full"
                                    :class="
                                        disable.userDetails
                                            ? 'bg-surface-400'
                                            : 'bg-emerald-500'
                                    "
                                ></span>
                                {{
                                    disable.userDetails
                                        ? "View mode"
                                        : "Editing"
                                }}
                            </span>
                        </div>
                    </template>

                    <template #content>
                        <form @submit.prevent="updateProfile" class="mt-5">
                            <div
                                class="divide-y divide-surface-200 dark:divide-slate-600"
                            >
                                <section class="space-y-4 pb-5">
                                    <div class="flex items-center gap-2">
                                        <IconUser
                                            class="h-4 w-4 text-primary"
                                        />
                                        <h4
                                            class="text-sm font-semibold text-surface-800 dark:text-surface-100"
                                        >
                                            Personal details
                                        </h4>
                                    </div>
                                    <div
                                        class="grid grid-cols-1 gap-5 md:grid-cols-2"
                                    >
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
                                </section>

                                <section class="space-y-4 py-5">
                                    <div class="flex items-center gap-2">
                                        <IconPhone
                                            class="h-4 w-4 text-primary"
                                        />
                                        <h4
                                            class="text-sm font-semibold text-surface-800 dark:text-surface-100"
                                        >
                                            Contact information
                                        </h4>
                                    </div>
                                    <div
                                        class="grid grid-cols-1 gap-5 md:grid-cols-2"
                                    >
                                        <div>
                                            <TextInput
                                                label="Email Address"
                                                v-model="form.email"
                                                type="email"
                                                disabled
                                            />
                                            <p
                                                class="mt-1 text-xs text-surface-500"
                                            >
                                                Email changes are unavailable
                                                here. Contact an administrator
                                                if this needs updating.
                                            </p>
                                        </div>
                                        <TextInput
                                            label="Contact Number"
                                            v-model="form.phone"
                                            :disabled="disable.userDetails"
                                        />
                                    </div>
                                </section>

                                <section class="space-y-4 pt-5">
                                    <div class="flex items-center gap-2">
                                        <IconBriefcase
                                            class="h-4 w-4 text-primary"
                                        />
                                        <h4
                                            class="text-sm font-semibold text-surface-800 dark:text-surface-100"
                                        >
                                            Work information
                                        </h4>
                                    </div>
                                    <div
                                        class="grid grid-cols-1 gap-5 md:grid-cols-2"
                                    >
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
                                </section>
                            </div>

                            <!-- Actions -->
                            <div
                                class="mt-5 flex flex-wrap justify-end gap-2 border-t border-surface-200 pt-4 dark:border-slate-600"
                            >
                                <template v-if="!disable.userDetails">
                                    <Button
                                        type="button"
                                        label="Cancel"
                                        severity="secondary"
                                        outlined
                                        @click="resetForm"
                                    >
                                        <IconX class="h-4 w-4" />
                                        <span>Cancel</span>
                                    </Button>

                                    <Button type="submit">
                                        <IconDeviceFloppy class="h-4 w-4" />
                                        <span>Save changes</span>
                                    </Button>
                                </template>
                                <template v-else>
                                    <Button
                                        type="button"
                                        @click="disable.userDetails = false"
                                    >
                                        <IconEdit class="h-4 w-4" />
                                        <span>Edit details</span>
                                    </Button>
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
                            <IconShieldCheck class="h-5 w-5 text-orange-600" />
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
                                        <IconLock
                                            class="h-4 w-4 text-primary"
                                        />

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
                                        <IconKey class="h-4 w-4 text-primary" />

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
                                        <IconFingerprint
                                            class="h-4 w-4 text-primary"
                                        />

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
                                            class="flex items-center justify-between gap-4 rounded-lg border border-surface-200 bg-surface-50/60 px-3 py-3 dark:border-slate-600 dark:bg-slate-800"
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
                                    label="Add passkey"
                                    icon="pi pi-plus"
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
                            <div class="flex items-center gap-2">
                                <IconHistory class="h-5 w-5 text-primary" />
                                <h3 class="text-lg font-semibold">
                                    Recent Login Activity
                                </h3>
                            </div>

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
                                            <component
                                                :is="
                                                    [
                                                        'iPhone',
                                                        'iPad',
                                                        'Android',
                                                    ].includes(data.device)
                                                        ? IconDeviceMobile
                                                        : IconDeviceDesktop
                                                "
                                                class="h-5 w-5 text-surface-600 dark:text-surface-300"
                                            />
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
        :style="{ width: 'min(50rem, calc(100vw - 2rem))' }"
        class="m-2"
    >
        <template #header>
            <div class="flex items-center gap-3">
                <div
                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-primary/10 text-primary"
                >
                    <IconCircleKey class="h-5 w-5" />
                </div>

                <div class="flex flex-col">
                    <div
                        class="text-sm font-semibold text-surface-900 dark:text-white"
                    >
                        Two-Factor Security
                    </div>

                    <div class="text-xs leading-5 text-surface-500">
                        Secure your account with an authenticator app
                    </div>
                </div>
            </div>
        </template>
        <template #default>
            <div class="space-y-5">
                <!-- Header / Instructions -->
                <div
                    class="rounded-lg border border-emerald-200 bg-emerald-50 p-4 dark:border-emerald-900 dark:bg-emerald-950/40"
                >
                    <div class="flex items-start gap-3">
                        <IconShieldCheck
                            class="mt-0.5 h-5 w-5 shrink-0 text-emerald-700 dark:text-emerald-300"
                        />
                        <div>
                            <h3
                                class="font-semibold text-emerald-900 dark:text-emerald-100"
                            >
                                Your account has an extra layer of protection
                            </h3>
                            <p
                                class="mt-1 text-sm leading-5 text-emerald-800 dark:text-emerald-200"
                            >
                                Keep your authenticator and recovery codes
                                accessible only to you.
                            </p>
                        </div>
                    </div>
                </div>

                <!-- Main Content -->
                <div class="grid grid-cols-1 gap-4 md:grid-cols-2">
                    <!-- QR Code -->
                    <div
                        class="flex flex-col items-center rounded-xl border border-surface-200 bg-surface-50 p-5 dark:border-slate-600 dark:bg-slate-800"
                    >
                        <div class="flex items-center gap-2">
                            <IconQrcode class="h-5 w-5 text-primary" />
                            <h4
                                class="font-semibold text-surface-900 dark:text-white"
                            >
                                Authenticator QR code
                            </h4>
                        </div>

                        <p class="mt-2 text-center text-sm text-surface-500">
                            Scan with your authenticator app to link this
                            account.
                        </p>

                        <div
                            v-if="QrCodeResult"
                            v-html="QrCodeResult"
                            class="mt-5 rounded-xl border border-surface-200 bg-white p-4 shadow-sm"
                        ></div>
                    </div>

                    <!-- Recovery Codes -->
                    <div
                        class="rounded-xl border border-surface-200 bg-white p-5 dark:border-slate-600 dark:bg-slate-700"
                    >
                        <div class="flex items-center justify-center gap-2">
                            <IconKey class="h-5 w-5 text-primary" />
                            <h4
                                class="font-semibold text-center text-surface-900 dark:text-white"
                            >
                                Recovery codes
                            </h4>
                        </div>

                        <p class="mt-2 text-sm leading-6 text-surface-500">
                            Use one of these codes if you lose access to your
                            authenticator.
                        </p>

                        <div
                            class="mt-3 flex items-center justify-between rounded-md bg-surface-50 px-3 py-2 text-xs dark:bg-slate-800"
                        >
                            <span class="text-surface-500"
                                >Available codes</span
                            >
                            <span
                                class="font-semibold text-surface-700 dark:text-surface-200"
                                >{{ recoveryCodes?.length ?? 0 }}</span
                            >
                        </div>

                        <!-- Recovery Codes -->
                        <div
                            class="mt-3 rounded-lg bg-surface-50 p-3 dark:bg-slate-800"
                        >
                            <div class="grid grid-cols-2 gap-2">
                                <div
                                    v-for="(code, index) in recoveryCodes"
                                    :key="index"
                                    class="rounded-md border border-surface-200 bg-white px-2 py-2 text-center font-mono text-xs text-surface-700 shadow-sm dark:border-slate-600 dark:bg-slate-700 dark:text-surface-100 sm:text-sm"
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
                    class="flex gap-3 rounded-lg border border-amber-200 bg-amber-50 p-4 dark:border-amber-900 dark:bg-amber-950/40"
                >
                    <IconShieldLock
                        class="mt-0.5 h-5 w-5 shrink-0 text-amber-700 dark:text-amber-300"
                    />

                    <div>
                        <p
                            class="text-sm font-medium text-amber-950 dark:text-amber-100"
                        >
                            Keep your recovery codes private
                        </p>

                        <p
                            class="mt-1 text-xs leading-5 text-amber-800 dark:text-amber-200"
                        >
                            Anyone with these codes may be able to access your
                            account. Store them somewhere safe and do not share
                            them.
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
    IconShieldCheck,
    IconFingerprint,
    IconHistory,
    IconBriefcase,
    IconBuilding,
    IconLock,
    IconKey,
    IconDeviceMobile,
    IconDeviceDesktop,
    IconQrcode,
    IconShieldLock,
    IconUser,
    IconPhone,
    IconEdit,
    IconDeviceFloppy,
    IconX,
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

const profileCompleteness = computed(() => {
    const details = [
        props.user?.profile?.fname,
        props.user?.profile?.lname,
        props.user?.email,
        props.user?.profile?.contact_no,
        props.user?.profile?.designation,
        props.user?.profile?.agency_array?.id,
        props.user?.profile?.avatar,
    ];

    return Math.round(
        (details.filter((detail) => Boolean(detail)).length / details.length) *
            100,
    );
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
