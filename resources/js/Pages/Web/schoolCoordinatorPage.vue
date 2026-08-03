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
                            <div class="flex gap-1">
                                <h1 class="text-xl font-semibold">
                                    {{ campus?.name }}
                                </h1>
                                <div class="flex justify-between">
                                    <Button
                                        class="rounded-full! w-7 h-7"
                                        text
                                        severity="secondary"
                                    >
                                        <div>
                                            <IconPencilCog :size="18" />
                                        </div>
                                    </Button>
                                    <Popover ref="editOp">
                                        <div class="max-w-72 w-full">
                                            <div
                                                class="flex items-center justify-between"
                                            >
                                                <span class="font-semibold"
                                                    >Create a New
                                                    Workspace</span
                                                >
                                            </div>
                                            <p
                                                class="text-sm text-muted-color mt-2 mb-0!"
                                            >
                                                Name your workspace to get
                                                started. You can always change
                                                this later.
                                            </p>
                                            <InputText
                                                placeholder="Workspace Name"
                                                class="mt-3 w-full"
                                            />
                                            <div
                                                class="flex items-center justify-between mt-4"
                                            >
                                                <span
                                                    class="text-xs text-surface-500 dark:text-surface-400"
                                                    >Lowercase letters and
                                                    dashes only</span
                                                >
                                                <div
                                                    class="flex items-center gap-2"
                                                >
                                                    <Button
                                                        type="button"
                                                        severity="secondary"
                                                        variant="outlined"
                                                        size="small"
                                                        @click="hide"
                                                        >Cancel</Button
                                                    >
                                                    <Button
                                                        type="button"
                                                        size="small"
                                                        @click="hide"
                                                        >Create</Button
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                    </Popover>
                                </div>
                            </div>

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
            <div class="flex-1 flex flex-col lg:flex-row gap-2">
                <div
                    class="flex-1 lg:flex-3 flex flex-col gap-3 rounded-xl p-3"
                >
                    <div class="flex justify-between">
                        <div class="flex items-center gap-1">
                            <IconTextInput
                                :icon="Tablericon.IconSearch"
                                placeholder="Search keywords..."
                                v-model="search.program"
                                class="lg:w-90"
                            />
                            <Button
                                size="small"
                                severity="secondary"
                                class="rounded-full! w-9 h-9"
                                v-if="search.program"
                                @click="search.program = null"
                            >
                                <IconX :size="20" />
                            </Button>
                        </div>
                        <div class="flex items-center gap-2">
                            <Button
                                size="small"
                                class="rounded-lg!"
                                @click="openGradeSystem"
                                severity="secondary"
                            >
                                <div><IconCalendarWeek size="20" /></div>
                            </Button>
                            <Button
                                size="small"
                                class="rounded-lg!"
                                @click="openGradeSystem"
                                severity="secondary"
                            >
                                <div><IconReportAnalytics size="20" /></div>
                            </Button>
                            <Button
                                size="small"
                                label="Create Program"
                                :disabled="loading.openCreateProgram"
                                raised
                                class="rounded-lg!"
                                @click="openCreateProgram"
                            ></Button>
                        </div>
                    </div>
                    <DefaultSelectionTable
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
                        <Column header="Abbreviation">
                            <template #body="{ data }">
                                {{ data.abbreviation }}
                            </template>
                        </Column>
                        <Column>
                            <template #header>
                                <div class="font-semibold text-center w-full">
                                    Year Level
                                </div>
                            </template>
                            <template #body="{ data }">
                                <div class="text-center font-medium w-full">
                                    {{ data.yearLevel }}
                                </div>
                            </template>
                        </Column>
                    </DefaultSelectionTable>
                </div>
                <Card class="flex-2">
                    <template #header>
                        <div class="flex items-center justify-between p-3">
                            <div class="font-semibold">School Information</div>
                            <div class="flex items-center gap-2">
                                <Button
                                    size="small"
                                    severity="secondary"
                                    class="rounded-lg!"
                                    v-if="editSchoolInfo != true"
                                    @click="editSchoolInfo = true"
                                >
                                    <div class="flex items-center gap-1.5">
                                        <IconPencilCog :size="15" />
                                        <div class="text-sm">
                                            Update details
                                        </div>
                                    </div>
                                </Button>
                                <div v-else class="flex items-center gap-2">
                                    <Button
                                        size="small"
                                        class="rounded-lg!"
                                        @click="saveInfo"
                                        :disabled="loading.infoForm"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            <IconCheck
                                                :size="15"
                                                v-if="!loading.infoForm"
                                            />
                                            <IconLoader2
                                                :size="15"
                                                v-else
                                                class="animate-spin"
                                            />
                                            <div class="text-sm">Save</div>
                                        </div>
                                    </Button>
                                    <Button
                                        size="small"
                                        severity="secondary"
                                        class="rounded-lg!"
                                        text
                                        @click="cancelEditSchoolInfo()"
                                    >
                                        <div class="flex items-center gap-1.5">
                                            <IconX :size="15" />
                                            <div class="text-sm">Cancel</div>
                                        </div>
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </template>
                    <template #content>
                        <div class="flex flex-col gap-4">
                            <div class="flex items-center gap-4">
                                <div class="flex flex-1 flex-col">
                                    <div class="flex items-center gap-2">
                                        <Avatar
                                            size="small"
                                            class="bg-blue-100! text-blue-500! rounded-lg! shadow w-10! h-10! shadow-blue-300!"
                                        >
                                            <IconSchool :size="20" />
                                        </Avatar>

                                        <div class="flex flex-1 flex-col">
                                            <div class="font-semibold text-sm">
                                                President
                                            </div>
                                            <div
                                                class="text-gray-600"
                                                v-if="!editSchoolInfo"
                                            >
                                                {{ infoForm.president }}
                                            </div>
                                            <InputText
                                                v-else
                                                type="text"
                                                v-model="infoForm.president"
                                                placeholder="Enter text"
                                                size="small"
                                            />
                                        </div>
                                    </div>
                                </div>
                                <div class="flex flex-col flex-1">
                                    <div class="flex items-center gap-2">
                                        <Avatar
                                            size="small"
                                            class="bg-blue-100! text-blue-500! rounded-lg! shadow w-10! h-10! shadow-blue-300!"
                                        >
                                            <IconCalculator :size="20" />
                                        </Avatar>

                                        <div class="flex flex-1 flex-col">
                                            <div class="font-semibold text-sm">
                                                Registrar
                                            </div>
                                            <div
                                                class="text-gray-600"
                                                v-if="!editSchoolInfo"
                                            >
                                                {{ infoForm.registrar }}
                                            </div>
                                            <InputText
                                                v-else
                                                type="text"
                                                v-model="infoForm.registrar"
                                                placeholder="Enter text"
                                                size="small"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-4">
                                <div class="flex flex-1 flex-col">
                                    <div class="flex items-center gap-2">
                                        <Avatar
                                            size="small"
                                            class="bg-blue-100! text-blue-500! rounded-lg! shadow w-10! h-10! shadow-blue-300!"
                                        >
                                            <IconDeviceMobile :size="20" />
                                        </Avatar>

                                        <div class="flex flex-1 flex-col">
                                            <div class="font-semibold text-sm">
                                                Contact
                                            </div>
                                            <div
                                                class="text-gray-600"
                                                v-if="!editSchoolInfo"
                                            >
                                                {{ infoForm.contact }}
                                            </div>
                                            <InputText
                                                v-else
                                                type="text"
                                                v-model="infoForm.contact"
                                                placeholder="Enter text"
                                                size="small"
                                            />
                                        </div>
                                    </div>
                                </div>

                                <div class="flex flex-1 flex-col">
                                    <div class="flex items-center gap-2">
                                        <Avatar
                                            size="small"
                                            class="bg-blue-100! text-blue-500! rounded-lg! shadow w-10! h-10! shadow-blue-300!"
                                        >
                                            <IconSend :size="20" />
                                        </Avatar>

                                        <div class="flex flex-1 flex-col">
                                            <div class="font-semibold text-sm">
                                                Email
                                                <span
                                                    v-tooltip.top="
                                                        infoForm.errors?.email
                                                    "
                                                    v-if="
                                                        infoForm.errors?.email
                                                    "
                                                    class="text-red-400 font-medium cursor-default"
                                                    >*</span
                                                >
                                            </div>
                                            <div
                                                class="text-gray-600"
                                                v-if="!editSchoolInfo"
                                            >
                                                {{ infoForm.email }}
                                            </div>
                                            <InputText
                                                v-else
                                                type="text"
                                                v-model="infoForm.email"
                                                placeholder="Enter email address"
                                                size="small"
                                            />
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <Divider class="mt-8!" align="right">
                            <span class="font-semibold text-sm"
                                >Activity Logs</span
                            >
                        </Divider>
                        <div class="max-h-130 overflow-y-auto">
                            <Timeline
                                v-if="logs"
                                :value="logs"
                                align="left"
                                class="p-1"
                                :pt="{
                                    eventOpposite: '!hidden',
                                    eventSeparator: '!min-w-[3rem]',
                                }"
                            >
                                <template #marker="slotProps">
                                    <div
                                        class="w-10 h-10 bg-slate-50 border-gray-300 rounded-2xl border flex items-center justify-center shadow-sm"
                                    >
                                        <IconWood
                                            class="text-gray-600"
                                            :size="22"
                                        />
                                    </div>
                                </template>

                                <template #content="slotProps">
                                    <div class="flex flex-col gap-2">
                                        <div class="flex flex-col">
                                            <div
                                                class="text-sm py-1 font-medium"
                                            >
                                                {{ slotProps.item.action }}
                                            </div>
                                            <div
                                                class="text-xs flex gap-4 items-center text-gray-400"
                                            >
                                                <div
                                                    class="flex gap-1 items-center"
                                                >
                                                    <IconCalendar :size="15" />
                                                    <div>
                                                        {{
                                                            slotProps.item.date
                                                        }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div
                                            class="p-2 text-sm rounded-r-xl border-l-amber-600 border-l-4 bg-amber-50"
                                        >
                                            <div
                                                v-for="(value, key) in slotProps
                                                    .item.new_data"
                                                :key="key"
                                                class="flex items-center gap-2"
                                            >
                                                <span
                                                    class="text-gray-700 min-w-36 capitalize"
                                                >
                                                    {{
                                                        key.replaceAll("_", " ")
                                                    }}
                                                </span>

                                                <span class="text-red-500">
                                                    {{
                                                        slotProps.item
                                                            .old_data?.[key] !=
                                                        ""
                                                            ? slotProps.item
                                                                  .old_data?.[
                                                                  key
                                                              ]
                                                            : "Not Set"
                                                    }}
                                                </span>

                                                <IconArrowRight
                                                    :size="14"
                                                    class="text-gray-400"
                                                />

                                                <span
                                                    class="text-emerald-600 font-medium"
                                                >
                                                    {{
                                                        value != ""
                                                            ? value
                                                            : "Removed"
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </Timeline>
                            <div
                                v-else
                                class="flex items-center justify-center p-4"
                            >
                                <div class="text-gray-400 text-sm">
                                    No activity logs found.
                                </div>
                            </div>
                        </div>
                    </template>
                </Card>
            </div>
        </div>
    </AuthLayout>
    <Dialog
        v-model:visible="dialog.createProgram"
        modal
        :style="{ width: '35rem' }"
    >
        <template #header>
            <div class="flex gap-2 items-center">
                <Avatar
                    size="small"
                    class="bg-blue-100! text-blue-500! rounded-lg! shadow w-9! h-9! shadow-blue-300!"
                >
                    <IconBook2 :size="20" />
                </Avatar>
                <div class="flex flex-col">
                    <div class="font-semibold">Create Program</div>
                    <div class="text-xs text-gray-500">
                        Create a new program for the school campus.
                    </div>
                </div>
            </div>
        </template>
        <template #default>
            <div class="flex flex-col gap-3 mt-5 mb-2">
                <SelectInput
                    v-model="courseForm.course"
                    label="Program"
                    :options="programOptions"
                    clearable
                    filter
                >
                </SelectInput>
                <TextInput v-model="courseForm.years" label="Years"></TextInput>
                <div class="flex items-center justify-end gap-2 mt-4">
                    <Button
                        size="small"
                        severity="secondary"
                        text
                        @click="dialog.createProgram = false"
                    >
                        Cancel
                    </Button>
                    <Button
                        size="small"
                        label="Create Program"
                        raised
                        :loading="loading.createProgram"
                        @click="createProgram"
                    ></Button>
                </div>
            </div>
        </template>
    </Dialog>
    <Dialog
        v-model:visible="dialog.gradeSystem"
        modal
        :style="{ width: '35rem' }"
    >
        <template #header>
            <div class="flex gap-2 items-center">
                <Avatar
                    size="small"
                    class="bg-blue-100! text-blue-500! rounded-lg! shadow w-9! h-9! shadow-blue-300!"
                >
                    <IconReportAnalytics :size="20" />
                </Avatar>
                <div class="flex flex-col">
                    <div class="font-semibold">Create Grade System</div>
                    <div class="text-xs text-gray-500">
                        Create a new grade system for the school campus.
                    </div>
                </div>
            </div>
        </template>
        <template #default>
            <div class="flex flex-col gap-3 mt-5 mb-2">
                <div class="flex items-center justify-between">
                    <div>
                        <Button
                            size="small"
                            class="rounded-lg!"
                            @click="toggleOpGrade"
                        >
                            <div
                                class="flex items-center gap-1"
                                v-if="!selectedGrade"
                            >
                                <IconPlus :size="20" />
                                <div class="text-sm">Add Grade</div>
                            </div>
                            <div class="flex items-center gap-1" v-else>
                                <IconEdit :size="20" />
                                <div class="text-sm">Update Grade</div>
                            </div>
                        </Button>
                        <Popover ref="opAddGrades">
                            <div class="max-w-82 w-full">
                                <div class="flex items-center justify-between">
                                    <span class="font-semibold"
                                        >Create a new grade</span
                                    >
                                </div>
                                <p
                                    class="text-xs text-muted-color mt-2 text-justify mb-0!"
                                >
                                    Define a new grade by specifying its
                                    description, grade equivalent, and score
                                    range. The grade will be added to the
                                    grading scale for this campus.
                                </p>
                                <div class="flex flex-col gap-5 mt-5">
                                    <TextInput
                                        v-model="gradeForm.grade"
                                        :error="gradeForm?.errors?.grade"
                                        :error-mark="
                                            gradeForm?.errors?.grade
                                                ? true
                                                : false
                                        "
                                        label="Grade"
                                        placeholder="e.g. A, B, C, etc."
                                    ></TextInput>
                                    <div class="flex gap-5 items-center">
                                        <TextInput
                                            :error="gradeForm?.errors?.lower"
                                            :error-mark="
                                                gradeForm?.errors?.lower
                                                    ? true
                                                    : false
                                            "
                                            v-model="gradeForm.lower"
                                            label="Lower Limit"
                                            placeholder="e.g. 90, 80, etc."
                                        ></TextInput>
                                        <TextInput
                                            v-model="gradeForm.upper"
                                            :error="gradeForm?.errors?.upper"
                                            :error-mark="
                                                gradeForm?.errors?.upper
                                                    ? true
                                                    : false
                                            "
                                            label="Upper Limit"
                                            placeholder="e.g. 100, 89, etc."
                                        ></TextInput>
                                    </div>
                                </div>
                                <div class="flex flex-col">
                                    <Divider type="dashed" />
                                    <div
                                        class="flex justify-between items-center"
                                    >
                                        <div class="text-sm">
                                            Is it a failing grade?
                                        </div>

                                        <DefaultToggle
                                            v-model="gradeForm.fail"
                                            :check-icon="IconCheck"
                                            :un-check-icon="IconX"
                                        />
                                    </div>
                                </div>
                                <div class="flex flex-col">
                                    <Divider type="dashed" />
                                    <div
                                        class="flex justify-between items-center"
                                    >
                                        <div class="text-sm">
                                            Is it an incomplete grade?
                                        </div>

                                        <DefaultToggle
                                            v-model="gradeForm.incomplete"
                                            :check-icon="IconCheck"
                                            :un-check-icon="IconX"
                                        />
                                    </div>
                                </div>
                                <div class="flex flex-col">
                                    <Divider type="dashed" />
                                    <div
                                        class="flex justify-between items-center"
                                    >
                                        <div class="text-sm">
                                            Is it a drop grade?
                                        </div>

                                        <DefaultToggle
                                            v-model="gradeForm.drop"
                                            :check-icon="IconCheck"
                                            :un-check-icon="IconX"
                                        />
                                    </div>
                                </div>
                                <div class="flex items-center justify-end mt-4">
                                    <div class="flex items-center gap-2">
                                        <Button
                                            type="button"
                                            severity="secondary"
                                            variant="outlined"
                                            size="small"
                                            @click="toggleOpGrade"
                                            >Cancel</Button
                                        >
                                        <Button
                                            type="button"
                                            size="small"
                                            @click="createGrade"
                                            :disabled="loading.createGrade"
                                        >
                                            <div
                                                class="flex items-center gap-1"
                                            >
                                                <div>
                                                    <IconDeviceFloppy
                                                        :size="20"
                                                        v-if="
                                                            !loading.createGrade
                                                        "
                                                    />
                                                    <IconLoader2
                                                        :size="20"
                                                        v-else
                                                        class="animate-spin"
                                                    />
                                                </div>

                                                <div>Save Grade</div>
                                            </div>
                                        </Button>
                                    </div>
                                </div>
                            </div>
                        </Popover>
                    </div>

                    <div
                        class="px-4 bg-blue-50 border rounded-md py-2 text-xs font-medium text-blue-500 flex items-center gap-1"
                    >
                        <IconList :size="16" />
                        <div class="flex">
                            {{ grades.length }} Grades Configured
                        </div>
                    </div>
                </div>
                <DataTable
                    v-model:selection="selectedGrade"
                    :value="grades"
                    size="small"
                    class="text-sm border border-b-0 border-t-gray-200 border-x-gray-200"
                    selectionMode="single"
                    dataKey="id"
                >
                    <Column header="Description">
                        <template #body="props">
                            <div class="flex items-center">
                                <div v-if="props.data.is_failed">
                                    <div
                                        class="font-semibold flex items-center gap-1 text-red-600"
                                    >
                                        <IconCircleX
                                            size="20"
                                            stroke-width="2"
                                        />
                                        <div>FAILED</div>
                                    </div>
                                </div>
                                <div v-else-if="props.data.is_incomplete">
                                    <div
                                        class="font-semibold flex items-center gap-1 text-yellow-600"
                                    >
                                        <IconDotsCircleHorizontal
                                            size="20"
                                            stroke-width="2"
                                        />
                                        <div>INCOMPLETE</div>
                                    </div>
                                </div>
                                <div v-else-if="props.data.is_drop">
                                    <div
                                        class="font-semibold flex items-center gap-1 text-red-600"
                                    >
                                        <IconCircleX
                                            size="20"
                                            stroke-width="2"
                                        />
                                        <div>DROPPED</div>
                                    </div>
                                </div>
                                <div v-else>
                                    <div
                                        class="font-semibold flex items-center gap-1 text-green-600"
                                    >
                                        <IconCircleCheck
                                            size="20"
                                            stroke-width="2"
                                        />
                                        <div>PASSED</div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column field="grade">
                        <template #header>
                            <div class="text-center w-full font-semibold">
                                Grade
                            </div>
                        </template>
                        <template #body="{ data }">
                            <div class="text-center w-full">
                                {{ data.grade }}
                            </div>
                        </template>
                    </Column>
                    <Column field="grade">
                        <template #header>
                            <div class="text-center w-full font-semibold">
                                Score Range
                            </div>
                        </template>
                        <template #body="{ data }">
                            <div
                                v-if="data.lower && data.upper"
                                class="text-center"
                            >
                                {{ data.lower }} - {{ data.upper }}
                            </div>
                        </template>
                    </Column>
                    <Column>
                        <template #header>
                            <div class="flex justify-center w-full">
                                <IconSettings :size="20" />
                            </div>
                        </template>
                        <template #body="{ body }">
                            <div class="flex gap-2 justify-center w-full">
                                <Button
                                    size="small"
                                    severity="danger"
                                    class="rounded-full h-8! w-8! p-0!"
                                    text
                                >
                                    <IconTrash :size="18" />
                                </Button>
                            </div>
                        </template>
                    </Column>
                </DataTable>
            </div>
        </template>
    </Dialog>
</template>
<script setup>
import DefaultSelectionTable from "../../Components/tables/DefaultSelectionTable.vue";

import IconTextInput from "../../Components/inputs/IconTextInput.vue";
import AuthLayout from "../../Layouts/AuthLayout.vue";
import TextInput from "../../Components/inputs/TextInput.vue";
import SelectInput from "../../Components/inputs/SelectInput.vue";
import * as Tablericon from "@tabler/icons-vue";
import { Head, router, usePage, useForm } from "@inertiajs/vue3";
import {
    IconMapPin,
    IconSchool,
    IconTrash,
    IconX,
    IconCalculator,
    IconReportAnalytics,
    IconSettings,
    IconPencilCog,
    IconSend,
    IconBook2,
    IconLoader2,
    IconCheck,
    IconWood,
    IconDotsCircleHorizontal,
    IconArrowRight,
    IconDeviceMobile,
    IconList,
    IconCalendar,
    IconCircleX,
    IconCircleCheck,
    IconEdit,
    IconPlus,
    IconCalendarWeek,
    IconDeviceFloppy,
} from "@tabler/icons-vue";
import { onMounted, ref, watch } from "vue";
import { useToast } from "primevue/usetoast";
import DefaultToggle from "../../Components/toggleswitches/DefaultToggle.vue";

const toast = useToast();

const props = defineProps({
    programs: Object,
    campus: Object,
    info: Object,
    errors: Object,
    flash: Object,
    logs: Object,
    programOptions: Object,
    grades: Object,
});
const dialog = ref({
    createProgram: false,
    gradeSystem: false,
});
const opAddGrades = ref(null);
const page = usePage();
const timerBounce = ref(null);
const editOp = ref(null);
const editSchoolInfo = ref(false);
const infoForm = useForm({
    president: props.info?.president ?? "",
    registrar: props.info?.registrar ?? "",
    contact: props.info?.contact ?? "",
    email: props.info?.email ?? "",
});
const courseForm = useForm({
    course: null,
    years: null,
});
const search = ref({
    program: null,
});

const selectedGrade = ref();

const toggleOpGrade = (event) => {
    opAddGrades.value.toggle(event);
    if (!selectedGrade) {
        gradeForm.resetAndClearErrors();
    }
};

const gradeForm = useForm({
    id: null,
    grade: null,
    lower: null,
    upper: null,
    fail: false,
    incomplete: false,
    drop: false,
});
const loading = ref({
    programTable: false,
    infoForm: false,
    openCreateProgram: false,
    createProgram: false,
    createGrade: false,
});

const loadPage = (page) => {
    router.get(
        route("schoolCoordinator"),
        {
            page,
            ...(search.value.program && {
                search: search.value.program,
            }),
        },
        {
            preserveState: true,
            preserveScroll: true,
        },
    );
};

const saveInfo = () => {
    loading.value.infoForm = true;
    infoForm.put(route("schoolCoordinator.updateInfo"), {
        onSuccess: () => {
            editSchoolInfo.value = false;
            toast.add({
                severity: props.flash?.status,
                summary: props.flash?.title,
                detail: props.flash?.message,
                life: 3000,
            });
        },
        onFinish: () => {
            loading.value.infoForm = false;
        },
        onError: (errors) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Failed to update school information.",
                life: 3000,
            });
        },
    });
};

