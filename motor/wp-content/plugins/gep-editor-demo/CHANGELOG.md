# GEP Editor — CHANGELOG

## 10.0.2 · Ajustes de la revisión externa (25-11-2026)
Sin cambios de datos ni de pantallas del editor; se instala igual que la 10.0.1 (carpeta completa del plugin).
- **Guardado** (`gep-editor-demo.php`, `gep_demo_handle_admin_save`): el tipo de página (Inicio, Servicios, Publicaciones…) se deduce de la página misma. El campo oculto `gep_editor_context` del formulario solo decide si la página no se reconoce, como antes. Si no coinciden, se guarda según la página y queda una nota en Diagnóstico → Registro técnico. Evita que un formulario equivocado escriba campos de otra pestaña.
- **Acceso al sitio** (`includes/sitio-privado.php`): tras 30 claves equivocadas (contadas aunque lleguen al mismo tiempo), la clave deja de revisarse por 10 minutos (respuesta 401 con aviso y `Retry-After`) y queda anotado en el Registro técnico. Guardar la pantalla Acceso al sitio levanta la pausa. Ajustable con `GEP_PRIVADO_MAX_FALLOS` y `GEP_PRIVADO_MINUTOS_PAUSA`. El aviso de que fotos y PDF de la Mediateca no quedan protegidos ahora es un recuadro visible.
- Documentación del repositorio alineada a 10.0.2, nueva guía `documentacion/07-ambiente-local.md` y revisión automática de sintaxis PHP en GitHub (`.github/workflows/php-lint.yml`).

## 10.0.1 · Botón «Modo oscuro» visible en la Ayuda (24-11-2026)
- En la Ayuda, en modo claro, el botón «Modo oscuro» quedaba blanco sobre fondo claro (funcionaba pero no se veía). Ahora usa el mismo estilo que «Abrir historial». Solo cambia `assets/dark.css`; el modo oscuro queda igual.

## 10.0.0 · Versión definitiva de producción (24-11-2026)
Deja de llamarse «GEP Editor Beta». Misma carpeta (`gep-editor-demo`) y mismo archivo principal: WordPress lo mantiene activo y todo el contenido sigue igual. No hay migración de datos. Va con `gep-config-migracion.php` 10.0.0 y gep-acceso 10.0.0; sitio público 9.29.0 y Media Organizer 1.6.4 sin cambios.
- **Guardado seguro** (`assets/edit-lock.js`, `includes/edit-lock.php`): antes de enviar se comprueba la sesión y la conexión. Si la sesión venció, no hay Internet o el servidor no responde, no se envía nada y aparece un aviso con el estilo del editor; lo escrito sigue en pantalla. Tras volver a iniciar sesión en otra pestaña, «Ya entré, guardar» renueva las claves de seguridad y guarda. Las claves también se renuevan solas si la pestaña llevaba más de un día abierta. Si una clave vencida llega igual al servidor, se explica en vez de «El enlace ha caducado».
- **Archivos en uso** (`includes/archivos-en-uso.php`): la Mediateca muestra «Se usa en» y no deja borrar (cuadrícula, lista ni «Editar medio») un archivo que usa alguna de las 9 páginas. Apagable con `GEP_PROTEGER_ARCHIVOS` false.
- **Acceso al sitio** (`includes/sitio-privado.php`): interruptor solo para administradores que pide usuario y clave (ventana del navegador) para ver el sitio público, o lo deja público. Clave cifrada en la base (`gep_sitio_privado`). Emergencia: `GEP_SITIO_PRIVADO_APAGADO`.
- **Diagnóstico** (`includes/diagnostico.php`): requisitos, configuración, páginas, contenido legible, archivos usados que faltan, direcciones antiguas, subidas, espacio, último respaldo y registro técnico. «Copiar informe» sin rutas ni claves.
- **Registro técnico** (`includes/registro-tecnico.php`): últimos 100 errores (guardado rechazado, respaldo no válido, DOI sin conexión, imagen de video, correo no enviado, errores fatales de PHP en código GEP), sin datos sensibles. Opción `gep_registro_tecnico`.
- **Ayuda**: recorrido guiado de 9 pasos con el editor actual (antes 4, y uno apuntaba a pestañas que ya no existen) y tutoriales nuevos «Si se corta la sesión o Internet», «Fotos y archivos en uso» y «Diagnóstico y acceso al sitio».
- Respaldo completo: anota la fecha de la última descarga (Diagnóstico avisa después de 7 días).
- Limpieza: se quitaron tres funciones de carga inicial antiguas que no se ejecutaban (`gep_demo_seed_*`).
- Validado en WordPress 7.1.4 + MariaDB con la base del 10-11: guardar las 9 pestañas sin cambios deja el contenido idéntico a 9.29.1; páginas públicas idénticas byte a byte.

## 9.29.1 · Vista previa que acompaña en notebooks
Solo cambia `assets/ux.css` (CSS, sin JavaScript).
- La vista previa fija bajaba con la página solo si la ventana medía al menos 1281 × 700. En notebooks (1366 × 768) o con Windows/Chrome al 125-150 % quedaba quieta arriba. Ahora acompaña desde 1181 × 560, igual que la barra «Estado de la página».
- Entre 1181 y 1280 de ancho la barra ocupa siempre dos líneas (138 px) y la vista previa queda justo debajo. Desde 1281, igual que antes (una línea, 84 px).
- Con poca altura la vista previa mide como mínimo 320 px (antes 420) para caber en la pantalla.
- Sigue sin medir nada al desplazarse (la causa del parpadeo de la 9.24.0 no vuelve). `GEP_EDITOR_DISABLE_STICKY` sigue funcionando.
- No depende del rol: administrador y Editor GEP ven lo mismo.

## 9.27.1 · Operación Motor
WordPress pasa de `/gep/wordpress/` a `/gep/motor/`. Va junto al migrador temporal `gep-operacion-motor.php`, el sitio público 9.27.1 (`includes/`) y `gep-config-migracion.php` 9.27.1.
- **Nuevo `includes/cms-location.php`**: única fuente de las reglas que convierten direcciones de ubicaciones anteriores del CMS (`gep.uchile.cl/gep/wordpress`, `gep.uchile.cl/wordpress`, `gepdev.bonfyr.cl/wordpress`) a la actual. Solo cambia el principio de una dirección (normal, dentro de JSON `\/` o codificada `%2F`) y la forma relativa `/gep/wordpress/` entre comillas, paréntesis o tras `=`. No toca otros dominios.
- **Historial**: la instantánea guardada no se modifica (la huella de integridad sigue valiendo); al restaurar, lo que se escribe lleva `/gep/motor/`. Nuevo tipo de versión «Traslado del CMS», que crea el migrador.
- **Respaldo completo**: importar un respaldo anterior al traslado escribe las direcciones con `/gep/motor/`.
- **Pantallas del editor** (`domain-urls.php`): la red de seguridad aplica primero las reglas de traslado.
- No cambia ningún dato por sí solo y funciona igual si WordPress sigue en `/gep/wordpress/` (no convierte la ubicación actual).

