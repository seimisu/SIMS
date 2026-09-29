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
                    <div class="flex flex-col gap-2 lg:flex-row lg:items-center lg:justify-start">
                        <IconTextInput
                            v-model="searchInput"
                            :icon="IconSearch"
                            placeholder="Search SPAS or scholar"
                            class="w-full sm:w-72"
                        />
                        <DefaultButton
                            :icon="filterSchool ? IconFilterFilled : IconFilter"
                            label="Schools"
                            class-name="!rounded-xl"
                            size="small"
                            severity="secondary"
                            @click="toggleFilter($event, 'school')"
                        />
                        <DefaultButton
                            :icon="filterProgram ? IconFilterFilled : IconFilter"
                            label="Programs"
                            class-name="!rounded-xl"
                            size="small"
                            severity="secondary"
                            @click="toggleFilter($event, 'program')"
                        />
                        <DefaultButton
                            :icon="filterType ? IconFilterFilled : IconFilter"
                            label="Types"
                            class-name="!rounded-xl"
                            size="small"
                            severity="secondary"
                            @click="toggleFilter($event, 'type')"
                        />
                        <DefaultButton
                            :icon="filterStatus ? IconFilterFilled : IconFilter"
                            label="Status"
                            class-name="!rounded-xl"
                            size="small"
                            severity="secondary"
                            @click="toggleFilter($event, 'status')"
                        />
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
                <Column header="School" field="school" />
                <Column header="Program" field="program" />
                <Column header="Type" field="type" />
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
import SelectMultiInput from "../../Components/inputs/SelectMultiInput.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import DialogScholarDetailRequest from "../../Modules/Others/DialogScholarDetailRequest.vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import { IconFilter, IconFilterFilled, IconSearch } from "@tabler/icons-vue";
import { route } from "ziggy-js";

const page = usePage();
const searchInput = ref(page.props.filters?.search ?? null);
const filterSchool = ref(page.props.filters?.schools ?? null);
const filterProgram = ref(page.props.filters?.programs ?? null);
const filterType = ref(page.props.filters?.sub ?? null);
const filterStatus = ref(page.props.filters?.status ?? null);
const timer = ref(null);
const profileDialog = ref(false);
const opSchool = ref(null);
const opProgram = ref(null);
const opType = ref(null);
const opStatus = ref(null);
const rows = computed(() => page.props.profileRequests ?? {});
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
    ...(filterStatus.value ? { status: filterStatus.value } : {}),
    ...extra,
});

const loadPage = (pageNumber = 1) => {
    router.get(route("scholar-profile-requests"), requestData(pageNumber), {
        preserveState: true,
        preserveScroll: true,
    });
};

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
