# GEP 10.0 · Instalación y actualización

Todo se hace con **FileZilla** conectado al servidor. Nunca uses «Plugins → Añadir nuevo → Subir plugin» de WordPress: en este servidor pide credenciales FTP y falla.

Tiempo: 15 minutos. El sitio sigue funcionando durante la actualización.

## A. Actualizar de 9.29.1 a 10.0.0 (paso a paso)

**Antes de empezar**

1. Entra al editor y descarga un respaldo: Contenido GEP → Historial de cambios → Respaldo completo → **Descargar**. Guárdalo en tu computador.
2. En FileZilla, descarga a tu computador (copia de seguridad de archivos) estas tres cosas del servidor:
   - `/gep/motor/gep-config-migracion.php`
   - `/gep/motor/wp-content/plugins/gep-editor-demo/` (carpeta completa)
   - `/gep/motor/wp-content/mu-plugins/gep-acceso.php` y la carpeta `gep-acceso/`
   (El paquete trae también una carpeta `REVERSION` con las versiones anteriores.)

**Subir los archivos** (del paquete, carpeta `1-subir-a-gep-motor/`, a `/gep/motor/` del servidor, sobrescribiendo):

3. `wp-content/plugins/gep-editor-demo/` → arrástrala sobre la carpeta del mismo nombre del servidor. FileZilla pregunta si sobrescribir: **Sobrescribir**, marcando «Usar siempre esta acción».
4. `wp-content/mu-plugins/gep-acceso.php` y `wp-content/mu-plugins/gep-acceso/` → igual.
5. `gep-config-migracion.php` → a `/gep/motor/`.
   - Si tu copia del servidor tenía algún cambio hecho a mano (por ejemplo otra dirección), ábrelas las dos y copia ese cambio en la nueva antes de subirla. La versión 10.0.0 viene con `GEP_PROXY_HTTPS` en true, igual que la 9.27.1.

**Comprobar** (5 minutos)

6. Recarga https://gep.uchile.cl/gep/motor/wp-admin/ → Plugins: debe decir **GEP Editor 10.0.0** activo (ya no «Beta»).
7. Contenido GEP → **Diagnóstico**: revisa que diga «Instalador de WordPress bloqueado» y «Contenido íntegro» en verde.
8. Abre https://gep.uchile.cl/gep/motor/wp-admin/install.php → debe decir «Sitio en mantenimiento».
9. Abre una página del editor, cambia una letra, guarda, y deshaz el cambio. Mira el sitio público en una ventana de incógnito.
10. Medios → abre una foto que use Inicio → debe decir **Se usa en: Inicio**.

**Limpieza pendiente del servidor** (Diagnóstico y Herramientas → Seguridad GEP → Revisión los muestran):

11. Borra con FileZilla: el archivo `gep-diagnostico-*.php`, la carpeta `/gep/gep-prueba-htaccess/`, las herramientas `gep-quitar-clave-*.php` y `gep-prueba-direcciones-*.php` si quedaron, y, si existe, `/gep/includes/gep-access.gepqc-off.php` (copia apagada del guardia anterior; el interruptor «Acceso al sitio» lo reemplaza). No borres `gep-access.php`: algunas páginas podrían seguir incluyéndolo.
12. Clic derecho sobre `/gep/motor/wp-config.php` → Permisos de archivo → **644** (o 640 si el sitio sigue funcionando).

## B. Volver atrás (si algo falla)

Sube los archivos que descargaste en el paso 2 (o los de la carpeta `REVERSION` del paquete) en los mismos lugares, sobrescribiendo. No hay que tocar la base de datos: 10.0.0 no cambia la forma de guardar el contenido. Las opciones nuevas (`gep_sitio_privado`, `gep_registro_tecnico`, `gep_ultimo_respaldo`) quedan sin uso y no molestan.

Si la pantalla queda en blanco después de subir el plugin: en FileZilla renombra la carpeta `gep-editor-demo` a `gep-editor-demo-off`, entra a WordPress, vuelve a ponerle el nombre y sube la versión anterior.

## C. Actualizar WordPress (núcleo)

1. Descarga el respaldo completo (paso A1) y exporta la base de datos (ver Manual de respaldo).
2. Escritorio → Actualizaciones → **Actualizar ahora**. Si pide FTP, descarga WordPress en español desde es.wordpress.org, descomprímelo y sube por FileZilla **todo menos** `wp-config.php` y la carpeta `wp-content/` a `/gep/motor/`.
3. Después de actualizar: borra otra vez `readme.html` y `license.txt` si quieres ocultar la versión (opcional), y abre **Diagnóstico**.

GEP solo usa funciones públicas de WordPress; la versión 10.0.0 se probó con WordPress 7.1.4. Una versión mayor nueva (8.x) se prueba primero en el ambiente local.

## D. Instalación desde cero (servidor nuevo)

1. Crea la base de datos y un usuario con contraseña (MariaDB 10.6+ o MySQL 8+).
2. Sube WordPress a `<sitio>/motor/` y el sitio público (carpeta `sitio-publico/` del repositorio) a `<sitio>/`.
3. Crea `motor/wp-config.php` desde `wp-config-sample.php` con los datos de la base, prefijo `wp_lep`, y agrega **antes** de la línea `require_once ABSPATH . 'wp-settings.php';`:
   ```php
   require_once __DIR__ . '/gep-config-migracion.php';
   ```
   Para instalar por primera vez, agrega también (y quítalo al terminar): `define( 'GEP_PERMITIR_INSTALADOR', true );`
4. Ajusta en `gep-config-migracion.php` la dirección (`WP_HOME`, `WP_SITEURL`) y `GEP_PROXY_HTTPS`.
5. Importa la base de datos de respaldo (Manual de respaldo, sección C) o instala WordPress vacío y luego restaura el **Respaldo completo** del editor.
6. Sube los plugins `gep-editor-demo`, `gep-media-organizer` y los mu-plugins `gep-acceso.php`, `gep-acceso/`, `gep-seguridad.php`. Activa los dos plugins en Plugins.
7. Ajustes → Enlaces permanentes → **Simple**.
8. Abre **Diagnóstico** y corrige lo que salga en rojo.

## E. Publicar una versión nueva del editor (para desarrolladores)

1. Sube el número en el encabezado de `gep-editor-demo.php` y en `GEP_EDITOR_VERSION`.
2. Escribe la entrada en `CHANGELOG.md`.
3. Prueba según el Manual técnico, sección 7.
4. Empaqueta solo los archivos cambiados con la misma estructura de carpetas, más una carpeta `REVERSION` con los anteriores.
