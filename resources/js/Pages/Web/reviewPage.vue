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
                            <div class="flex flex-col">
                                <div>{{ props.data.filename }}</div>
                                <a
                                    :href="props.data.file_url"
                                    download
                                    class="text-blue-500 underline text-xs"
                                    >Click to download</a
                                >
                            </div>
                        </template>
                    </Column>
                    <Column header="Regional Office" field="region_office">
                    </Column>
                    <Column header="Created By">
                        <template #body="props">
                            <div class="flex flex-col">
                                <div class="text-xs text-gray-600">
                                    {{ props.data.created_by }}
                                </div>
                                <div class="text-sm">
                                    {{ props.data.formatted_date }}
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column>
                        <template #header>
                            <div class="flex w-full justify-center">
                                <div class="font-bold">Status</div>
                            </div>
                        </template>
                        <template #body="props">
                            <div class="flex w-full justify-center">
                                <div
                                    v-if="props.data.status == 'pending'"
                                    class="text-yellow-500 capitalize"
                                >
                                    • {{ props.data.status }}
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
    IconExclamationCircle,
    IconExclamationCircleFilled,
    IconUserUp,
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
