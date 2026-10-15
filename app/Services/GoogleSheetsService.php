<?php


namespace App\Services;

use App\Models\Sheet;
use Google\Client as Google_Client;
use Google\Service\Sheets as Google_Service_Sheets;

class GoogleSheetsService
{
    public function getClient(Sheet $sheet): Google_Client
    {
        $client = new Google_Client();
        $client->setClientId(config('services.google.client_id'));
        $client->setClientSecret(config('services.google.client_secret'));
        $client->setRedirectUri(config('services.google.redirect'));

        // Google client expects this exact structure with 'created' as unix timestamp
        $client->setAccessToken([
            'access_token'  => $sheet->access_token,
            'refresh_token' => $sheet->refresh_token,
            'expires_in'    => 3600,
            'created'       => strtotime($sheet->token_expires_at) - 3600, // ← back-calculate created time
            'token_type'    => 'Bearer',
        ]);

        if ($client->isAccessTokenExpired()) {
            if (empty($sheet->refresh_token)) {
                throw new \Exception("No refresh token available for sheet {$sheet->id}. User must reconnect.");
            }

            $newToken = $client->fetchAccessTokenWithRefreshToken($sheet->refresh_token);

            if (isset($newToken['error'])) {
                throw new \Exception("Token refresh failed: {$newToken['error_description']} — user must reconnect.");
            }

            $sheet->update([
                'access_token'     => $newToken['access_token'],
                'token_expires_at' => now()->addSeconds($newToken['expires_in']),
            ]);

            $client->setAccessToken($newToken);
        }

        return $client;
    }

    public function getRows(Sheet $sheet, int $startRow = 1, ?int $endRow = null): array
    {
        $client  = $this->getClient($sheet);
        $service = new Google_Service_Sheets($client);

        if ($startRow === 1 && $endRow === null) {
            $range = $sheet->sheet_name; // fetch entire sheet
        } elseif ($endRow !== null) {
            $range = "{$sheet->sheet_name}!A{$startRow}:Z{$endRow}"; // bounded range
        } else {
            $range = "{$sheet->sheet_name}!A{$startRow}:Z"; // from startRow to end of sheet
        }

        $response = $service->spreadsheets_values->get(
            $sheet->spreadsheet_id,
            $range
        );

        return $response->getValues() ?? [];
    }
}
