<template>
    <DefaultDrawer v-model:visible="drawer" size="!w-[50rem]">
        <template #header>
            <div class="flex items-center gap-2 !sticky !top-0">
                <div class="">
                    <Avatar
                        :label="
                            page.props?.schoolDetail.generated_name
                                .charAt(0)
                                .toUpperCase()
                        "
                        style="background-color: #dee9fc; color: #1a2551"
                        shape="circle"
                        class="!w-[50px] !h-[50px] font-extrabold !text-2xl"
                    />
                </div>

                <div class="flex flex-col">
                    <div class="font-bold flex items-center gap-1 capitalize">
                        <div>
                            <span>{{
                                page.props?.schoolDetail.generated_name
                            }}</span>
                        </div>

                        <DefaultButton
                            v-if="canManageSchools"
                            rounded
                            text
                            size="small"
                            :icon-size="15"
                            severity="danger"
                            :icon="IconTrash"
                            @click="
                                deleteRow({
                                    id: page.props?.schoolDetail.id,
                                    type: 'campus',
                                })
                            "
                        ></DefaultButton>
                    </div>
                    <div class="text-xs flex items-center gap-1 text-gray-500">
                        <IconMapPin size="18" />
                        <div>
                            {{
                                page.props?.schoolDetail.address.full_address
                                    .name
                            }}
                        </div>
                    </div>
                </div>
            </div>
        </template>
        <template #body>
            <div class="flex flex-col h-full">
                <div class="flex items-center justify-between px-5 py-1">
                    <div>
                        <DefaultMessages
                            v-show="detailsForm.hasErrors"
                            :message="detailsForm.errors"
                            message-type="error"
                        ></DefaultMessages>
                    </div>

                    <div class="flex items-center" v-if="canManageSchools">
                        <DefaultButton
                            size="small"
                            label="Update"
                            :icon="IconSettingsFilled"
                            :icon-size="18"
                            raised
                            v-if="!updateSchool"
                            @click="openUpdateSchool()"
                            class-name="w-30  !rounded-xl"
                        />
                        <div class="flex items-center gap-2" v-else>
                            <DefaultButton
                                size="small"
                                :icon="IconX"
                                :icon-size="18"
                                raised
                                @click="
                                    ((updateSchool = false),
                                    detailsForm.clearErrors())
                                "
                                severity="danger"
                                class-name="w-30 !rounded-xl"
                            />
                            <DefaultButton
                                size="small"
                                :icon="IconCheck"
                                :loading="detailsForm.processing"
                                label="Update"
                                @click="UpdateDetailsForm"
                                :icon-size="18"
                                raised
                                class-name="w-30  !rounded-xl"
                            />
                        </div>
                    </div>
                </div>
                <div class="flex flex-col gap-5 px-5 py-2">
                    <div class="flex justify-between gap-2">
                        <div class="w-[50%] flex items-center gap-2">
                            <div class="bg-slate-200 p-2 shadow rounded-xl">
                                <IconUserStar size="20" />
                            </div>
                            <div
                                class="flex flex-col flex-1"
                                v-if="!updateSchool"
                            >
                                <div
                                    class="text-xs font-semibold flex items-center"
                                >
                                    <div>PRESIDENT:</div>
                                </div>
                                <div class="text-sm font-light capitalize">
                                    {{
                                        page.props?.schoolDetail?.info?.dean ??
                                        "Not yet provided"
                                    }}
                                </div>
                            </div>
                            <TextInput
                                v-else
                                v-model="detailsForm.dean"
                                capitalize
                                placeholder="President's name"
                            ></TextInput>
                        </div>
                        <div class="w-[50%] flex items-center gap-2">
                            <div class="bg-slate-200 p-2 shadow rounded-xl">
                                <IconUserQuestion size="20" />
                            </div>
                            <div
                                class="flex flex-col flex-1"
                                v-if="!updateSchool"
                            >
                                <div
                                    class="text-xs font-semibold flex items-center"
                                >
                                    <div>REGISTRAR:</div>
                                </div>
                                <div
                                    class="text-sm font-light capitalize"
                                    v-if="
                                        page.props?.schoolDetail?.info
                                            ?.registrar
                                    "
                                >
                                    {{
                                        page.props?.schoolDetail.info.registrar
                                    }}
                                </div>
                                <div v-else class="text-sm font-light">
                                    Not yet provided
                                </div>
                            </div>
                            <TextInput
                                v-else
                                v-model="detailsForm.registrar"
                                capitalize
                                placeholder="Registrar's name"
                            >
                            </TextInput>
                        </div>
                    </div>
                    <div class="flex justify-between gap-2">
                        <div class="w-[50%] flex items-center gap-2">
                            <div class="bg-slate-200 p-2 shadow rounded-xl">
                                <IconPhone size="20" />
                            </div>
                            <div
                                class="flex flex-col flex-1"
                                v-if="!updateSchool"
                            >
                                <div
                                    class="text-xs font-semibold flex items-center"
                                >
                                    <div>CONTACT NO:</div>
                                </div>
                                <div class="text-sm font-light">
                                    {{
                                        page.props?.schoolDetail?.info
                                            ?.contact ?? "Not yet provided"
                                    }}
                                </div>
                            </div>
                            <TextInput
                                v-else
                                v-model="detailsForm.contact"
                                placeholder="School Contact No."
                            >
                            </TextInput>
                        </div>
                        <div class="w-[50%] flex items-center gap-2">
                            <div class="bg-slate-200 p-2 shadow rounded-xl">
                                <IconAt size="20" />
                            </div>
                            <div
                                class="flex flex-col flex-1"
                                v-if="!updateSchool"
                            >
                                <div
                                    class="text-xs font-semibold flex items-center"
                                >
                                    <div>EMAIL:</div>
                                </div>
                                <div class="text-sm font-light">
                                    {{
                                        page.props?.schoolDetail?.info?.email ??
                                        "Not yet provided"
                                    }}
                                </div>
                            </div>
                            <TextInput
                                v-else
                                v-model="detailsForm.email"
                                placeholder="School Email"
                            ></TextInput>
                        </div>
                    </div>
                </div>
                <Divider align="left" type="dashed" class="!m-0">
                    <span class="text-xs font-semibold"
                        >Curriculum Management</span
                    >
                </Divider>
                <div class="px-5 py-2 gap-2 flex flex-col">
                    <Message
                        severity="info"
                        icon="pi pi-info-circle"
                        v-if="
                            page.props?.schoolDetail.grading_array.name ==
                            'Percent Grading'
                        "
                    >
                        <p class="text-xs">
                            The grading system is disabled for this school
                            because it uses percentage-based grading.
                        </p>
                    </Message>

                    <ToolbarModule
                        v-model="searchInput"
                        @deleteSearch="clearSearch"
                        @saveForm="submitForm('courses')"
                        button-label="Create"
                        :dialog-title="
                            !courseForm.id ? 'Create Programs' : 'Edit Programs'
                        "
                        :dialog-description="
                            !courseForm.id
                                ? 'Provide the necessary information to add a new course to the system.'
                                : 'Modify the existing course information and save your changes.'
                        "
                        :dialog-button-loading="courseForm.processing"
                        :dialog-icon="IconBook2"
                        dialog-button-label="Save"
                        :message-has-errors="courseForm.hasErrors"
                        :message-errors="courseForm.errors"
                        @buttonOpenModal="
                            toggleModal({ type: 'create', class: 'course' })
                        "
                        message-type="error"
                        :button-visible="canManageSchools"
                        ref="toolbarCourseRef"
                    >
                        <template #add1>
                            <DefaultButton
                                v-if="canManageSchools"
                                :icon="IconReport"
                                outlined
                                :disabled="
                                    page.props?.schoolDetail?.grading_array
                                        .name != 'Percent Grading'
                                        ? false
                                        : true
                                "
                                @click="gradeSystemDialog = true"
                                tooltip="Grade System"
                                size="small"
                                severity="secondary"
                                class-name="!w-10 !rounded-xl"
                            />
                        </template>
                        <template #add2>
                            <DefaultButton
                                v-if="canManageSchools"
                                :icon="IconCalendarWeek"
                                outlined
                                @click="semesterDialog = true"
                                size="small"
                                severity="secondary"
                                tooltip="Semester"
                                class-name="!w-10 !rounded-xl"
                            />
                        </template>
                        <template #form>
                            <div class="flex flex-col gap-3 mt-5 mb-2">
                                <SelectInput
                                    v-model="courseForm.course"
                                    label="Program"
                                    :options="courseOption"
                                    clearable
                                    filter
                                >
                                </SelectInput>
                                <TextInput
                                    v-model="courseForm.years"
                                    label="Years"
                                ></TextInput>
                            </div>
                        </template>
                    </ToolbarModule>
                    <DefaultScrollTable
                        :items="page.props?.schoolDetail.courses"
                    >
                        <Column header="Programs" field="course.name">
                            <template #body="props">
                                {{ props.data.course.name }}
                            </template>
                        </Column>
                        <Column field="course.abbreviation">
                            <template #header>
                                <div
                                    class="flex justify-center w-full font-semibold"
                                >
                                    Abbreviation
                                </div>
                            </template>
                            <template #body="props">
                                <div class="flex w-full justify-center">
                                    {{ props.data.course.abbreviation }}
                                </div>
                            </template>
                        </Column>
                        <Column field="course.years">
                            <template #header>
                                <div
                                    class="flex justify-center w-full font-semibold"
                                >
                                    Years
                                </div>
                            </template>
                            <template #body="props">
                                <div
                                    class="flex w-full justify-center font-extrabold"
                                >
                                    {{ props.data.years }}
                                </div>
                            </template>
                        </Column>

                        <Column>
                            <template #body="slotProps">
                                <div class="flex justify-end">
                                    <Button
                                        text
                                        v-tooltip.top="'Options'"
                                        rounded
                                        size="small"
                                        severity="secondary"
                                        icon="pi pi-ellipsis-v"
                                        @click="
                                            (e) =>
                                                toggleCourseOption(
                                                    e,
                                                    slotProps.data,
                                                )
                                        "
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
                    </DefaultScrollTable>
                </div>
            </div>
        </template>
    </DefaultDrawer>

    <DefaultDialog
        v-model:visible="gradeSystemDialog"
        hide-footer
        :icon="IconBook2"
        width-set="lg:!w-[35%]"
        title="Grading System"
        description="Shows how grades are computed, including score ranges, equivalents, and passing requirements."
    >
        <template #forms>
            <div class="pt-5 gap-1 flex flex-col">
                <ToolbarModule
                    v-model="searchInput"
                    hideSearch
                    @deleteSearch="clearSearch"
                    @saveForm="submitForm('grades')"
                    button-label="Create"
                    :dialog-title="
                        !gradeForm.id ? 'Create Grade' : 'Edit Grade'
                    "
                    dialog-description="Create a new grade entry or modify an existing one. You can define the grade name, set its corresponding value, and configure how it is applied within the school's grading system."
                    :dialog-button-loading="gradeForm.processing"
                    :dialog-icon="IconPencilCog"
                    dialog-button-label="Save"
                    :message-has-errors="gradeForm.hasErrors"
                    :message-errors="gradeForm.errors"
                    @buttonOpenModal="
                        toggleModal({ type: 'create', class: 'grades' })
                    "
                    message-type="error"
                    :button-visible="canManageSchools"
                    ref="toolbarGradeRef"
                >
                    <template #form>
                        <div class="flex flex-col gap-5 mt-5">
                            <TextInput
                                v-model="gradeForm.grade"
                                label="Grade"
                                placeholder="e.g. A, B, C, etc."
                            ></TextInput>
                            <div class="flex gap-5 items-center">
                                <TextInput
                                    v-model="gradeForm.lower"
                                    label="Lower Limit"
                                    placeholder="e.g. 90, 80, etc."
                                ></TextInput>
                                <TextInput
                                    v-model="gradeForm.upper"
                                    label="Upper Limit"
                                    placeholder="e.g. 100, 89, etc."
                                ></TextInput>
                            </div>
                        </div>
                        <div class="flex flex-col">
                            <Divider type="dashed" />
                            <div class="flex justify-between items-center">
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
                            <div class="flex justify-between items-center">
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
                            <div class="flex justify-between items-center">
                                <div class="text-sm">Is it a drop grade?</div>

                                <DefaultToggle
                                    v-model="gradeForm.drop"
                                    :check-icon="IconCheck"
                                    :un-check-icon="IconX"
                                />
                            </div>
                        </div>
                    </template>
                </ToolbarModule>
                <DefaultScrollTable :items="page.props.schoolDetail?.grades">
                    <Column header="Description">
                        <template #body="props">
                            <div class="flex items-center">
                                <div v-if="props.data.is_failed">
                                    <div
                                        class="font-semibold flex items-center gap-1 text-red-600 px-4 rounded-xl"
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
                                        class="font-semibold flex items-center gap-1 text-yellow-600 px-4 rounded-xl"
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
                                        class="font-semibold flex items-center gap-1 text-red-600 px-4 rounded-xl"
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
                                        class="font-semibold flex items-center gap-1 text-green-600 px-4 rounded-xl"
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
                    <Column class="text-center">
                        <template #header>
                            <div class="flex w-full justify-center">
                                <div class="text-xs font-bold">Grade</div>
                            </div>
                        </template>
                        <template #body="props">
                            <div class="flex justify-center font-semibold">
                                <div>{{ props.data.grade }}</div>
                            </div>
                        </template>
                    </Column>
                    <Column>
                        <template #header>
                            <div class="flex w-full justify-center">
                                <div class="text-xs font-bold">
                                    Grading Range
                                </div>
                            </div>
                        </template>
                        <template #body="props">
                            <div class="flex items-center justify-center">
                                <div
                                    v-if="
                                        props.data.lower != null ||
                                        props.data.upper != null
                                    "
                                >
                                    <span>{{ props.data.lower }}</span>
                                    -
                                    <span>{{ props.data.upper }}</span>
                                </div>
                                <div v-else>
                                    <div class="text-xs italic text-gray-400">
                                        No set
                                    </div>
                                </div>
                            </div>
                        </template>
                    </Column>
                    <Column>
                        <template #body="slotProps">
                            <div class="flex justify-end">
                                <Button
                                    v-if="canManageSchools"
                                    text
                                    v-tooltip.top="'Options'"
                                    rounded
                                    size="small"
                                    severity="secondary"
                                    icon="pi pi-ellipsis-v"
                                    @click="
                                        (e) =>
                                            toggleGradeOption(e, slotProps.data)
                                    "
                                />
                                <Menu
                                    ref="menuGrade"
                                    :model="gradeMenuItems"
                                    :popup="true"
                                >
                                    <template #item="{ item, props }">
                                        <a
                                            v-ripple
                                            class="flex items-center"
                                            v-bind="props.action"
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
                </DefaultScrollTable>
            </div>
        </template>
    </DefaultDialog>

    <DefaultDialog
        v-model:visible="semesterDialog"
        :icon="IconBook2"
        width-set="lg:!w-[35%]"
        :loading="semesterForm.processing"
        @submit-form="submitForm('semesters')"
        message-type="error"
        title="Semester Management"
        description="Manage the academic semesters offered by the institution, including start and end dates."
    >
        <template #forms>
            <div class="pt-5 px-3 gap-5 flex flex-col">
                <div
                    v-for="(semester, index) in page.props?.semesterOption"
                    :key="index"
                >
                    <div
                        :class="[
                            'text-xs flex items-center rounded-2xl font-semibold px-2 gap-1 py-1 ',
                            randomColor(index),
                        ]"
                    >
                        <IconGridDots size="15" />
                        <div>
                            {{ semesterForm.semester[index]?.name }}
                        </div>
                    </div>

                    <div class="flex items-center gap-3 my-3">
                        <DatePickerInput
                            label="Start Date"
                            v-model="semesterForm.semester[index].startDate"
                            view="month"
                            format-date="M yy"
                        >
                        </DatePickerInput>

                        <DatePickerInput
                            label="End Date"
                            v-model="semesterForm.semester[index].endDate"
                            view="month"
                            format-date="M yy"
                        >
                        </DatePickerInput>
                        <DatePickerInput
                            label="Submission Date"
                            v-model="
                                semesterForm.semester[index].submissionDate
                            "
                        >
                        </DatePickerInput>
                    </div>
                </div>
            </div>
        </template>
        <template #message>
            <DefaultMessages
                v-if="semesterForm.hasErrors"
                message-type="error"
                :message="semesterForm.errors"
            />
        </template>
    </DefaultDialog>
    <DefaultDialog
        v-model:visible="subjectDialog"
        :icon="IconBook2"
        :loading="curriculumForm.processing"
        width-set="lg:!w-[70%]"
        :submit-form="submitCurriculum"
        :title="selectedRow?.course?.name"
        @submit-form="submitCurriculum"
        :hide-footer="!canManageSchools"
        absolute-div
        description="View all subjects offered under this course, including their codes, units, and classifications."
    >
        <template #message>
            <DefaultMessages
                v-if="curriculumForm.hasErrors"
                message-type="error"
                :message="curriculumForm.errors"
            />
        </template>
        <template #forms>
            <div class="mt-5 dark:bg-gray-900 dark:text-gray-100">
                <Tabs :value="0" class="school-curriculum-tabs">
                    <TabList class="dark:!bg-gray-900">
                        <Tab
                            v-for="(curItem, curKey) in curriculumForm.multi"
                            :key="curKey"
                            class="text-sm !font-bold !p-1 !text-center"
                            :value="curKey"
                        >
                            <span>
                                <div
                                    class="flex items-end"
                                    v-if="!curItem.edit"
                                >
                                    <div class="flex items-start">
                                        <Button
                                            v-if="canManageSchools"
                                            severity="danger"
                                            variant="link"
                                            size="small"
                                            @click="
                                                deleteCurriculumAndSubject({
                                                    button: 'curriculum',
                                                    type: !curItem.id,
                                                    curriculum: curKey,
                                                })
                                            "
                                            class="!p-0"
                                        >
                                            <template #icon>
                                                <IconTrash
                                                    class="!text-red-600"
                                                    size="15"
                                                ></IconTrash>
                                            </template>
                                        </Button>
                                    </div>
                                    <div>
                                        Curriculum {{ curItem.yearLevel }}
                                    </div>
                                    <div class="flex items-start">
                                        <Button
                                            v-if="canManageSchools"
                                            severity="secondary"
                                            variant="link"
                                            size="small"
                                            @click="curItem.edit = true"
                                            class="!p-0"
                                        >
                                            <template #icon>
                                                <IconPencil
                                                    size="15"
                                                ></IconPencil>
                                            </template>
                                        </Button>
                                    </div>
                                </div>
                                <div
                                    v-else
                                    class="inline-flex items-center gap-2 font-normal"
                                >
                                    <TextInput
                                        placeholder="Select year"
                                        class="!w-25"
                                        v-model="curItem.yearLevel"
                                    >
                                    </TextInput>

                                    <DefaultButton
                                        size="small"
                                        rounded
                                        class-name="!w-8 !h-8"
                                        :icon="IconX"
                                        @click="curItem.edit = false"
                                        severity="danger"
                                        text
                                    ></DefaultButton>
                                </div>
                            </span>
                        </Tab>
                        <div class="flex items-end">
                            <DefaultButton
                                v-if="canManageSchools"
                                size="small"
                                rounded
                                class-name=" !rounded-xl "
                                :icon-size="20"
                                :icon="IconCirclePlusFilled"
                                @click="addCurriculum"
                                text
                            ></DefaultButton>
                        </div>
                    </TabList>
                    <TabPanels class="!p-0 flex flex-col gap-3 mt-3 dark:!bg-gray-900">
                        <TabPanel
                            v-for="(curItem, curKey) in curriculumForm.multi"
                            :key="curKey"
                            :value="curKey"
                            class="flex flex-col w-full gap-4"
                        >
                            <div class="flex items-center justify-between">
                                <DefaultButton
                                    label="Make this template"
                                    size="small"
                                    v-if="canManageSchools && curItem.id"
                                    :disabled="
                                        curItem.has_replication ||
                                        curItem.is_duplicated
                                    "
                                    :icon="
                                        curItem.has_replication ||
                                        curItem.is_duplicated
                                            ? IconCheck
                                            : IconDots
                                    "
                                    :icon-size="18"
                                    @click="copyTemplate(curKey)"
                                    :severity="
                                        curItem.has_replication ||
                                        curItem.is_duplicated
                                            ? 'primary'
                                            : 'secondary'
                                    "
                                    class="!px-4 !text-xs"
                                />
                                <div v-else class="flex items-center gap-2">
                                    <SelectInput
                                        v-model="templateForm.select"
                                        :options="page.props?.templateOptions"
                                        clearable
                                    />
                                    <DefaultButton
                                        v-if="canManageSchools"
                                        size="small"
                                        raised
                                        :disabled="loading.paste"
                                        :loading="loading.paste"
                                        label="Apply Template"
                                        class="text-nowrap w-60"
                                        @click="pasteTemplate(curKey)"
                                    ></DefaultButton>
                                </div>
                            </div>

                            <div
                                v-for="year in parseInt(selectedRow.years)"
                                :key="year"
                            >
                                <Panel
                                    toggleable
                                    :collapsed="year != 1 ? true : false"
                                    :pt="{
                                        root: 'dark:!border-gray-700 dark:!bg-gray-800',
                                        header: 'dark:!border-gray-700 dark:!bg-gray-800 dark:!text-gray-100',
                                        content: 'dark:!border-gray-700 dark:!bg-gray-800 dark:!text-gray-100',
                                    }"
                                >
                                    <template #header>
                                        <div
                                            class="flex w-full items-center justify-between"
                                        >
                                            <div class="text-sm font-semibold">
                                                {{ convertNumberToWord(year) }}
                                                Year
                                            </div>
                                            <div
                                                class="flex items-center gap-5 px-10"
                                            >
                                                <div
                                                    class="flex items-center gap-2"
                                                >
                                                    <IconBooks></IconBooks>

                                                    <div
                                                        class="text-xs font-bold"
                                                    >
                                                        {{
                                                            totalYearSubject({
                                                                curriculumKey:
                                                                    curKey,
                                                                year: year,
                                                            })
                                                        }}
                                                        <span
                                                            class="font-medium"
                                                            >Total
                                                            Subjects</span
                                                        >
                                                    </div>
                                                </div>

                                                <div
                                                    class="flex items-center gap-2"
                                                >
                                                    <IconNotebook></IconNotebook>
                                                    <div
                                                        class="text-xs font-bold"
                                                    >
                                                        {{
                                                            totalYearUnit({
                                                                curriculumKey:
                                                                    curKey,
                                                                year: year,
                                                            })
                                                        }}
                                                        <span
                                                            class="font-medium"
                                                            >Total units</span
                                                        >
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <template #default>
                                        <div
                                            v-for="(sem, semKey) in page.props
                                                .semesterOption"
                                            :key="sem.id"
                                            :value="semKey"
                                            class="py-2"
                                        >
                                            <Panel
                                                toggleable
                                                :collapsed="
                                                    semKey != 0 ? true : false
                                                "
                                                :pt="{
                                                    root: ['!border-0 '],
                                                    header: [
                                                        randomColor(semKey),
                                                        '!rounded-t-lg',
                                                    ],
                                                    content: [
                                                        '!border-x-1 !rounded-bl-lg !rounded-br-lg !border-b-1 !border-gray-200 dark:!border-gray-700 dark:!bg-gray-900 dark:!text-gray-100',
                                                    ],
                                                }"
                                            >
                                                <template #header>
                                                    <div
                                                        class="flex w-full items-center justify-between gap-2"
                                                    >
                                                        <div
                                                            class="flex gap-3 items-center"
                                                        >
                                                            <IconGridDots
                                                                size="15"
                                                            />
                                                            <div
                                                                :class="[
                                                                    'font-semibold text-sm',
                                                                ]"
                                                            >
                                                                {{ sem.name }}
                                                            </div>
                                                        </div>
                                                        <div
                                                            class="flex items-center gap-5 px-10"
                                                        >
                                                            <div
                                                                class="flex items-center gap-2"
                                                            >
                                                                <IconBooks></IconBooks>
                                                                <div
                                                                    class="text-xs font-bold"
                                                                >
                                                                    {{
                                                                        countSubject(
                                                                            {
                                                                                curriculumKey:
                                                                                    curKey,
                                                                                semesterId:
                                                                                    sem.id,
                                                                                semesterKey:
                                                                                    semKey,
                                                                                year: year,
                                                                            },
                                                                        )
                                                                    }}
                                                                    <span
                                                                        class="font-medium"
                                                                        >Subjects</span
                                                                    >
                                                                </div>
                                                            </div>

                                                            <div
                                                                class="flex items-center gap-2"
                                                            >
                                                                <IconNotebook></IconNotebook>
                                                                <div
                                                                    class="text-xs font-bold"
                                                                >
                                                                    {{
                                                                        countUnit(
                                                                            {
                                                                                curriculumKey:
                                                                                    curKey,
                                                                                semesterId:
                                                                                    sem.id,
                                                                                semesterKey:
                                                                                    semKey,
                                                                                year: year,
                                                                            },
                                                                        )
                                                                    }}
                                                                    <span
                                                                        class="font-medium"
                                                                        >Total
                                                                        units</span
                                                                    >
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="flex items-center gap-2"
                                                            >
                                                                <IconUserSquareRounded></IconUserSquareRounded>
                                                                <div
                                                                    class="text-xs font-medium"
                                                                >
                                                                    {{
                                                                        recentUpdateSubject(
                                                                            {
                                                                                curriculumKey:
                                                                                    curKey,
                                                                                semesterId:
                                                                                    sem.id,
                                                                                semesterKey:
                                                                                    semKey,
                                                                                year: year,
                                                                            },
                                                                        )
                                                                    }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                                <template #default>
                                                    <div
                                                        class="w-full h-fit pt-5"
                                                    >
                                                        <div
                                                            class="flex items-center gap-2 pb-5"
                                                        >
                                                            <div
                                                                class="bg-slate-100 text-slate-500 p-1 rounded-lg border-1 border-slate-500 shadow-slate-500 dark:border-gray-600 dark:bg-gray-800 dark:text-gray-300"
                                                            >
                                                                <IconBooks
                                                                    stroke-width="2"
                                                                ></IconBooks>
                                                            </div>
                                                            <div
                                                                class="flex-1 flex flex-col"
                                                            >
                                                                <div
                                                                    class="font-bold text-sm"
                                                                >
                                                                    Subjects for
                                                                    this
                                                                    semester
                                                                </div>
                                                                <div
                                                                    class="text-xs text-gray-400 font-light dark:text-gray-400"
                                                                >
                                                                    This section
                                                                    displays all
                                                                    subjects
                                                                    assigned for
                                                                    the selected
                                                                    year and
                                                                    semester.
                                                                </div>
                                                            </div>
                                                        </div>

                                                        <div
                                                            class="w-full"
                                                            v-for="(
                                                                item, index
                                                            ) in curriculumForm
                                                                .multi[curKey]
                                                                .subjects"
                                                            :key="index"
                                                        >
                                                            <div
                                                                class="flex gap-2 items-center py-2"
                                                                v-if="
                                                                    item
                                                                        .semester_array
                                                                        .name ==
                                                                        sem.name &&
                                                                    parseInt(
                                                                        item.year,
                                                                    ) == year
                                                                "
                                                            >
                                                                <div
                                                                    class="w-[20%]"
                                                                >
                                                                    <SelectInput
                                                                        v-model="
                                                                            item.class_array
                                                                        "
                                                                        :disable="
                                                                            item.is_lock
                                                                        "
                                                                        label="Subject Class"
                                                                        :options="
                                                                            page
                                                                                .props
                                                                                ?.subClassOption
                                                                        "
                                                                    ></SelectInput>
                                                                </div>
                                                                <div
                                                                    class="w-[40%]"
                                                                >
                                                                    <TextInput
                                                                        v-model="
                                                                            item.name
                                                                        "
                                                                        :disabled="
                                                                            item.is_lock
                                                                        "
                                                                        capitalize
                                                                        label="Description"
                                                                    >
                                                                    </TextInput>
                                                                </div>
                                                                <div
                                                                    class="w-[20%]"
                                                                >
                                                                    <TextInput
                                                                        v-model="
                                                                            item.subjectCode
                                                                        "
                                                                        :disabled="
                                                                            item.is_lock
                                                                        "
                                                                        label="Subject Code"
                                                                    ></TextInput>
                                                                </div>
                                                                <div
                                                                    class="w-[10%]"
                                                                >
                                                                    <TextInput
                                                                        v-model="
                                                                            item.unit
                                                                        "
                                                                        :disabled="
                                                                            item.is_lock
                                                                        "
                                                                        label="Unit"
                                                                    ></TextInput>
                                                                </div>
                                                                <div
                                                                    class="w-[10%] pt-5 gap-2 justify-start flex items-end h-full"
                                                                >
                                                                    <DefaultButton
                                                                        v-if="canManageSchools"
                                                                        size="small"
                                                                        rounded
                                                                        text
                                                                        v-show="
                                                                            item.is_lock !=
                                                                            null
                                                                        "
                                                                        severity="secondary"
                                                                        @click="
                                                                            curriculumForm.multi[
                                                                                curKey
                                                                            ].subjects[
                                                                                index
                                                                            ].is_lock =
                                                                                !item.is_lock
                                                                        "
                                                                        :icon="
                                                                            curriculumForm
                                                                                .multi[
                                                                                curKey
                                                                            ]
                                                                                .subjects[
                                                                                index
                                                                            ]
                                                                                .is_lock
                                                                                ? IconLock
                                                                                : IconLockOpen
                                                                        "
                                                                        tooltip="Unlock to edit"
                                                                    />

                                                                    <DefaultButton
                                                                        v-if="canManageSchools"
                                                                        size="small"
                                                                        rounded
                                                                        text
                                                                        @click="
                                                                            deleteCurriculumAndSubject(
                                                                                {
                                                                                    type: !item.id,
                                                                                    curriculum:
                                                                                        curKey,
                                                                                    subject:
                                                                                        index,
                                                                                    subId: item.id,
                                                                                    button: 'subject',
                                                                                },
                                                                            )
                                                                        "
                                                                        :tooltip="
                                                                            !item.id
                                                                                ? 'Remove'
                                                                                : 'Delete'
                                                                        "
                                                                        severity="danger"
                                                                        :icon="
                                                                            !item.id
                                                                                ? IconCircleMinus
                                                                                : IconTrash
                                                                        "
                                                                    />
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <Divider
                                                            type="dashed"
                                                        ></Divider>
                                                        <DefaultButton
                                                            v-if="canManageSchools"
                                                            size="small"
                                                            :icon="IconPlus"
                                                            @click="
                                                                addSubject(
                                                                    curKey,
                                                                    year,
                                                                    sem,
                                                                )
                                                            "
                                                            class-name="w-full !rounded-xl"
                                                            label="Add Subjects"
                                                        >
                                                        </DefaultButton>
                                                    </div>
                                                </template>
                                            </Panel>
                                        </div>
                                    </template>
                                </Panel>
                            </div>
                        </TabPanel>
                    </TabPanels>
                </Tabs>
            </div>
        </template>
    </DefaultDialog>

    <DefaultToast ref="toastRef" />
