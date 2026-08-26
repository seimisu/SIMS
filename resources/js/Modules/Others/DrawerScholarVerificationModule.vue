<template>
    <Drawer
        v-model:visible="modelValue"
        position="full"
        :pt="{
            root: 'dark:!bg-gray-900 dark:!text-gray-100',
            header: 'border-b-1 border-gray-300 border-dashed dark:!border-gray-700 dark:!bg-gray-900 dark:!text-gray-100',
            content: '!p-3 bg-slate-50 dark:!bg-gray-900 dark:!text-gray-100',
            footer: 'border-t-1 border-gray-300 border-dashed bg-white dark:!border-gray-700 dark:!bg-gray-900',
        }"
    >
        <template #header>
            <div class="flex min-w-0 items-center gap-3">
                <IconFileSpreadsheet :size="18" class="shrink-0 text-slate-500 dark:text-gray-400" />
                <div class="min-w-0 truncate text-sm font-semibold uppercase text-slate-700 dark:text-gray-100">
                    Scholar Import Review
                </div>
                <div class="shrink-0 rounded border border-amber-300 bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-700 dark:border-amber-700 dark:bg-amber-900/30 dark:text-amber-200">
                    {{ page.props?.validationStatus.completed ?? 0 }} / {{ page.props?.validationStatus.total ?? 0 }} validated
                </div>
            </div>
        </template>

        <template #default>
            <div class="flex h-full min-h-0 flex-col gap-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-col">
                        <div class="text-sm font-semibold text-slate-700 dark:text-gray-100">
                            Imported Scholar Rows
                        </div>
                        <div class="text-xs text-slate-500 dark:text-gray-400">
                            This is a validation preview. Fix rows with conflicts in the Excel file, then upload the corrected file again.
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-gray-400">
                        <span>{{ scholar.length }} row(s)</span>
                        <span>{{ validRows }} valid</span>
                        <span>{{ needsReviewRows }} need review</span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 border-y border-slate-200 bg-white px-2 py-1.5 text-xs text-slate-600 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    <span class="font-semibold text-slate-700 dark:text-gray-100">Import quality</span>
                    <span>Valid: <b>{{ qualityCounts.valid }}</b></span>
                    <span>Needs correction: <b>{{ qualityCounts.needsCorrection }}</b></span>
                    <span>Duplicate: <b>{{ qualityCounts.duplicate }}</b></span>
                    <span>Missing required: <b>{{ qualityCounts.missingRequired }}</b></span>
                </div>

                <div class="flex-1 overflow-auto rounded-lg border border-slate-200 bg-white dark:border-gray-700 dark:bg-gray-900">
                    <table class="min-w-[2700px] w-full text-xs text-slate-700 dark:text-gray-200">
                        <thead class="sticky top-0 z-10 bg-slate-50 dark:bg-gray-800 dark:text-gray-100">
                            <tr>
                                <th v-for="heading in tableHeadings" :key="heading" class="border border-slate-300 px-2 py-2 text-left dark:border-gray-700">
                                    {{ heading }}
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(item, index) in scholar"
                                :key="item.id"
                                :class="item.verified_by ? 'bg-emerald-50/50 dark:bg-emerald-950/20' : 'dark:bg-gray-900'"
                            >
                                <td class="border border-slate-300 px-2 py-1 font-semibold dark:border-gray-700">{{ index + 1 }}</td>
                                <td class="border border-slate-300 px-2 py-1 dark:border-gray-700">
                                    <span :class="rowStatusClass(item.row_status)">
                                        <span :class="rowStatusDotClass(item.row_status)" />
                                        {{ rowStatusLabel(item) }}
                                    </span>
                                </td>
                                <td class="border border-slate-300 px-2 py-1 min-w-80 dark:border-gray-700">
                                    <div
                                        v-if="item.row_status !== 'valid'"
                                        class="whitespace-normal text-[11px] leading-4 text-slate-700 dark:text-gray-200"
                                    >
                                        {{ conflictDetail(item) }}
                                    </div>
                                    <span v-else class="text-slate-500 dark:text-gray-400">Ready to import</span>
                                </td>
                                <td class="border border-slate-300 px-2 py-1 font-medium dark:border-gray-700">{{ item.spas_no }}</td>
                                <td v-for="field in rowFields" :key="field" :class="['border border-slate-300 px-2 py-1 dark:border-gray-700', field === 'address' ? 'min-w-44' : '']">{{ item[field] }}</td>
                                <td class="border border-slate-300 px-2 py-1 min-w-64 dark:border-gray-700">
                                    <div>{{ item.matchedCourse?.name || "-" }}</div>
                                    <div v-if="item.matchedCourse?.name && item.matchedCourse?.name !== item.course" class="text-[10px] text-slate-400 dark:text-gray-500">
                                        Excel: {{ item.course }}
                                    </div>
                                </td>
                                <td class="border border-slate-300 px-2 py-1 min-w-44 dark:border-gray-700">
                                    {{ item.matchedCurriculum?.name || "-" }}
                                </td>
                                <td class="border border-slate-300 px-2 py-1 min-w-72 dark:border-gray-700">
                                    <div>{{ item.matchedSchool?.name || item.matchedCourse?.campus || "-" }}</div>
                                    <div v-if="(item.matchedSchool?.name || item.matchedCourse?.campus) && (item.matchedSchool?.name || item.matchedCourse?.campus) !== item.school" class="text-[10px] text-slate-400 dark:text-gray-500">
                                        Excel: {{ item.school }}
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!scholar.length">
                                <td colspan="26" class="border border-slate-300 px-2 py-8 text-center text-slate-500 dark:border-gray-700 dark:text-gray-400">
                                    No imported rows found.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </template>

        <template #footer>
            <div class="w-full flex items-center justify-between gap-4">
                <div class="min-w-[220px]">
                    <div class="mb-1 flex items-center justify-between">
                        <span class="text-xs text-gray-500 font-medium dark:text-gray-400">Validation Progress</span>
                        <span class="text-xs font-semibold dark:text-gray-100">
                            {{ page.props?.validationStatus.completed ?? 0 }} /
                            {{ page.props?.validationStatus.total ?? 0 }}
                        </span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-gray-100 dark:bg-gray-700">
                        <div
                            class="h-full rounded-full bg-slate-700 transition-all duration-300 dark:bg-blue-400"
                            :style="{ width: `${validationPercent}%` }"
                        />
                    </div>
                </div>
                <DefaultButton
                    size="small"
                    raised
                    label="Import Scholars"
                    class="!rounded-lg !px-8"
                    :disabled="!canPublish"
                    @click="moveProd"
                />
            </div>
        </template>
    </Drawer>

    <DefaultConfirmDialog group="templating" />
