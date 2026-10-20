# Migration and Column Inventory

**Source:** `database/migrations/` (166 migration files).

This is a source-level inventory in migration filename order. It records table declarations and migration operations that reference schema columns (including column additions, removals, renames, type changes, indexes, and foreign keys). It is not a live database schema dump; review each migration up/down behavior and database state as needed.

## `0000_00_00_000000_create_list_roles_table.php`

```php
14:         Schema::create('list_roles', function (Blueprint $table) {
15:             $table->id();
16:             $table->string('name')->unique();
17:             $table->string('slug')->unique();
18:             $table->text('description')->nullable();
19:             $table->boolean('is_active')->default(true);
20:             $table->boolean('is_lock')->default(false);
21:             $table->boolean('is_delete')->default(false);
22:             $table->string('created_by')->nullable();
23:             $table->string('updated_by')->nullable();
24:             $table->timestamps();
33:         Schema::dropIfExists('list_roles');
```

## `0000_00_00_000001_create_list_agencies_table.php`

```php
14:         Schema::create('list_agencies', function (Blueprint $table) {
15:             $table->id();
16:             $table->string('name')->unique();
17:             $table->string('slug')->unique();
18:             $table->string('address')->nullable();
19:             $table->string('barangay_code')->nullable();
20:             $table->string('city_code')->nullable();
21:             $table->string('province_code')->nullable();
22:             $table->string('region_code')->nullable();
23:             $table->boolean('is_active')->default(true);
24:             $table->boolean('is_delete')->default(false);
25:             $table->string('created_by')->nullable();
26:             $table->string('updated_by')->nullable();
27:             $table->timestamps();
36:         Schema::dropIfExists('list_agencies');
```

## `0000_00_00_000002_create_list_courses_table.php`

```php
14:         Schema::create('list_courses', function (Blueprint $table) {
15:             $table->id();
16:             $table->string('name')->unique();
17:             $table->string('field')->nullable();
18:             $table->string('abbreviation')->nullable();
19:             $table->boolean('is_active')->default(true);
20:             $table->boolean('is_delete')->default(false);
21:             $table->string('created_by')->nullable();
22:             $table->string('updated_by')->nullable();
23:             $table->timestamps();
32:         Schema::dropIfExists('list_courses');
```

## `0000_00_00_000003_create_list_routes_table.php`

```php
14:         Schema::create('list_routes', function (Blueprint $table) {
15:             $table->id();
16:             $table->string('label')->nullable();
17:             $table->json('roles')->nullable();
18:             $table->unsignedBigInteger('main_id')->nullable();
19:             $table->string('route')->nullable();
20:             $table->string('component')->nullable();
21:             $table->string('slug')->nullable();
22:             $table->string('icon')->nullable();
23:             $table->integer('order_no')->default(0);
24:             $table->boolean('is_submenu')->default(false);
25:             $table->boolean('is_active')->default(true);
26:             $table->boolean('is_delete')->default(false);
27:             $table->string('created_by')->nullable();
28:             $table->string('updated_by')->nullable();
29:             $table->timestamps();
38:         Schema::dropIfExists('list_routes');
```

## `0000_00_00_000008_create_list_references_table.php`

```php
14:         Schema::create('list_references', function (Blueprint $table) {
15:             $table->id();
16:             $table->string('name')->nullable();
17:             $table->string('classification')->nullable();
18:             $table->string('type')->nullable();
19:             $table->string('color')->nullable();
20:             $table->string('others')->nullable();
21:             $table->boolean('is_active')->default(true);
22:             $table->boolean('is_delete')->default(false);
23:             $table->string('created_by')->nullable();
24:             $table->string('updated_by')->nullable();
25:             $table->timestamps();
34:         Schema::dropIfExists('list_references');
```

## `0000_00_00_000009_create_list_programs_table.php`

```php
14:         Schema::create('list_programs', function (Blueprint $table) {
15:             $table->bigIncrements('id');
16:             $table->string('name');
17:             $table->string('others')->default('n/a');
18:             $table->bigInteger('program_id')->unsigned()->index();
19:             $table->foreign('program_id')->references('id')->on('list_references')->onDelete('cascade');
20:             $table->bigInteger('type_id')->unsigned()->index();
21:             $table->foreign('type_id')->references('id')->on('list_references')->onDelete('cascade');
22:             $table->boolean('is_sub')->default(false);
23:             $table->boolean('is_active')->default(true);
24:             $table->boolean('is_delete')->default(false);
25:             $table->string('created_by')->nullable();
26:             $table->string('updated_by')->nullable();
27:             $table->timestamps();
36:         Schema::dropIfExists('list_programs');
```

## `0001_01_01_000000_create_users_table.php`

```php
14:         Schema::create('users', function (Blueprint $table) {
15:             $table->id();
16:             $table->string('email')->unique();
17:             $table->foreignId('role_id')->nullable()->constrained('list_roles')->onDelete('set null');
18:             $table->string('password')->nullable();
19:             $table->boolean('can_edit')->default(false);
20:             $table->boolean('can_create')->default(false);
21:             $table->boolean('can_delete')->default(false);
22:             $table->boolean('is_verified')->default(false);
23:             $table->boolean('is_active')->default(true);
24:             $table->boolean('is_delete')->default(false);
25:             $table->string('activation_token')->nullable();
26:             $table->rememberToken();
27:             $table->timestamps();
30:         Schema::create('password_reset_tokens', function (Blueprint $table) {
31:             $table->string('email')->primary();
32:             $table->string('token');
33:             $table->timestamp('created_at')->nullable();
36:         Schema::create('sessions', function (Blueprint $table) {
37:             $table->string('id')->primary();
38:             $table->foreignId('user_id')->nullable()->index();
39:             $table->string('ip_address', 45)->nullable();
40:             $table->text('user_agent')->nullable();
41:             $table->longText('payload');
42:             $table->integer('last_activity')->index();
51:         Schema::dropIfExists('users');
52:         Schema::dropIfExists('password_reset_tokens');
53:         Schema::dropIfExists('sessions');
```

## `0001_01_01_000001_create_cache_table.php`

```php
14:         Schema::create('cache', function (Blueprint $table) {
15:             $table->string('key')->primary();
16:             $table->mediumText('value');
17:             $table->integer('expiration');
20:         Schema::create('cache_locks', function (Blueprint $table) {
21:             $table->string('key')->primary();
22:             $table->string('owner');
23:             $table->integer('expiration');
32:         Schema::dropIfExists('cache');
33:         Schema::dropIfExists('cache_locks');
```

## `0001_01_01_000002_create_jobs_table.php`

```php
14:         Schema::create('jobs', function (Blueprint $table) {
15:             $table->id();
16:             $table->string('queue')->index();
17:             $table->longText('payload');
18:             $table->unsignedTinyInteger('attempts');
19:             $table->unsignedInteger('reserved_at')->nullable();
20:             $table->unsignedInteger('available_at');
21:             $table->unsignedInteger('created_at');
24:         Schema::create('job_batches', function (Blueprint $table) {
25:             $table->string('id')->primary();
26:             $table->string('name');
27:             $table->integer('total_jobs');
28:             $table->integer('pending_jobs');
29:             $table->integer('failed_jobs');
30:             $table->longText('failed_job_ids');
31:             $table->mediumText('options')->nullable();
32:             $table->integer('cancelled_at')->nullable();
33:             $table->integer('created_at');
34:             $table->integer('finished_at')->nullable();
37:         Schema::create('failed_jobs', function (Blueprint $table) {
38:             $table->id();
39:             $table->string('uuid')->unique();
40:             $table->text('connection');
41:             $table->text('queue');
42:             $table->longText('payload');
43:             $table->longText('exception');
44:             $table->timestamp('failed_at')->useCurrent();
53:         Schema::dropIfExists('jobs');
54:         Schema::dropIfExists('job_batches');
55:         Schema::dropIfExists('failed_jobs');
```

## `2025_10_28_072304_create_user_profiles_table.php`

```php
14:         Schema::create('user_profiles', function (Blueprint $table) {
15:             $table->id();
16:             $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
17:             $table->foreignId('agency_id')->nullable()->constrained('list_agencies')->onDelete('set null');
18:             $table->string('fname');
19:             $table->string('lname');
20:             $table->string('contact_no')->nullable();
21:             $table->string('designation')->nullable();
22:             $table->string('avatar')->nullable();
23:             $table->timestamps();
32:         Schema::dropIfExists('user_profiles');
```

## `2025_11_04_005303_create_otp_requests_table.php`

```php
14:         Schema::create('otp_requests', function (Blueprint $table) {
15:             $table->id();
16:             $table->unsignedBigInteger('user_id');
17:             $table->string('generated_token')->nullable();
18:             $table->timestamp('expires_at')->nullable();
19:             $table->integer('attempts')->default(0);
20:             $table->string('status')->default('new');
21:             $table->timestamps();
30:         Schema::dropIfExists('otp_requests');
```

## `2025_11_20_081322_create_location_regions_table.php`

```php
14:         Schema::create('location_regions', function (Blueprint $table) {
15:             $table->id();
16:             $table->string('code', 50)->unique();
17:             $table->string('name', 150)->unique();
18:             $table->string('region', 50)->unique();
19:             $table->string('island', 50);
20:             $table->boolean('is_active')->default(true);
21:             $table->boolean('is_delete')->default(false);
22:             $table->string('created_by')->nullable();
23:             $table->string('updated_by')->nullable();
24:             $table->timestamps();
33:         Schema::dropIfExists('location_regions');
```

## `2025_11_20_081523_create_location_provinces_table.php`

```php
14:         Schema::create('location_provinces', function (Blueprint $table) {
15:             $table->id();
16:             $table->string('code', 50)->unique();
17:             $table->string('name', 150)->unique();
18:             $table->string('old_name')->nullable();
19:             $table->string('region_code');
20:             $table->foreign('region_code')->references('code')->on('location_regions')->onDelete('cascade');
21:             $table->boolean('is_active')->default(true);
22:             $table->boolean('is_delete')->default(false);
23:             $table->string('created_by')->nullable();
24:             $table->string('updated_by')->nullable();
25:             $table->timestamps();
34:         Schema::dropIfExists('location_provinces');
```

## `2025_11_20_081547_create_location_cities_table.php`

```php
14:         Schema::create('location_cities', function (Blueprint $table) {
15:             $table->id();
16:             $table->string('code', 50)->unique();
17:             $table->string('name', 150);
18:             $table->string('old_name')->nullable();
19:             $table->boolean('is_municipality');
20:             $table->boolean('is_chartered')->default(0);
21:             $table->string('district')->nullable();
22:             $table->string('zipcode')->nullable();
23:             $table->string('province_code')->nullable()->constrained();
24:             $table->foreign('province_code')->references('code')->on('location_provinces')->onDelete('cascade');
25:             $table->boolean('is_active')->default(true);
26:             $table->boolean('is_delete')->default(false);
27:             $table->string('created_by')->nullable();
28:             $table->string('updated_by')->nullable();
29:             $table->timestamps();
38:         Schema::dropIfExists('location_cities');
```

## `2025_11_20_081708_create_location_barangays_table.php`

```php
14:         Schema::create('location_barangays', function (Blueprint $table) {
15:             $table->id();
16:             $table->string('code', 50)->unique();
17:             $table->string('name', 150);
18:             $table->string('old_name')->nullable();
19:             $table->string('district')->nullable();
21:             $table->string('municipality_code')->nullable()->constrained();
22:             $table->foreign('municipality_code')->references('code')->on('location_cities')->onDelete('cascade');
23:             $table->boolean('is_active')->default(true);
24:             $table->boolean('is_delete')->default(false);
25:             $table->string('created_by')->nullable();
26:             $table->string('updated_by')->nullable();
27:             $table->timestamps();
36:         Schema::dropIfExists('location_barangays');
```

