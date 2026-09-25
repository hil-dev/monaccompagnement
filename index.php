<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database/Database.php';

use App\Database\Database;

$pageTitle = APP_NAME . ' — De ton inscription à ton premier virement d’allocation';

// Récupération des formules depuis la BDD (avec repli si la BDD n'est pas encore configurée)
try {
    $pdo = Database::getConnection();
    $stmt = $pdo->query("SELECT * FROM formules WHERE actif = 1 ORDER BY prix ASC");
    $formules = $stmt->fetchAll();
    foreach ($formules as &$f) {
        $f['avantages'] = json_decode($f['avantages'], true);
    }
    unset($f);
} catch (\Throwable $e) {
    $formules = [
        ['code' => 'premium', 'nom' => 'Premium', 'prix' => 2000, 'places_restantes' => 210, 'places_totales' => 210,
            'avantages' => [
                'Espace de discussion WhatsApp privé',
                'Accompagnement concernant l’inscription',
                'Aides et conseils pour l’ouverture de compte bancaire',
                'Assistance complète pour la procédure de la demande d’allocation en ligne',
                'Suivi de la demande jusqu’au premier virement',
                'Assistance en cas de réclamation',
            ]],
    ];
}
$nbFormules = count($formules);

require_once __DIR__ . '/includes/header.php';
?>

<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,700;12..96,800&family=Figtree:wght@400;500;600;700&display=swap">

<div class="home-v2">

<div class="urgency-bar">
    <span class="urgency-dot" aria-hidden="true"></span>
    <p class="urgency-text">Vous êtes nouveau bachelier boursier ou secouru ? Nous vous prenons en charge de A à Z. Notre programme d’accompagnement personnalisé vous suit depuis votre inscription jusqu’à ce que vous perceviez votre première allocation universitaire.</p>
</div>

<div class="hero-wrap">
    <header class="topbar">
        <div class="topbar-inner">
            <span class="brand">APRÈS<strong>BAC</strong></span>
        </div>
    </header>

    <section class="hero">
        <div class="hero-inner">
            <div class="hero-text">
                <p class="eyebrow">Bacheliers 2026</p>
                <h1>On t’accompagne jusqu’à ton <em>premier virement d’allocation</em>.</h1>
                <p class="hero-sub">
                    Inscription, conseils pour l’ouverture de compte bancaire, procédure de la demande d’allocation en ligne, suivi… chaque étape avec toi.
                </p>
                
                <div class="hero-cta">
                    <a href="#formules" class="btn btn-primary">Obtenir rapidement ma place</a>
                </div>
            </div>
            <div class="hero-illustration">
                <div class="hero-cta hero-cta-mobile">
                    <a href="#formules" class="btn btn-primary">Obtenir rapidement ma place</a>
                </div>
                <div class="illustration-frame">
                    <img src="/assets/img/img.png" alt="Illustration d'un étudiant accompagné dans ses démarches" />
                </div>
            </div>
        </div>
    </section>
</div>

<section id="formules" class="formules">
    <div class="section-inner">
        <h2 class="formules-title">Sécurise ton allocation en un seul paiement…</h2>
        <p class="formules-sub">Reste tranquille jusqu’à la réception de ton argent.</p>

        <div class="cartes-formules cartes-formules-<?= $nbFormules ?>">
            <?php foreach ($formules as $index => $f):
                $placesOccupees = $f['places_totales'] - $f['places_restantes'];
                $pourcentageOccupe = $f['places_totales'] > 0
                    ? round(($placesOccupees / $f['places_totales']) * 100)
                    : 0;
                $complet = $f['places_restantes'] <= 0;
                $isFeatured = ($index === $nbFormules - 1) && $nbFormules > 1;

                if ($pourcentageOccupe >= 80) {
                    $jaugeClasse = 'jauge-critique';
                } elseif ($pourcentageOccupe >= 50) {
                    $jaugeClasse = 'jauge-moyenne';
                } else {
                    $jaugeClasse = 'jauge-faible';
                }
            ?>
            <article class="carte-formule carte-<?= htmlspecialchars($f['code']) ?> <?= $isFeatured ? 'carte-featured' : '' ?>">
                <?php if ($isFeatured): ?><span class="badge-populaire">Le plus complet</span><?php endif; ?>

                <div class="carte-head">
                    <p class="carte-nom">Accompagnement <?= htmlspecialchars($f['nom']) ?></p>
                    <p class="carte-prix"><?= number_format((float)$f['prix'], 0, ',', ' ') ?> <span>FCFA</span></p>
                    <p class="carte-unique">Paiement unique</p>
                </div>

                <ul class="carte-avantages">
                    <?php foreach ($f['avantages'] as $avantage): ?>
                        <li><?= htmlspecialchars($avantage) ?></li>
                    <?php endforeach; ?>
                </ul>

                <div class="carte-foot">
                    <div class="carte-places">
                        <p class="places-label">
                            <?= $complet
                                ? 'Complet'
                                : $placesOccupees . ' / ' . $f['places_totales'] . ' places disponibles'
                            ?>
                        </p>
                        <div class="jauge">
                            <div class="jauge-remplie <?= $jaugeClasse ?>" style="width: <?= $pourcentageOccupe ?>%"></div>
                        </div>
                    </div>

                    <?php if ($complet): ?>
                        <button class="btn btn-disabled" disabled>Places épuisées</button>
                    <?php else: ?>
                        <a href="/accompagnement-formulaire.php?formule=<?= htmlspecialchars($f['code']) ?>" class="btn btn-rouge">
                            Je passe à l'action
                        </a>
                    <?php endif; ?>
                    <p class="carte-note">Paiement sécurisé par Mobile Money ou carte bancaire</p>
                </div>
            </article>
            <?php endforeach; ?>
        </div>
    </div>
</section>

</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>