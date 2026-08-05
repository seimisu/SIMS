<template>
    <Drawer
        v-model:visible="modelValue"
        position="full"
        :pt="{
            header: 'border-b-1 border-gray-300 border-dashed dark:!border-gray-700 dark:!bg-gray-900 dark:!text-gray-100',
            content: 'bg-slate-50 dark:!bg-gray-900 dark:!text-gray-100',
        }"
    >
        <template #header>
            <div
                class="bg-slate-100 px-4 py-2 shadow rounded-lg flex items-center gap-2 dark:bg-gray-800 dark:text-gray-100"
            >
                <IconId :size="20" :stroke-width="2" />
                <div class="uppercase font-medium">Scholar Details</div>
            </div>
        </template>
        <template #default>
            <div class="flex flex-col lg:flex-row w-full my-5 gap-3 h-full">
                <div class="lg:flex-2 flex flex-col bg-white p-2 rounded-2xl dark:bg-gray-800 dark:text-gray-100">
                    <div class="flex-1">
                        <div class="flex gap-2 items-center">
                            <div class="">
                                <Avatar
                                    v-if="
                                        page.props?.details?.profile?.photo ==
                                        null
                                    "
                                    class="!w-[9rem] !h-[9rem] !rounded-xl !text-5xl !bg-slate-300 dark:!bg-gray-700 dark:!text-gray-200"
                                >
                                    <IconUserFilled :size="80" />
                                </Avatar>

                                <Avatar
                                    v-else
                                    :image="page.props.details?.profile?.photo"
                                    class="!w-[9rem] !h-[9rem] !bg-white shadow p-1 !rounded-xl dark:!bg-gray-700"
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
                                                class="font-medium text-gray-600 text-sm uppercase dark:text-gray-100"
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
                                                class="font-medium text-gray-600 text-sm dark:text-gray-100"
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
                                class="bg-slate-50 flex gap-7 rounded-2xl py-2 px-10 dark:bg-gray-900 dark:text-gray-100"
                            >
                                <div
                                    class="flex flex-col items-center justify-center"
                                >
                                    <div>
                                        {{ page.props?.details?.type.name }}
                                    </div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500">
                                        Type
                                    </div>
                                </div>
                                <div
                                    class="flex flex-col items-center justify-center"
                                >
                                    <div>
                                        {{ page.props?.details?.program.name }}
                                    </div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500">
                                        Program
                                    </div>
                                </div>
                                <div
                                    class="flex flex-col items-center justify-center"
                                >
                                    <div>
                                        {{ page.props?.details?.awardYear }}
                                    </div>
                                    <div class="text-xs text-gray-400 dark:text-gray-500">
                                        Award Year
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="mt-5 flex flex-col gap-3">
                            <div class="flex items-center gap-2">
                                <Avatar
                                    class="rounded-full border !bg-blue-50 !text-blue-500 dark:!border-blue-800 dark:!bg-blue-950/50 dark:!text-blue-300"
                                    size="small"
                                >
                                    <IconMapPin :size="20" stroke-width="2" />
                                </Avatar>
                                <div class="text-sm">
                                    {{ scholarLocationDisplay }}
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <Avatar
                                    class="rounded-full border !bg-blue-50 !text-blue-500 dark:!border-blue-800 dark:!bg-blue-950/50 dark:!text-blue-300"
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
                                    class="rounded-full border !bg-blue-50 !text-blue-500 dark:!border-blue-800 dark:!bg-blue-950/50 dark:!text-blue-300"
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
                                        academicStatusDisplay.bcolor,
                                        academicStatusDisplay.tcolor,
                                    ]"
                                >
                                    <component
                                        :is="
                                            TablerIcons[
                                                academicStatusDisplay.icon
                                            ]
                                        "
                                        :size="20"
                                        :stroke="2"
                                    />
                                </div>
                                <div class="text-sm uppercase">
                                    {{ academicStatusDisplay.name }}
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
                        <Menu
                            :model="tabs"
                            :pt="{
                                root: 'dark:!bg-gray-800 dark:!border-gray-700 dark:!text-gray-100',
                                submenuLabel: 'dark:!text-gray-400',
                            }"
                        >
                            <template #item="{ item, props }">
                                <a
                                    v-ripple
                                    :class="[
                                        'flex items-center gap-2 px-3 py-2 cursor-pointer',
                                        selectedTab.key == item.key
                                            ? 'text-blue-600 dark:text-blue-300'
                                            : 'dark:text-gray-200',
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
                            header: '!border-b-1 !border-gray-300 !border-dashed !p-0 dark:!border-gray-700 dark:!bg-gray-800 dark:!text-gray-100',
                            content: 'dark:!bg-gray-800 dark:!text-gray-100',
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
                                    <!-- <div>
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
                                    </div> -->

                                    <!-- <DefaultButton
                                        :icon="TablerIcons.IconCreditCard"
                                        @click="storePersonalInfo"
                                        outlined
                                        severity="secondary"
                                        label="View Landbank Details"
                                        size="small"
                                        rounded
                                        class-name="!rounded-xl !px-5"
                                    />
                                    <Divider layout="vertical" /> -->
                                    <DefaultButton
                                        :icon="TablerIcons.IconUserEdit"
                                        label="Edit Details"
                                        size="small"
                                        v-if="canUpdateScholars && !editBtn.info"
                                        @click="EditMode"
                                        raised
                                        class-name="!rounded-xl !px-5"
                                    />
                                    <div
                                        class="flex items-center gap-2"
                                        v-else-if="canUpdateScholars && editBtn.info"
                                    >
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
                            <div class="w-full flex flex-col pt-2 gap-4">
                                <section class="flex flex-col gap-2">
                                    <h3
                                        class="text-xs font-semibold uppercase text-slate-500"
                                    >
                                        Personal Information
                                    </h3>
                                    <div
                                        class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-2"
                                    >
                                        <TextInput
                                            v-model="personalInfo.first_name"
                                            label="First Name"
                                            :disabled="!editBtn.info"
                                            uppercase
                                        />
                                        <TextInput
                                            v-model="personalInfo.middle_name"
                                            label="Middle Name"
                                            :disabled="!editBtn.info"
                                            uppercase
                                        />
                                        <TextInput
                                            v-model="personalInfo.last_name"
                                            label="Last Name"
                                            :disabled="!editBtn.info"
                                            uppercase
                                        />
                                        <TextInput
                                            v-model="personalInfo.suffix"
                                            label="Suffix"
                                            :disabled="!editBtn.info"
                                            uppercase
                                        />
                                        <TextInput
                                            v-model="personalInfo.email"
                                            label="Email"
                                            :disabled="!editBtn.info"
                                        />
                                        <TextInput
                                            v-model="personalInfo.contact_no"
                                            label="Contact No"
                                            :disabled="!editBtn.info"
                                        />
                                        <DatePickerInput
                                            v-model="personalInfo.birth_date"
                                            label="Birth Date"
                                            :disabled="!editBtn.info"
                                        />
                                        <TextInput
                                            v-model="personalInfo.birth_place"
                                            label="Birth Place"
                                            :disabled="!editBtn.info"
                                        />
                                        <TextInput
                                            v-model="personalInfo.religion"
                                            label="Religion"
                                            uppercase
                                            :disabled="!editBtn.info"
                                        />
                                        <TextInput
                                            v-model="personalInfo.civil_status"
                                            uppercase
                                            label="Civil Status"
                                            :disabled="!editBtn.info"
                                        />
                                    </div>
                                </section>

                                <section
                                    class="flex flex-col gap-2 border-t border-slate-200 pt-3"
                                >
                                    <h3
                                        class="text-xs font-semibold uppercase text-slate-500"
                                    >
                                        School Information
                                    </h3>
                                    <div
                                        class="grid grid-cols-1 lg:grid-cols-2 gap-2"
                                    >
                                        <SelectInput
                                            label="School"
                                            v-model="personalInfo.school"
                                            :disable="!editBtn.info"
                                            @update:model-value="renderCourse"
                                            :options="page.props?.schoolOptions"
                                        />
                                        <SelectInput
                                            label="Course"
                                            v-model="personalInfo.course"
                                            uppercase
                                            :disable="!editBtn.info"
                                            :options="page.props?.courseOptions"
                                        />
                                    </div>
                                </section>

                                <section
                                    class="flex flex-col gap-2 border-t border-slate-200 pt-3"
                                >
                                    <h3
                                        class="text-xs font-semibold uppercase text-slate-500"
                                    >
                                        Scholarship Information
                                    </h3>
                                    <div
                                        class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-4 gap-2"
                                    >
                                        <SelectInput
                                            label="Scholar Program"
                                            v-model="personalInfo.program"
                                            :disable="!editBtn.info"
                                            :options="
                                                page.props?.programOptions
                                            "
                                        />
                                        <SelectInput
                                            label="Type"
                                            v-model="personalInfo.sub_program"
                                            :disable="!editBtn.info"
                                            :options="
                                                page.props?.subProgramOptions
                                            "
                                        />
                                        <DatePickerInput
                                            v-model="personalInfo.award_year"
                                            label="Award Year"
                                            view="year"
                                            :disabled="!editBtn.info"
                                            format-date="yy"
                                        />
                                        <SelectInput
                                            label="Progress Status"
                                            v-model="personalInfo.status"
                                            :disable="!editBtn.info"
                                            class="capitalize"
                                            :options="page.props?.statusOptions"
                                        />
                                    </div>
                                </section>

                                <section
                                    class="flex flex-col gap-2 border-t border-slate-200 pt-3"
                                >
                                    <h3
                                        class="text-xs font-semibold uppercase text-slate-500"
                                    >
                                        Address
                                    </h3>
                                    <TextInput
                                        v-model="personalInfo.address"
                                        label="Street Address"
                                        :disabled="!editBtn.info"
                                        placeholder="Street, Subdivision, etc."
                                    />
                                    <AutoCompleteInput
                                        v-model="personalInfo.fulladdress"
                                        label="Barangay / Municipality / Province / Region"
                                        :options="page.props?.resultSearch"
                                        :disabled="!editBtn.info"
                                        :loading="loading.address"
                                        placeholder="Find by Barangay, Municipality, Province, or Region"
                                        @complete="autoSearch"
                                        selection
                                    />
                                </section>

                                <section
                                    class="flex flex-col gap-2 border-t border-slate-200 pt-3"
                                >
                                    <h3
                                        class="text-xs font-semibold uppercase text-slate-500"
                                    >
                                        Other Information
                                    </h3>
                                    <div
                                        class="grid grid-cols-1 lg:grid-cols-2 gap-2"
                                    >
                                        <TextInput
                                            v-model="personalInfo.acc_name"
                                            label="Landbank Account Name"
                                            capitalize
                                            :disabled="!editBtn.info"
                                        />
                                        <TextInput
                                            v-model="personalInfo.acc_no"
                                            capitalize
                                            label="Landbank Account Number"
                                            :disabled="!editBtn.info"
                                        />
                                    </div>
                                </section>

                                <Panel
                                    toggleable
                                    collapsed
                                    class="!rounded-xl"
                                    :pt="{
                                        root: '!border-slate-200 dark:!border-gray-700 dark:!bg-gray-900',
                                        header: '!border-b !border-slate-200 !border-dashed !p-3 dark:!border-gray-700 dark:!bg-gray-900 dark:!text-gray-100',
                                        content: '!p-0 dark:!bg-gray-900 dark:!text-gray-100',
                                    }"
                                >
                                    <template #header>
                                        <div
                                            class="flex w-full items-center justify-between gap-3"
                                        >
                                            <div
                                                class="flex items-center gap-2"
                                            >
                                                <Avatar
                                                    class="rounded-full !bg-blue-100 !text-blue-500 dark:!bg-blue-950/60 dark:!text-blue-300"
                                                    size="small"
                                                >
                                                    <IconWood
                                                        :size="18"
                                                        stroke-width="2"
                                                    />
                                                </Avatar>
                                                <div>
                                                    <h3
                                                        class="text-sm font-semibold text-gray-800 dark:text-gray-100"
                                                    >
                                                        Activity Logs
                                                    </h3>
                                                    <p
                                                        class="text-xs text-gray-500 dark:text-gray-400"
                                                    >
                                                        Personal record edit
                                                        history
                                                    </p>
                                                </div>
                                            </div>
                                            <Badge
                                                size="small"
                                                severity="info"
                                                :value="`${page.props.details.logs.length} record(s)`"
                                            />
                                        </div>
                                    </template>
                                    <template #default>
                                        <div
                                            v-if="
                                                page.props.details.logs.length
                                            "
                                            class="max-h-[22rem] overflow-y-auto p-4"
                                        >
                                            <Timeline
                                                :value="page.props.details.logs"
                                                align="left"
                                                class="p-1"
                                                :pt="{
                                                    eventOpposite: '!hidden',
                                                    eventSeparator:
                                                        '!min-w-[3rem]',
                                                }"
                                            >
                                                <template
                                                    #marker="slotProps"
                                                >
                                                    <div
                                                        :class="[
                                                            'w-10 h-10 rounded-2xl border flex items-center justify-center shadow-sm dark:bg-gray-800',
                                                            {
                                                                'bg-blue-50 border-blue-200 dark:border-blue-800':
                                                                    slotProps
                                                                        .item
                                                                        .type ===
                                                                    'profile',
                                                                'bg-emerald-50 border-emerald-200 dark:border-emerald-800':
                                                                    slotProps
                                                                        .item
                                                                        .type ===
                                                                    'landbank',
                                                                'bg-cyan-50 border-cyan-200 dark:border-cyan-800':
                                                                    slotProps
                                                                        .item
                                                                        .type ===
                                                                    'address',
                                                                'bg-green-50 border-green-200 dark:border-green-800':
                                                                    slotProps
                                                                        .item
                                                                        .type ===
                                                                    'school',
                                                            },
                                                        ]"
                                                    >
                                                        <IconMapPin
                                                            v-if="
                                                                slotProps.item
                                                                    .type ===
                                                                'address'
                                                            "
                                                            class="text-cyan-600 dark:text-cyan-300"
                                                            :size="22"
                                                        />

                                                        <IconUser
                                                            v-else-if="
                                                                slotProps.item
                                                                    .type ===
                                                                'profile'
                                                            "
                                                            class="text-blue-600 dark:text-blue-300"
                                                            :size="22"
                                                        />

                                                        <IconBuildingBank
                                                            v-else-if="
                                                                slotProps.item
                                                                    .type ===
                                                                'landbank'
                                                            "
                                                            class="text-emerald-600 dark:text-emerald-300"
                                                            :size="22"
                                                        />

                                                        <IconSchool
                                                            v-else
                                                            class="text-green-600 dark:text-green-300"
                                                            :size="22"
                                                        />
                                                    </div>
                                                </template>

                                                <template
                                                    #content="slotProps"
                                                >
                                                    <div
                                                        class="flex flex-col gap-2 rounded-xl border border-slate-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800"
                                                    >
                                                        <div
                                                            class="flex flex-col"
                                                        >
                                                            <div
                                                                class="text-sm font-medium text-gray-800 dark:text-gray-100"
                                                            >
                                                                Updated User
                                                                {{
                                                                    slotProps
                                                                        .item
                                                                        .type
                                                                }}
                                                            </div>
                                                            <div
                                                                class="text-sm flex gap-4 items-center text-gray-400 dark:text-gray-500"
                                                            >
                                                                <div
                                                                    class="flex gap-1 items-center"
                                                                >
                                                                    <IconUserCircle
                                                                        :size="
                                                                            20
                                                                        "
                                                                    />
                                                                    <div>
                                                                        {{
                                                                            slotProps
                                                                                .item
                                                                                .created_by
                                                                        }}
                                                                    </div>
                                                                </div>
                                                                <div
                                                                    class="flex gap-1 items-center"
                                                                >
                                                                    <IconCalendarFilled
                                                                        :size="
                                                                            20
                                                                        "
                                                                    />
                                                                    <div>
                                                                        {{
                                                                            slotProps
                                                                                .item
                                                                                .date
                                                                        }}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </div>
                                                        <div
                                                            class="text-sm"
                                                        >
                                                            <div
                                                                v-for="(
                                                                    value, key
                                                                ) in slotProps
                                                                    .item
                                                                    .changes"
                                                                :key="key"
                                                                class="flex flex-wrap items-center gap-2 py-0.5"
                                                            >
                                                                <span
                                                                    class="min-w-36 capitalize text-gray-700 dark:text-gray-300"
                                                                >
                                                                    {{
                                                                        key.replaceAll(
                                                                            "_",
                                                                            " ",
                                                                        )
                                                                    }}
                                                                </span>

                                                                <span
                                                                    class="text-red-500 dark:text-red-300"
                                                                >
                                                                    {{
                                                                        slotProps
                                                                            .item
                                                                            .previous?.[
                                                                            key
                                                                        ] != ""
                                                                            ? slotProps
                                                                                  .item
                                                                                  .previous?.[
                                                                                  key
                                                                              ]
                                                                            : "Not Set"
                                                                    }}
                                                                </span>

                                                                <IconArrowRight
                                                                    :size="14"
                                                                    class="text-gray-400 dark:text-gray-500"
                                                                />

                                                                <span
                                                                    class="font-medium text-emerald-600 dark:text-emerald-300"
                                                                >
                                                                    {{
                                                                        value !=
                                                                        ""
                                                                            ? value
                                                                            : "Removed"
                                                                    }}
                                                                </span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </template>
                                            </Timeline>
                                        </div>
                                        <div
                                            v-else
                                            class="p-6 text-center text-sm text-gray-500 dark:text-gray-400"
                                        >
                                            No personal record activity yet.
                                        </div>
                                    </template>
                                </Panel>

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
                            class="rounded-2xl border border-slate-200 bg-slate-50 p-3 dark:border-gray-700 dark:bg-gray-800"
                            v-if="page.props?.details?.termGrades?.length > 0"
                        >
                            <div
                                class="mb-3 flex flex-col gap-1 rounded-xl bg-white p-3 text-gray-700 shadow-sm dark:bg-gray-900 dark:text-gray-100"
                            >
                                <div class="flex items-center gap-2">
                                    <IconHistory :size="18" />
                                    <span class="text-sm font-semibold">
                                        Academic Records
                                    </span>
                                </div>
                                <p class="text-xs text-gray-500 dark:text-gray-400">
                                    Records are arranged from newest to oldest.
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
                                        header: '!border-b-1 !border-gray-300 !border-dashed !p-0 dark:!border-gray-700 dark:!bg-gray-900 dark:!text-gray-100',
                                        content: 'dark:!bg-gray-900 dark:!text-gray-100',
                                    }"
                                >
                                    <template #header>
                                        <div
                                            class="p-3 w-full flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between"
                                        >
                                            <div
                                                class="flex items-center gap-3 text-md font-medium text-gray-700 dark:text-gray-100"
                                            >
                                                <IconSchool
                                                    class="text-gray-500 dark:text-gray-400"
                                                    :size="20"
                                                />
                                                <span
                                                    class="px-2 py-0.5 rounded-md bg-slate-200 dark:bg-gray-700 dark:text-gray-100"
                                                >
                                                    {{
                                                        termRecord.academic_year
                                                    }}
                                                </span>
                                                <span
                                                    class="px-2 py-0.5 rounded-md bg-gray-100 dark:bg-gray-700 dark:text-gray-100"
                                                >
                                                    {{
                                                        termRecord.termType ??
                                                        "Term"
                                                    }}
                                                </span>
                                            </div>
                                            <div
                                                class="flex flex-wrap items-center gap-2 lg:justify-end"
                                            >
                                                <DefaultButton
                                                    v-if="canUpdateScholars && !editingAcademicRecord(termRecord)"
                                                    :icon="TablerIcons.IconScript"
                                                    label="Edit Records"
                                                    size="small"
                                                    raised
                                                    class-name="!rounded-xl !px-4"
                                                    @click="editAcademicRecord(termRecord)"
                                                />
                                                <div
                                                    v-else-if="canUpdateScholars && editingAcademicRecord(termRecord)"
                                                    class="flex items-center gap-2"
                                                >
                                                    <DefaultButton
                                                        :icon="TablerIcons.IconScriptX"
                                                        label="Cancel"
                                                        size="small"
                                                        severity="danger"
                                                        class-name="!rounded-xl !px-4"
                                                        @click="cancelAcademicRecordEdit"
                                                    />
                                                    <DefaultButton
                                                        :icon="TablerIcons.IconDeviceFloppy"
                                                        label="Save"
                                                        size="small"
                                                        raised
                                                        :loading="academicRecordForm.processing"
                                                        :disabled="academicRecordForm.processing"
                                                        class-name="!rounded-xl !px-4"
                                                        @click="confirmAcademicRecordSave"
                                                    />
                                                </div>
                                                <span
                                                    class="text-xs font-semibold uppercase text-gray-400 dark:text-gray-500"
                                                >
                                                    Documents
                                                </span>
                                                <div
                                                    class="flex items-center gap-1"
                                                    v-if="termRecord.files"
                                                >
                                                    <template
                                                        v-for="(
                                                            file, index
                                                        ) in termRecord.files"
                                                        :key="file.id ?? `file-${index}`"
                                                    >
                                                        <a
                                                            v-if="
                                                                file.document_type ==
                                                                'cor'
                                                            "
                                                            :href="
                                                                file.file_path
                                                            "
                                                            target="_blank"
                                                            class="rounded-lg bg-blue-50 px-2 py-1 text-xs font-medium text-blue-600 hover:bg-blue-100 dark:bg-blue-950/50 dark:text-blue-200 dark:hover:bg-blue-900/70"
                                                        >
                                                            COR:
                                                            {{ file.file_name }}
                                                        </a>
                                                        <a
                                                            v-if="
                                                                file.document_type ==
                                                                'grades_proof'
                                                            "
                                                            :href="
                                                                file.file_path
                                                            "
                                                            target="_blank"
                                                            class="rounded-lg bg-blue-50 px-2 py-1 text-xs font-medium text-blue-600 hover:bg-blue-100 dark:bg-blue-950/50 dark:text-blue-200 dark:hover:bg-blue-900/70"
                                                        >
                                                            Proof of Grades:
                                                            {{ file.file_name }}
                                                        </a>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    <template #default>
                                        <div
                                            class="w-full flex flex-col pt-5 gap-3"
                                        >
                                            <div
                                                class="flex flex-col gap-3 rounded-xl bg-slate-50 px-3 py-2 sm:flex-row sm:items-center sm:justify-between dark:bg-gray-800"
                                            >
                                                <div
                                                    v-if="!editingAcademicRecord(termRecord)"
                                                    class="flex flex-col"
                                                >
                                                    <div
                                                        class="text-xs text-gray-500 dark:text-gray-400"
                                                    >
                                                        {{
                                                            page.props?.details
                                                                ?.school
                                                        }}
                                                    </div>
                                                    <div
                                                        class="text-base font-semibold text-gray-800 dark:text-gray-100"
                                                    >
                                                        {{
                                                            page.props?.details
                                                                ?.course
                                                        }}
                                                    </div>
                                                </div>
                                                <div
                                                    v-else
                                                    class="grid flex-1 gap-3 md:grid-cols-2 xl:grid-cols-3"
                                                >
                                                    <SelectInput
                                                        v-model="academicRecordForm.school"
                                                        label="School"
                                                        :options="page.props?.schoolOptions ?? []"
                                                        filter
                                                        @change="renderAcademicCourse"
                                                    />
                                                    <SelectInput
                                                        v-model="academicRecordForm.course"
                                                        label="Course"
                                                        :options="page.props?.courseOptions ?? []"
                                                        filter
                                                    />
                                                    <SelectInput
                                                        v-model="academicRecordForm.level"
                                                        label="Year Level"
                                                        :options="page.props?.yearOptions ?? []"
                                                        filter
                                                    />
                                                    <SelectInput
                                                        v-model="academicRecordForm.term"
                                                        label="Semester"
                                                        :options="page.props?.termOptions ?? []"
                                                        filter
                                                    />
                                                    <TextInput
                                                        v-model="academicRecordForm.academic_year"
                                                        label="Academic Year"
                                                    />
                                                    <SelectInput
                                                        v-model="academicRecordForm.scholarship_status"
                                                        label="Scholarship Status"
                                                        :options="page.props?.standingOptions ?? []"
                                                        filter
                                                    />
                                                </div>
                                                <div
                                                    v-if="!editingAcademicRecord(termRecord)"
                                                    class="rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-right dark:border-amber-800/70 dark:bg-amber-950/30"
                                                >
                                                    <div
                                                        class="text-[10px] font-semibold uppercase tracking-wide text-amber-600 dark:text-amber-300"
                                                    >
                                                        Scholarship Status
                                                    </div>
                                                    <div
                                                        class="text-sm font-semibold text-amber-800 dark:text-amber-100"
                                                    >
                                                        {{
                                                            termRecord.scholarshipStatus ??
                                                            "Scholarship status is not available"
                                                        }}
                                                    </div>
                                                </div>
                                            </div>
                                            <table
                                                class="min-w-full !border-none text-sm"
                                            >
                                                <thead>
                                                    <tr class="bg-gray-100 dark:bg-gray-800">
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
                                                            :class="[
                                                                'px-3 py-2',
                                                                editingAcademicRecord(termRecord) ? 'text-left' : 'text-right',
                                                            ]"
                                                        >
                                                            Unit
                                                        </th>
                                                        <th
                                                            :class="[
                                                                'px-3 py-2',
                                                                editingAcademicRecord(termRecord) ? 'text-left' : 'text-right',
                                                            ]"
                                                        >
                                                            Grades
                                                        </th>
                                                        <th
                                                            :class="[
                                                                'px-3 py-2',
                                                                editingAcademicRecord(termRecord) ? 'text-left' : 'text-right',
                                                            ]"
                                                        >
                                                            Total
                                                        </th>
                                                        <th
                                                            :class="[
                                                                'px-3 py-2',
                                                                editingAcademicRecord(termRecord) ? 'text-left' : 'text-center',
                                                                editingAcademicRecord(termRecord) ? '' : 'rounded-r-xl',
                                                            ]"
                                                        >
                                                            Remarks
                                                        </th>
                                                        <th
                                                            v-if="editingAcademicRecord(termRecord)"
                                                            class="w-12 rounded-r-xl px-3 py-2 text-left"
                                                        >
                                                        </th>
                                                    </tr>
                                                </thead>
                                                <tbody>
                                                    <tr
                                                        v-if="
                                                            !termRecord.subjects
                                                                ?.length
                                                        "
                                                    >
                                                        <td
                                                            :colspan="editingAcademicRecord(termRecord) ? 7 : 6"
                                                            class="px-3 py-6 text-center text-gray-500 dark:text-gray-400"
                                                        >
                                                            Approved academic
                                                            record found, but no
                                                            subjects are loaded
                                                            for this term yet.
                                                        </td>
                                                    </tr>
                                                    <tr
                                                        v-for="(
                                                            item, index
                                                        ) in academicRecordRows(termRecord)"
                                                        :key="index"
                                                        class="hover:bg-gray-50 dark:hover:bg-gray-800/80"
                                                    >
                                                        <template
                                                            v-if="editingAcademicRecord(termRecord)"
                                                        >
                                                            <td
                                                                class="px-3 py-2 min-w-72"
                                                            >
                                                                <SelectInput
                                                                    v-model="item.subject"
                                                                    :options="academicSubjectOptionsForRow(index)"
                                                                    filter
                                                                    uppercase
                                                                />
                                                            </td>
                                                            <td class="px-3 py-2 text-slate-600 dark:text-gray-300">
                                                                    {{
                                                                    item.subject?.code ??
                                                                    item.subject?.subject_code ??
                                                                    "-"
                                                                }}
                                                            </td>
                                                            <td
                                                                class="px-3 py-2 text-left text-slate-600 dark:text-gray-300"
                                                            >
                                                                    {{
                                                                    item.subject?.unit ??
                                                                    "-"
                                                                }}
                                                            </td>
                                                            <td
                                                                class="px-3 py-2 min-w-36"
                                                            >
                                                                <SelectInput
                                                                    v-model="item.grade"
                                                                    :options="academicGradeOptionsForRow(index)"
                                                                    filter
                                                                />
                                                            </td>
                                                            <td
                                                                class="px-3 py-2 text-left text-slate-600 dark:text-gray-300"
                                                            >
                                                                {{
                                                                    academicSubjectTotal(item) ??
                                                                    "-"
                                                                }}
                                                            </td>
                                                            <td
                                                                class="px-3 py-2 text-left"
                                                            >
                                                                <span
                                                                    :class="academicSubjectRemarkClass(item)"
                                                                >
                                                                    {{ academicSubjectRemark(item) }}
                                                                </span>
                                                            </td>
                                                            <td
                                                                class="px-3 py-2 text-left"
                                                            >
                                                                <DefaultButton
                                                                    :icon="TablerIcons.IconTrash"
                                                                    text
                                                                    severity="danger"
                                                                    size="small"
                                                                    shape="circle"
                                                                    @click="removeAcademicSubject(index)"
                                                                />
                                                            </td>
                                                        </template>
                                                        <template v-else>
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
                                                                class="text-slate-500 dark:text-gray-400"
                                                            >
                                                                Dropped
                                                            </div>
                                                            <div
                                                                v-else-if="
                                                                    item.request
                                                                        ?.is_failed
                                                                "
                                                                class="text-rose-600 dark:text-rose-300"
                                                            >
                                                                Failed
                                                            </div>
                                                            <div
                                                                v-else-if="
                                                                    item.request
                                                                        ?.is_incomplete
                                                                "
                                                                class="text-amber-600 dark:text-amber-300"
                                                            >
                                                                Incompleted
                                                            </div>
                                                            <div
                                                                v-else-if="
                                                                    item.request
                                                                        ?.grade
                                                                "
                                                                class="text-green-600 dark:text-green-300"
                                                            >
                                                                Passed
                                                            </div>
                                                            <div
                                                                v-else-if="
                                                                    item.grade
                                                                        ?.is_drop
                                                                "
                                                                class="text-slate-500 dark:text-gray-400"
                                                            >
                                                                Dropped
                                                            </div>
                                                            <div
                                                                v-else-if="
                                                                    item.grade
                                                                        ?.is_failed
                                                                "
                                                                class="text-rose-600 dark:text-rose-300"
                                                            >
                                                                Failed
                                                            </div>
                                                            <div
                                                                v-else-if="
                                                                    item.grade
                                                                        ?.is_incomplete
                                                                "
                                                                class="text-amber-600 dark:text-amber-300"
                                                            >
                                                                Incompleted
                                                            </div>
                                                            <div
                                                                v-else-if="
                                                                    item.grade
                                                                        ?.is_active
                                                                "
                                                                class="text-green-600 dark:text-green-300"
                                                            >
                                                                Passed
                                                            </div>
                                                        </td>
                                                        </template>
                                                    </tr>
                                                    <tr
                                                        v-if="editingAcademicRecord(termRecord)"
                                                    >
                                                        <td colspan="7" class="px-3 py-2">
                                                            <DefaultButton
                                                                :icon="TablerIcons.IconScriptPlus"
                                                                label="Add Subject"
                                                                severity="secondary"
                                                                size="small"
                                                                class-name="!rounded-xl w-full !px-5"
                                                                @click="addAcademicSubject"
                                                            />
                                                        </td>
                                                    </tr>
                                                    <tr
                                                        v-if="
                                                            termRecord.subjects
                                                                ?.length &&
                                                            !editingAcademicRecord(termRecord)
                                                        "
                                                        class="bg-blue-50 font-semibold text-gray-700 dark:bg-gray-800 dark:text-gray-100"
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
                                                                    ?.units ?? 0
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
                                                class="flex w-full justify-end gap-2 pt-4 border-t border-gray-200 dark:border-gray-700"
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
                            <Panel
                                toggleable
                                collapsed
                                class="!rounded-xl"
                                :pt="{
                                    root: '!border-slate-200 dark:!border-gray-700 dark:!bg-gray-900',
                                    header: '!border-b !border-slate-200 !border-dashed !p-3 dark:!border-gray-700 dark:!bg-gray-900 dark:!text-gray-100',
                                    content: '!p-0 dark:!bg-gray-900 dark:!text-gray-100',
                                }"
                            >
                                <template #header>
                                    <div
                                        class="flex w-full items-center justify-between gap-3"
                                    >
                                        <div class="flex items-center gap-2">
                                            <Avatar
                                                class="rounded-full !bg-blue-100 !text-blue-500 dark:!bg-blue-950/60 dark:!text-blue-300"
                                                size="small"
                                            >
                                                <IconHistory
                                                    :size="18"
                                                    stroke-width="2"
                                                />
                                            </Avatar>
                                            <div>
                                                <h3
                                                    class="text-sm font-semibold text-gray-800 dark:text-gray-100"
                                                >
                                                    Academic Record Logs
                                                </h3>
                                                <p
                                                    class="text-xs text-gray-500 dark:text-gray-400"
                                                >
                                                    Academic record edit
                                                    history
                                                </p>
                                            </div>
                                        </div>
                                        <Badge
                                            size="small"
                                            severity="info"
                                            :value="`${page.props.details.academicLogs?.length ?? 0} record(s)`"
                                        />
                                    </div>
                                </template>
                                <template #default>
                                    <div
                                        v-if="
                                            page.props.details.academicLogs
                                                ?.length
                                        "
                                        class="max-h-[22rem] overflow-y-auto p-4"
                                    >
                                        <Timeline
                                            :value="
                                                page.props.details.academicLogs
                                            "
                                            align="left"
                                            class="p-1"
                                            :pt="{
                                                eventOpposite: '!hidden',
                                                eventSeparator:
                                                    '!min-w-[3rem]',
                                            }"
                                        >
                                            <template #marker>
                                                <div
                                                    class="w-10 h-10 rounded-2xl border border-violet-200 bg-violet-50 flex items-center justify-center shadow-sm dark:border-violet-800 dark:bg-gray-800"
                                                >
                                                    <IconScript
                                                        class="text-violet-600 dark:text-violet-300"
                                                        :size="22"
                                                    />
                                                </div>
                                            </template>

                                            <template #content="slotProps">
                                                <div
                                                    class="flex flex-col gap-2 rounded-xl border border-slate-200 bg-white p-3 dark:border-gray-700 dark:bg-gray-800"
                                                >
                                                    <div class="flex flex-col">
                                                        <div
                                                            class="text-sm font-medium text-gray-800 dark:text-gray-100"
                                                        >
                                                            Updated Academic
                                                            Record
                                                        </div>
                                                        <div
                                                            class="text-sm flex gap-4 items-center text-gray-400 dark:text-gray-500"
                                                        >
                                                            <div
                                                                class="flex gap-1 items-center"
                                                            >
                                                                <IconUserCircle
                                                                    :size="20"
                                                                />
                                                                <div>
                                                                    {{
                                                                        slotProps
                                                                            .item
                                                                            .created_by
                                                                    }}
                                                                </div>
                                                            </div>
                                                            <div
                                                                class="flex gap-1 items-center"
                                                            >
                                                                <IconCalendarFilled
                                                                    :size="20"
                                                                />
                                                                <div>
                                                                    {{
                                                                        slotProps
                                                                            .item
                                                                            .date
                                                                    }}
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="text-sm">
                                                        <div
                                                            v-for="(
                                                                value, key
                                                            ) in slotProps.item
                                                                .changes"
                                                            :key="key"
                                                            class="flex flex-wrap items-start gap-2 py-0.5"
                                                        >
                                                            <span
                                                                class="min-w-40 capitalize text-gray-700 dark:text-gray-300"
                                                            >
                                                                {{
                                                                    key.replaceAll(
                                                                        "_",
                                                                        " ",
                                                                    )
                                                                }}
                                                            </span>

                                                            <span
                                                                class="max-w-xl whitespace-pre-line text-red-500 dark:text-red-300"
                                                            >
                                                                {{
                                                                    slotProps
                                                                        .item
                                                                        .previous?.[
                                                                        key
                                                                    ] != ""
                                                                        ? slotProps
                                                                              .item
                                                                              .previous?.[
                                                                              key
                                                                          ]
                                                                        : "Not Set"
                                                                }}
                                                            </span>

                                                            <IconArrowRight
                                                                :size="14"
                                                                class="mt-1 text-gray-400 dark:text-gray-500"
                                                            />

                                                            <span
                                                                class="max-w-xl whitespace-pre-line font-medium text-emerald-600 dark:text-emerald-300"
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
                                    </div>
                                    <div
                                        v-else
                                        class="p-6 text-center text-sm text-gray-500 dark:text-gray-400"
                                    >
                                        No academic record activity yet.
                                    </div>
                                </template>
                            </Panel>
                        </div>
                    </div>
                    <div
                        v-if="selectedTab.key === 3"
                        class="w-full flex flex-col gap-3"
                    >
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            <!-- Total Amount -->
                            <div
                                class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-all dark:bg-gray-800 dark:border-gray-700"
                            >
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-sm font-medium text-slate-500 dark:text-gray-300"
                                    >
                                        Total Financial Assistance Amount
                                    </span>

                                    <div
                                        class="w-10 h-10 rounded-xl bg-blue-100 flex items-center justify-center dark:bg-blue-500/15"
                                    >
                                        <i
                                            class="pi pi-wallet text-blue-600"
                                        ></i>
                                    </div>
                                </div>

                                <h2
                                    class="text-3xl font-bold text-slate-800 mt-4 dark:text-gray-50"
                                >
                                    ₱
                                    {{
                                        page.props?.details?.financialAid
                                            ?.grandTotal
                                    }}
                                </h2>

                                <p class="text-xs text-slate-400 mt-1 dark:text-gray-400">
                                    Total approved scholarship amount
                                </p>
                            </div>

                            <!-- Amount Received -->
                            <div
                                class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-all dark:bg-gray-800 dark:border-gray-700"
                            >
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-sm font-medium text-slate-500 dark:text-gray-300"
                                    >
                                        Total Financial Allowances
                                    </span>

                                    <div
                                        class="w-10 h-10 rounded-xl bg-green-100 flex items-center justify-center dark:bg-green-500/15"
                                    >
                                        <i
                                            class="pi pi-check-circle text-green-600"
                                        ></i>
                                    </div>
                                </div>

                                <h2
                                    class="text-3xl font-bold text-green-600 mt-4"
                                >
                                    ₱{{
                                        page.props?.details?.financialAid
                                            ?.approvedTotal
                                    }}
                                </h2>

                                <p class="text-xs text-slate-400 mt-1 dark:text-gray-400">
                                    Successfully received amount
                                </p>
                            </div>

                            <!-- Remaining Balance -->
                            <div
                                class="bg-white rounded-2xl border border-slate-200 p-5 shadow-sm hover:shadow-md transition-all dark:bg-gray-800 dark:border-gray-700"
                            >
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-sm font-medium text-slate-500 dark:text-gray-300"
                                    >
                                        Remaining Balance
                                    </span>

                                    <div
                                        class="w-10 h-10 rounded-xl bg-amber-100 flex items-center justify-center dark:bg-amber-500/15"
                                    >
                                        <i
                                            class="pi pi-chart-line text-amber-600"
                                        ></i>
                                    </div>
                                </div>

                                <h2
                                    class="text-3xl font-bold text-amber-600 mt-4"
                                >
                                    ₱{{
                                        page.props?.details?.financialAid
                                            ?.totalWithheld
                                    }}
                                </h2>

                                <p class="text-xs text-slate-400 mt-1 dark:text-gray-400">
                                    Outstanding scholarship balance
                                </p>
                            </div>
                        </div>
                        <Panel
                            class="w-full !rounded-xl"
                            :pt="{
                                header: '!border-b-1 !border-gray-300 !border-dashed !p-3 dark:!border-gray-700',
                                content: 'dark:!bg-gray-800',
                            }"
                        >
                            <template #header>
                                <div
                                    class="flex items-center justify-between gap-1"
                                >
                                    <h3
                                        class="text-sm font-semibold text-gray-800 dark:text-gray-100"
                                    >
                                        Semester Breakdown
                                    </h3>
                                </div>
                            </template>
                            <template #default>
                                <div
                                    class="grid grid-cols-1 xl:grid-cols-3 pt-3 gap-8"
                                >
                                    <div class="xl:col-span-2">
                                        <template
                                            v-for="(q, index) in page.props
                                                ?.details?.financialAid
                                                ?.monthly"
                                            :key="index"
                                        >
                                            <div
                                                class="flex items-center justify-between mt-5 mb-1"
                                            >
                                                <div>
                                                    <div
                                                        class="flex items-center gap-2"
                                                    >
                                                        <div
                                                            class="text-slate-700 text-sm font-semibold dark:text-gray-100"
                                                        >
                                                            {{ q.period }}
                                                        </div>
                                                        <span
                                                            v-if="index === 0"
                                                            class="text-xs bg-blue-100 px-4 rounded-2xl border border-blue-200 font-medium text-blue-600 py-0.5"
                                                            >Current
                                                            Semester</span
                                                        >
                                                    </div>
                                                    <p
                                                        class="text-xs text-slate-500 dark:text-gray-400"
                                                    >
                                                        Monthly Scholarship
                                                        Releases
                                                    </p>
                                                </div>

                                                <div class="text-right">
                                                    <div
                                                        class="text-xs text-slate-500 dark:text-gray-400"
                                                    >
                                                        Month Status
                                                    </div>
                                                    <div
                                                        class="text-lg itmes-center font-semibold capitalize flex gap-1 text-slate-800"
                                                    >
                                                        <div>
                                                            <Button
                                                                size="small"
                                                                text
                                                                class="!rounded-full !p-1"
                                                                severity="secondary"
                                                                @click="
                                                                    historyToggle(
                                                                        $event,
                                                                        index,
                                                                    )
                                                                "
                                                            >
                                                                <IconHistory
                                                                    :size="20"
                                                                />
                                                            </Button>

                                                            <Popover
                                                                :ref="
                                                                    (el) =>
                                                                        (opHistory[
                                                                            index
                                                                        ] = el)
                                                                "
                                                            >
                                                                <div
                                                                    class="max-w-70 w-full"
                                                                >
                                                                    <div
                                                                        class="flex items-center justify-between"
                                                                    >
                                                                        <span
                                                                            class="font-semibold"
                                                                            >Payroll
                                                                            Processing
                                                                            Timeline</span
                                                                        >
                                                                    </div>
                                                                    <p
                                                                        class="text-xs text-muted-color mt-2 mb-0!"
                                                                    >
                                                                        View the
                                                                        complete
                                                                        history
                                                                        of
                                                                        payroll
                                                                        processing,
                                                                        including
                                                                        submissions,
                                                                        approvals,
                                                                        rejections,
                                                                        remarks,
                                                                        and the
                                                                        users
                                                                        responsible
                                                                        for each
                                                                        action.
                                                                    </p>
                                                                    <Timeline
                                                                        :value="
                                                                            q.logs
                                                                        "
                                                                        class="mt-5"
                                                                        align="left"
                                                                        :pt="{
                                                                            eventOpposite:
                                                                                '!hidden',
                                                                        }"
                                                                    >
                                                                        <template
                                                                            #marker="slotProps"
                                                                        >
                                                                            <Avatar
                                                                                shape="circle"
                                                                                class="!bg-white border !border-slate-200 !shadow-sm dark:!bg-gray-900 dark:!border-gray-700"
                                                                            >
                                                                                <IconDotsCircleHorizontal
                                                                                    :size="
                                                                                        20
                                                                                    "
                                                                                    v-if="
                                                                                        slotProps
                                                                                            .item
                                                                                            .action ==
                                                                                        'draft'
                                                                                    "
                                                                                />
                                                                                <IconHelpCircle
                                                                                    :size="
                                                                                        20
                                                                                    "
                                                                                    class="text-yellow-600"
                                                                                    v-else-if="
                                                                                        slotProps
                                                                                            .item
                                                                                            .action ==
                                                                                        'submitted_payroll'
                                                                                    "
                                                                                />
                                                                                <IconCircleX
                                                                                    :size="
                                                                                        20
                                                                                    "
                                                                                    class="text-red-600"
                                                                                    v-else-if="
                                                                                        slotProps
                                                                                            .item
                                                                                            .action ==
                                                                                        'rejected_payroll'
                                                                                    "
                                                                                />
                                                                                <IconCircleCheck
                                                                                    :size="
                                                                                        20
                                                                                    "
                                                                                    class="text-green-600"
                                                                                    v-else
                                                                                />
                                                                            </Avatar>
                                                                        </template>
                                                                        <template
                                                                            #content="slotProps"
                                                                        >
                                                                            <div
                                                                                class="text-sm leading-4"
                                                                            >
                                                                                <div
                                                                                    class="text-sm font-semibold"
                                                                                    v-if="
                                                                                        slotProps
                                                                                            .item
                                                                                            .action ===
                                                                                        'draft'
                                                                                    "
                                                                                >
                                                                                    Payroll
                                                                                    Draft
                                                                                    Created
                                                                                </div>

                                                                                <div
                                                                                    class="text-sm font-semibold text-yellow-600"
                                                                                    v-else-if="
                                                                                        slotProps
                                                                                            .item
                                                                                            .action ===
                                                                                        'submitted_payroll'
                                                                                    "
                                                                                >
                                                                                    Ready
                                                                                    for
                                                                                    validation
                                                                                </div>

                                                                                <div
                                                                                    class="text-sm font-semibold text-red-600"
                                                                                    v-else-if="
                                                                                        slotProps
                                                                                            .item
                                                                                            .action ===
                                                                                        'rejected_payroll'
                                                                                    "
                                                                                >
                                                                                    Payroll
                                                                                    Rejected
                                                                                </div>

                                                                                <div
                                                                                    class="text-sm font-semibold text-green-600"
                                                                                    v-else
                                                                                >
                                                                                    Payroll
                                                                                    Approved
                                                                                </div>

                                                                                <div
                                                                                    class="text-xs text-surface-500 mt-1"
                                                                                >
                                                                                    {{
                                                                                        slotProps
                                                                                            .item
                                                                                            .created_by
                                                                                    }}
                                                                                </div>

                                                                                <span
                                                                                    class="text-[11px] text-surface-400"
                                                                                >
                                                                                    {{
                                                                                        slotProps
                                                                                            .item
                                                                                            .created_at
                                                                                    }}
                                                                                </span>

                                                                                <div
                                                                                    v-if="
                                                                                        slotProps
                                                                                            .item
                                                                                            .remarks
                                                                                    "
                                                                                    class="mt-3 rounded-lg bg-red-50 px-3 py-2 dark:bg-red-500/10"
                                                                                >
                                                                                    <div
                                                                                        class="text-xs font-semibold text-red-600"
                                                                                    >
                                                                                        Remarks
                                                                                    </div>

                                                                                    <div
                                                                                        class="mt-1 text-sm"
                                                                                    >
                                                                                        {{
                                                                                            slotProps
                                                                                                .item
                                                                                                .remarks
                                                                                        }}
                                                                                    </div>
                                                                                </div>
                                                                            </div>
                                                                        </template>
                                                                    </Timeline>
                                                                </div>
                                                            </Popover>
                                                        </div>

                                                        <div
                                                            :class="
                                                                q.status ==
                                                                'pending'
                                                                    ? ' text-yellow-600 '
                                                                    : q.status ==
                                                                        'approved'
                                                                      ? ' text-green-600 '
                                                                      : q.status ==
                                                                          'submitted'
                                                                        ? ' text-blue-600 '
                                                                        : q.status ==
                                                                            'rejected'
                                                                          ? ' text-red-600 '
                                                                          : ' text-slate-600 dark:text-gray-300 '
                                                            "
                                                        >
                                                            {{ q.status }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>

                                            <div
                                                class="border border-slate-200 rounded-xl overflow-hidden dark:border-gray-600"
                                            >
                                                <table
                                                    class="table table-auto w-full text-slate-800 dark:text-gray-100"
                                                >
                                                    <thead>
                                                        <tr>
                                                            <th
                                                                class="text-sm text-left p-2 font-semibold"
                                                            >
                                                                Month
                                                            </th>
                                                            <th
                                                                class="text-sm font-semibold p-2 text-right"
                                                            >
                                                                Amount
                                                            </th>
                                                        </tr>
                                                    </thead>

                                                    <tbody>
                                                        <tr
                                                            class="border-t border-slate-200 dark:border-gray-700"
                                                            v-for="(
                                                                item, idx
                                                            ) in q.stipends"
                                                            :key="idx"
                                                        >
                                                            <td
                                                                class="text-sm px-2 text-slate-600 py-1 dark:text-gray-300"
                                                            >
                                                                {{ item.month }}
                                                            </td>

                                                            <td
                                                                class="text-sm text-right font-medium px-2"
                                                            >
                                                                ₱{{
                                                                    item.amount
                                                                }}
                                                            </td>
                                                        </tr>
                                                        <tr
                                                            class="border-t border-slate-200 dark:border-gray-700"
                                                        >
                                                            <td
                                                                colspan="2"
                                                                class="text-sm px-2 py-1 font-semibold bg-slate-50 text-slate-700 dark:bg-gray-900 dark:text-gray-100"
                                                            >
                                                                <span>
                                                                    Financial
                                                                    Allowances</span
                                                                >
                                                            </td>
                                                        </tr>
                                                        <tr
                                                            class="border-t border-slate-200 dark:border-gray-700"
                                                            v-for="(
                                                                allowance,
                                                                allowanceKey
                                                            ) in q.financial"
                                                            :key="allowanceKey"
                                                        >
                                                            <td
                                                                class="text-sm text-slate-600 px-2 py-1 dark:text-gray-300"
                                                            >
                                                                {{
                                                                    allowance.name
                                                                }}
                                                            </td>

                                                            <td
                                                                class="text-sm text-right font-medium px-2"
                                                            >
                                                                ₱{{
                                                                    allowance.amount
                                                                }}
                                                            </td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </template>
                                    </div>

                                    <!-- Financial Allowances -->
                                    <div>
                                        <div class="mb-4">
                                            <h3
                                                class="text-sm font-semibold text-slate-700 dark:text-gray-100"
                                            >
                                                Total Financial Allowances
                                            </h3>

                                            <p class="text-xs text-slate-500 dark:text-gray-400">
                                                Additional Benefits
                                            </p>
                                        </div>

                                        <div class="space-y-2.5">
                                            <div
                                                class="flex justify-between text-sm text-slate-600 dark:text-gray-300"
                                            >
                                                <span>Clothing Allowance</span>

                                                <span class="font-medium"
                                                    >₱{{
                                                        page.props?.details
                                                            ?.financialAid
                                                            .clothing
                                                    }}</span
                                                >
                                            </div>
                                            <div
                                                class="flex justify-between text-sm text-slate-600 dark:text-gray-300"
                                            >
                                                <span>Transportation</span>
                                                <span class="font-medium"
                                                    >₱0.00</span
                                                >
                                            </div>

                                            <div
                                                class="flex justify-between text-sm text-slate-600 dark:text-gray-300"
                                            >
                                                <span
                                                    >Learning Materials &
                                                    Connectivity Allowance</span
                                                >
                                                <span class="font-medium"
                                                    >₱{{
                                                        page.props?.details
                                                            ?.financialAid
                                                            .connectivity
                                                    }}</span
                                                >
                                            </div>

                                            <div
                                                class="flex justify-between text-sm text-slate-600 dark:text-gray-300"
                                            >
                                                <span>Book Allowance</span>
                                                <span class="font-medium"
                                                    >₱0.00</span
                                                >
                                            </div>
                                            <div
                                                class="flex justify-between text-sm text-slate-600 dark:text-gray-300"
                                            >
                                                <span>Thesis Allowance</span>
                                                <span class="font-medium"
                                                    >₱0.00</span
                                                >
                                            </div>
                                            <div
                                                class="flex justify-between text-sm text-slate-600 dark:text-gray-300"
                                            >
                                                <span
                                                    >Graduation Allowance</span
                                                >
                                                <span class="font-medium"
                                                    >₱0.00</span
                                                >
                                            </div>

                                            <Divider class="my-2" />

                                            <div class="flex justify-between">
                                                <span
                                                    class="text-sm font-semibold text-slate-700 dark:text-gray-100"
                                                >
                                                    Total Allowances
                                                </span>

                                                <span
                                                    class="text-base font-semibold text-slate-800 dark:text-gray-50"
                                                >
                                                    ₱{{
                                                        page.props?.details
                                                            ?.financialAid
                                                            ?.totalAllowances
                                                    }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </Panel>
                    </div>
                    <Panel
                        class="!rounded-xl"
                        v-if="selectedTab.key == 4"
                        :pt="{
                            header: '!border-b-1 !border-gray-300 !border-dashed !p-0',
                            content: '!p-4',
                        }"
                    >
                        <template #header>
                            <div class="p-2 w-full flex justify-between">
                                <div class="flex items-center gap-2">
                                    <Avatar
                                        class="rounded-full !bg-blue-100 !text-blue-500"
                                        size="small"
                                    >
                                        <IconWood :size="20" stroke-width="2" />
                                    </Avatar>
                                    <h3 class="text-lg font-bold text-nowrap">
                                        Activity Logs
                                    </h3>
                                </div>
                            </div>
                        </template>
                        <template #default>
                            <div class="w-full h-full overflow-auto">
                                <Timeline
                                    :value="page.props.details.logs"
                                    align="left"
                                    class="p-1"
                                    :pt="{
                                        eventOpposite: '!hidden',
                                        eventSeparator: '!min-w-[3rem]',
                                    }"
                                >
                                    <template #marker="slotProps">
                                        <div
                                            :class="[
                                                'w-10 h-10 rounded-2xl border flex items-center justify-center shadow-sm',
                                                {
                                                    'bg-blue-50 border-blue-200':
                                                        slotProps.item.type ===
                                                        'profile',
                                                    'bg-emerald-50 border-emerald-200':
                                                        slotProps.item.type ===
                                                        'landbank',
                                                    'bg-cyan-50 border-cyan-200':
                                                        slotProps.item.type ===
                                                        'address',
                                                    'bg-green-50 border-green-200':
                                                        slotProps.item.type ===
                                                        'school',
                                                },
                                            ]"
                                        >
                                            <IconMapPin
                                                v-if="
                                                    slotProps.item.type ===
                                                    'address'
                                                "
                                                class="text-cyan-600"
                                                :size="22"
                                            />

                                            <IconUser
                                                v-else-if="
                                                    slotProps.item.type ===
                                                    'profile'
                                                "
                                                class="text-blue-600"
                                                :size="22"
                                            />

                                            <IconBuildingBank
                                                v-else-if="
                                                    slotProps.item.type ===
                                                    'landbank'
                                                "
                                                class="text-emerald-600"
                                                :size="22"
                                            />

                                            <IconSchool
                                                v-else
                                                class="text-green-600"
                                                :size="22"
                                            />
                                        </div>
                                    </template>

                                    <template #content="slotProps">
                                        <div class="flex flex-col gap-2">
                                            <div class="flex flex-col">
                                                <div
                                                    class="text-sm font-medium"
                                                >
                                                    Updated User
                                                    {{ slotProps.item.type }}
                                                </div>
                                                <div
                                                    class="text-sm flex gap-4 items-center text-gray-400"
                                                >
                                                    <div
                                                        class="flex gap-1 items-center"
                                                    >
                                                        <IconUserCircle
                                                            :size="20"
                                                        />
                                                        <div>
                                                            {{
                                                                slotProps.item
                                                                    .created_by
                                                            }}
                                                        </div>
                                                    </div>
                                                    <div
                                                        class="flex gap-1 items-center"
                                                    >
                                                        <IconCalendarFilled
                                                            :size="20"
                                                        />
                                                        <div>
                                                            {{
                                                                slotProps.item
                                                                    .date
                                                            }}
                                                        </div>
                                                    </div>
                                                </div>
                                            </div>
                                            <div class="p-3 text-sm">
                                                <div
                                                    v-for="(
                                                        value, key
                                                    ) in slotProps.item.changes"
                                                    :key="key"
                                                    class="flex items-center gap-2"
                                                >
                                                    <span
                                                        class="text-gray-700 min-w-36 capitalize"
                                                    >
                                                        {{
                                                            key.replaceAll(
                                                                "_",
                                                                " ",
                                                            )
                                                        }}
                                                    </span>

                                                    <span class="text-red-500">
                                                        {{
                                                            slotProps.item
                                                                .previous?.[
                                                                key
                                                            ] != ""
                                                                ? slotProps.item
                                                                      .previous?.[
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
                            </div>
                        </template>
                    </Panel>
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
    IconWood,
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
    IconHelpCircle,
    IconDotsCircleHorizontal,
    IconCircleCheck,
    IconCircleX,
    IconCircleDashed,
    IconArrowRight,
    IconBuildingBank,
    IconClock,
    IconCalendarFilled,
    IconUserCircle,
} from "@tabler/icons-vue";

import { computed, ref, watch } from "vue";
import { useToast } from "primevue";
import { useConfirm } from "primevue/useconfirm";
import * as TablerIcons from "@tabler/icons-vue";
import { router, useForm, usePage } from "@inertiajs/vue3";
import TextInput from "../../Components/inputs/TextInput.vue";
import SelectInput from "../../Components/inputs/SelectInput.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import DatePickerInput from "../../Components/inputs/DatePickerInput.vue";
import AutoCompleteInput from "../../Components/inputs/AutoCompleteInput.vue";

const toast = useToast();
const confirm = useConfirm();
const page = usePage();
const canUpdateScholars = computed(() =>
    (page.props?.permissions ?? []).includes("scholars.update"),
);
const opTransfer = ref(null);
const opHistory = ref([]);
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
    academicRecord: false,
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

const academicRecordForm = useForm({
    termRecordId: null,
    school: null,
    course: null,
    level: null,
    term: null,
    academic_year: null,
    scholarship_status: null,
    subjects: [],
    deleted_subjects: [],
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
        label: "Financial Assistance Records",
        icon: "IconCoins",
        key: 3,
    },
]);

const academicStatusDisplay = computed(() => {
    const rawStatus = String(
        page.props?.details?.academic_status ??
            page.props?.details?.status?.name ??
            "NEW",
    ).toUpperCase();
    const statusOption = page.props?.statusOptions?.find(
        (status) =>
            status.name?.toUpperCase() === rawStatus ||
            String(status.id ?? "").toUpperCase() === rawStatus,
    );

    if (statusOption) {
        return {
            name: statusOption.name,
            icon: statusOption.icon ?? "IconCircleCheck",
            bcolor: statusOption.bcolor ?? "bg-slate-50",
            tcolor: statusOption.tcolor ?? "text-slate-600",
        };
    }

    return {
        name: rawStatus,
        icon: "IconCircleCheck",
        bcolor: "bg-slate-50",
        tcolor: "text-slate-600",
    };
});

const isPrerequisiteSubject = (subject) => {
    const text = [
        subject?.name,
        subject?.code,
        subject?.subject_code,
        subject?.class,
        subject?.subject_class,
    ].filter(Boolean).join(" ").toLowerCase();

    return text.includes("prerequisite") || text.includes("pre-requisite");
};

const academicSubjectOptions = computed(() =>
    (page.props?.subjectOptions ?? [])
        .filter((subject) => {
            const subjectClass = String(subject?.class ?? subject?.subject_class ?? "academic")
                .trim()
                .toLowerCase();

            return subjectClass === "academic" && !isPrerequisiteSubject(subject);
        })
        .map((subject) => ({
            ...subject,
            name: String(subject?.name ?? "").toUpperCase(),
            code: subject?.code ? String(subject.code).toUpperCase() : subject?.code,
            subject_code: subject?.subject_code ? String(subject.subject_code).toUpperCase() : subject?.subject_code,
        })),
);

const academicSubjectOptionsForRow = (index) => {
    const selected = academicRecordForm.subjects[index]?.subject;
    const options = academicSubjectOptions.value;

    if (!selected?.id || options.some((subject) => subject.id === selected.id)) {
        return options;
    }

    return [selected, ...options];
};

const academicGradeOptions = computed(() =>
    (page.props?.gradeOptions ?? []).map((grade) => normalizeGradeOption(grade)),
);

const academicGradeOptionsForRow = (index) => {
    const selected = academicRecordForm.subjects[index]?.grade;
    const options = academicGradeOptions.value;

    if (!selected?.id) {
        return options;
    }

    return [
        selected,
        ...options.filter((grade) => grade.id !== selected.id),
    ];
};

const normalizeSubjectOption = (subject) => {
    const code = subject?.code ?? subject?.subject_code;

    return {
        id: subject?.id,
        name: subject?.name ? String(subject.name).toUpperCase() : subject?.name,
        code: code ? String(code).toUpperCase() : code,
        unit: subject?.unit,
        class: subject?.class ?? subject?.subject_class,
    };
};

const normalizeGradeOption = (grade) => grade
    ? {
          id: grade.id,
          name: grade.name ?? grade.grade,
          grade: grade.grade ?? grade.name,
          is_failed: grade.is_failed,
          is_incomplete: grade.is_incomplete,
          is_drop: grade.is_drop,
          is_active: grade.is_active,
      }
    : null;

const currentAcademicSubject = (row) => normalizeSubjectOption(row?.subject);

const academicSubjectTotal = (row) => {
    const gradeValue = Number(row?.grade?.name ?? row?.grade?.grade);
    const unit = Number(row?.subject?.unit);

    if (
        !Number.isFinite(gradeValue) ||
        !Number.isFinite(unit) ||
        row?.grade?.is_drop ||
        row?.grade?.is_incomplete
    ) {
        return null;
    }

    return Number((gradeValue * unit).toFixed(2));
};

const academicSubjectRemark = (row) => {
    if (row?.grade?.is_drop) return "Dropped";
    if (row?.grade?.is_failed) return "Failed";
    if (row?.grade?.is_incomplete) return "Incompleted";
    if (row?.grade?.id) return "Passed";

    return "-";
};

const academicSubjectRemarkClass = (row) => {
    if (row?.grade?.is_drop) return "text-slate-500";
    if (row?.grade?.is_failed) return "text-rose-600";
    if (row?.grade?.is_incomplete) return "text-amber-600";
    if (row?.grade?.id) return "text-green-600";

    return "text-slate-400";
};

const academicRecordHasUnsavedChanges = computed(() =>
    Boolean(editBtn.value.academicRecord) &&
    (
        academicRecordForm.isDirty ||
        (academicRecordForm.deleted_subjects?.length ?? 0) > 0
    ),
);

const scholarLocationDisplay = computed(() => {
    const details = page.props?.details;
    const parts = [
        details?.address?.barangay?.name,
        details?.address?.municipality?.name,
        details?.address?.province?.name,
    ].filter(Boolean);

    return parts.length ? parts.join(", ") : "N/A";
});

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
    toast.add({
        severity: "info",
        summary: "Request flow updated",
        detail: "Subject requests are now handled directly through term records.",
        life: 3000,
    });
};

