<template>
    <Dialog
        v-model:visible="modelValue"
        modal
        :pt="{
            header: 'border-b-1 border-gray-300 border-dashed',
            root: 'w-[99%] lg:w-[90rem]',
            content: '!p-0',
        }"
    >
        <template #header>
            <div
                class="bg-slate-100 px-4 py-2 shadow rounded-lg flex items-center gap-2"
            >
                <IconUserUp :size="18" :stroke-width="2" />
                <div class="uppercase font-medium text-sm">
                    Scholar Subjects & Grades Request
                </div>
            </div>
        </template>
        <template #default>
            <div class="p-3 w-full"></div>
        </template>
    </Dialog>
</template>
<script setup>
import {
    IconExclamationCircle,
    IconExclamationCircleFilled,
    IconUserUp,
    IconHistory,
    IconUser,
    IconCalendarTime,
    IconArrowRight,
    IconLock,
    IconArrowBigRightLines,
    IconDatabase,
    IconDatabaseEdit,
} from "@tabler/icons-vue";
import UploadInput from "../../Components/inputs/UploadInput.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import { ref, watch, onMounted } from "vue";
import { useForm, progress, usePage, router } from "@inertiajs/vue3";
import { useToast } from "primevue";
import { route } from "ziggy-js";

const modelValue = defineModel("modelValue");
const page = usePage();
const toast = useToast();
const selectedRow = ref(null);
const loading = ref({
    approve: false,
    reject: false,
});
const personalRequest = ref(null);

const selectedRequest = (item, index) => {
    selectedRow.value = item;
    selectedRow.value.index = index;
};

const approveRequest = (decision) => {
    router.post(
        route("profile.request", { type: decision }),
        {
            data: selectedRow.value,
        },
        {
            onBefore: () => {
                if (decision === "reject") {
                    loading.value.reject = true;
                } else {
                    loading.value.approve = true;
                }
            },
            onSuccess: () => {
                toast.add({
                    severity: page.props.flash?.status || "success",
                    summary: page.props.flash?.title || "Success",
                    detail:
                        page.props.flash?.message ||
                        "Request has been processed successfully.",
                    life: 3000,
                });

                if (page.props.flash?.status === "success") {
                    personalRequest.value[selectedRow.value.index].status =
                        decision === "accept" ? "approved" : "rejected";
                    selectedRow.value.reviewed_at = "Just now";
                    selectedRow.value.reviewed_by =
                        page.props.user.profile.fullname;
                }
            },

            onFinish: () => {
                if (decision === "reject") {
                    loading.value.reject = false;
                } else {
                    loading.value.approve = false;
                }
            },
        },
    );
};

onMounted(() => {
    personalRequest.value = page.props?.personalRequest;
});
</script>
