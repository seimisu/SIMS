import { ref, onMounted, onUnmounted } from "vue";
import { router } from "@inertiajs/vue3";
import { useToast } from "primevue/usetoast";

export function useInactivityGuard({
    inactivityTimeout = 15 * 60 * 1000,
    warningTimeout = 60,
}) {
    const toast = useToast();

    const remainingSeconds = ref(warningTimeout);

    let inactivityTimer = null;
    let countdownTimer = null;
    let toastId = null;

    const events = ["mousemove", "keydown", "click", "scroll", "touchstart"];

    const resetTimer = () => {
        clearTimeout(inactivityTimer);
        clearInterval(countdownTimer);

        remainingSeconds.value = warningTimeout;

        if (toastId) {
            toast.remove(toastId);
            toastId = null;
        }

        inactivityTimer = setTimeout(() => {
            showWarning();
        }, inactivityTimeout);
    };

    const showWarning = () => {
        remainingSeconds.value = warningTimeout;

        toast.add({
            severity: "warn",
            summary: "Session Expiring",
            detail: `You will be logged out in ${remainingSeconds.value} seconds due to inactivity.`,
            life: warningTimeout * 1000,
        });
        countdownTimer = setInterval(() => {
            remainingSeconds.value--;

            if (remainingSeconds.value <= 0) {
                logout();
            }
        }, 1000);
    };

    const staySignedIn = () => {
        resetTimer();
    };

    const logout = () => {
        clearTimeout(inactivityTimer);
        clearInterval(countdownTimer);

        if (toastId) {
            toast.remove(toastId);
            toastId = null;
        }

        router.post(route("logout"));
    };

    onMounted(() => {
        events.forEach((event) => {
            window.addEventListener(event, resetTimer);
        });

        resetTimer();
    });

    onUnmounted(() => {
        clearTimeout(inactivityTimer);
        clearInterval(countdownTimer);

        events.forEach((event) => {
            window.removeEventListener(event, resetTimer);
        });
    });

    return {
        remainingSeconds,
        staySignedIn,
        logout,
    };
}