## 9.26.1
Migración a https://gep.uchile.cl/gep/ (10-11). Solo cambia `includes/domain-urls.php`.
- Las direcciones guardadas con el servidor antiguo (`https://gepdev.bonfyr.cl/wordpress/...`) se muestran en el editor como `https://gep.uchile.cl/gep/wordpress/...` (antes quedaban sin la carpeta `/gep` y las vistas previas salían rotas). La carpeta base se calcula desde la dirección de WordPress; si WordPress está en una carpeta del primer nivel no cambia nada. Al pulsar Guardar quedan guardadas ya con la dirección nueva. No modifica la base de datos por sí solo.

## 9.26.0
Cinco mejoras pedidas por Cal (03-11). Plugin + sitio 9.26.0; GEP Media Organizer sigue en 1.6.4. No se borra, migra ni sobrescribe ningún dato existente.
- **Botones principal y secundario** (cabecera y llamado final de Inicio, llamado final de Servicios): tarjeta con muestra del botón (principal = relleno, secundario = borde), interruptor Visible/Oculto por botón (metas nuevas `gep_*_off`, no borra texto ni enlace), lista «¿A dónde lleva?» con páginas y apartados del sitio u «Enlace personalizado o externo». El campo de enlace de siempre se mantiene (mismo nombre e id): los enlaces guardados siguen igual. Vista previa en vivo. Componente común: `includes/button-editor.php` + `assets/button-editor.js`.
- **«Encuadre de la foto»**: botón junto a cada selector de imagen en todas las pestañas. Una sola herramienta (`includes/image-frame.php` + `assets/image-frame.js`): foto completa con punto arrastrable, vistas PC / Tablet / Teléfono con la forma real del contenedor, arrastre con mouse o dedo, flechas del teclado, acercamiento (no en cabeceras), encuadre distinto en teléfonos, Restablecer / Cancelar / Aplicar. Se guarda por foto en la meta `gep_image_frames_json` (clave sin dominio ni tamaño, sirve tras el cambio de dominio), entra en el Historial y en el respaldo. El sitio lo aplica solo con CSS (`site/includes/image-frame.php`), sin JavaScript ni saltos.
- **Un solo «Guardar cambios»**: se quitaron los botones inferiores de todas las pestañas. En PC queda el de la barra superior; en tablet y celular ese mismo botón flota abajo a la derecha (sobre la barra de pestañas en celular). Los avisos de cambios sin guardar siguen igual.
- **Proyectos vigentes y trayectoria unificados**: ya eran una sola lista (`gep_projects_records_json` con estado vigente/cerrado). Ahora «Proyectos vigentes» y «Trayectoria» abren la misma lista con filtro Todos / Vigentes / Trayectoria, contador, etiqueta de estado que se pulsa para mover el proyecto y aviso de posibles duplicados. «Añadir proyecto» usa el estado del filtro. Respaldo automático único antes del primer guardado: meta privada `_gep_projects_backup_v926` (nunca se sobrescribe). La lista antigua `gep_projects_features_json` se conserva intacta.
- **Video de Servicios**: causa medida: sin imagen de cabecera ni `NOMBRE-respaldo.jpg`, la cabecera queda verde ~1 s mientras llega el primer cuadro del MP4 (pesado); en YouTube/Vimeo no había portada y se veía negro. Ahora YouTube/Vimeo muestran portada (imagen de cabecera o miniatura de YouTube) hasta que el video realmente reproduce, y el editor indica bajo el video si tiene imagen de respaldo y versión liviana para teléfonos. Sin temporizadores de espera ni animaciones añadidas.
- Ayuda: tutoriales nuevos «Encuadre de la foto», «Botones principal y secundario» y «Proyectos vigentes y trayectoria».
- Corregido: filas de Proyectos y Publicaciones aparecían todas con borde rojo «incompleto» al cargar (`toggleClass` recibía `''`).

## 9.25.0
Siete mejoras pedidas por Cal (03-11). Plugin + sitio 9.25.0; GEP Media Organizer sigue en 1.6.4. Los contenidos guardados se leen igual.
- **Ir al apartado nuevo**: al crear un apartado (o usar cualquier «Añadir…») la página baja suavemente hasta él, queda justo debajo de la barra fija y con su ficha abierta y el cursor en el primer campo. Causa: `admin.js` hacía `$(document).off('click','.gep-add-section')` y borraba el oyente de `ux.js`; ahora `ux.js` escucha en fase de captura (no se puede borrar así). En listas con su propia barra de desplazamiento (Proyectos, Publicaciones) se ajusta primero la lista y luego la página. Desplazamiento de una sola vez: no se escucha el scroll.
- **Video en iPhone** (sitio): la imagen de respaldo se muestra de inmediato (reducida, sin descargarse dos veces) y el video empieza a cargarse en cuanto la página se dibuja, no al terminar de cargar todo. El video aparece con un fundido corto recién cuando tiene su primer cuadro listo: sin pantalla negra ni parpadeo. Se mantienen autoplay, silencio y reproducción en línea.
- **Título y descripción opcionales** en los apartados personalizados: si quedan vacíos no se dejan huecos ni avisos.
- **Editor visual de botones**: hasta 4 botones por apartado, con texto, destino (lista de páginas del sitio u otro enlace/correo), misma pestaña o nueva, estilo principal/secundario, subir/bajar/arrastrar y eliminar. Vista previa en vivo. El botón antiguo se convierte solo en el primer botón.
- **«Etiquetas» renombrado**: «Lista de temas» (tipo Biblioteca temática), «Temas destacados» (introducción de Quiénes somos) y «Textos de apoyo» (resumen de la portada). Los datos internos no cambian.
- **Quitado el «Ver página real» duplicado** de la barra de estado; el acceso queda en «Sitio público» del menú lateral.
- **Barra negra bajo «Herramientas y datos»** (vista previa de Inicio): era la nota de la portada vacía; ahora se oculta cuando no tiene texto.
- **Foto de «Mensaje de paisaje»**: con foto propia, la franja toma la forma de la foto (entre 380 px y 86 % de la pantalla) en vez de un recorte fijo; las fotos verticales se muestran completas sobre un fondo difuminado. Nuevo «Punto de la foto que siempre se ve» (clic, arrastre o flechas) y opción «Franja fija» para el comportamiento anterior.
- Celular: la barra de pestañas del editor vuelve a ocupar todo el ancho.

