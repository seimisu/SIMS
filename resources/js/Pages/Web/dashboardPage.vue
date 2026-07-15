<template>
    <Head title="Dashboard" />
    <AuthLayout>
        <div class="mx-auto" v-if="page.props.dashboardType === 'admin'">
            <h1 class="text-2xl font-bold mb-4">Welcome to the Dashboard</h1>
            <p class="text-gray-700">
                This is your dashboard where you can manage your activities.
            </p>
        </div>
        <div
            class="mx-auto flex flex-col"
            v-else-if="page.props.dashboardType === 'regional'"
        >
            <RegionStaffDashboardModule class="h-full" />
        </div>
        <div
            class="mx-auto flex flex-col"
            v-else-if="page.props.dashboardType === 'school_coordinator'"
        >
            <div class="w-full flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <Avatar size="large" class="!bg-gray-200 !rounded-xl">
                        <IconSchool size="30" />
                    </Avatar>
                    <div class="">
                        <h1 class="text-xl font-semibold">
                            {{ page.props.schoolDetails?.generated_name }}
                        </h1>
                        <div
                            class="text-sm text-gray-600 flex items-center gap-1"
                        >
                            <IconMapPin size="16" />
                            <span>
                                {{
                                    page.props.schoolDetails?.address
                                        ?.full_address?.name
                                }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>
            <div class="flex-1">
                <SchoolCoordinatorDashboardModule />
            </div>
        </div>
        <div class="mx-auto" v-else>
            <ScholarshipDashboardModule></ScholarshipDashboardModule>
        </div>
    </AuthLayout>
</template>
<script setup>
import { Head, usePage } from "@inertiajs/vue3";
import AuthLayout from "../../Layouts/AuthLayout.vue";
import RegionStaffDashboardModule from "../../Modules/Others/RegionStaffDashboardModule.vue";
import SchoolCoordinatorDashboardModule from "../../Modules/Others/SchoolCoordinatorDashboardModule.vue";
import { ref } from "vue";
import { IconMapPin, IconSchool } from "@tabler/icons-vue";
import ScholarshipDashboardModule from "../../Modules/Others/ScholarshipDashboardModule.vue";

const page = usePage();
</script>
