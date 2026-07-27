<template>
    <TieredMenu
        :model="list"
        class="flex-1 !bg-transparent !min-w-1 z-1000 !border-0"
        :pt="{
            submenu: { class: '!min-w-[200px] dark:!bg-gray-600 !border-0' },
            separator: {
                class: 'my-2 !border-gray-400 dark:border-gray-600',
            },
        }"
    >
        <template #item="{ item }">
            <Link
                v-if="item.route"
                :href="item.route"
                :class="[
                    'flex items-center gap-3 px-[0.7rem] py-[10px] rounded hover:text-blue-600 w-full dark:text-white',
                    item.component === page.component
                        ? ' text-blue-600 dark:!text-blue-400 font-semibold'
                        : '',
                ]"
                v-tooltip.top="item.subItem ? '' : item.label"
            >
                <span class="relative inline-flex">
                    <component
                        :is="TablerIcons[item.icon]"
                        :size="item.subItem ? '20px' : '23px'"
                        :stroke-width="1.5"
                    />
                    <span
                        v-if="Number(item.badge ?? 0) > 0"
                        class="absolute -right-2 -top-2 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[9px] font-semibold leading-none text-white"
                    >
                        {{ item.badge }}
                    </span>
                </span>
                <span class="text-[12px]" v-if="item.subItem">{{
                    item.label
                }}</span>
            </Link>
            <a
                v-ripple
                :class="[
                    'flex items-center py-[10px] px-[0.7rem] !bg-transparent hover:text-blue-600 cursor-pointer dark:text-white',
                    item.key == activeMenu
                        ? ' text-blue-600 dark:!text-blue-400 font-semibold'
                        : '',
                ]"
                v-else
                v-tooltip.top="item.label"
            >
                <span class="relative inline-flex">
                    <component
                        :is="TablerIcons[item.icon]"
                        :size="item.subItem ? '20px' : '23px'"
                        :stroke-width="1.5"
                    />
                    <span
                        v-if="Number(item.badge ?? 0) > 0"
                        class="absolute -right-2 -top-2 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[9px] font-semibold leading-none text-white"
                    >
                        {{ item.badge }}
                    </span>
                </span>
            </a>
        </template>
    </TieredMenu>
</template>

<script setup>
import { Link, usePage } from "@inertiajs/vue3";
import * as TablerIcons from "@tabler/icons-vue";
import { onBeforeMount, ref } from "vue";
const page = usePage();
const activeMenu = ref(null);
const props = defineProps({
    list: {
        required: true,
        type: Array,
    },
});

const getActiveRoute = () => {
    for (const child of props.list) {
        const match = child.items.some(
            (sub) => sub.component == page.component,
        );

        if (match) {
            const active = child.items.find(
                (sub) => sub.component == page.component,
            );
            activeMenu.value = active.parent_id;
        }
    }
};

onBeforeMount(() => getActiveRoute());
</script>
