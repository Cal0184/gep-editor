# GEP 10.0 · Respaldo, restauración y recuperación ante errores

El servidor de la Universidad **no tiene respaldos automáticos que podamos usar** (en octubre de 2026 se perdió la base de datos del servidor anterior y no había copia). Por eso los respaldos se hacen a mano y se guardan **fuera del servidor**.

## A. Qué respaldar y cada cuánto

| Qué | Cómo | Frecuencia | Dónde guardarlo |
| --- | --- | --- | --- |
| Contenido del editor (textos, listas, fotos elegidas, estructura) | Contenido GEP → Historial de cambios → Respaldo completo → **Descargar** | Cada semana y antes de cada actualización | Carpeta compartida del laboratorio (Drive/OneDrive), nombre con fecha |
| Base de datos completa (incluye usuarios, historial, Mediateca) | Interfaz web de la base de datos (dirección y acceso: los entrega el administrador, nunca por escrito en este repositorio) → base del sitio → Exportar → SQL | Cada mes y antes de actualizar WordPress | Igual, en una carpeta aparte |
| Fotos, videos y documentos | FileZilla: descargar `/gep/motor/wp-content/uploads/` | Cada mes (solo lo nuevo: carpetas del año/mes) | Igual |
| Archivos del sitio y plugins | El repositorio de GitHub tiene el código de cada versión | En cada versión | GitHub |

Nunca guardes respaldos dentro de `/gep/`: nginx los entregaría a cualquiera que adivine el nombre. Diagnóstico y Seguridad GEP → Revisión avisan si encuentran archivos `.sql` o `.zip` en el servidor.

Diagnóstico muestra la fecha del último respaldo descargado y avisa en amarillo después de 7 días.

## B. Restaurar contenido (lo más común)

**Un cambio puntual mal hecho** → Contenido GEP → Historial de cambios → elige la versión → **Restaurar versión**. Lo actual queda guardado.

**Todo el contenido** (por ejemplo después de un error grave) → Historial de cambios → Respaldo completo → elige el archivo `.json` → marca «Entiendo que el contenido actual se reemplaza» → **Restaurar respaldo**. Solo reemplaza el contenido de las páginas GEP; no toca usuarios, fotos ni ajustes. El estado anterior queda en el Historial.

## C. Restaurar la base de datos completa

Solo si la base se borró o se dañó (el sitio muestra «Sitio en mantenimiento» y el editor no abre).

1. No intentes «instalar WordPress» de nuevo: el instalador está bloqueado a propósito para que nadie cree un sitio nuevo sobre la base vacía.
2. Interfaz web de la base de datos → base del sitio. Si quedaron tablas `wp_lep…` dañadas, expórtalas primero (por si acaso) y luego elimínalas.
3. Importar → el último archivo `.sql` exportado.
4. Si el respaldo es de otro servidor o dirección, revisa en la tabla `wp_lepoptions` que `home` y `siteurl` digan `https://gep.uchile.cl/gep/motor` (de todas formas `gep-config-migracion.php` las fija).
5. Entra al editor y abre **Diagnóstico**.
6. Si el último `.sql` es antiguo pero tienes un Respaldo completo `.json` más nuevo, restáuralo después (sección B).

## D. Recuperación ante errores frecuentes

| Síntoma | Causa probable | Qué hacer |
| --- | --- | --- |
| Sitio público dice «Sitio en mantenimiento» | WordPress no responde o la base no conecta | Abre `/gep/motor/wp-admin/`. Si dice «Error al establecer conexión con la base de datos»: revisa usuario/clave en `wp-config.php` o que la base exista. Si las tablas no están: sección C. |
| El navegador pide usuario y clave en el sitio | Interruptor «Acceso al sitio» encendido | Administrador: Contenido GEP → Acceso al sitio → desmarcar. Sin acceso: en `wp-config.php` agrega `define( 'GEP_SITIO_PRIVADO_APAGADO', true );` |
| Pantalla blanca o «Ha habido un error crítico» | Error de PHP en un plugin | WordPress envía un correo de recuperación al administrador. Si no llega: FileZilla → renombra la carpeta del plugin culpable (por ejemplo `gep-editor-demo` → `gep-editor-demo-off`), entra y revisa Diagnóstico → Registro técnico. |
| No puedo entrar (cuenta bloqueada) | Muchos intentos fallidos | Espera los minutos que indica o usa «¿Has olvidado tu contraseña?». Emergencia: renombra `mu-plugins/gep-acceso.php` a `gep-acceso.php.off`. |
| Perdí el segundo factor | Teléfono perdido | Otro administrador: Usuarios → la cuenta → quitar el método. Sin otro administrador: renombra la carpeta `plugins/two-factor`. |
| Al guardar aparece «Tu sesión terminó» | Sesión vencida | Sigue el aviso: Iniciar sesión en otra pestaña y «Ya entré, guardar». No recargues. |
| Una foto no aparece en el sitio | Se borró el archivo del servidor | Diagnóstico → «archivos usados que no existen» dice cuál y dónde. Elige la foto de nuevo en esa página. |
| No deja borrar una foto | Está en uso | Medios → abrir el archivo → «Se usa en». Quítala de esa página primero. |
| Las fotos nuevas no se suben | Permisos o espacio | Diagnóstico → Archivos y almacenamiento. Carpeta `uploads` con permisos 755. |
| `/gep` sin barra lleva a una dirección con `:8000` | Configuración de nginx (no se puede cambiar con archivos) | Usa siempre `/gep/` con barra final. Los enlaces del sitio ya la llevan. |

## E. Información para soporte

Diagnóstico → **Copiar informe** y pégalo en el correo. No contiene contraseñas, rutas del servidor ni contenido. Nunca envíes `wp-config.php` ni contraseñas por correo o chat; si se compartieron, cámbialas.
