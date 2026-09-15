<?php

use App\Http\Controllers\Auth\ActivationController;
use App\Http\Controllers\Auth\ChangePasswordController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\OtpRequestController;
use App\Http\Controllers\Web\CampusCourseController;
use App\Http\Controllers\Web\CampusCourseSubjectController;
use App\Http\Controllers\Web\CampusGradeController;
use App\Http\Controllers\Web\CashierCreditController;
use App\Http\Controllers\Web\CourseController;
use App\Http\Controllers\Web\DashboardController;
use App\Http\Controllers\Web\DocumentController;
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
use App\Http\Controllers\Web\ScholarManagementController;
use App\Http\Controllers\Web\ScholarReviewController;
use App\Http\Controllers\Web\ScholarSubmissionController;
use App\Http\Controllers\Web\SchoolCampusCurriculumController;
use App\Http\Controllers\Web\SchoolCampusInfoController;
use App\Http\Controllers\Web\SchoolCampusSemesterController;
use App\Http\Controllers\Web\SchoolController;
use App\Http\Controllers\Web\SchoolCoordinatorController;
use App\Http\Controllers\Web\ScholarPortalDocumentController;
use App\Http\Controllers\Web\StatusController;
use App\Http\Controllers\Web\PayrollController;
use App\Http\Controllers\Web\UserController;
use App\Http\Controllers\Web\UserProfileController;
use App\Http\Controllers\Web\VideoResourceController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::get('api/documents', [DocumentController::class, 'publicIndex'])->middleware('throttle:public-api')->name('documents.public');
Route::get('api/video-resources', [VideoResourceController::class, 'publicIndex'])->middleware('throttle:public-api')->name('video-resources.public');
Route::get('documents/{document}/preview', [DocumentController::class, 'preview'])->middleware('throttle:downloads')->name('documents.preview');
Route::get('documents/{document}/download', [DocumentController::class, 'download'])->middleware('throttle:downloads')->name('documents.download');

