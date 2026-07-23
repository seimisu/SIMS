<template>
    <Head title="School Campus" />
    <AuthLayout>
        <div class="flex flex-col w-full h-full gap-10">
            <div class="flex flex-col md:flex-row justify-between items-center">
                <div class="w-full flex items-center justify-between">
                    <div class="flex items-center gap-2">
                        <Avatar
                            size="large"
                            class="bg-blue-100! text-blue-500! rounded-xl! shadow shadow-blue-300!"
                        >
                            <IconSchool size="30" />
                        </Avatar>
                        <div class="">
                            <h1 class="text-xl font-semibold">
                                {{ campus?.name }}
                            </h1>
                            <div
                                class="text-sm text-gray-600 flex items-center gap-1"
                            >
                                <IconMapPin size="16" />
                                <span>
                                    {{ campus?.address }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex-1 flex gap-2">
                <div class="flex-3 rounded-xl p-3">
                    {{ programs }}
                    <!-- <DefaultSelectionTable
                        :items="programs?.data"
                        :pagination="{
                            total: programs?.total,
                            perPage: programs?.per_page,
                            currentPage: programs?.current_page,
                        }"
                        @selected="selectPrograms"
                        :loading="loading.programTable"
                        @paginate="loadPage"
                    >
                        <Column header="Name">
                            <template #body="{ data }">
                                {{ data.course }}
                            </template>
                        </Column>
                    </DefaultSelectionTable> -->
                </div>
                <div class="flex-1"></div>
            </div>
        </div>
    </AuthLayout>
</template>
<script setup>
import AuthLayout from "../../Layouts/AuthLayout.vue";
import { Head, router, usePage } from "@inertiajs/vue3";
import { IconMapPin, IconSchool } from "@tabler/icons-vue";
import { onMounted, ref, watch } from "vue";
import DefaultSelectionTable from "../../Components/tables/DefaultSelectionTable.vue";

const page = usePage();
const campus = ref(null);
const search = ref({
    programs: null,
});

const props = defineProps({
    programs: Object,
});

// const programs = ref(page.props?.programs ?? []);
const loading = ref({
    programTable: false,
});

const loadPage = (page) => {
    router.get(
        route("schoolCoordinator"),
        {
            page,
            search: search.value.programs,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const selectPrograms = () => {
    console.log("test");
};

// watch(
//     () => search.value.programs,
//     () => {
//         clearTimeout(timerBounce.value);
//         timerBounce.value = setTimeout(() => {
//             loadPage(1);
//         }, 300);
//     },
// );

onMounted(() => {
    campus.value = page.props?.campus;
});
</script>
