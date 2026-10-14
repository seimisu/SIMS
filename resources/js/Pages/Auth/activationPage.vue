<template>

    <Head title="Activate Account" />
    <main class="w-full min-h-screen flex flex-col bak bg-[#ffffff] dark:text-gray-300 dark:bg-gray-800"
        style="background-image: url(&quot;/images/backgroundLogin.png&quot;)">
        <div class="flex-1 flex w-full h-full items-center justify-center">
            <div class="bg-white dark:!bg-gray-800 p-5 rounded-2xl shadow lg:w-[30%]">
                <div class="flex flex-col gap-2">
                    <div class="flex flex-col items-center mb-10 w-full text-slate-800">
                        <div class="flex items-center gap-2">
                            <Avatar size="large" image="/images/seilogo.png" />
                            <Avatar size="large" image="/images/dostlogo.svg" />
                        </div>

                        <div
                            class="font-semibold text-2xl md:text-[30px] text-gray-600 text-center antialiased dark:!text-gray-300 p-2">
                            Activate your
                            <span class="text-blue-600">Account!</span>
                        </div>
                        <div class="w-full text-center text-[14px] text-gray-400">
                            You're almost there! Just finalize your activation
                            to start using your account.
                        </div>
                    </div>

                    <form @submit.prevent="submitForm">
                        <div class="flex flex-col w-full gap-3">
                            <div class="flex items-center gap-3 justify-center">
                                <div class="relative inline-block">
                                    <Avatar class="!w-30 !h-30" shape="circle" :image="photoPreview" />

                                    <DefaultButton type="button" :icon="IconPlus" @click="initializePhoto($event)"
                                        size="small" raised rounded class="absolute bottom-3 right-8 translate-y-1/4" />
                                    <input @change="uploadPhotoUpdate" type="file" ref="fileInput" accept="image/*"
                                        style="display: none !important;"></input>
                                </div>
                            </div>

                            <div class="flex items-center gap-3">
                                <TextInput label="First Name" v-model="registrationForm.fname" uppercase></TextInput>
                                <TextInput label="Last Name" uppercase v-model="registrationForm.lname"></TextInput>
                            </div>
                            <SelectInput label="Agency" v-model="registrationForm.agency"
                                :options="page.props.agencyOption" :clearable="true" capitalize @update:model-value="onSchoolChange">
                            </SelectInput>
                            <TextInput label="Designation" capitalize v-model="registrationForm.designation">
                            </TextInput>
                            <SelectInput label="School Assigned" v-model="registrationForm.school"
                                v-if="page.props?.user?.role_array?.name == 'School Coordinator'" :disable="registrationForm.agency != null ? false : true" filter
                                :options="page.props.schoolOption" :clearable="true" capitalize >
                            </SelectInput>
                            <MaskInput label="Contact No." :placeholder="'(09__) ___-____'"
                                v-model="registrationForm.contact" :mask="'(9999) 999-9999'">
                            </MaskInput>
                            <Divider type="dashed" class="!m-0"></Divider>
                            <TextInput label="Email" disabled v-model="registrationForm.email"></TextInput>

                            <PasswordInput label="Password" v-model="registrationForm.password" :feedback="true"
                                toggle-icon />
                            <PasswordInput label="Confirm Password" v-model="registrationForm.cpassword"
                                :feedback="false" />
                            <DefaultMessages v-if="registrationForm.hasErrors" :message="registrationForm.errors"
                                message-type="error" :icon="IconAlertCircle"></DefaultMessages>
                            <DefaultButton label="Update account" size="small" class="mt-5 w-full"
                                :loading="registrationForm.processing" :disabled="registrationForm.processing" raised />
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>
    <Dialog v-model:visible="photoDialog" modal header="Crop your new profile picture" class="lg:!w-[30%]">
        <div class="flex w-full flex-col overflow-hidden">

            <Cropper class="cropper " ref="cropper" :stencil-component="CircleStencil" :stencil-props="{
                handlers: {},
                aspectRatio: 1,
                resizable: true,
                previewClass: 'preview'
            }" :src="photoRaw" />

            <DefaultButton label="  Set profile picture" class="mt-5 w-full" :icon="IconPhoto" @click="cropPicture"
                type="button" />
        </div>

    </Dialog>
</template>
<script setup>
import { IconAlertCircle, IconPhoto, IconPlus } from "@tabler/icons-vue";
import { Head, router, useForm, usePage } from "@inertiajs/vue3";
import { Cropper, CircleStencil } from 'vue-advanced-cropper'
import { onBeforeUnmount, onMounted, ref } from "vue";

