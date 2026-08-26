<template>
    <Dialog
        v-model:visible="modelValue"
        modal
        :pt="{
            root: 'w-[99%] lg:w-[110rem] dark:!bg-gray-900 dark:!text-gray-100',
            header: 'border-b-1 border-gray-300 border-dashed dark:!border-gray-700 dark:!bg-gray-900 dark:!text-gray-100',
            content: '!p-0 dark:!bg-gray-900 dark:!text-gray-100',
            footer: 'dark:!bg-gray-900',
        }"
    >
        <template #header>
            <div
                class="bg-slate-100 px-4 py-2 shadow rounded-lg flex items-center gap-2 dark:bg-gray-800 dark:text-gray-100"
            >
                <IconId :size="18" :stroke-width="2" />
                <div class="uppercase font-medium text-sm">
                    Scholar Subjects & Grades Request
                </div>
            </div>
        </template>
        <template #default>
            <div class="w-full flex bg-white dark:bg-gray-900 dark:text-gray-100">
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
                                    class="text-xl font-bold text-gray-900 leading-5 dark:text-gray-100"
                                >
                                    {{ page.props?.details?.fullname }}
                                </div>

                                <div
                                    class="mt-1 flex items-center gap-1 text-sm text-gray-500 dark:text-gray-400"
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
                                    class="text-xs uppercase tracking-wider text-gray-400 dark:text-gray-500"
                                >
                                    Scholarship
                                </div>

                                <div class="font-semibold text-gray-800 dark:text-gray-100">
                                    {{ page.props?.details?.type?.name }}
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
                                        <div class="text-xs text-slate-500 dark:text-gray-400">
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
                                        <div class="text-xs text-slate-500 dark:text-gray-400">
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
                                                        isGradesProofDocument(file)
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
                                                        isCorDocument(file)
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
                                        <tr class="bg-gray-100 dark:bg-gray-800">
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
                                                'border-b hover:bg-gray-50 dark:hover:bg-gray-800/80',
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
                                            class="border-t border-gray-200 font-medium text-gray-700 dark:border-gray-700 dark:text-gray-300"
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
                                                    class="w-[30rem] p-1 flex flex-col gap-4 dark:bg-gray-900 dark:text-gray-100"
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
                                                :pt="{
                                                    root: 'grade-approve-popover',
                                                }"
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
                                                                class="text-sm font-semibold text-gray-800 dark:text-gray-100"
                                                            >
                                                                Verified Correct
                                                            </h3>
                                                            <p
                                                                class="text-xs text-gray-500 mt-1 dark:text-gray-400"
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
                                                    <div class="border-t dark:border-gray-700"></div>

                                                    <!-- Form -->
                                                    <div
                                                        class="flex flex-col gap-2"
                                                    >
                                                        <label
                                                            class="text-xs font-semibold text-gray-600 leading-0 dark:text-gray-300"
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
                                                        <div
                                                            v-if="submittedTermRecommendation"
                                                            class="rounded-xl border border-blue-100 bg-blue-50 p-3 text-xs text-blue-900 dark:border-blue-800/70 dark:bg-gray-800 dark:text-gray-100"
                                                        >
                                                            <div
                                                                class="flex items-center justify-between gap-3"
                                                            >
                                                                <span
                                                                    class="font-semibold"
                                                                >
                                                                    Recommended:
                                                                    {{
                                                                        submittedTermRecommendation.recommended_status
                                                                    }}
                                                                </span>
                                                                <span
                                                                    class="rounded-full bg-white px-2 py-0.5 font-medium text-blue-700 whitespace-nowrap dark:bg-blue-600 dark:text-white"
                                                                >
                                                                    {{
                                                                        submittedTermRecommendation.policy_group
                                                                    }}
                                                                </span>
                                                            </div>
                                                            <div
                                                                v-if="submittedTerm?.scholarshipEvaluationTerm"
                                                                class="mt-1 text-blue-700 dark:text-gray-300"
                                                            >
                                                                Evaluated term:
                                                                {{
                                                                    submittedTerm.scholarshipEvaluationTerm.academicYear
                                                                }}
                                                                /
                                                                {{
                                                                    submittedTerm.scholarshipEvaluationTerm.term
                                                                }}
                                                            </div>
                                                            <ul
                                                                v-if="visibleRecommendationReasons.length"
                                                                class="mt-2 list-disc space-y-1 pl-4 text-blue-800 dark:text-gray-300"
                                                            >
                                                                <li
                                                                    v-for="(
                                                                        reason,
                                                                        reasonIndex
                                                                    ) in visibleRecommendationReasons"
                                                                    :key="
                                                                        reasonIndex
                                                                    "
                                                                >
                                                                    {{ reason }}
                                                                </li>
                                                            </ul>
                                                        </div>
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
                        class="flex flex-col items-center justify-center text-center p-8 rounded-xl border border-dashed border-gray-300 bg-gray-50 w-full max-w-md dark:border-gray-700 dark:bg-gray-900"
                    >
                        <!-- Icon -->
                        <div class="bg-white p-4 rounded-full shadow-sm mb-4 dark:bg-gray-800">
                            <IconFileSearch class="w-10 h-10 text-gray-500" />
                        </div>

                        <!-- Title -->
                        <h3 class="text-lg font-semibold text-gray-700 dark:text-gray-100">
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
                            class="flex items-center justify-between px-5 pb-4 border-b border-gray-200 dark:border-gray-700"
                        >
                            <div class="flex items-center gap-3">
                                <div
                                    class="w-10 h-10 rounded-xl bg-red-50 text-red-600 flex items-center justify-center dark:bg-red-950/40 dark:text-red-300"
                                >
                                    <IconFileTypePdf
                                        v-if="
                                            selectedFilePath
                                                ?.toLowerCase()
                                                .endsWith('.pdf')
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
                                            selectedFilePath
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
                                        scholarPortalFileUrl(selectedFilePath)
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
                                scholarPortalFileUrl(selectedFilePath)
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
    IconFileTypePdf,
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
import { computed, ref, watch, onMounted } from "vue";
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
const selectedFilePath = computed(
    () => selectedFile.value?.file_path ?? selectedFile.value?.path ?? null,
);
const scholarPortalFileUrl = (path) => {
    if (!path) return null;

    if (/^https?:\/\//i.test(path)) {
        return path;
    }

    return `${page.props?.filePreview?.scholarPortalBaseUrl ?? ""}/${String(path).replace(/^\/+/, "")}`;
};

