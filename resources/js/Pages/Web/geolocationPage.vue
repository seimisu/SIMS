<template>
    <Head title="Barangay" />
    <AuthLayout>
        <div class="locations-page flex h-full min-h-0 w-full flex-col gap-4 overflow-hidden text-slate-800 dark:text-gray-100">
            <div class="shrink-0 border-b border-slate-200 pb-4 dark:border-gray-700">
                <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                    <div class="flex min-w-0 items-start gap-3"><div class="flex size-10 shrink-0 items-center justify-center rounded-md bg-blue-600 text-white"><IconMap2 :size="21" /></div><HeaderModule title="Geographical Locations" description="Import and maintain the system location directory." /></div>
                    <DefaultButton size="small" label="Import Locations" severity="secondary" outlined :icon="IconFileImport" class="self-start xl:self-auto" @click="toggleModal()" />
                </div>
            </div>
            <div class="flex min-h-0 flex-1 flex-col gap-3 overflow-auto">
                <ToolbarModule
                    v-model="searchInput"
                    @deleteSearch="clearSearch"
                    @saveForm="submitForm"
                    button-label="Create"
                    dialog-title="Upload Geographical Location"
                    dialog-description="Add a new location and provide relevant details for it."
                    :dialog-button-loading="uploadFileForm.processing"
                    :dialog-icon="IconFileImport"
                    dialog-button-label="Save"
                    :message-has-errors="uploadFileForm.hasErrors"
                    :message-errors="uploadFileForm.errors"
                    @buttonOpenModal="toggleModal()"
                    message-type="error"
                    :button-visible="false"
                    ref="toolbarRef"
                >
                    <template #form>
                        <div class="mt-5">
                            <UploadInput
                                ref="uploadRef"
                                @select-files="handleFiles"
                                @remove-file="clearForm"
                                :progress="progressUpload"
                                accept=".csv, application/vnd.ms-excel, application/vnd.openxmlformats-officedocument.spreadsheetml.sheet"
                            ></UploadInput>
                        </div>
                    </template>
                </ToolbarModule>
                <!-- <DefaultTable
                    :items="page.props.barangay.data"
                    :pagination="{
                        total: page.props.barangay.total,
                        perPage: page.props.barangay.per_page,
                        currentPage: page.props.barangay.current_page,
                    }"
                    @paginate="loadPage"
                >
                    <Column field="name" header="Name"> </Column>

                    <Column field="old_name">
                        <template #header>
                            <div class="w-full flex justify-start">
                                <p class="font-semibold">Old Name</p>
                            </div>
                        </template>
                        <template #body="props">
                            <div class="flex justify-start w-full">
                                <div v-if="props.data.old_name">
                                    {{ props.data.old_name }}
                                </div>
                                <div
                                    class="text-gray-400 text-xs font-light"
                                    v-else
                                >
                                    not set yet
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column field="code">
                        <template #header>
                            <div class="w-full flex justify-center">
                                <p class="font-semibold">Barangay Code</p>
                            </div>
                        </template>
                        <template #body="props">
                            <div
                                class="flex items-center justify-center w-full"
                            >
                                <div class="text-xs font-semibold">
                                    {{ props.data.code }}
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column field="city_code">
                        <template #header>
                            <div class="w-full flex justify-start">
                                <p class="font-semibold">Municipality</p>
                            </div>
                        </template>
                        <template #body="props">
                            <div class="flex justify-start w-full">
                                <div>{{ props.data.city_code.name }}</div>
                            </div>
                        </template>
                    </Column>
                    <Column field="status" class="w-[5%]">
                        <template #header>
                            <div class="w-full flex justify-center">
                                <p class="font-semibold">Status</p>
                            </div>
                        </template>
                        <template #body="props">
                            <div
                                class="flex items-center justify-center w-full"
                            >
                                <DefaultToggle
                                    :check-icon="IconCheck"
                                    :un-check-icon="IconX"
                                    v-model="props.data.is_active"
                                    @update-value="updateStatus(props.data)"
                                />
                            </div>
                        </template>
                    </Column>
                    <Column field="option" class="w-[5%]">
                        <template #body="prop">
                            <div class="flex w-full justify-end">
                                <Button
                                    text
                                    v-tooltip.top="'Options'"
                                    rounded
                                    size="small"
                                    severity="secondary"
                                    icon="pi pi-ellipsis-v"
                                    @click="(e) => toggleOption(e, prop.data)"
                                />
                                <Menu
                                    ref="menu"
                                    :model="menuItems"
                                    :popup="true"
                                >
                                    <template #item="{ item, props }">
                                        <a
                                            v-ripple
                                            class="flex items-center"
                                            v-bind="props.action"
                                            @click="item.command"
                                        >
                                            <div>
                                                <component
                                                    :is="item.icon"
                                                    :class="item.class"
                                                    size="20"
                                                    stroke-width="1.5"
                                                ></component>
                                            </div>
                                            <span class="ml-2 text-xs">{{
                                                item.label
                                            }}</span>
                                        </a>
                                    </template>
                                </Menu>
                            </div>
                        </template>
                    </Column>
                </DefaultTable> -->
            </div>
        </div>
        <DefaultToast ref="toastRef" />
        <DefaultConfirmDialog ref="confirmRef" />
    </AuthLayout>
