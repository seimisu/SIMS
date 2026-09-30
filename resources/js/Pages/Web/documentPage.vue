<template>
    <Head title="Downloadables" />
    <AuthLayout>
        <DefaultConfirmDialog ref="confirmRef" />
        <div class="downloadables-page flex h-full min-h-0 w-full flex-col gap-4 overflow-hidden text-slate-800 dark:text-gray-100">
            <div class="shrink-0 border-b border-slate-200 pb-4 dark:border-gray-700">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="flex min-w-0 items-start gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-md bg-blue-600 text-white shadow-sm dark:bg-blue-500">
                            <IconFileDownload :size="21" stroke-width="1.8" />
                        </div>
                        <HeaderModule
                            title="Downloadables"
                            description="Manage downloadable files, categories, and audience availability."
                        />
                    </div>
                    <DefaultButton
                        size="small"
                        severity="secondary"
                        outlined
                        :label="activeTab === 'documents' ? 'Upload Downloadable' : 'Create Category'"
                        :icon="activeTab === 'documents' ? IconFileUpload : IconCategory"
                        class="self-start xl:self-auto"
                        @click="activeTab === 'documents' ? openDocumentForm() : openCategoryForm()"
                    />
                </div>
            </div>

            <div class="flex min-h-0 flex-1 flex-col gap-3 overflow-auto">
                <div class="flex items-center gap-1 border-b border-slate-200 dark:border-gray-700">
                    <button
                        v-for="tab in tabs"
                        :key="tab.value"
                        type="button"
                        :class="[
                            'border-b-2 px-4 py-2 text-sm font-semibold transition-colors',
                            activeTab === tab.value
                                ? 'border-blue-600 text-blue-600 dark:border-blue-400 dark:text-blue-300'
                                : 'border-transparent text-slate-500 hover:text-slate-800 dark:text-gray-400 dark:hover:text-gray-100',
                        ]"
                        @click="activeTab = tab.value"
                    >
                        {{ tab.label }}
                    </button>
                </div>

                <div v-show="activeTab === 'documents'" class="flex flex-col gap-3">
                            <ToolbarModule
                                v-model="searchInput"
                                button-label="Upload"
                                :dialog-title="documentForm.id ? 'Edit Downloadable' : 'Upload Downloadable'"
                                dialog-description="Set the file details and who can download it."
                                dialog-button-label="Save"
                                :dialog-icon="IconFileUpload"
                                :dialog-button-loading="documentForm.processing"
                                :message-has-errors="documentForm.hasErrors"
                                :message-errors="documentForm.errors"
                                message-type="error"
                                :button-visible="false"
                                @deleteSearch="clearSearch"
                                @buttonOpenModal="openDocumentForm()"
                                @saveForm="saveDocument"
                                ref="documentToolbarRef"
                            >
                                <template #form>
                                    <div class="flex flex-col gap-3 mt-5">
                                        <TextInput v-model="documentForm.title" label="Downloadable Name" />
                                        <div class="flex flex-col">
                                            <span class="text-sm font-medium">Description</span>
                                            <Textarea v-model="documentForm.description" rows="3" autoResize fluid />
                                        </div>
                                        <SelectInput
                                            v-model="documentForm.category"
                                            label="Category"
                                            :options="page.props.categoryOptions"
                                            :clearable="true"
                                        />
                                        <UploadInput
                                            ref="uploadRef"
                                            accept=".pdf,.doc,.docx,.xls,.xlsx,.csv,.jpg,.jpeg,.png"
                                            :progress="progressUpload"
                                            @select-files="handleFile"
                                            @remove-file="clearFile"
                                        />
                                        <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                                            <div class="flex items-center justify-between rounded-lg border px-3 py-2">
                                                <span class="text-sm font-medium">Active</span>
                                                <DefaultToggle
                                                    v-model="documentForm.is_active"
                                                    :check-icon="IconCheck"
                                                    :un-check-icon="IconX"
                                                />
                                            </div>
                                            <div class="flex items-center justify-between rounded-lg border px-3 py-2">
                                                <span class="text-sm font-medium">Published</span>
                                                <DefaultToggle
                                                    v-model="documentForm.publish_now"
                                                    :check-icon="IconCheck"
                                                    :un-check-icon="IconX"
                                                />
                                            </div>
                                        </div>

                                        <Divider />
                                        <LibraryAccessInput
                                            v-model:available-all="documentForm.available_all"
                                            v-model:region-scopes="documentForm.region_scopes"
                                            v-model:scholarships="documentForm.scholarships"
                                            v-model:programs="documentForm.programs"
                                            :region-options="page.props.regionOptions"
                                            :school-options="page.props.schoolOptions"
                                            :scholarship-options="page.props.scholarshipOptions"
                                            :program-options="page.props.programOptions"
                                            :region-locked="isRegionLocked"
                                            :locked-region="lockedRegion"
                                        />
                                    </div>
                                </template>
                            </ToolbarModule>

                            <DefaultTable
                                :items="page.props.documents.data"
                                :pagination="{
                                    total: page.props.documents.total,
                                    perPage: page.props.documents.per_page,
                                    currentPage: page.props.documents.current_page,
                                }"
                                @paginate="loadPage"
                            >
                                <Column field="title" header="Downloadable" class="font-semibold">
                                    <template #body="props">
                                        <div class="min-w-60 py-1">
                                            <span class="block text-sm font-semibold text-slate-800 dark:text-gray-100">{{ props.data.title }}</span>
                                            <span class="block truncate text-xs text-slate-500 dark:text-gray-400">{{ props.data.original_filename }}</span>
                                        </div>
                                    </template>
                                </Column>
                                <Column field="category.name" header="Category">
                                    <template #body="props">
                                        {{ props.data.category?.name ?? "-" }}
                                    </template>
                                </Column>
                                <Column header="Availability">
                                    <template #body="props">
                                        <div class="flex flex-wrap gap-1">
                                            <Tag
                                                v-for="target in props.data.targets"
                                                :key="`${target.target_type}-${target.target_id}`"
                                                severity="info"
                                                :value="targetLabel(target)"
                                            />
                                        </div>
                                    </template>
                                </Column>
                                <Column header="Status" class="w-[10%]">
                                    <template #body="props">
                                        <Tag
                                            :severity="props.data.is_active ? 'success' : 'danger'"
                                            :value="props.data.is_active ? 'Active' : 'Inactive'"
                                        />
                                    </template>
                                </Column>
                                <Column header="Published" class="w-[10%]">
                                    <template #body="props">
                                        <Tag
                                            :severity="props.data.published_at ? 'success' : 'warn'"
                                            :value="props.data.published_at ? 'Published' : 'Draft'"
                                        />
                                    </template>
                                </Column>
                                <Column class="w-[8%]">
                                    <template #body="props">
                                        <div class="flex justify-end gap-1">
                                            <Button
                                                text
                                                rounded
                                                size="small"
                                                severity="secondary"
                                                icon="pi pi-download"
                                                as="a"
                                                :href="route('documents.download', props.data.id)"
                                                target="_blank"
                                            />
                                            <Button
                                                text
                                                rounded
                                                size="small"
                                                severity="secondary"
                                                icon="pi pi-pencil"
                                                @click="openDocumentForm(props.data)"
                                            />
                                            <Button
                                                text
                                                rounded
                                                size="small"
                                                severity="danger"
                                                icon="pi pi-trash"
                                                @click="deleteDocument(props.data)"
                                            />
                                        </div>
                                    </template>
                                </Column>
                            </DefaultTable>
                        </div>

                <div
                    v-show="activeTab === 'categories'"
                    class="flex flex-col gap-3"
                >
                            <ToolbarModule
                                v-model="categorySearchInput"
                                button-label="Create"
                                :dialog-title="categoryForm.id ? 'Edit Category' : 'Create Category'"
                                dialog-description="Organize downloadable files by category."
                                dialog-button-label="Save"
                                :dialog-icon="IconCategory"
                                :dialog-button-loading="categoryForm.processing"
                                :message-has-errors="categoryForm.hasErrors"
                                :message-errors="categoryForm.errors"
                                message-type="error"
                                :button-visible="false"
                                @deleteSearch="clearCategorySearch"
                                @buttonOpenModal="openCategoryForm()"
                                @saveForm="saveCategory"
                                ref="categoryToolbarRef"
                            >
                                <template #form>
                                    <div class="flex flex-col gap-3 mt-5">
                                <TextInput v-model="categoryForm.name" label="Name" />
                                <div class="flex flex-col">
                                    <span class="text-sm font-medium">Description</span>
                                    <Textarea v-model="categoryForm.description" rows="3" autoResize fluid />
                                </div>
                                <div class="flex items-center justify-between rounded-lg border px-3 py-2">
                                    <span class="text-sm font-medium">Active</span>
                                    <DefaultToggle
                                        v-model="categoryForm.is_active"
                                        :check-icon="IconCheck"
                                        :un-check-icon="IconX"
                                    />
                                </div>
                                    </div>
                                </template>
                            </ToolbarModule>

                            <DefaultTable
                                :items="page.props.categories.data"
                                :pagination="{
                                    total: page.props.categories.total,
                                    perPage: page.props.categories.per_page,
                                    currentPage: page.props.categories.current_page,
                                }"
                                @paginate="loadPage"
                            >
                                <Column field="name" header="Category" class="font-semibold" />
                                <Column field="description" header="Description" />
                                <Column header="Status" class="w-[10%]">
                                    <template #body="props">
                                        <Tag
                                            :severity="props.data.is_active ? 'success' : 'danger'"
                                            :value="props.data.is_active ? 'Active' : 'Inactive'"
                                        />
                                    </template>
                                </Column>
                                <Column class="w-[8%]">
                                    <template #body="props">
                                        <div class="flex justify-end gap-1">
                                            <Button
                                                text
                                                rounded
                                                size="small"
                                                severity="secondary"
                                                icon="pi pi-pencil"
                                                @click="openCategoryForm(props.data)"
                                            />
                                            <Button
                                                text
                                                rounded
                                                size="small"
                                                severity="danger"
                                                icon="pi pi-trash"
                                                @click="deleteCategory(props.data)"
                                            />
                                        </div>
                                    </template>
                                </Column>
                            </DefaultTable>
                        </div>
            </div>
        </div>
    </AuthLayout>
