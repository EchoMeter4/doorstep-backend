<?php

namespace App\Http\Controllers;

use App\Models\AccessLog;
use App\Models\Credential;
use App\Models\Vehicle;
use App\Models\Zone;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class ZoneAccessController extends Controller
{
    /**
     * @throws ConnectionException
     */
    public function attempt(Request $request)
    {
        $data = $request->validate([
            'zone_id' => ['required', 'integer', 'exists:zones,id'],
            'image'   => ['required', 'image', 'max:10240'],
        ]);

        $zone = Zone::findOrFail($data['zone_id']);
        $imagePath = $request->file('image')->getRealPath();

        $result = $zone->type === 'vehicular'
            ? $this->attemptVehicularAccess($imagePath, $zone)
            : $this->attemptCredentialAccess($imagePath, $zone);

        $log = AccessLog::create([
            'log_code'         => Str::random(12),
            'zone_id'          => $zone->id,
            'action_type'      => 'zone_access',
            'is_authorized'    => $result['authorized'],
            'credential_type'  => $result['credential_type'],
            'credential_value' => $result['credential_value'],
        ]);

        $log->users()->sync($result['related_user_ids']);

        return response()->json([
            'authorized' => $result['authorized'],
            'log_code'   => $log->log_code,
            'extracted'  => [
                'type'  => $result['credential_type'],
                'value' => $result['credential_value'],
            ],
            'message'    => $result['authorized'] ? 'Access granted' : 'Access denied',
        ]);
    }

    /**
     * @throws ConnectionException
     */
    private function attemptVehicularAccess(string $imagePath, Zone $zone): array
    {
        $response = Http::withToken(config('services.plate_recognizer.token'), 'Token')
            ->attach('upload', file_get_contents($imagePath), 'plate.jpg')
            ->post(config('services.plate_recognizer.endpoint'));

        $plate = strtoupper(trim($response->json('results.0.plate') ?? ''));

        if ($plate === '') {
            return [
                'authorized'       => false,
                'credential_type'  => 'lpn',
                'credential_value' => '',
                'related_user_ids' => [],
            ];
        }

        $vehicle = Vehicle::query()
            ->where('plate_number', $plate)
            ->whereNull('deleted_at')
            ->with(['users.roles.zones'])
            ->first();

        if (!$vehicle) {
            return [
                'authorized'       => false,
                'credential_type'  => 'lpn',
                'credential_value' => $plate,
                'related_user_ids' => [],
            ];
        }

        $relatedUserIds = $vehicle->users
            ->pluck('id')
            ->unique()
            ->values()
            ->all();

        $authorized = $vehicle->users
            ->where('enabled', true)
            ->contains(fn ($user) => $this->userHasZoneAccess($user, $zone->id));

        return [
            'authorized'       => $authorized,
            'credential_type'  => 'lpn',
            'credential_value' => $plate,
            'related_user_ids' => $relatedUserIds,
        ];
    }

    /**
     * @throws ConnectionException
     */
    private function attemptCredentialAccess(string $imagePath, Zone $zone): array
    {
        $response = Http::withHeaders([
            'Ocp-Apim-Subscription-Key' => config('services.azure_vision.key'),
            'Content-Type'              => 'application/octet-stream',
        ])
            ->withBody(file_get_contents($imagePath), 'application/octet-stream')
            ->post(
                config('services.azure_vision.endpoint') . '/computervision/imageanalysis:analyze?api-version=2024-02-01&features=read'
            );

        $credentialCode = $this->extractCredentialCodeFromAzure($response->json());

        if ($credentialCode === '') {
            return [
                'authorized'       => false,
                'credential_type'  => 'credential',
                'credential_value' => '',
                'related_user_ids' => [],
            ];
        }

        $credential = Credential::query()
            ->with('user.roles.zones')
            ->where('credential_code', $credentialCode)
            ->where('is_active', true)
            ->first();

        $user = $credential?->user;

        if (!$user || !$user->enabled) {
            return [
                'authorized'       => false,
                'credential_type'  => 'credential',
                'credential_value' => $credentialCode,
                'related_user_ids' => [],
            ];
        }

        return [
            'authorized'       => $this->userHasZoneAccess($user, $zone->id),
            'credential_type'  => 'credential',
            'credential_value' => $credentialCode,
            'related_user_ids' => [$user->id],
        ];
    }

    private function extractCredentialCodeFromAzure(array $payload): string
    {
        foreach ($payload['readResult']['blocks'] ?? [] as $block) {
            foreach ($block['lines'] ?? [] as $line) {
                foreach ($line['words'] ?? [] as $word) {
                    $text = $word['text'] ?? '';

                    if (preg_match('/^\d{9}$/', $text)) {
                        return $text;
                    }
                }
            }
        }

        return '';
    }

    private function userHasZoneAccess($user, int $zoneId): bool
    {
        return $user->roles
            ->where('enabled', true)
            ->contains(fn ($role) => $role->zones->contains('id', $zoneId));
    }
}