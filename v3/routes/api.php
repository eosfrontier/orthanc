<?php

use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\InventoryController;
use App\Http\Controllers\Api\ItemTypeController;
use App\Http\Controllers\Api\LabelController;
use App\Http\Controllers\Api\LogController;
use App\Http\Controllers\Api\SettingsController;
use App\Http\Controllers\Api\TransferController;
use Illuminate\Support\Facades\Route;

Route::prefix('v3/storage')->middleware('auth:sanctum')->group(function () {

    // READ — require storage:read ability
    Route::middleware('require-ability:storage:read')->group(function () {
        Route::get('categories', [CategoryController::class, 'index']);
        Route::get('categories/{id}', [CategoryController::class, 'show']);
        Route::get('item-types', [ItemTypeController::class, 'index']);
        Route::get('item-types/{id}', [ItemTypeController::class, 'show']);
        Route::get('inventory', [InventoryController::class, 'index']);
        Route::get('log', [LogController::class, 'index']);
        Route::get('settings', [SettingsController::class, 'index']);
        Route::get('labels', [LabelController::class, 'index']);
    });

    // WRITE — require storage:write ability
    Route::middleware('require-ability:storage:write')->group(function () {
        Route::post('inventory', [InventoryController::class, 'store']);
        Route::patch('inventory/{id}', [InventoryController::class, 'adjust']);
        Route::delete('inventory/{id}', [InventoryController::class, 'destroy']);
        Route::post('transfer', [TransferController::class, 'store']);
        Route::post('labels/claim', [LabelController::class, 'claim']);
    });

    // ADMIN — require storage:admin ability
    Route::middleware('require-ability:storage:admin')->group(function () {
        Route::post('categories', [CategoryController::class, 'store']);
        Route::put('categories/{id}', [CategoryController::class, 'update']);
        Route::delete('categories/{id}', [CategoryController::class, 'destroy']);
        Route::post('item-types', [ItemTypeController::class, 'store']);
        Route::put('item-types/{id}', [ItemTypeController::class, 'update']);
        Route::delete('item-types/{id}', [ItemTypeController::class, 'destroy']);
        Route::post('inventory/bulk', [InventoryController::class, 'bulk']);
        Route::put('settings', [SettingsController::class, 'update']);
        Route::post('labels', [LabelController::class, 'store']);
    });
});
