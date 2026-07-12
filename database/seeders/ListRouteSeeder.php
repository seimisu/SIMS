<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ListRouteSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('list_routes')->insert(
            [
                [
                    "id" => 4,
                    "label" => "user roles",
                    "roles" =>  json_encode([
                        [
                            'id' => 1,
                            'name' => 'Administrator'
                        ]
                    ]),
                    "main_id" => 2,
                    "route" => "/roles",
                    "component" => "Web/rolePage",
                    "slug" => "roles",
                    "icon" => "IconUserCog",
                    "order_no" => 2,
                    "is_submenu" => true,
                    "is_active" => true,
                    "is_delete" => false,
                    "created_by" => "John Rey Dalit",
                    "updated_by" => null,
                    "created_at" => "2025-11-12 14:04:15",
                    "updated_at" => "2025-11-12 14:04:15",
                ],
                [
                    "id" => 3,
                    "label" => "navigation settings",
                    "roles" =>  json_encode([
                        [
                            'id' => 1,
                            'name' => 'Administrator'
                        ]
                    ]),
                    "main_id" => 2,
                    "route" => "/routes",
                    "component" => "Web/routePage",
                    "slug" => "routes",
                    "icon" => "IconCategory",
                    "order_no" => 1,
                    "is_submenu" => true,
                    "is_active" => true,
                    "is_delete" => false,
                    "created_by" => "John Rey Dalit",
                    "updated_by" => null,
                    "created_at" => "2025-11-12 14:01:25",
                    "updated_at" => "2025-11-12 14:01:25",
                ],
                [
                    "id" => 13,
                    "label" => "user management",
                    "roles" =>  json_encode([
                        [
                            'id' => 1,
                            'name' => 'Administrator'
                        ]
                    ]),
                    "main_id" => 2,
                    "route" => "/users",
                    "component" => "Web/userPage",
                    "slug" => "user-management",
                    "icon" => "IconUserPlus",
                    "order_no" => 3,
                    "is_submenu" => true,
                    "is_active" => true,
                    "is_delete" => false,
                    "created_by" => "John Rey Dalit",
                    "updated_by" => null,
                    "created_at" => "2025-11-13 09:29:36",
                    "updated_at" => "2025-11-13 09:29:36",
                ],
                [
                    "id" => 8,
                    "label" => "barangay",
                    "roles" =>  json_encode([
                        [
                            'id' => 1,
                            'name' => 'Administrator'
                        ]
                    ]),
                    "main_id" => 7,
                    "route" => "/location/barangay",
                    "component" => "Web/locationBarangayPage",
                    "slug" => "barangay",
                    "icon" => "IconPennant2",
                    "order_no" => 5,
                    "is_submenu" => true,
                    "is_active" => true,
                    "is_delete" => false,
                    "created_by" => "John Rey Dalit",
                    "updated_by" => null,
                    "created_at" => "2025-11-13 09:18:20",
                    "updated_at" => "2025-11-13 09:18:20",
                ],
                [
                    "id" => 9,
                    "label" => "cities",
                    "roles" =>  json_encode([
                        [
                            'id' => 1,
                            'name' => 'Administrator'
                        ]
                    ]),
                    "main_id" => 7,
                    "route" => "/location/cities",
                    "component" => "Web/locationCityPage",
                    "slug" => "cities",
                    "icon" => "IconPennant2",
                    "order_no" => 5,
                    "is_submenu" => true,
                    "is_active" => true,
                    "is_delete" => false,
                    "created_by" => "John Rey Dalit",
                    "updated_by" => null,
                    "created_at" => "2025-11-13 09:19:39",
                    "updated_at" => "2025-11-13 09:19:39",
                ],
                [
                    "id" => 10,
                    "label" => "provinces",
                    "roles" =>  json_encode([
                        [
                            'id' => 1,
                            'name' => 'Administrator'
                        ]
                    ]),
                    "main_id" => 7,
                    "route" => "/location/provinces",
                    "component" => "Web/locationProvincePage",
                    "slug" => "provinces",
                    "icon" => "IconPennant2",
                    "order_no" => 5,
                    "is_submenu" => true,
                    "is_active" => true,
                    "is_delete" => false,
                    "created_by" => "John Rey Dalit",
                    "updated_by" => null,
                    "created_at" => "2025-11-13 09:21:58",
                    "updated_at" => "2025-11-13 09:21:58",
                ],
                [
                    "id" => 11,
                    "label" => "regions",
                    "roles" =>  json_encode([
                        [
                            'id' => 1,
                            'name' => 'Administrator'
                        ]
                    ]),
                    "main_id" => 7,
                    "route" => "/location/regions",
                    "component" => "Web/locationRegionPage",
                    "slug" => "regions",
                    "icon" => "IconPennant2",
                    "order_no" => 5,
                    "is_submenu" => true,
                    "is_active" => true,
                    "is_delete" => false,
                    "created_by" => "John Rey Dalit",
                    "updated_by" => null,
                    "created_at" => "2025-11-13 09:22:39",
                    "updated_at" => "2025-11-13 09:22:39",
                ],
                [
                    "id" => 14,
                    "label" => "courses",
                    "roles" =>  json_encode([
                        [
                            'id' => 1,
                            'name' => 'Administrator'
                        ]
                    ]),
                    "main_id" => 5,
                    "route" => "/academic/courses",
                    "component" => "Web/coursePage",
                    "slug" => "courses",
                    "icon" => "IconBook2",
                    "order_no" => 5,
                    "is_submenu" => true,
                    "is_active" => true,
                    "is_delete" => false,
                    "created_by" => "John Rey Dalit",
                    "updated_by" => null,
                    "created_at" => "2025-11-13 09:34:49",
                    "updated_at" => "2025-11-13 09:34:49",
                ],
                [
                    "id" => 15,
                    "label" => "References",
                    "roles" =>  json_encode([
                        [
                            'id' => 1,
                            'name' => 'Administrator'
                        ]
                    ]),
                    "main_id" => 5,
                    "route" => "/academic/references",
                    "component" => "Web/referencePage",
                    "slug" => "academic-references",
                    "icon" => "IconContract",
                    "order_no" => 5,
                    "is_submenu" => true,
                    "is_active" => true,
                    "is_delete" => false,
                    "created_by" => "John Rey Dalit",
                    "updated_by" => null,
                    "created_at" => "2025-11-13 10:44:07",
                    "updated_at" => "2025-11-13 10:44:07",
                ],
                [
                    "id" => 2,
                    "label" => "admin settings",
                    "roles" =>  json_encode([
                        [
                            'id' => 1,
                            'name' => 'Administrator'
                        ]
                    ]),
                    "main_id" => null,
                    "route" => null,
                    "component" => null,
                    "slug" => "admin-setting",
                    "icon" => "IconSettingsSpark",
                    "order_no" => 10,
                    "is_submenu" => false,
                    "is_active" => true,
                    "is_delete" => false,
                    "created_by" => "John Rey Dalit",
                    "updated_by" => null,
                    "created_at" => "2025-11-12 13:58:09",
                    "updated_at" => "2025-11-12 13:58:09",
                ],
                [
                    "id" => 5,
                    "label" => "academic data",
                    "roles" =>  json_encode([
                        [
                            'id' => 1,
                            'name' => 'Administrator'
                        ]
                    ]),
                    "main_id" => null,
                    "route" => null,
                    "component" => null,
                    "slug" => "academic-data",
                    "icon" => "IconSchool",
                    "order_no" => 5,
                    "is_submenu" => false,
                    "is_active" => true,
                    "is_delete" => false,
                    "created_by" => "John Rey Dalit",
                    "updated_by" => null,
                    "created_at" => "2025-11-13 09:06:58",
                    "updated_at" => "2025-11-13 09:06:58",
                ],
                [
                    "id" => 6,
                    "label" => "Schools",
                    "roles" =>  json_encode([
                        [
                            'id' => 1,
                            'name' => 'Administrator'
                        ]
                    ]),
                    "main_id" => 5,
                    "route" => "/academic/universities",
                    "component" => "Web/schoolPage",
                    "slug" => "universities",
                    "icon" => "IconBuildings",
                    "order_no" => 5,
                    "is_submenu" => true,
                    "is_active" => true,
                    "is_delete" => false,
                    "created_by" => "John Rey Dalit",
                    "updated_by" => null,
                    "created_at" => "2025-11-13 09:08:57",
                    "updated_at" => "2025-11-13 09:08:57",
                ],
                [
                    "id" => 7,
                    "label" => "places",
                    "roles" =>  json_encode([
                        [
                            'id' => 1,
                            'name' => 'Administrator'
                        ]
                    ]),
                    "main_id" => null,
                    "route" => null,
                    "component" => null,
                    "slug" => "places",
                    "icon" => "IconMapPinCog",
                    "order_no" => 5,
                    "is_submenu" => false,
                    "is_active" => true,
                    "is_delete" => false,
                    "created_by" => "John Rey Dalit",
                    "updated_by" => null,
                    "created_at" => "2025-11-13 09:15:46",
                    "updated_at" => "2025-11-13 09:15:46",
                ],
                [
                    "id" => 17,
                    "label" => "scholars",
                    "roles" =>  json_encode([
                        [
                            'id' => 1,
                            'name' => 'Administrator'
                        ]
                    ]),
                    "main_id" => 5,
                    "route" => "scholar/scholars",
                    "component" => "Web/scholarPage",
                    "slug" => "scholars",
                    "icon" => "IconUserStar",
                    "order_no" => 5,
                    "is_submenu" => true,
                    "is_active" => true,
                    "is_delete" => false,
                    "created_by" => "John Rey Dalit",
                    "updated_by" => null,
                    "created_at" => "2025-11-13 13:25:51",
                    "updated_at" => "2025-11-13 13:25:51",
                ],
                [
                    "id" => 18,
                    "label" => "statuses",
                    "roles" =>  json_encode([
                        [
                            'id' => 1,
                            'name' => 'Administrator'
                        ]
                    ]),
                    "main_id" => 16,
                    "route" => "scholar/statuses",
                    "component" => "Web/scholarStatusPage",
                    "slug" => "scholar-status",
                    "icon" => "IconNotes",
                    "order_no" => 5,
                    "is_submenu" => true,
                    "is_active" => true,
                    "is_delete" => false,
                    "created_by" => "John Rey Dalit",
                    "updated_by" => null,
                    "created_at" => "2025-11-13 13:40:31",
                    "updated_at" => "2025-11-13 13:40:31",
                ],
                [
                    "id" => 19,
                    "label" => "programs",
                    "roles" =>  json_encode([
                        [
                            'id' => 1,
                            'name' => 'Administrator'
                        ]
                    ]),
                    "main_id" => 16,
                    "route" => "scholar/program",
                    "component" => "Web/programPage",
                    "slug" => "scholar-program",
                    "icon" => "IconCoins",
                    "order_no" => 5,
                    "is_submenu" => true,
                    "is_active" => true,
                    "is_delete" => false,
                    "created_by" => "John Rey Dalit",
                    "updated_by" => null,
                    "created_at" => "2025-11-13 13:50:34",
                    "updated_at" => "2025-11-13 13:50:34",
                ],
                [
                    "id" => 20,
                    "label" => "privileges",
                    "roles" =>  json_encode([
                        [
                            'id' => 1,
                            'name' => 'Administrator'
                        ]
                    ]),
                    "main_id" => 16,
                    "route" => "scholar/privileges",
                    "component" => "Web/scholarPrivilegesPage",
                    "slug" => "scholar-privileges",
                    "icon" => "IconMoneybag",
                    "order_no" => 5,
                    "is_submenu" => true,
                    "is_active" => true,
                    "is_delete" => false,
                    "created_by" => "John Rey Dalit",
                    "updated_by" => null,
                    "created_at" => "2025-11-13 14:08:37",
                    "updated_at" => "2025-11-13 14:08:37",
                ],
                [
                    "id" => 21,
                    "label" => "documents",
                    "roles" =>  json_encode([
                        [
                            'id' => 1,
                            'name' => 'Administrator'
                        ]
                    ]),
                    "main_id" => 16,
                    "route" => "/documents",
                    "component" => "Web/documentPage",
                    "slug" => "documents",
                    "icon" => "IconFileDescription",
                    "order_no" => 6,
                    "is_submenu" => true,
                    "is_active" => true,
                    "is_delete" => false,
                    "created_by" => "John Rey Dalit",
                    "updated_by" => null,
                    "created_at" => "2026-07-12 00:00:00",
                    "updated_at" => "2026-07-12 00:00:00",
                ],
                [
                    "id" => 1,
                    "label" => "dashboard",
                    "roles" =>  json_encode([
                        [
                            'id' => 1,
                            'name' => 'Administrator'
                        ]
                    ]),
                    "main_id" => null,
                    "route" => "/dashboard",
                    "component" => "Web/dashboardPage",
                    "slug" => "dashboard",
                    "icon" => "IconDashboard",
                    "order_no" => 1,
                    "is_submenu" => false,
                    "is_active" => true,
                    "is_delete" => false,
                    "created_by" => "John Rey Dalit",
                    "updated_by" => null,
                    "created_at" => "2025-11-12 13:30:20",
                    "updated_at" => "2025-11-12 13:30:20",
                ],
                [
                    "id" => 16,
                    "label" => "scholar data",
                    "roles" =>  json_encode([
                        [
                            'id' => 1,
                            'name' => 'Administrator'
                        ]
                    ]),
                    "main_id" => null,
                    "route" => null,
                    "component" => null,
                    "slug" => "scholar-data",
                    "icon" => "IconCertificate",
                    "order_no" => 2,
                    "is_submenu" => false,
                    "is_active" => true,
                    "is_delete" => false,
                    "created_by" => "John Rey Dalit",
                    "updated_by" => null,
                    "created_at" => "2025-11-13 11:00:32",
                    "updated_at" => "2025-11-13 11:00:32",
                ],
                [
                    "id" => 12,
                    "label" => "backup & restore",
                    "roles" =>  json_encode([
                        [
                            'id' => 1,
                            'name' => 'Administrator'
                        ]
                    ]),
                    "main_id" => null,
                    "route" => "/maintenance",
                    "component" => "Web/maintenance",
                    "slug" => "backup-restore",
                    "icon" => "IconServerSpark",
                    "order_no" => 11,
                    "is_submenu" => false,
                    "is_active" => true,
                    "is_delete" => false,
                    "created_by" => "John Rey Dalit",
                    "updated_by" => null,
                    "created_at" => "2025-11-13 09:27:16",
                    "updated_at" => "2025-11-13 09:27:16",
                ],
            ]
        );
    }
}
