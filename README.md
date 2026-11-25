# Sitio web del Laboratorio GEP · Código y traspaso

Código del sitio **https://gep.uchile.cl/gep/** (Laboratorio de Geomática y Ecología del Paisaje, Universidad de Chile) y de su editor de contenido **GEP Editor 10.0.2** (versión definitiva 10.0.0 entregada el 24-11-2026, con ajustes 10.0.1 y 10.0.2).

Este repositorio es el **traspaso** del proyecto al equipo GEP: todo lo necesario para seguir usando, manteniendo y mejorando el sitio sin depender de quien lo desarrolló.

---

## 1. Si solo quieres actualizar el contenido

No necesitas este repositorio. Entra a **https://gep.uchile.cl/gep/motor/wp-admin/** → **Contenido GEP**, y lee el [Manual de usuario](documentacion/01-manual-de-usuario.md). Dentro del editor, **Ayuda** tiene tutoriales con buscador y un recorrido guiado.

## 2. Cómo está hecho (en un minuto)

```
Visitante → https://gep.uchile.cl/gep/            sitio público (PHP propio, solo lee)
                         /gep/motor/               WordPress: editor, Mediateca, usuarios
                         base de datos MariaDB     todo el contenido
```

- El **sitio público** (`sitio-publico/`) tiene diseño fijo y lee el contenido desde WordPress.
- El **editor** es un plugin propio de WordPress (`motor/wp-content/plugins/gep-editor-demo/`, menú «Contenido GEP»). Guarda cada página como datos `gep_*` en la base.
- La **seguridad** está en `motor/wp-content/mu-plugins/gep-acceso*` (límite de intentos, segundo factor, cierre del WordPress público) y en `motor/gep-config-migracion.php` (dirección, HTTPS, instalador bloqueado).
- **GEP Media Organizer** (`motor/wp-content/plugins/gep-media-organizer/`) agrega carpetas a la Mediateca.

Detalle completo: [Manual técnico](documentacion/02-manual-tecnico.md).

## 3. Contenido del repositorio

| Carpeta | Va en el servidor en | Qué es |
| --- | --- | --- |
| `sitio-publico/` | `/gep/` | Sitio público 9.29.0 |
| `motor/gep-config-migracion.php` | `/gep/motor/` | Ajustes GEP de WordPress (se carga desde `wp-config.php`) |
| `motor/wp-content/plugins/gep-editor-demo/` | `/gep/motor/wp-content/plugins/` | GEP Editor 10.0.2 (el nombre de la carpeta es histórico: no cambiarlo) |
| `motor/wp-content/plugins/gep-media-organizer/` | igual | Carpetas de la Mediateca 1.6.4 |
| `motor/wp-content/mu-plugins/` | `/gep/motor/wp-content/mu-plugins/` | gep-acceso 10.0.0 y gep-seguridad 1.0.0 |
| `documentacion/` | **no se sube** | Manuales |

**No están aquí, a propósito:** WordPress (se descarga de wordpress.org), `wp-config.php` (tiene la contraseña de la base de datos), la base de datos, las fotos (`uploads/`) y los respaldos. Este repositorio es público: **nunca agregues contraseñas, claves, `wp-config.php`, archivos `.sql` ni respaldos**.

## 4. Manuales

| Documento | Para quién |
| --- | --- |
| [01 · Manual de usuario](documentacion/01-manual-de-usuario.md) | Quien edita el contenido |
| [02 · Manual técnico](documentacion/02-manual-tecnico.md) | Quien mantiene o programa: arquitectura, archivos, base de datos, constantes, cómo probar |
| [03 · Instalación y actualización](documentacion/03-instalacion-y-actualizacion.md) | Actualizar el editor o WordPress, volver atrás, instalar en un servidor nuevo |
| [04 · Respaldo y recuperación](documentacion/04-respaldo-y-recuperacion.md) | Qué respaldar, cómo restaurar, qué hacer ante cada error |
| [05 · Requisitos y dependencias](documentacion/05-requisitos-y-dependencias.md) | Servidor, versiones, servicios externos, roles |
| [06 · Registro de cambios 10.0.0](documentacion/06-registro-de-cambios-10.0.0.md) | Qué cambió en la versión definitiva y cómo se validó |
| [07 · Ambiente local](documentacion/07-ambiente-local.md) | Copia del sitio en un computador para probar cambios sin tocar el servidor |
| [CHANGELOG del editor](motor/wp-content/plugins/gep-editor-demo/CHANGELOG.md) | Historia completa versión por versión |

