<template>
    <Drawer v-model:visible="modelValue" position="full" :pt="{
        header: 'border-b-1 border-gray-300 border-dashed',
        content: 'bg-slate-50',
        footer: 'border-t-1 border-gray-300 border-dashed',
    }">
        <template #header>
            <div class="bg-slate-100 px-4 py-2 shadow rounded-lg flex items-center gap-2">
                <IconId :size="20" :stroke-width="2" />
                <div class="uppercase font-medium">Scholar Validation</div>
            </div>
        </template>
        <template #default>
            <div class="mt-5 flex flex-col gap-3">
                <div
                    class="flex items-start p-3 shadow border border-green-300 text-green-500 rounded-xl bg-green-50 gap-1">
                    <div>
                        <IconExclamationCircleFilled :size="20" />
                    </div>

                    <p class="text-xs leading-5 text-justify">
                        Please ensure that the address, school, course, and
                        email provided are valid and correctly formatted before
                        submission. Invalid or incomplete information may result
                        in validation errors or rejection.
                    </p>
                </div>
                <template v-for="(item, index) in scholar" :key="index">
                    <Panel :pt="{
                        root: [
                            '!rounded-xl',
                            item.verified_by
                                ? '!border-green-500 !shadow-green-500 '
                                : null,
                        ],
                    }">
                        <template #header>
                            <div class="flex items-center gap-2">
                                <Avatar class="!bg-blue-100 !text-blue-600 border">
                                    <IconUser :size="18" />
                                </Avatar>
                                <div class="text-sm">
                                    <div :class="[
                                        item.sex == 'M'
                                            ? 'text-blue-500'
                                            : 'text-red-500',
                                    ]">
                                        # {{ item.spas_no }}
                                    </div>
                                    <div class="font-semibold">
                                        {{ item.fullname }}
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template #icons>
                            <div class="flex items-start gap-3 text-xs">
                                <div class="bg-red-100 px-3 py-1 rounded-2xl shadow">
                                    • {{ item.status }}
                                </div>

                                <div class="bg-slate-100 px-3 py-1 rounded-2xl shadow">
                                    • {{ item.standing }}
                                </div>
                            </div>
                        </template>
                        <template #default>
                            <div class="flex flex-col lg:flex-row gap-3 w-full">
                                <div class="flex flex-col flex-1">
                                    <div class="flex-1 flex flex-col items-center justify-center">
                                        <div class="text-sm text-gray-500">
                                            Imported address data:
                                        </div>
                                        <div class="font-medium">
                                            {{
                                                page.props?.selected[index]
                                                    .address
                                            }}
                                            {{
                                                page.props?.selected[index]
                                                    .barangay
                                            }}
                                            {{
                                                page.props?.selected[index]
                                                    .municipality
                                            }}
                                            {{
                                                page.props?.selected[index]
                                                    .province
                                            }}
                                            <span v-if="
                                                page.props?.selected[index]
                                                    .region
                                            ">
                                                (Region
                                                {{
                                                    page.props?.selected[index]
                                                        .region
                                                }})
                                            </span>
                                        </div>
                                    </div>
                                    <div class="flex-1 flex flex-col items-center justify-center">
                                        <div class="text-sm text-gray-500">
                                            Imported School data:
                                        </div>
                                        <div class="font-medium">
                                            {{
                                                page.props?.selected[index]
                                                    .school
                                            }}
                                        </div>
                                        <div class="font-medium">
                                            {{
                                                page.props?.selected[index]
                                                    .course
                                            }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-1 flex-col gap-3">
                                    <div class="flex flex-col gap-2">
                                        <Divider align="right" class="!m-0">
                                            <span class="text-xs font-medium">Residential Information
                                            </span>
                                        </Divider>
                                        <TextInput label="Street/Village" v-model="scholar[index].address" disabled />
                                        <AutoCompleteInput v-model="scholar[index].inputAddress
                                            " :options="page.props?.resultSearch"
                                            placeholder="Find by Barangay, Municipality, Province, or Region"
                                            @complete="autoSearch" selection></AutoCompleteInput>
                                    </div>
                                    <div class="flex flex-col gap-2">
                                        <Divider align="right" class="!m-0">
                                            <span class="text-xs font-medium">Educational Background
                                                Information
                                            </span>
                                        </Divider>
                                        <SelectInput label="School" filter v-model="scholar[index].inputSchool"
                                            :options="page.props?.schoolOption || []
                                                " :error-mark="item?.error2" @update:model-value="
                                                    renderCourse(item, index)
                                                    " />
                                        <SelectInput label="Course" filter :error-mark="item?.error3" :disable="!scholar[index].inputSchool
                                            " v-model="scholar[index].inputCourse" :options="scholar[index].courseOption ||
                                                []
                                                " />
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template #footer>
                            <div class="flex justify-between">
                                <div class="flex gap-1 items-center">
                                    <Avatar class="!bg-green-100 !text-green-600 border" shape="circle"
                                        v-if="item.verified_by">
                                        <IconUser :size="18" />
                                    </Avatar>
                                    <div class="flex flex-col text-xs" v-if="item.verified_by">
                                        <div>Verified by:</div>
                                        <div class="font-medium">
                                            {{ item.verified_by }} •
                                            {{ item.verified_at }}
                                        </div>
                                    </div>
                                </div>
                                <div class="">
                                    <DefaultButton size="small" raised :disabled="item.loading || !!item.verified_by
                                        " @click="saveValidate(item, index)" :loading="item.loading"
                                        label="Save and Review Submission" class="!rounded-xl !px-10" />
                                </div>
                            </div>
                        </template>
                    </Panel>
                </template>
            </div>
        </template>
        <template #footer>
            <div class="w-full flex gap-4 justify-between">
                <div class="min-w-[200px]">
                    <!-- Progress -->
                    <div class="flex items-center justify-between mb-1">
                        <span class="text-xs text-gray-500 font-medium">
                            Validation Progress
                        </span>

                        <span class="text-xs font-semibold">
                            {{ page.props?.validationStatus.completed }} /
                            {{ page.props?.validationStatus.total }}
                        </span>
                    </div>

                    <!-- Progress Bar -->
                    <div class="w-full bg-gray-100 rounded-full h-2 overflow-hidden">
                        <div class="h-full rounded-full transition-all duration-300" :class="{
                            'bg-green-500':
                                page.props?.validationStatus.completed ===
                                page.props?.validationStatus.total,
                            'bg-yellow-500':
                                page.props?.validationStatus.completed >
                                0 &&
                                page.props?.validationStatus.completed <
                                page.props?.validationStatus.total,
                            'bg-red-500':
                                page.props?.validationStatus.completed ===
                                0,
                        }" :style="{
                            width: `${page.props?.validationStatus.total
                                ? (page.props?.validationStatus.completed /
                                    page.props?.validationStatus.total) *
                                100
                                : 0
                                }%`,
                        }" />
                    </div>
                </div>
                <DefaultButton size="small" raised label="Move to Production" class="!rounded-xl !px-10"
                    @click="moveProd" />
            </div>
        </template>
    </Drawer>
    <ConfirmDialog group="templating" class="w-100">
        <template #message="slotProps">
            <div class="flex flex-col items-center w-full gap-4 text-center">
                <i :class="slotProps.message.icon" class="!text-6xl text-red-500"></i>
                <p class="text-wrap text-sm">{{ slotProps.message.message }}</p>
                <div class="flex items-center gap-2 px-3 py-2 rounded-xl border border-emerald-200 bg-emerald-50 w-fit">
                    <div class="flex flex-col">
                        <span class="text-xs text-emerald-700 font-medium uppercase tracking-wide">
                            Validation Progress
                        </span>

                        <div class="flex items-center gap-1">
                            <span class="text-lg font-bold text-emerald-600">
                                {{ page.props?.validationStatus.completed }}
                            </span>

                            <span class="text-sm text-gray-500">
                                out of
                            </span>

                            <span class="text-lg font-bold text-gray-600">
                                {{ page.props?.validationStatus.total }}
                            </span>

                            <span class="text-sm text-gray-500">
                                completed
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </ConfirmDialog>
</template>
<script setup>
import { router, usePage } from "@inertiajs/vue3";
import {
    IconArrowNarrowRight,
    IconExclamationCircleFilled,
    IconId,
    IconUser,
} from "@tabler/icons-vue";
import { computed, onMounted, ref } from "vue";
import TextInput from "../../Components/inputs/TextInput.vue";
import AutoCompleteInput from "../../Components/inputs/AutoCompleteInput.vue";
import SelectInput from "../../Components/inputs/SelectInput.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import { route } from "ziggy-js";
import { useConfirm } from "primevue/useconfirm";
import { useToast } from "primevue/usetoast";

