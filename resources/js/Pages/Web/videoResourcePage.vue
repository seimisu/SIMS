<template>
    <Head title="Video Library" />
    <AuthLayout>
        <DefaultConfirmDialog ref="confirmRef" />
        <div class="video-library-page flex h-full min-h-0 w-full flex-col gap-4 overflow-hidden text-slate-800 dark:text-gray-100">
            <div class="shrink-0 border-b border-slate-200 pb-4 dark:border-gray-700">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="flex min-w-0 items-start gap-3">
                        <div class="flex size-10 shrink-0 items-center justify-center rounded-md bg-blue-600 text-white shadow-sm dark:bg-blue-500">
                            <IconVideo :size="21" stroke-width="1.8" />
                        </div>
                        <HeaderModule
                            title="Video Library"
                            description="Manage video links, previews, and audience availability."
                        />
                    </div>
                    <DefaultButton
                        size="small"
                        label="Create Video"
                        severity="secondary"
                        outlined
                        :icon="IconCirclePlusFilled"
                        class="self-start xl:self-auto"
                        @click="openResourceForm()"
                    />
                </div>
            </div>

            <div class="flex min-h-0 flex-1 flex-col gap-3 overflow-auto">
                <ToolbarModule
                    v-model="searchInput"
                    button-label="Create"
                    :dialog-title="resourceForm.id ? 'Edit Video' : 'Create Video'"
                    dialog-description="Set the video details and who can view it."
                    dialog-button-label="Save"
                    :dialog-icon="IconVideo"
                    :dialog-button-loading="resourceForm.processing"
                    :message-has-errors="resourceForm.hasErrors"
                    :message-errors="resourceForm.errors"
                    message-type="error"
                    :button-visible="false"
                    @deleteSearch="clearSearch"
                    @buttonOpenModal="openResourceForm()"
                    @saveForm="saveResource"
                    ref="toolbarRef"
                >
                    <template #form>
                        <div class="flex flex-col gap-3 mt-5">
                            <TextInput v-model="resourceForm.title" label="Title" />
                            <TextInput v-model="resourceForm.video_url" label="Video Link" />
                            <div class="flex flex-col gap-2">
                                <span class="text-sm font-medium">Preview Image</span>
                                <input
                                    type="file"
                                    accept="image/*"
                                    class="rounded-lg border border-gray-300 p-2 text-sm dark:border-gray-600 dark:bg-gray-700"
                                    @change="handleThumbnail"
                                />
                                <img
                                    v-if="thumbnailPreview"
                                    :src="thumbnailPreview"
                                    class="h-28 w-48 rounded-lg object-cover"
                                    alt=""
                                />
                            </div>
                            <div class="flex flex-col">
                                <span class="text-sm font-medium">Description</span>
                                <Textarea v-model="resourceForm.description" rows="3" autoResize fluid />
                            </div>
                            <div class="grid grid-cols-1 gap-3 lg:grid-cols-2">
                                <div class="flex items-center justify-between rounded-lg border px-3 py-2">
                                    <span class="text-sm font-medium">Active</span>
                                    <DefaultToggle
                                        v-model="resourceForm.is_active"
                                        :check-icon="IconCheck"
                                        :un-check-icon="IconX"
                                    />
                                </div>
                                <div class="flex items-center justify-between rounded-lg border px-3 py-2">
                                    <span class="text-sm font-medium">Published</span>
                                    <DefaultToggle
                                        v-model="resourceForm.publish_now"
                                        :check-icon="IconCheck"
                                        :un-check-icon="IconX"
                                    />
                                </div>
                            </div>

                            <Divider />
                            <LibraryAccessInput
                                v-model:available-all="resourceForm.available_all"
                                v-model:region-scopes="resourceForm.region_scopes"
                                v-model:scholarships="resourceForm.scholarships"
                                v-model:programs="resourceForm.programs"
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
                    :items="page.props.resources.data"
                    :pagination="{
                        total: page.props.resources.total,
                        perPage: page.props.resources.per_page,
                        currentPage: page.props.resources.current_page,
                    }"
                    @paginate="loadPage"
                >
                    <Column header="Preview" class="w-[9rem]">
                        <template #body="props">
                            <div class="h-16 w-28 overflow-hidden rounded-md border border-slate-200 bg-slate-100 dark:border-gray-700 dark:bg-gray-800">
                                <img
                                    v-if="props.data.thumbnail_url"
                                    :src="props.data.thumbnail_url"
                                    class="h-full w-full object-cover"
                                    alt=""
                                />
                                <div v-else class="flex h-full items-center justify-center text-gray-400">
                                    <IconVideo :size="24" />
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column field="title" header="Title" class="font-semibold">
                        <template #body="props">
                            <div class="min-w-60 py-1">
                                <span class="block text-sm font-semibold text-slate-800 dark:text-gray-100">{{ props.data.title }}</span>
                                <a
                                    :href="props.data.video_url"
                                    target="_blank"
                                    class="max-w-[28rem] truncate text-xs text-blue-500"
                                >
                                    {{ props.data.video_url }}
                                </a>
                            </div>
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
                                    icon="pi pi-external-link"
                                    as="a"
                                    :href="props.data.video_url"
                                    target="_blank"
                                />
                                <Button
                                    text
                                    rounded
                                    size="small"
                                    severity="secondary"
                                    icon="pi pi-pencil"
                                    @click="openResourceForm(props.data)"
                                />
                                <Button
                                    text
                                    rounded
                                    size="small"
                                    severity="danger"
                                    icon="pi pi-trash"
                                    @click="deleteResource(props.data)"
                                />
                            </div>
                        </template>
                    </Column>
                </DefaultTable>
            </div>
        </div>
    </AuthLayout>
