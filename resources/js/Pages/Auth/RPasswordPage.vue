<template>

    <Head title="Reset Password" />
    <SingleLayout>
        <template #form>
            <Stepper :value="stepper" linear>
                <StepPanels>

                    <!-- Step 1 -->
                    <StepPanel :value="1">

                        <form @submit.prevent="submit">

                            <div
                                class="lg:w-120 lg:h-m-100 flex justify-between flex-col gap-5 p-1"
                            >

                                <!-- Header -->
                                <div
                                    class="text-center flex justify-center items-center flex-col"
                                >

                                    <div
                                        class="rounded-full p-2 border border-blue-400 m-3"
                                    >
                                        <IconLock
                                            :size="25"
                                            :stroke-width="1.25"
                                            class="text-blue-500"
                                        />
                                    </div>

                                    <span
                                        class="text-lg text-gray-800 text-shadow"
                                    >
                                        Reset Password
                                    </span>

                                    <p
                                        class="text-gray-500 text-sm font-light"
                                    >
                                        Please fillup the form to reset your
                                        password.
                                    </p>

                                </div>

                                <!-- Form -->
                                <ResetPasswordForm
                                    :form="form"
                                    :v="$v"
                                />

                                <!-- Submit -->
                                <Button
                                    fluid
                                    :label="
                                        form.processing
                                            ? 'Setting password...'
                                            : 'Set Password'
                                    "
                                    size="small"
                                    raised
                                    type="submit"
                                    :loading="form.processing"
                                    :disabled="form.processing"
                                />

                            </div>

                        </form>

                    </StepPanel>

                    <!-- Step 2 -->
                    <StepPanel :value="2">

                        <div
                            class="lg:w-120 lg:h-m-100 flex justify-between flex-col gap-5 p-1"
                        >

                            <div
                                class="text-center mb-10 flex justify-center items-center flex-col"
                            >

                                <div
                                    class="rounded-full p-2 border border-green-400 m-3"
                                >
                                    <IconCheck
                                        :size="25"
                                        :stroke-width="1.25"
                                        class="text-green-500"
                                    />
                                </div>

                                <span
                                    class="text-lg text-gray-800 text-shadow mb-5"
                                >
                                    Password Reset Successful
                                </span>

                                <p
                                    class="text-gray-500 text-sm font-light"
                                >
                                    Your password has been successfully
                                    updated. You may now log in using your new
                                    credentials.
                                </p>

                            </div>

                            <Button
                                fluid
                                label="Go to Login"
                                size="small"
                                raised
                                @click="$inertia.visit(route('login'))"
                            />

                        </div>

                    </StepPanel>

                </StepPanels>

            </Stepper>

        </template>
    </SingleLayout>

</template>

<script setup>
import { computed, ref } from "vue";
import { Head, useForm, usePage } from "@inertiajs/vue3";

import useVuelidate from "@vuelidate/core";
import {
    required,
    sameAs,
    minLength,
    helpers,
} from "@vuelidate/validators";

import { useToast } from "primevue/usetoast";

import SingleLayout from "../../Layouts/SingleLayout.vue";
import ResetPasswordForm from "../../Modules/Others/ResetPasswordForm.vue";

import { IconCheck, IconLock } from "@tabler/icons-vue";

const page = usePage();

const toast = useToast();

const stepper = ref(1);

/*
|--------------------------------------------------------------------------
| Form
|--------------------------------------------------------------------------
*/

const form = useForm({
    token: page.props?.token,
    email: page.props?.email,
    password: "",
    password_confirmation: "",
});

/*
|--------------------------------------------------------------------------
| Validation Rules
|--------------------------------------------------------------------------
*/

const rules = computed(() => ({
    password: {
        required: helpers.withMessage(
            "Password is required",
            required
        ),

        minLength: helpers.withMessage(
            "Password must be at least 8 characters",
            minLength(8)
        ),
    },

    password_confirmation: {
        required: helpers.withMessage(
            "Please confirm your password",
            required
        ),

        sameAsPassword: helpers.withMessage(
            "Passwords do not match",
            sameAs(form.password)
        ),
    },
}));

/*
|--------------------------------------------------------------------------
| Vuelidate
|--------------------------------------------------------------------------
*/

const $v = useVuelidate(rules, form);

/*
|--------------------------------------------------------------------------
| Submit
|--------------------------------------------------------------------------
*/

const submit = () => {

    $v.value.$touch();

    if ($v.value.$invalid) {

        toast.add({
            severity: "warn",
            summary: "Validation Error",
            detail: "Please fix the errors in the form before submitting.",
            life: 3000,
        });

        return;
    }

    form.post(route("password.update"), {

        onSuccess: () => {

            form.reset(
                "password",
                "password_confirmation"
            );

            $v.value.$reset();

            stepper.value = 2;

        },

        onError: (errors) => {

            if (errors.email) {

                toast.add({
                    severity: "error",
                    summary: "Reset Password Failed",
                    detail: errors.email,
                    life: 4000,
                });

            } else if (errors.password) {

                toast.add({
                    severity: "error",
                    summary: "Reset Password Failed",
                    detail: errors.password,
                    life: 4000,
                });

            } else {

                toast.add({
                    severity: "error",
                    summary: "Reset Password Failed",
                    detail: "Unable to reset your password. Please try again.",
                    life: 4000,
                });

            }

        },

    });

};
</script>