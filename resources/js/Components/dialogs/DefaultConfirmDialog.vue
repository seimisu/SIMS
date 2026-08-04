<template>
    <ConfirmDialog :group="group" class="w-[90%] sm:w-[24rem]">
        <template #container="{ message, acceptCallback, rejectCallback }">
            <div
                class="flex flex-col items-center rounded bg-surface-0 p-8 text-center dark:bg-surface-900"
            >
                <div
                    :class="[
                        'inline-flex h-20 w-20 -mt-16 items-center justify-center rounded-full text-white shadow',
                        severityClass(message.severity),
                    ]"
                >
                    <i :class="[message.icon ?? 'pi pi-question', '!text-3xl']"></i>
                </div>
                <span class="font-bold text-2xl block mb-2 mt-6">{{
                    message.header
                }}</span>
                <p class="mb-0 text-sm font-light">{{ message.message }}</p>
                <div class="flex items-center gap-2 mt-6">
                    <Button
                        :label="message.rejectLabel ?? 'Cancel'"
                        outlined
                        :severity="message.rejectSeverity ?? 'secondary'"
                        :class="[
                            'min-w-20 !rounded-lg',
                            outlinedButtonClass(message.rejectSeverity ?? 'secondary'),
                        ]"
                        @click="rejectCallback"
                        size="small"
                    ></Button>
                    <Button
                        :label="message.acceptLabel ?? 'Confirm'"
                        @click="acceptCallback"
                        size="small"
                        :severity="message.acceptSeverity ?? message.severity ?? 'danger'"
                        :class="[
                            'min-w-20 !rounded-lg',
                            filledButtonClass(message.acceptSeverity ?? message.severity ?? 'danger'),
                        ]"
                    ></Button>
                </div>
            </div>
        </template>
    </ConfirmDialog>
</template>
<script setup>
import { useConfirm } from "primevue/useconfirm";
const confirm = useConfirm();

const props = defineProps({
    group: {
        type: String,
        default: undefined,
    },
});

const severityClass = (severity = "danger") => ({
    danger: "bg-red-500",
    warn: "bg-amber-500",
    warning: "bg-amber-500",
    info: "bg-blue-500",
    success: "bg-green-500",
    secondary: "bg-slate-500",
}[severity] ?? "bg-red-500");

const filledButtonClass = (severity = "danger") => ({
    danger: "!border-red-500 !bg-red-500 hover:!border-red-600 hover:!bg-red-600",
    warn: "!border-amber-500 !bg-amber-500 hover:!border-amber-600 hover:!bg-amber-600",
    warning: "!border-amber-500 !bg-amber-500 hover:!border-amber-600 hover:!bg-amber-600",
    info: "!border-blue-500 !bg-blue-500 hover:!border-blue-600 hover:!bg-blue-600",
    success: "!border-green-500 !bg-green-500 hover:!border-green-600 hover:!bg-green-600",
    secondary: "!border-slate-500 !bg-slate-500 hover:!border-slate-600 hover:!bg-slate-600",
}[severity] ?? "!border-red-500 !bg-red-500 hover:!border-red-600 hover:!bg-red-600");

const outlinedButtonClass = (severity = "secondary") => ({
    danger: "!border-red-500 !text-red-500 hover:!bg-red-50",
    warn: "!border-amber-500 !text-amber-600 hover:!bg-amber-50",
    warning: "!border-amber-500 !text-amber-600 hover:!bg-amber-50",
    info: "!border-blue-500 !text-blue-500 hover:!bg-blue-50",
    success: "!border-green-500 !text-green-500 hover:!bg-green-50",
    secondary: "!border-slate-300 !text-slate-500 hover:!bg-slate-50",
}[severity] ?? "!border-slate-300 !text-slate-500 hover:!bg-slate-50");

const popupDialog = (onAccept, options = {}) => {
    confirm.require({
        group: props.group,
        message: options.message ?? "Do you want to delete this row?",
        header: options.header ?? "Confirmation",
        icon: options.icon ?? "pi pi-question",
        rejectLabel: options.rejectLabel ?? "Cancel",
        rejectSeverity: options.rejectSeverity ?? "secondary",
        acceptLabel: options.acceptLabel ?? "Delete",
        acceptSeverity: options.acceptSeverity ?? options.severity ?? "danger",
        severity: options.severity ?? "danger",

        accept: () => {
            if (typeof onAccept === "function") {
                onAccept();
            }
        },
        reject: () => {
            if (typeof options.onReject === "function") {
                options.onReject();
            }
        },
    });
};

defineExpose({
    popupDialog,
});
</script>
