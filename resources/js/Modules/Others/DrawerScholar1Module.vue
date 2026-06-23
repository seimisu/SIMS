<template>
    <Drawer
        v-model:visible="modelValue"
        position="full"
        :pt="{
            header: 'border-b-1 border-gray-300 border-dashed',
            content: 'bg-slate-50',
        }"
    >
        <template #header>
            <div
                class="bg-slate-100 px-4 py-2 shadow rounded-lg flex items-center gap-2"
            >
                <IconId :size="20" :stroke-width="2" />
                <div class="uppercase font-medium">Scholar Details</div>
            </div>
        </template>
        <template #default>
            <div class="flex flex-col lg:flex-row w-full my-5 gap-3 h-full">
                <div class="lg:flex-2 flex flex-col bg-white p-2 rounded-2xl">
                    <div class="flex-1">
                        <div class="flex gap-2 items-center">
                            <div class="">
                                <Avatar
                                    v-if="
                                        page.props?.details?.profile?.photo ==
                                        null
                                    "
                                    class="!w-[9rem] !h-[9rem] !rounded-xl !text-5xl !bg-slate-300"
                                >
                                    <IconUserFilled :size="80" />
                                </Avatar>

                                <Avatar
                                    v-else
                                    :image="page.props.details?.profile?.photo"
                                    class="!w-[9rem] !h-[9rem] !bg-white shadow p-1 !rounded-xl"
                                />
                            </div>
                            <div class="flex-1 flex flex-col">
                                <div class="flex flex-col gap-2">
                                    <div class="">
                                        <div
                                            class="text-xs font-light text-gray-400 leading-none"
                                        >
                                            SPAS NO.
                                        </div>
                                        <div
                                            class="flex items-center text-sm gap-1"
                                        >
                                            <div
                                                v-tooltip.top="'Copy'"
                                                class="w-fit"
                                            >
                                                <IconCopy
                                                    :size="15"
                                                    class="cursor-pointer"
                                                />
                                            </div>
                                            <div class="">
                                                {{
                                                    page.props.details?.spas_no
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <div
                                            class="text-xs font-light text-gray-400 leading-none"
                                        >
                                            NAME
                                        </div>

                                        <div
                                            class="flex items-center text-sm gap-1"
                                        >
                                            <div
                                                v-tooltip.top="'Copy'"
                                                class="w-fit"
                                            >
                                                <IconUser
                                                    :size="15"
                                                    class="cursor-pointer"
                                                />
                                            </div>
                                            <div
                                                class="font-medium text-gray-600 text-sm"
                                            >
                                                {{
                                                    page.props.details?.fullname
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                    <div>
                                        <div
                                            class="text-xs font-light text-gray-400 leading-none"
                                        >
                                            EMAIL
                                        </div>
                                        <div
                                            class="flex items-center text-sm gap-1"
                                        >
                                            <div
                                                v-tooltip.top="'Copy'"
                                                class="w-fit"
                                            >
                                                <IconAt
                                                    :size="15"
                                                    class="cursor-pointer"
                                                />
                                            </div>
                                            <div
                                                class="font-medium text-gray-600 text-sm"
                                            >
                                                {{ page.props.details?.email }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <Divider align="left">
                            <span class="text-xs font-medium"
                                >Scholar Details</span
                            >
                        </Divider>
                    </div>

                    <div class="flex-3 flex flex-col">
                        <div class="flex items-center justify-center">
                            <div
                                class="bg-slate-50 flex gap-7 rounded-2xl py-2 px-10"
                            >
                                <div
                                    class="flex flex-col items-center justify-center"
                                >
                                    <div>
                                        {{ page.props?.details?.type.name }}
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        Type
                                    </div>
                                </div>
                                <div
                                    class="flex flex-col items-center justify-center"
                                >
                                    <div>
                                        {{ page.props?.details?.program.name }}
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        Program
                                    </div>
                                </div>
                                <div
                                    class="flex flex-col items-center justify-center"
                                >
                                    <div>
                                        {{ page.props?.details?.awardYear }}
                                    </div>
                                    <div class="text-xs text-gray-400">
                                        Award Year
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 flex flex-col gap-3">
                            <div class="flex items-center gap-2">
                                <Avatar
                                    class="rounded-full border !bg-blue-50 !text-blue-500"
                                    size="small"
                                >
                                    <IconMapPin :size="20" stroke-width="2" />
                                </Avatar>
                                <div class="text-sm">
                                    {{ page.props?.details?.region["name"] }}
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Avatar
                                    class="rounded-full border !bg-blue-50 !text-blue-500"
                                    size="small"
                                >
                                    <IconSchool :size="20" stroke-width="2" />
                                </Avatar>
                                <div class="text-sm">
                                    {{ page.props?.details?.course }}
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Avatar
                                    class="rounded-full border !bg-blue-50 !text-blue-500"
                                    size="small"
                                >
                                    <IconBuildingEstate
                                        :size="20"
                                        stroke-width="2"
                                    />
                                </Avatar>
                                <div class="text-sm">
                                    {{ page.props?.details?.school }}
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <div
                                    :class="[
                                        'rounded-full border p-[5px]',
                                        page.props?.details?.status?.bcolor,
                                        page.props?.details?.status?.tcolor,
                                    ]"
                                >
                                    <component
                                        :is="
                                            TablerIcons[
                                                page.props?.details?.status.icon
                                            ]
                                        "
                                        :size="20"
                                        :stroke="2"
                                    />
                                </div>
                                <div class="text-sm uppercase">
                                    {{ page.props?.details?.status.name }}
                                </div>
                            </div>
                        </div>
                        <Divider align="left">
                            <span class="text-xs font-medium"
                                >Scholar Menu</span
                            >
                        </Divider>
                    </div>
                    <div class="flex-3">
                        <Menu :model="tabs">
                            <template #item="{ item, props }">
                                <a
                                    v-ripple
                                    :class="[
                                        'flex items-center gap-2 px-3 py-2 cursor-pointer',
                                        selectedTab.key == item.key
                                            ? 'text-blue-600'
                                            : '',
                                    ]"
                                    @click="changeMenu(item)"
                                >
                                    <component
                                        :is="TablerIcons[item.icon]"
                                        :size="18"
                                    ></component>
                                    <span class="!text-xs">{{
                                        item.label
                                    }}</span>
                                    <Badge
                                        v-if="item.badge"
                                        size="small"
                                        severity="danger"
                                        class="ml-auto !text-xs"
                                        :value="item.badge"
                                    />
                                    <Badge
                                        v-if="item.status"
                                        size="small"
                                        severity="info"
                                        class="ml-auto !text-xs"
                                        :value="item.status"
                                    />
                                </a>
                            </template>
                        </Menu>
                    </div>
                </div>
                <div class="lg:flex-7">
                    <Panel
                        class="!rounded-xl"
                        v-if="selectedTab.key == 1"
                        :pt="{
                            header: '!border-b-1 !border-gray-300 !border-dashed !p-0',
                        }"
                    >
                        <template #header>
                            <div class="p-2 w-full flex justify-between">
                                <div class="flex items-center gap-2">
                                    <Avatar
                                        class="rounded-full !bg-blue-100 !text-blue-500"
                                        size="small"
                                    >
                                        <IconUserQuestion
                                            :size="20"
                                            stroke-width="2"
                                        />
                                    </Avatar>
                                    <h3 class="text-lg font-bold text-nowrap">
                                        Personal Records
                                    </h3>
                                </div>
                                <div class="flex w-full justify-end gap-2">
                                    <div>
                                        <DefaultButton
                                            :icon="TablerIcons.IconTransfer"
                                            @click="opTransfer.toggle($event)"
                                            outlined
                                            severity="secondary"
                                            label="Transfer School/Course"
                                            size="small"
                                            class-name="!rounded-xl !px-5"
                                            rounded
                                        />
                                        <Popover ref="opTransfer">
                                            <div
                                                class="w-100 flex flex-col text-sm"
                                            >
                                                <div
                                                    class="flex items-start p-3 shadow border border-blue-300 text-blue-500 rounded-xl bg-blue-50 gap-1"
                                                >
                                                    <div>
                                                        <IconExclamationCircleFilled
                                                            :size="20"
                                                        />
                                                    </div>

                                                    <p
                                                        class="text-xs leading-5 text-justify"
                                                    >
                                                        Update the scholar's
                                                        school and course
                                                        information based on the
                                                        approved transfer
                                                        request. Ensure that all
                                                        changes are accurate
                                                        before saving.
                                                    </p>
                                                </div>
                                                <Tabs :value="transferTab">
                                                    <TabList>
                                                        <Tab value="school">
                                                            <div
                                                                class="flex items-center gap-2"
                                                            >
                                                                <IconSchool />
                                                                <div>
                                                                    School
                                                                </div>
                                                            </div>
                                                        </Tab>
                                                        <Tab value="course">
                                                            <div
                                                                class="flex items-center gap-2"
                                                            >
                                                                <IconBook2 />
                                                                <div>
                                                                    Course
                                                                </div>
                                                            </div></Tab
                                                        >
                                                    </TabList>
                                                    <TabPanels
                                                        :pt="{
                                                            root: {
                                                                class: '!p-0 !pt-3',
                                                            },
                                                        }"
                                                    >
                                                        <TabPanel
                                                            value="school"
                                                        >
                                                            <div
                                                                class="flex flex-col gap-3"
                                                            >
                                                                <SelectInput
                                                                    label="School"
                                                                    :error-mark="
                                                                        transferInfo
                                                                            .errors
                                                                            .school !=
                                                                        null
                                                                            ? true
                                                                            : false
                                                                    "
                                                                    v-model="
                                                                        transferInfo.school
                                                                    "
                                                                    @update:model-value="
                                                                        rendertCourse
                                                                    "
                                                                    :options="
                                                                        page
                                                                            .props
                                                                            ?.schoolOptions
                                                                    "
                                                                ></SelectInput>
                                                                <SelectInput
                                                                    label="Course"
                                                                    v-model="
                                                                        transferInfo.course
                                                                    "
                                                                    :loading="
                                                                        loading.transferCourse
                                                                    "
                                                                    :disable="
                                                                        transferInfo.school !=
                                                                        null
                                                                            ? false
                                                                            : true
                                                                    "
                                                                    :options="
                                                                        page
                                                                            .props
                                                                            ?.transferCourseOptions
                                                                    "
                                                                ></SelectInput>

                                                                <DefaultButton
                                                                    :icon="
                                                                        TablerIcons.IconTransfer
                                                                    "
                                                                    @click="
                                                                        transferSubmit
                                                                    "
                                                                    raised
                                                                    :loading="
                                                                        loading.transferSubmit
                                                                    "
                                                                    label="Transfer"
                                                                    size="small"
                                                                    class-name="!rounded-xl !px-5"
                                                                />
                                                            </div>
                                                        </TabPanel>
                                                        <TabPanel
                                                            value="course"
                                                        >
                                                            <p class="m-0">
                                                                Sed ut
                                                                perspiciatis
                                                                unde omnis iste
                                                                natus error sit
                                                                voluptatem
                                                                accusantium
                                                                doloremque
                                                                laudantium,
                                                                totam rem
                                                                aperiam, eaque
                                                                ipsa quae ab
                                                                illo inventore
                                                                veritatis et
                                                                quasi architecto
                                                                beatae vitae
                                                                dicta sunt
                                                                explicabo. Nemo
                                                                enim ipsam
                                                                voluptatem quia
                                                                voluptas sit
                                                                aspernatur aut
                                                                odit aut fugit,
                                                                sed quia
                                                                consequuntur
                                                                magni dolores
                                                                eos qui ratione
                                                                voluptatem sequi
                                                                nesciunt.
                                                                Consectetur,
                                                                adipisci velit,
                                                                sed quia non
                                                                numquam eius
                                                                modi.
                                                            </p>
                                                        </TabPanel>
                                                    </TabPanels>
                                                </Tabs>
                                            </div>
                                        </Popover>
                                    </div>

                                    <DefaultButton
                                        :icon="TablerIcons.IconCreditCard"
                                        @click="storePersonalInfo"
                                        outlined
                                        severity="secondary"
                                        label="View Landbank Details"
                                        size="small"
                                        rounded
                                        class-name="!rounded-xl !px-5"
                                    />
                                    <Divider layout="vertical" />
                                    <DefaultButton
                                        :icon="TablerIcons.IconUserEdit"
                                        label="Edit Details"
                                        size="small"
                                        v-if="!editBtn.info"
                                        @click="EditMode"
                                        raised
                                        class-name="!rounded-xl !px-5"
                                    />
                                    <div class="flex items-center gap-2" v-else>
                                        <DefaultButton
                                            :icon="TablerIcons.IconUserCancel"
                                            label="Cancel Edit"
                                            size="small"
                                            severity="danger"
                                            @click="cancelEdit"
                                            outlined
                                            class-name="!rounded-xl !px-5"
                                        />
                                        <DefaultButton
                                            :icon="TablerIcons.IconUserCheck"
                                            @click="storePersonalInfo"
                                            raised
                                            :loading="loading.storePersonalInfo"
                                            label="Save this details"
                                            size="small"
                                            class-name="!rounded-xl !px-5"
                                        />
                                    </div>
                                </div>
                            </div>
                        </template>
                        <template #default>
                            <div class="w-full flex flex-col pt-5 gap-3">
                                <div class="flex items-center gap-3">
                                    <TextInput
                                        v-model="personalInfo.first_name"
                                        label="First Name"
                                        :disabled="!editBtn.info"
                                    ></TextInput>

                                    <TextInput
                                        v-model="personalInfo.middle_name"
                                        label="Middle Name"
                                        :disabled="!editBtn.info"
                                    ></TextInput>
                                </div>
                                <div class="flex items-center gap-3">
                                    <TextInput
                                        v-model="personalInfo.last_name"
                                        label="Last Name"
                                        :disabled="!editBtn.info"
                                    ></TextInput>
                                    <TextInput
                                        v-model="personalInfo.suffix"
                                        label="Suffix"
                                        :disabled="!editBtn.info"
                                    >
                                    </TextInput>
                                </div>
                                <div class="flex items-center gap-2">
                                    <TextInput
                                        v-model="personalInfo.email"
                                        label="Email"
                                        :disabled="!editBtn.info"
                                    >
                                    </TextInput>
                                    <TextInput
                                        v-model="personalInfo.contact_no"
                                        label="Contact No"
                                        :disabled="!editBtn.info"
                                    ></TextInput>
                                </div>
                                <div class="flex items-center gap-2">
                                    <DatePickerInput
                                        v-model="personalInfo.birth_date"
                                        label="Birth Date"
                                        :disabled="!editBtn.info"
                                    ></DatePickerInput>
                                    <TextInput
                                        v-model="personalInfo.birth_place"
                                        label="Birth Place"
                                        :disabled="!editBtn.info"
                                    ></TextInput>
                                </div>
                                <div class="flex items-center gap-2">
                                    <TextInput
                                        v-model="personalInfo.religion"
                                        label="Religion"
                                        capitalize
                                        :disabled="!editBtn.info"
                                    ></TextInput>
                                    <TextInput
                                        v-model="personalInfo.civil_status"
                                        capitalize
                                        label="Civil Status"
                                        :disabled="!editBtn.info"
                                    >
                                    </TextInput>
                                </div>

                                <TextInput
                                    v-model="personalInfo.address"
                                    label="Address"
                                    :disabled="!editBtn.info"
                                    placeholder="Street, Subdivision, etc."
                                ></TextInput>
                                <AutoCompleteInput
                                    v-model="personalInfo.fulladdress"
                                    :options="page.props?.resultSearch"
                                    :disabled="!editBtn.info"
                                    :loading="loading.address"
                                    placeholder="Find by Barangay, Municipality, Province, or Region"
                                    @complete="autoSearch"
                                    selection
                                ></AutoCompleteInput>
                                <Divider align="left">
                                    <span class="text-xs font-semibold"
                                        >Scholarship Assignment</span
                                    >
                                </Divider>
                                <div class="flex items-center gap-3">
                                    <SelectInput
                                        label="Scholar Program"
                                        v-model="personalInfo.program"
                                        :disable="!editBtn.info"
                                        :options="page.props?.programOptions"
                                    ></SelectInput>

                                    <SelectInput
                                        label="Type"
                                        v-model="personalInfo.sub_program"
                                        :disable="!editBtn.info"
                                        :options="page.props?.subProgramOptions"
                                    ></SelectInput>
                                </div>
                                <div class="flex items-center gap-3">
                                    <SelectInput
                                        label="School"
                                        v-model="personalInfo.school"
                                        :disable="!editBtn.info"
                                        @update:model-value="renderCourse"
                                        :options="page.props?.schoolOptions"
                                    ></SelectInput>
                                    <SelectInput
                                        label="Course"
                                        v-model="personalInfo.course"
                                        :disable="!editBtn.info"
                                        :options="page.props?.courseOptions"
                                    ></SelectInput>
                                </div>
                                <div class="flex items-center gap-3">
                                    <DatePickerInput
                                        v-model="personalInfo.award_year"
                                        label="Award Year"
                                        view="year"
                                        :disabled="!editBtn.info"
                                        format-date="yy"
                                    ></DatePickerInput>
                                    <SelectInput
                                        label="Status"
                                        v-model="personalInfo.status"
                                        :disable="!editBtn.info"
                                        class="capitalize"
                                        :options="page.props?.statusOptions"
                                    ></SelectInput>
                                </div>

                                <Divider align="left">
                                    <span class="text-xs font-semibold"
                                        >Landbank Information</span
                                    >
                                </Divider>
                                <TextInput
                                    v-model="personalInfo.acc_name"
                                    label="Account Name"
                                    capitalize
                                    :disabled="!editBtn.info"
                                ></TextInput>
                                <TextInput
                                    v-model="personalInfo.acc_no"
                                    capitalize
                                    label="Account Number"
                                    :disabled="!editBtn.info"
                                >
                                </TextInput>

                                <!-- <Divider align="left">
                                    <span class="text-xs font-semibold"
                                        >Guardian Information</span
                                    >
                                </Divider>
                                <div class="flex items-center gap-3">
                                    <TextInput
                                        v-model="personalInfo.guardian_name"
                                        label="Parent/Guardian Name"
                                        capitalize
                                        :disabled="!editBtn.info"
                                    ></TextInput>
                                    <TextInput
                                        v-model="personalInfo.guardian_id_no"
                                        label="ID Number"
                                        :disabled="!editBtn.info"
                                    >
                                    </TextInput>
                                </div>
                                <div class="flex items-center gap-3">
                                    <TextInput
                                        v-model="
                                            personalInfo.guardian_place_issue
                                        "
                                        label="ID Place of Issue"
                                        :disabled="!editBtn.info"
                                    ></TextInput>
                                    <TextInput
                                        v-model="
                                            personalInfo.guardian_date_issue
                                        "
                                        label="ID Date of Issue"
                                        :disabled="!editBtn.info"
                                    >
                                    </TextInput>
                                </div> -->
                            </div>
                        </template>
                    </Panel>
                    <div
                        class="flex flex-col gap-4"
                        v-if="selectedTab.key == 2"
                    >
                        <div
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-3"
                            v-if="
                                page.props?.details?.termGrades?.length > 0
                            "
                        >
                            <div
                                class="mb-3 flex flex-col gap-1 rounded-xl bg-white p-3 text-gray-700 shadow-sm"
                            >
                                <div class="flex items-center gap-2">
                                    <IconHistory :size="18" />
                                    <span class="text-sm font-semibold">
                                    Academic Records
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500">
                                    Records are arranged from oldest to newest.
                                    Totals and semester average use academic
                                    subjects only.
                                </p>
                            </div>
                            <div
                                v-for="(termRecord, index) in page.props
                                    ?.details?.termGrades"
                                class="my-3"
                            >
                                <Panel
                                    class="!rounded-xl"
                                    :pt="{
                                        header: '!border-b-1 !border-gray-300 !border-dashed !p-0',
                                    }"
                                >
                                    <template #header>
                                        <div
                                            class="p-3 w-full flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
                                        >
                                            <div
                                                class="flex items-center gap-3 text-md font-medium text-gray-700"
                                            >
                                                <IconSchool
                                                    class="text-gray-500"
                                                    :size="20"
                                                />
                                                <span
                                                    class="px-2 py-0.5 rounded-md bg-slate-200"
                                                >
                                                    {{
                                                        termRecord.academic_year
                                                    }}
                                                </span>
                                                <span
                                                    class="px-2 py-0.5 rounded-md bg-gray-100"
                                                >
                                                    {{
                                                        termRecord.term
                                                            ?.name ?? "Term"
                                                    }}
                                                </span>
                                            </div>
                                            <div
                                                class="flex flex-wrap items-center gap-2 lg:justify-end"
                                            >
                                                <span
                                                    class="text-xs font-semibold uppercase text-gray-400"
                                                >
                                                    Documents
                                                </span>
                                                <a
                                                    v-if="
                                                        termRecord.documents
                                                            ?.cor
                                                    "
                                                    :href="
                                                        `/${termRecord.documents.cor.file_path}`.replace(
                                                            '//',
                                                            '/',
                                                        )
                                                    "
                                                    target="_blank"
                                                    class="rounded-lg bg-blue-50 px-2 py-1 text-xs font-medium text-blue-600 hover:bg-blue-100"
                                                >
                                                    COR:
                                                    {{
                                                        termRecord.documents
                                                            .cor.file_name
                                                    }}
                                                </a>
                                                <a
                                                    v-if="
                                                        termRecord.documents
                                                            ?.grades_proof
                                                    "
                                                    :href="
                                                        `/${termRecord.documents.grades_proof.file_path}`.replace(
                                                            '//',
                                                            '/',
                                                        )
                                                    "
                                                    target="_blank"
                                                    class="rounded-lg bg-blue-50 px-2 py-1 text-xs font-medium text-blue-600 hover:bg-blue-100"
                                                >
                                                    Proof:
                                                    {{
                                                        termRecord.documents
                                                            .grades_proof
                                                            .file_name
                                                    }}
                                                </a>
                                                <span
                                                    v-if="
                                                        !termRecord.documents
                                                            ?.cor &&
                                                        !termRecord.documents
                                                            ?.grades_proof
                                                    "
                                                    class="rounded-lg bg-gray-100 px-2 py-1 text-xs text-gray-500"
                                                >
                                                    No attached documents
                                                </span>
                                            </div>
                                        </div>
                                    </template>
                                    <template #default>
                                        <div
                                            class="w-full flex flex-col pt-5 gap-3"
                                        >
                                            <div
                                                class="flex items-center justify-between"
                                            >
                                                <div class="flex flex-col">
                                                    <div class="text-xs">
                                                        {{
                                                            page.props?.details
                                                                ?.course
                                                        }}
                                                    </div>
                                                    <div>
                                                        {{
                                                            page.props?.details
                                                                ?.school
                                                        }}
                                                    </div>
                                                </div>
                                                <div
                                                    class="flex items-center gap-2"
                                                ></div>
                                            </div>
                                            <table
                                                class="min-w-full !border-none text-sm"
                                            >
                                                <thead>
                                                    <tr class="bg-gray-100">
                                                        <th
                                                            class="px-3 py-2 text-left rounded-l-xl"
                                                        >
                                                            Subject Name
                                                        </th>
                                                        <th
                                                            class="px-3 py-2 text-left"
                                                        >
                                                            Subject Code
                                                        </th>
                                                        <th
                                                            class="px-3 py-2 text-right"
                                                        >
                                                            Unit
                                                        </th>
                                                        <th
                                                            class="px-3 py-2 text-right"
                                                        >
                                                            Grades
                                                        </th>
                                                        <th
                                                            class="px-3 py-2 text-right"
                                                        >
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
                                                        v-if="
                                                            !termRecord
                                                                .subjects
                                                                ?.length
                                                        "
                                                    >
                                                        <td
                                                            colspan="6"
                                                            class="px-3 py-6 text-center text-gray-500"
                                                        >
                                                            Approved academic
                                                            record found, but
                                                            no subjects are
                                                            loaded for this
                                                            term yet.
                                                        </td>
                                                    </tr>
                                                    <tr
                                                        v-for="(
                                                            item, index
                                                        ) in termRecord.subjects"
                                                        :key="index"
                                                        class="hover:bg-gray-50"
                                                    >
                                                        <td
                                                            class="px-3 py-2 uppercase max-w-70"
                                                        >
                                                            {{
                                                                item.subject
                                                                    .name
                                                            }}
                                                        </td>
                                                        <td class="px-3 py-2">
                                                            {{
                                                                item.subject
                                                                    ?.code ??
                                                                item.code
                                                            }}
                                                        </td>
                                                        <td
                                                            class="px-3 py-2 text-right"
                                                        >
                                                            {{
                                                                item.subject
                                                                    ?.unit ??
                                                                item.unit
                                                            }}
                                                        </td>
                                                        <td
                                                            class="px-3 py-2 text-right max-w-35"
                                                        >
                                                            {{
                                                                item.request
                                                                    ?.grade ??
                                                                item.grade
                                                                    ?.grade
                                                            }}
                                                        </td>
                                                        <td
                                                            class="px-3 py-2 text-right"
                                                        >
                                                            {{
                                                                item.total ??
                                                                "-"
                                                            }}
                                                        </td>
                                                        <td
                                                            class="px-3 py-2 text-center"
                                                        >
                                                            <div
                                                                v-if="
                                                                    item.request
                                                                        ?.is_drop
                                                                "
                                                                class="text-red-600"
                                                            >
                                                                Dropped
                                                            </div>
                                                            <div
                                                                v-else-if="
                                                                    item.request
                                                                        ?.is_failed
                                                                "
                                                                class="text-rose-600"
                                                            >
                                                                Failed
                                                            </div>
                                                            <div
                                                                v-else-if="
                                                                    item.request
                                                                        ?.is_incomplete
                                                                "
                                                                class="text-amber-600"
                                                            >
                                                                Incompleted
                                                            </div>
                                                            <div
                                                                v-else-if="
                                                                    item.request
                                                                        ?.grade
                                                                "
                                                                class="text-green-600"
                                                            >
                                                                Passed
                                                            </div>
                                                            <div
                                                                v-else-if="
                                                                    item.grade
                                                                        ?.is_drop
                                                                "
                                                                class="text-red-600"
                                                            >
                                                                Dropped
                                                            </div>
                                                            <div
                                                                v-else-if="
                                                                    item.grade
                                                                        ?.is_failed
                                                                "
                                                                class="text-rose-600"
                                                            >
                                                                Failed
                                                            </div>
                                                            <div
                                                                v-else-if="
                                                                    item.grade
                                                                        ?.is_incomplete
                                                                "
                                                                class="text-amber-600"
                                                            >
                                                                Incompleted
                                                            </div>
                                                            <div
                                                                v-else-if="
                                                                    item.grade
                                                                        ?.is_active
                                                                "
                                                                class="text-green-600"
                                                            >
                                                                Passed
                                                            </div>
                                                        </td>
                                                    </tr>
                                                    <tr
                                                        v-if="
                                                            termRecord.subjects
                                                                ?.length
                                                        "
                                                        class="bg-blue-50 font-semibold text-gray-700"
                                                    >
                                                        <td
                                                            class="px-3 py-2"
                                                            colspan="2"
                                                        >
                                                            Semester Average
                                                        </td>
                                                        <td
                                                            class="px-3 py-2 text-right"
                                                        >
                                                            {{
                                                                termRecord
                                                                    .summary
                                                                    ?.units ??
                                                                0
                                                            }}
                                                        </td>
                                                        <td
                                                            class="px-3 py-2"
                                                        ></td>
                                                        <td
                                                            class="px-3 py-2 text-right"
                                                        >
                                                            {{
                                                                termRecord
                                                                    .summary
                                                                    ?.average ??
                                                                "-"
                                                            }}
                                                        </td>
                                                        <td
                                                            class="px-3 py-2"
                                                        ></td>
                                                    </tr>
                                                </tbody>
                                            </table>
                                            <!-- Group Action Buttons -->
                                            <div
                                                v-if="termRecord.gradeRequest"
                                                class="flex w-full justify-end gap-2 pt-4 border-t border-gray-200"
                                            >
                                                <div>
                                                    <DefaultButton
                                                        @click="
                                                            (e) =>
                                                                opGradeToggle(
                                                                    e,
                                                                    index,
                                                                )
                                                        "
                                                        :icon="
                                                            TablerIcons.IconX
                                                        "
                                                        label="Reject All"
                                                        severity="danger"
                                                        :icon-size="15"
                                                        rounded
                                                        outlined
                                                        size="small"
                                                        class-name="!px-5"
                                                    />
                                                    <Popover
                                                        :ref="
                                                            (el) =>
                                                                (opGradeRequest[
                                                                    index
                                                                ] = el)
                                                        "
                                                    >
                                                        <div
                                                            class="w-[26rem] p-1 flex flex-col gap-4"
                                                        >
                                                            <!-- Header -->
                                                            <div
                                                                class="flex items-start justify-between"
                                                            >
                                                                <div>
                                                                    <h3
                                                                        class="text-sm font-semibold text-gray-800"
                                                                    >
                                                                        Reject
                                                                        Grade
                                                                        Request
                                                                    </h3>
                                                                    <p
                                                                        class="text-xs text-gray-500 mt-1"
                                                                    >
                                                                        Provide
                                                                        a reason
                                                                        for
                                                                        rejecting
                                                                        all
                                                                        grade
                                                                        requests.
                                                                    </p>
                                                                </div>
                                                            </div>

                                                            <!-- Divider -->
                                                            <div
                                                                class="border-t"
                                                            ></div>

                                                            <!-- Form -->
                                                            <div
                                                                class="flex flex-col gap-2"
                                                            >
                                                                <label
                                                                    class="text-xs font-medium text-gray-600 leading-0"
                                                                >
                                                                    Remarks
                                                                    <span
                                                                        class="text-red-500"
                                                                        >*</span
                                                                    >
                                                                </label>

                                                                <Textarea
                                                                    v-model="
                                                                        validateGradeForm.reason
                                                                    "
                                                                    rows="4"
                                                                    placeholder="Enter your reason here..."
                                                                    class="w-full !text-sm"
                                                                    size="small"
                                                                />
                                                            </div>

                                                            <!-- Actions -->
                                                            <div
                                                                class="flex justify-end gap-2 pt-2"
                                                            >
                                                                <DefaultButton
                                                                    @click="
                                                                        (e) =>
                                                                            opGradeToggle(
                                                                                e,
                                                                                index,
                                                                            )
                                                                    "
                                                                    label="Cancel"
                                                                    rounded
                                                                    outlined
                                                                    size="small"
                                                                    class-name="!px-4"
                                                                />

                                                                <DefaultButton
                                                                    label="Reject Request"
                                                                    :icon="
                                                                        TablerIcons.IconX
                                                                    "
                                                                    :loading="
                                                                        loading.validateGrade
                                                                    "
                                                                    :disabled="
                                                                        loading.validateGrade
                                                                    "
                                                                    @click="
                                                                        validateGradeRequest(
                                                                            {
                                                                                id: termRecord.id,
                                                                                type: 'reject',
                                                                            },
                                                                        )
                                                                    "
                                                                    severity="danger"
                                                                    rounded
                                                                    size="small"
                                                                    class-name="!px-5"
                                                                />
                                                            </div>
                                                        </div>
                                                    </Popover>
                                                </div>

                                                <DefaultButton
                                                    @click="
                                                        validateGradeRequest({
                                                            id: termRecord.id,
                                                            type: 'accept',
                                                        })
                                                    "
                                                    label="Approve All"
                                                    :icon="
                                                        TablerIcons.IconCheck
                                                    "
                                                    :loading="
                                                        loading.validateGrade
                                                    "
                                                    :disabled="
                                                        loading.validateGrade
                                                    "
                                                    severity="success"
                                                    :icon-size="15"
                                                    rounded
                                                    size="small"
                                                    class-name="!px-5"
                                                />
                                            </div>
                                        </div>
                                    </template>
                                </Panel>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </Drawer>
