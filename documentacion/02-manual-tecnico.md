# GEP 10.0 · Manual técnico

Para quien mantenga o desarrolle el sitio en el futuro. Describe la arquitectura, los archivos, los datos y las reglas del proyecto. Estado a 25-11-2026: GEP Editor 10.0.2, gep-acceso 10.0.0, configuración 10.0.0, sitio público 9.29.0, GEP Media Organizer 1.6.4, WordPress 7.1, PHP 8.4, MariaDB 10.6.

## 1. Arquitectura

```
Visitante ──HTTPS──> proxy de la Universidad ──HTTP :8000──> nginx 1.18 + PHP-FPM 8.4
                                                             │
           /gep/              sitio público (PHP propio, solo lee)
           /gep/motor/        WordPress (CMS): editor, Mediateca, usuarios
           base de datos      MariaDB, prefijo de tablas wp_lep
```

- **Sitio público** (`/gep/`): páginas PHP propias (`index.php`, `proyectos.php`…) con diseño fijo. Cada página carga WordPress con `includes/wp-bootstrap.php` y lee el contenido de la base. Nunca escribe. Direcciones limpias: `/gep/proyectos/` es una carpeta con `index.php` que incluye `../proyectos.php` (9.29.0); `proyectos.php` redirige 301 a la carpeta.
- **CMS** (`/gep/motor/`): WordPress normal. El tema Kadence no se usa para el sitio: gep-acceso (etapa C) redirige el WordPress público a `/gep/`.
- **GEP Editor** (plugin `gep-editor-demo`): pantallas «Contenido GEP» para editar las 9 páginas. Guarda todo como metadatos `gep_*` de cada página.
- **gep-acceso** (mu-plugin): seguridad de inicio de sesión, segundo factor, cierre de superficie, panel Seguridad GEP.
- **GEP Media Organizer** (plugin): carpetas virtuales en la Mediateca (taxonomía `gep_media_folder`).
- **gep-config-migracion.php**: ajustes del CMS cargados desde `wp-config.php` (dirección, HTTPS tras proxy, instalador bloqueado, carpeta obligatoria «motor»).

Separación: el editor y el sitio comparten solo la base de datos. Si WordPress no está, el sitio responde 503 «en mantenimiento» sin rutas (`includes/cms-ubicacion.php`).

Limitaciones del servidor (decididas, no cambiar sin necesidad): no hay acceso a la configuración de nginx ni SSH; solo FileZilla y la interfaz web de la base. nginx ignora `.htaccess`. Una carpeta escrita sin barra final redirige a `http://gep.uchile.cl:8000/…` (no se puede corregir con archivos), por eso los enlaces internos siempre terminan en `/`. Instalar plugins por la pantalla de WordPress pide credenciales FTP: se suben por FileZilla.

## 2. Estructura de archivos

```
/gep/                                   sitio público
├── index.php, quienes-somos.php, docencia.php, proyectos.php, proyecto.php,
│   servicios.php, publicaciones.php, comunicaciones.php, noticias.php,
│   contactanos.php, 404.php
├── proyectos/index.php …               carpetas de direcciones limpias (una por página)
├── assets/css, assets/js, assets/…     estilos, scripts e imágenes fijas del diseño
├── includes/
│   ├── wp-bootstrap.php                carga WordPress (solo desde «motor»)
│   ├── cms-ubicacion.php               dónde está el CMS; 503 si falta
│   ├── direcciones-limpias.php         301 desde .php y enlaces limpios en la salida
│   ├── media-urls.php                  corrige dominios antiguos y fotos livianas
│   ├── header.php, footer.php, hero-media.php, image-frame.php,
│   │   landscape-frame.php, image-alt.php, structured-data.php,
│   │   content-hub-public.php, public-notes.php
└── motor/                              WordPress
    ├── wp-config.php                   credenciales (NO va al repositorio)
    ├── gep-config-migracion.php        ajustes GEP del CMS
    └── wp-content/
        ├── mu-plugins/gep-acceso.php + gep-acceso/   seguridad
        ├── mu-plugins/gep-seguridad.php              XML-RPC y lista de usuarios
        ├── plugins/gep-editor-demo/                  GEP Editor
        ├── plugins/gep-media-organizer/              carpetas de la Mediateca
        └── uploads/                                  fotos, videos, documentos
```

