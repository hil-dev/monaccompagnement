<?php
declare(strict_types=1);

namespace App\Auth;

/**
 * Intégration Google OAuth 2.0 minimaliste (pas de SDK externe, juste cURL).
 */
class GoogleAuth
{
    private const AUTH_URL = 'https://accounts.google.com/o/oauth2/v2/auth';
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const USERINFO_URL = 'https://www.googleapis.com/oauth2/v3/userinfo';

    public static function getAuthUrl(): string
    {
        // CSRF protection via un state aléatoire stocké en session
        $state = bin2hex(random_bytes(16));
        $_SESSION['google_oauth_state'] = $state;

        $params = [
            'client_id' => GOOGLE_CLIENT_ID,
            'redirect_uri' => GOOGLE_REDIRECT_URI,
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
        ];

        return self::AUTH_URL . '?' . http_build_query($params);
    }

    /**
     * Échange le code d'autorisation contre un access token, puis récupère le profil.
     * @return array{sub: string, email: string, name: ?string} Profil Google normalisé
     * @throws \RuntimeException en cas d'échec
     */
    public static function handleCallback(string $code, string $state): array
    {
        if (!isset($_SESSION['google_oauth_state']) || !hash_equals($_SESSION['google_oauth_state'], $state)) {
            throw new \RuntimeException('Requête d\'authentification invalide (state incorrect).');
        }
        unset($_SESSION['google_oauth_state']);

        $tokenResponse = self::httpPost(self::TOKEN_URL, [
            'code' => $code,
            'client_id' => GOOGLE_CLIENT_ID,
            'client_secret' => GOOGLE_CLIENT_SECRET,
            'redirect_uri' => GOOGLE_REDIRECT_URI,
            'grant_type' => 'authorization_code',
        ]);

        if (!isset($tokenResponse['access_token'])) {
            throw new \RuntimeException('Impossible d\'obtenir le token Google.');
        }

        $profile = self::httpGet(self::USERINFO_URL, $tokenResponse['access_token']);

        if (!isset($profile['sub'], $profile['email'])) {
            throw new \RuntimeException('Profil Google incomplet.');
        }

        return [
            'sub' => $profile['sub'],
            'email' => $profile['email'],
            'name' => $profile['name'] ?? null,
        ];
    }

    private static function httpPost(string $url, array $fields): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query($fields),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('Erreur réseau Google OAuth : ' . $error);
        }

        return json_decode($response, true) ?? [];
    }

    private static function httpGet(string $url, string $accessToken): array
    {
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_HTTPHEADER => ["Authorization: Bearer $accessToken"],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
        ]);
        $response = curl_exec($ch);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false) {
            throw new \RuntimeException('Erreur réseau Google OAuth : ' . $error);
        }

        return json_decode($response, true) ?? [];
    }
}
