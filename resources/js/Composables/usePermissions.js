import { usePage } from "@inertiajs/vue3";

export function usePermissions() {
    const page = usePage();

    const can = (permission) =>
        (page.props.permissions ?? []).includes(permission);

    const canAny = (permissions) => permissions.some((permission) => can(permission));

    const canAll = (permissions) => permissions.every((permission) => can(permission));

    return {
        can,
        canAny,
        canAll,
    };
}
