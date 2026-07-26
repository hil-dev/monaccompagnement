<?php
declare(strict_types=1);

namespace App\Analytics;

use App\Database\Database;

/**
 * Suivi simple des visites du site.
 *
 * Une "visite" correspond à un visiteur (session) unique par jour :
 * recharger la même page plusieurs fois dans la même journée ne compte
 * qu'une seule fois. Cela donne un chiffre de fréquentation réaliste
 * plutôt qu'un simple compteur de pages vues.
 */
class VisiteService
{
    public static function track(): void
    {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        if (!isset($_SESSION['visite_id'])) {
            $_SESSION['visite_id'] = bin2hex(random_bytes(16));
        }

        try {
            $pdo = Database::getConnection();
            $stmt = $pdo->prepare(
                'INSERT IGNORE INTO visites (session_id, date_visite) VALUES (?, CURDATE())'
            );
            $stmt->execute([$_SESSION['visite_id']]);
        } catch (\Throwable $e) {
            // On ne casse jamais l'affichage d'une page à cause du tracking.
            error_log('VisiteService::track — ' . $e->getMessage());
        }
    }

    public static function totalVisites(): int
    {
        try {
            return (int) Database::getConnection()
                ->query('SELECT COUNT(*) FROM visites')
                ->fetchColumn();
        } catch (\Throwable $e) {
            error_log('VisiteService::totalVisites — ' . $e->getMessage());
            return 0;
        }
    }

    public static function visitesAujourdhui(): int
    {
        try {
            return (int) Database::getConnection()
                ->query("SELECT COUNT(*) FROM visites WHERE date_visite = CURDATE()")
                ->fetchColumn();
        } catch (\Throwable $e) {
            error_log('VisiteService::visitesAujourdhui — ' . $e->getMessage());
            return 0;
        }
    }
}