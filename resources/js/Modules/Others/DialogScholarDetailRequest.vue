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
                <IconUserEdit :size="18" :stroke-width="2" />
                <div class="uppercase font-medium text-sm">
                    Scholar Detail request
                </div>
            </div>
        </template>
        <template #default>
            <div class="bg-white dark:bg-gray-900 dark:text-gray-100">
                <div
                    class="flex flex-col lg:flex-row h-full w-full lg:h-[40rem]"
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
                                v-for="(item, index) in personalRequest"
                                :key="index"
                                class="overflow-x-auto"
                            >
                                <div
                                    class="border rounded-xl gap-3 hover:shadow border-gray-200 bg-white flex flex-col text-sm p-2 dark:border-gray-600 dark:bg-gray-700 dark:text-gray-100"
                                >
                                    <div
                                        class="flex justify-between items-center"
                                    >
                                        <div class="flex items-center gap-1">
                                            <Avatar
                                                class="!w-[40px] !h-[40px] shadow border border-slate-400 !rounded-xl"
                                            >
                                                <IconUserEdit :size="23" />
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
                                    <div class="flex-1 flex flex-col p-1">
                                        <div
                                            class="flex-1 flex flex-col p-1 gap-2 text-sm"
                                        >
                                            <div class="flex-1">
                                                <div
                                                    class="text-xs text-slate-500 dark:text-gray-400"
                                                >
                                                    Requested On
                                                </div>
                                                <p class="font-medium">
                                                    {{ item.request_date }}
                                                </p>
                                            </div>
                                            <div class="flex-1">
                                                <div
                                                    class="text-xs text-slate-500 dark:text-gray-400"
                                                >
                                                    Reason for Change
                                                </div>
                                                <p class="font-medium">
                                                    {{ item.purpose }}
                                                </p>
                                            </div>
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
                                                        {{
                                                            toTitleCase(
                                                                item.reviewed_by,
                                                            )
                                                        }}
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
                        class="flex flex-col gap-2 p-3 h-full w-full min-h-0 overflow-y-auto lg:w-8/12"
                    >
                        <div
                            class="flex flex-col gap-4 border-b border-gray-200 pb-4 lg:flex-row lg:items-center lg:justify-between"
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
                                        class="text-lg font-bold text-gray-900 leading-5"
                                    >
                                        {{ scholar.fullname }}
                                    </div>

                                    <div
                                        class="mt-1 flex items-center gap-1 text-sm text-gray-500"
                                    >
                                        <IconHash :size="14" />
                                        {{ scholar.spas_no }}
                                    </div>
                                </div>
                            </div>

                            <div class="flex items-center gap-8">
                                <div class="text-right">
                                    <div
                                        class="text-xs uppercase tracking-wider text-gray-400"
                                    >
                                        Scholarship
                                    </div>

                                    <div class="font-semibold text-gray-800">
                                        {{ scholar.type }}
                                    </div>
                                </div>

                                <Divider layout="vertical" class="!h-10" />

                                <div class="text-right">
                                    <div
                                        class="text-xs uppercase tracking-wider text-gray-400"
                                    >
                                        Program
                                    </div>

                                    <div class="font-semibold text-gray-800">
                                        {{ scholar.program }}
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
                                    root: '!p-2 w-full',
                                    content: 'pb-2',
                                }"
                            >
                                <template #legend>
                                    <p class="text-sm font-medium">
                                        Req# {{ selectedRow.count }}
                                    </p>
                                </template>
                                <template #default>
                                    <div class="flex flex-col gap-2">
                                        <div
                                            class="flex flex-col gap-3"
                                        >
                                            <div
                                                class="bg-green-50 border-b border-green-200 px-4 py-2 flex items-center gap-2 dark:bg-green-950/30 dark:border-green-800"
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
                                                    v-for="field in changedFields"
                                                    :key="field.key"
                                                    class="grid grid-cols-[minmax(0,1fr)_auto_minmax(0,1fr)] items-center gap-3 rounded-lg border border-slate-200 bg-slate-50 px-4 py-3 dark:border-gray-700 dark:bg-gray-800"
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
                                                        class="flex h-9 w-9 items-center justify-center rounded-full bg-white text-slate-500 shadow-sm dark:bg-gray-900 dark:text-gray-300"
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
                                                class="rounded-lg border border-slate-200 bg-slate-50 px-4 py-6 text-center text-sm text-slate-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
                                            >
                                                No changed fields were found for
                                                this request.
                                            </div>
                                        </div>

                                        <Divider type="dashed" class="!my-1" />
                                        <div
                                            class="flex flex-col w-full gap-2"
                                        >
                                            <div
                                                class="flex items-center gap-1 text-slate-500"
                                            >
                                                <IconPaperclip :size="15" />
                                                <div class="text-xs">
                                                    Attachment (Optional)
                                                </div>
                                            </div>
                                            <div
                                                v-if="selectedRow.file"
                                                class="flex-1 gap-3 flex justify-between"
                                            >
                                                <div class="flex items-center">
                                                    <div
                                                        class="flex items-center gap-1"
                                                    >
                                                        <Avatar
                                                            class="!w-[35px] !h-[35px] border !text-slate-500 border-slate-400 !rounded-xl"
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
                                                                    selectedRow.file_type
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
                                                                class="w-full h-[500px] border-0"
                                                            />
                                                        </div>
                                                    </Popover>
                                                </div>
                                            </div>
                                            <div
                                                v-else
                                                class="flex items-center gap-2 rounded-lg border border-dashed border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-500 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300"
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
                v-if="selectedRow && selectedRow.requested_at && !selectedRow.reviewed_at"
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
                                Reject Profile Request
                            </h3>

                            <p class="text-xs text-gray-500">
                                Please provide the reason for rejecting this
                                profile request. The remarks will be sent to the
                                scholar.
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
                                v-model="selectedRow.remarks"
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
                                @click="approveRequest('reject')"
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
                    @click="approveRequest('accept')"
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
</template>
<script setup>
import {
    IconExclamationCircle,
    IconUserUp,
    IconHistory,
    IconUser,
    IconPaperclip,
    IconCalendarTime,
    IconArrowRight,
    IconLock,
    IconArrowNarrowRight,
    IconDatabase,
    IconDatabaseEdit,
    IconCircleCheckFilled,
    IconLoader2,
    IconCircleXFilled,
    IconFileTypePdf,
    IconEye,
    IconUserEdit,
    IconFileOff,
    IconHash,
    IconFile,
} from "@tabler/icons-vue";
import UploadInput from "../../Components/inputs/UploadInput.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import { computed, ref, watch, onMounted } from "vue";
import { useForm, progress, usePage, router } from "@inertiajs/vue3";
import { useToast } from "primevue";
import { route } from "ziggy-js";

