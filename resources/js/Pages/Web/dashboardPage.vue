<template>
    <Head title="Dashboard" />
    <AuthLayout>
        <div
            class="mx-auto flex flex-col"
            v-if="page.props.dashboardType === 'admin'"
        >
            <AdminDashboardModule :summary="page.props.adminSummary" />
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
            <div class="flex-1">
                <SchoolCoordinatorDashboardModule />
            </div>
        </div>
        <div
            class="mx-auto min-h-full"
            v-else-if="page.props.dashboardType === 'cashier'"
        >
            <CashierDashboardModule />
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
import CashierDashboardModule from "../../Modules/Others/CashierDashboardModule.vue";
import { ref } from "vue";
import { IconMapPin, IconSchool } from "@tabler/icons-vue";
import ScholarshipDashboardModule from "../../Modules/Others/ScholarshipDashboardModule.vue";
import AdminDashboardModule from "../../Modules/Others/AdminDashboardModule.vue";

const page = usePage();
</script>
