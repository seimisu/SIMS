<template>
  <Head title="My Profile" />

  <AuthLayout>
    <div class="flex flex-col w-full h-full gap-6">
      <!-- Header -->
      <HeaderModule
        title="My Profile"
        description="Manage your personal information, account details, and security settings."
      />

      <!-- Profile Content -->
      <div class="grid grid-cols-1 xl:grid-cols-3 gap-6">
        <!-- ================================================= -->
        <!-- PROFILE CARD -->
        <!-- ================================================= -->

        <Card class="xl:col-span-1 dark:bg-slate-700! dark:text-white!">
          <template #content>
            <div class="flex flex-col items-center text-center">
              <!-- Avatar -->
              <div class="relative mb-4">
                <Avatar
                  v-if="user.profile.avatar === null"
                  :label="user.email.charAt(0).toUpperCase()"
                  class="!w-28 !h-28 !text-3xl bg-primary text-white"
                  shape="circle"
                />

                <Avatar
                  v-else
                  style="background-color: #dee9fc; color: #1a2551"
                  class="!w-28 !h-28"
                  shape="circle"
                  :image="user.profile.avatar_url"
                />

                <Button
                  icon="pi pi-camera"
                  rounded
                  severity="secondary"
                  size="small"
                  class="!absolute bottom-0 right-0 !w-9 !h-9"
                  @click="openPhotoDialog"
                />
              </div>

              <!-- Name -->
              <h2 class="text-xl font-semibold text-surface-900">
                {{ user.profile.fullname }}
              </h2>

              <p class="text-sm text-surface-500 mt-1">
                {{ user.email }}
              </p>

              <!-- Role -->
              <Tag :value="user.role_array?.name" severity="info" class="mt-3" />

              <Divider />

              <!-- Profile Summary -->
              <div class="w-full space-y-4 flex justify-between">
                <div>
                  <span class="text-xs text-surface-500"> Account Status </span>

                  <div class="mt-1">
                    <Tag value="Active" severity="success" />
                  </div>
                </div>

                <div>
                  <span class="text-xs text-surface-500"> Member Since </span>

                  <p class="font-medium mt-1">
                    {{ profile.memberSince }}
                  </p>
                </div>
              </div>
            </div>
          </template>
        </Card>

        <!-- ================================================= -->
        <!-- ACCOUNT INFORMATION -->
        <!-- ================================================= -->

        <Card class="xl:col-span-2 dark:bg-slate-700! dark:text-white!">
          <template #title>
            <div class="flex items-center gap-3">
              <div
                class="w-10 h-10 rounded-lg bg-primary/10 flex items-center justify-center"
              >
                <i class="pi pi-user text-primary"></i>
              </div>

              <div>
                <h3 class="text-lg font-semibold">Personal Information</h3>

                <p class="text-sm text-surface-500 font-normal">
                  Update your personal account information.
                </p>
              </div>
            </div>
          </template>

          <template #content>
            <form @submit.prevent="updateProfile" class="space-y-5 mt-5">
              <!-- Name -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <TextInput
                  label="First Name"
                  v-model="form.firstName"
                  :disabled="disable.userDetails"
                  uppercase
                />

                <TextInput
                  label="Last Name"
                  v-model="form.lastName"
                  :disabled="disable.userDetails"
                  uppercase
                />
              </div>

              <!-- Email / Phone -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <TextInput
                  label="Email Address"
                  v-model="form.email"
                  type="email"
                  :disabled="disable.userDetails"
                />

                <TextInput
                  label="Contact Number"
                  v-model="form.phone"
                  :disabled="disable.userDetails"
                />
              </div>

              <!-- Position / Office -->
              <div class="grid grid-cols-1 md:grid-cols-2 gap-5">
                <TextInput
                  label="Position"
                  v-model="form.position"
                  :disabled="disable.userDetails"
                />

                <SelectInput
                  label="Office / Unit"
                  :options="agencyOption"
                  v-model="form.office"
                  :disable="disable.userDetails"
                />
              </div>

              <!-- Actions -->
              <div class="flex justify-end gap-2 pt-4 border-t border-surface-200">
                <template v-if="!disable.userDetails">
                  <Button
                    type="button"
                    label="Cancel"
                    severity="secondary"
                    outlined
                    @click="resetForm"
                  />

                  <Button type="submit" label="Save Changes" icon="pi pi-check" />
                </template>
                <template v-else>
                  <Button
                    type="submit"
                    label="Update Details"
                    @click="disable.userDetails = false"
                  />
                </template>
              </div>
            </form>
          </template>
        </Card>
      </div>

      <!-- ================================================= -->
      <!-- SECURITY -->
      <!-- ================================================= -->

      <Card class="dark:bg-slate-700! dark:text-white!">
        <template #title>
          <div class="flex items-center gap-3">
            <div
              class="w-10 h-10 rounded-lg bg-orange-100 flex items-center justify-center"
            >
              <i class="pi pi-shield text-orange-600"></i>
            </div>

            <div>
              <h3 class="text-lg font-semibold">Security</h3>

              <p class="text-sm text-surface-500 font-normal">
                Manage your password and account security.
              </p>
            </div>
          </div>
        </template>

        <template #content>
          <div class="grid grid-cols-1 lg:grid-cols-2 gap-5">
            <!-- Password -->
            <div class="border border-surface-200 rounded-xl p-5">
              <div class="flex items-start justify-between gap-4">
                <div>
                  <div class="flex items-center gap-2">
                    <i class="pi pi-lock text-primary"></i>

                    <h4 class="font-semibold">Password</h4>
                  </div>

                  <p class="text-sm text-surface-500 mt-2">
                    Change your password regularly to keep your account secure.
                  </p>
                </div>

                <Button
                  label="Change"
                  severity="secondary"
                  outlined
                  size="small"
                  @click="passwordDialog = true"
                />
              </div>
            </div>

            <!-- Two Factor -->
            <div class="border border-surface-200 rounded-xl p-5">
              <div class="flex items-start justify-between gap-4">
                <div>
                  <div class="flex items-center gap-2">
                    <i class="pi pi-key text-primary"></i>

                    <h4 class="font-semibold">Two-Factor Authentication</h4>
                  </div>

                  <p class="text-sm text-surface-500 mt-2">
                    Add an additional layer of security to your account.
                  </p>
                </div>

                <ToggleSwitch v-model="twoFactorEnabled" />
              </div>
            </div>
          </div>
        </template>
      </Card>

      <!-- ================================================= -->
      <!-- LOGIN ACTIVITY -->
      <!-- ================================================= -->

      <Card class="dark:bg-slate-700! dark:text-white!">
        <template #title>
          <div class="flex items-center justify-between">
            <div>
              <h3 class="text-lg font-semibold">Recent Login Activity</h3>

              <p class="text-sm text-surface-500 font-normal">
                Review recent activity on your account.
              </p>
            </div>

            <Button label="View All" text size="small" />
          </div>
        </template>

        <template #content>
          <div class="overflow-x-auto">
            <DataTable :value="logs" responsiveLayout="scroll">
              <!-- Device -->
              <Column field="device" header="Device">
                <template #body="{ data }">
                  <div class="flex items-center gap-3">
                    <div
                      class="w-9 h-9 rounded-lg bg-surface-100 flex items-center justify-center"
                    >
                      <i
                        :class="
                          data.device === 'iPhone' || data.device === 'iPad'
                            ? 'pi pi-mobile'
                            : data.device === 'Android'
                            ? 'pi pi-mobile'
                            : 'pi pi-desktop'
                        "
                      ></i>
                    </div>

                    <div>
                      <p class="font-medium">
                        {{ data.device }}
                      </p>

                      <p class="text-xs text-surface-500">
                        {{ data.browser ?? "Unknown Browser" }}
                      </p>
                    </div>
                  </div>
                </template>
              </Column>

              <!-- IP Address -->
              <Column field="ip_address" header="IP Address">
                <template #body="{ data }">
                  <span class="font-mono text-sm">
                    {{ data.ip_address }}
                  </span>
                </template>
              </Column>

              <!-- Last Activity -->
              <Column field="last_activity" header="Last Activity">
                <template #body="{ data }">
                  <span class="text-sm text-surface-600 dark:text-surface-300">
                    {{ data.last_activity }}
                  </span>
                </template>
              </Column>

              <!-- Status -->
              <Column field="status" header="Status">
                <template #body="{ data }">
                  <Tag
                    :value="data.status"
                    :severity="data.status === 'Current' ? 'success' : 'secondary'"
                  />
                </template>
              </Column>
            </DataTable>
          </div>
        </template>
      </Card>
    </div>
    <Dialog
      v-model:visible="passwordDialog"
      modal
      header="Change Password"
      :style="{ width: '450px' }"
    >
      <div class="flex flex-col gap-5 pt-2">
        <div class="flex flex-col gap-2">
          <label class="font-medium"> Current Password </label>

          <Password
            v-model="passwordForm.current"
            toggleMask
            class="w-full"
            inputClass="w-full"
          />
        </div>

        <div class="flex flex-col gap-2">
          <label class="font-medium"> New Password </label>

          <Password
            v-model="passwordForm.new"
            toggleMask
            class="w-full"
            inputClass="w-full"
          />
        </div>

        <div class="flex flex-col gap-2">
          <label class="font-medium"> Confirm New Password </label>

          <Password
            v-model="passwordForm.confirm"
            toggleMask
            class="w-full"
            inputClass="w-full"
          />
        </div>
      </div>

      <template #footer>
        <Button
          label="Cancel"
          severity="secondary"
          outlined
          @click="passwordDialog = false"
        />

        <Button label="Update Password" icon="pi pi-check" @click="changePassword" />
      </template>
    </Dialog>

    <!-- ================================================= -->
    <!-- PROFILE PHOTO DIALOG -->
    <!-- ================================================= -->

    <Dialog
      v-model:visible="photoDialog"
      modal
      header="Update Profile Photo"
      :style="{ width: '400px' }"
    >
      <div class="flex flex-col items-center gap-4 py-5">
        <!-- Profile Preview -->
        <Avatar
          v-if="photoPreview"
          :image="photoPreview"
          size="xlarge"
          shape="circle"
          class="!w-28 !h-28"
        />

        <Avatar
          v-else
          :label="initials"
          size="xlarge"
          shape="circle"
          class="!w-28 !h-28 !text-3xl bg-primary text-white"
        />

        <!-- File Upload -->
        <FileUpload
          mode="basic"
          name="profilePhoto"
          accept="image/jpeg,image/png,image/webp"
          :maxFileSize="2000000"
          chooseLabel="Choose Photo"
          :auto="false"
          @select="onPhotoSelect"
        />

        <p class="text-xs text-surface-500">JPG, PNG or WEBP. Maximum file size: 2MB.</p>

        <!-- Upload Button -->
        <Button
          label="Save Photo"
          icon="pi pi-upload"
          :loading="uploadingPhoto"
          :disabled="!selectedPhoto"
          @click="uploadPhoto"
        />
      </div>
    </Dialog>
  </AuthLayout>