Route::middleware('guest')->group(function () {
    Route::get('login', [LoginController::class, 'create'])->name('login');
    Route::post('login', [LoginController::class, 'store'])->middleware('throttle:auth-submit')->name('login.store');
    Route::post('otp/request', [OtpRequestController::class, 'create'])->middleware('throttle:otp-submit')->name('otp.check');
    Route::post('otp/login', [OtpRequestController::class, 'store'])->middleware('throttle:auth-submit')->name('otp.store');
    Route::get('/activate/{token}', [ActivationController::class, 'show'])->name('activation.show');
    Route::post('/activate/{id}', [ActivationController::class, 'update'])->middleware('throttle:auth-submit')->name('activation.update');
    Route::post('forgot-password', [ForgotPasswordController::class, 'store'])->middleware('throttle:password-reset-submit')->name('password.store');
    Route::get('reset-password/{token}', [ForgotPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [ForgotPasswordController::class, 'update'])->middleware('throttle:password-reset-submit')->name('password.update');
});

Route::middleware(['auth', 'web'])->group(function () {
    Route::get('/profile', [UserProfileController::class, 'index'])->name('profile');
    Route::put('/profile/update', [UserProfileController::class, 'update'])->middleware('throttle:writes')->name('profile.update');
    Route::post('/profile/photo',[UserProfileController::class, 'updatePhoto'])->middleware('throttle:uploads')->name('profile.photo.update');
    Route::get('scholar-documents/{document}/preview', [ScholarPortalDocumentController::class, 'preview'])->middleware('throttle:downloads')->name('scholar-documents.preview');
    Route::get('scholar-documents/{document}/download', [ScholarPortalDocumentController::class, 'download'])->middleware('throttle:downloads')->name('scholar-documents.download');
});

Route::middleware(['auth', 'web', 'permission'])->group(function () {
    Route::post('logout', [LoginController::class, 'destroy'])->name('logout');
    Route::post('roles', [RoleController::class, 'store'])->middleware('throttle:writes')->name('roles.store');
    Route::put('roles/{id}/{type}', [RoleController::class, 'update'])->middleware('throttle:writes')->name('roles.update');
    Route::delete('roles/{id}', [RoleController::class, 'destroy'])->middleware('throttle:state-actions')->name('roles.destroy');
    Route::patch('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notif.read');
    Route::post('user/changePassword', [ChangePasswordController::class, 'update'])->name('user.changePassword');

    Route::post('routes', [RouteController::class, 'store'])->middleware('throttle:writes')->name('routes.store');
    Route::put('routes/{id}/{type}', [RouteController::class, 'update'])->middleware('throttle:writes')->name('routes.update');
    Route::delete('routes/{id}', [RouteController::class, 'destroy'])->middleware('throttle:state-actions')->name('routes.destroy');

    Route::post('users', [UserController::class, 'store'])->middleware('throttle:writes')->name('users.store');
    Route::post('users/email/{id}', [UserController::class, 'resend'])->middleware('throttle:sensitive-actions')->name('users.resend');
    Route::put('users/{id}/{type}', [UserController::class, 'update'])->middleware('throttle:writes')->name('users.update');
    Route::delete('users/{id}/{type}', [UserController::class, 'destroy'])->middleware('throttle:state-actions')->name('users.destroy');

    Route::post('programs', [programController::class, 'store'])->middleware('throttle:writes')->name('programs.store');
    Route::put('programs/{id}/{type}', [programController::class, 'update'])->middleware('throttle:writes')->name('programs.update');
    Route::delete('programs/{id}/{type}', [programController::class, 'destroy'])->middleware('throttle:state-actions')->name('programs.destroy');

    Route::post('location/regions', [LocationRegionController::class, 'store'])->middleware('throttle:writes')->name('location.regions.store');
    Route::put('location/regions/{id}/{type}', [LocationRegionController::class, 'update'])->middleware('throttle:writes')->name('location.regions.update');
    Route::delete('location/regions/{id}/{type}', [LocationRegionController::class, 'destroy'])->middleware('throttle:state-actions')->name('location.regions.destroy');

    Route::post('location/provinces', [LocationProvinceController::class, 'store'])->middleware('throttle:writes')->name('location.provinces.store');
    Route::put('location/provinces/{id}/{type}', [LocationProvinceController::class, 'update'])->middleware('throttle:writes')->name('location.provinces.update');
    Route::delete('location/provinces/{id}/{type}', [LocationProvinceController::class, 'destroy'])->middleware('throttle:state-actions')->name('location.provinces.destroy');

    Route::post('location/cities', [LocationCityController::class, 'store'])->middleware('throttle:writes')->name('location.cities.store');
    Route::put('location/cities/{id}/{type}', [LocationCityController::class, 'update'])->middleware('throttle:writes')->name('location.cities.update');
    Route::delete('location/cities/{id}/{type}', [LocationCityController::class, 'destroy'])->middleware('throttle:state-actions')->name('location.cities.destroy');

    Route::post('location/barangays', [LocationBarangayController::class, 'store'])->middleware('throttle:writes')->name('location.barangays.store');
    Route::put('location/barangay/{id}/{type}', [LocationBarangayController::class, 'update'])->middleware('throttle:writes')->name('location.barangays.update');
    Route::delete('location/barangay/{id}/{type}', [LocationBarangayController::class, 'destroy'])->middleware('throttle:state-actions')->name('location.barangays.destroy');

    Route::post('academic/courses', [CourseController::class, 'store'])->middleware('throttle:writes')->name('academic.courses.store');
    Route::put('academic/courses/{id}/{type}', [CourseController::class, 'update'])->middleware('throttle:writes')->name('academic.courses.update');
    Route::delete('academic/courses/{id}/{type}', [CourseController::class, 'destroy'])->middleware('throttle:state-actions')->name('academic.courses.destroy');

    Route::post('academic/references', [ReferenceController::class, 'store'])->middleware('throttle:writes')->name('academic.references.store');
    Route::put('academic/references/{id}/{type}', [ReferenceController::class, 'update'])->middleware('throttle:writes')->name('academic.references.update');
    Route::delete('academic/references/{id}/{type}', [ReferenceController::class, 'destroy'])->middleware('throttle:state-actions')->name('academic.references.destroy');

    Route::post('academic/universities', [SchoolController::class, 'store'])->middleware('throttle:writes')->name('academic.universities.store');
    Route::put('academic/universities/{id}/{type}', [SchoolController::class, 'update'])->middleware('throttle:writes')->name('academic.universities.update');
    Route::delete('academic/universities/{id}/{type}', [SchoolController::class, 'destroy'])->middleware('throttle:state-actions')->name('academic.universities.destroy');

    Route::post('academic/universities/course', [CampusCourseController::class, 'store'])->middleware('throttle:writes')->name('academic.universities.course.store');
    Route::put('academic/universities/course/{id}/{type}', [CampusCourseController::class, 'update'])->middleware('throttle:writes')->name('academic.universities.course.update');
    Route::delete('academic/universities/course/{id}/{type}', [CampusCourseController::class, 'destroy'])->middleware('throttle:state-actions')->name('academic.universities.course.destroy');

    Route::post('academic/universities/grade', [CampusGradeController::class, 'store'])->middleware('throttle:writes')->name('academic.universities.grade.store');
    Route::put('academic/universities/grade/{id}/{type}', [CampusGradeController::class, 'update'])->middleware('throttle:writes')->name('academic.universities.grade.update');
    Route::delete('academic/universities/grade/{id}/{type}', [CampusGradeController::class, 'destroy'])->middleware('throttle:state-actions')->name('academic.universities.grade.destroy');

    // Route::post('subjects', [CampusCourseSubjectController::class, 'store'])->name('subject.store');
    // Route::put('subjects/{id}/{type}', [CampusCourseSubjectController::class, 'update'])->name('subject.update');
    // Route::delete('subjects/{id}/{type}', [CampusCourseSubjectController::class, 'destroy'])->name('campus.subject.destroy');

    Route::post('statuses', [StatusController::class, 'store'])->middleware('throttle:writes')->name('status.store');
    Route::put('statuses/{id}/{type}', [StatusController::class, 'update'])->middleware('throttle:writes')->name('status.update');
    Route::delete('statuses/{id}/{type}', [StatusController::class, 'destroy'])->middleware('throttle:state-actions')->name('status.destroy');

    Route::post('campus/info', [SchoolCampusInfoController::class, 'store'])->middleware('throttle:writes')->name('campus.info.store');
    Route::put('campus/info/{id}/{type}', [SchoolCampusInfoController::class, 'update'])->middleware('throttle:writes')->name('campus.info.update');
    Route::delete('campus/info/{id}/{type}', [SchoolCampusInfoController::class, 'destroy'])->middleware('throttle:state-actions')->name('campus.info.destroy');

    Route::post('campus/semester', [SchoolCampusSemesterController::class, 'store'])->middleware('throttle:writes')->name('campus.semester.store');
    Route::put('campus/semester/{id}/{type}', [SchoolCampusSemesterController::class, 'update'])->middleware('throttle:writes')->name('campus.semester.update');
    Route::delete('campus/semester/{id}/{type}', [SchoolCampusSemesterController::class, 'destroy'])->middleware('throttle:state-actions')->name('campus.semester.destroy');

    Route::post('campus/curriculum', [SchoolCampusCurriculumController::class, 'store'])->middleware('throttle:writes')->name('campus.curriculum.store');
    Route::put('campus/curriculum/{id}/{type}', [SchoolCampusCurriculumController::class, 'update'])->middleware('throttle:writes')->name('campus.curriculum.update');
    Route::delete('campus/curriculum/{id}/{type}/subject', [SchoolCampusCurriculumController::class, 'destroySubject'])->middleware('throttle:state-actions')->name('campus.curriculum.destroysubject');
    Route::delete('campus/curriculum/{id}/{type}', [SchoolCampusCurriculumController::class, 'destroyCurriculum'])->middleware('throttle:state-actions')->name('campus.curriculum.destroyCurriculum');
    Route::patch('campus/curriculum/{id}/copy', [SchoolCampusCurriculumController::class, 'copy'])->middleware('throttle:writes')->name('campus.curriculum.copy');
    Route::patch('campus/curriculum/{id}/paste', [SchoolCampusCurriculumController::class, 'paste'])->middleware('throttle:writes')->name('campus.curriculum.paste');

    Route::post('scholar', [ScholarReviewController::class, 'store'])->middleware('throttle:uploads')->name('scholar.store');
    Route::get('scholar/import-template', [ScholarReviewController::class, 'template'])->middleware('throttle:exports')->name('scholar.template');
    Route::post('scholar/{id}/validated', [ScholarReviewController::class, 'insert'])->middleware('throttle:sensitive-actions')->name('scholar.insert');
    Route::delete('scholar/{id}/{type}', [ScholarReviewController::class, 'destroy'])->middleware('throttle:state-actions')->name('scholar.destroy');
    Route::post('scholar/{id}/grade-update', [ScholarReviewController::class, 'gradeUpdate'])->middleware('throttle:writes')->name('scholar.grade-update');
    Route::post('scholar/{id}/grade-delete', [ScholarReviewController::class, 'gradeDelete'])->middleware('throttle:state-actions')->name('scholar.grade-delete');
    Route::post('geolocation', [GeolocationController::class, 'store'])->middleware('throttle:uploads')->name('geolocation.store');

    Route::put('stipends/recipients/{id}/mark-for-removal', [PayrollController::class, 'markRecipientForRemoval'])->middleware('throttle:state-actions')->name('stipends.recipients.mark-for-removal');
    Route::put('stipends/recipients/{id}/cancel-removal', [PayrollController::class, 'cancelRecipientForRemoval'])->middleware('throttle:state-actions')->name('stipends.recipients.cancel-removal');
    Route::put('cashier/credits/{id}/months/{month}', [CashierCreditController::class, 'update'])->middleware('throttle:writes')->name('cashier.credits.update');
    Route::post('stipends/import-historical/preview', [PayrollController::class, 'previewHistorical'])->middleware('throttle:uploads')->name('stipends.import-historical.preview');
    Route::post('stipends/import-historical', [PayrollController::class, 'importHistorical'])->middleware('throttle:uploads')->name('stipends.import-historical');
    Route::put('stipends/{id}/payroll', [PayrollController::class, 'savePayroll'])->middleware('throttle:writes')->name('stipends.payroll.update');
    Route::get('stipends/{id}/export', [PayrollController::class, 'export'])->middleware('throttle:exports')->name('stipends.export');
    Route::put('stipends/{id}/{type}', [PayrollController::class, 'update'])->middleware('throttle:state-actions')->name('stipends.update');
    Route::delete('stipends/{id}/{type}', [PayrollController::class, 'destroy'])->middleware('throttle:state-actions')->name('stipends.destroy');

    Route::post('scholars/{id}/{type}', [ScholarManagementController::class, 'update'])->middleware('throttle:writes')->name('scholars.update');
    Route::post('scholars/{id}/landbank/reveal', [ScholarManagementController::class, 'revealLandbank'])->middleware('throttle:sensitive-actions')->name('scholars.landbank.reveal');
    Route::post('scholarsActivation/{id}', [ScholarManagementController::class, 'activation'])->middleware('throttle:sensitive-actions')->name('scholars.activation');
    Route::post('scholars/{id}/{type}/transfer', [ScholarManagementController::class, 'transfer'])->middleware('throttle:state-actions')->name('scholars.transfer');
    // scholar preview
    Route::post('scholar-review/{id}/validate', [ScholarReviewController::class, 'validate'])->middleware('throttle:sensitive-actions')->name('review.validate');
    Route::post('scholar-review/{id}/publish', [ScholarReviewController::class, 'publish'])->middleware('throttle:sensitive-actions')->name('review.publish');

    Route::post('scholar-grade-request/{type}', [ScholarManagementController::class, 'gradeRequest'])->middleware('throttle:state-actions')->name('scholar.grade-request');
    Route::post('profileRequest/{type}', [ScholarManagementController::class, 'profileRequest'])->middleware('throttle:state-actions')->name('profile.request');
    Route::post('landbankRequest/{type}', [ScholarManagementController::class, 'landbankRequest'])->middleware('throttle:state-actions')->name('landbank.request');

    Route::post('documents', [DocumentController::class, 'store'])->middleware('throttle:uploads')->name('documents.store');
    Route::put('documents/{document}', [DocumentController::class, 'update'])->middleware('throttle:uploads')->name('documents.update');
    Route::delete('documents/{document}', [DocumentController::class, 'destroy'])->middleware('throttle:state-actions')->name('documents.destroy');
    Route::post('document-categories', [DocumentController::class, 'storeCategory'])->middleware('throttle:writes')->name('document-categories.store');
    Route::put('document-categories/{category}', [DocumentController::class, 'updateCategory'])->middleware('throttle:writes')->name('document-categories.update');
    Route::delete('document-categories/{category}', [DocumentController::class, 'destroyCategory'])->middleware('throttle:state-actions')->name('document-categories.destroy');
    Route::post('video-resources', [VideoResourceController::class, 'store'])->middleware('throttle:uploads')->name('video-resources.store');
    Route::put('video-resources/{videoResource}', [VideoResourceController::class, 'update'])->middleware('throttle:uploads')->name('video-resources.update');
    Route::delete('video-resources/{videoResource}', [VideoResourceController::class, 'destroy'])->middleware('throttle:state-actions')->name('video-resources.destroy');

    Route::put('schoolCoordinator/info', [SchoolCoordinatorController::class, 'updateInfo'])->middleware('throttle:writes')->name('schoolCoordinator.updateInfo');
    Route::post('schoolCoordinator/program', [SchoolCoordinatorController::class, 'createProgram'])->middleware('throttle:writes')->name('schoolCoordinator.createProgram');
    Route::post('schoolCoordinator/grade', [SchoolCoordinatorController::class, 'createGrade'])->middleware('throttle:writes')->name('schoolCoordinator.createGrade');
    Route::post('schoolCoordinator/{id}/DeleteGrade', [SchoolCoordinatorController::class, 'deleteGrade'])->middleware('throttle:state-actions')->name('schoolCoordinator.deleteGrade');
    Route::post('schoolCoordinator/semester', [SchoolCoordinatorController::class, 'updateSemester'])->middleware('throttle:writes')->name('schoolCoordinator.updateSemester');
});
Route::middleware(['auth', 'web', 'role'])->group(function () {
    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('cashier/credits', [CashierCreditController::class, 'index'])->name('cashier.credits');
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
    // Route::get('scholarsV1/oldVersion', [ScholarReviewController::class, 'index'])->name('scholarsOldVersion');
    Route::get('scholars', [ScholarManagementController::class, 'index'])->name('scholars');
    Route::get('scholar-submissions', [ScholarSubmissionController::class, 'index'])->name('scholar-submissions');
    Route::get('scholar-profile-requests', [ScholarSubmissionController::class, 'profileRequests'])->name('scholar-profile-requests');
    Route::get('scholar-landbank-requests', [ScholarSubmissionController::class, 'landbankRequests'])->name('scholar-landbank-requests');
    Route::get('programs', [programController::class, 'index'])->name('programs');
    Route::get('events', [eventController::class, 'index'])->name('events');
    Route::get('stipends', [PayrollController::class, 'index'])->name('stipends');
    Route::get('documents', [DocumentController::class, 'index'])->name('documents');
    Route::get('schoolCoordinator', [SchoolCoordinatorController::class, 'index'])->name('schoolCoordinator');
    Route::get('video-resources', [VideoResourceController::class, 'index'])->name('video-resources');
    Route::get('geolocation', [GeolocationController::class, 'index']);
    Route::get('scholar-review', [ScholarReviewController::class, 'index'])->name('review');
});
