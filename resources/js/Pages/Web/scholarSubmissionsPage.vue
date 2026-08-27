<template>
    <Head title="Scholar Submissions" />
    <AuthLayout>
        <div class="flex h-full w-full flex-col gap-4">
            <HeaderModule
                class="!flex-none shrink-0"
                title="Scholar Submissions"
                description="Review submitted grades, profile updates, and Landbank requests across all academic years and schools."
            />

            <div class="flex">
                <Tabs
                    :value="activeTab"
                    class="w-fit max-w-full compact-submission-tabs"
                    @update:value="switchTab"
                >
                    <TabList class="dark:!bg-gray-800">
                        <Tab
                            v-for="tab in tabs"
                            :key="tab.id"
                            :value="tab.id"
                        >
                            <span class="inline-flex items-center gap-1.5">
                                {{ tab.label }}
                                <span
                                    v-if="Number(counts[tab.id] ?? 0) > 0"
                                    class="rounded-full bg-red-100 px-2 py-0.5 text-[11px] font-semibold text-red-600"
                                >
                                    {{ counts[tab.id] ?? 0 }}
                                </span>
                            </span>
                        </Tab>
                    </TabList>
                </Tabs>
            </div>

            <DefaultSelectionTable
                class="min-h-0 flex-1"
                :items="activeRows.data ?? []"
                :pagination="{
                    total: activeRows.total ?? 0,
                    perPage: activeRows.per_page ?? 10,
                    currentPage: activeRows.current_page ?? 1,
                }"
                scrollable
                scroll-height="flex"
                @selected="openSubmission"
                @paginate="loadPage"
            >
                <template #header>
                    <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-start">
                        <IconTextInput
                            v-model="searchInput"
                            :icon="IconSearch"
                            placeholder="Search SPAS or scholar"
                            class="w-full sm:w-72"
                        />
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
                <Column header="Scholarship" field="type" />
                <Column v-if="activeTab === 'grades'" header="School">
                    <template #body="props">
                        <div class="min-w-64">
                            <div class="text-sm text-slate-700 dark:text-gray-200">{{ props.data.school }}</div>
                            <div class="text-xs text-slate-500 dark:text-gray-400">{{ props.data.course }}</div>
                        </div>
                    </template>
                </Column>
                <Column v-if="activeTab === 'grades'" header="AY / Term">
                    <template #body="props">
                        <div class="text-sm">
                            {{ props.data.academic_year }}
                            <span class="text-slate-400 dark:text-gray-500">/</span>
                            {{ props.data.term }}
                        </div>
                    </template>
                </Column>
                <Column header="Status">
                    <template #body="props">
                        <span class="rounded border border-slate-200 bg-slate-50 px-2 py-1 text-xs uppercase text-slate-600 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300">
                            {{ props.data.status }}
                        </span>
                    </template>
                </Column>
                <Column header="Submitted" field="submitted_at" />
            </DefaultSelectionTable>

            <DialogScholarDetailRequest
                v-if="profileDialog"
                v-model="profileDialog"
            />
            <DialogScholarGradeRequest
                v-if="gradeDialog"
                v-model="gradeDialog"
            />
            <DialogScholarLandbankRequest
                v-if="landbankDialog"
                v-model="landbankDialog"
            />
        </div>
    </AuthLayout>
</template>

<script setup>
import AuthLayout from "../../Layouts/AuthLayout.vue";
import DefaultSelectionTable from "../../Components/tables/DefaultSelectionTable.vue";
import HeaderModule from "../../Modules/Others/HeaderModule.vue";
import IconTextInput from "../../Components/inputs/IconTextInput.vue";
import DialogScholarDetailRequest from "../../Modules/Others/DialogScholarDetailRequest.vue";
import DialogScholarGradeRequest from "../../Modules/Others/DialogScholarGradeRequest.vue";
import DialogScholarLandbankRequest from "../../Modules/Others/DialogScholarLandbankRequest.vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import { IconSearch } from "@tabler/icons-vue";
import { route } from "ziggy-js";

const page = usePage();
const searchInput = ref(page.props.filters?.search ?? null);
const timer = ref(null);
const gradeDialog = ref(false);
const profileDialog = ref(false);
const landbankDialog = ref(false);

const tabs = [
    { id: "grades", label: "Grade Submissions" },
    { id: "profile", label: "Profile Requests" },
    { id: "landbank", label: "Landbank Requests" },
];
const initialTab = tabs.some((tab) => tab.id === page.props.filters?.tab)
    ? page.props.filters.tab
    : "grades";
const activeTab = ref(initialTab);
const counts = computed(() => page.props.counts ?? {});
const activeRows = computed(() => {
    if (activeTab.value === "profile") return page.props.profileRequests ?? {};
    if (activeTab.value === "landbank") return page.props.landbankRequests ?? {};
    return page.props.gradeSubmissions ?? {};
});

const requestData = (pageNumber = 1, extra = {}) => ({
    tab: activeTab.value,
    page: pageNumber,
    ...(searchInput.value ? { search: searchInput.value } : {}),
    ...extra,
});

const loadPage = (pageNumber = 1) => {
    router.get(route("scholar-submissions"), requestData(pageNumber), {
        preserveState: true,
        preserveScroll: true,
    });
};

const switchTab = (tab) => {
    activeTab.value = tab;
    loadPage(1);
};

const openSubmission = (row) => {
    if (!row?.scholar_id) return;

    const dialog = activeTab.value;
    const dialogData = {
        scholar: row.scholar_id,
        dialog,
        ...(dialog === "grades" ? { term: row.id } : {}),
    };

    router.reload({
        data: requestData(activeRows.value.current_page ?? 1, dialogData),
        only: ["details", "subjectRequest", "personalRequest", "landbankRequest"],
        preserveScroll: true,
        onSuccess: () => {
            gradeDialog.value = dialog === "grades";
            profileDialog.value = dialog === "profile";
            landbankDialog.value = dialog === "landbank";
        },
    });
};

watch(
    () => searchInput.value,
    () => {
        clearTimeout(timer.value);
        timer.value = setTimeout(() => loadPage(1), 300);
    },
);
</script>

<style scoped>
:deep(.compact-submission-tabs .p-tablist-tab-list) {
    gap: 0.25rem;
    border-width: 0;
}

:global(.dark) :deep(.compact-submission-tabs .p-tablist),
:global(.dark) :deep(.compact-submission-tabs .p-tablist-tab-list),
:global(.dark) :deep(.compact-submission-tabs .p-tab) {
    background: #1f2937 !important;
    color: #d1d5db !important;
    border-color: #374151 !important;
}

:deep(.compact-submission-tabs .p-tab) {
    padding: 0.45rem 0.8rem;
    font-size: 0.8125rem;
    border-bottom: 2px solid transparent;
}

:deep(.compact-submission-tabs .p-tab-active) {
    border-bottom-color: #1a2551;
    color: #1a2551;
}

:global(.dark) :deep(.compact-submission-tabs .p-tab-active) {
    border-bottom-color: #60a5fa !important;
    color: #ffffff !important;
}

:deep(.compact-submission-tabs .p-tablist-active-bar) {
    display: none;
}

:deep(.p-datatable-header) {
    border-bottom: 0;
    padding: 0.5rem 0 0.75rem;
}

:deep(.p-tabs *),
:deep(.p-datatable *),
:deep(.p-select *),
:deep(.p-inputtext) {
    transition: none !important;
    animation: none !important;
}
</style>
