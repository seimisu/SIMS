<template>
    <Dialog
        v-model:visible="modelValue"
        modal
        :pt="{
            root: 'w-[99%] lg:w-[70rem] dark:!bg-gray-900 dark:!text-gray-100',
            header: 'border-b-1 border-gray-300 border-dashed dark:!border-gray-700 dark:!bg-gray-900 dark:!text-gray-100',
            content: '!p-0 dark:!bg-gray-900 dark:!text-gray-100',
            footer: 'dark:!bg-gray-900',
        }"
    >
        <template #header>
            <div
                class="bg-slate-100 px-4 py-2 shadow rounded-lg flex items-center gap-2 dark:bg-gray-800 dark:text-gray-100"
            >
                <IconCreditCard :size="18" :stroke-width="2" />
                <div class="uppercase font-medium text-sm">
                    Landbank request
                </div>
            </div>
        </template>
        <template #default>
            <div class="">
                <div
                    class="flex flex-col lg:flex-row h-full w-full lg:h-[33rem]"
                >
                    <div
                        class="w-full lg:w-4/12 bg-slate-100 lg:rounded-bl-xl flex flex-col flex-1 overflow-y-auto p-3 gap-3 dark:bg-gray-800"
                    >
                        <div class="flex items-center gap-1">
                            <IconHistory :size="20" />
                            <div class="text-sm uppercase font-medium">
                                History Request:
                            </div>
                        </div>
                        <div class="flex lg:flex-col gap-2">
                            <template
                                v-for="(item, index) in landbankRequest"
                                :key="index"
                            >
                                <div
                                    class="border rounded-xl gap-3 hover:shadow border-gray-200 bg-white flex flex-col text-sm p-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                >
                                    <div
                                        class="flex justify-between items-start"
                                    >
                                        <div class="flex items-center gap-1">
                                            <Avatar
                                                class="!w-[40px] !h-[40px] shadow border border-slate-400 !rounded-xl"
                                            >
                                                <IconCreditCard :size="23" />
                                            </Avatar>
                                            <div class="text-sm">
                                                <div
                                                    class="text-xs text-slate-500 dark:text-gray-400"
                                                >
                                                    Request ID
                                                </div>
                                                <div class="font-medium">
                                                    #{{ item.count }}
                                                </div>
                                            </div>
                                        </div>

                                        <div
                                            v-if="item.status == 'pending'"
                                            class="text-xs bg-amber-100 text-amber-500 border capitalize rounded-xl px-3 py-1"
                                        >
                                            {{ item.status }} Review
                                        </div>
                                        <div
                                            v-else-if="
                                                item.status == 'approved'
                                            "
                                            class="text-xs bg-green-100 text-green-500 border capitalize rounded-xl px-3 py-1"
                                        >
                                            {{ item.status }}
                                        </div>
                                        <div
                                            v-else
                                            class="text-xs bg-red-100 text-red-500 border capitalize rounded-xl px-3 py-1"
                                        >
                                            {{ item.status }}
                                        </div>
                                    </div>
                                    <div
                                        class="flex-1 flex flex-col p-1 gap-2 text-sm"
                                    >
                                        <div class="flex-1">
                                            <div class="text-xs text-slate-500 dark:text-gray-400">
                                                Requested On
                                            </div>
                                            <p class="font-medium">
                                                {{ item.request_date }}
                                            </p>
                                        </div>
                                        <div class="flex-1">
                                            <div class="text-xs text-slate-500 dark:text-gray-400">
                                                Reason for Change
                                            </div>
                                            <p class="font-medium">
                                                {{ item.remarks }}
                                            </p>
                                        </div>
                                    </div>
                                    <div
                                        class="flex justify-between items-center border-t border-gray-200 pt-2"
                                    >
                                        <div
                                            class="flex gap-1 items-center"
                                            v-if="item.reviewed_at"
                                        >
                                            <Avatar
                                                class="!bg-green-100 !text-green-600 border"
                                                shape="circle"
                                            >
                                                <IconUser :size="18" />
                                            </Avatar>
                                            <div class="flex flex-col text-xs">
                                                <div class="leading-none">
                                                    <div class="font-medium">
                                                        {{ item.reviewed_at }}
                                                    </div>
                                                    <div class="capitalize">
                                                        {{ item.reviewed_by }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            v-else
                                            class="flex items-center text-gray-700 gap-1"
                                        >
                                            <IconCalendarTime :size="18" />
                                            <p class="text-xs">
                                                {{ item.requested_at }}
                                            </p>
                                        </div>
                                        <div>
                                            <Button
                                                label="View this request"
                                                size="small"
                                                class="!text-xs !rounded-lg"
                                                severity="secondary"
                                                iconPos="right"
                                                @click="
                                                    selectedRequest(item, index)
                                                "
                                                icon="pi pi-arrow-right"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                    <div
                        class="flex flex-col gap-2 p-3 h-full w-full min-h-0 overflow-y-auto bg-white dark:bg-gray-900 dark:text-gray-100 lg:w-8/12"
                    >
                        <div
                            class="flex flex-col gap-4 border-b border-gray-200 pb-4 dark:border-gray-700 lg:flex-row lg:items-center lg:justify-between"
                        >
                            <div class="flex items-center gap-3">
                                <Avatar
                                    class="!bg-blue-100 !text-blue-600 !rounded-2xl shadow border border-blue-300"
                                    size="large"
                                >
                                    <IconUser :size="22" />
                                </Avatar>

                                <div>
                                    <div
                                        class="text-lg font-bold text-gray-900 leading-5 dark:text-gray-100"
                                    >
                                        {{ scholar.fullname }}
                                    </div>

                                    <div
                                        class="mt-1 flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        <IconHash :size="14" />
                                        {{ scholar.spas_no }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-8">
                                <div class="text-right">
                                    <div
                                        class="text-xs uppercase tracking-wider text-gray-400 dark:text-gray-500"
                                    >
                                        Scholarship
                                    </div>

                                    <div class="font-semibold text-gray-800 dark:text-gray-100">
                                        {{ scholar.type?.name ?? scholar.type }}
                                    </div>
                                </div>

                                <Divider layout="vertical" class="!h-10" />

                                <div class="text-right">
                                    <div
                                        class="text-xs uppercase tracking-wider text-gray-400 dark:text-gray-500"
                                    >
                                        Program
                                    </div>

                                    <div class="font-semibold text-gray-800 dark:text-gray-100">
                                        {{ scholar.program?.name ?? scholar.program ?? "-" }}
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex items-stretch w-full gap-1"
                            v-if="selectedRow"
                        >
                            <Fieldset
                                :pt="{
                                    root: '!p-2 w-full dark:!bg-gray-800 dark:!border-gray-700',
                                    legend: 'dark:!bg-gray-800 dark:!text-gray-100 dark:!border-gray-700',
                                    content: 'pb-2 dark:!bg-gray-800 dark:!text-gray-100',
                                }"
                            >
                                <template #legend>
                                    <p class="text-sm font-medium">
                                        Req# {{ selectedRow.count }}
                                    </p>
                                </template>
                                <template #default>
                                    <div class="flex flex-col gap-2">
                                        <div class="flex flex-col gap-3">
                                            <div
                                                class="bg-green-50 border-b border-green-200 px-4 py-2 flex items-center gap-2 dark:border-green-800 dark:bg-green-900/30"
                                            >
                                                <IconDatabaseEdit
                                                    class="text-green-500"
                                                    :size="18"
                                                />

                                                <div
                                                    class="text-sm font-semibold text-green-700 dark:text-green-300"
                                                >
                                                    Requested Changes
                                                </div>
                                            </div>
                                                <div
                                                    v-if="changedFields.length"
                                                    class="grid max-h-[18rem] min-h-[12rem] gap-2 overflow-y-auto pr-1"
                                                >
                                                    <div
                                                        v-if="canRevealLandbank && hasStoredLandbankValues"
                                                        class="flex justify-end"
                                                    >
                                                        <DefaultButton
                                                            :icon="landbankRevealed ? IconEyeOff : IconEye"
                                                            :label="landbankRevealed ? 'Hide Landbank Details' : 'View Landbank Details'"
                                                            size="small"
                                                            severity="secondary"
                                                            outlined
                                                            rounded
                                                            class-name="!rounded-xl !px-3 !min-w-[13.5rem] !justify-center focus:!shadow-none focus:!ring-0 focus:!outline-none"
                                                            @click="
                                                                landbankRevealed
                                                                    ? hideLandbank()
                                                                    : (landbankPasswordDialog = true)
                                                            "
                                                        />
                                                    </div>
                                                    <div
                                                        v-for="field in changedFields"
                                                    :key="field.key"
                                                    class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-900"
                                                >
                                                    <div class="min-w-0">
                                                        <div
                                                            class="text-xs uppercase text-slate-400 dark:text-gray-500"
                                                        >
                                                            Current
                                                            {{ field.label }}
                                                        </div>
                                                        <div
                                                            class="mt-1 break-words font-medium text-slate-800 dark:text-gray-100"
                                                        >
                                                            {{
                                                                field.current ||
                                                                "No record"
                                                            }}
                                                        </div>
                                                    </div>
                                                    <div
                                                        class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-slate-500 shadow-sm dark:bg-gray-800 dark:text-gray-300"
                                                    >
                                                        <IconArrowNarrowRight
                                                            :size="22"
                                                        />
                                                    </div>
                                                    <div class="min-w-0">
                                                        <div
                                                            class="text-xs uppercase text-green-600 dark:text-green-300"
                                                        >
                                                            Requested
                                                            {{ field.label }}
                                                        </div>
                                                        <div
                                                            class="mt-1 break-words font-semibold text-green-700 dark:text-green-200"
                                                        >
                                                            {{ field.requested }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div
                                                v-else
                                                class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                            >
                                                No changed fields were found for
                                                this request.
                                            </div>
                                        </div>
                                        <Divider type="dashed" class="!my-1" />
                                        <div class="flex flex-col w-full gap-2">
                                            <div
                                                class="flex items-center gap-1 text-slate-500 dark:text-gray-400"
                                            >
                                                <IconPaperclip :size="15" />
                                                <div class="text-xs">
                                                    Attachment (Optional)
                                                </div>
                                            </div>
                                            <div
                                                v-if="selectedRow.file"
                                                class="flex-1 gap-3 flex justify-between dark:text-gray-100"
                                            >
                                                <div class="flex items-center">
                                                    <div
                                                        class="flex items-center gap-1"
                                                    >
                                                        <Avatar
                                                            class="!w-[35px] !h-[35px] border !text-slate-500 border-slate-400 !rounded-xl dark:!border-gray-600 dark:!text-gray-300"
                                                        >
                                                            <IconFileTypePdf
                                                                :size="20"
                                                            />
                                                        </Avatar>
                                                        <div class="text-sm">
                                                            <div
                                                                class="text-xs"
                                                            >
                                                                File Type
                                                            </div>
                                                            <div
                                                                class="font-medium"
                                                            >
                                                                {{
                                                                    selectedRow.type
                                                                }}
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div>
                                                    <Button
                                                        size="small"
                                                        class="!text-xs !rounded-full"
                                                        @click="
                                                            toggleOpFileViewer
                                                        "
                                                        text
                                                    >
                                                        <template #default>
                                                            <div
                                                                class="flex gap-1 items-center"
                                                            >
                                                                <IconEye
                                                                    :stroke-width="
                                                                        1.5
                                                                    "
                                                                    :size="20"
                                                                />
                                                                <p>
                                                                    View
                                                                    uploaded
                                                                    file
                                                                </p>
                                                            </div>
                                                        </template>
                                                    </Button>
                                                    <Popover
                                                        ref="opFileViewer"
                                                        class="!rounded-2xl !shadow-2xl"
                                                        :pt="{
                                                            content: '!p-0',
                                                        }"
                                                    >
                                                        <div
                                                            class="w-[600px] max-w-[90vw] h-[550px]"
                                                        >
                                                            <!-- Header -->
                                                            <div
                                                                class="flex items-center justify-between px-5 py-4 border-b border-gray-200"
                                                            >
                                                                <div
                                                                    class="flex items-center gap-3"
                                                                >
                                                                    <div
                                                                        class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center"
                                                                    >
                                                                        <IconFileTypePdf
                                                                            v-if="
                                                                                selectedRow?.file?.endsWith(
                                                                                    '.pdf',
                                                                                )
                                                                            "
                                                                            :size="
                                                                                22
                                                                            "
                                                                        />

                                                                        <IconFile
                                                                            v-else
                                                                            :size="
                                                                                22
                                                                            "
                                                                        />
                                                                    </div>

                                                                    <div>
                                                                        <h3
                                                                            class="font-semibold text-gray-800"
                                                                        >
                                                                            Document
                                                                            Preview
                                                                        </h3>

                                                                        <p
                                                                            class="text-sm text-gray-500 truncate max-w-[500px]"
                                                                        >
                                                                            {{
                                                                                selectedRow?.file
                                                                                    ?.split(
                                                                                        "/",
                                                                                    )
                                                                                    .pop()
                                                                            }}
                                                                        </p>
                                                                    </div>
                                                                </div>
                                                                <div
                                                                    class="flex items-center gap-3"
                                                                >
                                                                    <!-- <Button
                                                                        icon="pi pi-download"
                                                                        severity="secondary"
                                                                        text
                                                                        size="small"
                                                                        rounded
                                                                        as="a"
                                                                        :href="
                                                                            scholarPortalFileUrl(selectedRow.file)
                                                                        "
                                                                        download
                                                                        v-tooltip.top="
                                                                            'Download'
                                                                        "
                                                                    /> -->
                                                                    <Button
                                                                        icon="pi pi-external-link"
                                                                        severity="secondary"
                                                                        text
                                                                        size="small"
                                                                        rounded
                                                                        as="a"
                                                                        target="_blank"
                                                                        :href="
                                                                            scholarPortalFileUrl(selectedRow.file)
                                                                        "
                                                                        v-tooltip.top="
                                                                            'Open in new tab'
                                                                        "
                                                                    />
                                                                </div>
                                                            </div>

                                                            <!-- Viewer -->
                                                            <iframe
                                                                :src="
                                                                    scholarPortalFileUrl(selectedRow.file)
                                                                "
                                                                class="w-full h-[500px]! border-0"
                                                            />
                                                        </div>
                                                    </Popover>
                                                </div>
                                            </div>
                                            <div
                                                v-else
                                                class="flex items-center gap-2 rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500 dark:border-gray-700 dark:bg-gray-900 dark:text-gray-300"
                                            >
                                                <IconFileOff :size="18" />
                                                <span>
                                                    No available attachment
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </Fieldset>
                        </div>
                        <div
                            v-else
                            class="flex items-center justify-center h-full w-full"
                        >
                            <div class="flex items-center gap-1">
                                <IconLock />
                                <div class="text-sm">
                                    Please complete verification to view change
                                    requests.
                                </div>
                            </div>
                        </div>
                        <div
                            v-if="selectedRow && selectedRow.reject"
                            class="flex items-start gap-3 rounded-lg border border-red-200 bg-red-50 p-4"
                        >
                            <i
                                class="pi pi-exclamation-circle text-red-500 text-lg mt-0.5"
                            ></i>

                            <div class="flex-1">
                                <div class="text-sm font-semibold text-red-700">
                                    Request Rejected
                                </div>

                                <div
                                    class="mt-1 text-sm text-red-600 leading-relaxed"
                                >
                                    {{ selectedRow.reject }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <template #footer>
            <div
                v-if="selectedRow && !selectedRow.reviewed_at"
                class="flex justify-end gap-3"
            >
                <Button
                    size="small"
                    class="!text-xs !rounded-xl"
                    outlined
                    :loading="loading.reject"
                    severity="danger"
                    @click="toggleOpReject"
                >
                    <template #default>
                        <div class="flex gap-1 items-center">
                            <IconCircleXFilled
                                :stroke-width="1.5"
                                :size="20"
                                v-if="!loading.reject"
                            />
                            <IconLoader2
                                v-else
                                :size="20"
                                class="animate-spin"
                            />
                            <p>Reject</p>
                        </div>
                    </template>
                </Button>
                <Popover ref="opReject">
                    <div class="w-[26rem] p-1 flex flex-col gap-4">
                        <div class="flex flex-col gap-1">
                            <h3 class="text-sm font-semibold">
                                Reject Landbank Request
                            </h3>

                            <p class="text-xs text-gray-500">
                                Please provide the reason for rejecting this
                                Landbank account request. The remarks will be
                                sent to the scholar.
                            </p>
                        </div>

                        <div class="border-t"></div>

                        <div class="flex flex-col gap-2">
                            <label class="text-xs font-semibold text-gray-700">
                                Remarks <span class="text-red-500">*</span>
                            </label>
                            <Textarea
                                id="remarks"
                                class="!text-sm"
                                placeholder="Help the user understand why this request was rejected and what needs to be corrected."
                                fluid
                                rows="5"
                                v-model="selectedRow.reject"
                            />
                        </div>

                        <div class="flex justify-end gap-2 pt-2">
                            <DefaultButton
                                label="Cancel"
                                rounded
                                severity="secondary"
                                @click="toggleOpReject"
                                outlined
                                size="small"
                                class-name="!px-4"
                            />

                            <DefaultButton
                                label="Reject this request"
                                rounded
                                severity="danger"
                                :loading="loading.reject"
                                @click="validationRequest('reject')"
                                size="small"
                                class-name="!px-5"
                            />
                        </div>
                    </div>
                </Popover>

                <Button
                    size="small"
                    class="!text-xs !rounded-xl"
                    raised
                    :loading="loading.approve"
                    @click="validationRequest('accept')"
                >
                    <template #default>
                        <div class="flex gap-1 items-center">
                            <IconCircleCheckFilled
                                :stroke-width="1.5"
                                :size="20"
                                v-if="!loading.approve"
                            />
                            <IconLoader2
                                v-else
                                :size="20"
                                class="animate-spin"
                            />
                            <p>Accept</p>
                        </div>
                    </template>
                </Button>
            </div>
        </template>
    </Dialog>
    <Dialog
        v-model:visible="landbankPasswordDialog"
        modal
        header="View Landbank Details"
        :style="{ width: '26rem' }"
        :pt="{
            root: 'dark:!bg-gray-900 dark:!text-gray-100',
            content: 'dark:!bg-gray-900 dark:!text-gray-100',
            header: 'dark:!bg-gray-900 dark:!text-gray-100',
        }"
    >
        <div class="flex flex-col gap-4">
            <TextInput
                v-model="landbankPassword"
                label="Password"
                type="password"
                fluid
                @keyup.enter="revealLandbank"
            />
            <div class="flex justify-end gap-2">
                <DefaultButton
                    label="Cancel"
                    severity="secondary"
                    outlined
                    rounded
                    size="small"
                    @click="closeLandbankPasswordDialog"
                />
                <DefaultButton
                    label="View"
                    rounded
                    size="small"
                    :loading="loading.revealLandbank"
                    @click="revealLandbank"
                />
            </div>
        </div>
    </Dialog>
</template>
<script setup>
import {
    IconExclamationCircle,
    IconExclamationCircleFilled,
    IconUserUp,
    IconHistory,
    IconUser,
    IconCalendarTime,
    IconArrowRight,
    IconLock,
    IconArrowNarrowRight,
    IconDatabase,
    IconDatabaseEdit,
    IconCreditCard,
    IconPaperclip,
    IconFileTypePdf,
    IconEye,
    IconCircleCheckFilled,
    IconCircleXFilled,
    IconLoader,
    IconFile,
    IconLoader2,
    IconHash,
    IconFileOff,
    IconEyeOff,
} from "@tabler/icons-vue";

import UploadInput from "../../Components/inputs/UploadInput.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import TextInput from "../../Components/inputs/TextInput.vue";
import { computed, ref, watch, onMounted } from "vue";
import { useForm, progress, usePage, router } from "@inertiajs/vue3";
import { useToast } from "primevue";
import { route } from "ziggy-js";

const props = defineProps({
    user: Object,
});

const modelValue = defineModel("modelValue");
const page = usePage();
const toast = useToast();
const selectedRow = ref(null);
const opFileViewer = ref(null);
const opReject = ref(null);
const loading = ref({
    approve: false,
    reject: false,
    revealLandbank: false,
});

const landbankRequest = ref(null);
const scholarPortalFileUrl = (path) => {
    if (!path) return null;

    if (/^https?:\/\//i.test(path)) {
        return path;
    }

    return `${page.props?.filePreview?.scholarPortalBaseUrl ?? ""}/${String(path).replace(/^\/+/, "")}`;
};
const landbankPasswordDialog = ref(false);
const landbankPassword = ref("");
const landbankRevealed = ref(false);
const revealedLandbank = ref({
    account_name: null,
    account_number: null,
});
const scholar = computed(() => page.props?.details ?? props.user ?? {});
const canRevealLandbank = computed(() =>
    (page.props?.permissions ?? []).includes("scholars.landbank.view-sensitive") ||
    [
        "administrator",
        "regional staff",
        "regional supervisor",
        "scholarship staff",
        "scholarship coordinator",
    ].includes(String(page.props?.user?.role_array?.name ?? "").toLowerCase()),
);
const hasStoredLandbankValues = computed(
    () =>
        Boolean(selectedRow.value?.hasNameStored) ||
        Boolean(selectedRow.value?.hasNoStored),
);
const maskedLandbankValue = (hasValue) => hasValue ? "**********************" : null;
const changedFields = computed(() => {
    const row = selectedRow.value;

    if (!row) {
        return [];
    }

    return [
        {
            key: "account_name",
            label: "Account Name",
            current: landbankRevealed.value
                ? revealedLandbank.value.account_name
                : maskedLandbankValue(row.hasNameStored),
            requested: row.name,
        },
        {
            key: "account_number",
            label: "Account No.",
            current: landbankRevealed.value
                ? revealedLandbank.value.account_number
                : maskedLandbankValue(row.hasNoStored),
            requested: row.no,
        },
    ].filter((field) => field.requested);
});

const selectedRequest = (item, index) => {
    selectedRow.value = item;
    selectedRow.value.index = index;
    hideLandbank();
};

const syncLandbankRequests = (requests) => {
    landbankRequest.value = requests ?? [];

    if (!selectedRow.value && landbankRequest.value.length) {
        selectedRequest(landbankRequest.value[0], 0);
    }
};

const toggleOpFileViewer = (event) => {
    opFileViewer.value.toggle(event);
};

const toggleOpReject = (event) => {
    opReject.value.toggle(event);
};

const closeLandbankPasswordDialog = () => {
    landbankPasswordDialog.value = false;
    landbankPassword.value = "";
};

const hideLandbank = () => {
    landbankRevealed.value = false;
    revealedLandbank.value = {
        account_name: null,
        account_number: null,
    };
    closeLandbankPasswordDialog();
};

const revealLandbank = async () => {
    if (!landbankPassword.value || loading.value.revealLandbank) return;

    loading.value.revealLandbank = true;

    try {
        const token = document
            .querySelector('meta[name="csrf-token"]')
            ?.getAttribute("content");
        const response = await fetch(
            `/scholars/${page.props?.details?.id}/landbank/reveal`,
            {
                method: "POST",
                headers: {
                    "Content-Type": "application/json",
                    Accept: "application/json",
                    "X-CSRF-TOKEN": token ?? "",
                },
                body: JSON.stringify({
                    password: landbankPassword.value,
                }),
            },
        );
        const payload = await response.json().catch(() => ({}));

        if (!response.ok) {
            throw new Error(payload.message || "Unable to reveal Landbank details.");
        }

        revealedLandbank.value = {
            account_name: payload.account_name ?? null,
            account_number: payload.account_number ?? null,
        };
        landbankRevealed.value = true;
        closeLandbankPasswordDialog();
    } catch (error) {
        toast.add({
            severity: "error",
            summary: "Unable to reveal Landbank details",
            detail: error.message,
            life: 3000,
        });
    } finally {
        loading.value.revealLandbank = false;
    }
};

const validationRequest = (decision) => {
    router.post(
        route("landbank.request", { type: decision }),
        {
            data: selectedRow.value,
        },
        {
            onBefore: () => {
                if (decision === "reject") {
                    loading.value.reject = true;
                } else {
                    loading.value.approve = true;
                }
            },
            onSuccess: () => {
                toast.add({
                    severity: page.props.flash?.status || "success",
                    summary: page.props.flash?.title || "Success",
                    detail:
                        page.props.flash?.message ||
                        "Request has been processed successfully.",
                    life: 3000,
                });

                if (page.props.flash?.status === "success") {
                    landbankRequest.value[selectedRow.value.index].status =
                        decision === "accept" ? "approved" : "rejected";
                    selectedRow.value.reviewed_at = "Just now";
                    selectedRow.value.reviewed_by =
                        page.props.user.profile.fullname;
                }
            },

            onFinish: () => {
                if (decision === "reject") {
                    loading.value.reject = false;
                } else {
                    loading.value.approve = false;
                }
            },
        },
    );
};

onMounted(() => {
    syncLandbankRequests(page.props?.landbankRequest);
});

watch(
    () => page.props?.landbankRequest,
    (requests) => syncLandbankRequests(requests),
);
</script>
