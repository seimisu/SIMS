<?php

use App\Http\Controllers\Auth\ActivationController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OtpRequestController;
use App\Http\Controllers\Web\CampusCourseController;
use App\Http\Controllers\Web\CampusCourseSubjectController;
use App\Http\Controllers\Web\CampusGradeController;
use App\Http\Controllers\Web\CourseController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\eventController;
use App\Http\Controllers\Web\GeolocationController;
use App\Http\Controllers\Web\LocationBarangayController;
use App\Http\Controllers\Web\LocationCityController;
use App\Http\Controllers\Web\LocationProvinceController;
use App\Http\Controllers\Web\LocationRegionController;
use App\Http\Controllers\Web\NotificationController;
use App\Http\Controllers\Web\programController;
use App\Http\Controllers\Web\ReferenceController;
use App\Http\Controllers\Web\RoleController;
use App\Http\Controllers\Web\RouteController;
use App\Http\Controllers\Web\Scholar1Controller;
use App\Http\Controllers\Web\ScholarController;
use App\Http\Controllers\Web\SchoolCampusCurriculumController;
use App\Http\Controllers\Web\SchoolCampusInfoController;
use App\Http\Controllers\Web\SchoolCampusSemesterController;
use App\Http\Controllers\Web\SchoolController;
use App\Http\Controllers\Web\StatusController;
use App\Http\Controllers\Web\StipendController;
use App\Http\Controllers\Web\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->name('login.store');
    Route::post('otp/request', [OtpRequestController::class, 'create'])->name('otp.check');
    Route::post('otp/login', [OtpRequestController::class, 'store'])->name('otp.store');
    Route::get('/activate/{token}', [ActivationController::class, 'show'])->name('activation.show');
    Route::post('/activate/{id}', [ActivationController::class, 'update'])->name('activation.update');
});