## 9.24.1
Corrige el parpadeo crítico de la 9.24.0 (la barra de estado y la vista previa saltaban arriba y abajo sin parar). Solo plugin; no cambia datos guardados ni la página pública.
- **Causa**: `assets/ux.js` medía la barra en cada desplazamiento (sentinela + clase `is-stuck`), la achicaba al fijarse (84 → 50 px), recalculaba variables CSS (`--gep-preview-top`, `--gep-viewport-h`) de las que dependían la posición y el alto de la vista previa, y el navegador corregía el scroll (scroll anchoring). Cerca del final de páginas cortas eso volvía a soltar la barra, que crecía, y el ciclo se repetía 60 veces por segundo.
- **Arreglo**: barra y vista previa fijas solo con CSS (`assets/ux.css`), con posiciones constantes. Se eliminaron el sentinela, `is-stuck`, la compactación, la compensación de margen, el `ResizeObserver`, el `setInterval`, los oyentes de scroll/resize y toda escritura de estilos en `<html>`/`<body>`. `overflow-anchor:none` en el área que se desplaza.
- La barra ya no se achica al fijarse (mide siempre lo mismo). Se fija solo en pantallas de 1181 px o más y 560 px de alto o más; la vista previa, solo desde 1281 px y 700 px de alto. En pantallas más chicas o bajas todo queda en su lugar normal (en tablet/celular se guarda con el botón de abajo).
- Alternativa estática: `define( 'GEP_EDITOR_DISABLE_STICKY', true );` en wp-config.php (o el filtro `gep_editor_disable_sticky`) quita ambos comportamientos fijos.
- En modo General, pasar el mouse por los apartados ya no desplaza la vista previa (solo el clic la enfoca) (`assets/sections.js`).
- Con "reducir movimiento" del sistema, sin desplazamientos suaves ni destellos.

## 9.24.0
Mejoras de comodidad del editor (8 puntos de Cal, 03-11). Solo plugin; el sitio sigue en 9.23.0 y GEP Media Organizer en 1.6.4. No cambia ningún dato guardado.
- **Barra "Estado de la página" fija**: acompaña al bajar, justo debajo de los botones de arriba, y se compacta a una línea (en móvil: estado + Guardar). Causa de fondo: los contenedores de WordPress tenían `overflow-x:hidden`, que anulaba `position:sticky`; ahora usan `overflow-x:clip` (`assets/ux.css`, `assets/ux.js`, `includes/ux.php`).
- **Vista previa que acompaña**: en PC queda fija debajo de la barra, con sus botones General/Apartado y PC/Tablet/Móvil siempre a mano, sin salirse de su columna. En tablet y móvil sigue debajo del formulario.
- **Editar en "modo enfoque"**: al pulsar Editar sección la lista se pliega y queda solo ese apartado, con una tira para saltar a otro. Pulsar el apartado otra vez (o "Todas las secciones") vuelve a la lista. Después de Guardar se reabre el mismo apartado (`assets/sections.js`).
- **Ir a lo recién creado**: al añadir un apartado o usar cualquier botón "Añadir…", la página se desplaza suavemente hasta lo nuevo y lo resalta un momento, sin quedar tapado por la barra fija.
- **Arrastrar más cómodo**: asa ≡ más grande y visible en todas las listas, recuadro "Soltar aquí", arrastre solo tras mover 6 px (sin arrastres accidentales), desplazamiento automático cerca de los bordes y flechas ↑ ↓ del teclado con el asa seleccionada.
- **Correo de soporte en Ayuda**: los administradores lo cambian desde "Cambiar correo de soporte" (se valida el formato; vacío = correo del administrador). Todos los enlaces de contacto de la Ayuda usan ese correo. En tablet/móvil el recuadro de soporte aparece al final de la Ayuda.
- **Vista previa en "General" al entrar** a cualquier pestaña; "Apartado" sigue disponible.
- **Publicaciones y Buscador separados**: nuevo apartado "Buscador" (título del buscador, texto del menú y filtros por categoría) y "Publicaciones" (registros, DOI y nota). Mismos nombres de campos: los datos existentes se leen igual. El Buscador va siempre justo antes de Publicaciones; si se oculta, la página pública quita el buscador y su acceso del menú.
- Ayuda: tutorial nuevo "Editar un apartado sin perderte" y atajo ↑ / ↓.

## 9.23.6
- Los botones de arriba a la derecha (Modo oscuro, Carpetas de imágenes, Ayuda) bajan solos cuando la barra de WordPress ("Hola, …") está visible, para no quedar tapados (`assets/theme-toggle.js`).

## 9.23.5
- **Botón "Carpetas de imágenes"** arriba a la derecha del editor, junto a Ayuda: lleva a Medios > Carpetas GEP para subir y ordenar fotos (solo si GEP Media Organizer está activo).
- **Ayuda actualizada**: tutoriales nuevos "Abrir y cerrar una ficha", "Personas y grupos (Quiénes somos)", "Noticias y fechas" y "Dos personas editando a la vez"; la portada con video ya explica que la imagen de respaldo se crea sola; 4 dudas rápidas nuevas.
- Va junto con GEP Media Organizer 1.6.4 (sin "Ordenar fotos del GEP").

## 9.23.4
- **Sin X para cerrar** (Cal): se quitó la X / "Cerrar edición" de las fichas (personas, proyectos, publicaciones, servicios y apartados de la estructura). Se cierran pulsando de nuevo el elemento (9.23.3).
- **Flecha que gira**: la flecha ">" de cada fila apunta hacia abajo mientras su ficha está abierta (personas, proyectos, publicaciones; Descripción de imágenes ya lo hacía).

## 9.23.3
- **Pulsar de nuevo cierra** (Cal): en todo el editor, volver a pulsar el elemento que está abierto cierra su ficha. Aplica a personas de Quiénes somos, proyectos, publicaciones y al botón "Editar" de cada apartado de la estructura. Servicios, Descripción de imágenes y las filas desplegables ya lo hacían.

## 9.23.2
Quiénes somos más liviano (Cal: "se siente sobrecargado"). Solo plugin; el sitio sigue en 9.23.0.
- **Añadir un apartado** pasa al final de la lista como un solo botón; al pulsarlo aparecen los 3 tipos.
- **Grupos**: se quitó la caja "Grupos / 0 grupos / Sin grupo". Queda un botón "Organizar en grupos" (o "Grupos (N)") en la barra de la lista. Sin grupos, la lista es simple, sin la caja "Sin grupo".
- **Filas**: sin selector de grupo en cada fila (tapaba los nombres en Postgrado). El grupo se cambia dentro de la ficha o arrastrando entre grupos.
- **Buscador** solo con más de 8 personas. "Añadir persona" / "Añadir registro" / "Añadir tarjeta" según el tipo.
- **Título y "Mostrar abierto"** pasan al pie, en "Ajustes del apartado", junto a Duplicar y Eliminar. La lista queda primero.
- Botón Eliminar de la ficha con el mismo estilo rojo (se veía mal en modo oscuro).

