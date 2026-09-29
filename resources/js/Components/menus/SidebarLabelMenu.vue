<template>
    <div class="flex flex-col gap-1">
        <p class="px-3 pb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-300">
            Navigation
        </p>

        <template v-for="item in primaryItems" :key="item.key">
            <SidebarItem :item="item" :open="expandedKey === item.key" @toggle="toggle(item)" />
        </template>

        <template v-if="systemItems.length">
            <div class="mx-3 my-3 border-t border-slate-200 dark:border-slate-600" />
            <p class="px-3 pb-2 text-[10px] font-semibold uppercase tracking-wider text-slate-400 dark:text-slate-300">
                System
            </p>
            <template v-for="item in systemItems" :key="item.key">
                <SidebarItem :item="item" :open="expandedKey === item.key" @toggle="toggle(item)" />
            </template>
        </template>
    </div>
</template>

<script setup>
import { computed, defineComponent, h, onBeforeMount, ref, watch } from "vue";
import { Link, usePage } from "@inertiajs/vue3";
import * as TablerIcons from "@tabler/icons-vue";
import { navigationIcon } from "../../Utils/navigationIcons";

const page = usePage();
const props = defineProps({
    list: { required: true, type: Array },
});

const expandedKey = ref(null);
const systemSlugs = ["admin-setting", "backup-restore"];
const primaryItems = computed(() => props.list.filter((item) => !systemSlugs.includes(item.slug)));
const systemItems = computed(() => props.list.filter((item) => systemSlugs.includes(item.slug)));

const isCurrent = (item) => item.component === page.component;
const hasCurrentChild = (item) => item.items?.some(isCurrent) ?? false;
const syncOpenSection = () => {
    expandedKey.value = props.list.find(hasCurrentChild)?.key ?? null;
};
const toggle = (item) => {
    if (!item.items?.length) return;
    expandedKey.value = expandedKey.value === item.key ? null : item.key;
};

const badge = (item) => {
    if (item.badgeDot) {
        return h("span", { class: "size-2 rounded-full bg-red-500" });
    }
    if (Number(item.badge ?? 0) > 0) {
        return h(
            "span",
            { class: "rounded-full bg-red-50 px-2 py-0.5 text-[10px] font-bold text-red-600 dark:bg-red-950/60 dark:text-red-300" },
            item.badge,
        );
    }
};

const SidebarItem = defineComponent({
    props: {
        item: { type: Object, required: true },
        open: Boolean,
    },
    emits: ["toggle"],
    setup(childProps, { emit }) {
        return () => {
            const item = childProps.item;
            const children = item.items ?? [];
            const active = isCurrent(item) || hasCurrentChild(item);
            const Icon = TablerIcons[navigationIcon(item)] ?? TablerIcons.IconCircle;
            const rowClass = [
                "group flex min-h-10 w-full items-center gap-3 rounded-md px-3 text-left text-[13px] transition-colors",
                active
                    ? "bg-blue-600 font-semibold text-white shadow-sm"
                    : "font-medium text-slate-600 hover:bg-white hover:text-slate-950 dark:text-slate-100 dark:hover:bg-slate-600 dark:hover:text-white",
            ];
            const rowChildren = [
                h(Icon, { size: 19, strokeWidth: 1.8, class: "shrink-0" }),
                h("span", { class: "min-w-0 flex-1 truncate" }, item.label),
                badge(item),
            ];

            if (children.length) {
                rowChildren.push(
                    h(TablerIcons.IconChevronRight, {
                        size: 15,
                        strokeWidth: 2,
                        class: ["shrink-0 text-slate-400 transition-transform", childProps.open ? "rotate-90" : ""],
                    }),
                );
            }

            const row = item.route
                ? h(Link, { href: item.route, preserveScroll: true, class: rowClass }, () => rowChildren)
                : h("button", { type: "button", class: rowClass, onClick: () => emit("toggle") }, rowChildren);

            const childRows = childProps.open
                ? h(
                      "div",
                      { class: "relative ml-[21px] mt-1 flex flex-col gap-0.5 border-l border-slate-300 pl-3 dark:border-slate-500" },
                      children.map((child) =>
                          h(
                              Link,
                              {
                                  key: child.key,
                                  href: child.route,
                                  preserveScroll: true,
                                  class: [
                                      "flex min-h-8 items-center rounded-md px-3 text-xs transition-colors",
                                      isCurrent(child)
                                          ? "bg-blue-50 font-semibold text-blue-700 dark:bg-slate-600 dark:text-blue-200"
                                          : "text-slate-500 hover:bg-white hover:text-slate-900 dark:text-slate-200 dark:hover:bg-slate-600 dark:hover:text-white",
                                  ],
                              },
                              () => [h("span", { class: "min-w-0 flex-1 truncate" }, child.label), badge(child)],
                          ),
                      ),
                  )
                : null;

            return h("div", [row, childRows]);
        };
    },
});

onBeforeMount(syncOpenSection);
watch(() => page.component, syncOpenSection);
</script>
