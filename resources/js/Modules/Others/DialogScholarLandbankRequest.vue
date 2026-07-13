<template>
    <Dialog
        v-model:visible="modelValue"
        modal
        :pt="{
            header: 'border-b-1 border-gray-300 border-dashed',
            root: 'w-[99%] lg:w-[70rem]',
            content: '!p-0',
        }"
    >
        <template #header>
            <div
                class="bg-slate-100 px-4 py-2 shadow rounded-lg flex items-center gap-2"
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
                        class="w-full lg:w-4/12 bg-slate-100 lg:rounded-bl-xl flex flex-col flex-1 overflow-y-auto p-3 gap-3"
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
                                    class="border rounded-xl gap-3 hover:shadow border-gray-200 bg-white flex flex-col text-sm p-2"
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
                                                    class="text-xs text-slate-500"
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
                                            <div class="text-xs text-slate-500">
                                                Requested On
                                            </div>
                                            <p class="font-medium">
                                                {{ item.request_date }}
                                            </p>
                                        </div>
                                        <div class="flex-1">
                                            <div class="text-xs text-slate-500">
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
                                                    <div>
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
                        class="flex flex-col gap-2 p-3 h-full w-full lg:w-8/12"
                    >
                        <div
                            class="flex items-start p-3 shadow border border-blue-300 text-blue-500 rounded-xl bg-blue-50 gap-1"
                        >
                            <div>
                                <IconExclamationCircleFilled :size="20" />
                            </div>

                            <p class="text-xs leading-5 text-justify">
                                Please upload the scholar’s complete information
                                and supporting documents. Ensure that all
                                required fields are properly filled out and the
                                uploaded files are accurate and up to date
                                before submitting.
                            </p>
                        </div>
                        {{ console.log(selectedRow) }}
                        <div
                            class="flex items-center w-full h-full gap-1"
                            v-if="selectedRow"
                        >
                            <Fieldset
                                :pt="{
                                    root: '!p-2 w-full ',
                                    content: '!h-full',
                                }"
                            >
                                <template #legend>
                                    <p class="text-sm font-medium">
                                        Req# {{ selectedRow.count }}
                                    </p>
                                </template>
                                <template #default>
                                    <div
                                        class="flex flex-col justify-between h-70"
                                    >
                                        <div
                                            class="flex-1 flex w-full justify-evenly"
                                        >
                                            <div
                                                class="flex-2 flex flex-col gap-2"
                                            >
                                                <div
                                                    class="bg-amber-50 border-b border-amber-200 px-4 py-2 flex items-center gap-2"
                                                >
                                                    <IconDatabase
                                                        class="text-amber-500"
                                                        :size="18"
                                                    />

                                                    <div
                                                        class="text-sm font-semibold text-amber-700"
                                                    >
                                                        Stored Data
                                                    </div>
                                                </div>
                                                <div
                                                    class="flex flex-col gap-3 bg-slate-50 rounded-lg p-1 flex-1"
                                                >
                                                    <div class="leading-none">
                                                        <div
                                                            class="text-xs text-gray-500 font-light"
                                                        >
                                                            Account Name
                                                        </div>

                                                        <p
                                                            v-if="
                                                                selectedRow.reviewed_at
                                                            "
                                                        >
                                                            {{
                                                                selectedRow
                                                                    .records
                                                                    ?.previous
                                                                    ?.account_name ??
                                                                "No record"
                                                            }}
                                                        </p>
                                                        <p v-else>
                                                            {{
                                                                selectedRow.nameStored ??
                                                                "No record"
                                                            }}
                                                        </p>
                                                    </div>
                                                    <div class="leading-none">
                                                        <div
                                                            class="text-xs text-gray-500 font-light"
                                                        >
                                                            Account No.
                                                        </div>
                                                        <p
                                                            v-if="
                                                                selectedRow.reviewed_at
                                                            "
                                                        >
                                                            {{
                                                                selectedRow
                                                                    .records
                                                                    ?.previous
                                                                    ?.account_number ??
                                                                "No record"
                                                            }}
                                                        </p>
                                                        <p v-else>
                                                            {{
                                                                selectedRow.noStored ??
                                                                "No record"
                                                            }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                            <div
                                                class="flex-1 flex items-center justify-center"
                                            >
                                                <div
                                                    class="flex flex-col items-center text-gray-500"
                                                >
                                                    <IconArrowBigRightLines
                                                        :size="35"
                                                    />
                                                    <div
                                                        class="text-xs text-nowrap"
                                                    >
                                                        Change to
                                                    </div>
                                                </div>
                                            </div>
                                            <div
                                                class="flex-2 flex flex-col gap-2"
                                            >
                                                <div
                                                    class="bg-green-50 border-b border-green-200 px-4 py-2 flex items-center gap-2"
                                                >
                                                    <IconDatabaseEdit
                                                        class="text-green-500"
                                                        :size="18"
                                                    />

                                                    <div
                                                        class="text-sm font-semibold text-green-700"
                                                    >
                                                        Requested Changes
                                                    </div>
                                                </div>
                                                <div
                                                    class="flex flex-col gap-3 bg-slate-50 rounded-lg p-1 flex-1"
                                                >
                                                    <div class="leading-none">
                                                        <div
                                                            class="text-xs text-gray-500 font-light"
                                                        >
                                                            Account Name
                                                        </div>
                                                        <p>
                                                            {{
                                                                selectedRow.name ??
                                                                "No record"
                                                            }}
                                                        </p>
                                                    </div>
                                                    <div class="leading-none">
                                                        <div
                                                            class="text-xs text-gray-500 font-light"
                                                        >
                                                            Account No.
                                                        </div>
                                                        <p>
                                                            {{
                                                                selectedRow.no ??
                                                                "No record"
                                                            }}
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <Divider type="dashed" />
                                        <div class="flex flex-col w-full gap-2">
                                            <div
                                                class="flex items-center gap-1 text-slate-500"
                                            >
                                                <IconPaperclip :size="15" />
                                                <div class="text-xs">
                                                    Attachment (Optional)
                                                </div>
                                            </div>
                                            <div
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
                                                                            'http://172.16.8.35/' +
                                                                            selectedRow.file
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
                                                                            'http://172.16.8.35/' +
                                                                            selectedRow.file
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
                                                                    'http://172.16.8.35/' +
                                                                    selectedRow.file
                                                                "
                                                                class="w-full h-[500px] border-0"
                                                            />
                                                        </div>
                                                    </Popover>
                                                </div>
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
                            v-if="selectedRow.reject"
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
                        <div class="flex flex-col gap-3" v-if="selectedRow">
                            <div class="flex flex-col gap-4">
                                <div
                                    class="flex justify-end"
                                    v-if="!selectedRow.reviewed_at"
                                >
                                    <div class="flex items-center gap-3">
                                        <div>
                                            <Button
                                                size="small"
                                                class="!text-xs !rounded-xl"
                                                outlined
                                                :loading="loading.reject"
                                                severity="danger"
                                                @click="toggleOpReject"
                                            >
                                                <template #default>
                                                    <div
                                                        class="flex gap-1 items-center"
                                                    >
                                                        <IconCircleXFilled
                                                            :stroke-width="1.5"
                                                            :size="20"
                                                            v-if="
                                                                !loading.reject
                                                            "
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
                                                <div
                                                    class="w-[26rem] p-1 flex flex-col gap-4"
                                                >
                                                    <!-- Header -->
                                                    <div
                                                        class="flex flex-col gap-1"
                                                    >
                                                        <h3
                                                            class="text-sm font-semibold"
                                                        >
                                                            Reject Landbank
                                                            Request
                                                        </h3>

                                                        <p
                                                            class="text-xs text-gray-500"
                                                        >
                                                            Please provide the
                                                            reason for rejecting
                                                            this Landbank
                                                            account request. The
                                                            remarks will be sent
                                                            to the scholar.
                                                        </p>
                                                    </div>

                                                    <!-- Divider -->
                                                    <div class="border-t"></div>

                                                    <!-- Form -->
                                                    <div
                                                        class="flex flex-col gap-2"
                                                    >
                                                        <label
                                                            class="text-xs font-semibold text-gray-700"
                                                        >
                                                            Remarks
                                                            <span
                                                                class="text-red-500"
                                                                >*</span
                                                            >
                                                        </label>
                                                        <Textarea
                                                            id="remarks"
                                                            class="!text-sm"
                                                            placeholder="Help the user understand why this request was rejected and what needs to be corrected."
                                                            fluid
                                                            rows="5"
                                                            v-model="
                                                                selectedRow.reject
                                                            "
                                                            :disabled="
                                                                selectedRow.reviewed_at
                                                                    ? true
                                                                    : false
                                                            "
                                                        />
                                                    </div>

                                                    <!-- Actions -->
                                                    <div
                                                        class="flex justify-end gap-2 pt-2"
                                                    >
                                                        <DefaultButton
                                                            label="Cancel"
                                                            rounded
                                                            severity="secondary"
                                                            @click="
                                                                toggleOpReject
                                                            "
                                                            outlined
                                                            size="small"
                                                            class-name="!px-4"
                                                        />

                                                        <DefaultButton
                                                            label="Reject this request"
                                                            rounded
                                                            severity="danger"
                                                            :loading="
                                                                loading.reject
                                                            "
                                                            @click="
                                                                validationRequest(
                                                                    'reject',
                                                                )
                                                            "
                                                            size="small"
                                                            class-name="!px-5"
                                                        />
                                                    </div>
                                                </div>
                                            </Popover>
                                        </div>

                                        <Button
                                            size="small"
                                            class="!text-xs !rounded-xl"
                                            raised
                                            :loading="loading.approve"
                                            @click="validationRequest('accept')"
                                        >
                                            <template #default>
                                                <div
                                                    class="flex gap-1 items-center"
                                                >
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
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
    IconArrowBigRightLines,
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
} from "@tabler/icons-vue";

import UploadInput from "../../Components/inputs/UploadInput.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import { ref, watch, onMounted } from "vue";
import { useForm, progress, usePage, router } from "@inertiajs/vue3";
import { useToast } from "primevue";
import { route } from "ziggy-js";

const modelValue = defineModel("modelValue");
const page = usePage();
const toast = useToast();
const selectedRow = ref(null);
const opFileViewer = ref(null);
const opReject = ref(null);
const loading = ref({
    approve: false,
    reject: false,
});

const landbankRequest = ref(null);

const selectedRequest = (item, index) => {
    selectedRow.value = item;
    selectedRow.value.index = index;
};

const toggleOpFileViewer = (event) => {
    opFileViewer.value.toggle(event);
};

const toggleOpReject = (event) => {
    opReject.value.toggle(event);
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
    landbankRequest.value = page.props?.landbankRequest;
});
</script>
