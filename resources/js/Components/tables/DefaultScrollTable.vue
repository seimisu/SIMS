<template>
    <DataTable
        v-model:expandedRows="expandedRows"
        :value="items"
        :rowGroupMode="rowGroupMode"
        :groupRowsBy="groupRowsBy"
        sortMode="single"
        dataKey="id"
        :sortOrder="1"
        scrollable
        :scrollHeight="scrollHeight"
        :pt="{
            root: {
                class: '!text-sm dark:!text-gray-100',
            },
            headerRow: {
                class: '!text-xs !font-bold dark:!bg-gray-900 dark:!text-gray-100',
            },
            pcPaginator: {
                root: 'dark:!bg-transparent ',
            },
            tableContainer: {
                class: ' border-t border-x rounded-xl !border-gray-200 dark:!border-gray-700 dark:!bg-gray-800 ',
            },
            bodycell: {
                class: 'dark:!border-gray-700 dark:!bg-gray-800 dark:!text-gray-100',
            },
            column: {
                root: { class: 'dark:!border-gray-700 dark:!bg-gray-900 dark:!text-gray-100' },
            },
            bodyRow: {
                class: '!bg-transparent dark:!border-gray-700 dark:!text-gray-100',
            },
            rowGroupHeader: {
                class: 'dark:!bg-transparent !bg-gray-50 dark:!text-gray-300',
            },
        }"
    >
        <template #groupheader="slotProps">
            <slot name="groupheader" v-bind="slotProps" />
        </template>
        <slot></slot>
        <template #groupfooter="slotProps">
            <slot name="groupfooter" v-bind="slotProps" />
        </template>
        <template #expansion="slotProps">
            <slot name="expansion" v-bind="slotProps"></slot>
        </template>

        <template #empty>
            <div
                class="flex justify-center font-semibold items-center gap-2 text-gray-500"
            >
                <IconDatabaseSearch size="20" />
                <div class="text-sm">No records available.</div>
            </div>
        </template>
    </DataTable>
</template>
<script setup>
import { IconDatabaseSearch } from "@tabler/icons-vue";
import { ref } from "vue";

const expandedRows = ref([]);
defineProps({
    items: [Array, Object, null],
    scrollHeight: String,
    size: {
        type: String,
    },
    groupRowsBy: {
        type: String,
        default: null,
    },
    grid: {
        type: Boolean,
        default: false,
    },
    rowGroupMode: {
        type: String,
        default: null,
    },
});
</script>
