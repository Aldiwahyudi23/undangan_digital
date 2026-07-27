<?php

use App\Http\Controllers\Api\AgoraController;
use App\Http\Controllers\Api\AttendanceController;
use App\Http\Controllers\Api\InvitationController;
use App\Http\Controllers\Api\LiveChatController;
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
        Route::get('/viewers', [AgoraController::class, 'viewers']);
        Route::get('/heartbeat', [AgoraController::class, 'heartbeat']);
    });
    Route::prefix('live-chat')->group(function () {
        Route::get('/', [LiveChatController::class, 'index']);
        Route::post('/', [LiveChatController::class, 'store']);
        Route::delete('/{id}', [LiveChatController::class, 'destroy']);
    });
});

    Route::prefix('live-chat')->group(function () {
        Route::get('/', [LiveChatController::class, 'index']);
        Route::post('/', [LiveChatController::class, 'store']);
        Route::delete('/{id}', [LiveChatController::class, 'destroy']);
    });
    
Route::get('/gift-accounts', [AttendanceController::class, 'getGiftAccounts']);


use App\Http\Controllers\Api\Receptionist\AuthController;
use App\Http\Controllers\Api\Receptionist\DashboardController;
use App\Http\Controllers\Api\Receptionist\GuestSearchController;
use App\Http\Controllers\Api\Receptionist\CheckinController;
use App\Http\Controllers\Api\Receptionist\ManualCheckinController;
use App\Http\Controllers\Api\Receptionist\DoorprizeController;

Route::get('/test', function () {
    return response()->json([
        'status' => 'ok',
        'message' => 'API jalan'
    ]);
});

// Receptionist Auth (public)
Route::prefix('receptionist')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);
});

// Receptionist Protected
Route::prefix('receptionist')
    ->middleware(['auth:sanctum', 'receptionist'])
    ->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me', [AuthController::class, 'me']);
        Route::get('/dashboard', DashboardController::class);
        Route::get('/guests/search', [GuestSearchController::class, 'search']);
        Route::post('/checkin', [CheckinController::class, 'checkin']);
        Route::post('/checkout', [CheckinController::class, 'checkout']);
        Route::post('/checkin/manual', [ManualCheckinController::class, 'store']);
        Route::post('/checkout/manual', [ManualCheckinController::class, 'checkout']);
        Route::post('/doorprize/spin', [DoorprizeController::class, 'spin']);
        Route::post('/doorprize/save', [DoorprizeController::class, 'store']);
    });

// 4|X7UtX7mRImr0FDmFekFRKxjMZQvXaeihxcroOM1a20ef4c00

// http://localhost:8000/api/agora/token?guest_token=aTo21zuXBbCw2H3sfCbmEPUeEgOYuYItvr4tFgz5

// http://localhost:8000/api/agora/token?guest_token=cthQlPR4IqxuyGtKAMFuAp9sEv53jRbUYsMTr7fq
// http://localhost:8000/api/agora/token?guest_token=p36psY3O72UGQqWGNz1QMmV9gcLBG02GzCBxojJZ
// http://localhost:8000/api/agora/token?guest_token=GyZmQEXeza4Oq1xglhe1Cp5Ehdlhpz0iKgJSztm5