</template>

<script setup>
import { computed, ref } from "vue";

import { Head, router, useForm } from "@inertiajs/vue3";

import AuthLayout from "../../Layouts/AuthLayout.vue";

import HeaderModule from "../../Modules/Others/HeaderModule.vue";
import SelectInput from "../../Components/inputs/SelectInput.vue";
import TextInput from "../../Components/inputs/TextInput.vue";
import Card from "primevue/card";
import Avatar from "primevue/avatar";
import Button from "primevue/button";
import InputText from "primevue/inputtext";
import Password from "primevue/password";
import ToggleSwitch from "primevue/toggleswitch";
import Tag from "primevue/tag";
import Divider from "primevue/divider";
import DataTable from "primevue/datatable";
import Column from "primevue/column";
import Dialog from "primevue/dialog";
import FileUpload from "primevue/fileupload";
import { useToast } from "primevue";

const toast = useToast();

const disable = ref({
  userDetails: true,
});
const props = defineProps({
  user: Object,
  agencyOption: Object,
  logs: Object,
});

const profile = ref({
  name: "John Rey Dalit",
  firstName: "John Rey",
  lastName: "Dalit",

  email: "johnrey@example.com",

  phone: "0912 345 6789",

  employeeId: "DOST-SEI-001",

  role: "Administrator",

  position: "Project Technical Specialist I",

  office: "DOST-SEI",

  memberSince: "January 2024",
});

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const selectedPhoto = ref(null);
const photoPreview = ref(null);
const uploadingPhoto = ref(false);