## `2025_11_25_154716_create_schools_table.php`

```php
14:         Schema::create('schools', function (Blueprint $table) {
15:             $table->id();
16:             $table->string('name')->unique();
17:             $table->string('shortcut');
18:             $table->string('photo')->nullable();
19:             $table->foreignId('reference_id')->nullable()->constrained('list_references')->onDelete('set null');
20:             $table->boolean('is_active')->default(true);
21:             $table->boolean('is_delete')->default(false);
22:             $table->string('created_by')->nullable();
23:             $table->string('updated_by')->nullable();
24:             $table->timestamps();
33:         Schema::dropIfExists('schools');
```

## `2025_11_25_154803_create_school_campuses_table.php`

```php
14:         Schema::create('school_campuses', function (Blueprint $table) {
15:             $table->id();
16:             $table->string('name')->nullable();
17:             $table->string('generated_name')->nullable();
18:             $table->foreignId('school_id')->constrained('schools')->onDelete('set null');
19:             $table->foreignId('agency_id')->constrained('list_agencies')->onDelete('set null');
20:             $table->foreignId('term_id')->constrained('list_references')->onDelete('set null');
21:             $table->foreignId('grading_id')->constrained('list_references')->onDelete('set null');
22:             $table->boolean('is_main')->default(false);
23:             $table->boolean('is_active')->default(true);
24:             $table->boolean('is_delete')->default(false);
25:             $table->string('created_by')->nullable();
26:             $table->string('updated_by')->nullable();
27:             $table->timestamps();
36:         Schema::dropIfExists('school_campuses');
```

## `2025_11_25_155001_create_school_campus_addresses_table.php`

```php
14:         Schema::create('school_campus_addresses', function (Blueprint $table) {
15:             $table->id();
16:             $table->string('address')->nullable();
18:             $table->foreignId('campus_id')->constrained('school_campuses')->onDelete('set null');
20:             $table->string('barangay_code')->nullable();
21:             $table->foreign('barangay_code')->references('code')->on('location_barangays')->onDelete('set null');
23:             $table->string('municipality_code')->nullable();
24:             $table->foreign('municipality_code')->references('code')->on('location_cities')->onDelete('set null');
26:             $table->string('province_code')->nullable();
27:             $table->foreign('province_code')->references('code')->on('location_provinces')->onDelete('set null');
29:             $table->string('region_code')->nullable();
30:             $table->foreign('region_code')->references('code')->on('location_regions')->onDelete('set null');
32:             $table->timestamps();
41:         Schema::dropIfExists('school_campus_addresses');
```

## `2025_11_26_145513_create_school_name_histories_table.php`

```php
14:         Schema::create('school_name_histories', function (Blueprint $table) {
15:             $table->id();
16:             $table->unsignedBigInteger('school_id');
17:             $table->foreign('school_id')->references('id')->on('schools')->onDelete('cascade');
18:             $table->string('old_name');
19:             $table->date('effective_date')->nullable();
20:             $table->date('verified_at')->nullable();
21:             $table->string('verified_by')->nullable();
22:             $table->string('changed_by')->nullable();
23:             $table->timestamps();
32:         Schema::dropIfExists('school_name_histories');
```

## `2025_11_26_155256_create_school_campus_courses_table.php`

```php
14:         Schema::create('school_campus_courses', function (Blueprint $table) {
15:             $table->id();
16:             $table->tinyInteger('years');
17:             $table->integer('course_id')->unsigned()->index();
18:             $table->foreign('course_id')->references('id')->on('list_courses')->onDelete('cascade');
19:             $table->integer('campus_id')->unsigned()->index();
20:             $table->foreign('campus_id')->references('id')->on('school_campuses')->onDelete('cascade');
21:             $table->string('created_by')->nullable();
22:             $table->string('updated_by')->nullable();
23:             $table->boolean('is_active')->default(true);
24:             $table->boolean('is_delete')->default(false);
25:             $table->timestamps();
34:         Schema::dropIfExists('school_campus_courses');
```

## `2025_12_04_105313_create_school_campus_contacts_table.php`

```php
14:         Schema::create('school_campus_contacts', function (Blueprint $table) {
15:             $table->id();
16:             $table->foreignId('campus_id')
20:             $table->string('dean')->nullable();
21:             $table->string('registrar')->nullable();
22:             $table->string('email')->nullable();
23:             $table->string('contact')->nullable();
24:             $table->string('webpage')->nullable();
25:             $table->string('verified_by')->nullable();
26:             $table->string('created_by')->nullable();
27:             $table->string('updated_by')->nullable();
28:             $table->date('verified_at')->nullable();
29:             $table->timestamps();
38:         Schema::dropIfExists('school_campus_contacts');
```

## `2025_12_05_110006_create_school_campus_course_subjects_table.php`

```php
14:         Schema::create('school_campus_course_subjects', function (Blueprint $table) {
15:             $table->id();
16:             $table->foreignId('course_id')
20:             $table->string('name')->nullable();
21:             $table->string('subject_code')->nullable();
22:             $table->string('subject_class')->nullable();
23:             $table->string('unit')->nullable();
24:             $table->string('verified_by')->nullable();
25:             $table->string('created_by')->nullable();
26:             $table->string('updated_by')->nullable();
27:             $table->date('verified_at')->nullable();
28:             $table->boolean('is_delete')->default(false);
29:             $table->timestamps();
38:         Schema::dropIfExists('school_campus_course_subjects');
```

## `2025_12_16_090043_create_school_campus_grades_table.php`

```php
14:         Schema::create('school_campus_grades', function (Blueprint $table) {
15:             $table->id();
16:             $table->integer('campus_id')->unsigned()->index();
17:             $table->foreign('campus_id')->references('id')->on('school_campuses')->onDelete('cascade');
18:             $table->string('grade')->nullable();
19:             $table->string('lower')->nullable();
20:             $table->string('upper')->nullable();
21:             $table->boolean('is_failed')->default(false);
22:             $table->boolean('is_incomplete')->default(false);
23:             $table->boolean('is_drop')->default(false);
24:             $table->boolean('is_active')->default(true);
25:             $table->boolean('is_delete')->default(false);
26:             $table->string('created_by')->nullable();
27:             $table->string('updated_by')->nullable();
29:             $table->timestamps();
38:         Schema::dropIfExists('school_campus_grades');
```

## `2025_12_16_290851_create_school_campus_course_curriculums_table.php`

```php
14:         Schema::create('school_campus_course_curriculums', function (Blueprint $table) {
15:             $table->id();
16:             $table->foreignId('campus_course_id')->nullable()->constrained('school_campus_courses')->nullOnDelete();
17:             $table->foreignId('semester_type_id')->nullable()->constrained('list_references')->nullOnDelete();
18:             $table->string('years');
19:             $table->string('created_by')->nullable();
20:             $table->string('updated_by')->nullable();
21:             $table->boolean('is_active')->default(true);
22:             $table->boolean('is_delete')->default(false);
23:             $table->timestamps();
32:         Schema::dropIfExists('school_campus_course_curriculums');
```

## `2025_12_17_095850_create_list_colors_table.php`

```php
14:         Schema::create('list_colors', function (Blueprint $table) {
15:             $table->id();
16:             $table->string('background_color')->nullable();
17:             $table->string('text_color')->nullable();
18:             $table->boolean('is_active')->default(true);
19:             $table->boolean('is_delete')->default(false);
20:             $table->string('created_by')->nullable();
21:             $table->string('updated_by')->nullable();
22:             $table->timestamps();
31:         Schema::dropIfExists('list_colors');
```

## `2025_12_17_096000_create_list_statuses_table.php`

```php
14:         Schema::create('list_statuses', function (Blueprint $table) {
15:             $table->id();
16:             $table->string('name')->nullable();
17:             $table->string('icon')->nullable();
18:             $table->string('type')->nullable();
19:             $table->foreignId('color_id')->nullable()->constrained('list_colors')->onDelete('set null');
20:             $table->boolean('is_active')->default(true);
21:             $table->boolean('is_delete')->default(false);
22:             $table->string('created_by')->nullable();
23:             $table->string('updated_by')->nullable();
24:             $table->timestamps();
33:         Schema::dropIfExists('list_statuses');
```

## `2025_12_17_160109_create_scholars_table.php`

```php
14:         Schema::create('scholars', function (Blueprint $table) {
15:             $table->id();
16:             $table->string('spas_no')->unique();
17:             $table->tinyInteger('program_id')->unsigned()->index();
18:             $table->foreign('program_id')->references('id')->on('list_programs')->onDelete('cascade');
19:             $table->tinyInteger('category_id')->unsigned()->index();
20:             $table->foreign('category_id')->references('id')->on('list_references')->onDelete('cascade');
21:             $table->tinyInteger('type_id')->unsigned()->index();
22:             $table->foreign('type_id')->references('id')->on('list_references')->onDelete('cascade');
23:             $table->tinyInteger('status_id')->unsigned()->index();
24:             $table->foreign('status_id')->references('id')->on('list_statuses')->onDelete('cascade');
25:             $table->boolean('is_active')->default(true);
26:             $table->boolean('is_delete')->default(false);
27:             $table->string('verified_by')->nullable();
28:             $table->string('created_by')->nullable();
29:             $table->string('updated_by')->nullable();
30:             $table->timestamp('verified_at')->nullable();
31:             $table->timestamps();
40:         Schema::dropIfExists('scholars');
```

## `2025_12_17_160842_create_scholar_profiles_table.php`

```php
14:         Schema::create('scholar_profiles', function (Blueprint $table) {
15:             $table->id();
16:             $table->foreignId('scholar_id')->constrained('scholars')->cascadeOnDelete();
17:             $table->string('fname')->nullable();
18:             $table->string('lname')->nullable();
19:             $table->string('mname')->nullable();
20:             $table->string('suffix')->nullable();
21:             $table->string('photo')->nullable();
22:             $table->string('contact_no')->nullable();
23:             $table->date('birthdate')->nullable();
24:             $table->string('birthplace')->nullable();
25:             $table->string('religion')->nullable();
26:             $table->string('civil_status')->nullable();
27:             $table->string('email')->nullable();
28:             $table->string('sex')->nullable();
37:         Schema::dropIfExists('scholar_profiles');
```

## `2025_12_17_160919_create_scholar_addresses_table.php`

```php
14:         Schema::create('scholar_addresses', function (Blueprint $table) {
15:             $table->id();
16:             $table->foreignId('scholar_id')->constrained('scholars')->nullOnDelete();
17:             $table->text('address')->nullable();
18:             $table->string('barangay_custom')->nullable();
19:             $table->string('barangay_code')->nullable();
20:             $table->foreign('barangay_code')->references('code')->on('location_barangays')->onDelete('set null');
21:             $table->string('municipality_code')->nullable();
22:             $table->foreign('municipality_code')->references('code')->on('location_cities')->onDelete('set null');
23:             $table->string('province_code')->nullable();
24:             $table->foreign('province_code')->references('code')->on('location_provinces')->onDelete('set null');
25:             $table->string('region_code')->nullable();
26:             $table->foreign('region_code')->references('code')->on('location_regions')->onDelete('set null');
35:         Schema::dropIfExists('scholar_addresses');
```

## `2025_12_17_160920_create_scholar_parent_details_table.php`

