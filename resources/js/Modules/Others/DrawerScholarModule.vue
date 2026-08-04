<template>
    <Drawer
        v-model:visible="modelValue"
        position="full"
        :pt="{ header: 'border-b-1 border-gray-300 border-dashed' }"
    >
        <template #header>
            <div
                class="bg-slate-100 px-5 py-1 shadow rounded-lg flex items-center gap-2"
            >
                <IconId :size="25" />
                <div class="text-lg uppercase font-medium">
                    Scholar Information
                </div>
            </div>
        </template>
        <template #default>
            <div class="w-full h-full pt-5 gap-5 flex flex-col lg:flex-row">
                <div class="lg:w-4/12 flex flex-col gap-5">
                    <div
                        class="border border-slate-300 rounded-2xl p-2 flex flex-col lg:flex-row gap-2"
                    >
                        <div
                            class="bg-slate-50 lg:w-1/2 rounded-xl p-3 flex flex-col justify-between"
                        >
                            <div class="flex items-start justify-between">
                                <div>
                                    <Avatar
                                        v-if="details.photo == null"
                                        class="!w-[9rem] !h-[9rem] !rounded-xl !text-5xl !bg-slate-300"
                                    >
                                        <IconUserFilled :size="80" />
                                    </Avatar>

                                    <Avatar
                                        v-else
                                        style="
                                            background-color: #dee9fc;
                                            color: #1a2551;
                                        "
                                        :image="details.photo"
                                        class="!w-[9rem] !h-[9rem] !rounded-xl !text-5xl !bg-slate-300"
                                    />
                                </div>
                            </div>

                            <div class="flex flex-col">
                                <div class="text-sm flex items-center gap-2">
                                    <div>{{ details.spas_no }}</div>
                                    <div v-tooltip.right="'Copy'">
                                        <IconCopy
                                            :size="15"
                                            class="cursor-pointer"
                                        />
                                    </div>
                                </div>
                                <div class="font-medium text-lg">
                                    {{ details.fullname }}
                                </div>
                            </div>
                        </div>
                        <div class="flex-1">
                            <div
                                class="grid h-full w-full gap-2 grid-cols-4 grid-rows-5 rounded-lg"
                            >
                                <div
                                    class="col-span-4 row-span-1 flex items-start justify-end"
                                >
                                    <div
                                        :class="[
                                            ' flex items-center uppercase text-sm  rounded-xl py-1 px-5 gap-1',
                                            details.status.bcolor,
                                            details.status.tcolor,
                                        ]"
                                    >
                                        <component
                                            :is="
                                                TablerIcons[details.status.icon]
                                            "
                                            :size="20"
                                            :stroke="2"
                                        />
                                        <div class="">
                                            {{ details.status.name }}
                                        </div>
                                    </div>
                                </div>

                                <div
                                    class="col-span-1 row-span-1 flex items-start gap-1 text-gray-500 text-sm"
                                >
                                    <IconBook2 :size="20" />
                                    <div>Course</div>
                                </div>
                                <div
                                    class="col-span-3 row-span-1 flex items-start justify-start"
                                >
                                    <p class="text-sm">{{ details.course }}</p>
                                </div>

                                <div
                                    class="col-span-1 row-span-1 flex items-start gap-1 text-gray-500 text-sm"
                                >
                                    <IconBuilding :size="20" />
                                    <div>School</div>
                                </div>

                                <div
                                    class="col-span-3 row-span-1 flex items-start justify-start"
                                >
                                    <p class="text-sm">
                                        {{ details.school }}
                                    </p>
                                </div>

                                <div
                                    class="col-span-1 row-span-1 flex items-start gap-1 text-gray-500 text-sm"
                                >
                                    <IconMap2 :size="20" />
                                    <div>Region</div>
                                </div>

                                <div
                                    class="col-span-3 row-span-1 flex items-start justify-start"
                                >
                                    <p class="text-sm">
                                        {{ details.region.name }}
                                    </p>
                                </div>
                                <div
                                    class="col-span-1 row-span-1 flex items-start gap-1 text-gray-500 text-sm"
                                >
                                    <IconBuildingEstate :size="20" />

                                    <div>Office</div>
                                </div>

                                <div
                                    class="col-span-3 row-span-1 flex items-start justify-start"
                                >
                                    <p class="text-sm uppercase">
                                        {{ details.agency }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <div class="text-sm font-medium">
                            Scholarship Details
                        </div>
                        <div
                            class="grid h-full w-full grid-cols-3 grid-rows-1 rounded-lg border border-slate-300"
                        >
                            <div
                                class="col-span-1 row-span-1 rounded-lg flex p-1"
                            >
                                <div class="flex flex-col w-full gap-3">
                                    <div
                                        class="text-gray-500 flex gap-1 items-center"
                                    >
                                        <IconSchool
                                            :size="20"
                                            class="text-blue-500"
                                            :stroke-width="2"
                                        />
                                        <div>Category</div>
                                    </div>
                                    <div class="text-center text-lg">
                                        {{ details.type }}
                                    </div>
                                </div>
                            </div>

                            <div
                                class="col-span-1 row-span-1 border-x border-slate-300"
                            >
                                <div
                                    class="col-span-1 row-span-1 rounded-lg flex p-1"
                                >
                                    <div class="flex flex-col w-full gap-3">
                                        <div
                                            class="text-gray-500 flex gap-1 items-center"
                                        >
                                            <IconCertificate
                                                :size="20"
                                                class="text-blue-500"
                                                :stroke-width="2"
                                            />
                                            <div>Program</div>
                                        </div>
                                        <div class="text-center text-lg">
                                            {{ details.program }}
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-span-1 row-span-1 rounded-lg">
                                <div
                                    class="col-span-1 row-span-1 rounded-lg p-1"
                                >
                                    <div class="flex flex-col w-full gap-3">
                                        <div
                                            class="text-gray-500 flex gap-1 items-center"
                                        >
                                            <IconCalendar
                                                :size="20"
                                                class="text-blue-500"
                                                :stroke-width="2"
                                            />
                                            <div>Award Year</div>
                                        </div>
                                        <div
                                            class="flex items-center justify-center gap-1 text-lg"
                                        >
                                            {{ details.awardyear }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex flex-col">
                        <div class="text-sm font-medium">Contact Details</div>
                        <div
                            class="grid h-full w-full grid-cols-2 grid-rows-1 rounded-lg border border-slate-300"
                        >
                            <div
                                class="col-span-1 row-span-1 rounded-lg flex p-1"
                            >
                                <div class="flex flex-col w-full gap-3">
                                    <div
                                        class="text-gray-500 flex gap-1 items-center"
                                    >
                                        <IconDeviceMobileMessage
                                            :size="20"
                                            class="text-blue-500"
                                            :stroke-width="2"
                                        />
                                        <div>Contact Numbers</div>
                                    </div>
                                    <div class="text-center text-xs lg:text-lg">
                                        {{ details.contact_no }}
                                    </div>
                                </div>
                            </div>

                            <div
                                class="col-span-1 row-span-1 border-l border-slate-300"
                            >
                                <div
                                    class="col-span-1 row-span-1 rounded-lg flex p-1"
                                >
                                    <div class="flex flex-col w-full gap-3">
                                        <div
                                            class="text-gray-500 flex gap-1 items-center"
                                        >
                                            <IconSend
                                                :size="20"
                                                class="text-blue-500"
                                                :stroke-width="2"
                                            />
                                            <div>Email Address</div>
                                        </div>
                                        <div
                                            class="text-center text-xs lg:text-lg"
                                        >
                                            {{ details.email }}
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="flex-1 bg-blue-50 flex flex-col">
                        <div
                            class="flex items-center justify-center w-full h-full"
                        >
                            Activity logs
                        </div>
                    </div>
                </div>
                <div class="flex-1">
                    <Tabs
                        value="info"
                        class="w-full"
                        @update:value="tabChangeGet"
                    >
                        <TabList>
                            <Tab value="info" class="!px-3 !py-1">
                                <div class="flex items-center gap-2">
                                    <IconUserSearch :size="20" />
                                    <div class="text-sm font-light">
                                        Personal Information
                                    </div>
                                </div>
                            </Tab>
                            <Tab value="scholarship" class="!px-3 !py-1">
                                <div class="flex items-center gap-2">
                                    <IconSchool :size="20" />
                                    <div class="text-sm font-light">
                                        Scholarship Info
                                    </div>
                                </div>
                            </Tab>
                            <Tab value="academic" class="!px-3 !py-1">
                                <div class="flex items-center gap-2">
                                    <IconFileInvoice :size="20" />
                                    <div class="text-sm font-light">
                                        Academic Records
                                    </div>
                                </div>
                            </Tab>
                        </TabList>
                        <TabPanels class="!pt-3 !px-0">
                            <TabPanel value="info">
                                <div
                                    class="flex w-full flex-col gap-4 items-center justify-center"
                                >
                                    <div class="flex w-full justify-end">
                                        <DefaultButton
                                            :icon="TablerIcons.IconUserEdit"
                                            label="Edit Details"
                                            size="small"
                                            v-if="!editBtn.info"
                                            @click="editBtn.info = true"
                                            raised
                                            class-name="!rounded-xl !px-5"
                                        />
                                        <div
                                            class="flex items-center gap-2"
                                            v-else
                                        >
                                            <DefaultButton
                                                :icon="
                                                    TablerIcons.IconUserCancel
                                                "
                                                label="Cancel Edit"
                                                size="small"
                                                severity="danger"
                                                @click="editBtn.info = false"
                                                outlined
                                                class-name="!rounded-xl !px-5"
                                            />
                                            <DefaultButton
                                                :icon="
                                                    TablerIcons.IconUserCheck
                                                "
                                                severity="secondary"
                                                raised
                                                label="Save this details"
                                                size="small"
                                                class-name="!rounded-xl !px-5"
                                            />
                                        </div>
                                    </div>
                                    <div
                                        class="flex flex-col gap-4 mb-2 lg:w-7/12 overflow-auto"
                                    >
                                        <TextInput
                                            v-model="personalInfo.spas_no"
                                            label="SPAS ID"
                                            capitalize
                                            :disabled="!editBtn.info"
                                        ></TextInput>
                                        <div class="flex items-center gap-2">
                                            <TextInput
                                                v-model="
                                                    personalInfo.first_name
                                                "
                                                label="First Name"
                                                :disabled="!editBtn.info"
                                            ></TextInput>

                                            <TextInput
                                                v-model="
                                                    personalInfo.middle_name
                                                "
                                                label="Middle Name"
                                                :disabled="!editBtn.info"
                                            ></TextInput>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <TextInput
                                                v-model="personalInfo.last_name"
                                                label="Last Name"
                                                :disabled="!editBtn.info"
                                            ></TextInput>
                                            <TextInput
                                                v-model="personalInfo.suffix"
                                                label="Suffix"
                                                :disabled="!editBtn.info"
                                            ></TextInput>
                                        </div>
                                        <TextInput
                                            v-model="personalInfo.email"
                                            label="Email"
                                            :disabled="!editBtn.info"
                                        ></TextInput>
                                        <TextInput
                                            v-model="personalInfo.contact_no"
                                            label="Contact No"
                                            :disabled="!editBtn.info"
                                        ></TextInput>
                                        <Divider />
                                        <div class="flex items-center gap-2">
                                            <DatePickerInput
                                                v-model="
                                                    personalInfo.birth_date
                                                "
                                                label="Birth Date"
                                                :disabled="!editBtn.info"
                                            ></DatePickerInput>
                                            <TextInput
                                                v-model="
                                                    personalInfo.birth_place
                                                "
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
                                                v-model="
                                                    personalInfo.civil_status
                                                "
                                                capitalize
                                                label="Civil Status"
                                                :disabled="!editBtn.info"
                                            ></TextInput>
                                        </div>
                                        <TextInput
                                            v-model="personalInfo.address"
                                            label="Address"
                                            :disabled="!editBtn.info"
                                            placeholder="Street, Subdivision, etc."
                                        ></TextInput>
                                        <AutoCompleteInput
                                            v-model="personalInfo.fulladdress"
                                            :options="page.props.georesult"
                                            :disabled="!editBtn.info"
                                            :loading="loading.address"
                                            placeholder="Find by Barangay, Municipality, Province, or Region"
                                            @complete="autoSearch"
                                            selection
                                        ></AutoCompleteInput>
                                    </div>
                                </div>
                            </TabPanel>
                            <TabPanel value="scholarship">
                                <p class="m-0">
                                    Sed ut perspiciatis unde omnis iste natus
                                    error sit voluptatem accusantium doloremque
                                    laudantium, totam rem aperiam, eaque ipsa
                                    quae ab illo inventore veritatis et quasi
                                    architecto beatae vitae dicta sunt
                                    explicabo. Nemo enim ipsam voluptatem quia
                                    voluptas sit aspernatur aut odit aut fugit,
                                    sed quia consequuntur magni dolores eos qui
                                    ratione voluptatem sequi nesciunt.
                                    Consectetur, adipisci velit, sed quia non
                                    numquam eius modi.
                                </p>
                            </TabPanel>
                            <TabPanel value="academic">
                                <div
                                    class="w-full"
                                    v-for="(item, index) in page.props
                                        ?.academic"
                                    :key="index"
                                >
                                    <panel
                                        :pt="{
                                            root: {
                                                class: '!rounded-2xl',
                                            },
                                            header: {
                                                class: '!p-3  !border-b',
                                            },
                                            content: {
                                                class: '!p-3',
                                            },
                                        }"
                                    >
                                        <template #header>
                                            <div
                                                class="w-full flex items-center justify-between"
                                            >
                                                <div
                                                    class="flex items-center gap-2"
                                                >
                                                    <div
                                                        class="bg-blue-50 text-blue-500 border p-2 rounded-xl"
                                                    >
                                                        <IconBuilding />
                                                    </div>
                                                    <div
                                                        class="flex-1 flex flex-col"
                                                    >
                                                        <div class="leading-5">
                                                            {{ item.name }}
                                                        </div>
                                                        <div
                                                            class="text-xs text-slate-500 font-light leading-3"
                                                        >
                                                            {{ item.course }}
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="flex gap-3"></div>
                                            </div>
                                        </template>
                                        <template #default>
                                            <div class="flex w-full gap-3">
                                                <div class="flex-1/4">
                                                    <Menu
                                                        :model="item.records"
                                                        class="text-sm"
                                                    >
                                                        <template
                                                            #item="{
                                                                item,
                                                                props,
                                                                key,
                                                            }"
                                                        >
                                                            <a
                                                                v-bind="
                                                                    props.action
                                                                "
                                                                @click="
                                                                    getGrades(
                                                                        item,
                                                                        key,
                                                                    )
                                                                "
                                                                ><span>{{
                                                                    item.label
                                                                }}</span>
                                                            </a>
                                                        </template>
                                                    </Menu>
                                                </div>
                                                <div class="flex-3/4">
                                                    <panel
                                                        v-if="selectTerm"
                                                        :pt="{
                                                            header: {
                                                                class:
                                                                    randomColor(
                                                                        selectTerm.index,
                                                                    ) +
                                                                    ' !p-3 !rounded-t-lg',
                                                            },
                                                            root: borderColor(
                                                                selectTerm.index,
                                                            ),
                                                        }"
                                                        class=""
                                                    >
                                                        <template #header>
                                                            <div
                                                                class="flex justify-between w-full items-center"
                                                            >
                                                                <div
                                                                    class="flex gap-2 items-center"
                                                                >
                                                                    <IconGridDots
                                                                        size="20"
                                                                    />
                                                                    <div>
                                                                        {{
                                                                            selectTerm.year_level
                                                                        }}
                                                                        /
                                                                        {{
                                                                            selectTerm.label
                                                                        }}
                                                                    </div>
                                                                </div>
                                                                <div
                                                                    class="flex gap-2"
                                                                >
                                                                    <DefaultButton
                                                                        :icon="
                                                                            TablerIcons.IconScript
                                                                        "
                                                                        label="Edit Grades"
                                                                        size="small"
                                                                        raised
                                                                        v-if="
                                                                            !editBtn.grades
                                                                        "
                                                                        @click="
                                                                            editGrades(
                                                                                item,
                                                                            )
                                                                        "
                                                                        class-name="!rounded-xl !px-5"
                                                                    />
                                                                    <div
                                                                        class="flex items-center gap-2"
                                                                        v-else
                                                                    >
                                                                        <DefaultButton
                                                                            :icon="
                                                                                TablerIcons.IconScriptX
                                                                            "
                                                                            label="Cancel Edit"
                                                                            size="small"
                                                                            severity="danger"
                                                                            @click="
                                                                                editBtn.grades = false
                                                                            "
                                                                            class-name="!rounded-xl !px-5"
                                                                        />
                                                                        <DefaultButton
                                                                            :icon="
                                                                                TablerIcons.IconScriptPlus
                                                                            "
                                                                            label="Update Details"
                                                                            raised
                                                                            :loading="
                                                                                gradesForm.processing
                                                                            "
                                                                            :disabled="
                                                                                gradesForm.processing
                                                                            "
                                                                            @click="
                                                                                updateGrades
                                                                            "
                                                                            size="small"
                                                                            class-name="!rounded-xl !px-5"
                                                                        />
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        </template>
                                                        <template #default>
                                                            <div
                                                                class="pt-2 w-full flex flex-col gap-3"
                                                            >
                                                                <table
                                                                    class="min-w-full !border-none text-sm"
                                                                    v-if="
                                                                        !editBtn.grades
                                                                    "
                                                                >
                                                                    <thead>
                                                                        <tr
                                                                            class="bg-gray-100"
                                                                        >
                                                                            <th
                                                                                class="px-3 py-2 text-left"
                                                                            >
                                                                                Subject
                                                                                Name
                                                                            </th>
                                                                            <th
                                                                                class="px-3 py-2 text-left"
                                                                            >
                                                                                Subject
                                                                                Code
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
                                                                                Remarks
                                                                            </th>
                                                                        </tr>
                                                                    </thead>
                                                                    <tbody>
                                                                        <tr
                                                                            v-for="(
                                                                                item,
                                                                                index
                                                                            ) in selectTerm.grades"
                                                                            :key="
                                                                                index
                                                                            "
                                                                            class="hover:bg-gray-50"
                                                                        >
                                                                            <td
                                                                                class="px-3 py-2 uppercase"
                                                                            >
                                                                                {{
                                                                                    item
                                                                                        .subject
                                                                                        ?.name ??
                                                                                    item.name
                                                                                }}
                                                                            </td>
                                                                            <td
                                                                                class="px-3 py-2"
                                                                            >
                                                                                {{
                                                                                    item
                                                                                        .subject
                                                                                        ?.subject_code ??
                                                                                    item.subject_code
                                                                                }}
                                                                            </td>
                                                                            <td
                                                                                class="px-3 py-2 text-right"
                                                                            >
                                                                                {{
                                                                                    item
                                                                                        .subject
                                                                                        ?.unit ??
                                                                                    item.unit
                                                                                }}
                                                                            </td>
                                                                            <td
                                                                                class="px-3 py-2 text-right"
                                                                            >
                                                                                {{
                                                                                    item
                                                                                        .grade
                                                                                        ?.name
                                                                                }}
                                                                            </td>
                                                                            <td
                                                                                class="px-3 py-2 text-right"
                                                                            >
                                                                                <div
                                                                                    v-if="
                                                                                        item
                                                                                            .grade
                                                                                            ?.is_drop
                                                                                    "
                                                                                    class="text-slate-500"
                                                                                >
                                                                                    Dropped
                                                                                </div>
                                                                                <div
                                                                                    v-else-if="
                                                                                        item
                                                                                            .grade
                                                                                            ?.is_failed
                                                                                    "
                                                                                    class="text-rose-600"
                                                                                >
                                                                                    Failed
                                                                                </div>
                                                                                <div
                                                                                    v-else-if="
                                                                                        item
                                                                                            .grade
                                                                                            ?.is_incomplete
                                                                                    "
                                                                                    class="text-amber-600"
                                                                                >
                                                                                    Incompleted
                                                                                </div>
                                                                                <div
                                                                                    v-else-if="
                                                                                        item
                                                                                            .grade
                                                                                            ?.is_active
                                                                                    "
                                                                                    class="text-green-600"
                                                                                >
                                                                                    Passed
                                                                                </div>
                                                                            </td>
                                                                        </tr>
                                                                    </tbody>
                                                                </table>
                                                                <div
                                                                    v-else
                                                                    class="flex flex-col gap-3"
                                                                >
                                                                    <div
                                                                        v-for="(
                                                                            item,
                                                                            index
                                                                        ) in gradesForm.subjects"
                                                                        class="flex w-full items-end gap-3"
                                                                        :key="
                                                                            index
                                                                        "
                                                                    >
                                                                        <div
                                                                            class="lg:w-[60%]"
                                                                        >
                                                                            <SelectInput
                                                                                label="Subject"
                                                                                v-model="
                                                                                    gradesForm
                                                                                        .subjects[
                                                                                        index
                                                                                    ]
                                                                                        .subject
                                                                                "
                                                                                :error-mark="
                                                                                    gradesForm
                                                                                        .errors?.[
                                                                                        `subjects.${index}.subject`
                                                                                    ]
                                                                                "
                                                                                :options="
                                                                                    options.subjects
                                                                                "
                                                                                filter
                                                                            ></SelectInput>
                                                                        </div>
                                                                        <div
                                                                            class="lg:w-[30%]"
                                                                        >
                                                                            <SelectInput
                                                                                label="Grade"
                                                                                v-model="
                                                                                    gradesForm
                                                                                        .subjects[
                                                                                        index
                                                                                    ]
                                                                                        .grade
                                                                                "
                                                                                :error-mark="
                                                                                    gradesForm
                                                                                        .errors?.[
                                                                                        `subjects.${index}.grade`
                                                                                    ]
                                                                                "
                                                                                :options="
                                                                                    options.grades
                                                                                "
                                                                            ></SelectInput>
                                                                        </div>
                                                                        <div
                                                                            class="flex flex-col items-"
                                                                        >
                                                                            <DefaultButton
                                                                                :icon="
                                                                                    TablerIcons.IconTrash
                                                                                "
                                                                                text
                                                                                severity="danger"
                                                                                size="small"
                                                                                shape="circle"
                                                                                @click="
                                                                                    removeGrades(
                                                                                        index,
                                                                                        item,
                                                                                    )
                                                                                "
                                                                            />
                                                                        </div>
                                                                    </div>
                                                                </div>

                                                                <DefaultButton
                                                                    :icon="
                                                                        TablerIcons.IconScriptPlus
                                                                    "
                                                                    label="Add Grades"
                                                                    @click="
                                                                        addGrades
                                                                    "
                                                                    v-if="
                                                                        editBtn.grades
                                                                    "
                                                                    severity="secondary"
                                                                    size="small"
                                                                    class-name="!rounded-xl w-full !px-5"
                                                                />
                                                            </div>
                                                        </template>
                                                    </panel>
                                                    <div
                                                        v-else
                                                        class="flex items-center justify-center h-full"
                                                    >
                                                        <p
                                                            class="text-gray-400 font-light text-sm"
                                                        >
                                                            Please select
                                                            semester to view
                                                        </p>
                                                    </div>
                                                </div>
                                            </div>
                                        </template>
                                    </panel>
                                </div>
                            </TabPanel>
                        </TabPanels>
                    </Tabs>
                </div>
            </div>
        </template>
    </Drawer>