</template>
<script setup>
import {
    IconCopy,
    IconBook2,
    IconId,
    IconUserFilled,
    IconAt,
    IconMapPin,
    IconUser,
    IconSchool,
    IconBuildingEstate,
    IconCalendar,
    IconUserQuestion,
    IconScript,
    IconExclamationCircleFilled,
    IconHistory,
} from "@tabler/icons-vue";

import { ref, watch } from "vue";
import { useToast } from "primevue";
import * as TablerIcons from "@tabler/icons-vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import TextInput from "../../Components/inputs/TextInput.vue";
import SelectInput from "../../Components/inputs/SelectInput.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import DatePickerInput from "../../Components/inputs/DatePickerInput.vue";
import AutoCompleteInput from "../../Components/inputs/AutoCompleteInput.vue";

const toast = useToast();
const page = usePage();
const opTransfer = ref(null);
const transferTab = ref("school");
const modelValue = defineModel("modelValue");
const selectedTab = ref({
    label: "Personal Records",
    icon: "IconUserQuestion",
    key: 1,
});
const opRequest = ref([]);
const opGradeRequest = ref([]);

const transferInfo = useForm({
    school: null,
    course: null,
});

const createBtn = ref({
    tr: false,
    stipend: false,
});

const editBtn = ref({
    info: false,
    tr: false,
    stipend: false,
    activity: false,
});
const loading = ref({
    address: false,
    storePersonalInfo: false,
    validateReject: false,
    validateGrade: false,
    course: false,
    transferCourse: false,
    transferSubmit: false,
});


