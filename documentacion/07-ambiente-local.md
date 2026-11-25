# GEP 10.0 · Ambiente local (copia para probar)

Para probar un cambio o un respaldo sin tocar el servidor. Necesitas un computador con PHP 8.1 o superior y MariaDB/MySQL. Sirven [LocalWP](https://localwp.com), XAMPP o similar. Ningún paso usa contraseñas del servidor real.

## 1. Estructura de carpetas

Copia el repositorio para que quede igual que en el servidor:

```
<raíz web local>/gep/                  ← contenido de sitio-publico/
<raíz web local>/gep/motor/            ← WordPress 7.x descargado de wordpress.org
<raíz web local>/gep/motor/gep-config-migracion.php          ← de motor/
<raíz web local>/gep/motor/wp-content/plugins/…, mu-plugins/… ← de motor/wp-content/
```

La carpeta de WordPress **debe llamarse `motor`**: con otro nombre, `gep-config-migracion.php` responde «Esta dirección ya no está en uso» a propósito. El sitio público busca WordPress en `motor/` (o en la carpeta superior). Para otra ubicación define `GEP_CMS_RUTA` con la ruta absoluta.

## 2. Base de datos

1. Crea una base vacía local.
2. Importa el último `.sql` exportado del servidor (ver [respaldo](04-respaldo-y-recuperacion.md)). Las tablas usan el prefijo `wp_lep`.
3. Si no tienes un `.sql`, instala WordPress vacío (ver punto 3: define `GEP_PERMITIR_INSTALADOR`) y luego restaura un **Respaldo completo** `.json` desde Contenido GEP → Historial de cambios.

## 3. wp-config.php local

Copia `wp-config-sample.php` a `wp-config.php` en `motor/`, pon los datos de **tu** base local y `$table_prefix = 'wp_lep';`. Antes de la línea `require_once ABSPATH . 'wp-settings.php';` agrega:

```php
define( 'WP_HOME', 'http://localhost/gep/motor' );     // tu dirección local
define( 'WP_SITEURL', 'http://localhost/gep/motor' );
define( 'GEP_PROXY_HTTPS', false );                    // en local no hay proxy HTTPS
define( 'GEP_EDITOR_PUBLIC_BASE_URL', 'http://localhost/gep' );
define( 'GEP_SITIO_PRIVADO_APAGADO', true );           // sin ventana de clave en local
// define( 'GEP_PERMITIR_INSTALADOR', true );          // solo si instalas WordPress vacío
require_once __DIR__ . '/gep-config-migracion.php';
```

Las definiciones de arriba ganan sobre las de `gep-config-migracion.php`, que trae las del servidor real.

## 4. Entrar y probar

1. Abre `http://localhost/gep/motor/wp-admin/`. Con un `.sql` del servidor, entra con tu cuenta de siempre; si no recuerdas la clave, cámbiala desde la interfaz de la base local (en local no llegan correos).
2. Si gep-acceso pide segundo factor y no tienes el plugin Two Factor en local, instálalo o define `GEP_ACCESO_APAGADO` true **solo en local**.
3. Sigue «Cómo probar un cambio» del [manual técnico](02-manual-tecnico.md): `php -l`, guardar las 9 pestañas sin cambios, abrir las 10 páginas públicas y revisar Diagnóstico.
4. Las fotos no vienen en la base: copia `motor/wp-content/uploads/` del servidor si las necesitas ver.

## 5. Probar una restauración (una vez al año, o tras cambiar el servidor)

1. Restaura el último `.sql` y luego el último Respaldo completo `.json`.
2. Abre las 10 páginas públicas y compáralas con el sitio real.
3. Anota la fecha de la prueba en el registro del laboratorio.

Nota: esta guía reemplaza a la antigua guía de LocalWP de octubre de 2026 (estructura `GEP_LEP/`, ya no vigente).
