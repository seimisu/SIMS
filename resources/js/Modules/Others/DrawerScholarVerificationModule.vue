<template>
    <Drawer
        v-model:visible="modelValue"
        position="full"
        :pt="{
            header: 'border-b-1 border-gray-300 border-dashed',
            content: '!p-3 bg-slate-50',
            footer: 'border-t-1 border-gray-300 border-dashed bg-white',
        }"
    >
        <template #header>
            <div class="flex min-w-0 items-center gap-3">
                <IconFileSpreadsheet :size="18" class="shrink-0 text-slate-500" />
                <div class="min-w-0 truncate text-sm font-semibold uppercase text-slate-700">
                    Scholar Import Review
                </div>
                <div class="shrink-0 rounded border border-amber-300 bg-amber-50 px-2 py-1 text-[11px] font-semibold text-amber-700">
                    {{ page.props?.validationStatus.completed ?? 0 }} / {{ page.props?.validationStatus.total ?? 0 }} validated
                </div>
            </div>
        </template>

        <template #default>
            <div class="flex h-full min-h-0 flex-col gap-2">
                <div class="flex flex-wrap items-center justify-between gap-2">
                    <div class="flex flex-col">
                        <div class="text-sm font-semibold text-slate-700">
                            Imported Scholar Rows
                        </div>
                        <div class="text-xs text-slate-500">
                            This is a validation preview. Fix rows with conflicts in the Excel file, then upload the corrected file again.
                        </div>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs text-slate-500">
                        <span>{{ scholar.length }} row(s)</span>
                        <span>{{ validRows }} valid</span>
                        <span>{{ needsReviewRows }} need review</span>
                    </div>
                </div>

                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 border-y bg-white px-2 py-1.5 text-xs text-slate-600">
                    <span class="font-semibold text-slate-700">Import quality</span>
                    <span>Valid: <b>{{ qualityCounts.valid }}</b></span>
                    <span>Needs correction: <b>{{ qualityCounts.needsCorrection }}</b></span>
                    <span>Duplicate: <b>{{ qualityCounts.duplicate }}</b></span>
                    <span>Missing required: <b>{{ qualityCounts.missingRequired }}</b></span>
                </div>

                <div class="flex-1 overflow-auto rounded-lg border bg-white">
                    <table class="min-w-[2700px] w-full text-xs text-slate-700">
                        <thead class="sticky top-0 z-10 bg-slate-50">
                            <tr>
                                <th class="border px-2 py-2 text-left">Row</th>
                                <th class="border px-2 py-2 text-left">Review</th>
                                <th class="border px-2 py-2 text-left">Issue</th>
                                <th class="border px-2 py-2 text-left">SPAS No</th>
                                <th class="border px-2 py-2 text-left">Status</th>
                                <th class="border px-2 py-2 text-left">Type</th>
                                <th class="border px-2 py-2 text-left">Subprogram</th>
                                <th class="border px-2 py-2 text-left">First Name</th>
                                <th class="border px-2 py-2 text-left">Last Name</th>
                                <th class="border px-2 py-2 text-left">Middle Name</th>
                                <th class="border px-2 py-2 text-left">Suffix</th>
                                <th class="border px-2 py-2 text-left">Sex</th>
                                <th class="border px-2 py-2 text-left">Email</th>
                                <th class="border px-2 py-2 text-left">Contact</th>
                                <th class="border px-2 py-2 text-left">Birthdate</th>
                                <th class="border px-2 py-2 text-left">Birthplace</th>
                                <th class="border px-2 py-2 text-left">Civil Status</th>
                                <th class="border px-2 py-2 text-left">Address Line</th>
                                <th class="border px-2 py-2 text-left">Barangay</th>
                                <th class="border px-2 py-2 text-left">Municipality</th>
                                <th class="border px-2 py-2 text-left">Province</th>
                                <th class="border px-2 py-2 text-left">Region</th>
                                <th class="border px-2 py-2 text-left">Year Awarded</th>
                                <th class="border px-2 py-2 text-left">Database Course</th>
                                <th class="border px-2 py-2 text-left">Database Curriculum</th>
                                <th class="border px-2 py-2 text-left">Database School</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="(item, index) in scholar"
                                :key="item.id"
                                :class="item.verified_by ? 'bg-emerald-50/50' : ''"
                            >
                                <td class="border px-2 py-1 font-semibold">{{ index + 1 }}</td>
                                <td class="border px-2 py-1">
                                    <span :class="rowStatusClass(item.row_status)">
                                        <span :class="rowStatusDotClass(item.row_status)" />
                                        {{ rowStatusLabel(item) }}
                                    </span>
                                </td>
                                <td class="border px-2 py-1 min-w-80">
                                    <div
                                        v-if="item.row_status !== 'valid'"
                                        class="whitespace-normal text-[11px] leading-4 text-slate-700"
                                    >
                                        {{ conflictDetail(item) }}
                                    </div>
                                    <span v-else class="text-slate-500">Ready to import</span>
                                </td>
                                <td class="border px-2 py-1 font-medium">{{ item.spas_no }}</td>
                                <td class="border px-2 py-1">{{ item.status }}</td>
                                <td class="border px-2 py-1">{{ item.scholarship_type }}</td>
                                <td class="border px-2 py-1">{{ item.scholarship_subprogram }}</td>
                                <td class="border px-2 py-1">{{ item.fname }}</td>
                                <td class="border px-2 py-1">{{ item.lname }}</td>
                                <td class="border px-2 py-1">{{ item.mname }}</td>
                                <td class="border px-2 py-1">{{ item.suffix }}</td>
                                <td class="border px-2 py-1">{{ item.sex }}</td>
                                <td class="border px-2 py-1">{{ item.email }}</td>
                                <td class="border px-2 py-1">{{ item.contact_no }}</td>
                                <td class="border px-2 py-1">{{ item.birthdate }}</td>
                                <td class="border px-2 py-1">{{ item.birthplace }}</td>
                                <td class="border px-2 py-1">{{ item.civil_status }}</td>
                                <td class="border px-2 py-1 min-w-44">{{ item.address }}</td>
                                <td class="border px-2 py-1">{{ item.barangay }}</td>
                                <td class="border px-2 py-1">{{ item.municipality }}</td>
                                <td class="border px-2 py-1">{{ item.province }}</td>
                                <td class="border px-2 py-1">{{ item.region }}</td>
                                <td class="border px-2 py-1">{{ item.year_awarded }}</td>
                                <td class="border px-2 py-1 min-w-64">
                                    <div>{{ item.matchedCourse?.name || "-" }}</div>
                                    <div v-if="item.matchedCourse?.name && item.matchedCourse?.name !== item.course" class="text-[10px] text-slate-400">
                                        Excel: {{ item.course }}
                                    </div>
                                </td>
                                <td class="border px-2 py-1 min-w-44">
                                    {{ item.matchedCurriculum?.name || "-" }}
                                </td>
                                <td class="border px-2 py-1 min-w-72">
                                    <div>{{ item.matchedSchool?.name || item.matchedCourse?.campus || "-" }}</div>
                                    <div v-if="(item.matchedSchool?.name || item.matchedCourse?.campus) && (item.matchedSchool?.name || item.matchedCourse?.campus) !== item.school" class="text-[10px] text-slate-400">
                                        Excel: {{ item.school }}
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="!scholar.length">
                                <td colspan="26" class="border px-2 py-8 text-center text-slate-500">
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
                        <span class="text-xs text-gray-500 font-medium">Validation Progress</span>
                        <span class="text-xs font-semibold">
                            {{ page.props?.validationStatus.completed ?? 0 }} /
                            {{ page.props?.validationStatus.total ?? 0 }}
                        </span>
                    </div>
                    <div class="h-1.5 overflow-hidden rounded-full bg-gray-100">
                        <div
                            class="h-full rounded-full bg-slate-700 transition-all duration-300"
                            :style="{ width: `${validationPercent}%` }"
                        />
                    </div>
                </div>
                <DefaultButton
                    size="small"
                    raised
                    label="Import Batch"
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
            valid: "border-slate-200 bg-white text-slate-700",
            needs_correction: "border-slate-200 bg-white text-slate-700",
            duplicate: "border-slate-200 bg-white text-slate-700",
            missing_required: "border-slate-200 bg-white text-slate-700",
        }[status] || "border-slate-200 bg-white text-slate-700",
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
        header: "Import Batch",
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
