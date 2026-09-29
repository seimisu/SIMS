<template>
    <TieredMenu
        :model="list"
        class="z-1000 !min-w-0 flex-1 !border-0 !bg-transparent px-2"
        :pt="{
            submenu: { class: '!min-w-[220px] !rounded-md !border-slate-200 !bg-white !p-1 !shadow-lg dark:!border-slate-700 dark:!bg-slate-900' },
            separator: {
                class: 'my-2 !border-slate-200 dark:!border-slate-700',
            },
        }"
    >
        <template #item="{ item }">
            <Link
                v-if="item.route"
                :href="item.route"
                :class="[
                    'flex min-h-10 w-full items-center rounded-md text-slate-600 transition-colors hover:bg-white hover:text-slate-950 dark:text-slate-100 dark:hover:bg-slate-600 dark:hover:text-white',
                    item.subItem ? 'justify-start gap-3 px-3' : 'justify-center',
                    item.component === page.component
                        ? 'bg-blue-600 !text-white shadow-sm'
                        : '',
                ]"
                v-tooltip.top="item.subItem ? '' : item.label"
            >
                <span class="relative inline-flex">
                    <component
                        :is="TablerIcons[navigationIcon(item)]"
                        :size="item.subItem ? '18px' : '20px'"
                        :stroke-width="1.7"
                    />
                    <span
                        v-if="item.badgeDot"
                        class="absolute -right-1 -top-1 h-2 w-2 rounded-full bg-red-500"
                    />
                    <span
                        v-else-if="Number(item.badge ?? 0) > 0"
                        class="absolute -right-2 -top-2 flex h-4 min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[9px] font-semibold leading-none text-white"
                    >
                        {{ item.badge }}
                    </span>
                </span>
                <span class="text-xs" v-if="item.subItem">{{
                    item.label
                }}</span>
            </Link>
            <a
                v-ripple
                :class="[
                    'flex min-h-10 w-full cursor-pointer items-center justify-center rounded-md text-slate-600 transition-colors hover:!bg-white hover:text-slate-950 dark:text-slate-100 dark:hover:!bg-slate-600 dark:hover:text-white',
                    item.key == activeMenu
                        ? '!bg-blue-600 !text-white shadow-sm'
                        : '',
                ]"
                v-else
                v-tooltip.top="item.label"
            >
                <span class="relative inline-flex">
                    <component
                        :is="TablerIcons[navigationIcon(item)]"
                        :size="item.subItem ? '18px' : '20px'"
                        :stroke-width="1.7"
                    />
                    <span
                        v-if="item.badgeDot"
                        class="absolute -right-1 -top-1 h-2 w-2 rounded-full bg-red-500"
                    />
                    <span
                        v-else-if="Number(item.badge ?? 0) > 0"
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
import { navigationIcon } from "../../Utils/navigationIcons";
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
        const match = child.items?.some(
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