</template>

<script setup>
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import { route } from "ziggy-js";
import {
    IconCategory,
    IconCheck,
    IconFileUpload,
    IconFileDownload,
    IconX,
} from "@tabler/icons-vue";
import AuthLayout from "../../Layouts/AuthLayout.vue";
import HeaderModule from "../../Modules/Others/HeaderModule.vue";
import ToolbarModule from "../../Modules/Others/ToolbarModule.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import DefaultTable from "../../Components/tables/DefaultTable.vue";
import DefaultToggle from "../../Components/toggleswitches/DefaultToggle.vue";
import TextInput from "../../Components/inputs/TextInput.vue";
import SelectInput from "../../Components/inputs/SelectInput.vue";
import LibraryAccessInput from "../../Components/inputs/LibraryAccessInput.vue";
import UploadInput from "../../Components/inputs/UploadInput.vue";
import DefaultConfirmDialog from "../../Components/dialogs/DefaultConfirmDialog.vue";
import { buildLibraryTargets, libraryAccessFromTargets } from "../../Utils/libraryAccess";

const page = usePage();
const searchInput = ref(null);
const categorySearchInput = ref(null);
const searchTimer = ref(null);
const documentToolbarRef = ref(null);
const categoryToolbarRef = ref(null);
const confirmRef = ref(null);
const uploadRef = ref(null);
const progressUpload = ref(0);
const activeTab = ref("documents");
const tabs = [
    { label: "Downloadables", value: "documents" },
    { label: "Categories", value: "categories" },
];
const isRegionLocked = computed(() => Boolean(page.props.targetingScope?.is_region_locked));
const lockedRegion = computed(() => page.props.targetingScope?.region ?? page.props.regionOptions?.[0] ?? null);