const validateGradeRequest = (data) => {
    toast.add({
        severity: "info",
        summary: "Request flow updated",
        detail: "Grade approvals are now handled from the term-record request workflow.",
        life: 3000,
    });
};

const storePersonalInfo = async () => {
    if (!canUpdateScholars.value) return;

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
                    severity: page.props.flash?.status,
                    summary: page.props.flash?.title,
                    detail: page.props.flash?.message,
                    life: 3000,
                });
            },

            onFinish: () => {
                loading.value.storePersonalInfo = false;
            },
        },
    );
};

const editingAcademicRecord = (termRecord) =>
    editBtn.value.academicRecord &&
    academicRecordForm.termRecordId === termRecord?.id;

const academicRecordRows = (termRecord) =>
    editingAcademicRecord(termRecord)
        ? academicRecordForm.subjects
        : termRecord?.subjects ?? [];

const editAcademicRecord = (termRecord) => {
    if (!canUpdateScholars.value) return;

    editBtn.value.academicRecord = true;
    academicRecordForm.clearErrors();
    academicRecordForm.termRecordId = termRecord.id;
    academicRecordForm.deleted_subjects = [];
    academicRecordForm.school = termRecord.schoolInput ?? page.props?.details?.schoolInput ?? null;
    academicRecordForm.course = termRecord.courseInput ?? page.props?.details?.courseInput ?? null;
    academicRecordForm.level = termRecord.levelInput ?? null;
    academicRecordForm.term = termRecord.termInput ?? null;
    academicRecordForm.academic_year = termRecord.academic_year ?? null;
    academicRecordForm.scholarship_status =
        page.props?.standingOptions?.find((status) => {
            const currentStatus = String(termRecord.scholarshipStatus ?? "").toUpperCase();

            return (
                status.name?.toUpperCase() === currentStatus ||
                String(status.id ?? "").toUpperCase() === currentStatus
            );
        }) ?? (termRecord.scholarshipStatus
            ? { id: termRecord.scholarshipStatus, name: termRecord.scholarshipStatus }
            : null);
    academicRecordForm.subjects = (termRecord.subjects ?? []).map((subject) => ({
        id: subject.id ?? null,
        subject: academicSubjectOptions.value.find((option) => option.id === subject.subject?.id) ??
            currentAcademicSubject(subject),
        grade: normalizeGradeOption(subject.grade),
    }));
};

