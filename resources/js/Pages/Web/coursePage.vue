<template>
    <Head title="Courses" />
    <AuthLayout>
        <div class="courses-page flex h-full min-h-0 w-full flex-col gap-4 overflow-hidden text-slate-800 dark:text-gray-100">
            <div class="shrink-0 border-b border-slate-200 pb-4 dark:border-gray-700">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="flex min-w-0 items-start gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-md bg-blue-600 text-white shadow-sm dark:bg-blue-500">
                            <IconBook2 :size="21" stroke-width="1.8" />
                        </div>
                        <HeaderModule
                            title="Course Management"
                            description="Manage course names, categories, abbreviations, and availability."
                        />
                    </div>
                    <DefaultButton
                        size="small"
                        label="Create Course"
                        severity="secondary"
                        outlined
                        :icon="IconCirclePlusFilled"
                        class="self-start xl:self-auto"
                        @click="toggleModal({ type: 'create' })"
                    />
                </div>
            </div>
            <div class="flex min-h-0 flex-1 flex-col gap-3 overflow-auto">
                <ToolbarModule
                    v-model="searchInput"
                    @deleteSearch="clearSearch"
                    @saveForm="submitForm"
                    button-label="Create"
                    :dialog-title="
                        !courseForm.id ? 'Create Course' : 'Edit Course'
                    "
                    dialog-description="Define a new course and manage its information, content, and availability."
                    :dialog-button-loading="courseForm.processing"
                    :dialog-icon="IconUserCog"
                    dialog-button-label="Save"
                    :message-has-errors="courseForm.hasErrors"
                    :message-errors="courseForm.errors"
                    @buttonOpenModal="toggleModal({ type: 'create' })"
                    message-type="error"
                    :button-visible="false"
                    ref="toolbarRef"
                >
                    <template #form>
                        <div class="flex flex-col gap-3 mt-5">
                            <TextInput
                                v-model="courseForm.name"
                                label="Name"
                            ></TextInput>
                            <AutoCompleteInput
                                label="Category"
                                v-model="courseForm.field"
                                @complete="autoSearch"
                                :options="suggestions"
                            ></AutoCompleteInput>
                            <TextInput
                                v-model="courseForm.abbreviation"
                                label="Abbreviation"
                            ></TextInput>
                        </div>
                    </template>
                </ToolbarModule>
                <DefaultTable
                    :items="page.props.courses.data"
                    :pagination="{
                        total: page.props.courses.total,
                        perPage: page.props.courses.per_page,
                        currentPage: page.props.courses.current_page,
                    }"
                    @paginate="loadPage"
                >
                    <Column header="Course" class="w-[40%]">
                        <template #body="props">
                            <div class="min-w-60 py-1">
                                <div class="text-sm font-semibold capitalize text-slate-800 dark:text-gray-100">
                                    {{ props.data.name }}
                                </div>
                                <div class="mt-0.5 text-xs uppercase text-slate-500 dark:text-gray-400">
                                    {{ props.data.abbreviation || "No abbreviation" }}
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column header="Category" class="w-[35%]">
                        <template #body="props">
                            <span class="text-sm capitalize text-slate-700 dark:text-gray-300">
                                {{ props.data.field || "Uncategorized" }}
                            </span>
                        </template>
                    </Column>
                    <Column field="status" class="w-[5%]">
                        <template #header>
                            <div class="w-full flex justify-center">
                                <p class="font-semibold">Status</p>
                            </div>
                        </template>
                        <template #body="props">
                            <div
                                class="flex items-center justify-center w-full"
                            >
                                <DefaultToggle
                                    :check-icon="IconCheck"
                                    :un-check-icon="IconX"
                                    v-model="props.data.is_active"
                                    :disabled="props.data.is_lock"
                                    v-if="!props.data.is_lock"
                                    @update-value="updateStatus(props.data)"
                                />
                                <div
                                    v-else
                                    v-tooltip.top="'This course cannot be deactivated.'"
                                >
                                    <IconLock size="18" />
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column field="options" class="w-[5%]">
                        <template #body="prop">
                            <div class="flex w-full justify-end">
                                <Button
                                    text
                                    v-tooltip.top="'Options'"
                                    rounded
                                    size="small"
                                    severity="secondary"
                                    icon="pi pi-ellipsis-v"
                                    @click="(e) => toggleOption(e, prop.data)"
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
                        </template>
                    </Column>
                </DefaultTable>
            </div>
        </div>
        <DefaultToast ref="toastRef" />
        <DefaultConfirmDialog ref="confirmRef" />
    </AuthLayout>
