<?php

use App\Http\Controllers\Api\BarangayController;
use App\Http\Controllers\Api\ChangePasswordController;
use App\Http\Controllers\Api\ClusterController;
use App\Http\Controllers\Api\CooperativeController;
use App\Http\Controllers\Api\CropController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EquipmentController;
use App\Http\Controllers\Api\ExpenseController;
use App\Http\Controllers\Api\FarmerController;
use App\Http\Controllers\Api\FisherfolkController;
use App\Http\Controllers\Api\FisheryController;
use App\Http\Controllers\Api\FisheryRecordController;
use App\Http\Controllers\Api\HarvestController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\PlantingController;
use App\Http\Controllers\Api\ReportController;
use App\Http\Controllers\Api\RoleController;
use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:sanctum', 'token.not_expired', 'token.device_match'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('/register', [RegisteredUserController::class, 'store'])->middleware('guest')->name('api.register');
Route::post('/login', [AuthenticatedSessionController::class, 'store']);
Route::post('/forgot-password', [PasswordResetLinkController::class, 'store'])->middleware('guest');
Route::post('/reset-password', [NewPasswordController::class, 'store'])->middleware('guest');

Route::middleware(['auth:sanctum', 'token.not_expired', 'token.device_match'])->group(function () {
    Route::post('/update-password', [ChangePasswordController::class, 'updatePassword']);
    Route::get('/users', [UserController::class, 'getUserData']);
    Route::post('/users-store', [UserController::class, 'store']);
    Route::put('/users-update/{id}', [UserController::class, 'update']);
    Route::delete('/users-delete/{id}', [UserController::class, 'destroy']);
    Route::get('/roles', [RoleController::class, 'index']);
    Route::post('/roles', [RoleController::class, 'store']);
    Route::get('/me', [UserController::class, 'me']);

    Route::put('/roles/{id}', [RoleController::class, 'update']);
    Route::delete('/roles/{id}', [RoleController::class, 'destroy']);

    Route::apiResource('farmers', FarmerController::class);
    Route::apiResource('barangays', BarangayController::class);
    Route::apiResource('fishery', FisheryController::class);
    Route::apiResource('crops', CropController::class);
    Route::apiResource('cooperatives', CooperativeController::class);
    Route::apiResource('fisherfolks', FisherfolkController::class);
    Route::apiResource('clusters', ClusterController::class);
    Route::apiResource('plantings', PlantingController::class);
    Route::delete('/planting-history/{id}', [PlantingController::class, 'destroyHistory']);
    Route::get('/dashboard/stats', [DashboardController::class, 'index']);
    Route::apiResource('harvests', HarvestController::class);
    Route::apiResource('fisheries', FisheryRecordController::class);

    Route::get('equipments/lookups', [EquipmentController::class, 'lookups']);
    Route::apiResource('equipments', EquipmentController::class);
    Route::apiResource('inventory', InventoryController::class);
    Route::patch('/inventory/{id}/stock', [InventoryController::class, 'updateStock']);
    Route::delete('/inventory/transactions/{id}', [InventoryController::class, 'destroyTransaction']);
    Route::post('/inventory/transactions/{id}/revert', [InventoryController::class, 'revertTransaction']);

    Route::apiResource('expenses', ExpenseController::class);
    Route::post('expenses/{id}/restore', [ExpenseController::class, 'restore']);

    Route::prefix('reports')->group(function () {
        Route::get('/', [ReportController::class, 'index']);    // GET    /api/reports
        Route::post('/', [ReportController::class, 'store']);    // POST   /api/reports
        Route::get('/{report}/download', [ReportController::class, 'download']); // GET    /api/reports/{id}/download
        Route::delete('/{report}', [ReportController::class, 'destroy']); // DELETE /api/reports/{id}
    });

});

Route::middleware(['auth:sanctum', 'token.not_expired', 'token.device_match'])->post('/logout', [AuthenticatedSessionController::class, 'destroy']);