## 5. Lo que el equipo GEP debe hacer (traspaso)

**Una sola vez, ahora:**

- [ ] Que al menos **dos personas** del laboratorio tengan cuenta de **Administrador** en WordPress, con segundo factor (plugin Two Factor). El resto, perfil **Editor GEP**.
- [ ] Recibir en privado (no por correo ni en este repositorio): acceso FTP/FileZilla al servidor, acceso a la interfaz web de la base de datos y la cuenta de administrador.
- [ ] **Cambiar las contraseñas** que se compartieron durante el desarrollo: base de datos (y actualizarla en `wp-config.php`), cuentas de administrador y la clave de **Acceso al sitio** si se usa.
- [ ] En **Contenido GEP → Ayuda**, escribir el correo de soporte del laboratorio.
- [ ] Abrir **Contenido GEP → Diagnóstico** y dejar todo en verde (siga las indicaciones en pantalla). Borrar los archivos que señale **Herramientas → Seguridad GEP → Revisión**.
- [ ] Decidir si el sitio queda **público** o **con usuario y clave**: **Contenido GEP → Acceso al sitio**.

**Siempre:**

- [ ] **Cada semana**: Historial de cambios → Respaldo completo → Descargar, y guardarlo en la carpeta compartida del laboratorio (fuera del servidor).
- [ ] **Cada mes**: exportar la base de datos y descargar las fotos nuevas (ver [manual de respaldo](documentacion/04-respaldo-y-recuperacion.md)).
- [ ] **Antes de actualizar WordPress**: respaldo completo + base de datos, y después revisar Diagnóstico.
- [ ] **Cada mes**: actualizar WordPress, el plugin Two Factor y el tema Kadence (el servidor no se actualiza solo porque pide FTP; ver [instalación y actualización](documentacion/03-instalacion-y-actualizacion.md)). Después, revisar Diagnóstico.
- [ ] **Una vez**: probar que un respaldo se puede restaurar en el [ambiente local](documentacion/07-ambiente-local.md). Un respaldo que nunca se probó no es garantía.

## 6. Reglas para quien siga desarrollando

Estas decisiones se tomaron durante el proyecto. Respetarlas evita romper lo que funciona:

1. **El diseño del sitio público está aprobado.** Solo se cambia por errores, adaptación a pantallas, rendimiento o funciones nuevas.
2. **No reconstruir.** Nada de Elementor, ACF, React, Webflow ni temas nuevos. Usar las funciones de WordPress, nonces, permisos, saneamiento y escape.
3. **Editor:** lista primero y edición de un elemento a la vez. La pestaña «Quiénes somos» es el modelo para todas. Mantener el estilo visual de `assets/editor.css`.
4. **Cambios pequeños y reversibles.** Antes de borrar algo, comprobar quién lo usa. Cada versión con su entrada en `CHANGELOG.md` y número en `GEP_EDITOR_VERSION`.
5. **El servidor no se puede configurar** (nginx sin `.htaccess`, sin SSH): todo se resuelve con archivos subidos por FileZilla. Los plugins se instalan por FileZilla, no por la pantalla de WordPress.
6. **No cambiar** el nombre de la carpeta del plugin (`gep-editor-demo`), la carpeta `motor` ni los nombres de las páginas (inicio, quienes-somos, docencia, proyectos, servicios, publicaciones, comunicaciones, noticias, contacto).

Si usas un asistente de IA (Cursor, Copilot, Claude…), pídele que lea primero [`AGENTS.md`](AGENTS.md).

## 7. Créditos

Desarrollo 2026: Cal ([@Cal0184](https://github.com/Cal0184)), para el Laboratorio GEP, con asistencia de Claude (Anthropic).
