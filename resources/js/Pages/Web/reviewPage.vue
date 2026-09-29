<template>

    <Head title="Review" />
    <AuthLayout>
        <div class="flex flex-col w-full h-full gap-5">
            <div class="flex flex-col lg:flex-row items-center space-x-0 gap-4">
                <HeaderModule title="Scholar Import Review"
                    description="Review uploaded scholar Excel files, check validation issues, and publish fully valid import batches." />
                <div class="flex items-center gap-2">
                    <DefaultButton size="small" label="Import Excel" :icon="IconPlus"
                        @click="dialogUploadScholar = true" class-name="!rounded-xl !text-sm !px-4" raised />
                </div>
            </div>
            <div class="flex-1 flex flex-col gap-2">
                <DefaultSelectionTable :items="page.props.files.data" :pagination="{
                    total: page.props.files.total,
                    perPage: page.props.files.per_page,
                    currentPage: page.props.files.current_page,
                }" @selected="selectScholar" :loading="loading.table" @paginate="loadPage">
                    <Column header="Filename">
                        <template #body="props">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex flex-col">
                                    <div class="font-medium text-gray-800">
                                        {{ props.data.filename }}
                                    </div>

                                    <div class="text-xs text-gray-400">
                                        Uploaded file
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column header="Regional Office">
                        <template #body="props">
                            <div class="flex items-center">
                                <span class="px-3 py-1 text-xs font-medium rounded-full
                       bg-indigo-50 text-indigo-600 border border-indigo-200">
                                    {{ props.data.region_office ?? 'N/A' }}
                                </span>
                            </div>
                        </template>
                    </Column>
                    <Column header="Created By">
                        <template #body="props">
                            <div class="flex items-center justify-between gap-3">
                                <!-- Creator Info -->
                                <div class="flex flex-col">
                                    <div class="text-sm font-medium text-gray-800">
                                        {{ props.data.created_by }}
                                    </div>

                                    <div class="text-xs text-gray-400 flex items-center gap-1">
                                        <IconCalendar size="12" />
                                        {{ props.data.formatted_date }}
                                    </div>
                                </div>

                            </div>
                        </template>
                    </Column>
                    <Column>
                        <template #header>
                            <div class="flex w-full justify-center">
                                <div class="font-semibold text-gray-700">
                                    Status
                                </div>
                            </div>
                        </template>

                        <template #body="props">
                            <div class="flex w-full justify-center">
                                <!-- Pending -->
                                <div v-if="props.data.status === 'pending'"
                                    class="flex items-center gap-2 px-3 py-1 rounded-full bg-yellow-50 border border-yellow-200 text-yellow-700 text-xs font-medium capitalize">
                                    <span class="w-2 h-2 rounded-full bg-yellow-500"></span>
                                    Pending
                                </div>

                                <div v-else-if="props.data.status === 'Needs Correction'"
                                    class="flex items-center gap-2 px-3 py-1 rounded-full bg-red-50 border border-red-200 text-red-700 text-xs font-medium">
                                    <span class="w-2 h-2 rounded-full bg-red-500"></span>
                                    Needs Correction
                                </div>

                                <div v-else-if="props.data.status === 'Ready'"
                                    class="flex items-center gap-2 px-3 py-1 rounded-full bg-green-50 border border-green-200 text-green-700 text-xs font-medium">
                                    <span class="w-2 h-2 rounded-full bg-green-500"></span>
                                    Ready
                                </div>

                                <!-- Completed -->
                                <div v-else-if="props.data.status === 'Completed'"
                                    class="flex items-center gap-2 px-3 py-1 rounded-full bg-emerald-50 border border-emerald-200 text-emerald-700 text-xs font-medium">
                                    <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                                    Completed
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column>
                        <template #header>
                            <div class="flex w-full justify-center">
                                <div class="font-semibold text-gray-700">
                                    Actions
                                </div>
                            </div>
                        </template>
                        <template #body="props">
                            <div class="flex justify-center gap-2" @click.stop>
                                <a :href="props.data.file_url" download
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-blue-200 bg-blue-50 text-blue-600 hover:bg-blue-100 transition"
                                    title="Download uploaded file">
                                    <IconDownload size="16" />
                                </a>
                                <button
                                    v-if="(props.data.status || '').toLowerCase() !== 'completed'"
                                    type="button"
                                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg border border-rose-200 bg-rose-50 text-rose-600 hover:bg-rose-100 transition"
                                    title="Delete import batch"
                                    @click.stop="deleteBatch(props.data)"
                                >
                                    <IconTrash size="16" />
                                </button>
                            </div>
                        </template>
                    </Column>
                </DefaultSelectionTable>
            </div>
        </div>
        <DialogUploadScholarModule v-if="dialogUploadScholar" v-model="dialogUploadScholar"></DialogUploadScholarModule>
        <DrawerScholarVerificationModule v-if="selectedDrawer" v-model="selectedDrawer" :id="selectedId" />
        <DefaultConfirmDialog ref="confirmRef" group="review-delete" />
    </AuthLayout>
</template>
<script setup>
import AuthLayout from "../../Layouts/AuthLayout.vue";
import HeaderModule from "../../Modules/Others/HeaderModule.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import DefaultSelectionTable from "../../Components/tables/DefaultSelectionTable.vue";
import DefaultConfirmDialog from "../../Components/dialogs/DefaultConfirmDialog.vue";
import DrawerScholarVerificationModule from "../../Modules/Others/DrawerScholarVerificationModule.vue";
import {
    IconCalendar,
    IconDownload,
    IconPlus,
    IconTrash,

} from "@tabler/icons-vue";
import { usePage, Head, router } from "@inertiajs/vue3";
import { ref } from "vue";
import DialogUploadScholarModule from "../../Modules/Others/DialogUploadScholarModule.vue";
import { useToast } from "primevue";

const page = usePage();
const toast = useToast()
const confirmRef = ref(null)
const dialogUploadScholar = ref(false)
const selectedDrawer = ref(false);
const selectedId = ref(null)
const loading = ref({
    table: false,
});

const selectScholar = (e) => {
    if ((e.status || '').toLowerCase() === 'completed') {
        toast.add({
            severity: 'info',
            summary: 'Unable to Open Module',
            detail: 'This module has been marked as completed and cannot be reopened.',
            life: 3000
        });
        return;
    }

    router.reload({
        only: ["selected", "courseOption", "schoolOption", "validationStatus"],
        data: { id: e.hash_id },
        replace: true,
        onBefore: () => {
            selectedId.value = e.hash_id
            loading.value.table = true;
        },
        onFinish: () => {
            selectedDrawer.value = true;
            loading.value.table = false;
        },
    });
};

const loadPage = (page) => {
    router.get(
        route("review"),
        {
            page,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const deleteBatch = (file) => {
    confirmRef.value?.popupDialog(
        () => {
            router.delete(route("scholar.destroy", { id: file.hash_id, type: "import-batch" }), {
                preserveScroll: true,
                onSuccess: () => {
                    toast.add({
                        severity: page.props?.flash?.status,
                        summary: page.props?.flash?.title,
                        detail: page.props?.flash?.message,
                        life: 3000,
                    });
                },
            });
        },
        {
            header: "Delete Import Batch",
            message: `Delete "${file.filename}" from the review list? This will hide the uploaded batch but keep the record for history.`,
            icon: "pi pi-trash",
            acceptLabel: "Delete",
            severity: "danger",
        },
    );
};
</script>