const applyRegionalTargeting = () => {
    if (!isRegionLocked.value || !lockedRegion.value) return;

    documentForm.available_all = false;
    const existing = documentForm.region_scopes.find(
        (scope) => String(scope.region.id) === String(lockedRegion.value.id),
    );
    documentForm.region_scopes = [
        existing ?? {
            region: lockedRegion.value,
            school_coverage: { id: "all", name: "All schools in this region" },
            schools: [],
        },
    ];
};

const documentForm = useForm({
    id: null,
    title: null,
    description: null,
    category: null,
    file: null,
    is_active: true,
    publish_now: true,
    available_all: !isRegionLocked.value,
    region_scopes: [],
    scholarships: [],
    programs: [],
});

const categoryForm = useForm({
    id: null,
    name: null,
    description: null,
    is_active: true,
});

const targets = computed(() => buildLibraryTargets({
    availableAll: documentForm.available_all,
    regionScopes: documentForm.region_scopes,
    scholarships: documentForm.scholarships,
    programs: documentForm.programs,
}));

watch(searchInput, () => {
    clearTimeout(searchTimer.value);
    searchTimer.value = setTimeout(() => {
        router.get(
            route("documents"),
            {
                search: searchInput.value,
                categorySearch: categorySearchInput.value,
            },
            { preserveState: true, replace: true },
        );
    }, 400);
});