const personalInfo = useForm({
    schoolId: null,
    last_name: null,
    first_name: null,
    middle_name: null,
    suffix: null,
    email: null,
    contact_no: null,
    birth_date: null,
    formatBD: null,
    birth_place: null,
    religion: null,
    acc_name: null,
    acc_no: null,
    civil_status: null,
    address: null,
    fulladdress: null,
    school: null,
    course: null,
    program: null,
    sub_program: null,
    award_year: null,
    status: null,
    guardian_name: null,
    guardian_id_no: null,
    guardian_place_issue: null,
    guardian_date_issue: null,
});
const validateRequestForm = useForm({
    reason: null,
});

const validateGradeForm = useForm({
    reason: null,
});

const tabs = ref([
    { label: "Personal Records", icon: "IconUserQuestion", key: 1 },
    {
        separator: true,
    },
    {
        label: "Academic Records",
        icon: "IconScript",
        key: 2,
    },
    {
        separator: true,
    },
    {
        label: "Stipend",
        icon: "IconCoins",
        key: 3,
        disabled: true,
        status: "Ongoing",
    },
    {
        separator: true,
    },
    {
        label: "Activity Logs",
        icon: "IconWood",
        key: 4,
        disabled: true,
        status: "Ongoing",
    },
]);


const changeMenu = (item) => {
    selectedTab.value = item;
};

