<template>
    <Dialog
        v-model:visible="visible"
        modal
        header="Academic History Submission"
        class="w-[min(1120px,96vw)]"
        :pt="dialogPt"
    >
        <div v-if="history" class="space-y-4">
            <div class="grid gap-2 text-sm sm:grid-cols-2">
                <div>
                    <p class="text-xs uppercase text-slate-500 dark:text-gray-400">Scholar</p>
                    <p class="font-semibold text-slate-800 dark:text-gray-100">{{ history.fullname }}</p>
                    <p class="text-xs text-slate-500 dark:text-gray-400">{{ history.spas_no }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase text-slate-500 dark:text-gray-400">Submitted</p>
                    <p class="text-slate-700 dark:text-gray-200">{{ history.submitted_at || "-" }}</p>
                    <p class="text-xs uppercase text-slate-500 dark:text-gray-400">{{ history.status }}</p>
                </div>
            </div>

            <div class="max-h-[58vh] space-y-3 overflow-y-auto pr-1">
                <section
                    v-for="term in history.terms"
                    :key="term.id"
                    class="rounded border border-slate-200 dark:border-gray-600"
                >
                    <div class="border-b border-slate-200 px-3 py-2 dark:border-gray-600">
                        <p class="text-sm font-semibold text-slate-800 dark:text-gray-100">
                            {{ term.academic_year || "Academic Year" }}
                            <span class="text-slate-400 dark:text-gray-500">/</span>
                            {{ term.term || "Term" }}
                        </p>
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            {{ [term.school, term.course, term.curriculum].filter(Boolean).join(" • ") }}
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full table-fixed text-left text-sm">
                            <colgroup>
                                <col class="w-[42%]" />
                                <col class="w-[16%]" />
                                <col class="w-[10%]" />
                                <col class="w-[12%]" />
                                <col class="w-[20%]" />
                            </colgroup>
                            <thead class="bg-slate-50 text-xs uppercase text-slate-500 dark:bg-gray-800 dark:text-gray-300">
                                <tr>
                                    <th class="px-3 py-2">Subject</th>
                                    <th class="px-3 py-2">Code</th>
                                    <th class="px-3 py-2">Unit</th>
                                    <th class="px-3 py-2">Grade</th>
                                    <th class="px-3 py-2">Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(subject, index) in term.subjects"
                                    :key="`${term.id}-${index}`"
                                    :class="[
                                        'border-t',
                                        subjectRowClass(subject),
                                    ]"
                                >
                                    <td class="px-3 py-2 font-medium uppercase text-slate-700 dark:text-gray-200">
                                        <span class="block truncate" :title="subject.name">
                                            {{ subject.name }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-slate-600 dark:text-gray-300">
                                        <span class="block truncate" :title="subject.code || '-'">
                                            {{ subject.code || "-" }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-slate-600 dark:text-gray-300">{{ subject.unit || "-" }}</td>
                                    <td class="px-3 py-2">
                                        <span
                                            :class="[
                                                'inline-flex rounded px-2 py-0.5 text-xs font-semibold uppercase',
                                                subjectGradeClass(subject),
                                            ]"
                                        >
                                            {{ subject.grade || "-" }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-slate-600 dark:text-gray-300">
                                        <span class="block truncate" :title="subject.remarks || '-'">
                                            {{ subject.remarks || "-" }}
                                        </span>
                                    </td>
                                </tr>
                                <tr v-if="!term.subjects?.length">
                                    <td colspan="5" class="px-3 py-4 text-center text-slate-500 dark:text-gray-400">
                                        No subjects submitted.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

        </div>

        <template #footer>
            <div class="flex w-full justify-end gap-2">
                <Button label="Close" severity="secondary" outlined @click="visible = false" />
                <Button
                    v-if="history?.status === 'submitted'"
                    label="Return"
                    severity="danger"
                    outlined
                    @click="openReturnDialog"
                />
                <Button
                    v-if="history?.status === 'submitted'"
                    label="Approve"
                    @click="approveDialog = true"
                />
            </div>
        </template>
    </Dialog>

    <Dialog
        v-model:visible="approveDialog"
        modal
        header="Approve Academic History"
        class="w-[min(620px,94vw)]"
        :pt="dialogPt"
    >
        <div class="space-y-2">
            <p class="text-sm text-slate-700 dark:text-gray-300">
                Approve this academic history submission?
            </p>
            <div>
                <p class="text-sm font-semibold text-slate-800 dark:text-gray-100">
                    {{ history?.fullname || "Scholar" }}
                </p>
                <p class="text-xs text-slate-500 dark:text-gray-400">{{ history?.spas_no }}</p>
            </div>
            <div class="space-y-3 pt-2">
                <div
                    v-for="term in history?.terms ?? []"
                    :key="term.id"
                    class="rounded border border-slate-200 p-3 dark:border-gray-600"
                >
                    <div class="mb-2">
                        <p class="text-sm font-semibold text-slate-800 dark:text-gray-100">
                            {{ term.academic_year || "Academic Year" }}
                            <span class="text-slate-400 dark:text-gray-500">/</span>
                            {{ term.term || "Term" }}
                        </p>
                        <p class="text-xs text-slate-500 dark:text-gray-400">
                            {{ [term.school, term.course].filter(Boolean).join(" / ") }}
                        </p>
                    </div>
                    <SelectInput
                        v-model="termStatuses[term.id]"
                        label="Academic Status"
                        :options="page.props?.standingOptions ?? []"
                        error-mark
                    />
                </div>
            </div>
        </div>

        <template #footer>
            <div class="flex w-full justify-end gap-2">
                <Button label="Cancel" severity="secondary" outlined @click="approveDialog = false" />
                <Button
                    label="Approve"
                    :disabled="!allTermStatusesSelected"
                    @click="approveSubmission"
                />
            </div>
        </template>
    </Dialog>

    <Dialog
        v-model:visible="returnDialog"
        modal
        header="Return Academic History"
        class="w-[min(520px,92vw)]"
        :pt="dialogPt"
    >
        <div class="space-y-3">
            <div>
                <p class="text-sm font-semibold text-slate-800 dark:text-gray-100">
                    {{ history?.fullname || "Scholar" }}
                </p>
                <p class="text-xs text-slate-500 dark:text-gray-400">{{ history?.spas_no }}</p>
            </div>
            <Textarea
                v-model="returnReason"
                rows="4"
                class="w-full"
                autofocus
                placeholder="Reason for returning this submission"
            />
        </div>

        <template #footer>
            <div class="flex w-full justify-end gap-2">
                <Button label="Cancel" severity="secondary" outlined @click="cancelReturn" />
                <Button
                    label="Return"
                    severity="danger"
                    :disabled="!returnReason.trim()"
                    @click="returnSubmission"
                />
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import Textarea from "primevue/textarea";
import SelectInput from "../../Components/inputs/SelectInput.vue";
import { router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const visible = defineModel({ type: Boolean, default: false });
const page = usePage();
const dialogPt = {
    root: 'dark:!bg-gray-900 dark:!text-gray-100',
    header: 'dark:!bg-gray-900 dark:!text-gray-100 dark:!border-gray-700',
    content: 'dark:!bg-gray-900 dark:!text-gray-100',
    footer: 'dark:!bg-gray-900 dark:!text-gray-100 dark:!border-gray-700',
};
const history = computed(() => page.props.academicHistoryRequest);
const approveDialog = ref(false);
const returnDialog = ref(false);
const returnReason = ref("");
const termStatuses = ref({});

const approvalTerms = computed(() => history.value?.terms ?? []);
const allTermStatusesSelected = computed(() =>
    approvalTerms.value.length > 0 &&
    approvalTerms.value.every((term) => Boolean(termStatuses.value[term.id])),
);

const subjectStatus = (subject) => {
    if (subject?.is_dropped) return "dropped";
    if (subject?.is_incomplete) return "incomplete";
    if (subject?.is_failed) return "failed";

    return null;
};

const subjectRowClass = (subject) => {
    const status = subjectStatus(subject);

    return {
        dropped: "border-slate-200 bg-slate-100/80 dark:border-gray-600 dark:bg-gray-800",
        incomplete: "border-amber-200 bg-amber-50",
        failed: "border-red-200 bg-red-50",
    }[status] ?? "border-slate-100 dark:border-gray-700";
};

const subjectGradeClass = (subject) => {
    const status = subjectStatus(subject);

    return {
        dropped: "bg-slate-200 text-slate-700 dark:bg-gray-700 dark:text-gray-200",
        incomplete: "bg-amber-100 text-amber-800",
        failed: "bg-red-100 text-red-700",
    }[status] ?? "bg-slate-100 text-slate-700 dark:bg-gray-800 dark:text-gray-200";
};

const approveSubmission = () => {
    if (!history.value?.id || !allTermStatusesSelected.value) return;

    router.post(`/scholar-academic-history/${history.value.id}/approve`, {
        terms: approvalTerms.value.map((term) => ({
            term_id: term.id,
            scholarshipStatus: termStatuses.value[term.id],
        })),
    }, {
        preserveScroll: true,
        onSuccess: () => {
            approveDialog.value = false;
            termStatuses.value = {};
            visible.value = false;
        },
    });
};

const openReturnDialog = () => {
    returnDialog.value = true;
};

const cancelReturn = () => {
    returnDialog.value = false;
    returnReason.value = "";
};

const returnSubmission = () => {
    if (!history.value?.id || !returnReason.value.trim()) return;

    router.post(`/scholar-academic-history/${history.value.id}/return`, {
        return_reason: returnReason.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            visible.value = false;
            returnDialog.value = false;
            returnReason.value = "";
        },
    });
};
</script>
