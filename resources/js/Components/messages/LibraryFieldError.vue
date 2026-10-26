<template>
    <p v-for="(message, key) in matchingErrors" :key="key" role="alert" class="text-xs text-red-600 dark:text-red-400">{{ message }}</p>
</template>
<script setup>
import { computed } from "vue";
const props = defineProps({ errors: Object, field: String });
const matchingErrors = computed(() => Object.fromEntries(Object.entries(props.errors ?? {}).filter(([key]) => key === props.field || key.startsWith(`${props.field}.`))));
</script>
