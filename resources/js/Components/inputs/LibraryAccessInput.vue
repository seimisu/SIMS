<template>
    <div class="flex flex-col gap-3">
        <div class="text-sm font-semibold">Availability</div>

        <div v-if="!regionLocked" class="flex items-center justify-between rounded-md border px-3 py-2">
            <span class="text-sm">Available to everyone</span>
            <DefaultToggle v-model="availableAll" :check-icon="IconCheck" :un-check-icon="IconX" />
        </div>

        <SelectMultiInput
            v-model="selectedRegions"
            label="Regions"
            :options="regionOptions"
            :disable="availableAll || regionLocked"
            filter
        />

        <div v-if="!availableAll" class="flex flex-col gap-2">
            <div
                v-for="scope in regionScopes"
                :key="scope.region.id"
                class="flex flex-col gap-2 border-l-2 border-blue-400 pl-3"
            >
                <SelectInput
                    v-model="scope.school_coverage"
                    :label="`${scope.region.name} school coverage`"
                    :options="schoolCoverageOptions"
                />
                <SelectMultiInput
                    v-if="scope.school_coverage?.id === 'specific'"
                    v-model="scope.schools"
                    label="Specific schools"
                    :options="schoolsFor(scope.region.id)"
                    placeholder="Select schools"
                    filter
                />
            </div>
        </div>

        <SelectMultiInput
            v-model="scholarships"
            label="Scholarship Program"
            :options="scholarshipOptions"
            :disable="availableAll"
            filter
        />
        <SelectMultiInput
            v-model="programs"
            label="Program"
            :options="programOptions"
            :disable="availableAll"
            filter
        />
    </div>
</template>

<script setup>
import { computed, watch } from "vue";
import { IconCheck, IconX } from "@tabler/icons-vue";
import DefaultToggle from "../toggleswitches/DefaultToggle.vue";
import SelectInput from "./SelectInput.vue";
import SelectMultiInput from "./SelectMultiInput.vue";

const props = defineProps({
    regionOptions: { type: Array, default: () => [] },
    schoolOptions: { type: Array, default: () => [] },
    scholarshipOptions: { type: Array, default: () => [] },
    programOptions: { type: Array, default: () => [] },
    regionLocked: Boolean,
    lockedRegion: { type: Object, default: null },
});

const availableAll = defineModel("availableAll", { type: Boolean, default: false });
const regionScopes = defineModel("regionScopes", { type: Array, default: () => [] });
const scholarships = defineModel("scholarships", { type: Array, default: () => [] });
const programs = defineModel("programs", { type: Array, default: () => [] });
const schoolCoverageOptions = [
    { id: "all", name: "All schools in this region" },
    { id: "specific", name: "Specific schools" },
];

const selectedRegions = computed({
    get: () => regionScopes.value.map((scope) => scope.region),
    set: (regions) => {
        regionScopes.value = regions.map((region) => {
            const existing = regionScopes.value.find(
                (scope) => String(scope.region.id) === String(region.id),
            );

            return existing ?? {
                region,
                school_coverage: schoolCoverageOptions[0],
                schools: [],
            };
        });
    },
});

const schoolsFor = (regionId) =>
    props.schoolOptions.filter(
        (school) => String(school.region_id) === String(regionId),
    );

watch(
    () => [props.regionLocked, props.lockedRegion],
    () => {
        if (!props.regionLocked || !props.lockedRegion) return;

        availableAll.value = false;
        const existing = regionScopes.value.find(
            (scope) => String(scope.region.id) === String(props.lockedRegion.id),
        );
        regionScopes.value = [
            existing ?? {
                region: props.lockedRegion,
                school_coverage: schoolCoverageOptions[0],
                schools: [],
            },
        ];
    },
    { immediate: true, deep: true },
);
</script>
