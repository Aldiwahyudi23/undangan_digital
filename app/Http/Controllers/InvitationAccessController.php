<?php

namespace App\Http\Controllers;

use App\Models\Attendance;
use App\Models\Event;
use App\Models\InvitationGuest;
use Illuminate\Http\Request;
use Laravel\Sanctum\PersonalAccessToken;

class InvitationAccessController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

        // Login via link undangan
    public function loginViaLink($uuid, Request $request)
    {
        try {
            $token = $request->query('token');
            
            if (!$token) {
                return response()->json([
                    'success' => false,
                    'message' => 'Token tidak ditemukan dalam link'
                ], 400);
            }
            
            // Cari guest
            $guest = InvitationGuest::where('uuid', $uuid)
                ->where('token', $token)
                ->first();
            
            if (!$guest) {
                return response()->json([
                    'success' => false,
                    'message' => 'Link undangan tidak valid'
                ], 404);
            }
            
            // Dapatkan device fingerprint dari header
            $deviceFingerprint = $this->getDeviceFingerprint($request);
            
            // Register device (cek limit)
            $guest->registerDevice($deviceFingerprint);
            
            // Update tracking
            $guest->updateTracking($request, $deviceFingerprint);
            
            // Hapus semua token lama dari device ini (optional)
            // $guest->tokens()->where('name', $deviceFingerprint)->delete();
            
            // Buat token API baru untuk device ini
            $apiToken = $guest->createToken($deviceFingerprint, ['*'], now()->addDays(30))->plainTextToken;
            
            // Simpan device fingerprint di token abilities atau metadata
            $tokenModel = $guest->tokens()->latest()->first();
            $tokenModel->device_fingerprint = $deviceFingerprint;
            $tokenModel->save();

            //mengambil nama pengantin
             $invitation = $guest->invitation;  //mengambil data invitation dari guest
             $couples = $invitation->couples;   //mengambil data couple dari invitation
        
            $male = $couples->firstWhere('gender', 'male'); //mengambil data couple yang berjenis kelamin laki-laki
            $female = $couples->firstWhere('gender', 'female');  //mengambil data couple yang berjenis kelamin perempuan

            // =========================
            // 2. AMBIL EVENT PALING AWAL
            // =========================
            $firstEvent = Event::where('invitation_id', $invitation->id)
                ->orderBy('date', 'asc')
                ->first();

            $eventDate = $firstEvent ? $firstEvent->date : null;

            //mengambil data slide (gambar dengan placement hero_slide)
            $slide = $invitation->images
                        ->filter(function ($image) {
                            return $image->placements->contains('placement', 'hero_slide');
                        })
                        ->sortBy('order')
                        ->map(function ($image) {
                            return [
                                // 'id' => $image->id,
                                'title' => $image->title,
                                'path' => asset('storage/' . $image->path),
                                'theme' => $image->theme,
                                'note' => $image->note,
                                'order' => $image->order,
                                'metadata' => $image->metadata,
                            ];
                        })
                        ->values();

            return response()->json([
                'success' => true,
                'message' => 'Login berhasil',
                'data' => [
                    'guest' => [
                        'id' => $guest->id,
                        'name' => $guest->name,
                        'uuid' => $guest->uuid,
                        'group_name' => $guest->group_name,
                        'location_tag' => $guest->location_tag,
                        // 'max_device' => $guest->max_device,
                        // 'is_opened' => $guest->is_opened,
                    ],
                    'invitation' => [
                        'theme' => $invitation->theme,
                        'title' => $invitation->title,
                    ],
                    'couple' => [
                        'male' => $male->nickname,
                        'female' => $female->nickname,
                        ], 
                        
                    'slide' => $slide,  //Foto Slide untuk cover dan hero slide
        
                    'event_date' => $eventDate ,     // tanggal paling awal

                    'token' => $apiToken,
                    'token_type' => 'Bearer',
                    'expires_in' => 30 * 24 * 60 * 60, // 30 hari
                    'device_fingerprint' => $deviceFingerprint
                ]
            ], 200);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ],  500);
        }
    }
    
    // Get current authenticated guest
    public function me(Request $request)
    {
        $guest = $request->user();
        
        // Validasi device masih valid
        $currentToken = $guest->currentAccessToken();
        $deviceFingerprint = $currentToken->device_fingerprint ?? null;
        
        if ($deviceFingerprint && !in_array($deviceFingerprint, $guest->device_ids ?? [])) {
            return response()->json([
                'success' => false,
                'message' => 'Device tidak dikenali, silahkan login ulang'
            ], 401);
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'id' => $guest->id,
                'name' => $guest->name,
                'uuid' => $guest->uuid,
                'note' => $guest->note,
                'group_name' => $guest->group_name,
                'location_tag' => $guest->location_tag,
                'is_opened' => $guest->is_opened,
                'opened_at' => $guest->opened_at,
                'max_device' => $guest->max_device,
                'registered_devices' => count($guest->device_ids ?? [])
            ]
        ]);
    }
    
    // Logout (revoke current token)
    public function logout(Request $request)
    {
        $guest = $request->user();
        $currentToken = $guest->currentAccessToken();
        
        // Hapus device fingerprint dari daftar device_ids
        $deviceFingerprint = $currentToken->device_fingerprint ?? null;
        if ($deviceFingerprint) {
            $devices = $guest->device_ids ?? [];
            $devices = array_filter($devices, fn($d) => $d !== $deviceFingerprint);
            $guest->device_ids = array_values($devices);
            $guest->save();
        }
        
        // Hapus token
        $currentToken->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Logout berhasil'
        ]);
    }
    
    // Get all registered devices
    public function getDevices(Request $request)
    {
        $guest = $request->user();
        $devices = $guest->device_ids ?? [];
        
        // Ambil informasi device dari tokens
        $tokens = $guest->tokens()->get();
        $deviceInfo = [];
        
        foreach ($devices as $index => $fingerprint) {
            $token = $tokens->firstWhere('device_fingerprint', $fingerprint);
            $deviceInfo[] = [
                'fingerprint' => $fingerprint,
                'is_current' => ($token && $token->id === $guest->currentAccessToken()->id),
                'last_used_at' => $token ? $token->last_used_at : null,
                'created_at' => $token ? $token->created_at : null
            ];
        }
        
        return response()->json([
            'success' => true,
            'data' => [
                'devices' => $deviceInfo,
                'max_device' => $guest->max_device,
                'current_device_count' => count($devices)
            ]
        ]);
    }
    
    // Revoke specific device (logout dari device tertentu)
    public function revokeDevice(Request $request, $fingerprint)
    {
        $guest = $request->user();
        $currentToken = $guest->currentAccessToken();
        
        // Cek apakah yang dihapus device sendiri
        if ($currentToken->device_fingerprint === $fingerprint) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak bisa menghapus device yang sedang aktif'
            ], 400);
        }
        
        // Hapus dari daftar device_ids
        $devices = $guest->device_ids ?? [];
        $devices = array_filter($devices, fn($d) => $d !== $fingerprint);
        $guest->device_ids = array_values($devices);
        $guest->save();
        
        // Hapus token terkait
        $guest->tokens()->where('device_fingerprint', $fingerprint)->delete();
        
        return response()->json([
            'success' => true,
            'message' => 'Device berhasil dihapus'
        ]);
    }
    
    // Refresh token (extend expired token)
    public function refreshToken(Request $request)
    {
        $token = $request->bearerToken();
        
        if (!$token) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak ditemukan'
            ], 401);
        }
        
        $tokenModel = PersonalAccessToken::findToken($token);
        
        if (!$tokenModel) {
            return response()->json([
                'success' => false,
                'message' => 'Token tidak valid'
            ], 401);
        }
        
        $guest = $tokenModel->tokenable;
        
        // Cek apakah token expired
        if ($tokenModel->expires_at && $tokenModel->expires_at->isPast()) {
            // Buat token baru
            $tokenModel->delete();
            $newToken = $guest->createToken($tokenModel->name, ['*'], now()->addDays(30))->plainTextToken;
            
            return response()->json([
                'success' => true,
                'token' => $newToken,
                'token_type' => 'Bearer',
                'expires_in' => 30 * 24 * 60 * 60
            ]);
        }
        
        return response()->json([
            'success' => true,
            'message' => 'Token masih valid',
            'expires_in' => $tokenModel->expires_at->diffInSeconds(now())
        ]);
    }
    
    // Helper untuk generate device fingerprint dari header
    private function getDeviceFingerprint(Request $request)
    {
        $data = [
            $request->header('x-device-id', $request->ip()),
            $request->header('user-agent'),
            $request->header('x-platform', 'web'),
            $request->header('x-app-version', '1.0')
        ];
        
        return hash('sha256', implode('|', $data));
    }
}
