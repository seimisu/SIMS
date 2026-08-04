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
                <IconId :size="18" :stroke-width="2" />
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
                    <div class="flex items-center justify-between py-1">
                        <!-- Left -->
                        <div class="flex items-center gap-3">
                            <Avatar
                                class="!bg-blue-100 !text-blue-600 !rounded-2xl shadow border border-blue-300"
                                size="large"
                            >
                                <IconUser :size="22" />
                            </Avatar>

                            <div>
                                <div
                                    class="text-xl font-bold text-gray-900 leading-5"
                                >
                                    {{ page.props?.details?.fullname }}
                                </div>

                                <div
                                    class="mt-1 flex items-center gap-1 text-sm text-gray-500"
                                >
                                    <IconHash :size="14" />
                                    {{ page.props?.details?.spas_no }}
                                </div>
                            </div>
                        </div>

                        <!-- Right -->
                        <div class="flex items-center gap-8">
                            <div class="text-right">
                                <div
                                    class="text-xs uppercase tracking-wider text-gray-400"
                                >
                                    Scholarship
                                </div>

                                <div class="font-semibold text-gray-800">
                                    {{ page.props?.details?.type?.name }}
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
                                    {{ page.props?.details?.program?.name }}
                                </div>
                            </div>
                        </div>
                    </div>

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
                                                        item.status ==
                                                            'approved' &&
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
                                                        item.status ==
                                                            'submitted' &&
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
                                            :class="[
                                                'border-b hover:bg-gray-50',
                                                subjectRowClass(subject),
                                            ]"
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
                                                <p
                                                    v-if="subject.grade?.grade"
                                                    :class="[
                                                        'inline-flex rounded px-2 py-0.5 text-xs font-semibold',
                                                        subjectGradeClass(subject),
                                                    ]"
                                                >
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
                                                <span
                                                    :class="subjectRemarksClass(subject)"
                                                >
                                                    {{ subjectRemarks(subject) }}
                                                </span>
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr
                                            v-if="item.subjects"
                                            class="border-t border-gray-200 font-medium text-gray-700"
                                        >
                                            <td
                                                class="px-3 py-2 rounded-l-xl"
                                                colspan="2"
                                            >
                                                Semester Average
                                            </td>
                                            <td class="px-3 py-2 text-right">
                                                {{
                                                    item.summary?.units ??
                                                    item.totalUnit ??
                                                    0
                                                }}
                                            </td>
                                            <td
                                                class="px-3 py-2 text-right"
                                            ></td>
                                            <td
                                                class="px-3 py-2 text-right"
                                            >
                                                {{
                                                    item.summary?.average ??
                                                    "-"
                                                }}
                                            </td>
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
                            <div class="flex flex-col gap-4">
                                <div class="flex justify-end">
                                    <div class="flex items-center mb-3 gap-3">
                                        <div>
                                            <Button
                                                size="small"
                                                class="!text-xs !rounded-xl"
                                                outlined
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
                                                        />

                                                        <p>For Revisions</p>
                                                    </div>
                                                </template>
                                            </Button>
                                            <Popover ref="opReject">
                                                <div
                                                    class="w-[26rem] p-1 flex flex-col gap-4"
                                                >
                                                    <!-- Header -->
                                                    <div
                                                        class="flex items-start justify-between"
                                                    >
                                                        <div>
                                                            <h3
                                                                class="text-sm font-semibold text-gray-800"
                                                            >
                                                                For Revisions
                                                                (w/ Deficiency)
                                                            </h3>
                                                            <p
                                                                class="text-xs text-gray-500 mt-1"
                                                            >
                                                                Provide
                                                                deficiency
                                                                remarks.
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <!-- Divider -->
                                                    <div class="border-t"></div>

                                                    <!-- Form -->
                                                    <div
                                                        class="flex flex-col gap-2"
                                                    >
                                                        <label
                                                            class="text-xs font-semibold text-gray-600 leading-0"
                                                        >
                                                            Remarks
                                                            <span
                                                                class="text-red-500"
                                                                >*</span
                                                            >
                                                        </label>

                                                        <Textarea
                                                            v-model="
                                                                details.find(
                                                                    (item) =>
                                                                        item.status ===
                                                                        'submitted',
                                                                ).remarks
                                                            "
                                                            rows="4"
                                                            placeholder="Enter your reason here..."
                                                            class="w-full !text-sm"
                                                            size="small"
                                                        />
                                                    </div>

                                                    <!-- Actions -->
                                                    <div
                                                        class="flex justify-end gap-2 pt-2"
                                                    >
                                                        <DefaultButton
                                                            label="Cancel"
                                                            rounded
                                                            @click="
                                                                toggleOpReject
                                                            "
                                                            severity="secondary"
                                                            outlined
                                                            size="small"
                                                            class-name="!px-4"
                                                        />

                                                        <DefaultButton
                                                            label="For Revisions"
                                                            severity="danger"
                                                            @click="
                                                                approveRequest(
                                                                    'reject',
                                                                )
                                                            "
                                                            :loading="
                                                                loading.reject
                                                            "
                                                            rounded
                                                            size="small"
                                                            class-name="!px-5"
                                                        />
                                                    </div>
                                                </div>
                                            </Popover>
                                        </div>
                                        <div>
                                            <Button
                                                size="small"
                                                class="!text-xs !rounded-xl"
                                                raised
                                                @click="toggleOpAccept"
                                            >
                                                <template #default>
                                                    <div
                                                        class="flex gap-1 items-center"
                                                    >
                                                        <IconCircleCheckFilled
                                                            :stroke-width="1.5"
                                                            :size="20"
                                                        />

                                                        <p>Verified Correct</p>
                                                    </div>
                                                </template>
                                            </Button>
                                            <Popover
                                                ref="opApprove"
                                                :dismissable="false"
                                            >
                                                <div
                                                    class="w-[26rem] p-1 flex flex-col gap-4"
                                                >
                                                    <!-- Header -->
                                                    <div
                                                        class="flex items-start justify-between"
                                                    >
                                                        <div>
                                                            <h3
                                                                class="text-sm font-semibold text-gray-800"
                                                            >
                                                                Verified Correct
                                                            </h3>
                                                            <p
                                                                class="text-xs text-gray-500 mt-1"
                                                            >
                                                                Select the
                                                                scholarship
                                                                status before
                                                                approving the
                                                                document.
                                                            </p>
                                                        </div>
                                                    </div>

                                                    <!-- Divider -->
                                                    <div class="border-t"></div>

                                                    <!-- Form -->
                                                    <div
                                                        class="flex flex-col gap-2"
                                                    >
                                                        <label
                                                            class="text-xs font-semibold text-gray-600 leading-0"
                                                        >
                                                            Scholarship Status
                                                            <span
                                                                class="text-red-500"
                                                                >*</span
                                                            >
                                                        </label>

                                                        <SelectInput
                                                            v-model="
                                                                selectedScholarshipStatus
                                                            "
                                                            :options="
                                                                page.props
                                                                    ?.standingOptions ??
                                                                []
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
                                                                toggleOpAccept
                                                            "
                                                            outlined
                                                            size="small"
                                                            class-name="!px-4"
                                                        />

                                                        <DefaultButton
                                                            label="Verified Correct"
                                                            rounded
                                                            :loading="
                                                                loading.approve
                                                            "
                                                            @click="
                                                                approveRequest(
                                                                    'accept',
                                                                )
                                                            "
                                                            size="small"
                                                            class-name="!px-5"
                                                        />
                                                    </div>
                                                </div>
                                            </Popover>
                                        </div>
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
                    <div v-else class="w-full flex flex-col gap-3 p-5">
                        <div
                            class="flex items-center justify-between px-5 pb-4 border-b border-gray-200"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center"
                                >
                                    <IconFileTypePdf
                                        v-if="
                                            selectedRow?.file?.endsWith('.pdf')
                                        "
                                        :size="22"
                                    />

                                    <IconFile v-else :size="22" />
                                </div>

                                <div>
                                    <h3 class="font-semibold text-gray-800">
                                        Document Preview
                                    </h3>

                                    <p
                                        class="text-sm text-gray-500 truncate max-w-[500px]"
                                    >
                                        {{
                                            selectedFile?.file_path
                                                ?.split("/")
                                                .pop()
                                        }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <Button
                                    icon="pi pi-external-link"
                                    severity="secondary"
                                    text
                                    size="small"
                                    rounded
                                    as="a"
                                    target="_blank"
                                    :href="
                                        'http://172.16.8.98:85/' +
                                        selectedFile.file_path
                                    "
                                    v-tooltip.top="'Open in new tab'"
                                />
                                <Button
                                    icon="pi pi-times-circle"
                                    severity="secondary"
                                    text
                                    size="small"
                                    rounded
                                    @click="selectedFile = null"
                                    v-tooltip.top="'Close File'"
                                />
                            </div>
                        </div>

                        <iframe
                            :src="
                                'http://172.16.8.98:85/' + selectedFile.file_path
                            "
                            class="w-full h-[700px] rounded-xl border"
                        >
                        </iframe>
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
    IconArrowLeft,
    IconCircleXFilled,
    IconId,
    IconHash,
} from "@tabler/icons-vue";
import UploadInput from "../../Components/inputs/UploadInput.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import { ref, watch, onMounted } from "vue";
import { useForm, progress, usePage, router } from "@inertiajs/vue3";
import { useToast } from "primevue";
import VuePdfEmbed from "vue-pdf-embed";
import SelectInput from "../../Components/inputs/SelectInput.vue";
const modelValue = defineModel("modelValue");
const page = usePage();
const loading = ref({
    reject: false,
    approve: false,
});
const toast = useToast();
const opReject = ref(null);
const opApprove = ref(null);
const details = ref(null);
const remarks = ref(null);
const selectedFile = ref(null);
const selectedScholarshipStatus = ref(null);

