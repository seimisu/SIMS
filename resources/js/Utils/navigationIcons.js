const iconsBySlug = {
    dashboard: "IconLayoutDashboard",
    scholars: "IconUsers",
    "scholar-data": "IconUsers",
    submissions: "IconClipboardCheck",
    "scholar-submission-management": "IconClipboardCheck",
    "scholar-submissions": "IconFileAnalytics",
    "scholar-profile-requests": "IconUserEdit",
    "scholar-landbank-requests": "IconBuildingBank",
    "financial-assistance": "IconWallet",
    "stipend-management": "IconWallet",
    stipends: "IconWallet",
    "scholar-import-review": "IconUserPlus",
    review: "IconUserPlus",
    "schools-courses": "IconSchool",
    universities: "IconBuildingCommunity",
    courses: "IconBooks",
    "content-library": "IconLibrary",
    documents: "IconFileDownload",
    "video-resources": "IconVideo",
    places: "IconMapPin",
    regions: "IconMap2",
    provinces: "IconMapPins",
    cities: "IconBuilding",
    barangay: "IconHomeMapPin",
    "reference-library": "IconBook2",
    "scholar-status": "IconProgressCheck",
    "scholar-program": "IconRosetteDiscountCheck",
    "academic-references": "IconListDetails",
    "admin-setting": "IconSettings",
    roles: "IconUserShield",
    routes: "IconRoute",
    "user-management": "IconUsersGroup",
    "backup-restore": "IconDatabaseExport",
};

const iconsByComponent = {
    "Web/reviewPage": "IconUserPlus",
    "Web/stipendPage": "IconWallet",
};

export const navigationIcon = (item) =>
    iconsBySlug[item?.slug] ??
    iconsByComponent[item?.component] ??
    item?.icon ??
    "IconCircle";
