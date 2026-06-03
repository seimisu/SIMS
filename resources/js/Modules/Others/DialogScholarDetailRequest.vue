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
                <IconUserUp :size="18" :stroke-width="2" />
                <div class="uppercase font-medium text-sm">
                    Scholar Detail request
                </div>
            </div>
        </template>
        <template #default>
            <div class="">
                <div
                    class="flex flex-col lg:flex-row h-full w-full lg:h-[40rem]"
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
                                    <div class="font-medium">
                                        Request #<span class="text-slate-500">{{
                                            item.count
                                        }}</span>
                                    </div>
                                    <div
                                        v-if="item.status == 'pending'"
                                        class="text-xs bg-amber-100 text-amber-500 border capitalize rounded-xl px-3 py-1"
                                    >
                                        {{ item.status }}
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
                                    <div class="flex-1">
                                        <table class="table table-auto">
                                            <tbody>
                                                <tr>
                                                    <td
                                                        class="w-32 font-medium"
                                                    >
                                                        Email
                                                    </td>
                                                    <td>{{ item.email }}</td>
                                                </tr>
                                                <tr>
                                                    <td
                                                        class="w-32 font-medium"
                                                    >
                                                        Contact No
                                                    </td>
                                                    <td>
                                                        {{ item.contact_no }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td
                                                        class="w-32 font-medium"
                                                    >
                                                        Civil Status
                                                    </td>
                                                    <td>
                                                        {{ item.civil_status }}
                                                    </td>
                                                </tr>
                                                <tr>
                                                    <td
                                                        class="w-32 font-medium align-top"
                                                    >
                                                        Address
                                                    </td>
                                                    <td>
                                                        {{ item.fullAddress }}
                                                    </td>
                                                </tr>
                                            </tbody>
                                        </table>
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
                        class="flex flex-col gap-5 p-3 h-full w-full lg:w-8/12"
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
                        <div
                            class="flex items-center w-full h-full justify-center gap-1"
                            v-if="selectedRow"
                        >
                            <Fieldset :pt="{ root: '!p-2' }">
                                <template #legend>
                                    <p class="text-sm font-medium">
                                        Req# {{ selectedRow.count }}
                                    </p>
                                </template>
                                <template #default>
                                    <div
                                        class="flex items-center justify-evenly gap-3 w-full"
                                    >
                                        <div class="flex flex-col gap-2">
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
                                                    <p>
                                                        {{
                                                            selectedRow.contactStored ??
                                                            "No data"
                                                        }}
                                                    </p>
                                                </div>
                                                <div class="">
                                                    <div
                                                        class="text-sm text-gray-500 font-light"
                                                    >
                                                        Civil Status
                                                    </div>
                                                    <p class="uppercase">
                                                        {{
                                                            selectedRow.civilStored
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
                                        <div class="">
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
                                        <div class="flex flex-col gap-2">
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
                                                        {{ selectedRow.email }}
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

                        <div class="flex flex-col gap-4" v-if="selectedRow">
                            <div class="leading-none">
                                <label for="remarks" class="text-sm"
                                    >Remarks</label
                                >
                                <Textarea
                                    id="remarks"
                                    fluid
                                    rows="5"
                                    v-model="selectedRow.remarks"
                                    :disabled="selectedRow.reviewed_at"
                                />
                            </div>

                            <div class="flex justify-end">
                                <div class="flex items-center gap-5">
                                    <Button
                                        label="Reject Request"
                                        size="small"
                                        :disabled="
                                            selectedRow.reviewed_at
                                                ? true
                                                : false
                                        "
                                        class="!text-xs !rounded-lg"
                                        severity="danger"
                                        outlined
                                        :loading="loading.reject"
                                        @click="approveRequest('reject')"
                                    />
                                    <Button
                                        label="Approve Request"
                                        :disabled="
                                            selectedRow.reviewed_at
                                                ? true
                                                : false
                                        "
                                        size="small"
                                        class="!text-xs !rounded-lg"
                                        :loading="loading.approve"
                                        @click="approveRequest('accept')"
                                        raised
                                    />
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
