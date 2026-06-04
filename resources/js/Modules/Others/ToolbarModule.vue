<template>
    <div class="flex items-center justify-between">
        <div class="flex-1 flex items-center gap-2">
            <IconTextInput
                :icon="IconSearch"
                placeholder="Search keywords..."
                v-model="modelValue"
                :class="['w-64 lg:w-96', hideSearch ? '!hidden' : '']"
            />
            <DefaultButton
                rounded
                size="small"
                severity="secondary"
                :icon="IconX"
                v-show="modelValue"
                @click="triggerDelete"
                :icon-size="18"
            />
        </div>

        <div class="flex items-center gap-2">
            <slot name="add2"></slot>
            <slot name="add1"></slot>
            <DefaultButton
                :icon="IconCirclePlusFilled"
                :label="buttonLabel"
                size="small"
                @click="triggerOpenModal"
                class-name="w-30  !rounded-xl"
                raised
            />
        </div>
    </div>
    <DefaultDialog
        v-model:visible="modal"
        :icon="dialogIcon"
        width-set="lg:!w-[40%] "
        :title="dialogTitle"
        :description="dialogDescription"
        :button-label="dialogButtonLabel"
        :loading="dialogButtonLoading"
        @submit-form="triggerSave"
    >
        <template #forms>
            <slot name="form" />
        </template>
        <template #message>
            <DefaultMessages
                v-show="messageHasErrors"
                :message="messageErrors"
                :message-type="messageType"
            ></DefaultMessages>
        </template>
    </DefaultDialog>
</template>
<script setup>
import { IconCirclePlusFilled, IconSearch, IconX } from "@tabler/icons-vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import DefaultMessages from "../../Components/messages/DefaultMessages.vue";
import IconTextInput from "../../Components/inputs/IconTextInput.vue";
import DefaultDialog from "../../Components/dialogs/DefaultDialog.vue";
import { ref } from "vue";

const modal = ref(false);
const emit = defineEmits(["deleteSearch", "saveForm", "buttonOpenModal"]);

defineProps({
    dialogDescription: String,
    dialogTitle: String,
    dialogIcon: Function,
    dialogButtonLabel: String,
    dialogButtonLoading: Boolean,
    buttonLabel: String,
    messageHasErrors: Boolean,
    messageErrors: Object,
    hideSearch: {
        type: Boolean,
        default: false,
    },
    messageType: String,
});

const modelValue = defineModel({
    type: [String, Date, Object, null, Number],
    required: true,
});

const openModal = () => {
    modal.value = true;
};

const closeModal = () => {
    modal.value = false;
};

const triggerDelete = () => {
    emit("deleteSearch");
};
const triggerSave = () => {
    emit("saveForm");
};

const triggerOpenModal = () => {
    emit("buttonOpenModal");
};

defineExpose({
    openModal,
    closeModal,
});
</script>