Route::middleware(['auth', 'web'])->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
    Route::post('roles', [RoleController::class, 'store'])->name('roles.store');
    Route::put('roles/{id}/{type}', [RoleController::class, 'update'])->name('roles.update');
    Route::delete('roles/{id}', [RoleController::class, 'destroy'])->name('roles.destroy');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notif.read');
    Route::post('user/changePassword', [ChangePasswordController::class, 'update'])->name('user.changePassword');

    Route::post('routes', [RouteController::class, 'store'])->name('routes.store');
    Route::put('routes/{id}/{type}', [RouteController::class, 'update'])->name('routes.update');
    Route::delete('routes/{id}', [RouteController::class, 'destroy'])->name('routes.destroy');

    Route::post('users', [UserController::class, 'store'])->name('users.store');
    Route::post('users/email/{id}', [UserController::class, 'resend'])->name('users.resend');
    Route::put('users/{id}/{type}', [UserController::class, 'update'])->name('users.update');
    Route::delete('users/{id}/{type}', [UserController::class, 'destroy'])->name('users.destroy');

    Route::post('programs', [programController::class, 'store'])->name('programs.store');
    Route::put('programs/{id}/{type}', [programController::class, 'update'])->name('programs.update');
    Route::delete('programs/{id}/{type}', [programController::class, 'destroy'])->name('programs.destroy');

    Route::post('location/regions', [LocationRegionController::class, 'store'])->name('location.regions.store');
    Route::put('location/regions/{id}/{type}', [LocationRegionController::class, 'update'])->name('location.regions.update');
    Route::delete('location/regions/{id}/{type}', [LocationRegionController::class, 'destroy'])->name('location.regions.destroy');

    Route::post('location/provinces', [LocationProvinceController::class, 'store'])->name('location.provinces.store');
    Route::put('location/provinces/{id}/{type}', [LocationProvinceController::class, 'update'])->name('location.provinces.update');
    Route::delete('location/provinces/{id}/{type}', [LocationProvinceController::class, 'destroy'])->name('location.provinces.destroy');

    Route::post('location/cities', [LocationCityController::class, 'store'])->name('location.cities.store');
    Route::put('location/cities/{id}/{type}', [LocationCityController::class, 'update'])->name('location.cities.update');
    Route::delete('location/cities/{id}/{type}', [LocationCityController::class, 'destroy'])->name('location.cities.destroy');

    Route::post('location/barangays', [LocationBarangayController::class, 'store'])->name('location.barangays.store');
    Route::put('location/barangay/{id}/{type}', [LocationBarangayController::class, 'update'])->name('location.barangays.update');
    Route::delete('location/barangay/{id}/{type}', [LocationBarangayController::class, 'destroy'])->name('location.barangays.destroy');

    Route::post('academic/courses', [CourseController::class, 'store'])->name('academic.courses.store');
    Route::put('academic/courses/{id}/{type}', [CourseController::class, 'update'])->name('academic.courses.update');
    Route::delete('academic/courses/{id}/{type}', [CourseController::class, 'destroy'])->name('academic.courses.destroy');

    Route::post('academic/references', [ReferenceController::class, 'store'])->name('academic.references.store');
    Route::put('academic/references/{id}/{type}', [ReferenceController::class, 'update'])->name('academic.references.update');
    Route::delete('academic/references/{id}/{type}', [ReferenceController::class, 'destroy'])->name('academic.references.destroy');

    Route::post('academic/universities', [SchoolController::class, 'store'])->name('academic.universities.store');
    Route::put('academic/universities/{id}/{type}', [SchoolController::class, 'update'])->name('academic.universities.update');
    Route::delete('academic/universities/{id}/{type}', [SchoolController::class, 'destroy'])->name('academic.universities.destroy');

    Route::post('academic/universities/course', [CampusCourseController::class, 'store'])->name('academic.universities.course.store');
    Route::put('academic/universities/course/{id}/{type}', [CampusCourseController::class, 'update'])->name('academic.universities.course.update');
    Route::delete('academic/universities/course/{id}/{type}', [CampusCourseController::class, 'destroy'])->name('academic.universities.course.destroy');

    Route::post('academic/universities/grade', [CampusGradeController::class, 'store'])->name('academic.universities.grade.store');
    Route::put('academic/universities/grade/{id}/{type}', [CampusGradeController::class, 'update'])->name('academic.universities.grade.update');
    Route::delete('academic/universities/grade/{id}/{type}', [CampusGradeController::class, 'destroy'])->name('academic.universities.grade.destroy');

    // Route::post('subjects', [CampusCourseSubjectController::class, 'store'])->name('subject.store');
    // Route::put('subjects/{id}/{type}', [CampusCourseSubjectController::class, 'update'])->name('subject.update');
    // Route::delete('subjects/{id}/{type}', [CampusCourseSubjectController::class, 'destroy'])->name('campus.subject.destroy');

    Route::post('statuses', [StatusController::class, 'store'])->name('status.store');
    Route::put('statuses/{id}/{type}', [StatusController::class, 'update'])->name('status.update');
    Route::delete('statuses/{id}/{type}', [StatusController::class, 'destroy'])->name('status.destroy');

    Route::post('campus/info', [SchoolCampusInfoController::class, 'store'])->name('campus.info.store');
    Route::put('campus/info/{id}/{type}', [SchoolCampusInfoController::class, 'update'])->name('campus.info.update');
    Route::delete('campus/info/{id}/{type}', [SchoolCampusInfoController::class, 'destroy'])->name('campus.info.destroy');

    Route::post('campus/semester', [SchoolCampusSemesterController::class, 'store'])->name('campus.semester.store');
    Route::put('campus/semester/{id}/{type}', [SchoolCampusSemesterController::class, 'update'])->name('campus.semester.update');
    Route::delete('campus/semester/{id}/{type}', [SchoolCampusSemesterController::class, 'destroy'])->name('campus.semester.destroy');

    Route::post('campus/curriculum', [SchoolCampusCurriculumController::class, 'store'])->name('campus.curriculum.store');
    Route::put('campus/curriculum/{id}/{type}', [SchoolCampusCurriculumController::class, 'update'])->name('campus.curriculum.update');
    Route::delete('campus/curriculum/{id}/{type}/subject', [SchoolCampusCurriculumController::class, 'destroySubject'])->name('campus.curriculum.destroysubject');
    Route::delete('campus/curriculum/{id}/{type}', [SchoolCampusCurriculumController::class, 'destroyCurriculum'])->name('campus.curriculum.destroyCurriculum');
    Route::patch('campus/curriculum/{id}/copy', [SchoolCampusCurriculumController::class, 'copy'])->name('campus.curriculum.copy');
    Route::patch('campus/curriculum/{id}/paste', [SchoolCampusCurriculumController::class, 'paste'])->name('campus.curriculum.paste');

    Route::post('scholar', [ScholarController::class, 'store'])->name('scholar.store');
    Route::post('scholar/{id}/validated', [ScholarController::class, 'insert'])->name('scholar.insert');
    Route::delete('scholar/{id}/{type}', [ScholarController::class, 'destroy'])->name('scholar.destroy');
    Route::post('scholar/{id}/grade-update', [ScholarController::class, 'gradeUpdate'])->name('scholar.grade-update');
    Route::post('scholar/{id}/grade-delete', [ScholarController::class, 'gradeDelete'])->name('scholar.grade-delete');
    Route::post('scholar/{id}/requestSubject', [ScholarController::class, 'requestSubjectDenied'])->name('scholar.requestSubject-denied');
    Route::post('scholar/{id}/requestSubjectAccept', [ScholarController::class, 'requestSubjectAccept'])->name('scholar.requestSubject-accept');
    Route::post('geolocation', [GeolocationController::class, 'store'])->name('geolocation.store');

    Route::post('stipends', [StipendController::class, 'store'])->name('stipends.store');
    Route::put('stipends/{id}/{type}', [StipendController::class, 'update'])->name('stipends.update');
    Route::delete('stipends/{id}/{type}', [StipendController::class, 'destroy'])->name('stipends.destroy');

    // Scholar 1.0 Routes
    Route::post('scholars/{id}/{type}', [Scholar1Controller::class, 'update'])->name('scholars.update');
    Route::post('scholarsActivation/{id}', [Scholar1Controller::class, 'activation'])->name('scholars.activation');
    Route::post('scholars/{id}/{type}/SubjectRequest', [Scholar1Controller::class, 'validate'])->name('scholars.validate');
    Route::post('scholars/{id}/{type}/GradeRequest', [Scholar1Controller::class, 'gradeValidate'])->name('scholars.gradeValidate');

    Route::post('scholar-review/{id}/validate', [ScholarController::class, 'validate'])->name('review.validate');
});
Route::middleware(['auth', 'web', 'role'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('roles', [RoleController::class, 'index'])->name('roles');
    Route::get('routes', [RouteController::class, 'index'])->name('routes');
    Route::get('users', [UserController::class, 'index'])->name('users');
    Route::get('location/regions', [LocationRegionController::class, 'index'])->name('location.regions');
    Route::get('location/provinces', [LocationProvinceController::class, 'index'])->name('location.provinces');
    Route::get('location/cities', [LocationCityController::class, 'index'])->name('location.cities');
    Route::get('location/barangay', [LocationBarangayController::class, 'index'])->name('location.barangays');
    Route::get('academic/courses', [CourseController::class, 'index'])->name('academic.courses');
    Route::get('academic/references', [ReferenceController::class, 'index'])->name('academic.references');
    Route::get('academic/schools', [SchoolController::class, 'index'])->name('academic.universities');
    Route::get('scholar/statuses', [StatusController::class, 'index'])->name('statuses');
    // Route::get('scholarsV1/oldVersion', [ScholarController::class, 'index'])->name('scholarsOldVersion');
    Route::get('scholars', [Scholar1Controller::class, 'index'])->name('scholars');
    Route::get('programs', [programController::class, 'index'])->name('programs');
    Route::get('events', [eventController::class, 'index'])->name('events');
    Route::get('stipends', [StipendController::class, 'index'])->name('stipends');
    Route::get('geolocation', [GeolocationController::class, 'index']);
    Route::get('scholar-review', [ScholarController::class, 'index'])->name('review');
});
