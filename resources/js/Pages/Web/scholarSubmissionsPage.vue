<template>
    <Head title="Grade Submissions" />
    <AuthLayout>
        <div class="flex h-full w-full flex-col gap-4">
            <HeaderModule
                class="!flex-none shrink-0"
                title="Grade Submissions"
                description="Monitor scholar grade submissions by academic year and semester."
            />

            <div class="grid min-h-0 flex-1 grid-cols-1 gap-4 lg:grid-cols-[18rem_minmax(0,1fr)]">
                <aside class="min-h-0 border-r border-slate-200 pr-3 dark:border-gray-700">
                    <div class="mb-2 text-xs font-semibold uppercase text-slate-500 dark:text-gray-400">
                        Semesters
                    </div>
                    <div class="flex max-h-full flex-col gap-1 overflow-y-auto">
                        <button
                            v-for="semester in semesters"
                            :key="semester.id"
                            type="button"
                            :class="[
                                'w-full cursor-pointer rounded-md border px-3 py-2 text-left text-sm transition-colors duration-150',
                                selectedSemesterKey === semester.id
                                    ? 'border-blue-200 bg-blue-50 text-blue-700 hover:bg-blue-100 dark:border-blue-500/40 dark:bg-blue-500/10 dark:text-blue-200 dark:hover:bg-blue-500/20'
                                    : 'border-transparent text-slate-600 hover:border-slate-200 hover:bg-slate-50 hover:text-slate-900 dark:text-gray-300 dark:hover:border-gray-600 dark:hover:bg-gray-800 dark:hover:text-white',
                            ]"
                            @click="selectSemester(semester)"
                        >
                            <span class="block font-semibold">{{ semester.term_name }}</span>
                            <span class="block text-xs opacity-75">{{ semester.academic_year }}</span>
                        </button>
                    </div>
                </aside>

                <DefaultSelectionTable
                    class="min-h-0"
                    :items="rows.data ?? []"
                    :pagination="pagination"
                    scrollable
                    scroll-height="flex"
                    clickable
                    @selected="openSubmission"
                    @paginate="loadPage"
                >
                    <template #header>
                        <div class="flex flex-col gap-2">
                            <div class="flex flex-col gap-2 lg:flex-row lg:items-center">
                                <IconTextInput
                                    v-model="searchInput"
                                    :icon="IconSearch"
                                    placeholder="Search SPAS or scholar"
                                    class="w-full lg:w-72"
                                />
                                <div class="flex flex-wrap gap-2">
                                    <DefaultButton :icon="filterSchool ? IconFilterFilled : IconFilter" label="Schools" class-name="!rounded-xl" size="small" severity="secondary" @click="toggleFilter($event, 'school')" />
                                    <DefaultButton :icon="filterProgram ? IconFilterFilled : IconFilter" label="Programs" class-name="!rounded-xl" size="small" severity="secondary" @click="toggleFilter($event, 'program')" />
                                    <DefaultButton :icon="filterType ? IconFilterFilled : IconFilter" label="Types" class-name="!rounded-xl" size="small" severity="secondary" @click="toggleFilter($event, 'type')" />
                                    <DefaultButton :icon="filterStatus ? IconFilterFilled : IconFilter" label="Status" class-name="!rounded-xl" size="small" severity="secondary" @click="toggleFilter($event, 'status')" />
                                </div>
                            </div>
                            <Popover ref="opSchool">
                                <div class="flex gap-3">
                                    <div class="w-60">
                                        <SelectMultiInput v-model="filterSchool" :options="page.props.schoolFilter" filter capitalize />
                                    </div>
                                    <div class="flex items-center justify-end gap-2">
                                        <DefaultButton label="Clear" class-name="w-20 !rounded-xl" size="small" severity="secondary" @click="clearFilter('school')" />
                                        <DefaultButton label="Filter" class-name="w-20 !rounded-xl" size="small" @click="applyFilter('school')" />
                                    </div>
                                </div>
                            </Popover>
                            <Popover ref="opProgram">
                                <div class="flex gap-3">
                                    <div class="w-60">
                                        <SelectMultiInput v-model="filterProgram" :options="page.props.programFilter" filter capitalize />
                                    </div>
                                    <div class="flex items-center justify-end gap-2">
                                        <DefaultButton label="Clear" class-name="w-20 !rounded-xl" size="small" severity="secondary" @click="clearFilter('program')" />
                                        <DefaultButton label="Filter" class-name="w-20 !rounded-xl" size="small" @click="applyFilter('program')" />
                                    </div>
                                </div>
                            </Popover>
                            <Popover ref="opType">
                                <div class="flex gap-3">
                                    <div class="w-60">
                                        <SelectMultiInput v-model="filterType" :options="page.props.scholarTypeFilter" filter capitalize />
                                    </div>
                                    <div class="flex items-center justify-end gap-2">
                                        <DefaultButton label="Clear" class-name="w-20 !rounded-xl" size="small" severity="secondary" @click="clearFilter('type')" />
                                        <DefaultButton label="Filter" class-name="w-20 !rounded-xl" size="small" @click="applyFilter('type')" />
                                    </div>
                                </div>
                            </Popover>
                            <Popover ref="opStatus">
                                <div class="flex gap-3">
                                    <div class="w-60">
                                        <SelectMultiInput v-model="filterStatus" :options="page.props.statusFilter" filter capitalize />
                                    </div>
                                    <div class="flex items-center justify-end gap-2">
                                        <DefaultButton label="Clear" class-name="w-20 !rounded-xl" size="small" severity="secondary" @click="clearFilter('status')" />
                                        <DefaultButton label="Filter" class-name="w-20 !rounded-xl" size="small" @click="applyFilter('status')" />
                                    </div>
                                </div>
                            </Popover>
                        </div>
                    </template>

                    <Column header="Scholar">
                        <template #body="props">
                            <div class="min-w-56">
                                <div class="text-sm font-semibold uppercase text-slate-700 dark:text-gray-200">
                                    {{ props.data.fullname || "Unnamed Scholar" }}
                                </div>
                                <div class="text-xs text-slate-500 dark:text-gray-400">{{ props.data.spas_no }}</div>
                            </div>
                        </template>
                    </Column>
                    <Column header="Program" field="program" />
                    <Column header="Type" field="type" />
                    <Column header="School">
                        <template #body="props">
                            <div class="min-w-64">
                                <div class="text-sm text-slate-700 dark:text-gray-200">{{ props.data.school }}</div>
                                <div class="text-xs text-slate-500 dark:text-gray-400">{{ props.data.course }}</div>
                            </div>
                        </template>
                    </Column>
                    <Column header="Status">
                        <template #body="props">
                            <span :class="statusClass(props.data.status)">
                                {{ props.data.status }}
                            </span>
                        </template>
                    </Column>
                    <Column header="Scholarship Status">
                        <template #body="props">
                            {{ props.data.scholarship_status ?? "-" }}
                        </template>
                    </Column>
                    <Column header="Submitted" field="submitted_at" />
                </DefaultSelectionTable>
            </div>

            <DialogScholarGradeRequest
                v-if="gradeDialog"
                v-model="gradeDialog"
            />
        </div>
    </AuthLayout>
