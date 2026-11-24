# GEP 10.0.0 · Registro de cambios de la versión definitiva

Fecha: 24-11-2026. Base: GEP Editor 9.29.1, gep-acceso 9.27.1, configuración 9.27.1, sitio público 9.29.0, Media Organizer 1.6.4.

| Componente | Antes | Ahora | Cambio |
| --- | --- | --- | --- |
| GEP Editor | 9.29.1 «Beta» | 10.0.0 | Guardado seguro, archivos en uso, Acceso al sitio, Diagnóstico, registro técnico, recorrido y tutoriales nuevos, limpieza. |
| Configuración (`gep-config-migracion.php`) | 9.27.1 | 10.0.0 | Instalador de WordPress bloqueado. |
| gep-acceso | 9.27.1 | 10.0.0 | Revisión reconoce herramientas temporales 9.28/9.29 y notas beta; textos sin depender de terceros. |
| Sitio público | 9.29.0 | 9.29.0 | Sin cambios. |
| GEP Media Organizer | 1.6.4 | 1.6.4 | Sin cambios. |

## Nuevo

- **Guardado seguro.** Antes, si la sesión había vencido o se cortaba Internet, al guardar se perdía todo lo escrito («El enlace ha caducado»). Ahora el editor comprueba antes de enviar; si algo falla, avisa y no envía nada. Después de volver a entrar, «Ya entré, guardar» guarda sin perder nada. Las claves de seguridad se renuevan solas si la pestaña llevaba más de un día abierta.
- **Archivos en uso.** Medios muestra «Se usa en: …» y no deja borrar un archivo que una página usa (cuadrícula, lista y pantalla del archivo).
- **Acceso al sitio** (administradores). Interruptor para pedir usuario y clave antes de ver el sitio, o dejarlo público. Clave cifrada; reemplaza al guardia manual anterior.
- **Diagnóstico** (administradores). Requisitos, configuración, páginas, contenido, archivos que faltan, direcciones antiguas, espacio, último respaldo y registro técnico, con «Copiar informe».
- **Registro técnico.** Últimos 100 errores del editor sin datos sensibles.
- **Instalador bloqueado.** Si la base falla, nadie puede crear un WordPress nuevo desde `install.php`.
- **Ayuda.** Recorrido de 9 pasos adaptado al editor actual (el anterior tenía 4 y uno apuntaba a pestañas que ya no existen) y 3 tutoriales nuevos.

## Cambiado

- El plugin se llama «GEP Editor» (antes «GEP Editor Beta»). Misma carpeta: sigue activo y con el mismo contenido.
- El respaldo completo anota la fecha de descarga.
- Mensaje claro cuando el servidor rechaza un guardado por clave vencida.

## Quitado

- Tres funciones de carga inicial de contenido de versiones 9.9/9.10 que ya no se ejecutaban.

## Validación

En WordPress 7.1.4 + MariaDB 10.11 con la base real del 10-11-2026 (11 páginas, 46 archivos, 79 versiones de historial):

| Prueba | Resultado |
| --- | --- |
| Guardar las 9 pestañas sin cambios: contenido igual a 9.29.1 (197 campos, valor por valor) | Igual |
| 9 páginas públicas: HTML antes y después | Idéntico |
| Errores de JavaScript al abrir y guardar las 9 pestañas | 0 |
| Guardar sin conexión / con sesión vencida / con error 500 | Aviso, nada se pierde |
| Sesión vencida → entrar en otra pestaña → «Ya entré, guardar» | Guardado correcto |
| Clave del formulario vencida | Se renueva sola, guardado correcto |
| Borrar un archivo en uso / uno sin uso | Bloqueado con aviso / borrado |
| Sitio privado: sin clave, clave mala, clave buena, apagado | 401 / 401 / 200 / 200 |
| `install.php` con la base vacía y con la base normal | «Sitio en mantenimiento» (503) |
| Recorrido en PC (1440×900) y celular (390×844) | 9 pasos, sin errores |
| Diagnóstico y Acceso al sitio con rol Editor GEP | No aparecen (solo administradores) |

Pendiente de probar en el servidor real: pruebas 38-42 de las historias de usuario, segundo factor con el plugin Two Factor, envío de correos.
