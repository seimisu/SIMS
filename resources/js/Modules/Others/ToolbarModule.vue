<template>
    <ManagementFilterBar
        v-model="modelValue"
        :searchable="!hideSearch"
        search-placeholder="Search keywords"
    >
            <DefaultButton
                size="small"
                severity="secondary"
                :icon="IconX"
                v-show="modelValue"
                @click="triggerDelete"
                :icon-size="18"
                outlined
                tooltip="Clear search"
            />
            <slot name="add2"></slot>
            <slot name="add1"></slot>
            <DefaultButton
                v-if="buttonVisible"
                :icon="IconCirclePlusFilled"
                :label="buttonLabel"
                size="small"
                @click="triggerOpenModal"
                class-name="!rounded-md"
            />
    </ManagementFilterBar>
    <DefaultDialog
        v-model:visible="modal"
        :icon="dialogIcon"
        width-set="lg:!w-[40%] "
        :title="dialogTitle"
        :description="dialogDescription"
        :button-label="dialogButtonLabel"
        :loading="dialogButtonLoading"
        :button-disabled="dialogButtonDisabled"
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
import { IconCirclePlusFilled, IconX } from "@tabler/icons-vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import DefaultMessages from "../../Components/messages/DefaultMessages.vue";
import ManagementFilterBar from "../../Components/inputs/ManagementFilterBar.vue";
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
    dialogButtonDisabled: {
        type: Boolean,
        default: false,
    },
    buttonLabel: String,
    buttonVisible: {
        type: Boolean,
        default: true,
    },
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
