<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database/Database.php';

use App\Database\Database;

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée.']);
    exit;
}

$email = trim($_POST['email'] ?? '');

if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => 'Merci d’entrer une adresse email valide.']);
    exit;
}

try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare('SELECT id, actif FROM newsletter_subscribers WHERE email = ?');
    $stmt->execute([$email]);
    $existing = $stmt->fetch();

    if ($existing) {
        if ((int) $existing['actif'] === 1) {
            echo json_encode(['success' => true, 'message' => 'Tu es déjà inscrit(e) à la newsletter. 🎉']);
            exit;
        }
        // Ré-abonnement d'une adresse qui s'était désinscrite
        $update = $pdo->prepare('UPDATE newsletter_subscribers SET actif = 1, unsubscribed_at = NULL WHERE id = ?');
        $update->execute([$existing['id']]);
        echo json_encode(['success' => true, 'message' => 'Inscription confirmée, bienvenue à nouveau ! 🎉']);
        exit;
    }

    $token = bin2hex(random_bytes(24));
    $insert = $pdo->prepare('INSERT INTO newsletter_subscribers (email, token) VALUES (?, ?)');
    $insert->execute([$email, $token]);

    echo json_encode(['success' => true, 'message' => 'Inscription confirmée, merci ! 🎉']);
} catch (\Throwable $e) {
    error_log('newsletter-subscribe: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Une erreur est survenue, réessaie un peu plus tard.']);
}