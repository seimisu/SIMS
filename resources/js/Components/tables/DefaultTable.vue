<template>
    <DataTable
        v-model:expandedRows="expandedRows"
        :value="items"
        paginator
        :rows="pagination.perPage"
        :totalRecords="pagination.total"
        removableSort
        :lazy="true"
        dataKey="id"
        :rowGroupMode="rowGroupMode"
        :groupRowsBy="groupRowsBy"
        sortMode="single"
        :size="size"
        :first="(pagination.currentPage - 1) * pagination.perPage"
        :loading="loading"
        @page="onPageChange"
        responsiveLayout="scroll"
        :showGridlines="grid"
        :pt="{
            root: {
                class: '!text-sm dark:!bg-transparent dark:!text-gray-100',
            },
            header: {
                class: 'dark:!border-gray-700 dark:!bg-gray-800',
            },
            headerRow: {
                class: '!text-xs !font-bold !text-blue-300 dark:!bg-gray-800 dark:!text-gray-300',
            },
            pcPaginator: {
                root: 'dark:!bg-transparent dark:!text-gray-100',
            },
            tableContainer: {
                class: ' border-t border-x rounded-xl !border-gray-200 dark:!border-gray-700 dark:!bg-gray-800 ',
            },
            bodycell: {
                class: 'dark:!border-gray-700 dark:!bg-gray-800 dark:!text-gray-100',
            },
            column: {
                root: { class: 'dark:!border-gray-700 dark:!bg-gray-800 dark:text-gray-300' },
            },
            bodyRow: {
                class: '!bg-transparent dark:!bg-gray-800 dark:!border-gray-700 dark:!text-gray-100 dark:hover:!bg-gray-700',
            },
            rowGroupHeader: {
                class: 'dark:!bg-transparent !bg-slate-50 dark:!text-gray-300',
            },
            loadingIcon: {
                class: 'text-blue-300 ',
            },
        }"
    >
        <template #groupheader="slotProps">
            <slot name="groupheader" v-bind="slotProps" />
        </template>
        <slot></slot>
        <template #expansion="slotProps">
            <slot name="expansion" v-bind="slotProps"></slot>
        </template>

        <template #paginatorstart>
            <div class="text-gray-500 text-sm dark:text-gray-400">
                Showing
                <span class="font-semibold">{{
                    (pagination.currentPage - 1) * pagination.perPage + 1
                }}</span>
                to
                <span class="font-semibold">{{
                    Math.min(
                        pagination.currentPage * pagination.perPage,
                        pagination.total,
                    )
                }}</span>
                of
                <span class="font-semibold">{{ pagination.total }}</span>
                entries
            </div>
        </template>
        <template #empty>
            <div
                class="flex justify-center font-semibold items-center gap-2 text-gray-500 dark:text-gray-400"
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
const props = defineProps({
    items: [Array, Object],
    pagination: Object,
    loading: Boolean,
    grid: {
        type: Boolean,
        default: false,
    },
    size: {
        type: String,
    },
    groupRowsBy: {
        type: String,
        default: null,
    },
    rowGroupMode: {
        type: String,
        default: null,
    },
});

const emit = defineEmits(["paginate"]);

function onPageChange(event) {
    const page = event.page + 1;
    emit("paginate", page);
}
</script>

<style scoped>
::v-deep(.p-paginator) {
    justify-content: right;
}

::v-deep(.p-paginator .p-paginator-page),
::v-deep(.p-paginator .p-paginator-first),
::v-deep(.p-paginator .p-paginator-prev),
::v-deep(.p-paginator .p-paginator-next),
::v-deep(.p-paginator .p-paginator-last) {
    border-radius: 10px !important;
    padding: 0.5rem 0.75rem;
}

::v-deep(.p-datatable-paginator-bottom) {
    margin-top: 1rem;
    border: none !important;
}

::v-deep(.p-paginator-current) {
    order: -1;
    margin-right: 1rem !important;
    display: block;
}

::v-deep(.p-datatable-header-cell) {
    background-color: transparent !important;
}

:global(.dark) ::v-deep(.p-datatable),
:global(.dark) ::v-deep(.p-datatable-table),
:global(.dark) ::v-deep(.p-datatable-thead > tr > th),
:global(.dark) ::v-deep(.p-datatable-tbody > tr),
:global(.dark) ::v-deep(.p-datatable-tbody > tr > td),
:global(.dark) ::v-deep(.p-datatable-header),
:global(.dark) ::v-deep(.p-datatable-table-container) {
    background: #1f2937 !important;
    color: #f3f4f6 !important;
}

:global(.dark) ::v-deep(.p-datatable-thead > tr > th),
:global(.dark) ::v-deep(.p-datatable-tbody > tr > td) {
    border-color: #374151 !important;
}

:global(.dark) ::v-deep(.p-datatable-tbody > tr:hover > td) {
    background: #374151 !important;
}

::v-deep(.p-datatable-column-header-content) {
    &:where(.dark, .dark *) {
        color: #d1d1d1;
    }
}
::v-deep(.p-datatable-mask.p-overlay-mask) {
    background-color: #ffffff71 !important;
    border-radius: 1rem !important;
}
/* ::v-deep(.p-datatable-tbody) {
    background-color: transparent !important;
} */
</style>