```php
14:         Schema::create('scholar_parent_details', function (Blueprint $table) {
15:             $table->id();
16:             $table->foreignId('scholar_id')->nullable()->constrained('scholars')->cascadeOnDelete();
17:             $table->string('fname')->nullable();
18:             $table->string('id_no')->nullable();
19:             $table->string('id_date')->nullable();
20:             $table->string('id_place')->nullable();
21:             $table->string('companion')->nullable();
22:             $table->timestamps();
31:         Schema::dropIfExists('scholar_parent_details');
```

## `2026_01_05_114045_create_school_campus_infos_table.php`

```php
14:         Schema::create('school_campus_infos', function (Blueprint $table) {
15:             $table->id();
16:             $table->foreignId('campus_id')->nullable()->constrained('school_campuses')->onDelete('set null');
17:             $table->string('dean')->nullable();
18:             $table->string('registrar')->nullable();
19:             $table->string('contact')->nullable();
20:             $table->string('email')->nullable();
21:             $table->string('created_by')->nullable();
22:             $table->string('updated_by')->nullable();
23:             $table->boolean('is_active')->default(true);
24:             $table->boolean('is_delete')->default(false);
25:             $table->timestamps();
34:         Schema::dropIfExists('school_campus_infos');
```

## `2026_01_06_153203_create_school_campus_semesters_table.php`

```php
14:         Schema::create('school_campus_semesters', function (Blueprint $table) {
15:             $table->id();
16:             $table->foreignId('campus_id')->nullable()->constrained('school_campuses')->nullOnDelete();
17:             $table->foreignId('semester_id')->nullable()->constrained('list_references')->nullOnDelete();
18:             $table->date('start_date')->nullable();
19:             $table->date('end_date')->nullable();
20:             $table->date('submission_date')->nullable();
21:             $table->boolean('is_delete')->default(false);
22:             $table->boolean('is_active')->default(true);
23:             $table->timestamps();
32:         Schema::dropIfExists('school_campus_semesters');
```

## `2026_01_09_091439_create_school_campus_course_curriculum_subjects_table.php`

```php
14:         Schema::create('school_campus_course_curriculum_subjects', function (Blueprint $table) {
15:             $table->id();
16:             $table->foreignId('curriculum_id')->nullable()->constrained('school_campus_course_curriculums')->nullOnDelete();
17:             $table->foreignId('semester_id')->nullable()->constrained('list_references')->nullOnDelete();
18:             $table->foreignId('class_id')->nullable()->constrained('list_references')->nullOnDelete();
19:             $table->string('year')->nullable();
20:             $table->string('name')->nullable();
21:             $table->string('subject_code')->nullable();
22:             $table->string('subject_class')->nullable();
23:             $table->string('unit')->nullable();
24:             $table->string('created_by')->nullable();
25:             $table->string('updated_by')->nullable();
26:             $table->boolean('is_active')->default(true);
27:             $table->boolean('is_delete')->default(false);
28:             $table->timestamps();
37:         Schema::dropIfExists('school_campus_course_curriculum_subjects');
```

## `2026_02_02_151421_create_scholar_uploaded_files_table.php`

```php
14:         Schema::create('scholar_uploaded_files', function (Blueprint $table) {
15:             $table->id();
16:             $table->string('filename');
17:             $table->string('filepath');
18:             $table->string('region_office')->nullable();
19:             $table->string('created_by')->nullable();
20:             $table->string('validated_by')->nullable();
21:             $table->dateTime('validated_at')->nullable();
22:             $table->string('status')->nullable();
23:             $table->timestamps();
32:         Schema::dropIfExists('scholar_uploaded_files');
```

## `2026_02_03_162500_create_notifications_table.php`

```php
14:         Schema::create('notifications', function (Blueprint $table) {
15:             $table->uuid('id')->primary();
16:             $table->string('type');
17:             $table->morphs('notifiable');
18:             $table->text('data');
19:             $table->timestamp('read_at')->nullable();
20:             $table->timestamps();
29:         Schema::dropIfExists('notifications');
```

## `2026_02_12_101651_create_scholar_school_infos_table.php`

```php
14:         Schema::create('scholar_school_infos', function (Blueprint $table) {
15:             $table->id();
16:             $table->foreignId('scholar_id')->nullable()->constrained('scholars')->cascadeOnDelete();
17:             $table->string('campus_id')->nullable()->constrained('school_campuses')->cascadeOnDelete();
18:             $table->foreignId('campus_course_id')->nullable()->constrained('school_campus_courses')->cascadeOnDelete();
19:             $table->string('award_year')->nullable();
20:             $table->string('graduated_year')->nullable();
21:             $table->timestamps();
22:             $table->softDeletes();
31:         Schema::dropIfExists('scholar_school_infos');
```

## `2026_02_23_102036_create_geolocations_table.php`

```php
14:         Schema::create('geolocations', function (Blueprint $table) {
15:             $table->id();
16:             $table->foreignId('parent_id')
20:             $table->string('psgc_code')->unique();
21:             $table->string('name');
22:             $table->string('old_name')->nullable();
23:             $table->string('district')->nullable();
24:             $table->string('zip_code')->nullable();
25:             $table->string('type');
26:             $table->timestamps();
27:             $table->softDeletes();
28:             $table->index('parent_id');
29:             $table->index('type');
30:             $table->index(['parent_id', 'type']);
39:         Schema::dropIfExists('geolocations');
```

## `2026_02_23_133332_create_geolocation_files_table.php`

```php
14:         Schema::create('geolocation_files', function (Blueprint $table) {
15:             $table->id();
16:             $table->string('filename');
17:             $table->string('path');
18:             $table->string('created_by');
19:             $table->timestamps();
20:             $table->softDeletes();
29:         Schema::dropIfExists('geolocation_files');
```

## `2026_02_23_155132_remove_parent_id_from_geolocations_table.php`

```php
14:         Schema::table('geolocations', function (Blueprint $table) {
15:             $table->dropColumn('parent_id'); // remove the parent_id column
24:         Schema::table('geolocations', function (Blueprint $table) {
```

## `2026_02_23_155737_add_updated_by_from_geolocations_table.php`

```php
14:         Schema::table('geolocations', function (Blueprint $table) {
15:             $table->string('updated_by')->nullable();
24:         Schema::table('geolocations', function (Blueprint $table) {
```

## `2026_02_26_091734_add_award_year_from_scholars.php`

```php
14:         Schema::table('scholars', function (Blueprint $table) {
15:             $table->string('award_year')->nullable()->after('type_id');
18:         Schema::table('scholar_school_infos', function (Blueprint $table) {
19:             $table->dropColumn('award_year');
28:         Schema::table('scholars', function (Blueprint $table) {
```

## `2026_02_26_095819_change_campus_id_type_from_scholar_school_infos.php`

```php
14:         Schema::table('scholar_school_infos', function (Blueprint $table) {
16:             $table->dropColumn('campus_id');
17:             $table->foreignId('campus_id')->nullable()->constrained('school_campuses')->nullOnDelete();
26:         Schema::table('scholar_school_infos', function (Blueprint $table) {
```

## `2026_02_27_101228_add_validate_status_from_scholars.php`

```php
14:         Schema::table('scholars', function (Blueprint $table) {
15:             $table->string('validate_status')->nullable()->default('pending')->after('verified_at');
24:         Schema::table('scholars', function (Blueprint $table) {
```

## `2026_02_27_162658_create_scholar_school_grades_table.php`

```php
15:         Schema::create('scholar_term_records', function (Blueprint $table) {
16:             $table->id();
17:             $table->foreignId('scholar_id')
21:             $table->foreignId('scholar_school_id')
25:             $table->foreignId('term_id')->nullable()->constrained('list_references')->nullOnDelete();
26:             $table->foreignId('level_id')->nullable()->constrained('list_references')->nullOnDelete();
27:             $table->string('academic_year')->nullable();
28:             $table->timestamps();
29:             $table->softDeletes();
32:         Schema::create('scholar_school_grades', function (Blueprint $table) {
33:             $table->id();
34:             $table->foreignId('term_record_id')->nullable()->constrained('scholar_term_records')->nullOnDelete();
35:             $table->foreignId('subject_id')->nullable()->constrained('school_campus_course_subjects')->nullOnDelete();
36:             $table->string('remarks')->nullable();
37:             $table->foreignId('grades_id')->nullable()->constrained('list_statuses')->nullOnDelete();
38:             $table->timestamps();
47:         Schema::dropIfExists('scholar_school_grades');
```

## `2026_03_02_140928_create_curriculum_replications_table.php`

```php
14:         Schema::create('curriculum_replications', function (Blueprint $table) {
15:             $table->id();
16:             $table->foreignId('curriculum_id')
20:             $table->foreignId('user_id')
24:             $table->timestamps();
25:             $table->softDeletes();
34:         Schema::dropIfExists('curriculum_replications');
```

## `2026_03_04_131838_add_column_from_school_campus_course_curriculums.php`

```php
14:         Schema::table('school_campus_course_curriculums', function (Blueprint $table) {
15:             $table->boolean('is_duplicated')
27:         Schema::table('school_campus_course_curriculums', function (Blueprint $table) {
```

## `2026_03_05_104431_add_column_from_scholar_school_infos.php`

```php
14:         Schema::table('scholar_school_infos', function (Blueprint $table) {
15:             $table->foreignId('curriculum_id')
20:             $table->dropColumn('graduated_year');
32:         Schema::table('scholar_school_infos', function (Blueprint $table) {
```

## `2026_03_05_141555_add_column_from_scholar_school_infos.php`

```php
14:         Schema::table('scholar_school_infos', function (Blueprint $table) {
15:             $table->string('school_year')->nullable();
24:         Schema::table('scholar_school_infos', function (Blueprint $table) {
```

## `2026_03_05_153412_add_column_from_scholar_school_infos.php`

```php
14:         Schema::table('scholar_school_infos', function (Blueprint $table) {
15:             $table->foreignId('term_id')
27:         Schema::table('scholar_school_infos', function (Blueprint $table) {
```

## `2026_03_06_085530_add_column_from_scholar_term_records.php`

```php
14:         Schema::table('scholar_term_records', function (Blueprint $table) {
15:             $table->foreignId('term_type_id')
27:         Schema::table('scholar_term_records', function (Blueprint $table) {
```

## `2026_03_06_090759_remove_column_from_scholar_school_infos.php`

```php
14:         Schema::table('scholar_school_infos', function (Blueprint $table) {
15:             $table->dropForeign(['term_id']);
16:             $table->dropColumn('term_id');
17:             $table->foreignId('term_record_id')
29:         Schema::table('scholar_school_infos', function (Blueprint $table) {
```

## `2026_03_06_091242_remove_column_from_scholar_school_infos.php`

```php
14:         Schema::table('scholar_school_infos', function (Blueprint $table) {
15:             $table->dropForeign(['term_record_id']);
16:             $table->dropColumn('term_record_id');
25:         Schema::table('scholar_school_infos', function (Blueprint $table) {
```

## `2026_03_16_144019_add_column_school_year_from_school_campus_semesters.php`

```php
14:         Schema::table('school_campus_semesters', function (Blueprint $table) {
15:             $table->string('school_year')->nullable();
24:         Schema::table('school_campus_semesters', function (Blueprint $table) {
```

## `2026_03_31_075634_change_column_from_scholar_school_grades.php`

```php
14:         Schema::table('scholar_school_grades', function (Blueprint $table) {
15:             $table->dropForeign(['grades_id']);
18:             $table->dropColumn('grades_id');
19:             $table->unsignedBigInteger('grade_id')->nullable()->after('subject_id');
20:             $table->foreign('grade_id')->references('id')->on('school_campus_course_curriculum_subjects')->onDelete('set null');
29:         Schema::table('scholar_school_grades', function (Blueprint $table) {
```