## 9.23.1
Ajustes de Quiénes somos pedidos por Cal (solo plugin; el sitio sigue en 9.23.0).
- **"Añadir un apartado"**: los 3 botones (Personas, Estados / tesis, Tarjetas con foto) quedan del mismo tamaño en 3 columnas, con icono y una línea que explica cada tipo. En pantallas angostas pasan a una columna.
- **Duplicar y Eliminar apartado** ya no están en la cabecera: quedan dentro del apartado, al final, en "Acciones del apartado" (`directoryPlaceSectionActions` en admin.js). Eliminar es un botón rojo con borde.
- **Lista de personas**: el texto oculto "Mover a grupo" ya no se ve encima del nombre, y un cargo largo ya no tapa el nombre ni empuja el selector de grupo fuera de la fila.
- Modo oscuro con los mismos cambios.

## 9.23.0
Arreglos de las historias de usuario del 27-10 (sitio GEP_LEP 9.23.0 en el mismo paquete).
- **Quiénes somos vacío en el editor (E1)**: si lo guardado quedaba vacío, el editor no mostraba a nadie aunque el sitio mostraba el directorio original. Ahora el editor carga ese mismo directorio, y un guardado sin apartados ya no deja la lista vacía (`gep_demo_get_sections`, `gep_demo_save_content_fields`).
- **Dos personas editando la misma página (E2)**: aviso con el nombre de quien más tiene abierta la página (latido de WordPress cada 15 s) y, al guardar, si otra persona guardó después de que abriste la página, una ventana permite seguir editando, recargar o guardar igual. Archivos nuevos `includes/edit-lock.php`, `assets/edit-lock.js`, `assets/edit-lock.css`. El Historial guarda cada versión.
- **Imagen de respaldo automática del video de cabecera (E3/E4)**: al abrir el editor, el navegador toma el primer cuadro de cada video de cabecera sin `NOMBRE-respaldo.jpg` y lo sube a la Mediateca (misma carpeta y carpeta GEP del video). Archivos nuevos `includes/video-poster.php`, `assets/video-poster.js`.
- **Servicios (E5)**: la vista previa muestra el botón aunque falte su texto ("Más información").
- **DOI repetido (E6)**: un DOI que ya está en la lista no se puede marcar ni agregar, y al guardar se descarta un DOI repetido.
- **Cabecera (E7)**: el campo de video ya no menciona YouTube ni Vimeo; se elige un MP4 de la Mediateca.
- **Noticias y Comunicaciones (E9)**: la fecha se elige con calendario y la lista se ordena sola por fecha al guardar (lo más reciente primero). Las fechas antiguas escritas a mano se convierten solas.
- **Descripción de imágenes (E13)**: se encuentran las fotos guardadas con otro dominio o ruta, así la descripción llega al sitio.

## 9.22.1
- Corregido: en el editor el botón "Modo oscuro" no se podía pulsar (la barra superior tiene `pointer-events:none` desde la v9.13.3 y solo "Ayuda" recibía clics). Regla nueva al final de `assets/dark.css`.

## 9.22.0
- **Modo oscuro del editor**: botón "Modo oscuro / Modo claro" en la barra superior del editor y en las cabeceras de Ayuda, Historial de cambios y Descripción de imágenes. Cada persona elige el suyo y se guarda en su perfil de WordPress (user meta `gep_editor_theme`), así que lo ve igual en cualquier navegador. La clase `gep-theme-dark` se añade al `<body>` desde PHP antes de pintar, por lo que no hay parpadeo blanco al cargar.
- La **vista previa del sitio** dentro del editor **no se oscurece**: la web pública es clara y la preview debe mostrarla tal cual.
- **La web pública no cambia** y el modo claro queda idéntico: todo el modo oscuro vive en `assets/dark.css`, cuyas reglas empiezan por `body.gep-theme-dark`.
- Archivos nuevos: `includes/theme.php`, `assets/dark.css`, `assets/theme-toggle.js`. En `gep-editor-demo.php` solo cambia la versión y el `require_once` del archivo nuevo.

## 9.21.4
- **Cambio de dominio sin fotos rotas en el editor**: las fotos y videos se guardan con su dirección completa (por ejemplo `https://gepdev.bonfyr.cl/wordpress/wp-content/uploads/...`). Si el sitio se muda, las pantallas de Contenido GEP muestran esas direcciones con el dominio actual, y al pulsar Guardar quedan guardadas así. El plugin recuerda cada dominio por el que pasa el sitio (opción `gep_known_hosts`, compartida con el sitio público). Se pueden agregar dominios antiguos con `define( 'GEP_SITE_OLD_HOSTS', 'dominio1.cl,dominio2.cl' );` en wp-config.php. Archivo nuevo `includes/domain-urls.php`.
- Vista previa del Inicio: sin foto elegida en "Registro visual" y "Red" muestra la misma foto de respaldo que la página (`assets/admin.js`).
- Sitio GEP_LEP 9.21.4 (mismo paquete): enlaces con "&" ya no se rompen, direcciones independientes del dominio, fotos de la Mediateca en tamaño reducido, recuadros vacíos del Inicio con foto de respaldo, favicon y 404 en fichas de proyecto inexistentes.

## 9.21.3
- **Una sola barra de desplazamiento en la vista previa**: en Servicios la página simulada tenía su propio scroll dentro del marco y se veían dos barras. Ahora solo se desplaza el marco, igual que en las demás pestañas. Archivos `assets/services.css` y `assets/editor.css` (regla general al final: el contenido de una preview nunca tiene scroll propio).
- **Video de cabecera en las previews = primer cuadro**: en todas las pestañas el video ya no se reproduce dentro de la preview; se muestra quieto en su primer cuadro (carga mucho menos). Si es de YouTube se muestra su miniatura; Vimeo, el reproductor detenido. Al escribir textos el video ya no se vuelve a descargar. Servicios ahora también muestra videos de YouTube (antes la cabecera quedaba verde). Archivos `assets/admin.js` (`gepPreviewPlaceHeroVideo`), `assets/services.js`, `includes/content-hubs.php`.
- **Video de cabecera visible en Inicio, Docencia, Proyectos y Publicaciones**: en esas previews el video quedaba escondido detrás del fondo verde de la cabecera (les faltaba la regla de capas que sí tenían Quiénes somos, Contacto, Comunicaciones, Noticias y Servicios). Archivo `assets/editor.css`.
- **Quiénes somos y Contáctanos con la misma vista previa que el resto**: su cabecera ahora es verde oscuro con texto blanco y las mismas medidas que las demás (como en la página pública), también sin imagen elegida. Contáctanos usa la misma barra de navegador (nombre del archivo a la izquierda, "Vista escritorio" a la derecha). Archivo `assets/editor.css`.
- Las miniaturas de video del editor (selector de video de cabecera y apartado de video) muestran el primer cuadro también en Safari. Archivos `gep-editor-demo.php`, `assets/hero-media.js`, `assets/sections.js`.