const autoSearch = (event) => {
    loading.value.address = true;
    router.get(
        route("scholars"),
        { findAddress: event },
        {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ["resultSearch"],
            onFinish: () => {
                loading.value.address = false;
            },
        },
    );
};

const toggle = (type, event) => {
    console.log(event);
    if (type === "transfer") {
        opTransfer.value.toggle(event);
    }
};

const opToggle = (event, index) => {
    opRequest.value[index]?.toggle(event);
};

const opGradeToggle = (event, index) => {
    opGradeRequest.value[index]?.toggle(event);
};

const validateRequest = (data) => {
    loading.value.validateReject = true;
    validateRequestForm.post(
        route("scholars.validate", {
            id: data.id,
            type: data.type,
        }),
        {
            onSuccess: () => {
                toast.add({
                    severity: page.props.flash?.status,
                    summary: page.props.flash?.title,
                    detail: page.props.flash?.message,
                    life: 3000,
                });
            },
            onError: (errors) => {
                toast.add({
                    severity: "error",
                    summary: "Error",
                    detail: "Failed to save grade record.",
                    life: 3000,
                });
            },
            onFinish: () => (loading.value.validateReject = false),
        },
    );
};

const validateGradeRequest = (data) => {
    loading.value.validateGrade = true;

    if (data.type === "accept") {
        // For approval, send direct request without form validation
        router.post(
            route("scholars.gradeValidate", {
                id: data.id,
                type: data.type,
            }),
            {},
            {
                onSuccess: () => {
                    toast.add({
                        severity: page.props.flash?.status,
                        summary: page.props.flash?.title,
                        detail: page.props.flash?.message,
                        life: 3000,
                    });
                },
                onError: (errors) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: errors.message || "Failed to approve grades.",
                        life: 3000,
                    });
                },
                onFinish: () => (loading.value.validateGrade = false),
            },
        );
    } else {
        // For rejection, use form with reason validation
        validateGradeForm.post(
            route("scholars.gradeValidate", {
                id: data.id,
                type: data.type,
            }),
            {
                onSuccess: () => {
                    toast.add({
                        severity: page.props.flash?.status,
                        summary: page.props.flash?.title,
                        detail: page.props.flash?.message,
                        life: 3000,
                    });
                },
                onError: (errors) => {
                    toast.add({
                        severity: "error",
                        summary: "Error",
                        detail: "Failed to reject grades. Please ensure you've provided remarks.",
                        life: 3000,
                    });
                },
                onFinish: () => (loading.value.validateGrade = false),
            },
        );
    }
};


