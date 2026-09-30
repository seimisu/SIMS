<template>
    <div class="flex-1">
        <div class="text-2xl font-semibold text-slate-900 dark:text-white">{{ title }}</div>
        <div class="text-sm text-gray-500 mt-1 dark:text-gray-400">
            {{ description }}
        </div>
    </div>
</template>

<script setup>
defineProps({
    title: {
        type: String,
        default: null,
    },
    description: {
        type: String,
        default: null,
    },
});
</script>
