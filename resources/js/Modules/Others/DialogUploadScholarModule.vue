<template>
    <Dialog
        v-model:visible="modelValue"
        modal
        :closable="!filesUploadForm.processing"
        :close-on-escape="!filesUploadForm.processing"
        :style="{ width: '45rem' }"
        :pt="{ header: 'border-b-1 border-gray-300 border-dashed' }">
        <template #header>
            <div class="bg-slate-100 px-4 py-2 shadow rounded-lg flex items-center gap-2">
                <IconUserUp :size="18" :stroke-width="2" />
                <div class="uppercase font-medium text-sm">
                    Import Scholars
                </div>
            </div>
        </template>
        <template #default>
            <div class="mt-3">
                <div class="flex flex-col lg:flex-row gap-3">
                    <div class="flex-1 flex flex-col gap-5">
                        <div
                            class="flex items-start p-3 shadow border border-blue-300 text-blue-500 rounded-xl bg-blue-50 gap-1">
                            <div>
                                <IconExclamationCircleFilled :size="20" />
                            </div>

                            <p class="text-xs leading-5 text-justify">
                                Please upload the scholar’s complete information
                                and supporting documents. Ensure that all
                                required fields are properly filled out and the
                                uploaded files are accurate and up to date
                                before submitting.
                            </p>
                        </div>
                        <a
                            href="/scholar/import-template"
                            class="inline-flex w-fit items-center gap-2 rounded-xl border border-slate-200 bg-white px-4 py-2 text-xs text-slate-600 hover:bg-slate-50"
                        >
                            Download Template
                        </a>
                        <UploadInput ref="uploadRef" @select-files="handleFiles" @remove-file="clearForm"
                            :status="uploadStatus"
                            :disabled="filesUploadForm.processing"
                            accept=".csv, application/vnd.ms-excel, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">

                        </UploadInput>
                        <div class="" v-if="filesUploadForm.errors.files">
                            <div
                                class="flex items-start p-3 shadow border border-red-300 text-red-500 rounded-xl bg-red-50 gap-1">
                                <div>
                                    <IconExclamationCircleFilled :size="20" />
                                </div>

                                <div class="flex flex-col gap-1 text-xs leading-5">
                                    <div
                                        v-for="(error, index) in uploadErrors"
                                        :key="index"
                                    >
                                        {{ error }}
                                    </div>
                                </div>
                            </div>

                        </div>
                        <div class="flex justify-end">
                            <DefaultButton
                                size="small"
                                label="Upload File"
                                :disabled="filesUploadForm.processing || !filesUploadForm.files.length"
                                @click="submitForm"
                            />
                        </div>
                    </div>

                </div>
            </div>
        </template>
    </Dialog>
</template>
<script setup>
import {
    IconExclamationCircle,
    IconExclamationCircleFilled,
    IconUserUp,
} from "@tabler/icons-vue";
import UploadInput from "../../Components/inputs/UploadInput.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import { computed, ref } from "vue";
import { useForm, usePage } from "@inertiajs/vue3";
import { useToast } from "primevue";

const page = usePage();
const toast = useToast();
const uploadRef = ref(null);
const modelValue = defineModel("modelValue");
const fileUpload = ref(null);
const uploadStatus = ref("idle");
const filesUploadForm = useForm({
    files: [],
});
const uploadErrors = computed(() => {
    const errors = filesUploadForm.errors.files;

    if (Array.isArray(errors)) return errors;
    if (typeof errors === "string") return [errors];

    return [];
});

const handleFiles = (e) => {
    filesUploadForm.files = Array.from(e.files);
    uploadStatus.value = "idle";
};

const clearForm = () => {
    filesUploadForm.resetAndClearErrors();
    filesUploadForm.files = [];
    uploadStatus.value = "idle";
};

const submitForm = () => {
    if (!filesUploadForm.files.length || filesUploadForm.processing) return;

    filesUploadForm.post(route("scholar.store"), {
        forceFormData: true,
        preserveState: true,
        preserveScroll: true,
        onBefore: () => {
            uploadStatus.value = "uploading";
        },
        onSuccess: () => {
            // toastRef.value.show(page.props.flash);
            if (["success", "warn"].includes(page.props.flash?.status)) {
                uploadStatus.value = "success";
                toast.add({
                    severity: page.props.flash?.status,
                    summary: page.props.flash?.title,
                    detail: page.props.flash?.message,
                    life: 3000,
                });
                window.setTimeout(() => {
                    filesUploadForm.resetAndClearErrors();
                    filesUploadForm.files = [];
                    uploadRef.value.clear();
                    modelValue.value = false;
                    uploadStatus.value = "idle";
                }, 900);
            }
        },
        onError: (e) => {
            uploadStatus.value = "error";
            toast.add({
                severity: "error",
                summary: "Invalid Excel format",
                detail: "Please check the file headers/template and upload the corrected file.",
                life: 3000,
            });
        },
        onFinish: () => {
            if (uploadStatus.value === "uploading") {
                uploadStatus.value = "idle";
            }
        },
    });
};
</script>