watch(categorySearchInput, () => {
    clearTimeout(searchTimer.value);
    searchTimer.value = setTimeout(() => {
        router.get(
            route("documents"),
            {
                search: searchInput.value,
                categorySearch: categorySearchInput.value,
            },
            { preserveState: true, replace: true },
        );
    }, 400);
});

const clearSearch = () => {
    searchInput.value = null;
};

const clearCategorySearch = () => {
    categorySearchInput.value = null;
};

const loadPage = (pageNumber) => {
    router.get(
        route("documents"),
        {
            page: pageNumber,
            search: searchInput.value,
            categorySearch: categorySearchInput.value,
        },
        { preserveState: true },
    );
};

const openDocumentForm = (row = null) => {
    documentForm.reset();
    documentForm.clearErrors();
    progressUpload.value = 0;
    uploadRef.value?.clear();
    applyRegionalTargeting();

    if (row) {
        const hasAll = row.targets?.some((target) => target.target_type === "all");
        documentForm.id = row.id;
        documentForm.title = row.title;
        documentForm.description = row.description;
        documentForm.category = row.category
            ? { id: row.category.id, name: row.category.name }
            : null;
        documentForm.is_active = row.is_active;
        documentForm.publish_now = !!row.published_at;
        documentForm.available_all = isRegionLocked.value ? false : hasAll;
        documentForm.region_scopes = libraryAccessFromTargets(
            row.targets,
            page.props.regionOptions,
            page.props.schoolOptions,
        );
        documentForm.scholarships = mapTargets(row, "scholarship_program", page.props.scholarshipOptions);
        documentForm.programs = mapTargets(row, "program", page.props.programOptions);
    }

    applyRegionalTargeting();

    documentToolbarRef.value.openModal();
};

const mapTargets = (row, type, options) => {
    const targetIds = (row.targets ?? [])
        .filter((target) => target.target_type === type)
        .filter((target) => target.target_id !== "all")
        .map((target) => String(target.target_id));

    return options.filter((option) => targetIds.includes(String(option.id)));
};

const handleFile = (event) => {
    documentForm.file = event.files?.[0] ?? null;
    progressUpload.value = 0;
};