</template>
<script setup>
import {
    IconCirclePlusFilled,
    IconSettingsFilled,
    IconTrashX,
    IconUserCog,
    IconMapPin,
    IconUserStar,
    IconUserQuestion,
    IconPhone,
    IconAt,
    IconBook2,
    IconCircleXFilled,
    IconCheck,
    IconX,
    IconPencilCog,
    IconTrash,
    IconBooks,
    IconSettings,
    IconCircleX,
    IconDotsCircleHorizontal,
    IconCircleCheck,
    IconTablePlus,
    IconClipboardList,
    IconReport,
    IconArrowDownSquare,
    IconCalendarWeek,
    IconGridDots,
    IconPlus,
    IconEdit,
    IconPencil,
    IconZoomQuestion,
    IconLock,
    IconLockOpen,
    IconCircleMinus,
    IconTrashFilled,
    IconNotebook,
    IconUser,
    IconUserSquareRounded,
    IconInfoSmall,
    IconInfoCircleFilled,
    IconDots,
    IconDotsVertical,
} from "@tabler/icons-vue";
import CurriculmDialog from "../../Components/dialogs/CurriculmDialog.vue";
import DefaultScrollTable from "../../Components/tables/DefaultScrollTable.vue";
import DefaultDrawer from "../../Components/dialogs/DefaultDrawer.vue";
import DefaultDialog from "../../Components/dialogs/DefaultDialog.vue";
import TextInput from "../../Components/inputs/TextInput.vue";
import DatePickerInput from "../../Components/inputs/DatePickerInput.vue";
import SelectInput from "../../Components/inputs/SelectInput.vue";
import DefaultToast from "../../Components/messages/DefaultToast.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import DefaultToggle from "../../Components/toggleswitches/DefaultToggle.vue";
import DefaultMessages from "../../Components/messages/DefaultMessages.vue";
import ToolbarModule from "./ToolbarModule.vue";
import { usePermissions } from "../../Composables/usePermissions";
import { router, useForm, usePage } from "@inertiajs/vue3";
import { computed, reactive, ref, watch } from "vue";
import { route } from "ziggy-js";

