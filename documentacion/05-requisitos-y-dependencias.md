# GEP 10.0 · Requisitos del servidor y dependencias

## Servidor (producción actual, verificado el 11-11-2026)

| Componente | Actual | Mínimo para GEP 10.0 |
| --- | --- | --- |
| Servidor web | nginx 1.18 detrás del proxy HTTPS de la Universidad (`X-Forwarded-Proto: https`) | nginx o Apache con PHP-FPM |
| PHP | 8.4 | 8.1 (extensiones: mysqli, json, mbstring, gd o imagick, curl, zip) |
| Base de datos | MariaDB 10.6 (prefijo de tablas `wp_lep`) | MariaDB 10.4 o MySQL 8.0 |
| WordPress | 7.1 | 6.5 |
| HTTPS | Lo da el proxy; WordPress lo reconoce con `GEP_PROXY_HTTPS` true | — |
| Acceso | FileZilla (FTP/SFTP) y la interfaz web de la base | Igual; no se necesita SSH |

No se necesita: cambiar la configuración de nginx, `.htaccess`, cron del sistema, Node, Composer ni servicios externos de pago.

## Componentes GEP

| Componente | Versión | Ubicación | Depende de |
| --- | --- | --- | --- |
| GEP Editor | 10.0.0 | `motor/wp-content/plugins/gep-editor-demo/` | WordPress |
| gep-acceso | 10.0.0 | `motor/wp-content/mu-plugins/` | WordPress; plugin Two Factor para el segundo factor |
| gep-seguridad | 1.0.0 | `motor/wp-content/mu-plugins/gep-seguridad.php` | WordPress |
| Configuración GEP | 10.0.0 | `motor/gep-config-migracion.php` | Cargada desde `wp-config.php` |
| GEP Media Organizer | 1.6.4 | `motor/wp-content/plugins/gep-media-organizer/` | WordPress (independiente del editor) |
| Sitio público | 9.29.0 | `/gep/` | WordPress en `/gep/motor/` y GEP Editor (lee sus datos) |

## Dependencias externas

| Qué | Para qué | Si no está |
| --- | --- | --- |
| Plugin **Two Factor** (WordPress.org) | Segundo factor de administradores | El inicio de sesión funciona con contraseña; gep-acceso no bloquea a nadie. |
| Tema **Kadence** | WordPress lo exige como tema activo; no se usa en el sitio | Cualquier tema sirve; el sitio no cambia. |
| api.crossref.org y doi.org | Importar publicaciones por DOI | Se agregan a mano; el resto funciona. |
| YouTube / Vimeo (opcional) | Videos de cabecera alojados afuera | Usar MP4 de la Mediateca. |
| Google Fonts / CDN | No se usan: todo el CSS y JS del editor y del sitio está en el servidor | — |

No hay librerías de terceros dentro del plugin (sin jQuery UI externo, sin Bootstrap, sin React): usa jQuery, la Mediateca y el «latido» (Heartbeat) que trae WordPress.

## Navegadores

Editor: Chrome, Edge, Firefox y Safari actuales en PC; funciona en tablet y celular. Sitio público: navegadores actuales en PC y móviles (probado en iPhone/Safari).

## Cuentas y roles

| Perfil | Puede |
| --- | --- |
| Administrador | Todo, incluido Diagnóstico, Acceso al sitio, Seguridad GEP, plugins y usuarios. Recomendado: 2 personas, con segundo factor. |
| Editor GEP | Editar las 9 páginas, subir y ordenar archivos, Historial y respaldo. No ve plugins, usuarios ni ajustes. |