</template>

<script setup>
import AuthLayout from "../../Layouts/AuthLayout.vue";
import DefaultSelectionTable from "../../Components/tables/DefaultSelectionTable.vue";
import HeaderModule from "../../Modules/Others/HeaderModule.vue";
import IconTextInput from "../../Components/inputs/IconTextInput.vue";
import SelectMultiInput from "../../Components/inputs/SelectMultiInput.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import DialogScholarGradeRequest from "../../Modules/Others/DialogScholarGradeRequest.vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import { IconFilter, IconFilterFilled, IconSearch } from "@tabler/icons-vue";
import { route } from "ziggy-js";

const page = usePage();
const searchInput = ref(page.props.filters?.search ?? null);
const filterSchool = ref(page.props.filters?.schools ?? null);
const filterProgram = ref(page.props.filters?.programs ?? null);
const filterType = ref(page.props.filters?.sub ?? null);
const filterStatus = ref(page.props.filters?.submissionStatus ?? null);
const timer = ref(null);
const gradeDialog = ref(false);
const opSchool = ref(null);
const opProgram = ref(null);
const opType = ref(null);
const opStatus = ref(null);
const semesters = computed(() => page.props.semesters ?? []);
const selectedSemester = computed(() => page.props.selectedSemester ?? null);
const selectedSemesterKey = computed(() => selectedSemester.value?.id ?? null);
const rows = computed(() => page.props.gradeSubmissions ?? {});
const pagination = computed(() => ({
    total: rows.value.total ?? 0,
    perPage: rows.value.per_page ?? 10,
    currentPage: rows.value.current_page ?? 1,
}));