## 9.21.0
- **Importar por DOI** (Publicaciones > Registros bibliográficos): botón nuevo junto a "Añadir publicación". Se pegan uno o varios DOI (o enlaces doi.org), se buscan en Crossref y, si no están ahí, en doi.org (DataCite, Zenodo). Cada uno muestra su ficha con la categoría sugerida y avisa si ya está en la lista (por DOI o por título). Los elegidos entran al inicio de la lista con la referencia escrita en el mismo formato que los demás registros, el enlace al DOI y el botón "Ver DOI ↗"; se guardan con "Guardar cambios". Las respuestas se guardan 30 días en caché. Los registros tienen un campo nuevo opcional **DOI**. Archivos nuevos `includes/doi-import.php`, `assets/doi-import.js` y `assets/doi-import.css`; `admin.js` expone `window.gepPublicationsApi`.
- **Descripción de imágenes** (menú Contenido GEP): lista las fotos de la Mediateca que usa el sitio, en qué pestaña aparecen y si tienen descripción; también las que aún no se usan. Se edita una a la vez y se guarda en el "Texto alternativo" de la Mediateca, que vale en todas las páginas. Funciona con el rol Editor GEP. Archivos nuevos `includes/image-alt.php`, `assets/image-alt.js` y `assets/image-alt.css` (usa la base visual de `history.css`).
- **Datos para Google** (sitio GEP_LEP): cada página publica en `<head>` un bloque JSON-LD con el laboratorio (con correo, teléfono y dirección tomados de Contacto, vía `gep_demo_structured_org_data()` en `includes/structured-data.php`), el sitio, la página y su ruta (migas). Publicaciones agrega cada registro como obra académica (artículo, libro, capítulo u otro) con su DOI. También enlace canónico y etiquetas Open Graph para compartir.
- Ayuda: dos tutoriales nuevos, "Importar publicaciones por DOI" y "Describir las fotos".
- Corregido: el resumen del apartado de Publicaciones decía "0 registros".

## 9.20.0
- **Editor y página muestran lo mismo cuando una pestaña no tiene nada guardado** (por ejemplo, después de reinstalar WordPress). Nuevo `includes/defaults-v920.php`, solo de lectura: no escribe en la base hasta pulsar Guardar.
- **Quiénes somos**: el editor carga las 44 personas y 6 grupos que ya mostraba la página (antes salía "Aún no hay apartados" y no se podían editar). Al guardar, la página usa los apartados del editor sin depender del interruptor oculto "Usar el contenido creado".
- **Docencia**: la preview ya no inventa fotos (cabecera, cursos, Campos de formación) que la página no muestra, y ahora incluye Diplomado y Cursos y talleres con sus pestañas, como la página.
- **Vistas previas de cabecera** (todas las pestañas): sin imagen elegida, la preview queda sin foto, igual que la página.
- **Proyectos**: sin proyectos guardados vuelven los vigentes MapBiomas Agua, Fuego y Chile, con sus fotos de `uploads/2026/09` si existen. El título "Proyectos cerrados · trayectoria" ahora se edita de verdad (la página lo tenía fijo).
- **Comunicaciones y Noticias**: sin registros guardados, la página muestra los mismos registros iniciales que la preview (antes la preview mostraba 5 y la página ninguno).
- **Inicio**: nueva imagen de fondo opcional para "Mensaje de paisaje" (`gep_home_landscape_image`). El resumen de cada bloque muestra si tiene imagen y cuántas tarjetas tienen foto.

## 9.19.0
- **Respaldo completo** (Historial de cambios > Respaldo completo): descarga en un archivo JSON todo el contenido de las nueve pestañas y lo restaura desde ese archivo. Las páginas se reconocen por su pestaña, no por su número interno, así que sirve también en un WordPress nuevo (crea la página si falta). Antes de restaurar se guarda un punto de seguridad por pestaña en el historial. Las imágenes no van en el archivo (siguen en la Mediateca); el archivo trae la lista de las que se usan. Archivo nuevo `includes/backup.php`.
- **Rol "Editor GEP"** (`gep_editor`): puede usar Contenido GEP y la Mediateca, pero no plugins, temas, ajustes, usuarios ni borrar páginas. Al entrar llega directo al editor. Se crea solo; se asigna en Usuarios. Archivo nuevo `includes/roles.php`.
- **Corregido: comillas y saltos de línea rompían listas guardadas.** `update_post_meta()` quita las barras invertidas, así que un título con comillas (por ejemplo `"Bosque"`) dejaba el JSON inválido y la lista entera (tarjetas, publicaciones, proyectos, etc.) quedaba vacía; los saltos de línea se guardaban como la letra "n". Todos los campos `*_json` y la restauración del historial ahora se guardan con `wp_slash()`.
- Historial: nuevo tipo "Respaldo restaurado" en la lista y en los filtros.

## 9.18.1
- **Enlaces al sitio público**: "Ver página real", el enlace lateral de cada pestaña y las vistas previas ahora llevan a la carpeta del sitio (`/GEP_LEP/...`) y no a la raíz del dominio (`/index.php`). `gep_editor_public_base_url()` detecta la carpeta `GEP_LEP` junto a WordPress. Se puede cambiar con `GEP_EDITOR_PUBLIC_FOLDER` o el filtro `gep_editor_public_site_folder`.
- Raíz del servidor: nuevo `index.php`/`index.html` que lleva directo a `/GEP_LEP/` (el selector de propuestas queda guardado como `selector-propuestas.html`). Se entregan aparte, no van dentro del plugin.

