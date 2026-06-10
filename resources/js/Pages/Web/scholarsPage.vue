<template>
    <Head title="Scholars" />
    <AuthLayout>
        <div class="flex flex-col w-full h-full gap-5">
            <div class="flex flex-col lg:flex-row items-center space-x-0 gap-4">
                <HeaderModule
                    title="Scholar Management"
                    description="Comprehensive records of all scholars, including profile details, program assignments, and status monitoring."
                />
            </div>
            <div class="w-full flex items-end justify-between">
                <div class="flex items-center gap-2">
                    <IconTextInput
                        :icon="TablerIcons.IconUserSearch"
                        placeholder="Search by name or SPAS No..."
                        v-model="searchInput"
                        class="w-64 lg:w-96"
                    />
                    <div>
                        <DefaultButton
                            :icon="
                                filterSchool != null
                                    ? TablerIcons.IconFilterFilled
                                    : TablerIcons.IconFilter
                            "
                            label="Schools"
                            class-name="w-30  !rounded-xl"
                            size="small"
                            severity="secondary"
                            @click="toggleOpSchool"
                        />
                        <Popover ref="opSchool">
                            <div
                                class="gap-3 flex"
                                v-if="page.props?.schoolFilter"
                            >
                                <div class="flex-1 w-60">
                                    <SelectMultiInput
                                        filter
                                        v-model="filterSchool"
                                        :options="page.props?.schoolFilter"
                                        capitalize
                                    ></SelectMultiInput>
                                </div>

                                <div
                                    class="flex justify-end items-center gap-2"
                                >
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
                            class-name="w-30  !rounded-xl"
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

                                <div
                                    class="flex justify-end items-center gap-2"
                                >
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
                            class-name=" !rounded-xl"
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

                                <div
                                    class="flex justify-end items-center gap-2"
                                >
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
                            class-name=" !rounded-xl"
                            size="small"
                            severity="secondary"
                            @click="toggleopStatus"
                        />
                        <Popover ref="opStatus">
                            <div
                                class="gap-3 flex"
                                v-if="page.props?.statusFilter"
                            >
                                <div class="flex-1 w-60">
                                    <SelectMultiInput
                                        v-model="filterStatus"
                                        :options="page.props?.statusFilter"
                                        capitalize
                                    ></SelectMultiInput>
                                </div>

                                <div
                                    class="flex justify-end items-center gap-2"
                                >
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
                </div>

                <div class="flex items-center gap-2">
                    <ToggleButton
                        v-model="filterGradeRequest"
                        size="small"
                        :disabled="page.props?.grade_request_cnt == '0'"
                        class="!rounded-xl h-8.5"
                        @update:model-value="toggleSubjectRequest"
                    >
                        <template #default>
                            <div class="flex items-center gap-2">
                                <div class="text-xs">Grade Request</div>
                                <Badge
                                    v-if="page.props?.grade_request_cnt != '0'"
                                    :value="page.props?.grade_request_cnt"
                                    size="small"
                                    severity="danger"
                                ></Badge>
                            </div>
                        </template>
                    </ToggleButton>
                    <ToggleButton
                        v-model="filterSubjectRequest"
                        size="small"
                        :disabled="page.props?.request_cnt == '0'"
                        class="!rounded-xl h-8.5"
                        @update:model-value="toggleSubjectRequest"
                    >
                        <template #default>
                            <div class="flex items-center gap-2">
                                <div class="text-xs">Subject Request</div>
                                <Badge
                                    v-if="page.props?.request_cnt != '0'"
                                    :value="page.props?.request_cnt"
                                    size="small"
                                    severity="danger"
                                ></Badge>
                            </div>
                        </template>
                    </ToggleButton>
                    <!-- <DefaultButton
                        :icon="TablerIcons.IconPlus"
                        label="Create"
                        @click="dialogUploadScholar = true"
                        class-name="w-30  !rounded-xl"
                        size="small"
                        raised
                    /> -->
                </div>
            </div>
            <DefaultSelectionTable
                :items="page.props.scholars.data"
                :pagination="{
                    total: page.props.scholars.total,
                    perPage: page.props.scholars.per_page,
                    currentPage: page.props.scholars.current_page,
                }"
                @selected="toggleScholarDetails"
                :loading="loading.table"
                @paginate="loadPage"
            >
                <Column header="Scholars">
                    <template #body="props">
                        <div class="flex items-center gap-2">
                            <div class="">
                                <OverlayBadge
                                    severity="danger"
                                    class="inline-flex"
                                    v-if="
                                        props.data.request ||
                                        props.data.gradeRequest
                                    "
                                >
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
                                        class="!w-[40px] !h-[40px] !rounded-xl"
                                        :image="
                                            props.data.photo == null
                                                ? null
                                                : props.data.photo
                                        "
                                    />
                                </OverlayBadge>
                                <Avatar
                                    v-else
                                    :label="
                                        props.data.fullname
                                            .charAt(0)
                                            .toUpperCase()
                                    "
                                    style="
                                        background-color: #dee9fc;
                                        color: #1a2551;
                                    "
                                    class="!w-[40px] !h-[40px] !rounded-xl"
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
                                        'text-xs flex items-center',
                                        props.data.sex == 'M'
                                            ? '!text-blue-600'
                                            : '!text-rose-600',
                                    ]"
                                >
                                    <div># {{ props.data.spas_no }}</div>
                                </div>
                                <div class="flex gap-1 items-center">
                                    <div class="font-medium">
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
                        <div class="flex flex-col">
                            <div class="text-xs text-gray-400 font-light">
                                {{ props.data.course }}
                            </div>
                            <div class="">
                                {{ props.data.school }}
                            </div>
                        </div>
                    </template>
                </Column>
                <Column header="Regions">
                    <template #body="props">
                        <div class="uppercase text-xs font-medium">
                            {{ props.data.agency }}
                        </div>
                    </template>
                </Column>
                <Column>
                    <template #header>
                        <div class="flex justify-center w-full font-semibold">
                            <div class="font-semibold">Types</div>
                        </div>
                    </template>
                    <template #body="props">
                        <div class="text-center text-xs font-medium">
                            {{ props.data.type }}
                        </div>
                    </template>
                </Column>
                <Column>
                    <template #header>
                        <div class="flex justify-center w-full font-semibold">
                            <div class="font-semibold">Programs</div>
                        </div>
                    </template>
                    <template #body="props">
                        <div class="text-center text-xs font-medium">
                            {{ props.data.subProgram }}
                        </div>
                    </template>
                </Column>
                <Column>
                    <template #header>
                        <div class="flex justify-center w-full font-semibold">
                            <div class="font-semibold">Award Years</div>
                        </div>
                    </template>
                    <template #body="props">
                        <div class="text-center text-xs font-medium">
                            {{ props.data.awardyear }}
                        </div>
                    </template>
                </Column>

                <Column>
                    <template #header>
                        <div class="flex justify-center w-full font-semibold">
                            <div class="font-semibold">Status</div>
                        </div>
                    </template>
                    <template #body="props">
                        <div class="flex items-center justify-center">
                            <span
                                class="flex text-xs items-center py-1 px-3 border rounded-2xl gap-0.5"
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
                                        class="flex items-center !text-gray-500"
                                        v-bind="props.action"
                                    >
                                        <div>
                                            <component
                                                :is="item.icon"
                                                size="20"
                                                stroke-width="1.5"
                                            ></component>
                                        </div>
                                        <span class="ml-2 text-xs">{{
                                            item.label
                                        }}</span>
                                        <Badge
                                            v-if="
                                                selectedRow.personalRequest
                                                    .hasRequest &&
                                                item.label == 'Profile Request'
                                            "
                                            class="ml-auto"
                                            size="small"
                                            severity="danger"
                                            :value="
                                                selectedRow.personalRequest
                                                    .count
                                            "
                                        />

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
        <DialogScholarDetailRequest
            v-if="drawerDetailsRequest"
            v-model="drawerDetailsRequest"
        />
        <DialogScholarGradeRequest
            v-if="drawerGradeRequest"
            v-model="drawerGradeRequest"
        />
        <DrawerScholar1Module v-if="drawerScholar" v-model="drawerScholar" />
        <DialogScholarLandbankRequest
            v-if="dialogLandbankRequest"
            v-model="dialogLandbankRequest"
        />
    </AuthLayout>
