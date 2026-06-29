<template>
    <Head title="Schools" />
    <AuthLayout>
        <div class="flex flex-col w-full h-full gap-5">
            <div class="flex">
                <HeaderModule
                    title="Schools"
                    description="Manage and organize all registered schools, including details, classifications, and campus information."
                />
                <div class="flex items-center"></div>
            </div>
            <div class="flex-1 flex flex-col gap-2">
                <ToolbarModule
                    v-model="searchInput"
                    @deleteSearch="clearSearch"
                    @saveForm="submitForm"
                    button-label="Create"
                    :dialog-title="
                        !universityForm.id
                            ? 'Create School & Campus'
                            : 'Edit School & Campus'
                    "
                    dialog-description="Create a new school with its campus structure or modify existing school and campus information."
                    :dialog-button-loading="universityForm.processing"
                    :dialog-icon="IconSchool"
                    dialog-button-label="Save"
                    :message-has-errors="universityForm.hasErrors"
                    :message-errors="universityForm.errors"
                    @buttonOpenModal="toggleModal({ type: 'create' })"
                    message-type="error"
                    :button-visible="canManageSchools"
                    ref="toolbarRef"
                >
                    <template #form>
                        <div class="flex flex-col gap-3 mb-2">
                            <TextInput
                                v-model="universityForm.name"
                                :error-mark="universityForm.errors.name"
                                label="Name"
                                capitalize
                            ></TextInput>

                            <TextInput
                                v-model="universityForm.abbreviation"
                                :error-mark="universityForm.errors.abbreviation"
                                label="Abbreviation"
                            ></TextInput>
                            <SelectInput
                                v-model="universityForm.class"
                                :options="page.props.classOption"
                                :error-mark="universityForm.errors.class"
                                label="Class"
                                clearable
                            >
                            </SelectInput>
                        </div>
                        <Divider type="dashed" />
                        <div class="overflow-y-auto max-h-110 px-2">
                            <div class="flex items-center justify-between">
                                <div class="font-semibold">Campus</div>
                            </div>
                            <div
                                class="flex flex-col gap-2 mt-4"
                                v-for="(item, index) in universityForm.campuses"
                                :key="index"
                            >
                                <div class="flex justify-between items-center">
                                    <div
                                        class="text-xs bg-blue-100 dark:text-gray-700 w-fit font-semibold px-2 py-1 rounded-lg"
                                    >
                                        <span> {{ index + 1 }}#</span>
                                        Campus
                                    </div>

                                    <Button
                                        v-show="
                                            index != 0 &&
                                            (hideRemoveButton == 'create' ||
                                                universityForm.campuses[index]
                                                    .id == null)
                                        "
                                        size="small"
                                        class="!text-xs"
                                        rounded
                                        severity="danger"
                                        outlined
                                        @click="removeCampus(index)"
                                    >
                                        <div class="flex items-center gap-2">
                                            <IconCircleXFilled
                                                size="20"
                                            ></IconCircleXFilled>
                                            <div>Remove</div>
                                        </div>
                                    </Button>
                                </div>
                                <div class="flex flex-col gap-3">
                                    <TextInput
                                        v-model="
                                            universityForm.campuses[index].name
                                        "
                                        label="Name"
                                        placeholder="(Optional)"
                                        capitalize
                                    ></TextInput>
                                    <div class="flex items-center gap-3">
                                        <SelectInput
                                            v-model="
                                                universityForm.campuses[index]
                                                    .semester
                                            "
                                            :error-mark="
                                                universityForm.errors[
                                                    `campuses.${index}.semester`
                                                ]
                                            "
                                            :options="
                                                page.props.classificationOption
                                            "
                                            label="Academic Term"
                                            clearable
                                        >
                                        </SelectInput>
                                        <SelectInput
                                            v-model="
                                                universityForm.campuses[index]
                                                    .grading
                                            "
                                            :error-mark="
                                                universityForm.errors[
                                                    `campuses.${index}.grading`
                                                ]
                                            "
                                            :options="page.props.gradingOption"
                                            label="Grading System"
                                            clearable
                                        >
                                        </SelectInput>
                                    </div>
                                    <SelectInput
                                        v-model="
                                            universityForm.campuses[index]
                                                .agency
                                        "
                                        :error-mark="
                                            universityForm.errors[
                                                `campuses.${index}.agency`
                                            ]
                                        "
                                        :options="page.props.agencyOption"
                                        label="Regional Office"
                                        clearable
                                    >
                                    </SelectInput>
                                    <TextInput
                                        v-model="
                                            universityForm.campuses[index]
                                                .street
                                        "
                                        label="Address"
                                        placeholder="Street"
                                        capitalize
                                    ></TextInput>

                                    <AutoCompleteInput
                                        v-model="
                                            universityForm.campuses[index]
                                                .address
                                        "
                                        :error-mark="
                                            universityForm.errors[
                                                `campuses.${index}.address`
                                            ]
                                        "
                                        :options="page.props.resultSearch"
                                        placeholder="Find by Barangay, City, Province, or Region"
                                        @complete="autoSearch"
                                        selection
                                    ></AutoCompleteInput>
                                    <div
                                        class="flex justify-end gap-3 items-center"
                                    >
                                        <div class="text-sm font-semibold">
                                            Is main campus?
                                        </div>

                                        <DefaultToggle
                                            v-model="
                                                universityForm.campuses[index]
                                                    .main
                                            "
                                            :check-icon="IconCheck"
                                            :un-check-icon="IconX"
                                            @change="
                                                removeMainStatus({
                                                    key: index,
                                                    item: universityForm
                                                        .campuses[index].main,
                                                })
                                            "
                                        />
                                    </div>
                                </div>
                                <Divider type="dashed" />
                            </div>
                            <Button
                                size="small"
                                class="!text-xs !rounded-xl"
                                fluid
                                @click="addCampus"
                            >
                                <div class="flex items-center gap-2">
                                    <IconCirclePlusFilled
                                        size="18"
                                    ></IconCirclePlusFilled>
                                    <div>Add Campus</div>
                                </div>
                            </Button>
                        </div>
                    </template>
                </ToolbarModule>
                <DefaultTable
                    :items="page.props.universities.data"
                    :pagination="{
                        total: page.props.universities.total,
                        perPage: page.props.universities.per_page,
                        currentPage: page.props.universities.current_page,
                    }"
                    group-rows-by="school.name"
                    row-group-mode="subheader"
                    @paginate="loadPage"
                >
                    <template #groupheader="slotProps">
                        <div class="flex w-full items-center justify-between">
                            <div class="flex items-center gap-2">
                                <div class="">
                                    <Avatar
                                        v-if="
                                            slotProps.data.school.photo == null
                                        "
                                        :label="
                                            slotProps.data.school.name
                                                .charAt(0)
                                                .toUpperCase()
                                        "
                                        style="
                                            background-color: #dee9fc;
                                            color: #1a2551;
                                        "
                                        shape="circle"
                                        class="!w-[40px] !h-[40px]"
                                    />

                                    <Avatar
                                        v-else
                                        style="
                                            background-color: #dee9fc;
                                            color: #1a2551;
                                        "
                                        shape="circle"
                                        :image="page.props.user.avatar"
                                    />
                                </div>

                                <div class="flex flex-col">
                                    <div class="font-bold capitalize">
                                        {{ slotProps.data.school.name }}
                                    </div>
                                    <div class="text-xs">
                                        Shortcut :
                                        <span class="font-semibold">{{
                                            slotProps.data.school.shortcut
                                        }}</span>
                                        | Classification:
                                        <span class="font-semibold">{{
                                            slotProps.data.school.reference[
                                                "name"
                                            ]
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="flex justify-end">
                                <Button
                                    text
                                    v-tooltip.top="'Options'"
                                    rounded
                                    size="small"
                                    severity="secondary"
                                    icon="pi pi-ellipsis-v"
                                    @click="
                                        (e) => toggleOption(e, slotProps.data)
                                    "
                                />

                                <Menu
                                    ref="menu"
                                    :model="menuItems"
                                    :popup="true"
                                >
                                    <template #item="{ item, props }">
                                        <a
                                            v-ripple
                                            class="flex items-center"
                                            v-bind="props.action"
                                        >
                                            <div>
                                                <component
                                                    :is="item.icon"
                                                    :class="item.class"
                                                    size="20"
                                                    stroke-width="1.5"
                                                ></component>
                                            </div>
                                            <span class="ml-2 text-xs">{{
                                                item.label
                                            }}</span>
                                        </a>
                                    </template>
                                </Menu>
                            </div>
                        </div>
                    </template>
                    <Column
                        field="school.name"
                        header="Representative"
                    ></Column>
                    <Column header="Name">
                        <template #body="prop">
                            <div class="flex items-center gap-2">
                                <div class="capitalize">
                                    {{
                                        prop.data.name ??
                                        prop.data.address.municipality.name
                                    }}
                                </div>

                                <div
                                    v-show="prop.data.is_main"
                                    class="text-[10px] font-semibold bg-rose-100 px-2 rounded-lg text-red-700"
                                >
                                    MAIN
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column header="Term">
                        <template #body="prop">
                            <div class="text-xs">
                                {{ prop.data.term }}
                            </div>
                        </template>
                    </Column>
                    <Column header="Grading">
                        <template #body="prop">
                            <div class="text-xs">
                                {{ prop.data.grading }}
                            </div>
                        </template>
                    </Column>
                    <Column header="Regional Office">
                        <template #body="prop">
                            <div
                                :class="[
                                    'text-xs',
                                    prop.data.agency ==
                                        page.props.user.profile.agency_array
                                            .name &&
                                    page.props.user.role_array['name'] ==
                                        'regional staff'
                                        ? 'font-semibold '
                                        : 'text-gray-400',
                                ]"
                            >
                                {{ prop.data.agency }}
                            </div>
                        </template>
                    </Column>
                    <Column header="Coordinators">
                        <template #body="props">
                            <div v-if="props.data.coordinators?.length != 0">
                                <AvatarGroup>
                                    <Avatar
                                        v-for="coordinator in props.data.coordinators.slice(
                                            0,
                                            5,
                                        )"
                                        :key="coordinator"
                                        :label="
                                            coordinator.charAt(0).toUpperCase()
                                        "
                                        v-tooltip.top="coordinator"
                                        shape="circle"
                                        size="small"
                                        :style="{
                                            backgroundColor: '#ece9fc',
                                            color: '#2a1261',
                                        }"
                                    />

                                    <Avatar
                                        v-if="
                                            props.data.coordinators.length > 5
                                        "
                                        :label="`+${
                                            props.data.coordinators.length - 5
                                        }`"
                                        shape="circle"
                                        size="small"
                                        :style="{
                                            backgroundColor: '#d3d3d3',
                                            color: '#333',
                                            fontWeight: 'bold',
                                        }"
                                    />
                                </AvatarGroup>
                            </div>
                            <div v-else>
                                <span
                                    class="text-gray-400 font-light text-[12px] italic"
                                    >No assign coordinators</span
                                >
                            </div>
                        </template>
                    </Column>
                    <Column>
                        <template #header>
                            <div
                                class="flex justify-center font-semibold w-full"
                            >
                                <div>Active Semester</div>
                            </div>
                        </template>
                        <template #body="prop">
                            <div
                                v-if="prop.data.semester"
                                class="flex justify-center"
                                v-tooltip.top="
                                    prop.data.semester.acad_term.name
                                "
                            >
                                <div class="flex items-center gap-1 text-xs">
                                    <IconPointFilled class="text-green-600" />
                                    <span class="font-medium">{{
                                        prop.data.semester.start_date
                                    }}</span>

                                    <IconArrowNarrowRight
                                        class="text-gray-400"
                                    />
                                    <span class="font-medium">{{
                                        prop.data.semester.end_date
                                    }}</span>
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column>
                        <template #header>
                            <div
                                class="flex justify-center font-semibold w-full"
                            >
                                <div>Submission Date</div>
                            </div>
                        </template>
                        <template #body="prop">
                            <div
                                v-if="prop.data.semester"
                                class="flex items-center gap-1 justify-center"
                            >
                                <IconCalendar class="text-rose-500" />
                                <span class="text-xs font-medium">
                                    {{ prop.data.semester.submission_date }}
                                </span>
                            </div>
                        </template>
                    </Column>

                    <Column field="arrow">
                        <template #body="prop">
                            <div class="flex justify-end">
                                <DefaultButton
                                    size="small"
                                    rounded
                                    class-name="!p-0 !m-0"
                                    :icon="IconArrowRight"
                                    :icon-size="18"
                                    @click="openDrawer(prop)"
                                    text
                                    severity="secondary"
                                />
                            </div>
                        </template>
                    </Column>
                </DefaultTable>
            </div>
        </div>

        <DrawerSchoolModule
            ref="drawerRef"
            :id="drawerId"
            :course-option="page.props.courseOption"
            :sub-class-option="page.props.subClassOption"
            :confirm-ref="confirmRef"
        ></DrawerSchoolModule>
        <DefaultConfirmDialog ref="confirmRef" />
        <DefaultToast ref="toastRef" />
    </AuthLayout>
</template>
<script setup>
import AuthLayout from "../../Layouts/AuthLayout.vue";
import HeaderModule from "../../Modules/Others/HeaderModule.vue";
import DefaultTable from "../../Components/tables/DefaultTable.vue";
import ToolbarModule from "../../Modules/Others/ToolbarModule.vue";
import TextInput from "../../Components/inputs/TextInput.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import AutoCompleteInput from "../../Components/inputs/AutoCompleteInput.vue";
import DefaultToast from "../../Components/messages/DefaultToast.vue";
import DefaultConfirmDialog from "../../Components/dialogs/DefaultConfirmDialog.vue";
import SelectInput from "../../Components/inputs/SelectInput.vue";
import DrawerSchoolModule from "../../Modules/Others/DrawerSchoolModule.vue";
import DefaultToggle from "../../Components/toggleswitches/DefaultToggle.vue";
import { usePermissions } from "../../Composables/usePermissions";
import { computed, ref, watch } from "vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import {
    IconPencilCog,
    IconTrash,
    IconCirclePlusFilled,
    IconCircleXFilled,
    IconArrowRight,
    IconCheck,
    IconX,
    IconSchool,
    IconEdit,
    IconArrowNarrowRight,
    IconPointFilled,
    IconCalendar,
} from "@tabler/icons-vue";

const page = usePage();
const searchInput = ref(null);
const dataDrawer = ref(null);
const drawerId = ref(null);
const timerBounce = ref(null);
const hideRemoveButton = ref("create");
const selectedRow = ref(null);
const toolbarRef = ref(null);
const toastRef = ref(null);
const confirmRef = ref(null);
const drawerRef = ref(false);
const menu = ref(null);

const { can } = usePermissions();
const canManageSchools = computed(() => can("schools.manage"));
const universityForm = useForm({
    id: null,
    name: null,
    abbreviation: null,
    class: null,
    campuses: [
        {
            id: null,
            name: null,
            semester: null,
            startDate: null,
            endDate: null,
            grading: null,
            agency: page.props.user.profile.agency_array ?? null,
            street: null,
            address: null,
            main: true,
        },
    ],
    isActive: false,
});

const addCampus = () => {
    universityForm.campuses.push({
        main: false,
        name: null,
        startDate: null,
        endDate: null,
        semester: null,
        agency: page.props.user.profile.agency_array ?? null,
        grading: null,
        street: null,
        address: null,
    });
};

const removeCampus = (index) => {
    if (index == 0) {
        return;
    }
    universityForm.campuses.splice(index, 1);
};

const toggleOption = (event, rowData) => {
    selectedRow.value = rowData;
    menu.value.toggle(event);
};

const openDrawer = (res) => {
    dataDrawer.value = res.data;
    drawerId.value = res.data.id;
    const id = Number(res.data.id);
    const semesterType = res.data?.term ?? null;

    router.reload({
        data: JSON.parse(JSON.stringify({ id, semesterType })),
        only: [
            "schoolDetail",
            "semesterOption",
            "subClassOption",
            "regionAccess",
        ],
        replace: true,
        onSuccess: () => {
            if (page.props.regionAccess) {
                drawerRef.value.openDrawer();
            } else {
                toastRef.value.show({
                    status: "warn",
                    title: "Region Access Locked",
                    message:
                        "Sorry, you are not authorized to access or edit campuses outside your region.",
                });
            }
        },
    });
};

const menuItems = computed(() => {
    if (!selectedRow.value || !canManageSchools.value) return [];

    return [
        {
            label: "Edit",
            icon: IconPencilCog,
            class: "text-blue-500",
            command: () => {
                toggleModal({
                    type: "edit",
                    data: selectedRow.value,
                });
            },
        },
        {
            label: "Delete",
            icon: IconTrash,
            class: "text-red-500",
            command: () => {
                deleteRow(selectedRow.value.id);
            },
        },
    ];
});

const toggleModal = (res) => {
    if (!canManageSchools.value) return;

    universityForm.resetAndClearErrors();
    hideRemoveButton.value = res.type;

    if (res.type === "edit") {
        router.reload({
            data: { school_id: res.data.school.id },
            only: ["schoolEdit"],
            preserveState: true,
            preserveScroll: true,
            onSuccess: () => {
                universityForm.campuses = [];
                universityForm.id = page.props.schoolEdit?.id;
                universityForm.name = page.props.schoolEdit?.name;
                universityForm.abbreviation = page.props.schoolEdit?.shortcut;
                universityForm.class = page.props.schoolEdit?.reference_array;
                page.props.schoolEdit?.campuses
                    .sort((a, b) => b.is_main - a.is_main)
                    .forEach((element) => {
                        universityForm.campuses.push({
                            id: element.id,
                            semester: element.term_array,
                            grading: element.grading_array,
                            agency: element.agency_array,
                            startDate: element.start_date
                                ? new Date(element.start_date)
                                : null,
                            endDate: element.end_date
                                ? new Date(element.end_date)
                                : null,
                            name: element.name,
                            street: element.address.address,
                            address: element.address.full_address,
                            main: element.is_main,
                        });
                    });

                toolbarRef.value.openModal();
            },
        });
    } else {
        toolbarRef.value.openModal();
    }
};

const deleteRow = () => {
    toastRef.value.show({
        status: "info",
        title: "Unable to delete school",
        message:
            "You don’t have permission to access or modify campuses outside your assigned region.",
    });
};

const submitForm = () => {
    if (!canManageSchools.value) return;

    if (!universityForm.id) {
        universityForm.post(route("academic.universities.store"), {
            preserveState: true,
            onSuccess: () => {
                universityForm.resetAndClearErrors();
                toastRef.value.show(page.props.flash);
            },
        });
    } else {
        universityForm.put(
            route("academic.universities.update", {
                id: universityForm.id,
                type: "form",
            }),
            {
                onSuccess: () => {
                    toolbarRef.value.closeModal();
                    universityForm.resetAndClearErrors();
                    toastRef.value.show(page.props.flash);
                },
            },
        );
    }
};
// const updateStatus = (result) => {
//     universityForm.isActive = result.is_active;
//     universityForm.put(
//         route("academic.courses.update", { id: result.id, type: "status" }),
//         {
//             onSuccess: () => {
//                 toastRef.value.show(page.props.flash);
//             },
//         }
//     );
// };

const removeMainStatus = (res) => {
    if (!res.item) return;

    universityForm.campuses.forEach((el, k) => {
        if (res.key != k) {
            universityForm.campuses[k].main = false;
        }
    });
};

const clearSearch = () => {
    searchInput.value = null;
};

const autoSearch = (event) => {
    router.get(
        route("academic.universities"),
        { autosuggest: event },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ["resultSearch"],
        },
    );
};
const loadPage = (page) => {
    router.get(
        route("academic.universities"),
        {
            page,
            search: searchInput.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

watch(
    () => searchInput.value,
    () => {
        clearTimeout(timerBounce.value);
        timerBounce.value = setTimeout(() => {
            loadPage(1);
        }, 300);
    },
);
watch(
    () => universityForm.campuses.map((c) => c.main),
    (newVal) => {
        const trueIndex = newVal.indexOf(true); // which toggle was turned ON?

        if (trueIndex === -1) return; // nothing selected = exit

        universityForm.campuses.forEach((campus, i) => {
            // turn OFF all except the one selected
            if (i !== trueIndex) campus.main = false;
        });
    },
);
</script>