## `2026_03_31_080319_change_column_from_scholar_school_grades.php`

```php
14:         Schema::table('scholar_school_grades', function (Blueprint $table) {
15:             $table->dropForeign(['grade_id']);
16:             $table->dropForeign(['subject_id']);
19:             $table->dropColumn('subject_id');
20:             $table->dropColumn('grade_id');
21:             $table->unsignedBigInteger('grade_id')->nullable()->after('subject_id');
22:             $table->foreign('grade_id')->references('id')->on('school_campus_grades')->onDelete('set null');
23:             $table->unsignedBigInteger('subject_id')->nullable()->after('subject_id');
24:             $table->foreign('subject_id')->references('id')->on('school_campus_course_curriculum_subjects')->onDelete('set null');
33:         Schema::table('scholar_school_grades', function (Blueprint $table) {
```

## `2026_04_06_135208_add_column_from_scholar_school_grades.php`

```php
14:         Schema::table('scholar_school_grades', function (Blueprint $table) {
15:             $table->string('is_deleted')->default(false)->after('grade_id');
24:         Schema::table('scholar_school_grades', function (Blueprint $table) {
```

## `2026_04_06_141137_modified_column_from_scholar_school_grades.php`

```php
14:         Schema::table('scholar_school_grades', function (Blueprint $table) {
15:             $table->dropColumn('is_deleted');
16:             $table->boolean('is_deleted')->default(false)->after('grade_id');
25:         Schema::table('scholar_school_grades', function (Blueprint $table) {
```

## `2026_04_14_162055_add_column_from_scholars.php`

```php
14:         Schema::table('scholars', function (Blueprint $table) {
15:             $table->string('activation_token')->nullable()->after('status');
16:             $table->timestamp('activated_at')->nullable()->after('activation_token');
25:         Schema::table('scholars', function (Blueprint $table) {
```

## `2026_05_05_102023_create_scholar_upload_temps_table.php`

```php
14:         Schema::create('scholar_upload_temps', function (Blueprint $table) {
15:             $table->id();
16:             $table->foreignId('file_id')->nullable()->constrained('scholar_uploaded_files')->nullOnDelete();
17:             $table->string('spas_no')->unique();
18:             $table->string('status')->nullable();
19:             $table->string('standing')->nullable();
20:             $table->string('scholarship_type')->nullable();
21:             $table->string('scholarship_subprogram')->nullable();
22:             $table->string('fname')->nullable();
23:             $table->string('lname')->nullable();
24:             $table->string('mname')->nullable();
25:             $table->string('suffix')->nullable();
26:             $table->enum('sex', ['M', 'F'])->nullable();
27:             $table->string('email')->unique()->nullable();
28:             $table->string('contact_no')->nullable();
29:             $table->date('birthdate')->nullable();
30:             $table->string('birthplace')->nullable();
31:             $table->string('civil_status')->nullable();
32:             $table->string('address')->nullable();
33:             $table->string('barangay')->nullable();
34:             $table->string('municipality')->nullable();
35:             $table->string('province')->nullable();
36:             $table->string('region')->nullable();
37:             $table->string('year_awarded')->nullable();
38:             $table->string('course')->nullable();
39:             $table->string('school')->nullable();
40:             $table->string('created_by')->nullable();
41:             $table->string('updated_by')->nullable();
42:             $table->string('verified_by')->nullable();
43:             $table->timestamp('verified_at')->nullable();
44:             $table->timestamps();
53:         Schema::dropIfExists('scholar_upload_temps');
```

## `2026_05_05_174824_create_batches_table.php`

```php
14:         Schema::create('batches', function (Blueprint $table) {
15:             $table->id();
16:             $table->string('name')->unique();
17:             $table->string('region')->nullable();
18:             $table->string('academic_term')->nullable();
19:             $table->string('school_year')->nullable();
20:             $table->boolean('is_lock')->default(false);
21:             $table->timestamps();
22:             $table->softDeletes();
31:         Schema::dropIfExists('batches');
```

## `2026_05_05_174901_create_batches_logs_table.php`

```php
14:         Schema::create('batches_logs', function (Blueprint $table) {
15:             $table->id();
16:             $table->foreignId('batch_id')
20:             $table->enum('status', ['pending', 'approved', 'rejected', 'validated'])
22:             $table->text('remarks')->nullable();
23:             $table->string('action_by')->nullable();
24:             $table->timestamps();
33:         Schema::dropIfExists('batches_logs');
```

## `2026_05_05_174939_create_batch_recipients_table.php`

```php
14:         Schema::create('batch_recipients', function (Blueprint $table) {
15:             $table->id();
16:             $table->timestamps();
25:         Schema::dropIfExists('batch_recipients');
```

## `2026_05_05_175135_create_recipient_withhelds_table.php`

```php
14:         Schema::create('recipient_withhelds', function (Blueprint $table) {
15:             $table->id();
16:             $table->foreignId('recipient_id')
20:             $table->decimal('total_amount', 10, 2)->default(0);
21:             $table->text('remarks')->nullable();
22:             $table->string('status')->nullable();
31:         Schema::dropIfExists('recipient_withhelds');
```

## `2026_05_05_175212_create_recipient_stipends_table.php`

```php
14:         Schema::create('recipient_stipends', function (Blueprint $table) {
15:             $table->id();
16:             $table->foreignId('recipient_id')
20:             $table->decimal('amount', 10, 2)->default(0);
21:             $table->string('month')->nullable();
22:             $table->string('status')->nullable();
23:             $table->timestamps();
32:         Schema::dropIfExists('recipient_stipends');
```

## `2026_05_05_175235_create_recipient_allowances_table.php`

```php
14:         Schema::create('recipient_allowances', function (Blueprint $table) {
15:             $table->id();
16:             $table->timestamps();
25:         Schema::dropIfExists('recipient_allowances');
```

## `2026_05_05_175307_add_column_from_batch_recipients.php`

```php
14:         Schema::table('batch_recipients', function (Blueprint $table) {
15:             $table->string('email')->nullable()->unique();
16:             $table->date('birthday')->nullable();
25:         Schema::table('batch_recipients', function (Blueprint $table) {
```

## `2026_05_05_175359_add_column_from_list_agencies.php`

```php
15:             Schema::table('list_agencies', function (Blueprint $table) {
16:                 $table->string('code')->nullable();
26:         Schema::table('list_agencies', function (Blueprint $table) {
28:                 $table->dropColumn('code');
```

## `2026_05_05_175421_add_column_from_batch_recipients.php`

```php
14:         Schema::table('batch_recipients', function (Blueprint $table) {
15:             $table->string('period')->nullable();
24:         Schema::table('batch_recipients', function (Blueprint $table) {
```

## `2026_05_07_104643_add_column_from_scholar_upload_temps.php`

```php
14:         Schema::table('scholar_upload_temps', function (Blueprint $table) {
15:             $table->string('change_school')->nullable()->after('school');
16:             $table->string('change_course')->nullable()->after('change_school');
17:             $table->json('change_fulladdress')->nullable();
26:         Schema::table('scholar_upload_temps', function (Blueprint $table) {
```

## `2026_05_09_174114_add_column_from_scholar_upload_temps.php`

```php
14:         Schema::table('scholar_upload_temps', function (Blueprint $table) {
15:             $table->timestamp('publish_at')->nullable();
16:             $table->string('publish_by')->nullable();
25:         Schema::table('scholar_upload_temps', function (Blueprint $table) {
```

## `2026_05_11_093740_add_column_from_users.php`

```php
14:         Schema::table('users', function (Blueprint $table) {
15:             $table->foreignId('school_id')->nullable()->constrained('school_campuses')->nullOnDelete();
24:         Schema::table('users', function (Blueprint $table) {
```

## `2026_06_04_142230_create_scholar_landbanks_table.php`

```php
14:         Schema::create('scholar_landbanks', function (Blueprint $table) {
15:             $table->id();
16:             $table->foreignId('scholar_id')
20:             $table->string('account_number')->nullable();
21:             $table->string('account_name')->nullable();
22:             $table->string('uploaded_type')->nullable();
23:             $table->string('uploaded_file')->nullable();
24:             $table->string('created_by')->nullable();
25:             $table->string('updated_by')->nullable();
26:             $table->timestamps();
35:         Schema::dropIfExists('scholar_landbanks');
```

## `2026_06_16_132252_create_request_histories_table.php`

```php
14:         Schema::create('request_histories', function (Blueprint $table) {
15:             $table->id();
16:             $table->foreignId('scholar_id')->nullable()->constrained('scholars');
17:             $table->string('request_type')->nullable();
18:             $table->json('previous')->nullable();
19:             $table->json('changes')->nullable();
20:             $table->timestamp('created_at');
21:             $table->string('created_by');
30:         Schema::dropIfExists('request_histories');
```

## `2026_06_16_172357_add_column_to_request_histories_table.php`

```php
14:         Schema::table('request_histories', function (Blueprint $table) {
15:             $table->string('request_no')->nullable();
24:         Schema::table('request_histories', function (Blueprint $table) {
25:             $table->dropColumn('request_no');
```

## `2026_06_22_080000_refine_payroll_management_tables.php`

```php
17:                 Schema::table('batch_recipients', function (Blueprint $table) {
18:                     $table->dropUnique('batch_recipients_email_unique');
24:             Schema::table('batch_recipients', function (Blueprint $table) {
26:                     $table->foreignId('batch_id')
33:                     $table->foreignId('scholar_id')
40:                     $table->string('account_no')->nullable();
44:                     $table->string('scholarship_status')->nullable();
48:                     $table->decimal('total_stipend', 12, 2)->default(0);
52:                     $table->decimal('total_withheld', 12, 2)->default(0);
56:                     $table->decimal('learning_materials_amount', 12, 2)->default(0);
60:                     $table->decimal('clothing_amount', 12, 2)->default(0);
64:                     $table->decimal('grand_total', 12, 2)->default(0);
68:                     $table->text('remarks')->nullable();
72:                     $table->string('status')->default('pending');
77:                 DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS batch_recipients_batch_id_scholar_id_unique ON batch_recipients (batch_id, scholar_id)');
84:             Schema::table('recipient_stipends', function (Blueprint $table) {
86:                     $table->unsignedTinyInteger('month_no')->nullable();
90:                     $table->text('remarks')->nullable();
96:             Schema::table('recipient_withhelds', function (Blueprint $table) {
98:                     $table->unsignedTinyInteger('month_no')->nullable();
102:                     $table->timestamps();
107:         Schema::dropIfExists('recipient_allowances');
116:             Schema::create('recipient_allowances', function (Blueprint $table) {
117:                 $table->id();
118:                 $table->timestamps();
123:             Schema::table('recipient_withhelds', function (Blueprint $table) {
125:                     $table->dropColumn('month_no');
129:                     $table->dropTimestamps();
135:             Schema::table('recipient_stipends', function (Blueprint $table) {
137:                     $table->dropColumn('month_no');
141:                     $table->dropColumn('remarks');
147:             Schema::table('batch_recipients', function (Blueprint $table) {
160:                         $table->dropColumn($column);
165:                     $table->dropForeign(['scholar_id']);
166:                     $table->dropColumn('scholar_id');
170:                     $table->dropForeign(['batch_id']);
171:                     $table->dropColumn('batch_id');
```

## `2026_06_22_080100_update_batch_recipient_payroll_indexes.php`