const onPhotoSelect = (event) => {
  const file = event.files[0];

  if (!file) {
    return;
  }

  selectedPhoto.value = file;

  // Create preview
  photoPreview.value = URL.createObjectURL(file);
};

const uploadPhoto = () => {
  if (!selectedPhoto.value) {
    return;
  }

  uploadingPhoto.value = true;

  router.post(
    route("profile.photo.update"),
    {
      profilePhoto: selectedPhoto.value,
    },
    {
      forceFormData: true,

      onSuccess: () => {
        photoDialog.value = false;

        selectedPhoto.value = null;

        if (photoPreview.value) {
          URL.revokeObjectURL(photoPreview.value);
        }

        photoPreview.value = null;
      },

      onFinish: () => {
        uploadingPhoto.value = false;
      },
    }
  );
};
const form = useForm({
  firstName: props.user?.profile.fname,
  lastName: props.user?.profile.lname,
  email: props.user?.email,
  phone: props.user?.profile.contact_no,
  position: props.user?.profile.designation,
  office: props.user?.profile.agency_array,
});

/*
|--------------------------------------------------------------------------
| Initials
|--------------------------------------------------------------------------
*/

const initials = computed(() => {
  return profile.value.name
    .split(" ")
    .map((name) => name.charAt(0))
    .slice(0, 2)
    .join("")
    .toUpperCase();
});

