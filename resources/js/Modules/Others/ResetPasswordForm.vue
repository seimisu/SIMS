<template>
    <div class="flex flex-col gap-4">
        <div class="flex flex-col" v-if="ShowCurrent">
            <div class="flex gap-1 items-center">
                <div class="text-xs font-light">Current Password</div>
            </div>
            <IconField>
                <InputIcon>
                    <IconLock :size="20" :stroke-width="1.25" />
                </InputIcon>
                <Password size="small" fluid autocomplete="false" mask-icon v-model="form.current"
                    placeholder="•••••••••" :feedback="false">
                </Password>
            </IconField>

        </div>
        <div class="flex flex-col">
            <div class="flex gap-1 items-center">
                <div class="text-xs font-light">New Password</div>
            </div>
            <IconField>
                <InputIcon>
                    <IconLock :size="20" :stroke-width="1.25" />
                </InputIcon>
                <Password size="small" fluid autocomplete="false" toggle-mask v-model="form.password"
                    placeholder="•••••••••" :feedback="false">
                </Password>
            </IconField>
        </div>

        <div class="flex flex-col">

            <div class="flex gap-1 items-center">
                <div class="text-xs font-light">Confirm Password</div>
                <span v-show="$v.password_confirmation.$error" class="text-red-600">*</span>
            </div>
            <IconField>
                <InputIcon>
                    <IconLock :size="20" :stroke-width="1.25" />
                </InputIcon>
                <Password size="small" fluid autocomplete="false" v-model="form.password_confirmation"  class="bg-white!"
                    placeholder="•••••••••" :feedback="false">
                </Password>
            </IconField>

        </div>
        <div class="rounded-lg">

            <ul class="text-xs text-gray-500 p-1" v-for="item, key in validationList" :key="key">
                <li class="flex items-center gap-1">

                    <IconCircleCheck
                        v-if="!$v.password.$errors.find(e => e.$validator === item.validator) && $v.password.$dirty && form.password"
                        :size="18" :stroke-width="1.5" class="text-green-600" />
                    <IconCircleX v-else :size="18" :stroke-width="1.5" />

                    {{ item.message }}
                </li>
            </ul>
        </div>
    </div>
</template>
<script setup>

const props = defineProps({
    form: Object,
    ShowCurrent: {
        type: Boolean,
        default: false
    }
});
import { IconLock, IconCircleCheck, IconCircleX } from '@tabler/icons-vue';
import useVuelidate from '@vuelidate/core';
import { helpers, required, minLength, sameAs } from '@vuelidate/validators';
import { computed, reactive, ref } from 'vue';
const newPassword = computed(() => props.form.password);
const rules = computed(() => ({
    password: {
        required: helpers.withMessage('New password is required.', required),
        minLength: helpers.withMessage(
            'The password must be at least 8 characters.',
            minLength(8)
        ),
        hasNumber: helpers.withMessage(
            'The password must include at least one number.',
            value => !value || /\d/.test(value)
        ),

        hasUpperCase: helpers.withMessage(
            'The password must include at least one uppercase letter.',
            value => !value || /[A-Z]/.test(value)
        ),

        hasLowerCase: helpers.withMessage(
            'The password must include at least one lowercase letter.',
            value => !value || /[a-z]/.test(value)
        ),
        hasSpecialChar: helpers.withMessage(
            'The password must include at least one special character.',
            value => !value || /[!@#$%^&*(),.?":{}|<>]/.test(value)
        ),
    },
    password_confirmation: {
        sameAsNew: helpers.withMessage(
            'Confirm password must match the new password.',
            sameAs(newPassword)
        ),
    }
}));


const validationList = reactive([
    {
        message: 'The password must be at least 8 characters.',
        validator: 'minLength'
    },
    {
        message: 'The password must include at least one number.',
        validator: 'hasNumber'
    },
    {
        message: 'The password must include at least one uppercase letter.',
        validator: 'hasUpperCase'
    },
    {
        message: 'The password must include at least one lowercase letter.',
        validator: 'hasLowerCase'
    },
    {
        message: 'The password must include at least one special character.',
        validator: 'hasSpecialChar'
    }
])

const $v = useVuelidate(rules, props.form, { $autoDirty: true });

const validate = () => {
    $v.value.$touch();
    if ($v.value.$invalid) {
        return false;
    }
    return true;
}

const clear = () => {
    props.form.password = null;
    props.form.password_confirmation = null;
    $v.value.$reset();
}

defineExpose({
    validate,
    clear
})

</script>