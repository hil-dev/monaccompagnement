<?php
require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/src/Database/Database.php';
require_once __DIR__ . '/src/Auth/AuthService.php';

use App\Database\Database;
use App\Auth\AuthService;

$pageTitle = APP_NAME . ' — Du choix de ta filière à l’obtention de ton allocation';

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
        ['code' => 'premium', 'nom' => 'Premium', 'prix' => 982, 'places_restantes' => 210, 'places_totales' => 210,
            'avantages' => ['Bilan d’orientation personnalisé', 'Accès au guide des filières', 'Support par email']],
    ];
}
$nbFormules = count($formules);

// Utilisateur connecté ?
$currentUser = null;
$initiales = '';
if (AuthService::isLoggedIn()) {
    $pdoUser = Database::getConnection();
    $stmtUser = $pdoUser->prepare('SELECT * FROM users WHERE id = ?');
    $stmtUser->execute([AuthService::currentUserId()]);
    $currentUser = $stmtUser->fetch();

    if ($currentUser) {
        $localPart = explode('@', $currentUser['email'])[0];
        $morceaux = preg_split('/[._\-]/', $localPart);
        $morceaux = array_filter($morceaux);
        $morceaux = array_values($morceaux);
        if (count($morceaux) >= 2) {
            $initiales = strtoupper(mb_substr($morceaux[0], 0, 1) . mb_substr($morceaux[1], 0, 1));
        } else {
            $initiales = strtoupper(mb_substr($localPart, 0, 2));
        }
    }
}

require_once __DIR__ . '/includes/header.php';
?>

<div class="urgency-bar">
    <span class="urgency-dot"></span>
    <strong>Places limitées</strong> : Programme d’accompagnement forfaitaire, personnalisé et à durée limitée, spécialement conçu pour accompagner les bacheliers 2026 dans le choix de leurs filières en ligne. Bénéficiez d’un encadrement professionnel pour faire le bon choix, au bon moment.
</div>

<header class="topbar">
    <div class="topbar-inner">
        <span class="brand">APRÈS<strong>BAC</strong></span>

        <?php if ($currentUser): ?>
            <div class="user-menu">
                <button class="user-avatar" id="userAvatarBtn" type="button" aria-label="Menu du compte">
                    <span class="user-avatar-ring">
                        <span class="user-avatar-initials"><?= htmlspecialchars($initiales) ?></span>
                    </span>
                </button>

                <div class="user-dropdown" id="userDropdown">
                    <div class="user-dropdown-header">
                        <span class="user-dropdown-avatar"><?= htmlspecialchars($initiales) ?></span>
                        <div class="user-dropdown-identity">
                            <p class="user-dropdown-name">Mon compte</p>
                            <p class="user-dropdown-email"><?= htmlspecialchars($currentUser['email']) ?></p>
                        </div>
                    </div>

                    <div class="user-dropdown-divider"></div>

                    <a href="/profil.php" class="user-dropdown-item">
                        <span class="user-dropdown-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                        </span>
                        Mon profil
                    </a>

                    <a href="/auth/logout.php" class="user-dropdown-item user-dropdown-item-danger">
                        <span class="user-dropdown-icon">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path><polyline points="16 17 21 12 16 7"></polyline><line x1="21" y1="12" x2="9" y2="12"></line></svg>
                        </span>
                        Se déconnecter
                    </a>
                </div>
            </div>
        <?php else: ?>
            <a href="/connexion.php" class="btn btn-ghost">Se connecter</a>
        <?php endif; ?>
    </div>
</header>

<section class="hero">
    <div class="hero-inner">
        <div class="hero-text">
            <p class="eyebrow">Bacheliers 2026 · Orientation garantie</p>
            <h1>Ton bac est en poche.<br>Reste plus qu'à choisir <em>la bonne filière</em>, pas n'importe laquelle. Il te faut un guide.</h1>
            <p class="hero-sub">
                Filière, université, ville, bourse : des dizaines de choix, une seule vraie décision à prendre.
                On t'accompagne pas à pas pour transformer ta série et ta mention en un plan d'orientation clair,
                réaliste et qui te ressemble.
            </p>
            <a href="#formules" class="btn btn-primary">Obtenir rapidement ma place</a>
            <p class="hero-note" style="color:#e02424;">Déjà <strong>des centaines de bacheliers</strong> accompagnés cette année.</p>
        </div>
        <div class="hero-illustration">
            <a href="#formules" class="btn btn-primary hero-cta-mobile">Obtenir rapidement ma place</a>
            <div class="illustration-frame">
                <img src="/assets/img/etudiant-reflexion.png" alt="Illustration d'un étudiant en pleine réflexion sur son orientation" />
            </div>
        </div>
    </div>