</template>

<script setup>
import { router, usePage } from "@inertiajs/vue3";
import { IconFileSpreadsheet } from "@tabler/icons-vue";
import { computed } from "vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import { route } from "ziggy-js";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";
import DefaultConfirmDialog from "../../Components/dialogs/DefaultConfirmDialog.vue";

const toast = useToast();
const confirm = useConfirm();
const props = defineProps({
    id: String,
});
const modelValue = defineModel("modelValue");
const page = usePage();
const scholar = computed(() => page.props?.selected ?? []);
const tableHeadings = [
    "Row",
    "Review",
    "Issue",
    "SPAS No",
    "Status",
    "Type",
    "Subprogram",
    "First Name",
    "Last Name",
    "Middle Name",
    "Suffix",
    "Sex",
    "Email",
    "Contact",
    "Birthdate",
    "Birthplace",
    "Civil Status",
    "Address Line",
    "Barangay",
    "Municipality",
    "Province",
    "Region",
    "Year Awarded",
    "Database Course",
    "Database Curriculum",
    "Database School",
];
const rowFields = [
    "status",
    "scholarship_type",
    "scholarship_subprogram",
    "fname",
    "lname",
    "mname",
    "suffix",
    "sex",
    "email",
    "contact_no",
    "birthdate",
    "birthplace",
    "civil_status",
    "address",
    "barangay",
    "municipality",
    "province",
    "region",
    "year_awarded",
];

const validRows = computed(() => scholar.value.filter((item) => item.row_status === "valid").length);
const needsReviewRows = computed(() => scholar.value.length - validRows.value);
const canPublish = computed(() => scholar.value.length > 0 && needsReviewRows.value === 0);
const qualityCounts = computed(() => ({
    valid: scholar.value.filter((item) => item.row_status === "valid").length,
    needsCorrection: scholar.value.filter((item) => item.row_status === "needs_correction").length,
    duplicate: scholar.value.filter((item) => item.row_status === "duplicate").length,
    missingRequired: scholar.value.filter((item) => item.row_status === "missing_required").length,
}));
const validationPercent = computed(() => {
    const total = page.props?.validationStatus.total ?? 0;
    const completed = page.props?.validationStatus.completed ?? 0;
    return total ? (completed / total) * 100 : 0;
});

