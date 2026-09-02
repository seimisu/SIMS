<template>
    <Dialog
        v-model:visible="visible"
        modal
        :style="{ width: '30rem' }"
        close-on-escape
        :breakpoints="{ '1199px': '75vw', '575px': '90vw' }"
    >
        <template #header>
            <div
                class="flex items-center gap-1 font-semibold bg-slate-100 p-2 shadow rounded-lg"
            >
                <IconLockQuestion :size="20" :stroke-width="2" />
                <span class="text-sm">Forgot Password</span>
            </div>
        </template>

        <template #default>
            <Stepper :value="stepper" linear>
                <StepPanels>
                    <StepPanel :value="1">
                        <form @submit.prevent="submit" v-focustrap>
                            <div
                                class="flex flex-col gap-5 justify-between h-50"
                            >
                                <!-- Description -->
                                <span
                                    class="text-surface-500 text-justify text-sm indent-10 block text-gray-500"
                                >
                                    Please provide your registered email
                                    address. We will send a
                                    <b>reset link</b>
                                    to verify your identity and help you reset
                                    your password.
                                </span>

                                <!-- Email -->
                                <div>
                                    <div class="flex gap-1 items-center">
                                        <div class="text-sm font-light">
                                            Email
                                        </div>

                                        <span
                                            v-if="form.errors.email"
                                            class="text-red-500"
                                            v-tooltip.top="form.errors.email"
                                        >
                                            *
                                        </span>
                                    </div>

                                    <IconField>
                                        <InputIcon class="flex items-center">
                                            <div>
                                                <IconMail
                                                    :size="20"
                                                    :stroke-width="1.25"
                                                />
                                            </div>
                                        </InputIcon>

                                        <InputText
                                            v-model="form.email"
                                            fluid
                                            placeholder="Your email"
                                            :disabled="form.processing"
                                            size="small"
                                            autofocus
                                            autocomplete="email"
                                        />
                                    </IconField>
                                </div>

                                <!-- Submit -->
                                <Button
                                    :label="
                                        form.processing ? 'Sending...' : 'Send'
                                    "
                                    size="small"
                                    raised
                                    type="submit"
                                    :loading="form.processing"
                                />
                            </div>
                        </form>
                    </StepPanel>
                </StepPanels>
            </Stepper>
        </template>
    </Dialog>
</template>

<script setup>
import { useForm } from "@inertiajs/vue3";
import { IconLockQuestion, IconMail } from "@tabler/icons-vue";
import { ref } from "vue";
import { useToast } from "primevue/usetoast";

const visible = defineModel("visible");

const stepper = ref(1);

const toast = useToast();

const form = useForm({
    email: "",
});

const submit = () => {
    form.post(route("password.store"), {
        preserveScroll: true,

        onSuccess: () => {
            form.reset();
            form.clearErrors();

            visible.value = false;

            toast.add({
                severity: "success",
                summary: "Reset Link Sent",
                detail: "A password reset link has been sent to your email address.",
                life: 3000,
            });
        },

        onError: (errors) => {
            toast.add({
                severity: "error",
                summary: "Failed",
                detail: errors.email ?? "Failed to send reset link.",
                life: 3000,
            });
        },
    });
};
</script>
