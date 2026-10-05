<template>
    <Head title="Scholars" />
    <AuthLayout>
        <div
            class="scholars-page flex h-full min-h-0 w-full flex-col gap-4 overflow-hidden text-slate-800 dark:text-gray-100"
        >
            <div
                class="shrink-0 border-b border-slate-200 pb-4 dark:border-gray-700"
            >
                <div class="flex min-w-0 items-start gap-3">
                    <div
                        class="flex size-10 shrink-0 items-center justify-center rounded-md bg-blue-600 text-white shadow-sm dark:bg-blue-500"
                    >
                        <IconUsers :size="21" stroke-width="1.8" />
                    </div>
                    <HeaderModule
                        title="Scholar Management"
                        description="Manage scholar records, academic assignments, account access, and current status."
                    />
                </div>
            </div>
            <ManagementFilterBar
                v-model="searchInput"
                search-placeholder="Search name or SPAS number"
            >
                <div>
                    <DefaultButton
                        :icon="
                            filterSchool != null
                                ? TablerIcons.IconFilterFilled
                                : TablerIcons.IconFilter
                        "
                        label="Schools"
                        class-name="!rounded-md"
                        size="small"
                        severity="secondary"
                        @click="toggleOpSchool"
                    />
                    <Popover ref="opSchool">
                        <div class="gap-3 flex" v-if="page.props?.schoolFilter">
                            <div class="flex-1 w-60">
                                <SelectMultiInput
                                    filter
                                    v-model="filterSchool"
                                    :options="page.props?.schoolFilter"
                                    capitalize
                                ></SelectMultiInput>
                            </div>

                            <div class="flex justify-end items-center gap-2">
                                <DefaultButton
                                    @click="schoolFilterClear"
                                    label="Clear"
                                    class-name="w-20 !rounded-xl"
                                    size="small"
                                    severity="secondary"
                                />
                                <DefaultButton
                                    @click="schoolFilter"
                                    label="Filter"
                                    class-name="w-20 !rounded-xl"
                                    size="small"
                                />
                            </div>
                        </div>
                    </Popover>
                </div>
                <div>
                    <DefaultButton
                        :icon="
                            filterProgram != null
                                ? TablerIcons.IconFilterFilled
                                : TablerIcons.IconFilter
                        "
                        label="Programs"
                        class-name="!rounded-md"
                        size="small"
                        severity="secondary"
                        @click="toggleopProgram"
                    />
                    <Popover ref="opProgram">
                        <div
                            class="gap-3 flex"
                            v-if="page.props?.programFilter"
                        >
                            <div class="flex-1 w-60">
                                <SelectMultiInput
                                    v-model="filterProgram"
                                    :options="page.props?.programFilter"
                                    capitalize
                                ></SelectMultiInput>
                            </div>

                            <div class="flex justify-end items-center gap-2">
                                <DefaultButton
                                    @click="programFilterClear"
                                    label="Clear"
                                    class-name="w-20 !rounded-xl"
                                    size="small"
                                    severity="secondary"
                                />
                                <DefaultButton
                                    @click="programFilter"
                                    label="Filter"
                                    class-name="w-20 !rounded-xl"
                                    size="small"
                                />
                            </div>
                        </div>
                    </Popover>
                </div>
                <div>
                    <DefaultButton
                        :icon="
                            filterSub != null
                                ? TablerIcons.IconFilterFilled
                                : TablerIcons.IconFilter
                        "
                        label="Types"
                        class-name="!rounded-md"
                        size="small"
                        severity="secondary"
                        @click="toggleopSub"
                    />
                    <Popover ref="opSub">
                        <div
                            class="gap-3 flex"
                            v-if="page.props?.scholarTypeFilter"
                        >
                            <div class="flex-1 w-60">
                                <SelectMultiInput
                                    v-model="filterSub"
                                    :options="page.props?.scholarTypeFilter"
                                    capitalize
                                ></SelectMultiInput>
                            </div>

                            <div class="flex justify-end items-center gap-2">
                                <DefaultButton
                                    @click="subFilterClear"
                                    label="Clear"
                                    class-name="w-20 !rounded-xl"
                                    size="small"
                                    severity="secondary"
                                />
                                <DefaultButton
                                    @click="subFilter"
                                    label="Filter"
                                    class-name="w-20 !rounded-xl"
                                    size="small"
                                />
                            </div>
                        </div>
                    </Popover>
                </div>
                <div>
                    <DefaultButton
                        :icon="
                            filterStatus != null
                                ? TablerIcons.IconFilterFilled
                                : TablerIcons.IconFilter
                        "
                        label="Status"
                        class-name="!rounded-md"
                        size="small"
                        severity="secondary"
                        @click="toggleopStatus"
                    />
                    <Popover ref="opStatus">
                        <div class="gap-3 flex" v-if="page.props?.statusFilter">
                            <div class="flex-1 w-60">
                                <SelectMultiInput
                                    v-model="filterStatus"
                                    :options="page.props?.statusFilter"
                                    capitalize
                                ></SelectMultiInput>
                            </div>

                            <div class="flex justify-end items-center gap-2">
                                <DefaultButton
                                    @click="statusFilterClear"
                                    label="Clear"
                                    class-name="w-20 !rounded-xl"
                                    size="small"
                                    severity="secondary"
                                />
                                <DefaultButton
                                    @click="statusFilter"
                                    label="Filter"
                                    class-name="w-20 !rounded-xl"
                                    size="small"
                                />
                            </div>
                        </div>
                    </Popover>
                </div>
                <DefaultButton
                    :icon="TablerIcons.IconFilterOff"
                    tooltip="Clear all filters"
                    size="small"
                    severity="secondary"
                    outlined
                    :disabled="!activeFilterCount"
                    @click="clearAllFilters"
                />
            </ManagementFilterBar>
            <DefaultSelectionTable
                class="min-h-0 flex-1"
                :items="page.props.scholars.data"
                :pagination="{
                    total: page.props.scholars.total,
                    perPage: page.props.scholars.per_page,
                    currentPage: page.props.scholars.current_page,
                }"
                @selected="toggleScholarDetails"
                :loading="loading.table"
                @paginate="loadPage"
                scrollable
                scroll-height="flex"
            >
                <Column header="Scholars">
                    <template #body="props">
                        <div class="flex min-w-64 items-center gap-3 py-1">
                            <div class="">
                                <Avatar
                                    :label="
                                        props.data.fullname
                                            .charAt(0)
                                            .toUpperCase()
                                    "
                                    style="
                                        background-color: #dee9fc;
                                        color: #1a2551;
                                    "
                                    class="!h-9 !w-9 !rounded-md"
                                    :image="
                                        props.data.photo == null
                                            ? null
                                            : props.data.photo
                                    "
                                />
                            </div>
                            <div class="flex-1 flex flex-col">
                                <div
                                    :class="[
                                        'flex items-center text-[11px] font-medium text-blue-600 dark:text-blue-300',
                                    ]"
                                >
                                    <div># {{ props.data.spas_no }}</div>
                                </div>
                                <div class="flex gap-1 items-center">
                                    <div
                                        class="truncate text-sm font-semibold uppercase text-slate-800 dark:text-gray-100"
                                    >
                                        {{ props.data.fullname }}
                                    </div>
                                    <div
                                        v-tooltip.top="'Account activated'"
                                        v-if="props.data?.activated_at"
                                    >
                                        <IconRosetteDiscountCheckFilled
                                            :size="20"
                                            class="text-green-600"
                                        />
                                    </div>
                                </div>
                            </div>
                        </div>
                    </template>
                </Column>
                <Column header="School/Course">
                    <template #body="props">
                        <div class="flex min-w-64 flex-col gap-0.5 py-1">
                            <div
                                class="text-xs text-slate-500 dark:text-gray-400"
                            >
                                {{ props.data.course || "Course not assigned" }}
                            </div>
                            <div
                                class="truncate text-sm font-medium text-slate-700 dark:text-gray-200"
                            >
                                {{ props.data.school || "School not assigned" }}
                            </div>
                        </div>
                    </template>
                </Column>
                <Column header="Region">
                    <template #body="props">
                        <div
                            class="text-xs font-semibold uppercase text-slate-600 dark:text-gray-300"
                        >
                            {{ props.data.agency || "-" }}
                        </div>
                    </template>
                </Column>
                <Column>
                    <template #header>
                        <div class="flex justify-center w-full font-semibold">
                            <div class="font-semibold">Type</div>
                        </div>
                    </template>
                    <template #body="props">
                        <div
                            class="text-center text-xs font-medium text-slate-700 dark:text-gray-200"
                        >
                            {{ props.data.type || "-" }}
                        </div>
                    </template>
                </Column>
                <Column>
                    <template #header>
                        <div class="flex justify-center w-full font-semibold">
                            <div class="font-semibold">Program</div>
                        </div>
                    </template>
                    <template #body="props">
                        <div
                            class="text-center text-xs font-medium text-slate-700 dark:text-gray-200"
                        >
                            {{ props.data.subProgram || "-" }}
                        </div>
                    </template>
                </Column>
                <Column>
                    <template #header>
                        <div class="flex justify-center w-full font-semibold">
                            <div class="font-semibold">Scholar Status</div>
                        </div>
                    </template>
                    <template #body="props">
                        <div class="flex items-center justify-center">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-full border px-2.5 py-1 text-[11px] font-medium"
                                :class="[
                                    props.data.status.tcolor,
                                    props.data.status.bcolor,
                                ]"
                            >
                                <component
                                    :is="TablerIcons[props.data.status.icon]"
                                    :size="18"
                                    :stroke="1.8"
                                />
                                <div>
                                    {{ props.data.status.name }}
                                </div>
                            </span>
                        </div>
                    </template>
                </Column>
                <!-- <Column>
                    <template #header>
                        <div class="flex justify-center w-full font-semibold">
                            <div class="font-semibold">Activate Account</div>
                        </div>
                    </template>
                    <template #body="props">
                        <DefaultButton
                            :disabled="props.data.acticationRequest"
                            label="Activate"
                            @click="sendLinkEmail(props.data.id)"
                            class-name="!rounded-lg"
                            size="small"
                        />
                    </template>
                </Column> -->
                <Column>
                    <template #header>
                        <div class="flex justify-end w-full font-semibold">
                            <div class="font-semibold mr-2">
                                <IconSettings :size="20" />
                            </div>
                        </div>
                    </template>
                    <template #body="prop">
                        <div class="flex justify-end">
                            <Button
                                text
                                v-tooltip.top="'Options'"
                                rounded
                                size="small"
                                severity="secondary"
                                icon="pi pi-ellipsis-v"
                                @click="(e) => toggleOption(e, prop.data)"
                            />
                            <Menu ref="menu" :model="menuItems" :popup="true">
                                <template #item="{ item, props }">
                                    <a
                                        v-ripple
                                        class="flex items-center text-gray-500!"
                                        v-bind="props.action"
                                    >
                                        <div class="text-gray-400">
                                            <component
                                                :is="item.icon"
                                                size="20"
                                                stroke-width="1.5"
                                            ></component>
                                        </div>
                                        <span
                                            class="ml-2 text-xs dark:text-white"
                                            >{{ item.label }}</span
                                        >
                                        <Badge
                                            v-if="
                                                selectedRow.activationRequested &&
                                                item.label == 'Activation Link'
                                            "
                                            class="ml-auto"
                                            size="small"
                                            :value="'Sent'"
                                        />
                                    </a>
                                </template>
                            </Menu>
                        </div>
                    </template>
                </Column>
            </DefaultSelectionTable>
        </div>
        <DrawerScholar1Module v-if="drawerScholar" v-model="drawerScholar" />
    </AuthLayout>
