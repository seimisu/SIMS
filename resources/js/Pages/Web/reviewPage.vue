<template>
    <Head title="Review" />
    <AuthLayout>
        <div class="flex flex-col w-full h-full gap-5">
            <div class="flex flex-col lg:flex-row items-center space-x-0 gap-4">
                <HeaderModule
                    title="Pending Scholar Review"
                    description="This section allows administrators to review and validate pending scholar submissions before approval."
                />
            </div>
            <div class="flex-1 flex flex-col gap-2">
                <DefaultSelectionTable
                    :items="page.props.files.data"
                    :pagination="{
                        total: page.props.files.total,
                        perPage: page.props.files.per_page,
                        currentPage: page.props.files.current_page,
                    }"
                    @selected="selectScholar"
                    :loading="loading.table"
                    @paginate="loadPage"
                >
                    <Column header="Filename">
                        <template #body="props">
                            <div class="flex items-start justify-between gap-3">
                                <!-- File Info -->
                                <div class="flex flex-col">
                                    <div class="font-medium text-gray-800">
                                        {{ props.data.filename }}
                                    </div>

                                    <div class="text-xs text-gray-400">
                                        Uploaded file
                                    </div>
                                </div>

                                <!-- Download Button -->
                                <a
                                    :href="props.data.file_url"
                                    download
                                    class="flex items-center gap-1 text-xs px-3 py-1.5 rounded-full bg-blue-50 text-blue-600 border border-blue-200 hover:bg-blue-100 transition"
                                >
                                    <IconDownload size="14" />
                                    Download
                                </a>
                            </div>
                        </template>
                    </Column>
                  <Column header="Regional Office">
    <template #body="props">
        <div class="flex items-center">
            <span
                class="px-3 py-1 text-xs font-medium rounded-full
                       bg-indigo-50 text-indigo-600 border border-indigo-200"
            >
                {{ props.data.region_office ?? 'N/A' }}
            </span>
        </div>
    </template>
</Column>
                    <Column header="Created By">
                        <template #body="props">
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <!-- Creator Info -->
                                <div class="flex flex-col">
                                    <div
                                        class="text-sm font-medium text-gray-800"
                                    >
                                        {{ props.data.created_by }}
                                    </div>

                                    <div
                                        class="text-xs text-gray-400 flex items-center gap-1"
                                    >
                                        <IconCalendar size="12" />
                                        {{ props.data.formatted_date }}
                                    </div>
                                </div>

                                <!-- Optional badge -->
                              
                            </div>
                        </template>
                    </Column>
                    <Column header="Validation Status">
                        <template #body="props">
                            <div class="min-w-[180px]">
                                <!-- Progress -->
                                <div
                                    class="flex items-center justify-between mb-1"
                                >
                                    <span
                                        class="text-xs text-gray-500 font-medium"
                                    >
                                        Validation Progress
                                    </span>

                                    <span class="text-xs font-semibold">
                                        {{ props.data.active_temp_count }} /
                                        {{ props.data.temp_count }}
                                    </span>
                                </div>

                                <!-- Progress Bar -->
                                <div
                                    class="w-full bg-gray-100 rounded-full h-2 overflow-hidden"
                                >
                                    <div
                                        class="h-full rounded-full transition-all duration-300"
                                        :class="{
                                            'bg-green-500':
                                                props.data.active_temp_count ===
                                                props.data.temp_count,
                                            'bg-yellow-500':
                                                props.data.active_temp_count >
                                                    0 &&
                                                props.data.active_temp_count <
                                                    props.data.temp_count,
                                            'bg-red-500':
                                                props.data.active_temp_count ===
                                                0,
                                        }"
                                        :style="{
                                            width: `${
                                                props.data.temp_count
                                                    ? (props.data
                                                          .active_temp_count /
                                                          props.data
                                                              .temp_count) *
                                                      100
                                                    : 0
                                            }%`,
                                        }"
                                    />
                                </div>

                                <!-- Status -->
                                <div class="mt-2 flex justify-end">
                                    <div
                                        v-if="
                                            props.data.active_temp_count ===
                                            props.data.temp_count
                                        "
                                        class="text-green-600 bg-green-50 border border-green-200 rounded-full px-3 py-1 text-xs font-medium flex items-center gap-1"
                                    >
                                        <IconCircleCheckFilled size="14" />
                                        Completed
                                    </div>

                                    <div
                                        v-else-if="
                                            props.data.active_temp_count > 0
                                        "
                                        class="text-yellow-600 bg-yellow-50 border border-yellow-200 rounded-full px-3 py-1 text-xs font-medium flex items-center gap-1"
                                    >
                                        <IconProgressCheck size="14" />
                                        In Progress
                                    </div>

                                    <div
                                        v-else
                                        class="text-red-600 bg-red-50 border border-red-200 rounded-full px-3 py-1 text-xs font-medium flex items-center gap-1"
                                    >
                                        <IconExclamationCircleFilled
                                            size="14"
                                        />
                                        Pending
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>
                </DefaultSelectionTable>
            </div>
        </div>
        <DrawerScholarVerificationModule
            v-if="selectedDrawer"
            v-model="selectedDrawer"
        />
    </AuthLayout>
</template>
<script setup>
import AuthLayout from "../../Layouts/AuthLayout.vue";
import HeaderModule from "../../Modules/Others/HeaderModule.vue";
import UploadInput from "../../Components/inputs/UploadInput.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import DefaultSelectionTable from "../../Components/tables/DefaultSelectionTable.vue";
import DrawerScholarVerificationModule from "../../Modules/Others/DrawerScholarVerificationModule.vue";
import {
    IconCalendar,
    IconDownload,
    IconExclamationCircle,
    IconExclamationCircleFilled,
    IconUserUp,
    IconCircleCheckFilled,
    IconProgressCheck,

} from "@tabler/icons-vue";
import { useForm, progress, usePage, Head, router } from "@inertiajs/vue3";
import { ref } from "vue";

const page = usePage();
const selectedDrawer = ref(false);
const loading = ref({
    table: false,
});

const selectScholar = (e) => {
    router.reload({
        only: ["selected", "courseOption", "schoolOption"],
        data: { id: e.hash_id },
        preserveState: true,
        preserveScroll: true,
        showProgress: true,
        replace: true,
        onFinish: () => {
            selectedDrawer.value = true;
        },
    });
};

const loadPage = (page) => {
    router.get(
        route("review"),
        {
            page,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};
</script>