const subjectStatus = (subject) => {
    if (subject?.is_drop || subject?.grade?.is_drop) return "dropped";
    if (subject?.is_incomplete || subject?.grade?.is_incomplete) {
        return "incomplete";
    }
    if (subject?.is_failed || subject?.grade?.is_failed) return "failed";

    return null;
};

const subjectRowClass = (subject) => {
    const status = subjectStatus(subject);

    return {
        dropped: "border-slate-200 bg-slate-100/80",
        incomplete: "border-amber-200 bg-amber-50",
        failed: "border-red-200 bg-red-50",
    }[status] ?? "border-slate-100";
};

const subjectGradeClass = (subject) => {
    const status = subjectStatus(subject);

    return {
        dropped: "bg-slate-200 text-slate-700",
        incomplete: "bg-amber-100 text-amber-800",
        failed: "bg-red-100 text-red-700",
    }[status] ?? "bg-slate-100 text-slate-700";
};

const subjectRemarks = (subject) => {
    if (subject?.is_drop || subject?.grade?.is_drop) return "Dropped";
    if (subject?.is_failed || subject?.grade?.is_failed) return "Failed";
    if (subject?.is_incomplete || subject?.grade?.is_incomplete) {
        return "Incompleted";
    }
    if (subject?.grade?.id || subject?.grade?.grade || subject?.grade?.is_active) {
        return "Passed";
    }

    return "-";
};

