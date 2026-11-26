<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\UserDevice;
use Illuminate\Support\Facades\Auth;


class FCMController extends Controller
{
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