</template>

<script setup>
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import { computed, ref, watch } from "vue";
import { route } from "ziggy-js";
import { IconCheck, IconCirclePlusFilled, IconVideo, IconX } from "@tabler/icons-vue";
import AuthLayout from "../../Layouts/AuthLayout.vue";
import HeaderModule from "../../Modules/Others/HeaderModule.vue";
import ToolbarModule from "../../Modules/Others/ToolbarModule.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import DefaultTable from "../../Components/tables/DefaultTable.vue";
import DefaultToggle from "../../Components/toggleswitches/DefaultToggle.vue";
import TextInput from "../../Components/inputs/TextInput.vue";
import LibraryAccessInput from "../../Components/inputs/LibraryAccessInput.vue";
import DefaultConfirmDialog from "../../Components/dialogs/DefaultConfirmDialog.vue";
import { buildLibraryTargets, libraryAccessFromTargets } from "../../Utils/libraryAccess";

const page = usePage();
const searchInput = ref(null);
const searchTimer = ref(null);
const toolbarRef = ref(null);
const confirmRef = ref(null);
const isRegionLocked = computed(() => Boolean(page.props.targetingScope?.is_region_locked));
const lockedRegion = computed(() => page.props.targetingScope?.region ?? page.props.regionOptions?.[0] ?? null);

const applyRegionalTargeting = () => {
    if (!isRegionLocked.value || !lockedRegion.value) return;

    resourceForm.available_all = false;
    const existing = resourceForm.region_scopes.find(
        (scope) => String(scope.region.id) === String(lockedRegion.value.id),
    );
    resourceForm.region_scopes = [
        existing ?? {
            region: lockedRegion.value,
            school_coverage: { id: "all", name: "All schools in this region" },
            schools: [],
        },
    ];
};

const resourceForm = useForm({
    id: null,
    title: null,
    description: null,
    video_url: null,
    thumbnail_url: null,
    thumbnail: null,
    is_active: true,
    publish_now: true,
    available_all: !isRegionLocked.value,
    region_scopes: [],
    scholarships: [],
    programs: [],
});