const subjectRemarksClass = (subject) => {
    if (subject?.is_drop || subject?.grade?.is_drop) return "text-slate-500";
    if (subject?.is_failed || subject?.grade?.is_failed) return "text-rose-600";
    if (subject?.is_incomplete || subject?.grade?.is_incomplete) {
        return "text-amber-600";
    }
    if (subject?.grade?.id || subject?.grade?.grade || subject?.grade?.is_active) {
        return "text-green-600";
    }

    return "text-slate-400";
};

const selectFile = (file) => {
    selectedFile.value = file;
};
const toggleOpReject = (event) => {
    opReject.value.toggle(event);
};
const toggleOpAccept = (event) => {
    selectedScholarshipStatus.value = null;
    opApprove.value.toggle(event);
};

const approveRequest = (decision) => {
    const submittedTerm = details.value.find(
        (item) => item.status === "submitted",
    );

    if (decision === "accept" && !selectedScholarshipStatus.value) {
        toast.add({
            severity: "warn",
            summary: "Scholarship Status Required",
            detail: "Please select the scholarship status.",
            life: 3000,
        });
        return;
    }
    if (decision === "reject" && !submittedTerm?.remarks) {
        toast.add({
            severity: "warn",
            summary: "Remarks Required",
            detail: "Please enter the deficiency remarks.",
            life: 3000,
        });
        return;
    }

    router.post(
        `/scholar-grade-request/${decision}`,
        {
            data: details.value
                .filter((item) => item.status === "submitted")
                .map((item) => ({
                    ...item,
                    ...(decision === "accept"
                        ? {
                              scholarshipStatus:
                                  selectedScholarshipStatus.value,
                          }
                        : {}),
                })),
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
                }
                modelValue.value = false;
            },
        },
    );
};

onMounted(() => (details.value = page.props?.subjectRequest));
</script>