const page = usePage();
const drawer = ref(false);
const subjectDialog = ref(false);
const updateSchool = ref(false);
const gradeSystemDialog = ref(false);
const semesterDialog = ref(false);
const loading = reactive({
    paste: false,
});
const searchInput = ref(null);
const toastRef = ref(null);
const typeDialog = ref(null);
const toolbarCourseRef = ref(null);
const toolbarGradeRef = ref(null);
const selectedRow = ref(null);
const menu = ref(null);
const menuGrade = ref(null);
const hideRemoveButton = ref("create");
const { canAny } = usePermissions();
const canManageSchools = computed(() =>
    canAny([
        "schools.create",
        "schools.update",
        "schools.delete",
        "schools.curriculum.copy",
        "schools.curriculum.paste",
    ]),
);

const props = defineProps({
    id: [Number, String],
    value: [Array, Object],
    courseOption: [Array, Object],
    subClassOption: [Array, Object],
    confirmRef: Object,
});

const convertNumberToWord = (number) => {
    const words = [
        "First",
        "Second",
        "Third",
        "Fourth",
        "Fifth",
        "Sixth",
        "Seventh",
        "Eighth",
        "Ninth",
        "Tenth",
    ];
    return words[number - 1] || number;
};

const randomColor = (index) => {
    const colors = [
        "!text-sky-600 !bg-sky-50",
        "!text-indigo-600 !bg-indigo-50",
        "!text-rose-600 !bg-rose-50",
        "!text-yellow-600 !bg-yellow-50",
        "!text-purple-600 !bg-purple-50",
        "!text-pink-600 !bg-pink-50",
        "!text-indigo-600 !bg-indigo-50",
        "!text-teal-600 !bg-teal-50",
    ];
    return colors[index % colors.length];
};