const normalizedDocumentType = (file) =>
    String(file?.document_type ?? file?.type ?? "")
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, "_")
        .replace(/^_+|_+$/g, "");

const isCorDocument = (file) => normalizedDocumentType(file) === "cor";

const isGradesProofDocument = (file) =>
    ["cog", "grades_proof", "proof_of_grades"].includes(
        normalizedDocumentType(file),
    );

const normalizeStatus = (status) =>
    String(status ?? "")
        .toUpperCase()
        .replace(/[^A-Z0-9]+/g, " ")
        .trim()
        .replace(/\s+/g, " ");

const submittedTerm = computed(() =>
    details.value?.find((item) => item.status === "submitted"),
);

const submittedTermRecommendation = computed(
    () => submittedTerm.value?.scholarshipRecommendation ?? null,
);

const visibleRecommendationReasons = computed(() =>
    (submittedTermRecommendation.value?.reasons ?? []).filter(
        (reason) =>
            !String(reason ?? "").startsWith(
                "Policy group was derived from submitted curriculum subjects:",
            ),
    ),
);

const recommendedStandingOption = () => {
    const recommendation = submittedTermRecommendation.value;
    const options = page.props?.standingOptions ?? [];
    const recommended = normalizeStatus(
        recommendation?.recommended_status_normalized ??
            recommendation?.recommended_status,
    );

    if (!recommended) return null;

    return (
        options.find((option) => {
            const optionName = normalizeStatus(option?.name ?? option?.id);

            return (
                optionName === recommended ||
                (recommended.includes("PROBATION") &&
                    optionName.includes("PROBATION")) ||
                (recommended.includes("PARTIAL") &&
                    optionName.includes("PARTIAL")) ||
                (recommended.includes("TERMINATED") &&
                    optionName.includes("TERMINATED")) ||
                (recommended.includes("GOOD STANDING") &&
                    optionName.includes("GOOD STANDING"))
            );
        }) ?? null
    );
};

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
        dropped: "border-slate-200 bg-slate-100/80 dark:border-gray-600 dark:bg-gray-800",
        incomplete: "border-amber-200 bg-amber-50 dark:border-amber-900/60 dark:bg-amber-950/30",
        failed: "border-red-200 bg-red-50 dark:border-red-900/60 dark:bg-red-950/30",
    }[status] ?? "border-slate-100 dark:border-gray-700";
};

const subjectGradeClass = (subject) => {
    const status = subjectStatus(subject);

    return {
        dropped: "bg-slate-200 text-slate-700 dark:bg-gray-700 dark:text-gray-200",
        incomplete: "bg-amber-100 text-amber-800 dark:bg-amber-900/60 dark:text-amber-100",
        failed: "bg-red-100 text-red-700 dark:bg-red-900/60 dark:text-red-100",
    }[status] ?? "bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-gray-200";
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
    if (subject?.is_failed || subject?.grade?.is_failed) return "text-rose-600 dark:text-rose-300";
    if (subject?.is_incomplete || subject?.grade?.is_incomplete) {
        return "text-amber-600 dark:text-amber-300";
    }
    if (subject?.grade?.id || subject?.grade?.grade || subject?.grade?.is_active) {
        return "text-green-600 dark:text-green-300";
    }

    return "text-slate-400 dark:text-gray-500";
};

const selectFile = (file) => {
    selectedFile.value = file;
};
const toggleOpReject = (event) => {
    opReject.value.toggle(event);
};
const toggleOpAccept = (event) => {
    selectedScholarshipStatus.value = recommendedStandingOption();
    opApprove.value.toggle(event);
};

const approveRequest = (decision) => {
    if (decision === "accept" && !selectedScholarshipStatus.value) {
        toast.add({
            severity: "warn",
            summary: "Scholarship Status Required",
            detail: "Please select the scholarship status.",
            life: 3000,
        });
        return;
    }
    if (decision === "reject" && !submittedTerm.value?.remarks) {
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
