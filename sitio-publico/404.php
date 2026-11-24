<?php
/**
 * GEP · Página no encontrada (v9.23.0)
 * El servidor la muestra cuando alguien abre una dirección que no existe dentro
 * del sitio (ver .htaccess). Devuelve el código 404 para que Google no la guarde.
 */
require_once __DIR__ . '/includes/wp-bootstrap.php';
gep_site_bootstrap_wordpress();
http_response_code(404);
if (!function_exists('gep_404_h')) {
    function gep_404_h($v) { return htmlspecialchars((string) $v, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
}
// Las rutas del sitio (estilos, imágenes, menú) se resuelven desde la carpeta del
// sitio aunque la dirección pedida tenga subcarpetas inventadas.
$gep404_https = (!empty($_SERVER['HTTPS']) && 'off' !== strtolower((string) $_SERVER['HTTPS'])) || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && 'https' === strtolower((string) $_SERVER['HTTP_X_FORWARDED_PROTO']));
$gep404_host  = isset($_SERVER['HTTP_HOST']) ? preg_replace('/[^A-Za-z0-9.:\-]/', '', (string) $_SERVER['HTTP_HOST']) : '';
$gep404_dir   = '/' . trim(str_replace('\\', '/', dirname((string) ($_SERVER['SCRIPT_NAME'] ?? '/404.php'))), '/');
$pageBaseHref = ($gep404_host !== '' ? ($gep404_https ? 'https' : 'http') . '://' . $gep404_host : '') . rtrim($gep404_dir, '/') . '/';

$pageTitle = 'GEP · Página no encontrada';
$pageDescription = 'La página que buscas no existe o cambió de dirección.';
$bodyClass = 'page-404';
$activePage = '';
$isHome = false;
require __DIR__ . '/includes/header.php';
?>
<main>
<section class="page-hero hero-404">
  <div class="container page-hero-content">
    <p class="eyebrow">Error 404</p>
    <h1>Esta página no existe</h1>
    <p>Puede que el enlace esté mal escrito o que la página haya cambiado de dirección.</p>
  </div>
</section>
<section class="section">
  <div class="container gep-404-body">
    <p>Prueba con una de estas páginas:</p>
    <div class="gep-404-links">
      <a class="btn btn-green" href="index.php">Ir al inicio</a>
      <a class="btn btn-outline" href="proyectos.php">Proyectos</a>
      <a class="btn btn-outline" href="publicaciones.php">Publicaciones</a>
      <a class="btn btn-outline" href="contactanos.php">Contacto</a>
    </div>
  </div>
</section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