const countSubject = (res) => {
    const count = curriculumForm.multi[res.curriculumKey].subjects.filter(
        (s) =>
            s.semester_id == res.semesterId &&
            parseInt(s.year) == parseInt(res.year),
    ).length;
    return count;
};

const totalYearSubject = (res) => {
    const count = curriculumForm.multi[res.curriculumKey].subjects.filter(
        (s) => s.year == res.year,
    ).length;

    return count;
};

const totalYearUnit = (res) => {
    const count = curriculumForm.multi[res.curriculumKey].subjects
        .filter((s) => s.year == res.year)
        .reduce((sum, s) => sum + parseInt(s.unit), 0);

    return count;
};

const countUnit = (res) => {
    const count = curriculumForm.multi[res.curriculumKey].subjects
        .filter(
            (s) =>
                s.semester_id == res.semesterId &&
                parseInt(s.year) == parseInt(res.year),
        )
        .reduce((sum, s) => sum + parseInt(s.unit), 0);
    return count;
};

const recentUpdateSubject = (res) => {
    const mostRecent = curriculumForm.multi[res.curriculumKey].subjects
        .filter(
            (s) =>
                s.semester_id == res.semesterId &&
                parseInt(s.year) == parseInt(res.year),
        )
        .reduce((latest, s) => {
            return !latest ||
                new Date(s.updated_at) > new Date(latest.updated_at)
                ? s
                : latest;
        }, null);

    if (mostRecent == null) return;

    const stringRecent =
        mostRecent.updated_by == null
            ? mostRecent.created_by + "—" + mostRecent.updated_at_formatted
            : mostRecent.updated_by + "—" + mostRecent.updated_at_formatted;

    return stringRecent;
};

