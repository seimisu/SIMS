<?php

namespace Tests\Feature;

use App\Http\Controllers\Web\SchoolController;
use App\Http\Requests\Web\SchoolRequest;
use App\Models\SchoolCampusAddresses;
use App\Models\SchoolCampuses;
use App\Models\Schools;
use App\Services\Notifications\RoleBellNotificationService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class SchoolSaveIntegrityTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        // Use the real school/location schemas, isolated from the configured application database.
        config(['database.default' => 'sqlite', 'database.connections.sqlite.database' => ':memory:']);
        DB::purge('sqlite');
        foreach ([
            '0000_00_00_000001_create_list_agencies_table.php',
            '0000_00_00_000008_create_list_references_table.php',
            '2025_11_20_081322_create_location_regions_table.php',
            '2025_11_20_081523_create_location_provinces_table.php',
            '2025_11_20_081547_create_location_cities_table.php',
            '2025_11_20_081708_create_location_barangays_table.php',
            '2025_11_25_154716_create_schools_table.php',
            '2025_11_25_154803_create_school_campuses_table.php',
            '2025_11_25_155001_create_school_campus_addresses_table.php',
        ] as $migration) {
            (require database_path('migrations/'.$migration))->up();
        }
        DB::table('list_references')->insert([
            ['id' => 1, 'name' => 'Public', 'classification' => 'Class'],
            ['id' => 2, 'name' => 'Semester', 'classification' => 'Term Type'],
            ['id' => 3, 'name' => 'Transmutation', 'classification' => 'Grading System'],
        ]);
        DB::table('list_agencies')->insert(['id' => 1, 'name' => 'Agency', 'slug' => 'TEST']);
        DB::table('location_regions')->insert(['code' => 'R', 'name' => 'Region', 'region' => 'Region', 'island' => 'Island']);
        DB::table('location_provinces')->insert(['code' => 'P', 'name' => 'Province', 'region_code' => 'R']);
        DB::table('location_cities')->insert(['code' => 'C', 'name' => 'City', 'province_code' => 'P', 'is_municipality' => true]);
        DB::table('location_barangays')->insert(['code' => 'B', 'name' => 'Barangay', 'municipality_code' => 'C']);
        Auth::shouldReceive('user')->andReturn((object) ['profile' => (object) ['fullname' => 'Test Staff']]);
        $this->mock(RoleBellNotificationService::class)->shouldReceive('notifyRegionalAndScholarshipStaff')->zeroOrMoreTimes();
    }

    private function payload(): array
    {
        return [
            'name' => 'Test University', 'abbreviation' => 'TU', 'class' => ['id' => 1],
            'campuses' => [[
                'name' => null, 'semester' => ['id' => 2], 'grading' => ['id' => 3],
                'agency' => ['id' => 1], 'main' => true,
                'address' => ['id' => 'B-C-P-R', 'name' => 'Barangay, City, Province, Region'],
            ]],
        ];
    }

    private function requestFor(array $data): SchoolRequest
    {
        $request = SchoolRequest::create('/schools', 'POST', $data);
        $request->setValidator(Validator::make($data, $request->rules(), $request->messages()));

        return $request;
    }

    public function test_complete_school_is_valid_and_requires_a_campus_and_address(): void
    {
        $data = $this->payload();
        $this->assertEquals($data['campuses'], $this->requestFor($data)->validated()['campuses']);
        $data['campuses'] = [];
        $request = SchoolRequest::create('/', 'POST', $data);
        $this->assertTrue(Validator::make($data, $request->rules())->errors()->has('campuses'));
        $data = $this->payload();
        $data['campuses'][0]['address'] = null;
        $request = SchoolRequest::create('/', 'POST', $data);
        $this->assertTrue(Validator::make($data, $request->rules())->errors()->has('campuses.0.address'));
    }

    public function test_malformed_nonexistent_and_mismatched_address_codes_are_rejected(): void
    {
        foreach (['B-C', 'B-C-P-', 'UNKNOWN-C-P-R', 'B-WRONG-P-R', 'B-C-WRONG-R', 'B-C-P-WRONG'] as $id) {
            $data = $this->payload();
            $data['campuses'][0]['address']['id'] = $id;
            $request = SchoolRequest::create('/', 'POST', $data);
            $validator = Validator::make($data, $request->rules());
            $this->assertTrue($validator->errors()->has('campuses.0.address.id'), $id);
        }
    }

    public function test_failed_address_save_rolls_back_school_and_campus_creation(): void
    {
        $data = $this->payload();
        $data['campuses'][] = $data['campuses'][0];
        $attempts = 0;
        SchoolCampusAddresses::creating(function () use (&$attempts) {
            if (++$attempts === 2) {
                throw new \RuntimeException('Simulated address failure');
            }
        });
        try {
            (new SchoolController)->store($this->requestFor($data));
            $this->fail('Expected address failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated address failure', $exception->getMessage());
        } finally {
            SchoolCampusAddresses::flushEventListeners();
        }
        $this->assertSame(0, Schools::count());
        $this->assertSame(0, SchoolCampuses::count());
        $this->assertSame(0, SchoolCampusAddresses::count());
    }

    public function test_successful_creation_saves_address_and_uses_canonical_municipality_name(): void
    {
        $data = $this->payload();
        $data['campuses'][0]['address']['name'] = 'Untrusted display label';
        (new SchoolController)->store($this->requestFor($data));
        $this->assertSame(1, Schools::count());
        $this->assertSame('TEST UNIVERSITY-CITY', SchoolCampuses::first()->generated_name);
        $this->assertSame('B', SchoolCampusAddresses::first()->barangay_code);
        $this->artisan('schools:audit-addresses')->expectsOutputToContain('Found 0 campus address issue(s)')->assertSuccessful();
    }

    public function test_edit_restores_missing_address_and_does_not_duplicate_it(): void
    {
        $school = Schools::create(['name' => 'Old', 'shortcut' => 'OLD', 'reference_id' => 1]);
        $campus = $school->campuses()->create(['term_id' => 2, 'grading_id' => 3, 'agency_id' => 1]);
        $data = $this->payload();
        $data['campuses'][0]['id'] = $campus->id;
        $data['id'] = $school->id;
        for ($i = 0; $i < 2; $i++) {
            (new SchoolController)->update($this->requestFor($data), (string) $school->id, 'form');
        }
        $this->assertSame(1, $campus->address()->count());
        $this->assertSame('C', $campus->address()->first()->municipality_code);
    }

    public function test_failed_edit_rolls_back_school_changes(): void
    {
        $school = Schools::create(['name' => 'Old', 'shortcut' => 'OLD', 'reference_id' => 1]);
        $campus = $school->campuses()->create(['term_id' => 2, 'grading_id' => 3, 'agency_id' => 1]);
        $data = $this->payload();
        $data['campuses'][0]['id'] = $campus->id;
        $data['id'] = $school->id;
        SchoolCampusAddresses::creating(function () {
            throw new \RuntimeException('Simulated address failure');
        });
        try {
            (new SchoolController)->update($this->requestFor($data), (string) $school->id, 'form');
            $this->fail('Expected address failure');
        } catch (\RuntimeException $exception) {
            $this->assertSame('Simulated address failure', $exception->getMessage());
        } finally {
            SchoolCampusAddresses::flushEventListeners();
        }
        $this->assertSame('Old', $school->fresh()->name);
        $this->assertSame(0, $campus->address()->count());
    }

    public function test_audit_reports_missing_addresses_without_changing_records(): void
    {
        $school = Schools::create(['name' => 'Old', 'shortcut' => 'OLD', 'reference_id' => 1]);
        $school->campuses()->create(['term_id' => 2, 'grading_id' => 3, 'agency_id' => 1]);
        $this->artisan('schools:audit-addresses')->expectsOutputToContain('missing address')->assertFailed();
        $this->assertSame(0, SchoolCampusAddresses::count());
    }
}