```php
20:             Schema::table('batch_recipients', function (Blueprint $table) {
21:                 $table->dropUnique('batch_recipients_email_unique');
28:             DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS batch_recipients_batch_id_scholar_id_unique ON batch_recipients (batch_id, scholar_id)');
44:             DB::statement('DROP INDEX IF EXISTS batch_recipients_batch_id_scholar_id_unique');
```

## `2026_06_22_080200_revise_payroll_batch_statuses.php`

```php
18:         DB::statement('ALTER TABLE batches_logs DROP CONSTRAINT IF EXISTS batches_logs_status_check');
19:         DB::statement('ALTER TABLE batches_logs ALTER COLUMN status DROP DEFAULT');
20:         DB::statement('ALTER TABLE batches_logs ALTER COLUMN status TYPE VARCHAR(255)');
27:         DB::statement("ALTER TABLE batches_logs ALTER COLUMN status SET DEFAULT 'draft'");
44:         DB::statement("ALTER TABLE batches_logs ALTER COLUMN status SET DEFAULT 'pending'");
```

## `2026_06_22_080300_add_term_level_ids_to_batches.php`

```php
14:         Schema::table('batches', function (Blueprint $table) {
16:                 $table->foreignId('term_id')
24:                 $table->foreignId('level_id')
38:         Schema::table('batches', function (Blueprint $table) {
40:                 $table->dropForeign(['level_id']);
41:                 $table->dropColumn('level_id');
45:                 $table->dropForeign(['term_id']);
46:                 $table->dropColumn('term_id');
```

## `2026_06_22_080400_update_scholar_term_record_verification_status_check.php`

```php
18:         DB::statement('ALTER TABLE scholar_term_records DROP CONSTRAINT IF EXISTS scholar_term_records_verification_status_check');
19:         DB::statement("
46:         DB::statement('ALTER TABLE scholar_term_records DROP CONSTRAINT IF EXISTS scholar_term_records_verification_status_check');
47:         DB::statement("
```

## `2026_06_23_000000_create_recipient_allowances_table.php`

```php
16:             Schema::create('recipient_allowances', function (Blueprint $table) {
17:                 $table->id();
18:                 $table->foreignId('recipient_id')
21:                 $table->string('classification');
22:                 $table->decimal('amount', 12, 2)->default(0);
23:                 $table->text('remarks')->nullable();
24:                 $table->string('status')->default('pending');
25:                 $table->timestamps();
28:             Schema::table('recipient_allowances', function (Blueprint $table) {
30:                     $table->foreignId('recipient_id')
37:                     $table->string('classification')->nullable();
41:                     $table->decimal('amount', 12, 2)->default(0);
45:                     $table->text('remarks')->nullable();
49:                     $table->string('status')->default('pending');
54:         DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS recipient_allowances_recipient_id_classification_unique ON recipient_allowances (recipient_id, classification)');
66:         DB::statement('DROP INDEX IF EXISTS recipient_allowances_recipient_id_classification_unique');
67:         Schema::dropIfExists('recipient_allowances');
```

## `2026_06_23_000100_add_academic_status_to_scholars_table.php`

```php
19:         Schema::table('scholars', function (Blueprint $table) {
21:                 $table->string('academic_status')->default('Ongoing')->after('status_id');
40:         Schema::table('scholars', function (Blueprint $table) {
41:             $table->dropColumn('academic_status');
```

## `2026_06_23_000200_create_allowance_types_and_link_recipient_allowances.php`

```php
13:             Schema::create('allowance_types', function (Blueprint $table) {
14:                 $table->id();
15:                 $table->string('code')->unique();
16:                 $table->string('name');
17:                 $table->decimal('default_amount', 12, 2)->nullable();
18:                 $table->decimal('max_amount', 12, 2)->nullable();
19:                 $table->boolean('is_variable')->default(false);
20:                 $table->string('frequency')->nullable();
21:                 $table->text('description')->nullable();
22:                 $table->boolean('is_active')->default(true);
23:                 $table->timestamps();
108:             Schema::table('recipient_allowances', function (Blueprint $table) {
109:                 $table->foreignId('allowance_type_id')
138:             Schema::table('recipient_allowances', function (Blueprint $table) {
139:                 $table->dropConstrainedForeignId('allowance_type_id');
143:         Schema::dropIfExists('allowance_types');
```

## `2026_06_23_000300_rename_connectivity_allowance_type_code.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_06_25_142252_add_column_to_term_records.php`

```php
14:         Schema::table('scholar_term_records', function (Blueprint $table) {
15:             $table->string('rejection_reason')->nullable();
25:         Schema::table('scholar_term_records', function (Blueprint $table) {
```

## `2026_06_29_000000_create_scholar_addresses_surrent_table.php`

```php
14:         Schema::create('scholar_addresses_current', function (Blueprint $table) {
15:             $table->id();
16:             $table->foreignId('scholar_id')->constrained('scholars')->nullOnDelete();
17:             $table->text('address')->nullable();
18:             $table->string('barangay_custom')->nullable();
19:             $table->string('barangay_code')->nullable();
20:             $table->foreign('barangay_code')->references('code')->on('location_barangays')->onDelete('set null');
21:             $table->string('municipality_code')->nullable();
22:             $table->foreign('municipality_code')->references('code')->on('location_cities')->onDelete('set null');
23:             $table->string('province_code')->nullable();
24:             $table->foreign('province_code')->references('code')->on('location_provinces')->onDelete('set null');
25:             $table->string('region_code')->nullable();
26:             $table->foreign('region_code')->references('code')->on('location_regions')->onDelete('set null');
35:         Schema::dropIfExists('scholar_addresses_current');
```

## `2026_06_29_000100_create_list_permissions_table.php`

```php
14:         Schema::create('list_permissions', function (Blueprint $table) {
15:             $table->id();
16:             $table->string('name')->unique();
17:             $table->string('label')->nullable();
18:             $table->string('group_name')->nullable();
19:             $table->text('description')->nullable();
20:             $table->boolean('is_active')->default(true);
21:             $table->timestamps();
30:         Schema::dropIfExists('list_permissions');
```

## `2026_06_29_000200_create_list_role_permissions_table.php`

```php
14:         Schema::create('list_role_permissions', function (Blueprint $table) {
15:             $table->id();
16:             $table->foreignId('role_id')->constrained('list_roles')->cascadeOnDelete();
17:             $table->foreignId('permission_id')->constrained('list_permissions')->cascadeOnDelete();
18:             $table->timestamps();
20:             $table->unique(['role_id', 'permission_id']);
29:         Schema::dropIfExists('list_role_permissions');
```

## `2026_06_29_000300_create_list_permission_routes_table.php`

```php
14:         Schema::create('list_permission_routes', function (Blueprint $table) {
15:             $table->id();
16:             $table->string('route_name')->unique();
17:             $table->foreignId('permission_id')->constrained('list_permissions')->cascadeOnDelete();
18:             $table->timestamps();
27:         Schema::dropIfExists('list_permission_routes');
```

## `2026_06_29_000400_drop_legacy_user_action_flags.php`

```php
14:         Schema::table('users', function (Blueprint $table) {
20:                 $table->dropColumn($columns);
30:         Schema::table('users', function (Blueprint $table) {
32:                 $table->boolean('can_edit')->default(false)->after('password');
36:                 $table->boolean('can_create')->default(false)->after('can_edit');
40:                 $table->boolean('can_delete')->default(false)->after('can_create');
```

## `2026_07_06_180324_create_activity_logs_table.php`

```php
14:         Schema::create('activity_logs', function (Blueprint $table) {
15:             $table->id();
16:             $table->foreignId('scholar_id')->constrained('scholars')->onDelete('cascade');
17:             $table->json('previous_data')->nullable();
18:             $table->json('changes_data')->nullable();
19:             $table->string('created_by')->nullable();
20:             $table->timestamps();
30:         Schema::dropIfExists('activity_logs');
```

## `2026_07_07_104825_add_request_type_to_activity_logs_table.php`

```php
14:         Schema::table('activity_logs', function (Blueprint $table) {
15:             $table->string('request_type')->nullable();
24:         Schema::table('activity_logs', function (Blueprint $table) {
25:             $table->dropColumn('request_type');
```

## `2026_07_12_000000_create_document_categories_table.php`

```php
11:         Schema::create('document_categories', function (Blueprint $table) {
12:             $table->id();
13:             $table->string('name');
14:             $table->text('description')->nullable();
15:             $table->integer('sort_order')->default(0);
16:             $table->boolean('is_active')->default(true);
17:             $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
18:             $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
19:             $table->timestamps();
20:             $table->softDeletes();
26:         Schema::dropIfExists('document_categories');
```

## `2026_07_12_000001_create_documents_table.php`

```php
11:         Schema::create('documents', function (Blueprint $table) {
12:             $table->id();
13:             $table->foreignId('document_category_id')->nullable()->constrained('document_categories')->nullOnDelete();
14:             $table->string('title');
15:             $table->text('description')->nullable();
16:             $table->string('file_path');
17:             $table->string('original_filename');
18:             $table->string('mime_type')->nullable();
19:             $table->unsignedBigInteger('file_size')->default(0);
20:             $table->boolean('is_active')->default(true);
21:             $table->timestamp('published_at')->nullable();
22:             $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
23:             $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
24:             $table->timestamps();
25:             $table->softDeletes();
31:         Schema::dropIfExists('documents');
```

## `2026_07_12_000002_create_document_targets_table.php`

```php
11:         Schema::create('document_targets', function (Blueprint $table) {
12:             $table->id();
13:             $table->foreignId('document_id')->constrained('documents')->cascadeOnDelete();
14:             $table->string('target_type');
15:             $table->string('target_id')->nullable();
16:             $table->timestamps();
18:             $table->index(['target_type', 'target_id']);
19:             $table->unique(['document_id', 'target_type', 'target_id']);
25:         Schema::dropIfExists('document_targets');
```

## `2026_07_12_000003_add_documents_route_to_list_routes.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_07_12_000004_add_document_permissions.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_07_12_000005_create_video_resources_table.php`

```php
11:         Schema::create('video_resources', function (Blueprint $table) {
12:             $table->id();
13:             $table->string('title');
14:             $table->text('description')->nullable();
15:             $table->string('video_url');
16:             $table->string('thumbnail_url')->nullable();
17:             $table->string('thumbnail_path')->nullable();
18:             $table->boolean('is_active')->default(true);
19:             $table->timestamp('published_at')->nullable();
20:             $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
21:             $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
22:             $table->timestamps();
23:             $table->softDeletes();
29:         Schema::dropIfExists('video_resources');
```

## `2026_07_12_000006_create_video_resource_targets_table.php`

```php
11:         Schema::create('video_resource_targets', function (Blueprint $table) {
12:             $table->id();
13:             $table->foreignId('video_resource_id')->constrained('video_resources')->cascadeOnDelete();
14:             $table->string('target_type');
15:             $table->string('target_id')->nullable();
16:             $table->timestamps();
18:             $table->index(['target_type', 'target_id']);
19:             $table->unique(['video_resource_id', 'target_type', 'target_id']);
25:         Schema::dropIfExists('video_resource_targets');
```

## `2026_07_12_000007_add_video_resources_route_and_permissions.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_07_12_000008_add_thumbnail_path_to_video_resources_table.php`

```php
11:         Schema::table('video_resources', function (Blueprint $table) {
13:                 $table->string('thumbnail_path')->nullable()->after('thumbnail_url');
20:         Schema::table('video_resources', function (Blueprint $table) {
22:                 $table->dropColumn('thumbnail_path');
```

## `2026_07_12_000009_reorganize_library_navigation.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_07_12_000010_rename_stipend_navigation_to_financial_assistance.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_07_12_000011_rename_content_library_items.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_07_20_000001_add_payroll_file_to_batches_logs.php`