const semesterForm = useForm({
    type: "create",
    campusId: null,
    semester: [],
});

const curriculumForm = useForm({
    multi: [],
});

const courseForm = useForm({
    id: null,
    campusId: null,
    course: null,
    years: null,
    curriculum: [
        {
            id: null,
            course_id: null,
            semester_id: null,
            template: false,
            templateDefault: [],
            years: null,
        },
    ],
});

const detailsForm = useForm({
    id: null,
    campusId: null,
    dean: null,
    registrar: null,
    contact: null,
    email: null,
});

const gradeForm = useForm({
    id: null,
    campusId: null,
    grade: null,
    upper: null,
    lower: null,
    fail: false,
    incomplete: false,
    drop: false,
});

const templateForm = useForm({
    select: null,
});

const toggleCourseOption = (event, rowData) => {
    selectedRow.value = rowData;
    menu.value.toggle(event);
};

const toggleGradeOption = (event, rowData) => {
    selectedRow.value = rowData;
    menuGrade.value.toggle(event);
};

const clearSearch = () => {
    searchInput.value = null;
};

const openDrawer = () => {
    drawer.value = true;
};

const toggleModal = (res) => {
    typeDialog.value = res.class;
    hideRemoveButton.value = res.type;
    courseForm.resetAndClearErrors();
    gradeForm.resetAndClearErrors();
    if (res.type === "edit" && res.class == "course") {
        courseForm.subjects = [];
        courseForm.id = selectedRow.value.id;
        courseForm.course = selectedRow.value.course_array;
        courseForm.years = selectedRow.value.years;
        selectedRow.value.subjects.forEach((element) => {
            courseForm.subjects.push({
                id: element.id,
                name: element.name,
                code: element.subject_code,
                class: element.class_array,
                unit: element.unit,
            });
        });
    }
    if (res.type === "edit" && res.class == "grade") {
        gradeForm.id = selectedRow.value.id;
        gradeForm.grade = selectedRow.value.grade;
        gradeForm.upper = selectedRow.value.upper;
        gradeForm.lower = selectedRow.value.lower;
        gradeForm.fail = selectedRow.value.is_failed;
        gradeForm.drop = selectedRow.value.is_drop;
        gradeForm.incomplete = selectedRow.value.is_incomplete;
    }

    if (res.class == "course") {
        toolbarCourseRef.value.openModal();
    } else {
        toolbarGradeRef.value.openModal();
    }
};

