<template>
    <FileUpload
        name="excelfiles"
        :show-upload-button="false"
        :pt="{
            root: { class: '!rounded-[15px] !m-0 !border-dashed' },
        }"
        @uploader="onUpload($event)"
        @select="(files) => emit('select-files', files)"
        mode="advanced"
        ref="fileupload"
        :accept="accept"
    >
        <template #empty>
            <div
                class="w-full flex items-center justify-center text-gray-400 text-sm italic"
            >
                <div class="flex flex-col items-center justify-center">
                    <IconFileUpload :size="40" stroke-width="1" />
                    <div>Drag and drop files to here to upload.</div>
                    <div class="text-xs">
                        Accepted formats: (.csv, .xls, .xlsx)
                    </div>
                </div>
            </div>
        </template>
        <template
            #content="{
                files,
                uploadedFiles,
                removeUploadedFileCallback,
                removeFileCallback,
                messages,
            }"
        >
            <div class="'flex flex-col w-full">
                <div>
                    <div v-for="(item, key) in files" :key="key" class="w-full">
                        <div class="flex items-center gap-3 m-2">
                            <Avatar class="!w-[40px] !h-[40px]">
                                <IconFileDescription size="24" />
                            </Avatar>

                            <div class="flex flex-col gap-1 w-full">
                                <div class="flex justify-between">
                                    <div
                                        class="text-sm text-gray-700 flex items-end gap-2"
                                    >
                                        <div>{{ item.name }}</div>
                                        <div class="text-gray-400 text-[10px]">
                                            ({{ formatFileSize(item.size) }})
                                        </div>
                                    </div>
                                    <Button
                                        text
                                        severity="danger"
                                        :disabled="disabled"
                                        class="!rounded-[15px] !h-[20px] !w-[20px] !p-0"
                                        @click="
                                            handleRemoveFile(
                                                key,
                                                item,
                                                removeFileCallback,
                                                removeUploadedFileCallback,
                                            )
                                        "
                                    >
                                        <IconX size="30" />
                                    </Button>
                                </div>

                                <div
                                    class="text-xs text-gray-400"
                                    :class="status === 'uploading' ? 'animate-pulse font-medium text-blue-500' : ''"
                                >
                                    {{ statusLabel }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </FileUpload>
</template>
<script setup>
import { IconFileDescription, IconFileUpload, IconX } from "@tabler/icons-vue";
import { computed, ref } from "vue";
const emit = defineEmits(["select-files", "remove-file"]);
const fileupload = ref(null);
const props = defineProps({
    accept: {
        type: String,
        default: "",
    },
    status: {
        type: String,
        default: "idle",
    },
    disabled: {
        type: Boolean,
        default: false,
    },
});

const statusLabel = computed(() => {
    if (props.status === "uploading") return "Uploading";
    if (props.status === "success") return "Uploaded";
    if (props.status === "error") return "Error";
    return "Ready";
});

function formatFileSize(size) {
    const units = ["Bytes", "KB", "MB", "GB", "TB"];
    if (!size || size === 0) return "0 Bytes";
    const i = Math.floor(Math.log(size) / Math.log(1024));
    return (size / Math.pow(1024, i)).toFixed(2) + " " + units[i];
}

const handleRemoveFile = (
    key,
    file,
    removeFileCallback,
    removeUploadedFileCallback,
) => {
    // Remove file from FileUpload internal list
    removeFileCallback(key);
    removeUploadedFileCallback(key);
    emit("remove-file", file);
};

const clear = () => {
    fileupload.value.clear();
};

// const onClearTemplatingUpload = (clear) => {
//     clear();
//     totalSize.value = 0;
//     totalSizePercent.value = 0;
// };

// const uploadEvent = (callback) => {
//     totalSizePercent.value = totalSize.value / 10;
//     callback();
// };

defineExpose({
    clear,
});
</script>

<style>
.p-fileupload-choose-button {
    border-radius: 15px !important;
    font-size: 12px !important;
}
.p-fileupload-cancel-button {
    border-radius: 15px !important;
    font-size: 12px !important;
    color: #8d8d8d;
}
</style>
