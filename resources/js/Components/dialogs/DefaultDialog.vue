<template>
    <div class="flex justify-center">
        <Dialog
            v-model:visible="visible"
            modal
            :class="[
                ' w-[90%] sm:w-1/3 dark:!bg-gray-800 dark:!text-gray-300 dark:!border-gray-600',
                maximizable ? 'rounded-none' : '!rounded-[15px]',
                widthSet,
            ]"
            :maximizable="maximizable"
        >
            <template #header>
                <div class="flex items-center justify-center gap-2 px-2">
                    <Avatar size="medium">
                        <component :is="icon" size="20"></component>
                    </Avatar>

                    <div class="font-semibold antialiased">
                        {{ title }}
                    </div>
                </div>
            </template>
            <div class="mt-3 px-1">
                <div class="font-semibold text-sm">General Information</div>
                <span class="block text-gray-400 font-light indent-5 text-xs">
                    {{ description }}
                </span>
            </div>
            <form @submit.prevent="submit">
                <div :class="absoluteDiv ? 'overflow-y-auto lg:max-h-170' : ''">
                    <slot name="forms" />
                </div>

                <div
                    class="flex justify-between items-center w-full gap-5 mt-7"
                >
                    <div class="flex-1">
                        <slot name="message" />
                    </div>
                    <div class="flex items-center gap-3" v-show="!hideFooter">
                        <Button
                            type="button"
                            size="small"
                            class="!px-5 !rounded-md"
                            label="Cancel"
                            severity="secondary"
                            outlined
                            @click="visible = false"
                        >
                            <template #icon>
                                <component
                                    :is="buttonIcon"
                                    :size="size == 'small' ? 18 : 22"
                                    v-if="buttonIcon"
                                ></component>
                            </template>
                        </Button>
                        <Button
                            type="submit"
                            :label="buttonLabel"
                            :loading="loading"
                            :disabled="buttonDisabled"
                            size="small"
                            class="!px-5 !rounded-md"
                            @click="emit('submit-form')"
                        >
                            <template #icon>
                                <component
                                    :is="buttonIcon"
                                    :size="size == 'small' ? 18 : 22"
                                    v-if="buttonIcon"
                                ></component>
                            </template>
                        </Button>
                    </div>
                </div>
            </form>
        </Dialog>
    </div>
</template>

<script setup>
defineProps({
    description: String,
    title: String,
    icon: Function,
    loading: Boolean,
    buttonDisabled: {
        type: Boolean,
        default: false,
    },
    buttonLabel: {
        type: String,
        default: "Submit",
    },
    buttonIcon: {
        type: [Object, String, Function],
        default: null,
    },
    maximizable: {
        type: Boolean,
        default: false,
    },
    hideFooter: {
        type: Boolean,
        default: false,
    },
    widthSet: String,
    absoluteDiv: {
        type: Boolean,
        default: false,
    },
});

const visible = defineModel("visible");

const emit = defineEmits(["submit-form"]);
</script>
<style>
.p-dialog-header {
    padding: 10px !important;
}
</style>
