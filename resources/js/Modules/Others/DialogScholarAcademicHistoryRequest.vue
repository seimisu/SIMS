<template>
    <Dialog
        v-model:visible="visible"
        modal
        header="Academic History Submission"
        class="w-[min(980px,95vw)]"
    >
        <div v-if="history" class="space-y-4">
            <div class="grid gap-2 text-sm sm:grid-cols-2">
                <div>
                    <p class="text-xs uppercase text-slate-500">Scholar</p>
                    <p class="font-semibold text-slate-800">{{ history.fullname }}</p>
                    <p class="text-xs text-slate-500">{{ history.spas_no }}</p>
                </div>
                <div>
                    <p class="text-xs uppercase text-slate-500">Submitted</p>
                    <p class="text-slate-700">{{ history.submitted_at || "-" }}</p>
                    <p class="text-xs uppercase text-slate-500">{{ history.status }}</p>
                </div>
            </div>

            <div class="max-h-[58vh] space-y-3 overflow-y-auto pr-1">
                <section
                    v-for="term in history.terms"
                    :key="term.id"
                    class="rounded border border-slate-200"
                >
                    <div class="border-b border-slate-200 px-3 py-2">
                        <p class="text-sm font-semibold text-slate-800">
                            {{ term.academic_year || "Academic Year" }}
                            <span class="text-slate-400">/</span>
                            {{ term.term || "Term" }}
                        </p>
                        <p class="text-xs text-slate-500">
                            {{ [term.school, term.course, term.curriculum].filter(Boolean).join(" • ") }}
                        </p>
                    </div>
                    <div class="overflow-x-auto">
                        <table class="w-full table-fixed text-left text-sm">
                            <colgroup>
                                <col class="w-[58%]" />
                                <col class="w-[18%]" />
                                <col class="w-[12%]" />
                                <col class="w-[12%]" />
                            </colgroup>
                            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                                <tr>
                                    <th class="px-3 py-2">Subject</th>
                                    <th class="px-3 py-2">Code</th>
                                    <th class="px-3 py-2">Unit</th>
                                    <th class="px-3 py-2">Grade</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr
                                    v-for="(subject, index) in term.subjects"
                                    :key="`${term.id}-${index}`"
                                    class="border-t border-slate-100"
                                >
                                    <td class="px-3 py-2 font-medium uppercase text-slate-700">
                                        <span class="block truncate" :title="subject.name">
                                            {{ subject.name }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-slate-600">
                                        <span class="block truncate" :title="subject.code || '-'">
                                            {{ subject.code || "-" }}
                                        </span>
                                    </td>
                                    <td class="px-3 py-2 text-slate-600">{{ subject.unit || "-" }}</td>
                                    <td class="px-3 py-2 text-slate-700">{{ subject.grade || "-" }}</td>
                                </tr>
                                <tr v-if="!term.subjects?.length">
                                    <td colspan="4" class="px-3 py-4 text-center text-slate-500">
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
    >
        <div class="space-y-2">
            <p class="text-sm text-slate-700">
                Approve this academic history submission?
            </p>
            <div>
                <p class="text-sm font-semibold text-slate-800">
                    {{ history?.fullname || "Scholar" }}
                </p>
                <p class="text-xs text-slate-500">{{ history?.spas_no }}</p>
            </div>
            <div class="space-y-3 pt-2">
                <div
                    v-for="term in history?.terms ?? []"
                    :key="term.id"
                    class="rounded border border-slate-200 p-3"
                >
                    <div class="mb-2">
                        <p class="text-sm font-semibold text-slate-800">
                            {{ term.academic_year || "Academic Year" }}
                            <span class="text-slate-400">/</span>
                            {{ term.term || "Term" }}
                        </p>
                        <p class="text-xs text-slate-500">
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
    >
        <div class="space-y-3">
            <div>
                <p class="text-sm font-semibold text-slate-800">
                    {{ history?.fullname || "Scholar" }}
                </p>
                <p class="text-xs text-slate-500">{{ history?.spas_no }}</p>
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
