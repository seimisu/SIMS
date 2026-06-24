<template>
    <Dialog
        v-model:visible="modelValue"
        modal
        :pt="{
            header: 'border-b-1 border-gray-300 border-dashed',
            root: 'w-[99%] lg:w-[110rem]',
            content: '!p-0',
        }"
    >
        <template #header>
            <div
                class="bg-slate-100 px-4 py-2 shadow rounded-lg flex items-center gap-2"
            >
                <IconUserUp :size="18" :stroke-width="2" />
                <div class="uppercase font-medium text-sm">
                    Scholar Subjects & Grades Request
                </div>
            </div>
        </template>
        <template #default>
            <div class="w-full flex">
                <div
                    class="flex-6 flex flex-col gap-3 overflow-auto max-h-190 p-5"
                >
                    <div class="flex flex-col gap-5" v-if="details">
                        <template v-for="(item, index) in details" :key="index">
                            <Divider>
                                <span class="text-sm font-semibold"
                                    >{{ item.academicYear }} /
                                    {{ item.term }}</span
                                >
                            </Divider>
                            <div class="flex flex-col gap-3">
                                <div class="flex-1 flex justify-between">
                                    <div>
                                        <div class="text-xs text-slate-500">
                                            School/Course
                                        </div>
                                        <div class="text-sm">
                                            <p class="">
                                                {{ item.school }}
                                            </p>
                                            <p class="">
                                                {{ item.course }}
                                            </p>
                                        </div>
                                    </div>
                                    <div>
                                        <div class="text-xs text-slate-500">
                                            Select Document to view
                                        </div>
                                        <div
                                            class="flex gap-1 items-center"
                                            v-if="item.files"
                                        >
                                            <template
                                                v-for="(
                                                    file, fileKey
                                                ) in item.files"
                                                :key="fileKey"
                                            >
                                                <div
                                                    v-if="
                                                        index == 0 &&
                                                        file.document_type ==
                                                            'grades_proof'
                                                    "
                                                >
                                                    <Button
                                                        size="small"
                                                        class="!rounded-2xl"
                                                        text
                                                        @click="
                                                            selectFile(file)
                                                        "
                                                    >
                                                        <div
                                                            class="flex items-center gap-2"
                                                        >
                                                            <IconFile
                                                                :size="18"
                                                            />
                                                            <div
                                                                class="text-xs font-medium"
                                                            >
                                                                Proof of Grades
                                                            </div>
                                                        </div>
                                                    </Button>
                                                </div>
                                                <div
                                                    v-if="
                                                        index == 1 &&
                                                        file.document_type ==
                                                            'cor'
                                                    "
                                                >
                                                    <Button
                                                        size="small"
                                                        class="w-36 !rounded-2xl"
                                                        text
                                                        @click="
                                                            selectFile(file)
                                                        "
                                                    >
                                                        <div
                                                            class="flex items-center gap-2"
                                                        >
                                                            <IconFile
                                                                :size="18"
                                                            />
                                                            <div
                                                                class="text-xs font-medium"
                                                            >
                                                                COR
                                                            </div>
                                                        </div>
                                                    </Button>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                                <table class="min-w-full !border-none text-sm">
                                    <thead>
                                        <tr class="bg-gray-100">
                                            <th
                                                class="px-3 py-2 text-left rounded-l-xl"
                                            >
                                                Subject Code
                                            </th>
                                            <th class="px-3 py-2 text-left">
                                                Subject Description
                                            </th>

                                            <th class="px-3 py-2 text-right">
                                                Unit
                                            </th>
                                            <th class="px-3 py-2 text-right">
                                                Grades
                                            </th>
                                            <th class="px-3 py-2 text-right">
                                                Total
                                            </th>
                                            <th
                                                class="px-3 py-2 text-center rounded-r-xl"
                                            >
                                                Remarks
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(
                                                subject, key
                                            ) in item.subjects"
                                            :key="key"
                                            class="hover:bg-gray-50"
                                        >
                                            <td
                                                class="px-3 py-2 uppercase align-text-top"
                                            >
                                                {{ subject.code }}
                                            </td>
                                            <td
                                                class="px-3 py-2 uppercase max-w-70 min-w-90 align-text-top"
                                            >
                                                {{ subject.subject }}
                                            </td>

                                            <td
                                                class="px-3 py-2 text-right align-text-top"
                                            >
                                                {{ subject.unit }}
                                            </td>
                                            <td
                                                class="px-3 py-2 text-right max-w-35 align-text-top"
                                            >
                                                <p v-if="subject.grade?.grade">
                                                    {{ subject.grade?.grade }}
                                                </p>
                                                <p
                                                    class="text-xs text-gray-400"
                                                    v-else
                                                >
                                                    No Grade yet
                                                </p>
                                            </td>
                                            <td
                                                class="px-3 py-2 text-right align-text-top"
                                            >
                                                {{ subject.total ?? "-" }}
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <div
                                                    v-if="subject?.is_drop"
                                                    class="text-red-600"
                                                >
                                                    Dropped
                                                </div>
                                                <div
                                                    v-else-if="
                                                        subject?.is_failed
                                                    "
                                                    class="text-rose-600"
                                                >
                                                    Failed
                                                </div>
                                                <div
                                                    v-else-if="
                                                        subject?.is_incomplete
                                                    "
                                                    class="text-amber-600"
                                                >
                                                    Incompleted
                                                </div>
                                                <div
                                                    v-else-if="subject?.grade"
                                                    class="text-green-600"
                                                >
                                                    Passed
                                                </div>
                                                <div
                                                    v-else-if="
                                                        subject.grade?.is_drop
                                                    "
                                                    class="text-red-600"
                                                >
                                                    Dropped
                                                </div>
                                                <div
                                                    v-else-if="
                                                        subject.grade?.is_failed
                                                    "
                                                    class="text-rose-600"
                                                >
                                                    Failed
                                                </div>
                                                <div
                                                    v-else-if="
                                                        subject.grade
                                                            ?.is_incomplete
                                                    "
                                                    class="text-amber-600"
                                                >
                                                    Incompleted
                                                </div>
                                                <div
                                                    v-else-if="
                                                        subject.grade?.is_active
                                                    "
                                                    class="text-green-600"
                                                >
                                                    Passed
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr
                                            v-if="item.subjects"
                                            class="border-t border-gray-200 font-medium"
                                        >
                                            <td class="px-3 py-2 rounded-l-xl">
                                                Semester Average
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                Total Units:
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                {{ item.totalUnit }}
                                            </td>
                                            <td
                                                class="px-3 py-2 text-right"
                                            ></td>
                                            <td
                                                class="px-3 py-2 rounded-r-xl"
                                            ></td>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </template>
                        <Divider type="dashed">
                            <span class="text-sm font-semibold"
                                >End of Grades</span
                            >
                        </Divider>
                        <div class="flex flex-col gap-3">
                            <div class="leading-none">
                                <label
                                    for="standing"
                                    class="text-sm font-semibold"
                                    >Standing</label
                                >
                                <Dropdown
                                    id="standing"
                                    v-model="standing"
                                    :options="standingOptions"
                                    placeholder="Select Scholar Standing"
                                    class="w-full !text-sm"
                                    :disabled="loading.approve || loading.reject"
                                />
                            </div>
                            <div class="leading-none">
                                <label
                                    for="remarks"
                                    class="text-sm font-semibold"
                                    >Remarks</label
                                >
                                <Textarea
                                    id="remarks"
                                    class="!text-sm"
                                    placeholder="Help the user understand why this request was rejected and what needs to be corrected."
                                    fluid
                                    rows="5"
                                    v-model="details.remarks"
                                />
                            </div>
                            <div class="flex flex-col gap-4">
                                <div class="flex justify-end">
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
                <div class="flex-5 flex items-center justify-center">
                    <div
                        v-if="!selectedFile"
                        class="flex flex-col items-center justify-center text-center p-8 rounded-xl border border-dashed border-gray-300 bg-gray-50 w-full max-w-md"
                    >
                        <!-- Icon -->
                        <div class="bg-white p-4 rounded-full shadow-sm mb-4">
                            <IconFileSearch class="w-10 h-10 text-gray-500" />
                        </div>

                        <!-- Title -->
                        <h3 class="text-lg font-semibold text-gray-700">
                            No File Selected
                        </h3>

                        <!-- Description -->
                        <p class="text-sm text-gray-500 mt-1">
                            Choose a document from the list to preview its
                            contents here.
                        </p>

                        <!-- Hint -->
                        <div
                            class="mt-4 flex items-center gap-2 text-xs text-gray-400"
                        >
                            <IconArrowLeft class="w-4 h-4" />
                            Select a file on the left panel
                        </div>
                    </div>
                    <div v-else>
                        <PDF src="/demo.pdf" />
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
    IconPaperclip,
    IconFile,
    IconFileOff,
    IconFileSearch,
    IconCircleCheckFilled,
    IconLoader2,
    IconCircleXFilled,
} from "@tabler/icons-vue";
import UploadInput from "../../Components/inputs/UploadInput.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import { ref, watch, onMounted } from "vue";
import { useForm, progress, usePage, router } from "@inertiajs/vue3";
import PDF from "pdf-vue3";
import { useToast } from "primevue";