const storePersonalInfo = async () => {
    loading.value.storePersonalInfo = true;

    personalInfo.post(
        route("scholars.update", {
            id: page.props?.details.id,
            type: "personal",
        }),
        {
            onSuccess: () => {
                editBtn.value.info = false;
                toast.add({
                    severity: "success",
                    summary: "Success",
                    detail: "Personal information updated successfully.",
                    life: 3000,
                });
            },
            onError: (errors) => {
                toast.add({
                    severity: "error",
                    summary: "Error",
                    detail: "Failed to update personal information.",
                    life: 3000,
                });
            },
            onFinish: () => {
                loading.value.storePersonalInfo = false;
            },
        },
    );
};


watch(
    () => page.props?.details,
    (newVal) => {
        if (!newVal) return;
        personalInfo.last_name = newVal.lname ?? null;
        personalInfo.first_name = newVal.fname ?? null;
        personalInfo.middle_name = newVal.mname ?? null;
        personalInfo.suffix = newVal.suffix ?? null;
        personalInfo.email = newVal.email ?? null;
        personalInfo.contact_no = newVal.contact_no ?? null;
        personalInfo.birth_date = newVal.birthdate
            ? new Date(newVal.birthdate)
            : null;
        personalInfo.birth_place = newVal.birthplace ?? null;
        personalInfo.religion = newVal.religion ?? null;
        personalInfo.civil_status = newVal.civil_status ?? null;
        personalInfo.address = newVal.address?.address ?? null;
        personalInfo.fulladdress = newVal.fullAddress ?? null;
        personalInfo.program = newVal.program ?? null;
        personalInfo.sub_program = newVal.type ?? null;
        personalInfo.award_year = newVal.awardYear
            ? new Date(parseInt(newVal.awardYear), 0, 1)
            : null;
        personalInfo.status = newVal.status ?? null;
        personalInfo.school = newVal.schoolInput ?? null;
        personalInfo.course = newVal.courseInput ?? null;
        ((personalInfo.schoolId = newVal.schoolInfoId ?? null),
            (personalInfo.guardian_name = newVal.guardian?.name ?? null));
        personalInfo.guardian_id_no = newVal.guardian?.id_no ?? null;
        personalInfo.guardian_place_issue =
            newVal.guardian?.place_issue ?? null;
        personalInfo.guardian_date_issue = newVal.guardian?.date_issue ?? null;
    },
    { immediate: true },
);