</template>
<script setup>
import DefaultSelectionTable from "../../Components/tables/DefaultSelectionTable.vue";
import DrawerScholar1Module from "../../Modules/Others/DrawerScholar1Module.vue";
import SelectMultiInput from "../../Components/inputs/SelectMultiInput.vue";
import ManagementFilterBar from "../../Components/inputs/ManagementFilterBar.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import HeaderModule from "../../Modules/Others/HeaderModule.vue";
import AuthLayout from "../../Layouts/AuthLayout.vue";
import * as TablerIcons from "@tabler/icons-vue";

import { computed, reactive, ref, watch } from "vue";
import {
    IconRosetteDiscountCheckFilled,
    IconSettings,
    IconUsers,
} from "@tabler/icons-vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { useToast } from "primevue";
import { route } from "ziggy-js";

const toast = useToast();
const page = usePage();
const loading = reactive({
    table: false,
    request: false,
});

const menu = ref(null);
const opSchool = ref(null);
const opProgram = ref(null);
const opSub = ref(null);
const opStatus = ref(null);
const filterSchool = ref(page.props?.filterSchool ?? null);
const filterProgram = ref(null);
const filterSub = ref(null);
const filterStatus = ref(null);
const drawerScholar = ref(false);
const selectedRow = ref(null);
const searchInput = ref(page.props?.filterSearch ?? null);
const timerBounce = ref(null);
const DEBOUNCE_MS = 600;
const hasSelection = (value) =>
    Array.isArray(value) ? value.length > 0 : Boolean(value);
