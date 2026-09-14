<template>
    <span class="hidden"></span>
</template>
<script setup>
import { useToast } from "primevue";
const toast = useToast();
const props = defineProps({
    severity: {
        type: String,
        default: "success",
    },
    summary: String,
    details: String,
});

const show = (payload) => {
    toast.add({
        severity: payload.status,
        summary: payload.title,
        detail: payload.message,
        life: 3000,
    });
};

defineExpose({
    show,
});
</script>