const conflictSummary = (item) => {
    const firstError = conflictDetail(item);

    if (firstError.includes("School")) return "School not found";
    if (firstError.includes("Course")) return "Course not found for school";
    if (firstError.includes("Curriculum")) return "Curriculum not found";
    if (firstError.includes("Region")) return "Region not found";
    if (firstError.includes("Province")) return "Province not found";
    if (firstError.includes("Municipality")) return "Municipality not found";
    if (firstError.includes("Barangay")) return "Barangay not found";
    if (firstError.includes("Location")) return "Location not found";
    if (firstError.includes("Address")) return "Location not found";
    if (firstError.includes("SPAS")) return "Duplicate SPAS No";
    if (firstError.includes("Email")) return "Email conflict";
    if (firstError.includes("Status")) return "Invalid status";
    if (firstError.includes("Scholarship type")) return "Invalid scholarship type";
    if (firstError.includes("Scholarship subprogram")) return "Invalid subprogram";
    if (firstError.includes("Birthdate")) return "Invalid birthdate";
    if (firstError.includes("required")) return "Missing required data";

    return firstError || "Conflict found";
};

const conflictDetail = (item) => {
    const firstError = item.validation_errors?.[0] ?? "";
    if (firstError) return firstError.replace(/^Row\s+\d+:\s*/i, "");

    if (item.row_status === "duplicate") return "Duplicate SPAS No or email found in the database.";
    if (item.row_status === "missing_required") return "One or more required Excel fields are blank.";
    if (item.row_status === "valid") return "";
    if (!item.matchedSchool) return `School '${item.school || "-"}' was not found in the database.`;
    if (!item.matchedCourse) return `Course '${item.course || "-"}' was not found for school '${item.school || "-"}'.`;
    if (!item.matchedCurriculum) return `Curriculum was not found for course '${item.matchedCourse?.name || item.course || "-"}' at school '${item.matchedCourse?.campus || item.matchedSchool?.name || item.school || "-"}'.`;
    if (!item.matchedAddress) {
        return `Location was not found using barangay '${item.barangay || "-"}', municipality '${item.municipality || "-"}', province '${item.province || "-"}', region '${item.region || "-"}'. Address Line is free text.`;
    }

    return "The row has a conflict. Fix the Excel value and upload the file again.";
};

const rowStatusLabel = (item) =>
    item.row_status === "needs_correction"
        ? conflictSummary(item)
        : ({
        valid: "Valid",
        duplicate: "Duplicate",
        missing_required: "Missing Required",
    })[item.row_status] || "Conflict found";

const rowStatusClass = (status) =>
    [
        "inline-flex items-center gap-1 whitespace-nowrap rounded border px-2 py-0.5 text-[11px] font-semibold",
        {
            valid: "border-emerald-200 bg-emerald-50 text-emerald-700 dark:border-emerald-800 dark:bg-emerald-950/30 dark:text-emerald-200",
            needs_correction: "border-amber-200 bg-amber-50 text-amber-700 dark:border-amber-800 dark:bg-amber-950/30 dark:text-amber-200",
            duplicate: "border-rose-200 bg-rose-50 text-rose-700 dark:border-rose-800 dark:bg-rose-950/30 dark:text-rose-200",
            missing_required: "border-red-200 bg-red-50 text-red-700 dark:border-red-800 dark:bg-red-950/30 dark:text-red-200",
        }[status] || "border-slate-200 bg-white text-slate-700 dark:border-gray-700 dark:bg-gray-800 dark:text-gray-200",
    ];

const rowStatusDotClass = (status) =>
    [
        "h-1.5 w-1.5 rounded-full",
        {
            valid: "bg-emerald-500",
            needs_correction: "bg-amber-500",
            duplicate: "bg-rose-500",
            missing_required: "bg-red-500",
        }[status] || "bg-amber-500",
    ];

const moveProd = () => {
    if (!canPublish.value) {
        toast.add({
            severity: "warn",
            summary: "Import blocked",
            detail: "Only files with 100% valid rows can be imported.",
            life: 3000,
        });
        return;
    }

    confirm.require({
        group: "templating",
        message: `Import this complete batch to scholar records? ${page.props?.validationStatus.completed ?? 0} out of ${page.props?.validationStatus.total ?? 0} rows are valid.`,
        header: "Import Scholars",
        icon: "pi pi-exclamation-triangle",
        severity: "info",
        rejectLabel: "Cancel",
        rejectSeverity: "secondary",
        acceptLabel: "Import",
        acceptSeverity: "info",
        accept: () => {
            router.post(
                route("review.publish", { id: props.id }),
                {},
                {
                    onSuccess: () => {
                        toast.add({
                            severity: page.props?.flash?.status,
                            summary: page.props?.flash?.title,
                            detail: page.props?.flash?.message,
                            life: 3000,
                        });
                    },
                },
            );
        },
    });
};

</script>