```php
11:         Schema::table('batches_logs', function (Blueprint $table) {
12:             $table->string('payroll_file_path')->nullable()->after('remarks');
13:             $table->string('payroll_file_name')->nullable()->after('payroll_file_path');
19:         Schema::table('batches_logs', function (Blueprint $table) {
20:             $table->dropColumn(['payroll_file_path', 'payroll_file_name']);
```

## `2026_07_21_000001_create_payroll_batch_activity_logs_table.php`

```php
11:         Schema::create('payroll_batch_activity_logs', function (Blueprint $table) {
12:             $table->id();
13:             $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
14:             $table->foreignId('batch_recipient_id')->nullable()->constrained('batch_recipients')->nullOnDelete();
15:             $table->foreignId('scholar_id')->nullable()->constrained('scholars')->nullOnDelete();
16:             $table->string('action');
17:             $table->string('old_status')->nullable();
18:             $table->string('new_status')->nullable();
19:             $table->text('remarks')->nullable();
20:             $table->json('metadata')->nullable();
21:             $table->string('created_by')->nullable();
22:             $table->timestamps();
24:             $table->index(['batch_id', 'created_at']);
25:             $table->index(['batch_recipient_id', 'created_at']);
26:             $table->index(['scholar_id', 'created_at']);
32:         Schema::dropIfExists('payroll_batch_activity_logs');
```

## `2026_07_21_000002_add_defer_fields_to_batch_recipients_table.php`

```php
12:         Schema::table('batch_recipients', function (Blueprint $table) {
14:                 $table->boolean('is_deferred_from_payroll')->default(false);
18:                 $table->string('deferred_by')->nullable();
22:                 $table->timestamp('deferred_at')->nullable();
33:         Schema::table('batch_recipients', function (Blueprint $table) {
36:                     $table->dropColumn($column);
```

## `2026_07_21_000003_create_payroll_batch_revisions_table.php`

```php
11:         Schema::create('payroll_batch_revisions', function (Blueprint $table) {
12:             $table->id();
13:             $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
14:             $table->unsignedInteger('revision_no');
15:             $table->json('recipients_snapshot');
16:             $table->json('totals_snapshot')->nullable();
17:             $table->string('payroll_file_path')->nullable();
18:             $table->string('payroll_file_name')->nullable();
19:             $table->string('submitted_by')->nullable();
20:             $table->timestamp('submitted_at')->nullable();
21:             $table->timestamps();
23:             $table->unique(['batch_id', 'revision_no']);
24:             $table->index(['batch_id', 'submitted_at']);
30:         Schema::dropIfExists('payroll_batch_revisions');
```

## `2026_07_21_000004_create_payroll_batch_revision_recipients_table.php`

```php
11:         Schema::create('payroll_batch_revision_recipients', function (Blueprint $table) {
12:             $table->id();
13:             $table->foreignId('payroll_batch_revision_id')->constrained('payroll_batch_revisions')->cascadeOnDelete();
14:             $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
15:             $table->foreignId('batch_recipient_id')->nullable()->constrained('batch_recipients')->nullOnDelete();
16:             $table->foreignId('scholar_id')->nullable()->constrained('scholars')->nullOnDelete();
17:             $table->json('row_payload');
18:             $table->boolean('is_deferred')->default(false);
19:             $table->string('deferred_by')->nullable();
20:             $table->timestamp('deferred_at')->nullable();
21:             $table->timestamps();
23:             $table->index(['batch_id', 'scholar_id']);
24:             $table->index(['payroll_batch_revision_id', 'is_deferred']);
30:         Schema::dropIfExists('payroll_batch_revision_recipients');
```

## `2026_07_21_000005_rename_scholar_process_standing_to_scholarship_status.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_07_21_000006_rename_deferred_payroll_fields_to_for_removal.php`

```php
13:             Schema::table('batch_recipients', function (Blueprint $table) {
15:                     $table->renameColumn('is_deferred_from_payroll', 'is_for_removal_from_payroll');
19:                     $table->renameColumn('deferred_by', 'marked_for_removal_by');
23:                     $table->renameColumn('deferred_at', 'marked_for_removal_at');
33:             Schema::table('payroll_batch_revision_recipients', function (Blueprint $table) {
35:                     $table->renameColumn('is_deferred', 'is_for_removal');
39:                     $table->renameColumn('deferred_by', 'marked_for_removal_by');
43:                     $table->renameColumn('deferred_at', 'marked_for_removal_at');
65:             Schema::table('batch_recipients', function (Blueprint $table) {
67:                     $table->renameColumn('is_for_removal_from_payroll', 'is_deferred_from_payroll');
71:                     $table->renameColumn('marked_for_removal_by', 'deferred_by');
75:                     $table->renameColumn('marked_for_removal_at', 'deferred_at');
81:             Schema::table('payroll_batch_revision_recipients', function (Blueprint $table) {
83:                     $table->renameColumn('is_for_removal', 'is_deferred');
87:                     $table->renameColumn('marked_for_removal_by', 'deferred_by');
91:                     $table->renameColumn('marked_for_removal_at', 'deferred_at');
```

## `2026_07_21_000007_add_financial_assistance_performance_indexes.php`

```php
10:         DB::statement('CREATE INDEX IF NOT EXISTS idx_batches_logs_batch_created ON batches_logs (batch_id, created_at DESC)');
11:         DB::statement('CREATE INDEX IF NOT EXISTS idx_batch_recipients_batch ON batch_recipients (batch_id)');
12:         DB::statement('CREATE INDEX IF NOT EXISTS idx_batch_recipients_scholar ON batch_recipients (scholar_id)');
13:         DB::statement('CREATE INDEX IF NOT EXISTS idx_batch_recipients_batch_scholar ON batch_recipients (batch_id, scholar_id)');
14:         DB::statement('CREATE INDEX IF NOT EXISTS idx_recipient_stipends_recipient_month ON recipient_stipends (recipient_id, month_no)');
15:         DB::statement('CREATE INDEX IF NOT EXISTS idx_recipient_withhelds_recipient_month ON recipient_withhelds (recipient_id, month_no)');
16:         DB::statement('CREATE INDEX IF NOT EXISTS idx_recipient_allowances_recipient ON recipient_allowances (recipient_id)');
17:         DB::statement('CREATE INDEX IF NOT EXISTS idx_payroll_activity_logs_batch_action_created ON payroll_batch_activity_logs (batch_id, action, created_at DESC)');
18:         DB::statement('CREATE INDEX IF NOT EXISTS idx_payroll_revisions_batch_revision ON payroll_batch_revisions (batch_id, revision_no DESC)');
19:         DB::statement('CREATE INDEX IF NOT EXISTS idx_revision_recipients_revision ON payroll_batch_revision_recipients (payroll_batch_revision_id)');
24:         DB::statement('DROP INDEX IF EXISTS idx_batches_logs_batch_created');
25:         DB::statement('DROP INDEX IF EXISTS idx_batch_recipients_batch');
26:         DB::statement('DROP INDEX IF EXISTS idx_batch_recipients_scholar');
27:         DB::statement('DROP INDEX IF EXISTS idx_batch_recipients_batch_scholar');
28:         DB::statement('DROP INDEX IF EXISTS idx_recipient_stipends_recipient_month');
29:         DB::statement('DROP INDEX IF EXISTS idx_recipient_withhelds_recipient_month');
30:         DB::statement('DROP INDEX IF EXISTS idx_recipient_allowances_recipient');
31:         DB::statement('DROP INDEX IF EXISTS idx_payroll_activity_logs_batch_action_created');
32:         DB::statement('DROP INDEX IF EXISTS idx_payroll_revisions_batch_revision');
33:         DB::statement('DROP INDEX IF EXISTS idx_revision_recipients_revision');
```

## `2026_07_22_000001_add_move_notice_fields_to_batch_recipients.php`

```php
11:         Schema::table('batch_recipients', function (Blueprint $table) {
13:                 $table->foreignId('moved_from_batch_id')
21:                 $table->string('moved_from_batch_name')->nullable()->after('moved_from_batch_id');
25:                 $table->text('moved_from_reason')->nullable()->after('moved_from_batch_name');
29:                 $table->string('moved_from_marked_by')->nullable()->after('moved_from_reason');
33:                 $table->timestamp('moved_from_marked_at')->nullable()->after('moved_from_marked_by');
37:                 $table->timestamp('moved_notice_cleared_at')->nullable()->after('moved_from_marked_at');
41:         Schema::table('batch_recipients', function (Blueprint $table) {
42:             $table->index(
51:         Schema::table('batch_recipients', function (Blueprint $table) {
52:             $table->dropIndex('idx_batch_recipients_move_notice');
55:                 $table->dropForeign(['moved_from_batch_id']);
67:                     $table->dropColumn($column);
```

## `2026_07_22_000002_add_current_status_to_batches.php`

```php
12:         Schema::table('batches', function (Blueprint $table) {
14:                 $table->string('status')->default('draft')->after('is_lock');
23:         DB::statement("
35:         DB::statement('CREATE INDEX IF NOT EXISTS idx_batches_accepting_lookup ON batches (region, school_year, term_id, status)');
36:         DB::statement('CREATE INDEX IF NOT EXISTS idx_batches_accepting_name_lookup ON batches (region, school_year, academic_term, status)');
37:         DB::statement('CREATE INDEX IF NOT EXISTS idx_batches_status_created ON batches (status, created_at DESC)');
42:         DB::statement('DROP INDEX IF EXISTS idx_batches_status_created');
43:         DB::statement('DROP INDEX IF EXISTS idx_batches_accepting_name_lookup');
44:         DB::statement('DROP INDEX IF EXISTS idx_batches_accepting_lookup');
46:         Schema::table('batches', function (Blueprint $table) {
48:                 $table->dropColumn('status');
```

## `2026_07_22_000003_remove_level_id_from_payroll_batches.php`

```php
12:         DB::statement('DROP INDEX IF EXISTS idx_batches_accepting_lookup');
13:         DB::statement('DROP INDEX IF EXISTS idx_batches_accepting_name_lookup');
15:         DB::statement('CREATE INDEX IF NOT EXISTS idx_batches_accepting_lookup ON batches (region, school_year, term_id, status)');
16:         DB::statement('CREATE INDEX IF NOT EXISTS idx_batches_accepting_name_lookup ON batches (region, school_year, academic_term, status)');
18:         Schema::table('batches', function (Blueprint $table) {
20:                 $table->dropForeign(['level_id']);
21:                 $table->dropColumn('level_id');
28:         Schema::table('batches', function (Blueprint $table) {
30:                 $table->foreignId('level_id')
38:         DB::statement('DROP INDEX IF EXISTS idx_batches_accepting_lookup');
39:         DB::statement('DROP INDEX IF EXISTS idx_batches_accepting_name_lookup');
41:         DB::statement('CREATE INDEX IF NOT EXISTS idx_batches_accepting_lookup ON batches (region, school_year, term_id, level_id, status)');
42:         DB::statement('CREATE INDEX IF NOT EXISTS idx_batches_accepting_name_lookup ON batches (region, school_year, academic_term, level_id, status)');
```

## `2026_07_23_000001_update_payroll_permissions.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_07_23_000002_split_broad_permissions.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_07_23_000003_add_scholar_submissions_route.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_07_23_000004_create_scholar_academic_history_submissions_table.php`

