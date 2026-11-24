# Instrucciones para asistentes de IA (Cursor, Copilot, Claude y otros)

Lee esto antes de proponer o hacer cambios en este repositorio.

## Contexto

- Sitio del Laboratorio GEP (Universidad de Chile): https://gep.uchile.cl/gep/
- `sitio-publico/`: sitio PHP propio que **solo lee** contenido de WordPress. Se sirve en `/gep/`.
- `motor/`: archivos propios dentro de WordPress, instalado en `/gep/motor/`. El núcleo de WordPress no está en el repositorio.
- Plugin principal: `motor/wp-content/plugins/gep-editor-demo/` (GEP Editor 10.0.0, archivo `gep-editor-demo.php`, constante `GEP_EDITOR_VERSION`).
- Seguridad: `motor/wp-content/mu-plugins/gep-acceso.php` + `gep-acceso/`, `motor/gep-config-migracion.php`.
- Manual técnico completo: `documentacion/02-manual-tecnico.md`. Léelo antes de cambios grandes.
- Quienes usan el editor no son técnicos. Textos de la interfaz y documentación en **español de Chile**, claros y cortos.

## Reglas obligatorias

1. **No rediseñar** el sitio público ni el editor. Cambios solo por errores, responsive, rendimiento o funciones pedidas.
2. **No reconstruir ni agregar frameworks**: nada de Elementor, ACF, React, Vue, Tailwind, Bootstrap, Webflow ni temas nuevos. jQuery y las APIs de WordPress que ya se usan.
3. **Seguridad en todo cambio PHP**: `current_user_can()` + nonce (`check_admin_referer` / `check_ajax_referer`) en cada acción que escribe; sanear la entrada (`sanitize_*`, `absint`, `esc_url_raw`); escapar la salida (`esc_html`, `esc_attr`, `esc_url`); `$wpdb->prepare` en SQL.
4. **Nunca** escribir contraseñas, claves, usuarios reales, `wp-config.php`, volcados `.sql` ni respaldos en el repositorio (es público).
5. **Compatibilidad del contenido**: no cambiar los nombres de metadatos `gep_*`, la carpeta del plugin, la carpeta `motor` ni los slugs de las páginas. Si cambias el formato de un dato, el código debe seguir leyendo el formato anterior.
6. **Cambios pequeños y localizados.** No reemplaces archivos completos si basta una edición. Antes de borrar una función o archivo, busca todas sus referencias.
7. **Assets**: CSS/JS se versionan con `gep_demo_asset_version()` (versión + fecha del archivo). No uses CDN: todo local.
8. **El servidor** es nginx sin `.htaccess`, detrás de un proxy HTTPS, sin SSH. No propongas cambios de configuración del servidor como solución; todo debe funcionar subiendo archivos. Los enlaces internos a carpetas terminan en `/`.
9. Cada versión: subir el número en el encabezado del plugin y en `GEP_EDITOR_VERSION`, y escribir la entrada en `CHANGELOG.md`.

## Cómo comprobar un cambio

1. `php -l` en cada archivo PHP modificado.
2. En un WordPress local con una copia de la base: abrir las 9 pestañas de Contenido GEP, guardar cada una sin cambios y confirmar que los metadatos `gep_*` no cambian.
3. Abrir las páginas públicas y revisar el registro de errores de PHP.
4. Revisar Contenido GEP → Diagnóstico.