const activeFilterCount = computed(
    () =>
        [
            searchInput.value,
            hasSelection(filterSchool.value),
            hasSelection(filterProgram.value),
            hasSelection(filterSub.value),
            hasSelection(filterStatus.value),
        ].filter(Boolean).length,
);

const scheduleLoadPage = (pageNumber = 1) => {
    clearTimeout(timerBounce.value);
    timerBounce.value = setTimeout(() => loadPage(pageNumber), DEBOUNCE_MS);
};

const toggleOption = (event, rowData) => {
    selectedRow.value = rowData;

    menu.value.toggle(event);
};

const menuItems = computed((item) => {
    if (!selectedRow.value) return [];

    return [
        {
            label: "Activation Link",
            icon: TablerIcons.IconMailForward,
            class: "text-cyan-500",
            command: () => {
                router.post(
                    route("scholars.activation", { id: selectedRow.value.id }),
                    {},
                    {
                        onSuccess: (page) => {
                            toast.add({
                                severity: page.props.flash?.status || "success",
                                summary: page.props.flash?.title || "Success",
                                detail:
                                    page.props.flash?.message ||
                                    "Scholar activated successfully.",
                                life: 3000,
                            });
                        },
                        onError: () => {
                            toast.add({
                                severity: "error",
                                summary: "Error",
                                detail: "Failed to activate scholar.",
                                life: 3000,
                            });
                        },
                    },
                );
            },
        },
        {
            label: "Chat Support",
            icon: TablerIcons.IconMessageChatbot,
            class: "text-cyan-500",
            command: () => {
                toggleModal({
                    type: "resend",
                    data: selectedRow.value,
                });
            },
        },
    ];
});