## 9.18.0
- **Ayuda**: el recorrido del editor ya no se abre solo al entrar. Se abre solo con *Ayuda > Ver recorrido* (antes "Repetir recorrido"), y al recargar no vuelve a aparecer. Archivos `includes/help.php` y `assets/help.js`.
- **Video de cabecera en iPhone** (sitio GEP_LEP): el video declara su tipo (`video/mp4`), se fuerza silenciado e "inline" por JavaScript, se reintenta con el primer toque o scroll si el teléfono lo bloquea (ahorro de batería) y, si no se puede reproducir, se oculta para que se vea la imagen de cabecera. Archivos del sitio `includes/hero-media.php`, `assets/js/site.js` y `assets/css/style.css`.
- **Errores visuales del editor**: icono y texto alineados en "Restaurar original", "Ver página real" y "Guardar cambios"; la cabecera "Vista previa de la página" muestra el título en una línea con la descripción debajo y los controles (Apartado/General y PC/Tablet/Móvil) en su propia fila. Archivo `assets/editor.css`.
- **Fondo de la cabecera** (solo en los headers de todas las pestañas): Imagen o Video se eligen en un solo control. Con Video se puede poner una imagen de respaldo para teléfonos. Los datos guardados son los mismos de antes. Archivo nuevo `assets/hero-media.js` y estilos en `assets/editor.css`.
- Corregida una expresión regular en `gep_demo_public_asset_base_url()` que fallaba (aviso PHP) al calcular la dirección base de la vista previa.
- `admin.css`, `sections.css`, `editor.css` y `admin.js` ahora también se versionan con la fecha del archivo (`gep_demo_asset_version()`).

## 9.17.0
- Nueva pestaña **Servicios** (Inventarios forestales, Dron multiespectral y LiDAR UAV): lista primero y un servicio abierto a la vez, ocultar sin borrar, ordenar, duplicar y vista previa PC/Tablet/Móvil. Archivos `includes/services.php`, `assets/services.css` y `assets/services.js`. Necesita la página de WordPress con slug `servicios` y el sitio con `servicios.php`.
- **Historial de cambios** rehecho: lista por día con frases claras, comparación visual (lado a lado, solo diferencias y como en la web, con deslizador de fotos) y ventana propia para restaurar. El rollback y los datos guardados no cambian.
- **Ayuda** nueva: centro de ayuda visual con buscador (Ctrl + K), tutoriales cortos paso a paso, botón "?" en cada apartado, recorrido de bienvenida y Ctrl + S para guardar. Archivos `includes/help.php`, `assets/help.css` y `assets/help.js`. Incluye un tutorial de Servicios.
- Corregido un bucle infinito en `sections.js` al sacar el mouse de un apartado abierto (error en consola en todas las pestañas).
- CSS/JS nuevos o cambiados se versionan con la fecha del archivo para que la caché del navegador no muestre la versión anterior.

## 9.16.0
- Cambio de nombre público de “Demo” a “Beta”.
- Versión centralizada mediante `GEP_EDITOR_VERSION`.
- Se detuvo la creación automática de páginas y la reinyección de contenido provisional.
- Rutas públicas desacopladas del nombre fijo `/wordpress/`.
- Imágenes editoriales sin fallbacks locales: se controlan desde WordPress.
- `theme-913.css` pasa a `assets/editor.css`.
- Historial/exportación y carga de assets usan la versión central.

---

## Historial anterior

### v9.10.0

GEP Editor Demo v9.10.0

EDITOR / UX
- Panel superior de salud de contenido: cantidad de elementos, cobertura de imágenes y estado general.
- Indicadores por registro: Completo, Sin imagen o Falta texto cuando corresponde.
- Aviso visual no bloqueante en controles de imagen vacíos.
- Botones de añadir contenido reciben una jerarquía visual más clara.
- Feedback de guardado reforzado: estado “Guardando…” y toast de confirmación al volver del guardado.
- Se mantienen las confirmaciones antes de eliminar proyectos, publicaciones, cursos, contenidos, apartados, personas y registros repetibles.
- Contadores existentes se mantienen y se complementan con el resumen global.

RESPONSIVE DEL EDITOR
- Layout editor/preview se adapta a escritorio amplio, portátil, tablet y móvil.
- A <=1320 px la preview baja de columna para evitar formularios aplastados.
- Tabs, commandbars, botones, grids, galerías y acciones pasan a layouts seguros en tablet/móvil.
- Las previews PC/Tablet/Móvil tienen overflow controlado y nunca fuerzan el ancho del admin.
- Textos largos y URLs pueden partir sin romper tarjetas.

DATOS DE DEMOSTRACIÓN SEGUROS
- Se completan únicamente campos vacíos; nunca se reemplazan datos escritos por el usuario.
- Hasta 3 proyectos vigentes de demostración: MapBiomas Chile, MapBiomas Agua Chile y MapBiomas Fuego Chile, con imagen, descripción, instituciones, responsables, galería y enlaces.
- Noticias y Comunicaciones reciben imágenes locales de apoyo en registros que aún no tienen imagen.
- Cursos reciben imágenes locales de apoyo cuando su imagen está vacía.
- Tarjetas de equipo reciben imágenes locales de apoyo si están vacías.
- Las imágenes locales se muestran correctamente también dentro de las previews del editor.

CONGELACIÓN
- PHP y JavaScript pasan validación de sintaxis.
- Historial sigue capturando todos los metadatos gep_* de Inicio, Quiénes somos, Docencia, Proyectos, Publicaciones, Comunicaciones, Noticias y Contacto.
- No requiere SQL ni migraciones manuales.

### v9.10.1

GEP Editor Demo v9.10.1

- Eliminado el panel superior de diagnóstico de contenido (elementos / imágenes / por revisar).
- Eliminados los badges automáticos Completo / Falta texto / Sin imagen.
- Corregido el falso positivo de Inicio: los bloques sin campos title/name ya no se interpretan como contenido incompleto.
- Se conservan confirmaciones de eliminación, estado de cambios, feedback de guardado y resolución de imágenes en preview.

### v9.10.2

GEP Editor Demo v9.10.2

- Preview por hover: al pasar el mouse sobre un apartado, la previsualización se desplaza y resalta automáticamente el bloque correspondiente.
- La función se aplica a Inicio, Quiénes somos, Contacto, Docencia, Proyectos, Publicaciones, Comunicaciones y Noticias.
- El hover no abre ni cierra formularios; el clic conserva su comportamiento de edición.
- El ancho del editor ahora es fluido y aprovecha el espacio disponible al cambiar el zoom del navegador o usar pantallas grandes.
- Se mantienen los cortes responsive: por debajo de 1180 px el editor vuelve a una sola columna para evitar compresión.
- Assets versionados como 9.10.2 para evitar caché de CSS/JS anterior.

### v9.11.0

GEP Editor Demo v9.11.0

- Nueva gestión modular de apartados en todas las páginas editables.
- Los apartados existentes pueden reordenarse mediante arrastrar y soltar.
- Cada apartado completo puede eliminarse de la página sin destruir sus datos y restaurarse después.
- Botón “Añadir apartado” con biblioteca común de tipos reutilizables:
  Texto, Imagen + texto, Tarjetas, Cifras, Video y Llamado / CTA.
