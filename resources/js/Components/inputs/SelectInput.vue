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
            :loading="loading"
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
                        uppercase ? 'uppercase' : '',
                    ],
                },
                label: {
                    class: 'dark:!text-white',
                },
                option: {
                    class: [
                        '!text-sm dark:!text-gray-300 hover:!text-gray-700 dark:hover:!bg-gray-700 dark:hover:!text-white',
                        capitalize ? 'capitalize' : '',
                    ],
                },
                overlay: {
                    class: 'dark:!bg-gray-800  dark:!border-gray-700 ',
                },
                dropdown: {
                    class: 'dark:!text-gray-300',
                },
                clearIcon: {
                    class: 'dark:!text-gray-400 dark:hover:!text-gray-200',
                },
                filterInput: {
                    class: 'dark:!border-gray-600 dark:!bg-gray-900 dark:!text-gray-100',
                },
                emptyMessage: {
                    class: '!text-sm dark:!text-gray-400',
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
    uppercase: {
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
    loading: {
        type: Boolean,
        default: false,
    },
    clearable: {
        type: [Boolean],
        default: false,
    },
    errorMark: {
        type: Boolean,
        default: false,
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
