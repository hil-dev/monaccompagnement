<?php
declare(strict_types=1);

namespace App\Accompagnement;

require_once __DIR__ . '/../Database/Database.php';

use App\Database\Database;

/**
 * Remplace l'ancien AuthService public : il n'y a plus de compte,
 * de login ni de mot de passe côté visiteur. Ce qui reste utile
 * (génération du code d'accompagnement) est conservé ici, désormais
 * rattaché à `paiements` plutôt qu'à l'ancienne table `users`.
 */
class AccompagnementService
{
    /** Séries de bac acceptées */
    public const SERIES = ['A1', 'A2', 'B', 'C', 'D', 'E', 'F1', 'F2', 'F3', 'F4', 'G1', 'G2', 'G3'];

    /**
     * Génère un code d'accompagnement unique, à appeler une seule fois,
     * juste après un paiement réussi (ex: dans paiement-callback.php),
     * puis à stocker dans paiements.code_accompagnement.
     */
    public static function generateCodeAccompagnement(): string
    {
        $pdo = Database::getConnection();
        do {
            $code = 'APB-' . strtoupper(bin2hex(random_bytes(3))); // ex: APB-9F3C1A
            $stmt = $pdo->prepare('SELECT id FROM paiements WHERE code_accompagnement = ?');
            $stmt->execute([$code]);
            $existe = $stmt->fetch();
        } while ($existe);

        return $code;
    }
}