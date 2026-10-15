<?php

namespace App\Http\Controllers;
use Google\Client as Google_Client;
use Google\Service\Sheets as Google_Service_Sheets;
use Google\Service\Drive as Google_Service_Drive;
use Illuminate\Http\Request;
use App\Models\Sheet;
use Filament\Notifications\Notification;
class GoogleOAuthController extends Controller
{
     public function redirect(Request $request)
    {
        $client = new Google_Client();
    $client->setClientId(config('services.google.client_id'));
    $client->setClientSecret(config('services.google.client_secret'));
    $client->setRedirectUri(config('services.google.redirect'));
    $client->addScope(Google_Service_Sheets::SPREADSHEETS_READONLY);
    $client->addScope(Google_Service_Drive::DRIVE_READONLY);
    $client->setAccessType('offline');
    $client->setPrompt('consent select_account');

    // Pass sheet_id so callback knows which record to update
    $client->setState($request->sheet_id);

    return redirect($client->createAuthUrl());
    }

    public function callback(Request $request)
    {
       $client = new Google_Client();
    $client->setClientId(config('services.google.client_id'));
    $client->setClientSecret(config('services.google.client_secret'));
    $client->setRedirectUri(config('services.google.redirect'));

    // Exchange code for token
    $token = $client->fetchAccessTokenWithAuthCode($request->code);

    if (isset($token['error'])) {
        Notification::make()        
            ->title('Google auth failed: ' . $token['error'])
            ->danger()
            ->send();
        return redirect()->route('filament.app.resources.sheets.index');
    }

    // Get the sheet_id we passed via state
    $sheetId = $request->state;

    Sheet::where('id', $sheetId)
         ->where('user_id', auth()->id()) // security check
         ->update([
             'access_token'     => $token['access_token'],
             'refresh_token'    => $token['refresh_token'] ?? null,
             'token_expires_at' => now()->addSeconds($token['expires_in']),
         ]);
         Notification::make()
            ->title('Google Sheets connected successfully!')
            ->success()
            ->send();

    return redirect()->route('filament.app.resources.sheets.index');
    }
}