- Los apartados personalizados pueden editarse por completo desde el plugin.
- Los videos aceptan enlaces de YouTube, Vimeo y archivos MP4/WebM/OGG.
- La estructura y los apartados nuevos se guardan en metadatos gep_* y quedan incluidos en el historial existente.
- La web pública respeta el orden, visibilidad y apartados nuevos mediante el puente de estructura de GEP_LEP v9.11.0.
- Se mantienen la preview por hover y el layout adaptable al zoom/resolución de v9.10.2.

IMPORTANTE
Para que el orden/eliminación/adición se refleje en la web pública hay que subir también GEP_LEP-v9.11.0.zip. Esa versión añade identificadores de apartado y el hook wp_footer usado por el plugin.

### v9.11.1

GEP Editor Demo v9.11.1

Cambios visuales de Estructura de página
- “Añadir apartado” deja de usar el estilo rojo/primario y pasa a una acción verde integrada con la interfaz GEP.
- Editar, Eliminar y Restaurar ya no se muestran permanentemente en cada fila.
- Cada apartado se despliega al hacer clic y las acciones quedan dentro de ese panel.
- Eliminar usa un estilo neutro; el riesgo se comunica mediante la confirmación, no mediante rojo constante.

Preview por hover
- Al pasar el mouse por un apartado existente aparece una preview flotante junto al listado.
- La preview reutiliza el contenido real del editor cuando el apartado es nativo.
- Los apartados personalizados generan una miniatura con su contenido actual.
- Los tipos de la biblioteca (Texto, Imagen + texto, Tarjetas, Cifras, Video y CTA) muestran una preview antes de añadirlos.

Compatibilidad
- Mantiene la estructura modular de v9.11.0.
- Compatible con GEP_LEP v9.11.0 o posterior; no hace falta reemplazar el tema para este ajuste.

### v9.11.2

GEP Editor Demo v9.11.2

- Sincroniza el drag & drop de apartados con la preview principal del editor.
- Respeta grupos visuales compuestos (hero + navegación, trayectoria + registros, contacto completo, etc.).
- Al mover un apartado, la preview se desplaza a su nueva posición y lo resalta brevemente.
- Los apartados personalizados añadidos desde la biblioteca aparecen también en la preview y respetan el orden elegido.
- Ocultar/restaurar apartados se refleja inmediatamente en la preview.

### v9.12.0

GEP Editor Demo v9.12.0

- Nuevo editor unificado por apartados.
- Se elimina visualmente el bloque duplicado “Apartados editables”.
- Cada apartado desplegado muestra una mini-preview real, estado Completo/Incompleto/Oculto y resumen de contenido.
- “Editar sección” carga el editor histórico dentro de la propia tarjeta, conservando guardado, validaciones, media, drag & drop y previews existentes.
- Los apartados compuestos agrupan sus formularios relacionados (por ejemplo Contacto + Ubicación y Ruta + Cursos).
- Corrección de selectores de Proyectos y Publicaciones en la capa modular.

### v9.12.1

GEP Editor Demo v9.12.1

- Unifica todas las previews usando Quiénes somos como referencia.
- Inicio, Contacto, Docencia, Proyectos, Publicaciones, Comunicaciones y Noticias muestran solo el apartado seleccionado.
- El hover, la apertura de una tarjeta, editar, añadir, restaurar y reordenar cambian la preview al apartado correspondiente.
- La cabecera de la preview actualiza Zona y nombre del apartado según la estructura actual.
- Los apartados compuestos siguen mostrando sus sub-bloques juntos (por ejemplo Contacto + ubicación).
- Se mantiene PC / Tablet / Móvil y la preview flotante del hover.

### v9.12.2

GEP Editor Demo v9.12.2

- Añade selector de preview: Apartado / General.
- Vista Apartado conserva el comportamiento de v9.12.1: muestra solo la sección seleccionada.
- Vista General muestra la página completa respetando orden, apartados ocultos y apartados personalizados.
- En Vista General, pasar por un apartado desplaza la preview hasta ese bloque sin salir del modo general.
- El selector funciona en Inicio, Quiénes somos, Docencia, Proyectos, Publicaciones, Comunicaciones, Noticias y Contacto.

### v9.12.3

GEP Editor Demo v9.12.3

- Todas las previews vuelven a mostrar las imágenes hero/cabecera.
- Se usan las imágenes públicas predeterminadas cuando no existe una imagen personalizada.
- Las rutas relativas assets/... se resuelven correctamente dentro del administrador.
- Inicio muestra hero y franja de paisaje.
- Quiénes somos muestra hero, imagen destacada y fotos relativas correctamente.
- Docencia muestra miniaturas de cursos y la imagen de Campos de formación.
- Comunicaciones y Noticias muestran imágenes de respaldo en cabecera y tarjetas.
- Los apartados personalizados Imagen + texto y Tarjetas resuelven también imágenes relativas.

No requiere cambios en GEP_LEP v9.11.0.

### v9.12.4

GEP Editor Demo v9.12.4

- Corrige la cabecera de Quiénes somos en la preview.
- Usa una base pública absoluta para los assets de GEP_LEP.
- La fotografía predeterminada de Quiénes somos ya no depende de resolver una ruta relativa desde WordPress.
- La imagen se aplica desde PHP y se mantiene sincronizada desde JavaScript al cambiarla en la biblioteca de medios.

### v9.12.5

GEP Editor Demo v9.12.5

- Corrige el bug que repetía varias veces el mismo apartado dentro de la preview flotante.
- Las previews internas ya no son detectadas como bloques reales de la página.
- La fuente de la preview flotante queda limitada a la preview principal del editor.
- No requiere cambios en GEP_LEP.

### v9.13.0

GEP Editor Demo v9.13.0

- Rework visual completo del administrador inspirado en la nueva interfaz GEP/MapBiomas.
- Navegación lateral fija para Inicio, Quiénes somos, Docencia, Proyectos, Publicaciones, Comunicaciones, Noticias y Contacto.
- Área principal más limpia, fluida y adaptable al zoom/pantallas grandes.
- Nueva cabecera de contexto con breadcrumb, título y acciones de usuario/ayuda.
- Barra de estado y acciones rediseñada.
- Tarjetas de apartados, estados, previews y botones actualizados sin alterar las funciones incorporadas en v9.12.x.
- Se conservan: añadir/eliminar/mover apartados, edición integrada, hover-preview, vista Apartado/General, vistas PC/Tablet/Móvil e historial/guardado.

### v9.13.1

GEP Editor Demo v9.13.1

- Elimina los controles de perfil del encabezado superior.
- Reduce la barra superior y compacta el espacio vertical del editor.
- El editor ahora usa el ancho real disponible del navegador sin desbordamiento horizontal.
- Panel de edición y preview cambian de proporción según el ancho de la ventana.
- En navegadores estrechos la preview pasa debajo del editor automáticamente.
- Sidebar se compacta progresivamente en portátil/tablet y se convierte en barra inferior en móvil.
- Mantiene todas las funciones de v9.13.0.

