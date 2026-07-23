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
                        <table class="w-full text-left text-sm">
                            <thead class="bg-slate-50 text-xs uppercase text-slate-500">
                                <tr>
                                    <th class="px-3 py-2">Subject</th>
                                    <th class="px-3 py-2">Code</th>
                                    <th class="px-3 py-2">Class</th>
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
                                    <td class="px-3 py-2 font-medium text-slate-700">{{ subject.name }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ subject.code || "-" }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ subject.class || "-" }}</td>
                                    <td class="px-3 py-2 text-slate-600">{{ subject.unit || "-" }}</td>
                                    <td class="px-3 py-2 text-slate-700">{{ subject.grade || "-" }}</td>
                                </tr>
                                <tr v-if="!term.subjects?.length">
                                    <td colspan="5" class="px-3 py-4 text-center text-slate-500">
                                        No subjects submitted.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>

            <Textarea
                v-if="showReturn"
                v-model="returnReason"
                rows="3"
                class="w-full"
                placeholder="Reason for returning this submission"
            />
        </div>

        <template #footer>
            <div class="flex w-full justify-end gap-2">
                <Button label="Close" severity="secondary" outlined @click="visible = false" />
                <Button
                    v-if="history?.status === 'submitted'"
                    label="Return"
                    severity="danger"
                    outlined
                    @click="returnSubmission"
                />
                <Button
                    v-if="history?.status === 'submitted'"
                    label="Approve"
                    @click="approveSubmission"
                />
            </div>
        </template>
    </Dialog>
</template>

<script setup>
import Button from "primevue/button";
import Dialog from "primevue/dialog";
import Textarea from "primevue/textarea";
import { router, usePage } from "@inertiajs/vue3";
import { computed, ref } from "vue";

const visible = defineModel({ type: Boolean, default: false });
const page = usePage();
const history = computed(() => page.props.academicHistoryRequest);
const showReturn = ref(false);
const returnReason = ref("");

const approveSubmission = () => {
    if (!history.value?.id) return;

    router.post(`/scholar-academic-history/${history.value.id}/approve`, {}, {
        preserveScroll: true,
        onSuccess: () => {
            visible.value = false;
        },
    });
};

const returnSubmission = () => {
    if (!showReturn.value) {
        showReturn.value = true;
        return;
    }

    if (!history.value?.id || !returnReason.value.trim()) return;

    router.post(`/scholar-academic-history/${history.value.id}/return`, {
        return_reason: returnReason.value,
    }, {
        preserveScroll: true,
        onSuccess: () => {
            visible.value = false;
            showReturn.value = false;
            returnReason.value = "";
        },
    });
};
</script>