</template>
<script setup>
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import TextInput from "../../Components/inputs/TextInput.vue";
import AutoCompleteInput from "../../Components/inputs/AutoCompleteInput.vue";
import DatePickerInput from "../../Components/inputs/DatePickerInput.vue";
import SelectInput from "../../Components/inputs/SelectInput.vue";
import {
    IconSchool,
    IconCalendar,
    IconCertificate,
    IconCopy,
    IconId,
    IconScript,
    IconBook2,
    IconMap2,
    IconGridDots,
    IconBuilding,
    IconBuildingEstate,
    IconDeviceMobileMessage,
    IconSend,
    IconUserSearch,
    IconUserFilled,
    IconFileInvoice,
} from "@tabler/icons-vue";
import * as TablerIcons from "@tabler/icons-vue";
import { ref, watch, reactive } from "vue";

import { router } from "@inertiajs/vue3";
import { route } from "ziggy-js";
import { useForm, usePage } from "@inertiajs/vue3";

const modelValue = defineModel("modelValue");
const editBtn = reactive({
    info: false,
    grades: false,
});
const dialog = reactive({
    request: false,
});
const selectTerm = ref(null);
const loading = reactive({
    address: false,
});
const options = reactive({
    grades: [],
    subjects: [],
});
const popover = reactive({
    validateClose: null,
});
const page = usePage();

