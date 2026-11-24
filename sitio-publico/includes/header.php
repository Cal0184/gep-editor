<?php require_once __DIR__ . '/hero-media.php'; ?>
<?php require_once __DIR__ . '/structured-data.php'; ?>
<?php require_once __DIR__ . '/public-notes.php'; ?>
<?php
/**
 * GEP shared header.
 * Each page defines: $pageTitle, $pageDescription, $bodyClass, $activePage, $isHome.
 */
if (!function_exists('gep_escape')) {
    function gep_escape(string $value): string {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
}

$pageTitle = $pageTitle ?? 'GEP · Geomática y Ecología del Paisaje';
$pageDescription = $pageDescription ?? 'Laboratorio de Geomática y Ecología del Paisaje de la Universidad de Chile.';
$bodyClass = $bodyClass ?? '';
$activePage = $activePage ?? '';
$isHome = $isHome ?? false;
$pageStyles = isset($pageStyles) && is_array($pageStyles) ? $pageStyles : [];

// The site automatically uses the official logo when one of these files exists.
$logoPath = null;
$logoCandidates = [
    'assets/images/branding/gep-logo.png',
];
foreach ($logoCandidates as $candidate) {
    if (is_file(__DIR__ . '/../' . $candidate)) {
        $logoPath = $candidate;
        break;
    }
}

$navItems = [
    'inicio' => ['index.php', 'Inicio'],
    'quienes-somos' => ['quienes-somos.php', 'Quiénes somos'],
    'docencia' => ['docencia.php', 'Docencia'],
    'proyectos' => ['proyectos.php', 'Proyectos'],
    'servicios' => ['servicios.php', 'Servicios'],
    'publicaciones' => ['publicaciones.php', 'Publicaciones'],
    'comunicaciones' => ['comunicaciones.php', 'Comunicaciones'],
    'noticias' => ['noticias.php', 'Noticias'],
    'contactanos' => ['contactanos.php', 'Contacto'],
];
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8"/>
<?php if (!empty($pageBaseHref)): // v9.23.0: página 404 servida en cualquier dirección ?>
    <base href="<?php echo gep_escape((string) $pageBaseHref); ?>"/>
<?php endif; ?>
    <meta name="viewport" content="width=device-width, initial-scale=1"/>
    <meta name="description" content="<?= gep_escape($pageDescription) ?>"/>
    <meta name="theme-color" content="#173c2a"/>
    <link rel="icon" type="image/png" sizes="32x32" href="assets/images/branding/favicon-32.png"/>
    <link rel="icon" type="image/png" sizes="192x192" href="assets/images/branding/favicon-192.png"/>
    <link rel="apple-touch-icon" href="assets/images/branding/apple-touch-icon.png"/>
    <title><?= gep_escape($pageTitle) ?></title>
    <?php gep_site_render_head_meta($pageTitle, $pageDescription, $activePage, $logoPath, isset($pageSchema) && is_array($pageSchema) ? $pageSchema : []); ?>
    <link rel="stylesheet" href="assets/css/style.css?v=<?= (int) @filemtime(__DIR__ . '/../assets/css/style.css') ?>"/>
    <?php foreach ($pageStyles as $pageStyle): if (!preg_match('#^assets/css/[a-z0-9_-]+\.css$#', (string) $pageStyle) || !is_file(__DIR__ . '/../' . $pageStyle)) continue; ?>
    <link rel="stylesheet" href="<?= gep_escape($pageStyle) ?>?v=<?= (int) @filemtime(__DIR__ . '/../' . $pageStyle) ?>"/>
    <?php endforeach; ?>
</head>
<body class="<?= gep_escape($bodyClass) ?>">
<header class="site-header" id="siteHeader">
    <div class="container nav-wrap">


        <a class="brand gep-header-brand" href="index.php" aria-label="GEP · Inicio">
            <?php if ($logoPath): ?>
                <img
                    class="site-brand-logo"
                    src="<?= gep_escape($logoPath) ?>"
                    alt="GEP - Laboratorio de Geomática y Ecología del Paisaje"
                >
            <?php else: ?>
                <span class="brand-monogram">GEP</span>
            <?php endif; ?>
        </a>

        <button class="menu-btn" id="menuBtn" aria-controls="navLinks" aria-expanded="false" aria-label="Abrir navegación">☰</button>
        <ul class="nav-links" id="navLinks">
            <?php foreach ($navItems as $key => [$href, $label]): ?>
                <li><a href="<?= gep_escape($href) ?>"<?= $activePage === $key ? ' class="active" aria-current="page"' : '' ?>><?= gep_escape($label) ?></a></li>
            <?php endforeach; ?>
        </ul>
    </div>
</header>