const discardAcademicRecordEdit = () => {
    editBtn.value.academicRecord = false;
    academicRecordForm.reset();
};

const cancelAcademicRecordEdit = () => {
    if (!academicRecordHasUnsavedChanges.value) {
        discardAcademicRecordEdit();
        return;
    }

    confirm.require({
        group: "global",
        header: "Discard Changes?",
        message: "All unsaved academic record changes will not be saved.",
        icon: "pi pi-exclamation-triangle",
        severity: "danger",
        rejectLabel: "Keep Editing",
        acceptLabel: "Discard",
        accept: discardAcademicRecordEdit,
    });
};

const addAcademicSubject = () => {
    academicRecordForm.subjects.push({
        id: null,
        subject: null,
        grade: null,
    });
};

const removeAcademicSubject = (index) => {
    const subject = academicRecordForm.subjects[index];
    academicRecordForm.subjects.splice(index, 1);

    if (subject?.id) {
        academicRecordForm.deleted_subjects.push(subject.id);
    }
};

const renderAcademicCourse = (event) => {
    const school = event?.value ?? academicRecordForm.school;

    if (!school?.name) return;

    academicRecordForm.course = null;
    router.reload({
        only: ["courseOptions"],
        data: { campus: school.name },
        preserveState: true,
        preserveScroll: true,
        showProgress: true,
    });
};