### v9.13.2

GEP Editor Demo v9.13.2

- Oculta visualmente el scrollbar interno de la navegación GEP sin perder desplazamiento en pantallas bajas.
- Añade un botón lateral para abrir el menú nativo de WordPress cuando se necesite salir del editor o navegar por el administrador.
- El menú nativo aparece como drawer superpuesto: no mueve ni comprime el editor GEP.
- Al abrir el drawer también reaparece la barra superior de WordPress para conservar acceso al perfil/cierre de sesión.
- El mismo botón, o Escape, devuelve al editor GEP.

### v9.13.3

GEP Editor Demo v9.13.3

- Elimina la franja vacía superior del editor.
- El contenido comienza inmediatamente debajo del borde superior con margen compacto.
- "Ayuda" pasa a ser una acción flotante discreta y ya no reserva una barra completa.
- Mantiene el drawer de WordPress, sidebar GEP y comportamiento responsive.

### v9.13.4

GEP Editor Demo v9.13.4

- Se elimina la preview flotante al pasar el mouse por apartados ya existentes.
- El hover de un apartado existente sigue sincronizando la preview principal de la derecha.
- La preview flotante se mantiene únicamente en la biblioteca de “Añadir apartado”, para previsualizar tipos nuevos antes de insertarlos.

### v9.13.5

GEP Editor Demo v9.13.5

- El menú lateral GEP deja de tener desplazamiento vertical propio.
- La rueda del ratón sobre el menú ya no mueve la navegación lateral.
- Ajuste automático de alturas del menú para ventanas de navegador más bajas.
- Se mantiene el acceso flotante al menú nativo de WordPress.

### v9.13.6

GEP Editor Demo v9.13.6

- Preview principal más ancha para recuperar el peso visual del diseño seleccionado.
- Altura máxima de preview reducida; el contenido largo usa scroll interno.
- Tipografía de las previews ampliada en Inicio, Contacto, Docencia, Proyectos, Publicaciones, Comunicaciones y Noticias.
- Comunicaciones/Noticias dejan de forzar una preview artificialmente alta.
- Nueva pantalla de carga GEP con árbol en crecimiento para ocultar la construcción progresiva del editor.
- La pantalla de carga tiene salida de seguridad si algún recurso externo tarda o falla.

### v9.13.7

GEP Editor Demo v9.13.7

- Ajuste visual al mockup aprobado: editor y preview casi 50/50.
- Preview más alta, con scroll interno y texto más legible.
- Encabezado público MapBiomas/GEP incorporado a todas las previews.
- Cabecera de preview simplificada como “Vista previa de la página”.
- Tarjetas de secciones con mayor jerarquía y espaciado.
- Apartado abierto con mini-preview más grande y resumen editorial legible.
- Hero de Inicio resume Título, Descripción, Botones y Etiquetas en vez de campos genéricos.
- Barra superior presentada como Estado de la página.

### v9.14.0

GEP Editor Demo v9.14.0

- El apartado Video ahora puede seleccionar archivos directamente desde la Mediateca de WordPress.
- La Mediateca se abre filtrada por videos y guarda la URL real del archivo, evitando usar la página de adjunto.
- Vista previa inmediata del video dentro del editor y en la preview de la página.
- Sigue aceptando enlaces de YouTube y Vimeo.
- Soporte ampliado para MP4, M4V, WebM, OGV/OGG y MOV.
- Botón para quitar/reemplazar el video sin eliminar el apartado.

### v9.15.0

GEP Editor Demo v9.15.0

- Las cabeceras pueden usar video además de imagen.
- Selector de video desde la Mediateca de WordPress en Inicio, Quiénes somos, Docencia, Proyectos, Publicaciones, Comunicaciones, Noticias y Contacto.
- El video tiene prioridad visual y la imagen queda como poster/respaldo.
- Preview del editor actualizada en tiempo real.
- Soporte de archivos de WordPress y enlaces YouTube/Vimeo.

### v9.8.1

GEP Editor Demo · v9.8.1

CAMBIOS
- Proyectos incorpora identificador estable de ficha (slug), generado automáticamente cuando está vacío.
- La galería deja de ser un textarea reservado y pasa a usar la Biblioteca multimedia de WordPress.
- Permite seleccionar varias imágenes, quitar imágenes, reordenarlas y elegir cualquiera como imagen principal.
- La galería se guarda dentro del JSON existente de proyectos; no se crean nuevas tablas.
- Se mantiene compatibilidad con todos los proyectos guardados en versiones anteriores.
- Validación añadida para URL de ficha personalizada.
- Historial/rollback sigue incluyendo estos cambios porque el JSON de Proyectos ya forma parte de los metadatos GEP.

INSTALACIÓN
Subir el ZIP desde Plugins → Añadir plugin → Subir plugin y reemplazar la versión instalada.
No requiere SQL ni migración manual.
Al guardar Proyectos por primera vez en esta versión, los identificadores estables se persistirán automáticamente.

### v9.8.2

GEP Editor Demo v9.8.2

- Añade contenido real inicial a Comunicaciones y Noticias.
- Fuentes base: MapBiomas Chile y Universidad de Chile.
- 5 registros iniciales en Comunicaciones: 2 prensa, 1 evento y 2 videos.
- 4 registros iniciales en Noticias: 3 noticias y 1 entrevista/reportaje.
- Sembrado seguro: solo se ejecuta si la sección no tiene records_json guardado.
- No se sobrescribe contenido existente.
- No requiere SQL ni migración manual.

### v9.9.0

GEP Editor Demo v9.9.0

PROYECTOS
- Añade tres iniciativas actuales de MapBiomas Chile únicamente cuando no existe ningún proyecto vigente guardado.
- No sobreescribe proyectos vigentes definidos por el usuario.
- Incluye nombre, estado, período, descripción, institución, responsable y enlace.

PERSONAS
- Selector rápido de grupo en cada fila.
- Movimiento entre grupos sin abrir la ficha individual.
- Mejora visual de bloques, contadores y gestor de grupos.

DOCENCIA
- Completa Cursos y talleres con dos materiales históricos del GEP cuando esa colección está vacía.
- Mantiene imágenes editables por curso y compatibilidad con datos anteriores.

COMPATIBILIDAD
- Sin SQL ni migraciones manuales.
- Compatible con historial y metadatos existentes.

### v9.9.1

GEP Editor Demo v9.9.1

- Añade selector de imagen hero a Inicio, Quiénes somos, Docencia, Proyectos, Publicaciones y Contacto.
- Comunicaciones y Noticias ya disponían de cabecera editable.
- Mantiene fondos predeterminados si no se elige una imagen.
