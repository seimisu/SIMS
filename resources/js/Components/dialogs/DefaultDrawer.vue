<template>
    <Drawer
        v-model:visible="modelValue"
        :header="title"
        :position="position"
        :class="[size, className]"
        :pt="{
            header: '!sticky !top-0',
            content: 'overflow-hidden',
        }"
    >
        <template #container="{ closeCallback }">
            <div class="w-full h-full flex flex-col overflow-auto bg-white text-slate-900 dark:bg-gray-800 dark:text-gray-100">
                <div class="flex flex-col sticky top-0 bg-white z-9999 dark:bg-gray-800">
                    <div class="flex items-center justify-between p-3 shrink-0">
                        <div class="inline-flex items-center">
                            <slot name="header"></slot>
                        </div>
                        <span>
                            <Button
                                type="button"
                                @click="closeCallback"
                                icon="pi pi-times"
                                rounded
                                severity="secondary"
                                variant="outlined"
                                class="dark:!border-gray-600 dark:!text-gray-200 dark:hover:!bg-gray-700"
                            ></Button>
                        </span>
                    </div>
                </div>
                <Divider align="left" type="dashed" class="!m-0">
                    <span class="text-xs font-semibold text-slate-800 dark:text-gray-100">Information</span>
                </Divider>
                <div class="flex-1 h-full">
                    <slot name="body"></slot>
                </div>
            </div>
        </template>
    </Drawer>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        default: "Drawer Title",
    },
    position: {
        type: String,
        default: "right",
    },
    size: {
        type: String,
        default: "w-[30rem]",
    },
    className: {
        type: String,
        default: "",
    },
});
const modelValue = defineModel("modelValue");
</script>