const props = defineProps({
    details: {
        type: [Array, Object],
        default: [],
    },
});

const randomColor = (index) => {
    const colors = [
        "!text-sky-600 !bg-sky-50 !border-sky-600",
        "!text-indigo-600 !bg-indigo-50 ",
        "!text-rose-600 !bg-rose-50",
        "!text-yellow-600 !bg-yellow-50",
        "!text-purple-600 !bg-purple-50",
        "!text-pink-600 !bg-pink-50",
        "!text-cyan-600 !bg-cyan-50",
        "!text-teal-600 !bg-teal-50",
    ];
    return colors[index % colors.length];
};

const borderColor = (index) => {
    const borderColors = [
        "!border-sky-600",
        "!border-indigo-600",
        "!border-rose-600",
        "!border-yellow-600",
        "!border-purple-600",
        "!border-pink-600",
        "!border-cyan-600",
        "!border-teal-600",
    ];
    return borderColors[index % borderColors.length];
};

const personalInfo = useForm({
    spas_no: "",
    last_name: "",
    first_name: "",
    middle_name: "",
    suffix: "",
    email: "",
    contact_no: "",
    birth_date: "",
    birth_place: "",
    religion: "",
    civil_status: "",
    address: "",
    fulladdress: "",
});

const gradesForm = useForm({
    term_id: null,
    id: null,
    subjects: [],
});

