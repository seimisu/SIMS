<template>
    <Drawer
        v-model:visible="modelValue"
        position="full"
        :pt="{
            header: 'border-b-1 border-gray-300 border-dashed',
            content: '!p-3',
        }"
    >
        <template #header>
            <div
                class="bg-green-50 border text-green-600 px-5 py-1.5 shadow rounded-lg flex items-center gap-2"
            >
                <IconFileSpreadsheet :size="20" />
                <div class="text-sm uppercase font-medium">
                    {{ page.props?.details?.name ?? 'Stipend Batch' }}
                </div>
            </div>
        </template>
        <template #default>
            <div class="flex flex-col lg:flex-row w-full h-full gap-3">
                <div
                    class="flex-1 flex flex-col lg:flex-1/3 gap-3 border border-slate-200 rounded-2xl"
                >
                    <div class="">
                        <Stepper
                            v-model:value="stepper"
                            @update:value="changeStep"
                            class="basis-[50rem]"
                            :pt="{ root: { class: 'text-xs' } }"
                        >
                            <StepList>
                                <Step :value="1">Scholars</Step>
                                <Step :value="2">Stipend/ WithHelds</Step>
                                <Step :value="3">Allowances</Step>
                            </StepList>
                        </Stepper>
                    </div>
                    <div
                        class="flex-1 flex flex-col gap-5 p-3 overflow-auto"
                        v-if="stepper == 1"
                    >
                        <Message severity="info">
                            <template #icon>
                                <div class="flex items-start">
                                    <IconInfoCircleFilled :size="25" />
                                </div>
                            </template>
                            <span class="ml-1 text-xs font-normal">
                                <b>Step 1:</b> Provide the scholar's basic
                                information such as SPAS ID, account number,
                                name, scholarship program, and university.
                                Ensure the information is accurate before
                                continuing to the stipend details.
                            </span>
                        </Message>
                        <div class="flex flex-col gap-3">
                            <div
                                class="flex flex-col lg:flex-row gap-2 items-end"
                            >
                                <AutoCompleteInput
                                    label="Spas ID"
                                    placeholder="e.g U-2024-01-23456"
                                    v-model="scholarForm.spas_no"
                                    :tooltip="v1$.spas_no.$errors[0]?.$message"
                                    :errorMark="v1$.spas_no.$error"
                                />
                                <TextInput
                                    label="Account No"
                                    placeholder="e.g 1827123456"
                                    :errorMark="v1$.account_no.$error"
                                    :tooltip="
                                        v1$.account_no.$errors[0]?.$message
                                    "
                                    v-model="scholarForm.account_no"
                                />
                            </div>
                            <div
                                class="flex flex-col lg:flex-row gap-2 items-end"
                            >
                                <TextInput
                                    label="First Name"
                                    capitalize
                                    v-model="scholarForm.fname"
                                    :errorMark="v1$.fname.$error"
                                    :tooltip="v1$.fname.$errors[0]?.$message"
                                />
                                <TextInput
                                    label="Middle Name"
                                    capitalize
                                    v-model="scholarForm.mname"
                                />
                            </div>
                            <div
                                class="flex flex-col lg:flex-row gap-2 items-end"
                            >
                                <TextInput
                                    label="Last Name"
                                    capitalize
                                    v-model="scholarForm.lname"
                                    :errorMark="v1$.lname.$error"
                                    :tooltip="v1$.lname.$errors[0]?.$message"
                                />
                                <TextInput
                                    label="Suffix"
                                    placeholder="Optional"
                                    v-model="scholarForm.suffix"
                                />
                            </div>
                            <div
                                class="flex flex-col lg:flex-row gap-2 items-end"
                            >
                                <TextInput
                                    label="Email"
                                    v-model="scholarForm.email"
                                    :errorMark="v1$.email.$error"
                                    :tooltip="v1$.email.$errors[0]?.$message"
                                />
                                <DatePickerInput
                                    v-model="scholarForm.birthday"
                                    label="Birthday"
                                    :errorMark="v1$.birthday.$error"
                                    :tooltip="v1$.birthday.$errors[0]?.$message"
                                />
                            </div>
                            <SelectInput
                                label="Program"
                                :options="ProgramOption"
                                v-model="scholarForm.program"
                                :errorMark="v1$.program.$error"
                                :tooltip="v1$.program.$errors[0]?.$message"
                            />
                            <TextInput
                                label="University"
                                capitalize
                                placeholder="Type the complete name of the university"
                                v-model="scholarForm.university"
                                :errorMark="v1$.university.$error"
                                :tooltip="v1$.university.$errors[0]?.$message"
                            />
                            <SelectInput
                                label="Scholarship Status"
                                :options="statusOption"
                                v-model="scholarForm.scholarship_status"
                                :errorMark="v1$.scholarship_status.$error"
                                :tooltip="
                                    v1$.scholarship_status.$errors[0]?.$message
                                "
                            />
                            <TextInput
                                label="Period Covered"
                                placeholder="e.g 2ND TERM AY 2024-2025"
                                v-model="scholarForm.period"
                                :errorMark="v1$.period.$error"
                                :tooltip="v1$.period.$errors[0]?.$message"
                            />
                            <div class="flex justify-end">
                                <DefaultButton
                                    size="small"
                                    label="Add to Collection"
                                    @click="addToCollection"
                                ></DefaultButton>
                            </div>
                        </div>
                    </div>
                </div>
                <div
                    class="flex-1 lg:flex-9/10 bg-slate-50 shadow p-3 rounded-2xl"
                >
                    <div class="flex flex-col w-full h-full gap-3">
                        <div class="flex justify-end items-center gap-3">
                            <DefaultButton
                                size="small"
                                severity="secondary"
                                outlined
                                label="Download as Excel"
                            />
                            <DefaultButton size="small" label="Submit" />
                        </div>
                        <div class="flex-1 flex flex-col overflow-auto">
                            <table
                                class="min-w-full border border-gray-200 divide-y divide-gray-200"
                            >
                                <thead class="text-sm">
                                    <tr>
                                        <th
                                            class="px-4 py-2 text-left font-semibold text-gray-700"
                                        >
                                            SPAS No
                                        </th>
                                        <th
                                            class="px-4 py-2 text-left font-semibold text-gray-700"
                                        >
                                            Account No
                                        </th>
                                        <th
                                            class="px-4 py-2 text-left font-semibold text-gray-700"
                                        >
                                            Full Name
                                        </th>
                                        <th
                                            class="px-4 py-2 text-left font-semibold text-gray-700"
                                        >
                                            Program
                                        </th>
                                        <th
                                            class="px-4 py-2 text-left font-semibold text-gray-700"
                                        >
                                            University
                                        </th>
                                        <th
                                            class="px-4 py-2 text-left font-semibold text-gray-700"
                                        >
                                            Period Cover
                                        </th>
                                        <th
                                            class="px-4 py-2 text-left font-semibold text-gray-700"
                                        >
                                            Status
                                        </th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200 text-sm">
                                    <tr
                                        v-for="(item, index) in dataCollection"
                                        :key="index"
                                    >
                                        <td class="px-4 py-2 text-nowrap">
                                            {{ item.spas_no }}
                                        </td>
                                        <td class="px-4 py-2">
                                            {{ item.account_no }}
                                        </td>
                                        <td class="px-4 py-2 uppercase">
                                            {{
                                                item.fname +
                                                " " +
                                                item.mname +
                                                " " +
                                                item.lname +
                                                " " +
                                                (item.suffix ?? "")
                                            }}
                                        </td>
                                        <td class="px-4 py-2">
                                            {{ item.program.name }}
                                        </td>
                                        <td class="px-4 py-2 capitalize">
                                            {{ item.university }}
                                        </td>
                                        <td class="px-4 py-2 capitalize">
                                            {{ item.period }}
                                        </td>
                                        <td
                                            :class="[
                                                item.scholarship_status.text,
                                                'px-4 py-2 capitalize',
                                            ]"
                                        >
                                            <div
                                                :class="[
                                                    item.scholarship_status.bg,
                                                    'w-fit px-4 border py-0.5 rounded-2xl',
                                                ]"
                                            >
                                                {{
                                                    item.scholarship_status.name
                                                }}
                                            </div>
                                        </td>
                                        <td>
                                            <div
                                                class="flex p-2 items-center justify-end w-full"
                                            >
                                                <DefaultButton
                                                    size="small"
                                                ></DefaultButton>
                                            </div>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </Drawer>