const selectPrograms = (data) => {
    console.log(data);
};

const openGradeSystem = () => {
    router.reload({
        only: ["grades"],
        preserveState: true,
        preserveScroll: true,
        onFinish: () => {
            dialog.value.gradeSystem = true;
        },
    });
};

const openCreateProgram = () => {
    openCreateProgram.value = true;
    loading.value.openCreateProgram = true;
    router.reload({
        only: ["programOptions"],
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            dialog.value.createProgram = true;
        },
        onFinish: () => {
            loading.value.openCreateProgram = false;
        },
    });
};

const createProgram = () => {
    loading.value.createProgram = true;
    courseForm.post(route("schoolCoordinator.createProgram"), {
        onSuccess: () => {
            dialog.value.createProgram = false;
            toast.add({
                severity: props.flash?.status,
                summary: props.flash?.title,
                detail: props.flash?.message,
                life: 3000,
            });
            courseForm.resetAndClearErrors();
        },
        onError: (errors) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Failed to create program.",
                life: 3000,
            });
        },
        onFinish: () => {
            loading.value.createProgram = false;
        },
    });
};

const createGrade = () => {
    loading.value.createGrade = true;
    gradeForm.post(route("schoolCoordinator.createGrade"), {
        only: ["grades"],
        preserveState: true,
        preserveScroll: true,
        onSuccess: () => {
            dialog.value.createGrade = false;
            toast.add({
                severity: props.flash?.status,
                summary: props.flash?.title,
                detail: props.flash?.message,
                life: 3000,
            });

            gradeForm.resetAndClearErrors();
        },
        onError: (errors) => {
            toast.add({
                severity: "error",
                summary: "Error",
                detail: "Failed to create program.",
                life: 3000,
            });
        },
        onFinish: () => {
            loading.value.createGrade = false;
        },
    });
};

