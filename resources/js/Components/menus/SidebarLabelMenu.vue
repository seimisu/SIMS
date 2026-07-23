<template>
    <PanelMenu
        :model="list"
        class="flex-1"
        v-model:expandedKeys="expandedKeys"
        unstyled
        :pt="{
            separator: {
                class: 'my-2 !border-gray-400 dark:border-gray-600',
            },
        }"
    >
        <template #item="{ item }">
            <Link
                v-if="item.route"
                :href="item.route"
                preserve-state
                preserve-scroll
                :class="[
                    'flex items-center gap-2 px-[0.7rem] py-[10px]  hover:text-blue-500 transition-all duration-300 w-full',
                    item.component === page.component
                        ? ' text-blue-600 dark:text-blue-400 font-semibold'
                        : '',
                    item.subItem
                        ? 'ml-5 border-l border-l-gray-400'
                        : 'py-[12px]',
                ]"
            >
                <component
                    :is="TablerIcons[item.icon]"
                    :size="item.subItem ? '20px' : '23px'"
                    v-show="item.subItem ? false : true"
                    :stroke-width="1.5"
                />
                <span class="min-w-0 flex-1 text-nowrap truncate text-xs capitalize">{{
                    item.label
                }}</span>
                <span
                    v-if="Number(item.badge ?? 0) > 0"
                    class="ml-auto rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold leading-none text-red-600"
                >
                    {{ item.badge }}
                </span>
            </Link>

            <a
                v-else
                v-ripple
                :class="[
                    'flex justify-between px-2 py-[12px] items-center cursor-pointer text-surface-700 dark:text-surface-0 w-full hover:text-blue-500 transition-all duration-300',
                    active === item.active
                        ? ' text-blue-600 font-semibold'
                        : '',
                ]"
            >
                <div class="flex items-center">
                    <component
                        :is="TablerIcons[item.icon]"
                        size="23px"
                        :stroke-width="1.5"
                    />
                    <span class="ml-2 text-xs text-nowrap capitalize">{{
                        item.label
                    }}</span>
                    <span
                        v-if="Number(item.badge ?? 0) > 0"
                        class="ml-2 rounded-full bg-red-100 px-2 py-0.5 text-[10px] font-semibold leading-none text-red-600"
                    >
                        {{ item.badge }}
                    </span>
                </div>

                <span
                    v-if="item.items"
                    class="pi pi-angle-down text-primary ml-auto"
                />
            </a>
        </template>
    </PanelMenu>
</template>
<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import * as TablerIcons from "@tabler/icons-vue";
import { ref, onBeforeMount, watch } from "vue";
const page = usePage();
const expandedKeys = ref({});
const active = page.component.split("/")[0];
const componentActive = page.component;
const props = defineProps({
    list: {
        required: true,
        type: Array,
    },
});
function syncExpandedKeys() {
    for (const item of props.list) {
        if (item.items) {
            const match = item.items.some(
                (sub) => sub.component === componentActive,
            );

            if (match && item.label) {
                expandedKeys.value[item.key] = true;
            }
        }
    }
}
onBeforeMount(() => {
    syncExpandedKeys();
});

watch(
    () => page.component,
    () => {
        syncExpandedKeys();
    },
);
</script>