const menuItems = computed(() => {
    if (!selectedRow.value) return [];

    return [
        {
            label: "Subjects",
            icon: IconBooks,
            class: "text-gray-600",
            command: () => {
                router.reload({
                    data: {
                        campusCourseId: selectedRow.value.id,
                        semTypeId: page.props?.schoolDetail.term_array.id,
                        schoolId: page.props?.schoolDetail.school_id,
                    },
                    only: ["subjectDetail", "templateOptions"],
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        curriculumForm.multi = [];
                        if (page.props?.subjectDetail.length != 0) {
                            for (
                                let index = 0;
                                index < page.props?.subjectDetail.length;
                                index++
                            ) {
                                curriculumForm.multi.unshift(
                                    page.props?.subjectDetail[index],
                                );
                            }
                        } else {
                            addCurriculum();
                        }

                        templateForm.reset();
                        subjectDialog.value = true;
                        // curriculumDialog.value = true;
                    },
                });
            },
        },
        {
            label: "Edit",
            icon: IconPencilCog,
            class: "text-gray-600",
            command: () => {
                toggleModal({
                    type: "edit",
                    class: "course",
                    data: selectedRow.value,
                });
            },
        },
        {
            label: "Delete",
            icon: IconTrash,
            class: "text-red-500",
            command: () => {
                deleteRow({ id: selectedRow.value.id, type: "course" });
            },
        },
    ];
});

