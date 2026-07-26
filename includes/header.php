<?php
if (!defined('APP_NAME')) { require_once __DIR__ . '/../config/config.php'; }
require_once __DIR__ . '/../src/Database/Database.php';
require_once __DIR__ . '/../src/Analytics/VisiteService.php';

use App\Analytics\VisiteService;

// On ne comptabilise pas les visites de l'espace admin (/admin/...)
$isAdminArea = isset($_SERVER['REQUEST_URI']) && str_starts_with($_SERVER['REQUEST_URI'], '/admin');
if (!$isAdminArea) {
    VisiteService::track();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?? APP_NAME ?></title>
    <meta name="description" content="Un accompagnement complet pour transformer ton résultat au bac en une orientation claire, choisie et assumée.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,600;9..144,700;9..144,900&family=Manrope:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="/assets/css/style.css">
</head>
<body>