const cancelEditSchoolInfo = () => {
    editSchoolInfo.value = false;
    infoForm.president = props.info?.president ?? "";
    infoForm.registrar = props.info?.registrar ?? "";
    infoForm.contact = props.info?.contact ?? "";
    infoForm.email = props.info?.email ?? "";

    infoForm.clearErrors();
};

watch(
    () => search.value.program,
    () => {
        clearTimeout(timerBounce.value);
        timerBounce.value = setTimeout(() => {
            loadPage(1);
        }, 300);
    },
);

watch(
    () => gradeForm.drop,
    (val) => {
        if (val) {
            gradeForm.fail = false;
            gradeForm.incomplete = false;
        }
    },
);

watch(
    () => gradeForm.fail,
    (val) => {
        if (val) {
            gradeForm.drop = false;
            gradeForm.incomplete = false;
        }
    },
);

watch(
    () => gradeForm.incomplete,
    (val) => {
        if (val) {
            gradeForm.drop = false;
            gradeForm.fail = false;
        }
    },
);

watch(
    () => selectedGrade.value,
    (val) => {
        if (val) {
            Object.assign(gradeForm, {
                id: val.id,
                grade: val.grade,
                lower: val.lower,
                upper: val.upper,
                fail: val.is_failed ? true : false,
                incomplete: val.is_incomplete ? true : false,
                drop: val.is_drop ? true : false,
            });
            console.log(gradeForm);
        } else {
            gradeForm.resetAndClearErrors();
        }
    },
);
</script>