const addCurriculum = () => {
    if (!selectedRow.value) return [];
    curriculumForm.multi.push({
        yearNumber: parseInt(selectedRow.value.years),
        campus_course_id: selectedRow.value.id,
        semesterTypeId: page.props?.schoolDetail.term_array.id,
        edit: false,
        yearLevel: null,
        subjects: [],
        id: null,
    });

    curriculumForm.multi[curriculumForm.multi.length - 1].subjects.forEach(
        (cur, curKey) => {
            for (
                let indexYear = 0;
                indexYear < parseInt(selectedRow.value.years);
                indexYear++
            ) {
                page.props?.semesterOption.forEach((sem, semKey) => {
                    curriculumForm.multi[curKey].subjects.push({
                        id: null,
                        curriculumId: null,
                        year: indexYear + 1,
                        semester: sem.name,
                        semester_array: sem,
                        name: null,
                        class_array: null,
                        subjectClass: null,
                        unit: null,
                    });
                });
            }
        },
    );
};

const addSubject = (curriculumKey, year, semester) => {
    const newSubject = JSON.parse(
        JSON.stringify({
            id: null,
            curriculumId: null,
            semester_id: null,
            name: null,
            semester_array: semester,
            class_array: null,
            subject_class: null,
            subjectCode: null,
            unit: null,
            year: year,
        }),
    );

    curriculumForm.multi[curriculumKey].subjects.push(newSubject);
};

const deleteCurriculumAndSubject = (res) => {
    if (!canManageSchools.value) return;

    if (res.button == "subject") {
        if (res.type) {
            curriculumForm.multi[res.curriculum].subjects.splice(
                res.subject,
                1,
            );
        } else {
            props.confirmRef.popupDialog(() => {
                curriculumForm.delete(
                    route("campus.curriculum.destroysubject", {
                        id: curriculumForm.multi[res.curriculum].subjects[
                            res.subject
                        ].id,
                        type: "delete",
                    }),
                    {
                        onSuccess: () => {
                            curriculumForm.clearErrors();
                            curriculumForm.multi[
                                res.curriculum
                            ].subjects.splice(res.subject, 1);
                            toastRef.value.show(page.props.flash);
                        },
                    },
                );
            });
        }
    } else {
        if (res.type) {
            curriculumForm.multi.splice(res.curriculum, 1);
        } else {
            props.confirmRef.popupDialog(() => {
                curriculumForm.delete(
                    route("campus.curriculum.destroyCurriculum", {
                        id: curriculumForm.multi[res.curriculum].id,
                        type: "delete",
                    }),
                    {
                        onSuccess: () => {
                            curriculumForm.clearErrors();
                            curriculumForm.multi.splice(res.curriculum, 1);
                            toastRef.value.show(page.props.flash);
                        },
                    },
                );
            });
        }
    }
};

const gradeMenuItems = computed(() => {
    if (!selectedRow.value) return [];

    return [
        {
            label: "Edit",
            icon: IconPencilCog,
            class: "text-gray-600",
            command: () => {
                toggleModal({
                    type: "edit",
                    class: "grade",
                    data: selectedRow.value,
                });
            },
        },
        {
            label: "Delete",
            icon: IconTrash,
            class: "text-red-500",
            command: () => {
                deleteRow({ id: selectedRow.value.id, type: "grade" });
            },
        },
    ];
});

const openUpdateSchool = () => {
    if (!canManageSchools.value) return;

    updateSchool.value = true;
    detailsForm.campusId = props.id;
    if (page.props.schoolDetail.info.length != 0) {
        detailsForm.id = page.props.schoolDetail.info.id;
        detailsForm.dean = page.props.schoolDetail.info.dean;
        detailsForm.registrar = page.props.schoolDetail.info.registrar;
        detailsForm.contact = page.props.schoolDetail.info.contact;
        detailsForm.email = page.props.schoolDetail.info.email;
    }
};

const UpdateDetailsForm = () => {
    if (!canManageSchools.value) return;

    if (!detailsForm.id) {
        detailsForm.post(route("campus.info.store"), {
            onSuccess: () => {
                toastRef.value.show(page.props.flash);
                detailsForm.clearErrors();
                updateSchool.value = false;
            },
        });
    } else {
        detailsForm.put(
            route("campus.info.update", {
                id: detailsForm.id,
                type: "form",
            }),
            {
                onSuccess: () => {
                    detailsForm.clearErrors();
                    updateSchool.value = false;
                    toastRef.value.show(page.props.flash);
                },
            },
        );
    }
};

const submitCurriculum = () => {
    if (!canManageSchools.value) return;

    curriculumForm.post(route("campus.curriculum.store"), {
        onSuccess: () => {
            curriculumForm.resetAndClearErrors();
            curriculumForm.multi.length = 0;
            if (page.props?.subjectDetail.length != 0) {
                for (
                    let index = 0;
                    index < page.props?.subjectDetail.length;
                    index++
                ) {
                    curriculumForm.multi.unshift(
                        page.props?.subjectDetail[index],
                    );
                }
            }
            toastRef.value.show(page.props.flash);
        },
    });
};

const copyTemplate = (curKey) => {
    if (!canManageSchools.value) return;

    router.patch(
        route("campus.curriculum.copy", curriculumForm.multi[curKey].id),
        {},
        {
            onSuccess: () => {
                curriculumForm.multi[curKey].has_replication = true;
                curriculumForm.multi[curKey].templateDetails =
                    page.props.curriculumDetails;

                toastRef.value.show(page.props.flash);
            },
        },
    );
};

const pasteTemplate = (curKey) => {
    if (!canManageSchools.value) return;

    loading.paste = true;
    templateForm.patch(
        route(
            "campus.curriculum.paste",
            curriculumForm.multi[curKey].campus_course_id,
        ),
        {
            onSuccess: () => {
                router.reload({
                    data: {
                        campusCourseId: selectedRow.value.id,
                        semTypeId: page.props?.schoolDetail.term_array.id,
                        schoolId: page.props?.schoolDetail.school_id,
                    },
                    only: ["subjectDetail", "templateOptions"],
                    preserveState: true,
                    preserveScroll: true,
                    onSuccess: () => {
                        curriculumForm.multi = [];
                        if (page.props?.subjectDetail.length != 0) {
                            for (
                                let index = 0;
                                index < page.props?.subjectDetail.length;
                                index++
                            ) {
                                curriculumForm.multi.unshift(
                                    page.props?.subjectDetail[index],
                                );
                            }
                        }
                        templateForm.resetAndClearErrors();
                        toastRef.value.show(page.props.flash);
                    },
                    onFinish: () => {
                        loading.paste = false;
                    },
                });
            },
        },
    );
};