const targets = computed(() => buildLibraryTargets({
    availableAll: resourceForm.available_all,
    regionScopes: resourceForm.region_scopes,
    scholarships: resourceForm.scholarships,
    programs: resourceForm.programs,
}));

const thumbnailPreview = computed(() => {
    if (resourceForm.thumbnail) {
        return URL.createObjectURL(resourceForm.thumbnail);
    }

    return resourceForm.thumbnail_url;
});

watch(searchInput, () => {
    clearTimeout(searchTimer.value);
    searchTimer.value = setTimeout(() => {
        router.get(
            route("video-resources"),
            { search: searchInput.value },
            { preserveState: true, replace: true },
        );
    }, 400);
});

const clearSearch = () => {
    searchInput.value = null;
};

const loadPage = (pageNumber) => {
    router.get(
        route("video-resources"),
        { page: pageNumber, search: searchInput.value },
        { preserveState: true },
    );
};

const openResourceForm = (row = null) => {
    resourceForm.reset();
    resourceForm.clearErrors();
    resourceForm.is_active = true;
    resourceForm.publish_now = true;
    resourceForm.available_all = !isRegionLocked.value;
    applyRegionalTargeting();

    if (row) {
        const hasAll = row.targets?.some((target) => target.target_type === "all");
        resourceForm.id = row.id;
        resourceForm.title = row.title;
        resourceForm.description = row.description;
        resourceForm.video_url = row.video_url;
        resourceForm.thumbnail_url = row.thumbnail_url;
        resourceForm.thumbnail = null;
        resourceForm.is_active = row.is_active;
        resourceForm.publish_now = !!row.published_at;
        resourceForm.available_all = isRegionLocked.value ? false : hasAll;
        resourceForm.region_scopes = libraryAccessFromTargets(
            row.targets,
            page.props.regionOptions,
            page.props.schoolOptions,
        );
        resourceForm.scholarships = mapTargets(row, "scholarship_program", page.props.scholarshipOptions);
        resourceForm.programs = mapTargets(row, "program", page.props.programOptions);
    }

    applyRegionalTargeting();

    toolbarRef.value.openModal();
};

const handleThumbnail = (event) => {
    resourceForm.thumbnail = event.target.files?.[0] ?? null;
};

const mapTargets = (row, type, options) => {
    const targetIds = (row.targets ?? [])
        .filter((target) => target.target_type === type)
        .filter((target) => target.target_id !== "all")
        .map((target) => String(target.target_id));

    return options.filter((option) => targetIds.includes(String(option.id)));
};

const saveResource = () => {
    const payload = {
        title: resourceForm.title,
        description: resourceForm.description,
        video_url: resourceForm.video_url,
        thumbnail_url: resourceForm.thumbnail_url,
        thumbnail: resourceForm.thumbnail,
        is_active: resourceForm.is_active,
        publish_now: resourceForm.publish_now,
        targets: targets.value,
    };

    const options = {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            toolbarRef.value.closeModal();
            resourceForm.reset();
        },
    };

    if (resourceForm.id) {
        router.post(route("video-resources.update", resourceForm.id), {
            ...payload,
            _method: "put",
        }, options);
        return;
    }

    router.post(route("video-resources.store"), payload, options);
};

const deleteResource = (row) => {
    confirmRef.value.popupDialog(() => {
        router.delete(route("video-resources.destroy", row.id), {
            preserveScroll: true,
        });
    }, {
        header: "Delete Video",
        message: `Delete "${row.title}"?`,
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
:global(.dark .video-library-page .p-datatable-header-cell),
:global(.dark .video-library-page .p-datatable-column-header-content) {
    color: #e5e7eb !important;
}

:global(.video-library-page .p-datatable-tbody > tr > td) {
    padding-top: 0.65rem;
    padding-bottom: 0.65rem;
}
</style>