const autoSearch = (event) => {
    loading.address = true;
    router.get(
        route("scholars"),
        { geosearch: event },
        {
            preserveState: true,
            preserveScroll: true,
            only: ["georesult"],
            onFinish: () => {
                loading.address = false;
            },
        },
    );
};

const editGrades = (el) => {
    editBtn.grades = true;
    options.subjects = el.optionSubject;
    options.grades = el.optionGrades;
};

const addGrades = () => {
    gradesForm.subjects.push({
        id: null,
        subject: [],
        grade: [],
    });
};

const tabChangeGet = (el) => {
    router.reload({
        data: {
            tab: el,
        },
        only: ["academic"],
        onSuccess: () => {
            selectTerm.value = null;
        },
    });
};

const getGrades = (item, key) => {
    selectTerm.value = item;
    gradesForm.reset();
    Object.values(item.grades).forEach((element) => {
        gradesForm.subjects.push({
            id: element.id,
            subject: element.subject || element,
            grade: element.grade || [],
        });
    });
};

const removeGrades = (index, item) => {
    gradesForm.subjects.splice(index, 1);

    if (item.id != null) {
        router.post(route("scholar.grade-delete", { id: item.id }), {
            preserveScroll: true,
            onSuccess: () => {
                console.log("Grade record deleted successfully");
            },
        });
    }
};

const updateGrades = () => {
    gradesForm.post(
        route("scholar.grade-update", { id: selectTerm.value.term_id }),
        {
            preserveScroll: true,
            onSuccess: () => {
                editBtn.grades = false;

                gradesForm.reset();
            },
        },
    );
};
watch(
    () => page.props?.scholarDetails,
    (newVal) => {
        personalInfo.spas_no = newVal?.spas_no ?? null;
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
    },
    { immediate: true },
);
</script>