### GEP Editor (`plugins/gep-editor-demo/`)

| Archivo | Qué hace |
| --- | --- |
| `gep-editor-demo.php` | Arranque, menú Contenido GEP, pantallas de cada página, guardado (`admin_post_gep_demo_save_content`), carga de assets (versión = `GEP_EDITOR_VERSION` + fecha del archivo). |
| `includes/sections.php` | Apartados personalizados y estructura de cada página (orden, visibles). |
| `includes/content-hubs.php`, `services.php` | Comunicaciones/Noticias y Servicios. |
| `includes/history.php` | Historial: versiones (`gep_change`), comparar, restaurar, proteger, exportar. |
| `includes/backup.php` | Respaldo completo: descargar y restaurar JSON. |
| `includes/edit-lock.php` + `assets/edit-lock.js` | Aviso de edición simultánea y guardado seguro (10.0). |
| `includes/roles.php` | Rol «Editor GEP». |
| `includes/help.php` + `assets/help.js` | Ayuda, tutoriales, recorrido. |
| `includes/cms-location.php`, `domain-urls.php` | Reglas para direcciones de ubicaciones anteriores del CMS. |
| `includes/doi-import.php` | Importar publicaciones por DOI (Crossref). |
| `includes/image-alt.php`, `image-frame.php`, `video-poster.php`, `button-editor.php`, `structured-data.php`, `theme.php`, `ux.php` | Descripción de imágenes, encuadre, imagen de respaldo de video, botones, JSON-LD, modo oscuro, barra fija. |
| `includes/archivos-en-uso.php` | «Se usa en» y bloqueo de borrado (10.0). |
| `includes/sitio-privado.php` | Interruptor Acceso al sitio (10.0). |
| `includes/diagnostico.php` | Pantalla Diagnóstico (10.0). |
| `includes/registro-tecnico.php` | Registro técnico de errores (10.0). |
| `includes/defaults-v920.php` | Contenido por defecto cuando una página aún no tiene datos. |
| `assets/admin.js`, `sections.js`, `ux.js`, `editor.css`, `ux.css`, `dark.css` | Interfaz del editor. |

## 3. Base de datos

Solo tablas estándar de WordPress (prefijo `wp_lep`). GEP no crea tablas propias.

| Dónde | Clave | Contenido |
| --- | --- | --- |
| `wp_leppostmeta` de cada página | `gep_*` | Todo el contenido editable. Textos simples o JSON (`gep_*_json`: listas de tarjetas, personas, proyectos, publicaciones, estructura `gep_page_structure_*_json`, apartados `gep_custom_sections_*_json`, encuadres `gep_image_frames_json`). |
| `wp_leppostmeta` de cada página | `_gep_save_rev`, `_gep_editing_now` | Control de edición simultánea. |
| `wp_leppostmeta` | `_gep_projects_backup_v926` | Copia única de proyectos antes de la unificación 9.26 (no borrar). |
| `wp_lepposts` tipo `gep_change` | + metas `_gep_history_*` | Historial de cambios (instantánea, usuario, fecha, huella). Límite por página: `GEP_DEMO_HISTORY_LIMIT_PER_PAGE`. |
| `wp_lepposts` tipo `attachment` | | Mediateca. Carpeta: taxonomía `gep_media_folder`. |
| `wp_lepoptions` | `gep_acceso_ajustes`, `gep_acceso_contadores`, `gep_acceso_registro*` | Seguridad. |
| `wp_lepoptions` | `gep_sitio_privado` | Interruptor del sitio privado (usuario + clave cifrada). |
| `wp_lepoptions` | `gep_registro_tecnico`, `gep_ultimo_respaldo` | Registro técnico y fecha del último respaldo. |
| `wp_lepoptions` | `gep_help_support_email`, `gep_known_hosts`, `gep_demo_role_rev`, `gep_media_initialized` | Correo de soporte, dominios conocidos, versión del rol, Media Organizer. |
| `wp_lepusermeta` | `gep_editor_theme`, `gep_help_tour_done` | Preferencias por persona. |

