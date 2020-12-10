<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\AdminFrontofficeCombineController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\CombineCompanyAdminController;
use App\Http\Controllers\CompanyAdminController;
use App\Http\Controllers\CompanyController;
use App\Http\Controllers\ContactUsController;
use App\Http\Controllers\CurrentPriceController;
use App\Http\Controllers\DepotPriceController;
use App\Http\Controllers\FrontOfficeController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\SuperAdminController;
use App\Http\Controllers\MpesaController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

// Route::apiResources([
//     'register' => AuthController::class,
// ]);
// Route::get('register', [AuthController::class, 'register']);

Route::group([
    'middleware' => 'api',
    'prefix' => 'password',
], function () {
    Route::post('create', [PasswordResetController::class, 'create']);
    Route::get('find/{token}', [PasswordResetController::class, 'find']);
    Route::post('reset', [PasswordResetController::class, 'reset']);
});

Route::group([
], function () {
    Route::post('login', [AuthController::class, 'login']);
    Route::post('register', [AuthController::class, 'register']);
    Route::get('register/activate/{token}', [AuthController::class, 'registerActivate']);

    Route::group([
        'middleware' => 'auth:api',
    ], function () {
        Route::get('logout', [AuthController::class, 'logout']);
        Route::get('profile', [AuthController::class, 'profile']);
        Route::post('profile', [AuthController::class, 'update_avatar']);
        Route::patch('profile', [AuthController::class, 'update_name']);
        Route::apiResources([
            'currentprices' => CurrentPriceController::class,
            'frontoffices' => FrontOfficeController::class,
            'companies' => CompanyController::class,
            'companyadmins' => CompanyAdminController::class,
            'admins' => AdminController::class,
            'superadmins' => SuperAdminController::class,
        ]);
        Route::get('depotprices', [DepotPriceController::class, 'index']);
        Route::get('frontadmins', [AdminFrontofficeCombineController::class, 'index']);
        Route::get('combineadmins', [CombineCompanyAdminController::class, 'index']);
        Route::get('audits', [AuditController::class, 'index']);
        Route::get('audits/{token}', [AuditController::class, 'show']);
        Route::get('roles', [RoleController::class, 'index']);
        Route::get('frontshores', [FrontOfficeController::class, 'adminIndex']);
        Route::get('cofadmins', [CompanyAdminController::class, 'cofadmins']);

        Route::get('contact-us', [ContactUsController::class, 'index']);
        Route::post('contact-us', [ContactUsController::class, 'store']);
        Route::get('contact-us/{id}', [ContactUsController::class, 'show']);
        Route::delete('contact-us/{id}', [ContactUsController::class, 'destroy']);
        
        Route::get('mpesa/password', [MpesaController::class, 'lipaNaMpesaPassword']);
    });
});