</template>
<script setup>
import DialogScholarDetailRequest from "../../Modules/Others/DialogScholarDetailRequest.vue";
import DialogScholarGradeRequest from "../../Modules/Others/DialogScholarGradeRequest.vue";
import DrawerScholarRequestModule from "../../Modules/Others/DrawerScholar1Module.vue";
import DefaultSelectionTable from "../../Components/tables/DefaultSelectionTable.vue";
import DrawerScholar1Module from "../../Modules/Others/DrawerScholar1Module.vue";
import SelectMultiInput from "../../Components/inputs/SelectMultiInput.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import IconTextInput from "../../Components/inputs/IconTextInput.vue";
import HeaderModule from "../../Modules/Others/HeaderModule.vue";
import TextInput from "../../Components/inputs/TextInput.vue";
import AuthLayout from "../../Layouts/AuthLayout.vue";
import * as TablerIcons from "@tabler/icons-vue";

import { computed, onMounted, onUpdated, reactive, ref, watch } from "vue";
import {
    IconLineDashed,
    IconSettings,
    IconRosetteDiscountCheckFilled,
} from "@tabler/icons-vue";
import { Head, usePage, router } from "@inertiajs/vue3";
import { useToast } from "primevue";
import { route } from "ziggy-js";
import DialogScholarLandbankRequest from "../../Modules/Others/DialogScholarLandbankRequest.vue";

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
const filterSubjectRequest = ref(false);
const filterGradeRequest = ref(false);
const drawerDetailsRequest = ref(false);
const drawerGradeRequest = ref(false);
const drawerScholar = ref(false);
const dialogLandbankRequest = ref(false);
const selectedRow = ref(null);
const searchInput = ref(page.props?.filterSearch ?? null);
const timerBounce = ref(null);
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
            label: "Profile Request",
            icon: TablerIcons.IconUserQuestion,
            class: "text-cyan-500",
            command: () => {
                router.reload({
                    only: ["personalRequest"],
                    data: { id: selectedRow.value.id },
                    preserveState: false,
                    showProgress: true,
                    replace: true,
                    onFinish: () => {
                        drawerDetailsRequest.value = true;
                    },
                });
            },
        },
        {
            label: "Landbank Request",
            icon: TablerIcons.IconCreditCard,
            class: "text-cyan-500",
            command: () => {
                router.reload({
                    only: ["landbankRequest"],
                    data: { id: selectedRow.value.id },
                    preserveState: false,
                    showProgress: true,
                    replace: true,
                    onFinish: () => {
                        dialogLandbankRequest.value = true;
                    },
                });
            },
        },
        {
            label: "Subjects & Grades Request",
            icon: TablerIcons.IconId,
            class: "text-cyan-500",
            command: () => {
                router.reload({
                    only: ["gradeRequest"],
                    data: { id: selectedRow.value.id },

                    preserveState: false,
                    showProgress: true,
                    replace: true,
                    onFinish: () => {
                        drawerGradeRequest.value = true;
                    },
                });
            },
        },
        {
            separator: true,
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

const loadPage = (page) => {
    router.get(
        route("scholars"),
        {
            page,
            ...(searchInput.value ? { search: searchInput.value } : {}),
            ...(filterSchool.value ? { schools: filterSchool.value } : {}),
            ...(filterProgram.value ? { programs: filterProgram.value } : {}),
            ...(filterSub.value ? { sub: filterSub.value } : {}),
            ...(filterStatus.value ? { status: filterStatus.value } : {}),
            ...(filterSubjectRequest.value
                ? { subjectRequest: filterSubjectRequest.value }
                : {}),
            ...(filterGradeRequest.value
                ? { gradeRequest: filterGradeRequest.value }
                : {}),
        },
        {
            preserveState: true,
            preserveScroll: true,
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
    router.reload({
        only: ["schoolFilter"],
    });
};

const schoolFilter = (event) => {
    opSchool.value.toggle(event);
    clearTimeout(timerBounce.value);
    timerBounce.value = setTimeout(() => {
        loadPage(1);
    }, 300);
};

const schoolFilterClear = (event) => {
    opSchool.value.toggle(event);
    filterSchool.value = null;
    clearTimeout(timerBounce.value);
    timerBounce.value = setTimeout(() => {
        loadPage(1);
    }, 300);
};

const toggleopProgram = (event) => {
    opProgram.value.toggle(event);
    router.reload({
        only: ["programFilter"],
    });
};

const programFilter = (event) => {
    opProgram.value.toggle(event);
    clearTimeout(timerBounce.value);
    timerBounce.value = setTimeout(() => {
        loadPage(1);
    }, 300);
};

const programFilterClear = (event) => {
    opProgram.value.toggle(event);
    filterProgram.value = null;
    clearTimeout(timerBounce.value);
    timerBounce.value = setTimeout(() => {
        loadPage(1);
    }, 300);
};

const toggleopSub = (event) => {
    opSub.value.toggle(event);
    router.reload({
        only: ["scholarTypeFilter"],
    });
};

const subFilter = (event) => {
    opSub.value.toggle(event);
    clearTimeout(timerBounce.value);
    timerBounce.value = setTimeout(() => {
        loadPage(1);
    }, 300);
};

const subFilterClear = (event) => {
    opSub.value.toggle(event);
    filterSub.value = null;
    clearTimeout(timerBounce.value);
    timerBounce.value = setTimeout(() => {
        loadPage(1);
    }, 300);
};

const toggleopStatus = (event) => {
    opStatus.value.toggle(event);
    router.reload({
        only: ["statusFilter"],
    });
};

const statusFilter = (event) => {
    opStatus.value.toggle(event);
    clearTimeout(timerBounce.value);
    timerBounce.value = setTimeout(() => {
        loadPage(1);
    }, 300);
};

const statusFilterClear = (event) => {
    opStatus.value.toggle(event);
    filterStatus.value = null;
    clearTimeout(timerBounce.value);
    timerBounce.value = setTimeout(() => {
        loadPage(1);
    }, 300);
};

const toggleSubjectRequest = (event) => {
    clearTimeout(timerBounce.value);
    timerBounce.value = setTimeout(() => {
        loadPage(1);
    }, 300);
};

watch(
    () => searchInput.value ?? null,
    () => {
        clearTimeout(timerBounce.value);
        timerBounce.value = setTimeout(() => {
            loadPage(1);
        }, 300);
    },
);
</script>