const requestData = (pageNumber = 1, extra = {}) => ({
    page: pageNumber,
    ...(searchInput.value ? { search: searchInput.value } : {}),
    ...(filterSchool.value ? { schools: filterSchool.value } : {}),
    ...(filterProgram.value ? { programs: filterProgram.value } : {}),
    ...(filterType.value ? { sub: filterType.value } : {}),
    ...(filterStatus.value ? { submissionStatus: filterStatus.value } : {}),
    ...(selectedSemester.value
        ? {
              academicYear: selectedSemester.value.academic_year,
              termId: selectedSemester.value.term_id,
          }
        : {}),
    ...extra,
});

const filterPopovers = {
    school: opSchool,
    program: opProgram,
    type: opType,
    status: opStatus,
};

const filterRefs = {
    school: filterSchool,
    program: filterProgram,
    type: filterType,
    status: filterStatus,
};

const toggleFilter = (event, filter) => {
    filterPopovers[filter]?.value?.toggle(event);
};

const applyFilter = (filter) => {
    filterPopovers[filter]?.value?.hide();
    loadPage(1);
};

const clearFilter = (filter) => {
    filterRefs[filter].value = null;
    filterPopovers[filter]?.value?.hide();
    loadPage(1);
};

const loadPage = (pageNumber = 1, extra = {}) => {
    router.get(route("scholar-submissions"), requestData(pageNumber, extra), {
        preserveState: true,
        preserveScroll: true,
    });
};

const selectSemester = (semester) => {
    loadPage(1, {
        academicYear: semester.academic_year,
        termId: semester.term_id,
    });
};

const openSubmission = (row) => {
    if (!row?.scholar_id || !row.id || row.status === "No Submission") return;

    router.reload({
        data: requestData(rows.value.current_page ?? 1, {
            scholar: row.scholar_id,
            dialog: "grades",
            term: row.id,
        }),
        only: ["details", "subjectRequest"],
        preserveScroll: true,
        onSuccess: () => {
            gradeDialog.value = true;
        },
    });
};

const statusClass = (status) => [
    "rounded border px-2 py-1 text-xs uppercase",
    {
        submitted: "border-blue-200 bg-blue-50 text-blue-600 dark:border-blue-500/40 dark:bg-blue-500/10 dark:text-blue-200",
        approved: "border-green-200 bg-green-50 text-green-600 dark:border-green-500/40 dark:bg-green-500/10 dark:text-green-200",
        rejected: "border-red-200 bg-red-50 text-red-600 dark:border-red-500/40 dark:bg-red-500/10 dark:text-red-200",
    }[status] ??
        "border-slate-200 bg-slate-50 text-slate-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300",
];

watch(
    () => searchInput.value,
    () => {
        clearTimeout(timer.value);
        timer.value = setTimeout(() => loadPage(1), 300);
    },
);
</script>

<style scoped>
:deep(.p-datatable-header) {
    border-bottom: 0;
    padding: 0.5rem 0 0.75rem;
}

:deep(.p-datatable *),
:deep(.p-inputtext) {
    transition: none !important;
    animation: none !important;
}
</style>