</section>

<section class="probleme">
    <div class="section-inner">
        <h2>Le bac, c'était l'étape facile.</h2>
        <ul class="probleme-list">
            <li>Tu as ta série et ta mention, mais aucune idée de la filière qui te correspond vraiment.</li>
            <li>Tes parents, tes amis, Internet : tout le monde a un avis différent sur ton orientation.</li>
            <li>Tu as peur de choisir la mauvaise filière et de le regretter dans deux ans.</li>
            <li>Les délais d'inscription approchent et tu n'as toujours pas de plan clair.</li>
        </ul>
        <p class="probleme-conclusion">
            Ce n'est pas un manque de mérite. C'est un manque d'accompagnement. <strong>C'est exactement ce qu'on corrige ici.</strong>
        </p>
    </div>
</section>

<section id="formules" class="formules">
    <div class="section-inner">
        <p class="eyebrow eyebrow-center">Choisis ton niveau d'accompagnement</p>
        <h2 class="formules-title">Passe à l'action et bénéfice d'un accompagnemen</h2>

        <div class="cartes-formules cartes-formules-<?= $nbFormules ?>" <?= $nbFormules === 1 ? 'style="display:flex; justify-content:center;"' : '' ?>>
            <?php foreach ($formules as $index => $f):
                $pourcentageRestant = $f['places_totales'] > 0
                    ? round(($f['places_restantes'] / $f['places_totales']) * 100)
                    : 0;
                $complet = $f['places_restantes'] <= 0;
                $isFeatured = ($index === $nbFormules - 1) && $nbFormules > 1;
            ?>
            <div class="carte-formule carte-<?= htmlspecialchars($f['code']) ?> <?= $isFeatured ? 'carte-featured' : '' ?>">
                <?php if ($isFeatured): ?><span class="badge-populaire">Le plus complet</span><?php endif; ?>
                <p class="carte-prix"><?= number_format((float)$f['prix'], 0, ',', ' ') ?> <span>FCFA</span></p>

                <ul class="carte-avantages">
                    <?php foreach ($f['avantages'] as $avantage): ?>
                        <li><?= htmlspecialchars($avantage) ?></li>
                    <?php endforeach; ?>
                </ul>

                <div class="carte-places">
                    <div class="jauge">
                        <div class="jauge-remplie" style="width: <?= $pourcentageRestant ?>%"></div>
                    </div>
                    <p class="places-texte">
                        <?php if ($complet): ?>
                            Complet
                        <?php else: ?>
                            <strong><?= (int)$f['places_restantes'] ?></strong> place<?= $f['places_restantes'] > 1 ? 's' : '' ?> restante<?= $f['places_restantes'] > 1 ? 's' : '' ?> sur <?= (int)$f['places_totales'] ?>
                        <?php endif; ?>
                    </p>
                </div>

                <?php if ($complet): ?>
                    <button class="btn btn-disabled" disabled>Places épuisées</button>
                <?php else: ?>
                    <a href="<?= $currentUser ? '/orientation-formulaire.php?formule=' . htmlspecialchars($f['code']) : '/connexion.php?formule=' . htmlspecialchars($f['code']) ?>" class="btn btn-rouge">
                        Je passe à l'action
                    </a>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="urgency-bar formules-urgency">
            <span class="urgency-dot"></span>
            <strong>Places limitées</strong> : Programme d’accompagnement forfaitaire, personnalisé et à durée limitée, spécialement conçu pour accompagner les bacheliers 2026 dans le choix de leurs filières en ligne. Ce programme prend fin <strong>rigoureusement à la fermeture du site officiel de choix de filières (apresmonbac.bj)</strong>. Bénéficiez d’un encadrement professionnel pour faire le bon choix, au bon moment, avant qu'il ne soit trop tard.
        </div>
    </div>
</section>


<?php require_once __DIR__ . '/includes/footer.php'; ?>

<?php if ($currentUser): ?>
<script>
document.addEventListener('DOMContentLoaded', () => {
    const btn = document.getElementById('userAvatarBtn');
    const menu = document.getElementById('userDropdown');

    btn.addEventListener('click', (e) => {
        e.stopPropagation();
        menu.classList.toggle('is-open');
        btn.classList.toggle('is-active');
    });

    document.addEventListener('click', () => {
        menu.classList.remove('is-open');
        btn.classList.remove('is-active');
    });

    menu.addEventListener('click', (e) => e.stopPropagation());
});
</script>
<?php endif; ?>