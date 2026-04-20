<?php

use App\Http\Controllers\Api\AgoraController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\LiveController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\API\VehicleController;
use App\Http\Controllers\InvitationAccessController;
use Illuminate\Support\Facades\Route;


 Route::prefix('agora')->group(function () {
        Route::get('/token', [AgoraController::class, 'token']);
        Route::post('/leave', [AgoraController::class, 'leave']);
        Route::post('/kick', [AgoraController::class, 'kick']);
        Route::get('/status', [AgoraController::class, 'status']);
    });
// Public routes (tanpa auth)
Route::prefix('guest')->group(function () {
    // Login via link undangan
    Route::get('/invitation/{uuid}', [InvitationAccessController::class, 'loginViaLink']);
    
     // Get invitation data (tanpa auth, via link)
    Route::get('/invitation-data/{uuid}', [InvitationController::class, 'show']);

    // Refresh token
    Route::post('/refresh', [InvitationAccessController::class, 'refreshToken']);
});

// Protected routes (pake auth:sanctum)
Route::prefix('guest')->middleware(['auth:sanctum'])->group(function () {
    // Get current guest profile
    Route::get('/me', [InvitationAccessController::class, 'me']);
    
    // Logout
    Route::post('/logout', [InvitationAccessController::class, 'logout']);
    
    // Get devices list
    Route::get('/devices', [InvitationAccessController::class, 'getDevices']);
    
    // Revoke specific device
    Route::delete('/devices/{fingerprint}', [InvitationAccessController::class, 'revokeDevice']);

    Route::get('/attendances', [AttendanceController::class, 'index']);
    Route::post('/attendances', [AttendanceController::class, 'store']);
    Route::get('/gift-accounts', [AttendanceController::class, 'getGiftAccounts']);

        // Posts (Moment & Status)
    Route::prefix('posts')->group(function () {
        // Create
        Route::post('/moment', [PostController::class, 'createMoment']);
        Route::post('/status', [PostController::class, 'createStatus']);
        
        // Read (Get All with pagination)
        Route::get('/moments', [PostController::class, 'getMoments']);
        Route::get('/statuses', [PostController::class, 'getStatuses']);
        
        // Read (Single post)
        Route::get('/{id}', [PostController::class, 'show']);
        
        // Like/Unlike
        Route::post('/{id}/like', [PostController::class, 'toggleLike']);
    });

    Route::prefix('agora')->group(function () {
        Route::get('/token', [AgoraController::class, 'token']);
        Route::post('/join-slot',  [AgoraController::class, 'joinSlot']);
        Route::post('/leave-slot', [AgoraController::class, 'leaveSlot']);
        Route::post('/leave-by-uid', [AgoraController::class, 'leaveByUid']);
        Route::post('/leave', [AgoraController::class, 'leave']);
        Route::post('/kick', [AgoraController::class, 'kick']);
        Route::get('/status', [AgoraController::class, 'status']);
        Route::prefix('viewer')->group(function () {
           Route::post('/join', [LiveController::class, 'joinViewer']);
            Route::post('/leave', [LiveController::class, 'leaveViewer']);
            Route::get('/viewers/{id}', [LiveController::class, 'getViewerCount']);
        });
    });
});


Route::get('/gift-accounts', [AttendanceController::class, 'getGiftAccounts']);
// API Documentation route
Route::get('/docs', function () {
    return response()->json([
        'message' => 'Vehicle Management API v1',
        'endpoints' => [
            'GET /api/v1/vehicles' => 'List all vehicles with filters',
            'GET /api/v1/vehicles/{id}' => 'Get specific vehicle details',
            'GET /api/v1/brands' => 'List all vehicle brands',
            'GET /api/v1/brands/{id}/models' => 'Get models by brand',
            'GET /api/v1/vehicles/search?query=...' => 'Search vehicles',
        ],
        'filters' => [
            'brand' => 'Filter by brand ID',
            'model' => 'Filter by model ID',
            'year_from' => 'Filter by minimum year',
            'year_to' => 'Filter by maximum year',
            'fuel_type' => 'Filter by fuel type',
            'min_cc' => 'Filter by minimum engine CC',
            'max_cc' => 'Filter by maximum engine CC',
            'sort' => 'Sort by field (prefix with - for descending)',
            'per_page' => 'Items per page (max 100)',
        ]
    ]);
});

Route::get('/test', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API jalan'
    ]);
});

// 4|X7UtX7mRImr0FDmFekFRKxjMZQvXaeihxcroOM1a20ef4c00

// http://localhost:8000/api/agora/token?guest_token=aTo21zuXBbCw2H3sfCbmEPUeEgOYuYItvr4tFgz5

// http://localhost:8000/api/agora/token?guest_token=cthQlPR4IqxuyGtKAMFuAp9sEv53jRbUYsMTr7fq
// http://localhost:8000/api/agora/token?guest_token=p36psY3O72UGQqWGNz1QMmV9gcLBG02GzCBxojJZ
// http://localhost:8000/api/agora/token?guest_token=GyZmQEXeza4Oq1xglhe1Cp5Ehdlhpz0iKgJSztm5