const updateAcademicRecord = () => {
    if (!canUpdateScholars.value || !academicRecordForm.termRecordId) return;

    academicRecordForm.post(
        route("scholar.grade-update", {
            id: academicRecordForm.termRecordId,
        }),
        {
            preserveScroll: true,
            onSuccess: () => {
                editBtn.value.academicRecord = false;
                academicRecordForm.reset();
                toast.add({
                    severity: page.props.flash?.status,
                    summary: page.props.flash?.title,
                    detail: page.props.flash?.message,
                    life: 3000,
                });
            },
        },
    );
};

const confirmAcademicRecordSave = () => {
    if (!canUpdateScholars.value || !academicRecordForm.termRecordId) return;

    confirm.require({
        group: "global",
        header: "Save Academic Records?",
        message: "This will save the changes made to the scholar's academic records.",
        icon: "pi pi-save",
        severity: "info",
        rejectLabel: "Cancel",
        rejectSeverity: "secondary",
        acceptLabel: "Save",
        acceptSeverity: "info",
        accept: updateAcademicRecord,
    });
};

const historyToggle = (event, index) => {
    opHistory.value[index].toggle(event);
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
        personalInfo.status = page.props?.statusOptions?.find((status) => {
            const scholarStatus = String(
                newVal.academic_status ?? newVal.status?.name ?? "NEW",
            ).toUpperCase();

            return (
                status.name?.toUpperCase() === scholarStatus ||
                String(status.id ?? "").toUpperCase() === scholarStatus
            );
        }) ??
            newVal.status ?? { id: "NEW", name: "NEW" };
        personalInfo.acc_name = newVal.landbank.account_name ?? null;
        personalInfo.acc_no = newVal.landbank.account_number ?? null;
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
    if (!canUpdateScholars.value) return;

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
