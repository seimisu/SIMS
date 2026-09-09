<template>
    <Head title="Profile Requests" />
    <AuthLayout>
        <div class="flex h-full w-full flex-col gap-4">
            <HeaderModule
                class="!flex-none shrink-0"
                title="Profile Requests"
                description="Review scholar profile update requests."
            />

            <DefaultSelectionTable
                class="min-h-0 flex-1"
                :items="rows.data ?? []"
                :pagination="pagination"
                scrollable
                scroll-height="flex"
                clickable
                @selected="openRequest"
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
                <Column header="Purpose" field="purpose" />
                <Column header="Status">
                    <template #body="props">
                        <span :class="statusClass(props.data.status)">
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
        </div>
    </AuthLayout>
</template>

<script setup>
import AuthLayout from "../../Layouts/AuthLayout.vue";
import DefaultSelectionTable from "../../Components/tables/DefaultSelectionTable.vue";
import HeaderModule from "../../Modules/Others/HeaderModule.vue";
import IconTextInput from "../../Components/inputs/IconTextInput.vue";
import DialogScholarDetailRequest from "../../Modules/Others/DialogScholarDetailRequest.vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import { IconSearch } from "@tabler/icons-vue";
import { route } from "ziggy-js";

const page = usePage();
const searchInput = ref(page.props.filters?.search ?? null);
const timer = ref(null);
const profileDialog = ref(false);
const rows = computed(() => page.props.profileRequests ?? {});
const pagination = computed(() => ({
    total: rows.value.total ?? 0,
    perPage: rows.value.per_page ?? 10,
    currentPage: rows.value.current_page ?? 1,
}));

const requestData = (pageNumber = 1, extra = {}) => ({
    page: pageNumber,
    ...(searchInput.value ? { search: searchInput.value } : {}),
    ...extra,
});

const loadPage = (pageNumber = 1) => {
    router.get(route("scholar-profile-requests"), requestData(pageNumber), {
        preserveState: true,
        preserveScroll: true,
    });
};

const openRequest = (row) => {
    if (!row?.scholar_id) return;

    router.reload({
        data: requestData(rows.value.current_page ?? 1, {
            scholar: row.scholar_id,
        }),
        only: ["details", "personalRequest"],
        preserveScroll: true,
        onSuccess: () => {
            profileDialog.value = true;
        },
    });
};

const statusClass = (status) => [
    "rounded border px-2 py-1 text-xs uppercase",
    {
        pending: "border-amber-200 bg-amber-50 text-amber-600 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200",
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
