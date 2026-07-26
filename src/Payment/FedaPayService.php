<?php
declare(strict_types=1);

namespace App\Payment;

class FedaPayService
{
    private const API_BASE = 'https://api.fedapay.com/v1';

    /**
     * Crée une transaction FedaPay et retourne son objet (id, statut, etc.)
     * @throws \RuntimeException
     */
    public static function createTransaction(array $payload): array
    {
        $ch = curl_init(self::API_BASE . '/transactions');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . FEDAPAY_SECRET_KEY,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($payload),
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);

        if ($response === false) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \RuntimeException('Erreur de connexion à FedaPay : ' . $error);
        }
        curl_close($ch);

        $data = json_decode($response, true);
        if (!isset($data['v1/transaction']['id'])) {
            error_log('FedaPay createTransaction — réponse inattendue : ' . $response);
            throw new \RuntimeException('Impossible de créer la transaction FedaPay.');
        }

        return $data['v1/transaction'];
    }

    /**
     * Récupère l'URL de paiement (page hébergée FedaPay) pour une transaction donnée.
     * @throws \RuntimeException
     */
    public static function getPaymentUrl(int $transactionId): string
    {
        $ch = curl_init(self::API_BASE . "/transactions/{$transactionId}/token");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . FEDAPAY_SECRET_KEY,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if (!isset($data['url'])) {
            error_log('FedaPay getPaymentUrl — réponse inattendue : ' . $response);
            throw new \RuntimeException('Impossible de générer le lien de paiement.');
        }

        return $data['url'];
    }

    /**
     * Récupère l'état actuel d'une transaction depuis FedaPay (pour vérification au callback).
     * @throws \RuntimeException
     */
    public static function fetchTransaction(int $transactionId): array
    {
        $ch = curl_init(self::API_BASE . "/transactions/{$transactionId}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . FEDAPAY_SECRET_KEY,
                'Content-Type: application/json',
                'Accept: application/json',
            ],
            CURLOPT_TIMEOUT => 30,
        ]);
        $response = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($response, true);
        if (!isset($data['v1/transaction'])) {
            error_log('FedaPay fetchTransaction — réponse inattendue : ' . $response);
            throw new \RuntimeException('Transaction FedaPay introuvable.');
        }

        return $data['v1/transaction'];
    }
}