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
                <IconUserEdit :size="18" :stroke-width="2" />
                <div class="uppercase font-medium text-sm">
                    Scholar Detail request
                </div>
            </div>
        </template>
        <template #default>
            <div class="">
                <div
                    class="flex flex-col lg:flex-row h-full w-full lg:min-h-150"
                >
                    <div
                        class="w-full lg:w-4/12 bg-slate-100 lg:rounded-bl-xl flex flex-col max-h-[95vw] gap-3 p-3"
                    >
                        <div class="flex items-center gap-1">
                            <IconHistory :size="20" />
                            <div class="text-sm uppercase font-medium">
                                History Request:
                            </div>
                        </div>
                        <template
                            v-for="(item, index) in personalRequest"
                            :key="index"
                            class="overflow-x-auto"
                        >
                            <div
                                class="border rounded-xl gap-3 hover:shadow border-gray-200 bg-white flex flex-col text-sm p-2"
                            >
                                <div class="flex justify-between items-center">
                                    <div class="flex items-center gap-1">
                                        <Avatar
                                            class="!w-[40px] !h-[40px] shadow border border-slate-400 !rounded-xl"
                                        >
                                            <IconUserEdit :size="23" />
                                        </Avatar>
                                        <div class="text-sm">
                                            <div class="text-xs text-slate-500">
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
                                        v-else-if="item.status == 'approved'"
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
                    <div
                        class="flex flex-col gap-2 p-3 h-full w-full lg:w-8/12"
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
                                        {{
                                            (selectedRow ?? personalRequest?.[0])
                                                ?.fullname ?? "-"
                                        }}
                                    </div>

                                    <div
                                        class="mt-1 flex items-center gap-1 text-sm text-gray-500"
                                    >
                                        <IconHash :size="14" />
                                        {{
                                            (selectedRow ?? personalRequest?.[0])
                                                ?.spas_no ?? "-"
                                        }}
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
                                        {{
                                            (selectedRow ?? personalRequest?.[0])
                                                ?.scholarshipProgram ?? "-"
                                        }}
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
                                        {{
                                            (selectedRow ?? personalRequest?.[0])
                                                ?.program ?? "-"
                                        }}
                                    </div>
                                </div>
                            </div>
                        </div>
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
                                        class="flex flex-col justify-between h-90"
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
                                                    class="flex flex-col gap-3 bg-slate-50 rounded-lg p-1"
                                                >
                                                    <div class="leading-none">
                                                        <div
                                                            class="text-sm text-gray-500 font-light"
                                                        >
                                                            Email
                                                        </div>
                                                        <p>
                                                            {{
                                                                selectedRow.emailStored
                                                            }}
                                                        </p>
                                                    </div>
                                                    <div class="leading-none">
                                                        <div
                                                            class="text-sm text-gray-500 font-light"
                                                        >
                                                            Contact No.
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
                                                                    ?.contact_no ??
                                                                "No record"
                                                            }}
                                                        </p>
                                                        <p v-else>
                                                            {{
                                                                selectedRow.contactStored ??
                                                                "No record"
                                                            }}
                                                        </p>
                                                    </div>
                                                    <div class="">
                                                        <div
                                                            class="text-sm text-gray-500 font-light"
                                                        >
                                                            Civil Status
                                                        </div>
                                                        <p
                                                            class="uppercase"
                                                            v-if="
                                                                selectedRow.reviewed_at
                                                            "
                                                        >
                                                            {{
                                                                selectedRow
                                                                    .records
                                                                    ?.previous
                                                                    ?.contact_no ??
                                                                "No record"
                                                            }}
                                                        </p>
                                                        <p v-else>
                                                            {{
                                                                selectedRow.contactStored ??
                                                                "No record"
                                                            }}
                                                        </p>
                                                    </div>
                                                    <div class="">
                                                        <div
                                                            class="text-sm text-gray-500 font-light"
                                                        >
                                                            Address
                                                        </div>
                                                        <p>
                                                            {{
                                                                selectedRow.fullAddress
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
                                                    class="flex flex-col gap-3 bg-slate-50 rounded-lg p-1"
                                                >
                                                    <div class="leading-none">
                                                        <div
                                                            class="text-sm text-gray-500 font-light"
                                                        >
                                                            Email
                                                        </div>
                                                        <p>
                                                            {{
                                                                selectedRow.email
                                                            }}
                                                        </p>
                                                    </div>
                                                    <div class="leading-none">
                                                        <div
                                                            class="text-sm text-gray-500 font-light"
                                                        >
                                                            Contact No.
                                                        </div>
                                                        <p>
                                                            {{
                                                                selectedRow.contact_no
                                                            }}
                                                        </p>
                                                    </div>
                                                    <div class="">
                                                        <div
                                                            class="text-sm text-gray-500 font-light"
                                                        >
                                                            Civil Status
                                                        </div>
                                                        <p>
                                                            {{
                                                                selectedRow.civil_status
                                                            }}
                                                        </p>
                                                    </div>
                                                    <div class="">
                                                        <div
                                                            class="text-sm text-gray-500 font-light"
                                                        >
                                                            Address
                                                        </div>
                                                        <p>
                                                            {{
                                                                selectedRow.fullAddress
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
                                                v-if="selectedRow.file_type"
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
                                                <Button
                                                    size="small"
                                                    class="!text-xs !rounded-full"
                                                    :loading="loading.approve"
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
                                                                View uploaded
                                                                file
                                                            </p>
                                                        </div>
                                                    </template>
                                                </Button>
                                            </div>
                                            <div
                                                v-else
                                                class="flex justify-center"
                                            >
                                                <div
                                                    class="flex items-center gap-2 text-gray-500"
                                                >
                                                    <IconFileOff :size="20" />
                                                    <div class="text-sm">
                                                        No attachment file
                                                        available.
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div></div>
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
                            class="flex flex-col gap-3"
                            v-if="selectedRow && !selectedRow.requested_at"
                        >
                            <div class="leading-none">
                                <label for="remarks" class="text-sm"
                                    >Remarks</label
                                >
                                <Textarea
                                    id="remarks"
                                    class="!text-sm"
                                    placeholder="Help the user understand why this request was rejected and what needs to be corrected."
                                    fluid
                                    rows="5"
                                    v-model="selectedRow.reject"
                                    :disabled="
                                        selectedRow.reviewed_at ? true : false
                                    "
                                />
                            </div>
                            <div class="flex flex-col gap-4">
                                <div
                                    class="flex justify-end"
                                    v-if="!selectedRow.reviewed_at"
                                >
                                    <div class="flex items-center mb-3 gap-3">
                                        <Button
                                            size="small"
                                            class="!text-xs !rounded-xl"
                                            outlined
                                            :loading="loading.reject"
                                            severity="danger"
                                            @click="approveRequest('reject')"
                                        >
                                            <template #default>
                                                <div
                                                    class="flex gap-1 items-center"
                                                >
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
                                        <Button
                                            size="small"
                                            class="!text-xs !rounded-xl"
                                            raised
                                            :loading="loading.approve"
                                            @click="approveRequest('accept')"
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
    IconUserUp,
    IconHistory,
    IconUser,
    IconCalendarTime,
    IconArrowRight,
    IconLock,
    IconArrowBigRightLines,
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
const loading = ref({
    approve: false,
    reject: false,
});
const personalRequest = ref(null);

const selectedRequest = (item, index) => {
    selectedRow.value = item;
    selectedRow.value.index = index;
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

onMounted(() => {
    personalRequest.value = page.props?.personalRequest;
});
</script>