```php
11:         Schema::create('scholar_academic_history_submissions', function (Blueprint $table) {
12:             $table->id();
13:             $table->foreignId('scholar_id')->nullable()->constrained('scholars')->nullOnDelete();
14:             $table->string('spas_no')->nullable()->index();
15:             $table->string('status')->default('draft')->index();
16:             $table->timestamp('submitted_at')->nullable();
17:             $table->timestamp('reviewed_at')->nullable();
18:             $table->foreignId('reviewed_by')->nullable()->constrained('users')->nullOnDelete();
19:             $table->text('return_reason')->nullable();
20:             $table->timestamps();
21:             $table->softDeletes();
24:         Schema::create('scholar_academic_history_terms', function (Blueprint $table) {
25:             $table->id();
26:             $table->foreignId('submission_id')->constrained('scholar_academic_history_submissions')->cascadeOnDelete();
27:             $table->foreignId('campus_id')->nullable()->constrained('school_campuses')->nullOnDelete();
28:             $table->foreignId('campus_course_id')->nullable()->constrained('school_campus_courses')->nullOnDelete();
29:             $table->foreignId('curriculum_id')->nullable()->constrained('school_campus_course_curriculums')->nullOnDelete();
30:             $table->foreignId('term_id')->nullable()->constrained('list_references')->nullOnDelete();
31:             $table->foreignId('level_id')->nullable()->constrained('list_references')->nullOnDelete();
32:             $table->string('academic_year')->nullable();
33:             $table->string('school_name')->nullable();
34:             $table->string('course_name')->nullable();
35:             $table->string('term_name')->nullable();
36:             $table->string('level_name')->nullable();
37:             $table->text('remarks')->nullable();
38:             $table->timestamps();
39:             $table->softDeletes();
42:         Schema::create('scholar_academic_history_subjects', function (Blueprint $table) {
43:             $table->id();
44:             $table->foreignId('history_term_id')->constrained('scholar_academic_history_terms')->cascadeOnDelete();
45:             $table->foreignId('matched_subject_id')->nullable()->constrained('school_campus_course_curriculum_subjects')->nullOnDelete();
46:             $table->string('subject_name')->nullable();
47:             $table->string('subject_code')->nullable();
48:             $table->string('subject_class')->nullable();
49:             $table->decimal('unit', 8, 2)->nullable();
50:             $table->string('grade')->nullable();
51:             $table->boolean('is_failed')->default(false);
52:             $table->boolean('is_incomplete')->default(false);
53:             $table->boolean('is_dropped')->default(false);
54:             $table->text('remarks')->nullable();
55:             $table->timestamps();
56:             $table->softDeletes();
59:         Schema::create('scholar_academic_history_files', function (Blueprint $table) {
60:             $table->id();
61:             $table->foreignId('submission_id')->constrained('scholar_academic_history_submissions')->cascadeOnDelete();
62:             $table->foreignId('history_term_id')->nullable()->constrained('scholar_academic_history_terms')->cascadeOnDelete();
63:             $table->string('file_name')->nullable();
64:             $table->string('file_path');
65:             $table->string('file_type')->nullable();
66:             $table->string('mime_type')->nullable();
67:             $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
68:             $table->timestamps();
69:             $table->softDeletes();
75:         Schema::dropIfExists('scholar_academic_history_files');
76:         Schema::dropIfExists('scholar_academic_history_subjects');
77:         Schema::dropIfExists('scholar_academic_history_terms');
78:         Schema::dropIfExists('scholar_academic_history_submissions');
```

## `2026_07_29_080702_create_audit_logs_table.php`

```php
14:         Schema::create('audit_logs', function (Blueprint $table) {
15:             $table->id();
16:             $table->foreignId('user_id')->constrained('users')->onDelete('set null');
17:             $table->json('old_data')->nullable();
18:             $table->json('new_data')->nullable();
19:             $table->string('action')->nullable();
20:             $table->timestamp('created_at')->useCurrent();
29:         Schema::dropIfExists('audit_logs');
```

## `2026_08_03_000001_reactivate_scholars_update_permission.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_08_04_000001_grant_regional_scholar_academic_record_update.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_08_04_000002_add_generated_excel_to_batches.php`

```php
11:         Schema::table('batches', function (Blueprint $table) {
12:             $table->string('generated_excel_path')->nullable()->after('status');
13:             $table->string('generated_excel_name')->nullable()->after('generated_excel_path');
14:             $table->timestamp('generated_excel_at')->nullable()->after('generated_excel_name');
20:         Schema::table('batches', function (Blueprint $table) {
21:             $table->dropColumn([
```

## `2026_08_05_000001_add_historical_import_fields_to_batches.php`

```php
11:         Schema::table('batches', function (Blueprint $table) {
13:                 $table->string('source')->default('system')->index();
17:                 $table->boolean('is_historical')->default(false)->index();
21:                 $table->string('imported_by')->nullable();
25:                 $table->timestamp('imported_at')->nullable();
29:                 $table->string('import_file_path')->nullable();
33:                 $table->string('import_file_name')->nullable();
40:         Schema::table('batches', function (Blueprint $table) {
50:                     $table->dropColumn($column);
```

## `2026_08_11_110108_add_is_transfer_to_scholar_school_infos.php`

```php
14:         Schema::table('scholar_school_infos', function (Blueprint $table) {
15:             $table->boolean('is_transfer')->default(false);
24:         Schema::table('scholar_school_infos', function (Blueprint $table) {
```

## `2026_08_24_000001_add_validation_metadata_to_scholar_imports.php`

```php
11:         Schema::table('scholar_uploaded_files', function (Blueprint $table) {
12:             $table->unsignedInteger('total_rows')->default(0)->after('status');
13:             $table->unsignedInteger('valid_rows')->default(0)->after('total_rows');
14:             $table->unsignedInteger('needs_correction_rows')->default(0)->after('valid_rows');
15:             $table->unsignedInteger('duplicate_rows')->default(0)->after('needs_correction_rows');
16:             $table->unsignedInteger('missing_required_rows')->default(0)->after('duplicate_rows');
17:             $table->unsignedInteger('published_rows')->default(0)->after('missing_required_rows');
20:         Schema::table('scholar_upload_temps', function (Blueprint $table) {
21:             $table->unsignedInteger('row_number')->nullable()->after('file_id');
22:             $table->string('row_status')->default('needs_correction')->after('publish_by');
23:             $table->json('validation_errors')->nullable()->after('row_status');
29:         Schema::table('scholar_uploaded_files', function (Blueprint $table) {
30:             $table->dropColumn([
40:         Schema::table('scholar_upload_temps', function (Blueprint $table) {
41:             $table->dropColumn([
```

## `2026_08_24_000002_allow_duplicates_in_scholar_upload_temps.php`

```php
10:         DB::statement('ALTER TABLE scholar_upload_temps DROP CONSTRAINT IF EXISTS scholar_upload_temps_spas_no_unique');
11:         DB::statement('ALTER TABLE scholar_upload_temps DROP CONSTRAINT IF EXISTS scholar_upload_temps_email_unique');
16:         DB::statement('ALTER TABLE scholar_upload_temps ADD CONSTRAINT scholar_upload_temps_spas_no_unique UNIQUE (spas_no)');
17:         DB::statement('ALTER TABLE scholar_upload_temps ADD CONSTRAINT scholar_upload_temps_email_unique UNIQUE (email)');
```

## `2026_08_24_000003_cache_scholar_import_matches.php`

```php
11:         Schema::table('scholar_upload_temps', function (Blueprint $table) {
12:             $table->unsignedBigInteger('matched_school_id')->nullable()->after('validation_errors');
13:             $table->string('matched_school_name')->nullable()->after('matched_school_id');
14:             $table->unsignedBigInteger('matched_campus_id')->nullable()->after('matched_school_name');
15:             $table->unsignedBigInteger('matched_course_id')->nullable()->after('matched_campus_id');
16:             $table->string('matched_course_name')->nullable()->after('matched_course_id');
17:             $table->string('matched_course_campus')->nullable()->after('matched_course_name');
18:             $table->json('matched_address')->nullable()->after('matched_course_campus');
24:         Schema::table('scholar_upload_temps', function (Blueprint $table) {
25:             $table->dropColumn([
```

## `2026_08_24_000004_rename_scholar_review_route_label.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_08_25_000001_cache_scholar_import_curriculum_matches.php`

```php
11:         Schema::table('scholar_upload_temps', function (Blueprint $table) {
12:             $table->unsignedBigInteger('matched_curriculum_id')->nullable()->after('matched_course_campus');
13:             $table->string('matched_curriculum_name')->nullable()->after('matched_curriculum_id');
19:         Schema::table('scholar_upload_temps', function (Blueprint $table) {
20:             $table->dropColumn([
```

## `2026_08_27_000001_add_cashier_role.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_08_27_000002_create_payroll_batch_monthly_credits_table.php`

```php
11:         Schema::create('payroll_batch_monthly_credits', function (Blueprint $table) {
12:             $table->id();
13:             $table->foreignId('batch_id')->constrained('batches')->cascadeOnDelete();
14:             $table->unsignedTinyInteger('month_no');
15:             $table->string('status')->default('pending');
16:             $table->decimal('amount', 12, 2)->default(0);
17:             $table->unsignedInteger('recipient_count')->default(0);
18:             $table->foreignId('credited_by')->nullable()->constrained('users')->nullOnDelete();
19:             $table->timestamp('credited_at')->nullable();
20:             $table->text('remarks')->nullable();
21:             $table->timestamps();
23:             $table->unique(['batch_id', 'month_no']);
24:             $table->index(['status', 'credited_at']);
30:         Schema::dropIfExists('payroll_batch_monthly_credits');
```

## `2026_08_27_000003_grant_cashier_financial_assistance_access.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_08_27_000004_move_cashier_to_dedicated_crediting_page.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_08_27_000005_remove_historical_payroll_monthly_credits.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_08_27_000006_grant_regional_credit_view_permission.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_08_27_000007_sync_credited_recipient_stipends.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_09_02_100311_add_two_factor_columns_to_users_table.php`

```php
14:         Schema::table('users', function (Blueprint $table) {
15:             $table->text('two_factor_secret')
19:             $table->text('two_factor_recovery_codes')
23:             $table->timestamp('two_factor_confirmed_at')
34:         Schema::table('users', function (Blueprint $table) {
35:             $table->dropColumn([
```

## `2026_09_02_100312_create_passkeys_table.php`

```php
15:         Schema::create('passkeys', function (Blueprint $table) {
16:             $table->id();
17:             $table->foreignIdFor(Passkeys::userModel(), 'user_id')->constrained()->cascadeOnDelete();
18:             $table->string('name');
19:             $table->string('credential_id')->unique();
20:             $table->json('credential');
21:             $table->timestamp('last_used_at')->nullable();
22:             $table->timestamps();
24:             $table->index('user_id');
33:         Schema::dropIfExists('passkeys');
```

## `2026_09_07_132724_add_two_authenticator_to_scholar_table.php`

```php
14:         Schema::table('scholars', function (Blueprint $table) {
15:             $table->text('authenticator_secret')->nullable();
16:             $table->text('authenticator_recovery_codes')->nullable();
17:             $table->boolean('authenticator_enabled')->default(false);
26:         Schema::table('scholars', function (Blueprint $table) {
27:             $table->dropColumn(['authenticator_secret', 'authenticator_recovery_codes', 'authenticator_enabled']);
```

## `2026_09_09_000001_split_scholar_submission_request_pages.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_09_09_000002_rename_scholar_submissions_menu_label.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_09_09_000003_limit_submission_permissions_to_admin_and_regional_staff.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_09_09_000004_rename_academic_permissions_to_courses.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_09_09_000005_fix_courses_permission_names.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_09_09_000006_update_payroll_permission_labels.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_09_09_000007_update_scholars_schools_permission_labels.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_09_14_000001_add_input_grade_fields_to_scholar_school_grades.php`

