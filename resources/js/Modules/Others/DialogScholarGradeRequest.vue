<template>
    <Dialog
        v-model:visible="modelValue"
        modal
        :pt="{
            header: 'border-b-1 border-gray-300 border-dashed',
            root: 'w-[99%] lg:w-[110rem]',
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
            <div class="p-3 w-full flex gap-3">
                <div class="flex-6 flex flex-col gap-3">
                    <div class="flex flex-col gap-5" v-if="details">
                        <template v-for="(item, index) in details" :key="index">
                            <Divider>
                                <span class="text-sm font-semibold"
                                    >{{ item.academicYear }} /
                                    {{ item.term }}</span
                                >
                            </Divider>
                            <div>
                                <div class="flex-1 flex justify-between">
                                    <div>
                                        <div class="text-xs text-slate-500">
                                            School/Course
                                        </div>
                                        <div class="text-sm">
                                            <p class="">
                                                {{ item.school }}
                                            </p>
                                            <p class="">
                                                {{ item.course }}
                                            </p>
                                        </div>
                                    </div>
                                    <div class="">
                                        <div class="text-xs text-slate-500">
                                            document
                                        </div>
                                        <p class="font-medium">
                                            {{ details.academicYear }}
                                        </p>
                                    </div>
                                </div>
                                <table class="min-w-full !border-none text-sm">
                                    <thead>
                                        <tr class="bg-gray-100">
                                            <th
                                                class="px-3 py-2 text-left rounded-l-xl"
                                            >
                                                Subject Name
                                            </th>
                                            <th class="px-3 py-2 text-left">
                                                Subject Code
                                            </th>
                                            <th class="px-3 py-2 text-right">
                                                Unit
                                            </th>
                                            <th class="px-3 py-2 text-right">
                                                Grades
                                            </th>
                                            <th class="px-3 py-2 text-right">
                                                Total
                                            </th>
                                            <th
                                                class="px-3 py-2 text-center rounded-r-xl"
                                            >
                                                Remarks
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr
                                            v-for="(
                                                subject, key
                                            ) in item.subjects"
                                            :key="key"
                                            class="hover:bg-gray-50"
                                        >
                                            <td
                                                class="px-3 py-2 uppercase max-w-70 align-text-top"
                                            >
                                                {{ subject.subject }}
                                            </td>
                                            <td
                                                class="px-3 py-2 uppercase max-w-70 align-text-top"
                                            >
                                                {{ subject.code }}
                                            </td>
                                            <td
                                                class="px-3 py-2 text-right align-text-top"
                                            >
                                                {{ subject.unit }}
                                            </td>
                                            <td
                                                class="px-3 py-2 text-right max-w-35 align-text-top"
                                            >
                                                <p v-if="subject.grade?.grade">
                                                    {{ subject.grade?.grade }}
                                                </p>
                                                <p
                                                    class="text-xs text-gray-400"
                                                    v-else
                                                >
                                                    No Grade yet
                                                </p>
                                            </td>
                                            <td
                                                class="px-3 py-2 text-right align-text-top"
                                            >
                                                {{ subject.total ?? "-" }}
                                            </td>
                                            <td class="px-3 py-2 text-center">
                                                <div
                                                    v-if="subject?.is_drop"
                                                    class="text-red-600"
                                                >
                                                    Dropped
                                                </div>
                                                <div
                                                    v-else-if="
                                                        subject?.is_failed
                                                    "
                                                    class="text-rose-600"
                                                >
                                                    Failed
                                                </div>
                                                <div
                                                    v-else-if="
                                                        subject?.is_incomplete
                                                    "
                                                    class="text-amber-600"
                                                >
                                                    Incompleted
                                                </div>
                                                <div
                                                    v-else-if="subject?.grade"
                                                    class="text-green-600"
                                                >
                                                    Passed
                                                </div>
                                                <div
                                                    v-else-if="
                                                        subject.grade?.is_drop
                                                    "
                                                    class="text-red-600"
                                                >
                                                    Dropped
                                                </div>
                                                <div
                                                    v-else-if="
                                                        subject.grade?.is_failed
                                                    "
                                                    class="text-rose-600"
                                                >
                                                    Failed
                                                </div>
                                                <div
                                                    v-else-if="
                                                        subject.grade
                                                            ?.is_incomplete
                                                    "
                                                    class="text-amber-600"
                                                >
                                                    Incompleted
                                                </div>
                                                <div
                                                    v-else-if="
                                                        subject.grade?.is_active
                                                    "
                                                    class="text-green-600"
                                                >
                                                    Passed
                                                </div>
                                            </td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </template>
                    </div>
                </div>
                <div class="flex-5">dasda</div>
            </div>
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
const details = ref(null);

onMounted(() => (details.value = page.props?.subjectRequest));
</script>