Páginas: se reconocen por su nombre/slug (inicio, quienes-somos, docencia, proyectos, servicios, publicaciones, comunicaciones, noticias, contacto) en `gep_demo_get_page_navigation()`. No cambies esos slugs.

## 4. Seguridad (resumen)

- Toda acción de escritura usa nonce + permiso (`edit_pages` para editar, `manage_options` para Diagnóstico y Acceso al sitio).
- Datos de entrada saneados; salida escapada con `esc_html`/`esc_attr`/`esc_url`.
- Instalador bloqueado (`GEP_INSTALADOR_BLOQUEADO`), editor de archivos desactivado (`DISALLOW_FILE_EDIT`), WordPress solo arranca desde «motor».
- gep-acceso: límite de intentos por cuenta, mensajes genéricos, segundo factor (plugin Two Factor), contraseñas de aplicación cerradas, XML-RPC cerrado, sin enumeración de usuarios. Panel: Herramientas → Seguridad GEP.
- Ninguna credencial en el código. `wp-config.php` nunca va al repositorio.

## 5. Constantes útiles (en `wp-config.php`, antes de cargar `gep-config-migracion.php`)

| Constante | Efecto |
| --- | --- |
| `GEP_PERMITIR_INSTALADOR` true | Abre el instalador (solo para una reinstalación planificada). |
| `GEP_PROXY_HTTPS` | true en el servidor de la Universidad. |
| `GEP_SITIO_PRIVADO_APAGADO` true | Deja el sitio público aunque el interruptor esté encendido. |
| `GEP_PRIVADO_MAX_FALLOS` / `GEP_PRIVADO_MINUTOS_PAUSA` | Acceso al sitio: claves equivocadas seguidas antes de pausar (30) y minutos de pausa (10). |
| `GEP_PROTEGER_ARCHIVOS` false | Permite borrar archivos en uso. |
| `GEP_ACCESO_APAGADO` true | Apaga gep-acceso (emergencia). |
| `GEP_EDITOR_DISABLE_STICKY` true | Barra y vista previa sin fijar. |
| `GEP_EDITOR_PUBLIC_BASE_URL` | Dirección del sitio público si cambia la estructura. |
| `GEP_SITE_OLD_HOSTS` | Dominios antiguos extra a corregir. |
| `GEP_DIRECCIONES_LIMPIAS` false | Vuelve a enlaces `.php`. |

## 6. Reglas del proyecto (decisiones tomadas)

- El diseño del sitio público está aceptado: solo se cambia por errores, adaptación a pantallas, rendimiento o funciones nuevas.
- No usar Elementor, ACF, React, Webflow ni reconstruir. Usar APIs de WordPress.
- Editor: lista primero, edición de un elemento a la vez; «Quiénes somos» es el modelo de todas las pestañas; mantener el estilo de `assets/editor.css`.
- Versionar CSS/JS con la fecha del archivo (`gep_demo_asset_version()`).
- Cambios pequeños y reversibles. Antes de borrar algo, comprobar quién lo usa.

## 7. Cómo probar un cambio

1. Ambiente local: ver [07 · Ambiente local](07-ambiente-local.md).
2. `php -l` en cada archivo PHP modificado.
3. Abrir las 9 pestañas, guardar cada una sin cambios y comparar los metadatos `gep_*` antes/después: deben quedar iguales.
4. Abrir las 10 páginas públicas y revisar que no haya errores en el registro de PHP.
5. Revisar **Diagnóstico**: sin puntos rojos.
6. Anotar el cambio en `CHANGELOG.md` y subir la versión en el encabezado y en `GEP_EDITOR_VERSION`.
