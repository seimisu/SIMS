<?php

namespace Database\Seeders;

use App\Models\ListStatuses;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {



        //done seeders
        $this->call(ListColorSeeder::class);
        $this->call(ListAgencySeeder::class);
        $this->call(ListReferenceSeeder::class);
        $this->call(ListProgramSeeder::class);
        $this->call(ListCourseSeeder::class);
        $this->call(ListRoleSeeder::class);
        $this->call(ListPermissionSeeder::class);
        $this->call(ListRouteSeeder::class);
        $this->call(LocationRegionSeeder::class);
        $this->call(LocationProvinceSeeder::class);
        $this->call(LocationCitySeeder::class);
        $this->call(LocationBarangaySeeder::class);
        $this->call(ListStatusesSeeder::class);

        User::create([
            'email'         => 'jmdalit@sei.dost.gov.ph',
            'role_id'       => 1,
            'password'      => bcrypt('@dmin123'),
            'is_verified'   => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);
        UserProfile::create([
            'user_id'       => 1,
            'agency_id'     => 15,
            'fname'         => 'john rey',
            'lname'         => 'dalit',
            'contact_no'    => '09321312412',
            'designation'   => 'project technical assistant VI',
            'avatar'        => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);

        User::create([
            'email'         => 'mrcapistrano@sei.dost.gov.ph',
            'role_id'       => 1,
            'password'      => bcrypt('mrcapistrano'),
            'is_verified'   => true,
            'created_at'    => now(),
            'updated_at'    => now(),
        ]);

        UserProfile::create([
            'user_id'       => 2,
            'agency_id'     => 15,
            'fname'         => 'Mark John Paul',
            'lname'         => 'Capistrano',
            'contact_no'    => '09312313139',
            'designation'   => 'Senior Science Research Specialist',
            'avatar'        => null,
            'created_at'    => now(),
            'updated_at'    => now()
        ]);
    }
}
