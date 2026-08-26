<template>
    <ToggleSwitch
        v-model="model"
        v-tooltip.bottom="tooltip"
        @update:model-value="updateValue()"
        :disabled="disabled"
    >
        <template #handle="{ checked }">
            <div>
                <component
                    :is="checkIcon"
                    v-if="checked"
                    :size="12"
                    :class="checkIconClass"
                />
                <component
                    :is="unCheckIcon"
                    v-else
                    :size="12"
                    :class="unCheckIconClass"
                />
            </div>
        </template>
    </ToggleSwitch>
</template>
<script setup>
const model = defineModel();
const emit = defineEmits(["updateValue"]);
defineProps({
    checkIcon: {
        type: Function,
        default: null,
    },
    unCheckIconClass: {
        type: String,
    },
    checkIconClass: String,
    unCheckIcon: {
        type: Function,
        default: null,
    },
    tooltip: { type: String, default: null },
    disabled: {
        type: Boolean,
        default: false,
    },
});

function updateValue() {
    emit("updateValue", model.value);
}
</script>