import SelectInput from "../../Components/inputs/SelectInput.vue";
import MaskInput from "../../Components/inputs/MaskInput.vue";
import TextInput from "../../Components/inputs/TextInput.vue";
import PasswordInput from "../../Components/inputs/PasswordInput.vue";
import DefaultButton from "../../Components/buttons/DefaultButton.vue";
import DefaultMessages from "../../Components/messages/DefaultMessages.vue";
import 'vue-advanced-cropper/dist/style.css'

const page = usePage();
const isCashier = page.props?.user?.role_array?.name?.toLowerCase() === "cashier";
const defaultCashierAgency = (page.props.agencyOption ?? []).find(
    (agency) => agency.name?.trim().toLowerCase() === "science education institute",
);
const isDark = ref(false);
const photoDialog = ref(false);
const cropper = ref(null)
const fileInput = ref(null);
const photoRaw = ref(null);
const photoPreview = ref(null);
const registrationForm = useForm({
    photo: null,
    fname: page.props?.user.profile.fname ?? null,
    lname: page.props?.user.profile.lname ?? null,
    email: page.props?.user.email ?? null,
    designation: null,
    school: null,
    agency: isCashier ? defaultCashierAgency ?? null : null,
    contact: null,
    password: null,
    is_coordinator: page.props?.user?.role_array.name == 'School Coordinator' ? true : false,
    cpassword: null,
});


const initializePhoto = () => {
    fileInput.value?.click();
};
const submitForm = () => {
    registrationForm.post(route("activation.update", { id: page.props.user?.id }), {
        forceFormData: true,
        onSuccess: () => {
            registrationForm.reset();
            photoRaw.value = null;
            if (photoPreview.value) URL.revokeObjectURL(photoPreview.value);
            photoPreview.value = null;
        },
    });
};


const cropPicture = async () => {
    const canvas = cropper.value?.getResult()?.canvas;
    if (!canvas) {
        registrationForm.setError("photo", "Select and crop a profile picture before continuing.");
        return;
    }

    const blob = await new Promise((resolve) => canvas.toBlob(resolve, "image/jpeg", 0.9));
    if (!blob) {
        registrationForm.setError("photo", "Unable to process this profile picture. Please try another image.");
        return;
    }

    if (photoPreview.value) URL.revokeObjectURL(photoPreview.value);
    registrationForm.clearErrors("photo");
    registrationForm.photo = new File([blob], "profile.jpg", { type: "image/jpeg" });
    photoPreview.value = URL.createObjectURL(blob);
    photoDialog.value = false;
}

const uploadPhotoUpdate = (event) => {
    const input = event.target;
    const file = input.files?.[0];
    input.value = "";

    if (!file) return;

    registrationForm.photo = null;
    photoRaw.value = null;
    if (photoPreview.value) URL.revokeObjectURL(photoPreview.value);
    photoPreview.value = null;

    const supportedTypes = ["image/jpeg", "image/png", "image/webp"];
    if (!supportedTypes.includes(file.type)) {
        registrationForm.setError("photo", "Choose a JPG, PNG, or WebP image.");
        return;
    }

    if (file.size > 2 * 1024 * 1024) {
        registrationForm.setError("photo", "The profile picture must be 2 MB or smaller.");
        return;
    }

    registrationForm.clearErrors("photo");
    const reader = new FileReader();
    reader.onload = () => {
        photoRaw.value = reader.result;
        photoDialog.value = true;
    };
    reader.onerror = () => registrationForm.setError("photo", "Unable to read this image. Please try another file.");
    reader.readAsDataURL(file);
};

function applyTheme() {
    document.documentElement.classList.toggle("dark", isDark.value);
}

function onSchoolChange(agency) {
    const selectedAgency = agency ?? (isCashier ? defaultCashierAgency : null);

    if (!agency && selectedAgency) {
        registrationForm.agency = selectedAgency;
    }

    router.reload({ only: ["schoolOption"], data: { agency: selectedAgency?.id ?? null } });
}





onMounted(() => {
    isDark.value = localStorage.getItem("theme") === "dark";
    applyTheme();
});

onBeforeUnmount(() => {
    if (photoPreview.value) URL.revokeObjectURL(photoPreview.value);
});
</script>
<style>
/* .cropper {
    width: 400px !important;
    height: 400px !important;
} */

.preview {
    border: dashed 2px rgba(0, 0, 0, 0.25) !important;
}
</style>
