<template>
    <Dialog v-model:visible="modelValue" modal :style="{ width: '45rem' }"
        :pt="{ header: 'border-b-1 border-gray-300 border-dashed' }">
        <template #header>
            <div class="bg-slate-100 px-4 py-2 shadow rounded-lg flex items-center gap-2">
                <IconUserUp :size="18" :stroke-width="2" />
                <div class="uppercase font-medium text-sm">
                    Register Scholars
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
                        <UploadInput ref="uploadRef" @select-files="handleFiles" @remove-file="clearForm"
                            :progress="progressUpload"
                            accept=".csv, application/vnd.ms-excel, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">

                        </UploadInput>
                        <div class="flex justify-end">
                            <DefaultButton size="small" label="Upload File" @click="submitForm" />
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
import { useForm, progress, usePage } from "@inertiajs/vue3";
import { useToast } from "primevue";

const page = usePage();
const toast = useToast();
const uploadRef = ref(null);
const modelValue = defineModel("modelValue");
const fileUpload = ref(null);
const progressUpload = ref(0);
const filesUploadForm = useForm({
    files: [],
});

const handleFiles = (e) => {
    filesUploadForm.files = Array.from(e.files);
    progressUpload.value = 0;
};

const clearForm = () => {
    filesUploadForm.resetAndClearErrors();
    filesUploadForm.files = [];
};

const submitForm = () => {
    uploadRef.value.upload();
    filesUploadForm.post(route("scholar.store"), {
        forceFormData: true,
        onBefore: () => {
            progressUpload.value = 0;
        },
        onProgress: (e) => {
            if (!e.total) return;

            progressUpload.value = (e.loaded / e.total) * 97;
        },
        onSuccess: () => {
            // toastRef.value.show(page.props.flash);
            if (page.props.flash?.status == "success") {
                filesUploadForm.resetAndClearErrors();
                filesUploadForm.files = [];
                uploadRef.value.clear();
                toast.add({
                    severity: page.props.flash?.status,
                    summary: page.props.flash?.title,
                    detail: page.props.flash?.message,
                    life: 3000,
                });
            }
        },
        onError: (e) => {
            toast.add({
                severity: "error",
                summary: "Something is wrong",
                detail: page.props.errors?.files,
                life: 3000,
            });
        },
        onFinish: () => {
            progressUpload.value = 100;
        },
    });
};
</script>
