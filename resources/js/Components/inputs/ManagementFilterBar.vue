<template>
    <div class="management-filter-bar">
        <div v-if="searchable" class="management-filter-search">
            <IconSearch
                :size="17"
                class="pointer-events-none absolute left-3 top-1/2 z-10 -translate-y-1/2 text-slate-400 dark:text-gray-400"
            />
            <InputText
                :model-value="modelValue"
                :placeholder="searchPlaceholder"
                class="!h-[38px] !w-full !rounded-md !pl-9 !text-sm"
                @update:model-value="$emit('update:modelValue', $event)"
            />
        </div>
        <div class="management-filter-controls">
            <slot />
        </div>
    </div>
</template>

<script setup>
import { IconSearch } from "@tabler/icons-vue";

defineProps({
    modelValue: {
        default: null,
    },
    searchable: {
        type: Boolean,
        default: true,
    },
    searchPlaceholder: {
        type: String,
        default: "Search",
    },
});

defineEmits(["update:modelValue"]);
</script>

<style scoped>
.management-filter-bar {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    width: 100%;
    padding: 0.75rem;
    border: 1px solid #e2e8f0;
    border-radius: 0.375rem;
    background: #f8fafc;
}

.management-filter-search {
    position: relative;
    min-width: min(18rem, 100%);
    flex: 1 1 18rem;
}

.management-filter-controls {
    display: flex;
    flex: 0 1 auto;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
}

:global(.dark .management-filter-bar) {
    border-color: #374151;
    background: #1f2937;
}

@media (max-width: 767px) {
    .management-filter-search,
    .management-filter-controls {
        width: 100%;
    }
}
</style>