const submitForm = (res) => {
    if (!canManageSchools.value) return;

    if (res == "courses") {
        courseForm.campusId = props.id;
        if (!courseForm.id) {
            courseForm.post(route("academic.universities.course.store"), {
                onSuccess: () => {
                    courseForm.resetAndClearErrors();
                    toastRef.value.show(page.props.flash);
                },
            });
        } else {
            courseForm.put(
                route("academic.universities.course.update", {
                    id: courseForm.id,
                    type: "form",
                }),
                {
                    onSuccess: () => {
                        toolbarCourseRef.value.closeModal();
                        courseForm.resetAndClearErrors();
                        toastRef.value.show(page.props.flash);
                    },
                },
            );
        }
    } else if (res == "semesters") {
        semesterForm.campusId = props.id;

        if (semesterForm.type == "edit") {
            semesterForm.semester.forEach((element) => {
                if (element.startDate) {
                    const d = new Date(element.startDate);
                    const year = d.getFullYear();
                    const month = String(d.getMonth() + 1).padStart(2, "0"); // +1 because JS months start at 0
                    element.startDateFormatted = `${year}-${month}-01`;
                }

                if (element.endDate) {
                    const d2 = new Date(element.endDate);
                    const year2 = d2.getFullYear();
                    const month2 = String(d2.getMonth() + 1).padStart(2, "0");
                    element.endDateFormatted = `${year2}-${month2}-01`;
                }

                if (element.submissionDate) {
                    const d2 = new Date(element.submissionDate);

                    const year = d2.getFullYear();
                    const month = String(d2.getMonth() + 1).padStart(2, "0");
                    const day = String(d2.getDate()).padStart(2, "0");
                    console.log(`${year}-${month}-${day}`);
                    element.submissionDateFormatted = `${year}-${month}-${day}`;
                }
            });
            semesterForm.put(
                route("campus.semester.update", {
                    id: props.id,
                    type: "form",
                }),
                {
                    onSuccess: () => {
                        semesterForm.clearErrors();
                        toastRef.value.show(page.props.flash);
                    },
                },
            );
        } else {
            semesterForm.semester.forEach((element) => {
                if (element.startDate) {
                    const d = new Date(element.startDate);
                    const year = d.getFullYear();
                    const month = String(d.getMonth() + 1).padStart(2, "0");
                    element.startDateFormatted = `${year}-${month}-01`;
                }

                if (element.endDate) {
                    const d2 = new Date(element.endDate);
                    const year2 = d2.getFullYear();
                    const month2 = String(d2.getMonth() + 1).padStart(2, "0");
                    element.endDateFormatted = `${year2}-${month2}-01`;
                }

                if (element.submissionDate) {
                    const d2 = new Date(element.submissionDate);
                    const year2 = d2.getFullYear();
                    const month2 = String(d2.getMonth() + 1).padStart(2, "0");
                    element.submissionDateFormatted = `${year2}-${month2}-01`;
                }
            });

            semesterForm.post(route("campus.semester.store"), {
                onSuccess: () => {
                    semesterForm.clearErrors();
                    toastRef.value.show(page.props.flash);
                },
            });
        }
    } else {
        gradeForm.campusId = props.id;
        if (!gradeForm.id) {
            gradeForm.post(route("academic.universities.grade.store"), {
                onSuccess: () => {
                    gradeForm.resetAndClearErrors();
                    toastRef.value.show(page.props.flash);
                    toolbarGradeRef.value.closeModal();
                },
            });
        } else {
            gradeForm.put(
                route("academic.universities.grade.update", {
                    id: gradeForm.id,
                    type: "form",
                }),
                {
                    onSuccess: () => {
                        toolbarGradeRef.value.closeModal();
                        toastRef.value.show(page.props.flash);
                        gradeForm.resetAndClearErrors();
                    },
                },
            );
        }
    }
};

const deleteRow = (res) => {
    if (!canManageSchools.value) return;

    switch (res.type) {
        case "subject":
            props.confirmRef.popupDialog(() => {
                router.delete(
                    route("subject.destroy", { id: res.id, type: "delete" }),
                    {
                        onSuccess: () => {
                            toastRef.value.show(page.props.flash);
                        },
                    },
                );
            });
            break;
        case "grade":
            props.confirmRef.popupDialog(() => {
                router.delete(
                    route("academic.universities.grade.destroy", {
                        id: res.id,
                        type: "delete",
                    }),
                    {
                        onSuccess: () => {
                            toastRef.value.show(page.props.flash);
                        },
                    },
                );
            });
            break;
        case "campus":
            props.confirmRef.popupDialog(() => {
                router.delete(
                    route("academic.universities.destroy", {
                        id: res.id,
                        type: "delete",
                    }),
                    {
                        onSuccess: () => {
                            toastRef.value.show(page.props.flash);
                            drawer.value = false;
                        },
                    },
                );
            });
            break;
        default:
            props.confirmRef.popupDialog(() => {
                router.delete(
                    route("academic.universities.course.destroy", {
                        id: res.id,
                        type: "delete",
                    }),
                    {
                        onSuccess: () => {
                            toastRef.value.show(page.props.flash);
                        },
                    },
                );
            });
            break;
    }
};

defineExpose({
    openDrawer,
});

watch(
    () => subjectDialog.value,
    (val) => {
        if (val) return;
        curriculumForm.multi = JSON.parse(JSON.stringify([]));
        curriculumForm.resetAndClearErrors();
        curriculumForm.reset();
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
    () => page.props?.semesterOption,
    (val) => {
        semesterForm.semester = [];
        if (!page.props?.semesterOption) {
            return;
        }

        if (page.props?.schoolDetail?.semesters.length > 0) {
            semesterForm.type = "edit";
            page.props?.schoolDetail?.semesters.forEach((element) => {
                semesterForm.semester.push({
                    id: element.id,
                    semesterId: element.semester_array.id,
                    name: element.semester_array.name,
                    startDate: new Date(element.start_date),
                    startDateFormatted: new Date(element.start_date),
                    endDate: new Date(element.end_date),
                    endDateFormatted: new Date(element.end_date),
                    submissionDate: new Date(element.submission_date),
                    submissionDateFormatted: new Date(element.submission_date),
                });
            });
            semesterForm.semester.sort((a, b) => a.name.localeCompare(b.name));
        } else {
            semesterForm.type = "create";
            val.forEach((element) => {
                semesterForm.semester.push({
                    id: null,
                    semesterId: element.id,
                    name: element.name,
                    startDate: null,
                    startDateFormatted: null,
                    endDate: null,
                    endDateFormatted: null,
                    submissionDate: null,
                    submissionDateFormatted: null,
                });
            });
        }
    },
);
</script>