const clearFile = () => {
    documentForm.file = null;
};

const saveDocument = () => {
    const payload = {
        title: documentForm.title,
        description: documentForm.description,
        category_id: documentForm.category?.id ?? null,
        file: documentForm.file,
        is_active: documentForm.is_active,
        publish_now: documentForm.publish_now,
        targets: targets.value,
    };

    const options = {
        forceFormData: true,
        preserveScroll: true,
        onProgress: (event) => {
            if (event.total) {
                progressUpload.value = (event.loaded / event.total) * 97;
            }
        },
        onSuccess: () => {
            documentToolbarRef.value.closeModal();
            uploadRef.value?.clear();
            documentForm.reset();
        },
    };

    if (documentForm.id) {
        router.post(route("documents.update", documentForm.id), {
            ...payload,
            _method: "put",
        }, options);
        return;
    }

    router.post(route("documents.store"), payload, options);
};

const deleteDocument = (row) => {
    confirmRef.value.popupDialog(() => {
        router.delete(route("documents.destroy", row.id), {
            preserveScroll: true,
        });
    }, {
        header: "Delete Downloadable",
        message: `Delete "${row.title}"?`,
        icon: "pi pi-trash",
    });
};

const saveCategory = () => {
    const payload = {
        name: categoryForm.name,
        description: categoryForm.description,
        is_active: categoryForm.is_active,
    };

    if (categoryForm.id) {
        categoryForm.transform(() => payload).put(route("document-categories.update", categoryForm.id), {
            preserveScroll: true,
            onSuccess: () => {
                categoryToolbarRef.value.closeModal();
                resetCategoryForm();
            },
        });
        return;
    }

    categoryForm.transform(() => payload).post(route("document-categories.store"), {
        preserveScroll: true,
        onSuccess: () => {
            categoryToolbarRef.value.closeModal();
            resetCategoryForm();
        },
    });
};

const openCategoryForm = (row = null) => {
    categoryForm.reset();
    categoryForm.clearErrors();
    categoryForm.id = null;
    categoryForm.is_active = true;

    if (row) {
        editCategory(row);
    }

    categoryToolbarRef.value.openModal();
};

const editCategory = (row) => {
    categoryForm.id = row.id;
    categoryForm.name = row.name;
    categoryForm.description = row.description;
    categoryForm.is_active = row.is_active;
};

const resetCategoryForm = () => {
    categoryForm.reset();
    categoryForm.clearErrors();
    categoryForm.id = null;
    categoryForm.is_active = true;
};

const deleteCategory = (row) => {
    confirmRef.value.popupDialog(() => {
        router.delete(route("document-categories.destroy", row.id), {
            preserveScroll: true,
        });
    }, {
        header: "Delete Category",
        message: `Delete "${row.name}"?`,
        icon: "pi pi-trash",
    });
};

const targetLabel = (target) => {
    if (target.target_type === "all") return "Everyone";
    if (target.target_id === "all") {
        return {
            region: "All Regions",
            scholarship_program: "All Scholarship Programs",
            program: "All Programs",
        }[target.target_type] ?? "All";
    }

    const source = {
        region: page.props.regionOptions,
        school: page.props.schoolOptions,
        scholarship_program: page.props.scholarshipOptions,
        program: page.props.programOptions,
    }[target.target_type] ?? [];

    const option = source.find((item) => String(item.id) === String(target.target_id));
    if (!option) return `${target.target_type}: ${target.target_id}`;
    return target.target_type === "region"
        ? `All schools in ${option.name}`
        : option.name;
};
</script>

<style scoped>
:global(.dark .downloadables-page .p-datatable-header-cell),
:global(.dark .downloadables-page .p-datatable-column-header-content) {
    color: #e5e7eb !important;
}

:global(.downloadables-page .p-datatable-tbody > tr > td) {
    padding-top: 0.65rem;
    padding-bottom: 0.65rem;
}
</style>