const toast = useToast();
const confirm = useConfirm();
const props = defineProps({
    id: String
})
const modelValue = defineModel("modelValue");
const page = usePage();
const loading = ref({
    address: false,
    save: false,
});
const scholar = computed(() => page.props?.selected);

const renderCourse = (item, index) => {
    router.reload({
        only: ["courseOption"],
        data: { campus: item.inputSchool?.name },
        preserveState: true,
        preserveScroll: true,
        showProgress: true,
        replace: true,
        onFinish: () => {
            scholar.value[index].courseOption = page.props?.courseOption || [];
        },
    });
};

const autoSearch = (event) => {
    loading.value.address = true;

    router.reload({
        data: {
            findAddress: event,
        },
        preserveState: true,
        preserveScroll: true,
        only: ["resultSearch"],
        onFinish: () => {
            loading.value.address = false;
        },
    });
};

const saveValidate = (item, index) => {
    scholar.value[index].loading = true;
    router.post(route("review.validate", { id: item.id }), item, {
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => { },
        onError: (e) => {
            console.log(e);

            if (e.inputAddress) {
                scholar.value[index].error1 = e.inputAddress;
            }
            if (e.inputSchool) {
                scholar.value[index].error2 = e.inputSchool;
            }
            if (e.inputCourse) {
                scholar.value[index].error3 = e.inputCourse;
            }
        },
        onFinish: () => {
            scholar.value[index].loading = false;
        },
    });
};

const moveProd = () => {
    confirm.require({
        group: 'templating',
        message: 'Are you sure you want to publish all completed records?',
        header: 'Publish Completed Records',
        icon: 'pi pi-exclamation-triangle',
        rejectProps: {
            label: 'Cancel',
            severity: 'secondary',
            outlined: true
        },
        acceptProps: {
            label: 'Proceed'
        },
        accept: () => {
            router.post(route('review.publish', { id: props.id }), {}, {
                onSuccess: () => {
                    toast.add({
                        severity: page.props?.flash?.status,
                        summary: page.props?.flash?.title,
                        detail: page.props?.flash?.message,
                        life: 3000
                    });
                }
            });
        },
        reject: () => {
            toast.add({
                severity: 'warn',
                summary: 'Cancelled',
                detail: 'Validation was cancelled.',
                life: 3000
            });
        }
    });
}

</script>
