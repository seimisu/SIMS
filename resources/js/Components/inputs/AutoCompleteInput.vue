<template>
    <div class="w-full flex flex-col">
        <span class="text-sm font-medium" v-if="label">{{ label }}</span>
        <AutoComplete
            v-model="modelValue"
            :suggestions="options"
            :disabled="disabled"
            optionLabel="name"
            :placeholder="placeholder"
            :force-selection="selection"
            @complete="onComplete"
            :loading="loading"
            :min-length="4"
            fluid
            :pt="{
                root: {
                    class: 'h-[38px]',
                },
                pcInputText: {
                    root: '!text-sm dark:!bg-gray-700 dark:!border-gray-700  dark:!text-white',
                },
                overlay: {
                    class: '!text-sm',
                },
            }"
        >
            <template #option="slotProps">
                <slot name="option" v-bind="slotProps.option"></slot>
            </template>
        </AutoComplete>
        <Message
            severity="secondary"
            class="mt-1 mx-1"
            variant="simple"
            v-show="message"
        >
            <p class="text-xs font-light text-gray-400">{{ message }}</p>
        </Message>
    </div>
</template>

<script setup>
import { watch } from "vue";
const props = defineProps({
    options: {
        type: Array,
        default: () => [],
    },
    label: String,
    placeholder: String,
    message: {
        type: String,
        default: null,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    selection: {
        type: Boolean,
        default: false,
    },
    loading: {
        type: Boolean,
        default: false,
    },
});
const modelValue = defineModel({
    type: [String, Date, Object, null, Number],
    required: true,
});
const emit = defineEmits(["update:modelValue", "complete"]);

function onComplete(event) {
    emit("complete", event.query);
}

// Two-way binding for v-model
watch(
    () => props.modelValue,
    (value) => {
        emit("update:modelValue", value);
    },
);
</script>