```php
12:         Schema::table('scholar_school_grades', function (Blueprint $table) {
14:                 $table->string('input_grade')->nullable();
18:                 $table->boolean('is_incomplete')->default(false);
22:                 $table->boolean('is_drop')->default(false);
56:         Schema::table('scholar_school_grades', function (Blueprint $table) {
62:                 $table->dropColumn($columns);
```

## `2026_09_14_000002_add_withdrawn_grade_flags.php`

```php
11:         Schema::table('school_campus_grades', function (Blueprint $table) {
13:                 $table->boolean('is_withdrawn')->default(false);
17:         Schema::table('scholar_school_grades', function (Blueprint $table) {
19:                 $table->boolean('is_withdrawn')->default(false);
26:         Schema::table('school_campus_grades', function (Blueprint $table) {
28:                 $table->dropColumn('is_withdrawn');
32:         Schema::table('scholar_school_grades', function (Blueprint $table) {
34:                 $table->dropColumn('is_withdrawn');
```

## `2026_09_15_000001_update_cashier_deposit_labels.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_09_15_000002_add_idempotency_hashes_to_imports_and_media.php`

```php
11:         Schema::table('batches', function (Blueprint $table) {
13:                 $table->string('import_file_hash', 64)->nullable()->index();
17:                 $table->string('generated_excel_hash', 64)->nullable()->index();
21:         Schema::table('scholar_uploaded_files', function (Blueprint $table) {
23:                 $table->string('file_hash', 64)->nullable()->index();
27:         Schema::table('geolocation_files', function (Blueprint $table) {
29:                 $table->string('file_hash', 64)->nullable()->index();
33:         Schema::table('documents', function (Blueprint $table) {
35:                 $table->string('file_hash', 64)->nullable()->index();
39:         Schema::table('video_resources', function (Blueprint $table) {
41:                 $table->string('thumbnail_hash', 64)->nullable()->index();
55:             Schema::table($tableName, function (Blueprint $table) use ($tableName, $columns) {
58:                         $table->dropColumn($column);
```

## `2026_09_29_000001_add_course_specializations_and_subject_requirements.php`

```php
11:         Schema::create('school_campus_course_specializations', function (Blueprint $table) {
12:             $table->id();
13:             $table->foreignId('campus_course_id')->constrained('school_campus_courses')->cascadeOnDelete();
14:             $table->string('name');
15:             $table->string('code')->nullable();
16:             $table->unsignedTinyInteger('starts_at_year')->default(1);
17:             $table->boolean('is_active')->default(true);
18:             $table->boolean('is_delete')->default(false);
19:             $table->timestamps();
22:         Schema::table('school_campus_course_curriculum_subjects', function (Blueprint $table) {
23:             $table->foreignId('specialization_id')->nullable()->after('curriculum_id')
25:             $table->string('requirement_type')->default('required')->after('subject_class');
28:         Schema::table('scholar_school_infos', function (Blueprint $table) {
29:             $table->foreignId('specialization_id')->nullable()
33:         Schema::table('scholar_term_records', function (Blueprint $table) {
34:             $table->foreignId('specialization_id')->nullable()
41:         Schema::table('scholar_term_records', fn (Blueprint $table) => $table->dropConstrainedForeignId('specialization_id'));
42:         Schema::table('scholar_school_infos', fn (Blueprint $table) => $table->dropConstrainedForeignId('specialization_id'));
43:         Schema::table('school_campus_course_curriculum_subjects', function (Blueprint $table) {
44:             $table->dropConstrainedForeignId('specialization_id');
45:             $table->dropColumn('requirement_type');
47:         Schema::dropIfExists('school_campus_course_specializations');
```

## `2026_09_29_000002_create_curriculum_subject_specializations_table.php`

```php
12:         Schema::create('curriculum_subject_specializations', function (Blueprint $table) {
13:             $table->id();
14:             $table->foreignId('subject_id')->constrained('school_campus_course_curriculum_subjects')->cascadeOnDelete();
15:             $table->foreignId('specialization_id')->constrained('school_campus_course_specializations')->cascadeOnDelete();
16:             $table->timestamps();
17:             $table->unique(['subject_id', 'specialization_id']);
37:         Schema::dropIfExists('curriculum_subject_specializations');
```

## `2026_09_29_000003_remove_subject_gwa_and_scholarship_flags.php`

```php
11:         Schema::table('school_campus_course_curriculum_subjects', function (Blueprint $table) {
12:             $table->dropColumn(['counts_for_gwa', 'required_for_scholarship']);
18:         Schema::table('school_campus_course_curriculum_subjects', function (Blueprint $table) {
19:             $table->boolean('counts_for_gwa')->default(true);
20:             $table->boolean('required_for_scholarship')->default(true);
```

## `2026_09_29_000004_create_curriculum_elective_groups_table.php`

```php
11:         Schema::create('curriculum_elective_groups', function (Blueprint $table) {
12:             $table->id();
13:             $table->foreignId('curriculum_id')->constrained('school_campus_course_curriculums')->cascadeOnDelete();
14:             $table->foreignId('semester_id')->nullable()->constrained('list_references')->nullOnDelete();
15:             $table->string('year');
16:             $table->string('name');
17:             $table->unsignedSmallInteger('minimum_required')->default(0);
18:             $table->unsignedSmallInteger('maximum_allowed')->nullable();
19:             $table->timestamps();
20:             $table->unique(['curriculum_id', 'semester_id', 'year', 'name']);
23:         Schema::table('school_campus_course_curriculum_subjects', function (Blueprint $table) {
24:             $table->foreignId('elective_group_id')->nullable()->after('specialization_id')
31:         Schema::table('school_campus_course_curriculum_subjects', fn (Blueprint $table) => $table->dropConstrainedForeignId('elective_group_id'));
32:         Schema::dropIfExists('curriculum_elective_groups');
```

## `2026_09_29_000005_add_elective_limits_to_campus_courses.php`

```php
11:         Schema::table('school_campus_courses', function (Blueprint $table) {
12:             $table->unsignedSmallInteger('elective_limit')->nullable()->after('years');
15:         Schema::table('school_campus_course_specializations', function (Blueprint $table) {
16:             $table->unsignedSmallInteger('elective_limit')->nullable()->after('starts_at_year');
22:         Schema::table('school_campus_course_specializations', fn (Blueprint $table) => $table->dropColumn('elective_limit'));
23:         Schema::table('school_campus_courses', fn (Blueprint $table) => $table->dropColumn('elective_limit'));
```

## `2026_09_29_000006_move_elective_limit_to_curriculums.php`

```php
12:         Schema::table('school_campus_course_curriculums', function (Blueprint $table) {
13:             $table->unsignedSmallInteger('elective_limit')->nullable()->after('years');
16:         DB::statement('
24:         Schema::table('school_campus_course_specializations', fn (Blueprint $table) => $table->dropColumn('elective_limit'));
25:         Schema::table('school_campus_courses', fn (Blueprint $table) => $table->dropColumn('elective_limit'));
30:         Schema::table('school_campus_courses', function (Blueprint $table) {
31:             $table->unsignedSmallInteger('elective_limit')->nullable()->after('years');
33:         Schema::table('school_campus_course_specializations', function (Blueprint $table) {
34:             $table->unsignedSmallInteger('elective_limit')->nullable()->after('starts_at_year');
36:         Schema::table('school_campus_course_curriculums', fn (Blueprint $table) => $table->dropColumn('elective_limit'));
```

## `2026_09_29_000007_remove_curriculum_elective_groups.php`

```php
11:         Schema::table('school_campus_course_curriculum_subjects', function (Blueprint $table) {
12:             $table->dropConstrainedForeignId('elective_group_id');
15:         Schema::dropIfExists('curriculum_elective_groups');
20:         Schema::create('curriculum_elective_groups', function (Blueprint $table) {
21:             $table->id();
22:             $table->foreignId('curriculum_id')->constrained('school_campus_course_curriculums')->cascadeOnDelete();
23:             $table->foreignId('semester_id')->constrained('list_references')->cascadeOnDelete();
24:             $table->string('year');
25:             $table->string('name');
26:             $table->unsignedSmallInteger('minimum_required')->default(0);
27:             $table->unsignedSmallInteger('maximum_allowed')->nullable();
28:             $table->timestamps();
29:             $table->unique(['curriculum_id', 'semester_id', 'year', 'name'], 'curriculum_elective_groups_unique');
32:         Schema::table('school_campus_course_curriculum_subjects', function (Blueprint $table) {
33:             $table->foreignId('elective_group_id')->nullable()->after('specialization_id')
```

## `2026_09_29_000008_prevent_duplicate_curriculum_versions.php`

```php
10:         DB::statement('
19:         DB::statement('DROP INDEX IF EXISTS school_curriculums_active_version_unique');
```

## `2026_09_29_000009_refine_scholar_import_navigation.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_09_30_000001_secure_financial_assistance_routes.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_09_30_000002_add_payroll_verification_stage.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_09_30_000003_align_scholar_status_references.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_09_30_000004_rename_progress_status_type_to_scholar.php`

_No direct schema/column operations matched; this migration may update data, constraints, permissions, or indexes through other APIs._

## `2026_09_30_000005_add_recipient_deposit_tracking.php`

```php
11:         Schema::table('recipient_stipends', function (Blueprint $table) {
12:             $table->foreignId('credited_by')->nullable()->after('remarks')->constrained('users')->nullOnDelete();
13:             $table->timestamp('credited_at')->nullable()->after('credited_by');
14:             $table->index(['status', 'month_no', 'credited_at']);
20:         Schema::table('recipient_stipends', function (Blueprint $table) {
21:             $table->dropIndex(['status', 'month_no', 'credited_at']);
22:             $table->dropConstrainedForeignId('credited_by');
23:             $table->dropColumn('credited_at');
```

## `2026_09_30_000006_add_academic_period_lifecycle.php`

```php
11:         Schema::table('school_campus_semesters', function (Blueprint $table) {
12:             $table->string('status', 20)->default('draft')->after('school_year');
13:             $table->timestamp('opened_at')->nullable()->after('status');
14:             $table->foreignId('opened_by')->nullable()->after('opened_at')->constrained('users')->nullOnDelete();
15:             $table->timestamp('closed_at')->nullable()->after('opened_by');
16:             $table->foreignId('closed_by')->nullable()->after('closed_at')->constrained('users')->nullOnDelete();
17:             $table->unique(
23:         Schema::table('scholar_term_records', function (Blueprint $table) {
24:             $table->foreignId('campus_semester_id')
29:             $table->unique(
38:         Schema::table('scholar_term_records', function (Blueprint $table) {
39:             $table->dropUnique('scholar_campus_semester_unique');
40:             $table->dropConstrainedForeignId('campus_semester_id');
43:         Schema::table('school_campus_semesters', function (Blueprint $table) {
44:             $table->dropUnique('campus_semester_school_year_unique');
45:             $table->dropConstrainedForeignId('closed_by');
46:             $table->dropConstrainedForeignId('opened_by');
47:             $table->dropColumn(['status', 'opened_at', 'closed_at']);
```

## `2026_09_30_000007_repair_missing_idempotency_hash_columns.php`

```php
32:             Schema::table($tableName, function (Blueprint $table) use ($tableName, $columns) {
35:                         $table->dropColumn($column);
48:         Schema::table($tableName, function (Blueprint $table) use ($column) {
49:             $table->string($column, 64)->nullable()->index();
```