const loadPage = (page, options = {}) => {
    router.get(
        route("scholars"),
        {
            page,
            ...(searchInput.value ? { search: searchInput.value } : {}),
            ...(filterSchool.value ? { schools: filterSchool.value } : {}),
            ...(filterProgram.value ? { programs: filterProgram.value } : {}),
            ...(filterSub.value ? { sub: filterSub.value } : {}),
            ...(filterStatus.value ? { status: filterStatus.value } : {}),
        },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ["scholars", "filterSearch", "filterSchool"],
            onBefore: () => (loading.table = true),
            onFinish: () => (loading.table = false),
        },
    );
};

const toggleScholarDetails = (event) => {
    router.reload({
        only: [
            "details",

            "programOptions",
            "subProgramOptions",
            "statusOptions",
            "termOptions",
            "yearOptions",
            "schoolOptions",
            "courseOptions",
            "curriculumOptions",
            "subjectOptions",
            "gradeOptions",
        ],
        data: { id: event.id, campus: null },
        preserveState: false,
        showProgress: true,
        replace: true,
        onFinish: () => {
            const url = new URL(window.location.href);
            url.searchParams.delete("campus");
            window.history.replaceState({}, "", url);
            drawerScholar.value = true;
        },
    });
};

const toggleOpSchool = (event) => {
    opSchool.value.toggle(event);
    if (page.props?.schoolFilter) return;

    router.reload({
        only: ["schoolFilter"],
    });
};

const schoolFilter = (event) => {
    opSchool.value.toggle(event);
    scheduleLoadPage();
};

const schoolFilterClear = (event) => {
    opSchool.value.toggle(event);
    filterSchool.value = null;
    scheduleLoadPage();
};

const toggleopProgram = (event) => {
    opProgram.value.toggle(event);
    if (page.props?.programFilter) return;

    router.reload({
        only: ["programFilter"],
    });
};

const programFilter = (event) => {
    opProgram.value.toggle(event);
    scheduleLoadPage();
};

const programFilterClear = (event) => {
    opProgram.value.toggle(event);
    filterProgram.value = null;
    scheduleLoadPage();
};

const toggleopSub = (event) => {
    opSub.value.toggle(event);
    if (page.props?.scholarTypeFilter) return;

    router.reload({
        only: ["scholarTypeFilter"],
    });
};

const subFilter = (event) => {
    opSub.value.toggle(event);
    scheduleLoadPage();
};

const subFilterClear = (event) => {
    opSub.value.toggle(event);
    filterSub.value = null;
    scheduleLoadPage();
};

const toggleopStatus = (event) => {
    opStatus.value.toggle(event);
    if (page.props?.statusFilter) return;

    router.reload({
        only: ["statusFilter"],
    });
};

const statusFilter = (event) => {
    opStatus.value.toggle(event);
    scheduleLoadPage();
};

const statusFilterClear = (event) => {
    opStatus.value.toggle(event);
    filterStatus.value = null;
    scheduleLoadPage();
};

const clearAllFilters = () => {
    searchInput.value = null;
    filterSchool.value = null;
    filterProgram.value = null;
    filterSub.value = null;
    filterStatus.value = null;
    scheduleLoadPage(1);
};

const toggleRequest = (event) => {
    scheduleLoadPage();
};

watch(
    () => searchInput.value ?? null,
    (value, oldValue) => {
        if (value === oldValue) return;

        scheduleLoadPage();
    },
);
</script>

<style scoped>
:global(.dark .scholars-page .p-datatable-header-cell),
:global(.dark .scholars-page .p-datatable-column-header-content) {
    color: #e5e7eb !important;
}

:global(.scholars-page .p-datatable-tbody > tr > td) {
    padding-top: 0.65rem;
    padding-bottom: 0.65rem;
}
</style>