/*
|--------------------------------------------------------------------------
| Password
|--------------------------------------------------------------------------
*/

const passwordDialog = ref(false);

const passwordForm = ref({
  current: "",
  new: "",
  confirm: "",
});

/*
|--------------------------------------------------------------------------
| Two Factor
|--------------------------------------------------------------------------
*/

const twoFactorEnabled = ref(false);

/*
|--------------------------------------------------------------------------
| Profile Photo
|--------------------------------------------------------------------------
*/

const photoDialog = ref(false);

const openPhotoDialog = () => {
  photoDialog.value = true;
};

/*
|--------------------------------------------------------------------------
| Login Activities
|--------------------------------------------------------------------------
*/

const loginActivities = ref([
  {
    device: "Windows PC",
    browser: "Chrome 151",
    location: "Mandaluyong, Philippines",
    date: "Aug 18, 2026 02:15 PM",
    status: "Current",
    icon: "pi pi-desktop",
  },

  {
    device: "Windows PC",
    browser: "Chrome 151",
    location: "Mandaluyong, Philippines",
    date: "Aug 17, 2026 08:32 AM",
    status: "Completed",
    icon: "pi pi-desktop",
  },

  {
    device: "Mobile Device",
    browser: "Chrome Mobile",
    location: "Manila, Philippines",
    date: "Aug 15, 2026 06:41 PM",
    status: "Completed",
    icon: "pi pi-mobile",
  },
]);

/*
|--------------------------------------------------------------------------
| Actions
|--------------------------------------------------------------------------
*/

const updateProfile = () => {
  form
    .transform((data) => ({
      email: data.email,
      firstName: data.firstName,
      lastName: data.lastName,
      phone: data.phone,
      position: data.position,
      office_id: data.office?.id ?? null,
    }))
    .put(route("profile.update"), {
      preserveScroll: true,

      onSuccess: () => {
        toast.add({
          severity: "success",
          summary: "Profile Updated",
          detail: "Your profile has been successfully updated.",
          life: 3000,
        });
      },

      onError: (errors) => {
        console.log("Validation errors:", errors);
      },
      onFinish: () => {
        disable.value.userDetails = true;
      },
    });
};

const resetForm = () => {
  disable.value.userDetails = true;

  form.value = {
    firstName: props.user?.profile.fname,
    lastName: props.user?.profile.lname,
    email: props.user?.email,
    phone: props.user?.profile.contact_no,
    position: props.user?.profile.designation,
    office: props.user?.profile.agency_array,
  };
};

const changePassword = () => {
  if (
    !passwordForm.value.current ||
    !passwordForm.value.new ||
    !passwordForm.value.confirm
  ) {
    return;
  }

  if (passwordForm.value.new !== passwordForm.value.confirm) {
    return;
  }

  console.log("Changing password...", passwordForm.value);

  passwordDialog.value = false;

  passwordForm.value = {
    current: "",
    new: "",
    confirm: "",
  };
};
</script>
