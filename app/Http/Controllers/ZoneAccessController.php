<?php

namespace App\Http\Controllers;

use App\Models\AccessLog;
use App\Models\Credential;
use App\Models\User;
use App\Models\Zone;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use thiagoalessio\TesseractOCR\TesseractOCR;

class ZoneAccessController extends Controller
{
    public function attempt(Request $request)
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'image'   => ['required', 'image', 'max:10240'],
        ]);

        $extracted = trim(
            (new TesseractOCR($request->file('image')->getRealPath()))
                ->executable(config('services.tesseract.binary'))
                ->run()
        );
        dd($extracted);

        $isAuthorized = false;

        if ($extracted !== '') {
            $credential = Credential::where('credential_code', $extracted)
                ->where('user_id', $data['user_id'])
                ->where('is_active', true)
                ->first();

            if ($credential) {
                $isAuthorized = User::where('id', $data['user_id'])
                    ->whereHas('roles.zones', fn($q) => $q->where('zones.id', $data['zone_id']))
                    ->exists();
            }
        }

        $log = AccessLog::create([
            'log_code'         => Str::random(12),
            'user_id'          => $data['user_id'],
            'zone_id'          => $data['zone_id'],
            'action_type'      => 'zone_access',
            'is_authorized'    => $isAuthorized,
            'credential_type'  => 'id_card',
            'credential_value' => $extracted,
        ]);

        $log->users()->attach($data['user_id']);

        return response()->json([
            'authorized' => $isAuthorized,
            'log_code'   => $log->log_code,
            'message'    => $isAuthorized ? 'Access granted' : 'Access denied',
        ]);
    }
}