const renderCourse = (event) => {
    router.reload({
        only: ["courseOptions"],
        data: { campus: event.name },
        preserveState: true,
        preserveScroll: true,
        showProgress: true,
    });
};

const rendertCourse = (event) => {
    loading.value.transferCourse = true;
    router.reload({
        only: ["transferCourseOptions"],
        data: { tcampus: event.name },
        preserveState: true,
        preserveScroll: true,
        showProgress: true,
        onFinish: () => {
            loading.value.transferCourse = false;
        },
    });
};

const transferSubmit = () => {
    loading.value.transferSubmit = true;
    transferInfo.post(
        route("scholars.transfer", {
            id: page.props?.details.id,
            type: transferTab.value,
        }),
        {
            onSuccess: () => {
                toast.add({
                    severity: page.props.flash?.status,
                    summary: page.props.flash?.title,
                    detail: page.props.flash?.message,
                    life: 3000,
                });
                if (page.props.flash?.status == "success") {
                    transferInfo.value.school = null;
                    transferInfo.value.course = null;
                    opTransfer.value.hide();
                }
            },
            onError: (errors) => {
                toast.add({
                    severity: "error",
                    summary: "Error",
                    detail: "Failed to transfer course.",
                    life: 3000,
                });
            },
            onFinish: () => {
                loading.value.transferSubmit = false;
            },
        },
    );
};

const EditMode = () => {
    editBtn.value.info = true;
};

const cancelEdit = () => {
    editBtn.value.info = false;

    router.reload({
        only: ["courseOptions"],
        data: { campus: page.props?.details.schoolInput.name },
        preserveState: true,
        preserveScroll: true,
        showProgress: true,
        onFinish: () => {
            const url = new URL(window.location.href);
            url.searchParams.delete("campus");
            window.history.replaceState({}, "", url);
            personalInfo.school = page.props?.details.schoolInput ?? null;
            personalInfo.course = page.props?.details.courseInput ?? null;
        },
    });
};

</script>