</template>
<script setup>
import { IconFileSpreadsheet, IconInfoCircleFilled } from "@tabler/icons-vue";

import { ref, computed, watch } from "vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import TextInput from "../../Components/inputs/TextInput.vue";
import SelectInput from "../../Components/inputs/SelectInput.vue";

import AutoCompleteInput from "../../Components/inputs/AutoCompleteInput.vue";
import DatePickerInput from "../../Components/inputs/DatePickerInput.vue";
import { router, useRemember } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useForm, usePage } from "@inertiajs/vue3";
import useVuelidate from "@vuelidate/core";
import {
    required,
    email,
    helpers,
    numeric,
    minLength,
} from "@vuelidate/validators";
const modelValue = defineModel("modelValue");
const editBtn = ref(false);
const stepper = ref(1);
const page = usePage();
const ProgramOption = [
    { id: 1, name: "RA 7687" },
    { id: 2, name: "MERIT" },
    { id: 3, name: "RA 10612" },
];

const statusOption = [
    {
        id: 1,
        name: "Good Standing",
        text: "text-green-700",
        bg: "bg-green-100",
    },
    {
        id: 2,
        name: "Continue Under Probation",
        text: "text-yellow-700",
        bg: "bg-yellow-100",
    },
    {
        id: 3,
        name: "Continue with Partial Allowance",
        text: "text-blue-700",
        bg: "bg-blue-100",
    },
    {
        id: 4,
        name: "Leave of Absence",
        text: "text-purple-700",
        bg: "bg-purple-100",
    },
    {
        id: 5,
        name: "No Report",
        text: "text-red-700",
        bg: "bg-red-100",
    },
];

