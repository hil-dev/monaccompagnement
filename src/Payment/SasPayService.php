<?php
declare(strict_types=1);

namespace App\Payment;

/**
 * Service d'intégration SasPay (checkout hébergé).
 * Doc : https://docs.saspay.me/
 *
 * Constante requise dans config/config.php :
 *   define('SASPAY_SECRET_KEY', 'sk_test_xxx');   // sk_live_xxx en production
 */
class SasPayService
{
    private const API_BASE = 'https://api.saspay.me/api/v1';

    /**
     * Crée une session de checkout hébergé.
     * Retourne un tableau contenant notamment : id, checkout_url, status, amount...
     *
     * @throws \RuntimeException
     */
    public static function createCheckoutSession(array $payload): array
    {
        $data = self::request('POST', '/checkout-sessions/', $payload);

        if (empty($data['id']) || empty($data['checkout_url'])) {
            error_log('SasPay createCheckoutSession — réponse inattendue : ' . json_encode($data));
            throw new \RuntimeException('Impossible de créer la session de paiement SasPay.');
        }

        return $data;
    }

    /**
     * Récupère l'état actuel d'une session de checkout (pour vérification au retour du client).
     * Statuts possibles : PENDING, PAID, EXPIRED, CANCELLED.
     *
     * @throws \RuntimeException
     */
    public static function fetchCheckoutSession(string $sessionId): array
    {
        // L'id est un UUID : on refuse tout autre format avant de l'insérer dans l'URL
        if (!preg_match('/^[0-9a-fA-F-]{32,40}$/', $sessionId)) {
            throw new \RuntimeException('Identifiant de session SasPay invalide.');
        }

        $data = self::request('GET', '/checkout-sessions/' . $sessionId . '/');

        if (empty($data['id']) || !isset($data['status'])) {
            error_log('SasPay fetchCheckoutSession — réponse inattendue : ' . json_encode($data));
            throw new \RuntimeException('Session SasPay introuvable.');
        }

        return $data;
    }

    /**
     * Appel HTTP générique. Gère l'enveloppe de réponse SasPay
     * ({"success": true, "data": {...}}) ainsi que les réponses "à plat".
     *
     * @throws \RuntimeException
     */
    private static function request(string $method, string $path, ?array $body = null): array
    {
        $ch = curl_init(self::API_BASE . $path);

        $options = [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST  => $method,
            CURLOPT_HTTPHEADER     => [
                'Authorization: Bearer ' . SASPAY_SECRET_KEY,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT        => 30,
        ];

        if ($body !== null) {
            $options[CURLOPT_POSTFIELDS] = json_encode($body);
        }

        curl_setopt_array($ch, $options);
        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Erreur de connexion à SasPay : ' . $error);
        }

        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $json = json_decode($response, true);
        if (!is_array($json)) {
            error_log("SasPay {$method} {$path} — réponse non JSON (HTTP {$httpCode}) : " . $response);
            throw new \RuntimeException('Réponse invalide de SasPay.');
        }

        if ($httpCode < 200 || $httpCode >= 300 || (isset($json['success']) && $json['success'] === false)) {
            error_log("SasPay {$method} {$path} — erreur HTTP {$httpCode} : " . $response);
            throw new \RuntimeException("SasPay a refusé la requête (HTTP {$httpCode}).");
        }

        // Enveloppe {"success": true, "data": {...}} ou réponse directe
        return (isset($json['data']) && is_array($json['data'])) ? $json['data'] : $json;
    }
}