</template>
<script setup>
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import AuthLayout from "../../Layouts/AuthLayout.vue";
import HeaderModule from "../../Modules/Others/HeaderModule.vue";
import DefaultTable from "../../Components/tables/DefaultTable.vue";
import ToolbarModule from "../../Modules/Others/ToolbarModule.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import TextInput from "../../Components/inputs/TextInput.vue";
import DefaultToggle from "../../Components/toggleswitches/DefaultToggle.vue";
import DefaultToast from "../../Components/messages/DefaultToast.vue";
import DefaultConfirmDialog from "../../Components/dialogs/DefaultConfirmDialog.vue";
import SelectInput from "../../Components/inputs/SelectInput.vue";
import UploadInput from "../../Components/inputs/UploadInput.vue";

import { computed, ref, watch } from "vue";
import {
    IconCheck,
    IconLock,
    IconX,
    IconPencilCog,
    IconTrash,
    IconFileImport,
    IconMap2,
} from "@tabler/icons-vue";

const page = usePage();
const searchInput = ref(null);
const timerBounce = ref(null);
const selectedRow = ref(null);
const toolbarRef = ref(null);
const toastRef = ref(null);
const confirmRef = ref(null);
const uploadRef = ref(null);
const progressUpload = ref(0);
const uploadFileForm = useForm({
    files: null,
});

const toggleModal = () => {
    toolbarRef.value.openModal();
};

const clearSearch = () => {
    searchInput.value = null;
};

const handleFiles = (e) => {
    uploadFileForm.files = Array.from(e.files);
};

const clearForm = () => {
    uploadFileForm.files = null;
    uploadFileForm.resetAndClearErrors();
    progressUpload.value = 0;
};

const submitForm = () => {
    uploadRef.value.upload();
    uploadFileForm.post(route("geolocation.store"), {
        forceFormData: true,
        onBefore: () => {
            progressUpload.value = 0;
        },
        onProgress: (e) => {
            progressUpload.value = Math.round(e.loaded / e.total) * 80;
        },
        onSuccess: () => {
            toastRef.value.show(page.props.flash);
            if (page.props.flash?.status == "success") {
                uploadFileForm.resetAndClearErrors();
                uploadRef.value.clear();
            }
            progressUpload.value = 100;
        },
    });
};

const loadPage = (page) => {
    router.get(
        route("location.barangays"),
        {
            page,
            search: searchInput.value,
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

watch(
    () => searchInput.value,
    () => {
        clearTimeout(timerBounce.value);
        timerBounce.value = setTimeout(() => {
            loadPage(1);
        }, 300);
    },
);
</script>