const modelValue = defineModel("modelValue");
const page = usePage();
const loading = ref({
    reject: false,
    approve: false,
});
const toast = useToast();
const details = ref(null);
const remarks = ref(null);
const selectedFile = ref(null);
const standing = ref(null);
const standingOptions = [
    "GOOD STANDING",
    "CONTINUED",
    "CUP - Continued Under Probation",
    "CPA - Continued with Partial Allowance",
    "TERMINATED",
    "NO REPORT",
    "NON-COMPLIANCE",
    "GRADUATED",
];

const selectFile = (file) => {
    selectedFile.value = file;
};

const approveRequest = (decision) => {
    if (decision === "accept" && !standing.value) {
        toast.add({
            severity: "warn",
            summary: "Standing Required",
            detail: "Please select the scholar standing before accepting.",
            life: 3000,
        });
        return;
    }

    router.post(
        `/scholar-grade-request/${decision}`,
        {
            data: details.value.filter((item) => item.status === "submitted"),
            standing: standing.value,
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
            },

            onFinish: () => {
                if (decision === "reject") {
                    loading.value.reject = false;
                } else {
                    loading.value.approve = false;
                    modelValue.value = false;
                }
            },
        },
    );
};

onMounted(() => (details.value = page.props?.subjectRequest));
</script>
