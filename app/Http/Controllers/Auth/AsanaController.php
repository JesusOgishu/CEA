<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Models\AtUser;

class AsanaController extends Controller
{
    protected $client;
    protected $redirectUri;

    public function __construct()
    {
        $this->client = new Client();
        $this->redirectUri = env('ASANA_REDIRECT_URI');
    }

    /**
     * Redirige al usuario al login de Asana 
     */
    public function redirectToProvider()
    {
        $url = "https://app.asana.com/-/oauth_authorize?client_id=" . env('ASANA_CLIENT_ID') .
               "&redirect_uri=" . $this->redirectUri .
               "&response_type=code&scope=default";

        return Redirect::to($url);
    }

    /**
     * Callback del flujo OAuth de Asana
     */
    public function handleProviderCallback(Request $request)
    {
        $code = $request->input('code');
        if (!$code) {
            return redirect()->route('landing')->withErrors('Authorization failed or denied.');
        }

        // solicitar token
        try {
            $response = $this->client->post('https://app.asana.com/-/oauth_token', [
                'form_params' => [
                    'client_id'     => env('ASANA_CLIENT_ID'),
                    'client_secret' => env('ASANA_CLIENT_SECRET'),
                    'redirect_uri'  => $this->redirectUri,
                    'code'          => $code,
                    'grant_type'    => 'authorization_code',
                ]
            ]);
        } catch (\Exception $e) {
            return redirect()->route('landing')->withErrors('Error al obtener tokens de Asana.');
        }

        $data = json_decode($response->getBody()->getContents(), true);

        if (!isset($data['access_token'])) {
            return redirect()->route('landing')->withErrors('Failed to get access token from Asana.');
        }

        $accessToken  = $data['access_token'];
        $refreshToken = $data['refresh_token'] ?? null;
        $expiresIn    = $data['expires_in'] ?? null;

        // detalles 
        try {
            $userResponse = $this->client->get('https://app.asana.com/api/1.0/users/me', [
                'headers' => [
                    'Authorization' => 'Bearer ' . $accessToken,
                ]
            ]);
        } catch (\Exception $e) {
            return redirect()->route('landing')->withErrors('Failed to get user details from Asana.');
        }

        $userData = json_decode($userResponse->getBody()->getContents(), true);
        $user     = $userData['data'];

        // crear user en bd
        $existingUser = AtUser::where('asana_id', $user['gid'])->first();

        $updateData = [
            'username'               => $user['name'],
            'email'                  => $user['email'],
            'asana_access_token'     => $accessToken,
            'asana_refresh_token'    => $refreshToken,
            'asana_token_expires_at' => $expiresIn ? now()->addSeconds((int) $expiresIn) : null,
        ];

        if ($existingUser) {
            $existingUser->update($updateData);
            auth()->login($existingUser, true);
        } else {
            $newUser = AtUser::create(array_merge($updateData, [
                'asana_id' => $user['gid'],
            ]));
            auth()->login($newUser, true);
        }

        return redirect('/dashboard');
    }

    /**
     * Cierra sesión y limpia tokens
     */
    public function logout(Request $request)
    {
        auth()->logout();
        Session::forget('asana_token');
        return redirect('/');
    }
}
