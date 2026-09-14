<script setup>
defineProps({
    visible: {
        type: Boolean,
        default: false,
    },

    remainingSeconds: {
        type: Number,
        default: 60,
    },
});

const emit = defineEmits(["stay-signed-in", "logout"]);
</script>

<template>
    <Teleport to="body">
        <div
            v-if="visible"
            class="fixed inset-0 z-[9999] flex items-center justify-center bg-black/50 backdrop-blur-sm"
        >
            <div
                class="w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-gray-900"
            >
                <div class="mb-4 flex items-center gap-3">
                    <div
                        class="flex h-12 w-12 items-center justify-center rounded-full bg-yellow-100 text-yellow-600"
                    >
                        ⚠
                    </div>

                    <div>
                        <h2 class="text-lg font-semibold">Session Expiring</h2>

                        <p class="text-sm text-gray-500">
                            You've been inactive for a while.
                        </p>
                    </div>
                </div>

                <p class="text-sm text-gray-600 dark:text-gray-300">
                    You will be automatically logged out in
                    <strong>
                        {{ remainingSeconds }}
                    </strong>
                    seconds.
                </p>

                <div class="mt-6 flex justify-end gap-3">
                    <button
                        type="button"
                        class="rounded-lg border px-4 py-2 text-sm"
                        @click="emit('logout')"
                    >
                        Logout
                    </button>

                    <button
                        type="button"
                        class="rounded-lg bg-primary-600 px-4 py-2 text-sm font-medium text-white"
                        @click="emit('stay-signed-in')"
                    >
                        Stay Signed In
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>