const dataCollection = useRemember([], "scholarCollection");
const scholarForm = useForm({
    spas_no: null,
    account_no: null,
    fname: null,
    mname: null,
    lname: null,
    suffix: null,
    program: null,
    university: null,
    scholarship_status: null,
    email: null,
    birthday: null,
    period: null,
});

const scholarRules = computed(() => ({
    spas_no: {
        required,
        format: helpers.withMessage(
            "Format must be U-2024-01-23456",
            helpers.regex(/^U-\d{4}-\d{2}-\d{5}$/),
        ),

        unique: helpers.withMessage(
            "SPAS number already exists in the collection",
            (value) => {
                if (!value) return true;

                return !dataCollection.value.some(
                    (item) => item.spas_no === value,
                );
            },
        ),
    },
    account_no: {
        required,
        numeric,
        minLength: helpers.withMessage(
            "Account number must be at least 9 digits",
            minLength(10),
        ),
        unique: helpers.withMessage(
            "Account number already exists in the collection",
            (value) => {
                if (!value) return true;

                return !dataCollection.value.some(
                    (item) => item.account_no === value,
                );
            },
        ),
    },
    fname: { required },
    lname: { required },
    program: { required },
    university: { required },
    scholarship_status: { required },
    email: { required, email },
    birthday: { required },
    period: { required },
}));

const v1$ = useVuelidate(scholarRules, scholarForm);

const addToCollection = async () => {
    const valid = await v1$.value.$validate();
    if (!valid) return;

    dataCollection.value.push({ ...scholarForm.data() });

    scholarForm.reset();
    v1$.value.$reset();
};

const changeStep = (e) => {
    console.log(e);
};

watch(
    dataCollection,
    (newVal) => {
        newVal.sort((a, b) => {
            if (!a.program || !b.program) return 0;
            return a.program.name.localeCompare(b.program.name);
        });
    },
    { deep: true },
);
</script>