const props = defineProps({
    user: Array,
});

const modelValue = defineModel("modelValue");
const page = usePage();
const toast = useToast();
const selectedRow = ref(null);
const opReject = ref(null);
const opFileViewer = ref(null);
const loading = ref({
    approve: false,
    reject: false,
});
const personalRequest = ref(null);
const scholarPortalFileUrl = (path) => {
    if (!path) return null;

    if (/^https?:\/\//i.test(path)) {
        return path;
    }

    return `${page.props?.filePreview?.scholarPortalBaseUrl ?? ""}/${String(path).replace(/^\/+/, "")}`;
};

const scholar = computed(() => {
    const details = page.props?.details ?? {};
    const row = selectedRow.value ?? personalRequest.value?.[0] ?? {};

    return {
        fullname: details.fullname ?? row.fullname ?? "-",
        spas_no: details.spas_no ?? row.spas_no ?? "-",
        type: details.type?.name ?? row.scholarshipProgram ?? row.type ?? "-",
        program: details.program?.name ?? row.program ?? "-",
    };
});

const changedFields = computed(() => {
    const row = selectedRow.value;

    if (!row) {
        return [];
    }

    return [
        {
            key: "first_name",
            label: "First Name",
            current: row.firstNameStored,
            requested: row.first_name,
        },
        {
            key: "middle_name",
            label: "Middle Name",
            current: row.middleNameStored,
            requested: row.middle_name,
        },
        {
            key: "last_name",
            label: "Last Name",
            current: row.lastNameStored,
            requested: row.last_name,
        },
        {
            key: "suffix",
            label: "Suffix",
            current: row.suffixStored,
            requested: row.suffix,
        },
        {
            key: "email",
            label: "Email",
            current: row.emailStored,
            requested: row.email,
        },
        {
            key: "contact_no",
            label: "Contact No.",
            current: row.contactStored,
            requested: row.contact_no,
        },
        {
            key: "civil_status",
            label: "Civil Status",
            current: row.civilStored,
            requested: row.civil_status,
        },
        {
            key: "address",
            label: "Address",
            current: row.fullAddressStored,
            requested: row.fullAddress,
        },
    ].filter((field) => field.requested);
});

const selectedRequest = (item, index) => {
    selectedRow.value = item;
    selectedRow.value.index = index;
};

const syncPersonalRequest = (requests) => {
    personalRequest.value = requests ?? [];
    selectedRow.value = personalRequest.value[0] ?? null;

    if (selectedRow.value) {
        selectedRow.value.index = 0;
    }
};

const toggleOpReject = (event) => {
    opReject.value.toggle(event);
};

const approveRequest = (decision) => {
    router.post(
        route("profile.request", { type: decision }),
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
                    personalRequest.value[selectedRow.value.index].status =
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

const toggleOpFileViewer = (event) => {
    opFileViewer.value.toggle(event);
};

function toTitleCase(name) {
    return name
        .toLocaleLowerCase("es")
        .replace(/(^|\s)\p{L}/gu, (match) => match.toLocaleUpperCase("es"));
}

onMounted(() => {
    syncPersonalRequest(page.props?.personalRequest);
});

watch(
    () => page.props?.personalRequest,
    (requests) => {
        syncPersonalRequest(requests);
    },
);
</script>
