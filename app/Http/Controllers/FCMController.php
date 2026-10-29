<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserDevice;
use Illuminate\Support\Facades\Auth;
use App\Services\FcmService;
use Illuminate\Support\Facades\Log;


class FCMController extends Controller
{
    protected $fcm;

    public function __construct(FcmService $fcm)
    {
        $this->fcm = $fcm;
    }

    public function testNotification()
    {
        // Ambil semua token yang tidak null
        $tokens = UserDevice::whereNotNull('fcm_token')
            ->pluck('fcm_token')
            ->unique()
            ->values()
            ->toArray();

        if (empty($tokens)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada FCM token di database.'
            ], 404);
        }

        try {
            $this->fcm->sendNotification(
                $tokens,
                'Halo dari Infruity 🍉',
                'Tes notifikasi via Web Laravel (database user_devices)'
            );

            Log::info('✅ Notifikasi FCM berhasil dikirim ke semua token.', ['count' => count($tokens)]);
            return response()->json([
                'success' => true,
                'message' => 'Notifikasi dikirim ke semua token!',
                'total_tokens' => count($tokens)
            ]);
        } catch (\Throwable $e) {
            Log::error('❌ Gagal mengirim notifikasi: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim notifikasi',
                'error' => $e->getMessage()
            ], 500);
        }
    }


    public function store(Request $request)
    {
        $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = Auth::user(); // pastikan user sudah login (misal pakai sanctum atau jwt)

        UserDevice::updateOrCreate(
            ['fcm_token' => $request->fcm_token],
            [
                'user_id' => $user->id_user,
                'device_name' => $request->header('User-Agent') ?? 'Unknown',
            ]
        );

        return response()->json(['message' => 'FCM token saved successfully']);
    }
}
