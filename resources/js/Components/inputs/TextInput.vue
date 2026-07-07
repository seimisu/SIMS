<template>
    <div class="w-full flex flex-col">
        <div class="text-sm font-medium" v-show="label">
            {{ label }}
            <span class="text-red-600 font-semibold" v-if="errorMark">*</span>
        </div>

        <InputText
            :type="type"
            :placeholder="placeholder"
            v-model="modelValue"
            fluid
            :disabled="disabled"
            autocomplete="off"
            :pt="{
                root: {
                    class: [
                        'dark:!bg-gray-700 !text-sm  border dark:!border-gray-700 dark:!text-white',
                        capitalize ? 'capitalize' : '',
                        uppercase ? 'uppercase' : '',
                    ],
                },
            }"
        />
        <Message
            severity="secondary"
            class="mt-1 mx-1"
            variant="simple"
            v-show="message"
            ><p class="text-xs font-light text-gray-400">{{ message }}</p>
        </Message>
    </div>
</template>
<script setup>
defineProps({
    type: {
        type: String,
        default: "text",
    },
    label: {
        type: String,
        default: null,
    },
    placeholder: {
        type: String,
    },
    capitalize: { type: Boolean, default: false },
    uppercase: { type: Boolean, default: false },
    error: { type: [String, Array, Object], default: "" },
    message: {
        type: String,
        default: null,
    },
    disabled: {
        type: Boolean,
        default: false,
    },
    errorMark: {
        type: [Boolean, String],
        default: null,
    },
});
const modelValue = defineModel({
    type: [String, Date, Object, null, Number],
    required: true,
});
</script>
<style scoped>
::v-deep(.p-tooltip-text) {
    font-size: 10px !important;
}
</style>