</template>
<script setup>
import AuthLayout from "../../Layouts/AuthLayout.vue";
import HeaderModule from "../../Modules/Others/HeaderModule.vue";
import DefaultTable from "../../Components/tables/DefaultTable.vue";
import ToolbarModule from "../../Modules/Others/ToolbarModule.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import TextInput from "../../Components/inputs/TextInput.vue";
import DefaultToggle from "../../Components/toggleswitches/DefaultToggle.vue";
import DefaultToast from "../../Components/messages/DefaultToast.vue";
import DefaultConfirmDialog from "../../Components/dialogs/DefaultConfirmDialog.vue";
import AutoCompleteInput from "../../Components/inputs/AutoCompleteInput.vue";
import { computed, ref, watch } from "vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import {
    IconCheck,
    IconLock,
    IconUserCog,
    IconX,
    IconPencilCog,
    IconTrash,
    IconBook2,
    IconCirclePlusFilled,
} from "@tabler/icons-vue";

const page = usePage();
const searchInput = ref(null);
const timerBounce = ref(null);
const selectedRow = ref(null);
const toolbarRef = ref(null);
const toastRef = ref(null);
const confirmRef = ref(null);
const menu = ref(null);
const suggestions = ref(null);
const courseForm = useForm({
    id: null,
    name: null,
    field: null,
    abbreviation: null,
    isActive: false,
});

const toggleOption = (event, rowData) => {
    selectedRow.value = rowData;
    menu.value.toggle(event);
};

const menuItems = computed(() => {
    if (!selectedRow.value) return [];

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
    courseForm.resetAndClearErrors();

    if (res.type === "edit") {
        courseForm.id = res.data.id;
        courseForm.name = res.data.name;
        courseForm.abbreviation = res.data.abbreviation;

        courseForm.field = res.data.suggestion_array;
    }

    toolbarRef.value.openModal();
};

const deleteRow = (id) => {
    if (page.props.user?.role_array["name"] != "Administrator") {
        toastRef.value.show({
            status: "warn",
            title: "Access Denied",
            message:
                "You are not authorized to view or delete course information.",
        });
        return;
    }
    confirmRef.value.popupDialog(() => {
        courseForm.delete(
            route("academic.courses.destroy", { id: id, type: "delete" }),
            {
                onSuccess: () => {
                    courseForm.resetAndClearErrors();
                    toastRef.value.show(page.props.flash);
                },
            },
        );
    });
};

const submitForm = () => {
    if (!courseForm.id) {
        courseForm.post(route("academic.courses.store"), {
            onSuccess: () => {
                courseForm.resetAndClearErrors();
                toastRef.value.show(page.props.flash);
            },
        });
    } else {
        courseForm.put(
            route("academic.courses.update", {
                id: courseForm.id,
                type: "form",
            }),
            {
                onSuccess: () => {
                    toolbarRef.value.closeModal();
                    courseForm.resetAndClearErrors();
                    toastRef.value.show(page.props.flash);
                },
            },
        );
    }
};
const updateStatus = (result) => {
    courseForm.isActive = result.is_active;
    courseForm.put(
        route("academic.courses.update", { id: result.id, type: "status" }),
        {
            onSuccess: () => {
                toastRef.value.show(page.props.flash);
            },
        },
    );
};

const clearSearch = () => {
    searchInput.value = null;
};

const autoSearch = (event) => {
    setTimeout(() => {
        if (!event.trim().length) {
            suggestions.value = [...page.props.categories];
        } else {
            suggestions.value = page.props.categories.filter((search) => {
                return search.name.toLowerCase().includes(event.toLowerCase());
            });
        }
    }, 250);
};

const loadPage = (page) => {
    router.get(
        route("academic.courses"),
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
</script>

<style scoped>
:global(.dark .courses-page .p-datatable-header-cell),
:global(.dark .courses-page .p-datatable-column-header-content) {
    color: #e5e7eb !important;
}

:global(.courses-page .p-datatable-tbody > tr > td) {
    padding-top: 0.65rem;
    padding-bottom: 0.65rem;
}
</style>
