<template>
    <div class="w-full flex flex-col">
        <div class="text-sm font-medium" v-show="label">
            {{ label }}
            <span class="text-red-600 font-semibold" v-if="errorMark">*</span>
        </div>
        <Select
            :placeholder="placeholder"
            v-model="modelValue"
            :disabled="disable"
            :filter="filter"
            optionLabel="name"
            @update:modelValue="emit('update-model', $event)"
            :options="options"
            :showClear="clearable"
            fluid
            :pt="{
                root: {
                    class: [
                        'dark:!bg-gray-700 dark:!text-gray-100 !text-sm dark:!border-gray-700 ',
                        capitalize ? 'capitalize' : '',
                    ],
                },
                label: {
                    class: 'dark:!text-white',
                },
                option: {
                    class: [
                        '!text-sm dark:!text-gray-400 hover:!text-gray-700',
                        capitalize ? 'capitalize' : '',
                    ],
                },
                overlay: {
                    class: 'dark:!bg-gray-800  dark:!border-gray-700 ',
                },
                emptyMessage: {
                    class: '!text-sm',
                },
            }"
        >
            <template #option="slotProps">
                <slot name="option" v-bind="slotProps"></slot>
            </template>
        </Select>
    </div>
</template>
<script setup>
import { watch } from "vue";
defineProps({
    label: {
        type: String,
        default: null,
    },
    disable: {
        type: Boolean,
        default: false,
    },
    capitalize: {
        type: Boolean,
        default: false,
    },
    placeholder: {
        type: String,
        default: "Select an option",
    },
    options: {
        type: [Object, Array],
        required: true,
    },
    filter: {
        type: [Boolean],
        default: false,
    },
    clearable: {
        type: [Boolean],
        default: false,
    },
    errorMark: {
        type: String,
        default: null,
    },
});
const modelValue = defineModel({
    type: [Array, Object, null],
    required: true,
});
const emit = defineEmits(["update", "update-model"]);

watch(modelValue, (newVal) => {
    emit("update", newVal);
});
</script>
<style scoped>
::v-deep(.p-select-label) {
    font-size: 14px !important;
}
</style>
