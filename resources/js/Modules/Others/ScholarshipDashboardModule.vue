<template>
    <div class="flex flex-col w-full gap-5">
        <div class="flex-1 flex flex-col gap-5">
            <div class="flex flex-col leading-tight gap-0">
                <div
                    class="text-sm capitalize text-gray-500 flex items-center gap-1"
                >
                    <div class="capitalize">Analytics</div>
                    <div
                        class="text-xs bg-green-50 text-green-600 py-1 px-4 rounded-2xl"
                    >
                        <p class="font-medium">● Regional Staff</p>
                    </div>
                </div>
                <div class="text-4xl uppercase font-semibold">Dashboard</div>
                <div class="text-sm text-gray-500">
                    Monitor scholar requests, regional statistics, and manage
                    daily operations.
                </div>
            </div>
            <div class="flex items-center justify-between">
                <SelectButton
                    size="small"
                    v-model="FilterDate.value"
                    :options="FilterDate.Options"
                    optionLabel="label"
                    optionValue="value"
                    :allow-empty="false"
                    aria-labelledby="basic"
                />
            </div>
        </div>
        <div class="flex flex-col">
            <div class="flex items-start w-full gap-4">
                <Card class="flex-1">
                    <template #content>
                        <div class="flex justify-between">
                            <div class="flex flex-col">
                                <div class="text-sm">Total Active Scholars</div>
                                <div class="text-4xl font-semibold">1,245</div>
                            </div>
                            <Avatar
                                class="!rounded-xl border border-green-500 shadow shadow-green-300 !text-green-600 !bg-green-100"
                                size="large"
                            >
                                <IconUser size="25" />
                            </Avatar>
                        </div>
                    </template>
                    <template #footer>
                        <div class="text-sm text-green-600 font-medium">
                            ↑ 8.2% from last {{ FilterDate.value }}
                        </div>
                    </template>
                </Card>
                <Card class="flex-1">
                    <template #subtitle>
                        <div class="text-sm">Total Scholars</div>
                    </template>
                    <template #content>
                        <div class="text-3xl font-semibold">1,245</div>
                    </template>
                </Card>
                <Card class="flex-1">
                    <template #content> ... </template>
                </Card>

                <Card class="flex-1">
                    <template #content> ... </template>
                </Card>
            </div>
        </div>
    </div>
</template>
<script setup>
import { IconUser } from "@tabler/icons-vue";
import { onMounted, ref, watch } from "vue";
import { router } from "@inertiajs/vue3";
const FilterDate = ref({
    value: "year",
    Options: [
        { label: "Year", value: "year" },
        { label: "Monthly", value: "month" },
        { label: "Weekly", value: "week" },
    ],
});
watch(
    () => FilterDate.value.value,
    (value) => {
        router.get(
            route("dashboard"),
            { filter: value },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
            },
        );
    },
);
</script>
