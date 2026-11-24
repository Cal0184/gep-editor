GEP Editor v10.0.0 (versión definitiva de producción)

Editor guiado para Inicio, Quiénes somos, Contacto, Docencia, Proyectos, Servicios, Publicaciones, Comunicaciones y Noticias del sitio del Laboratorio GEP.
Cambios: CHANGELOG.md. Manuales (usuario, técnico, instalación, respaldo): carpeta «documentacion» del paquete de entrega y del repositorio, no se suben al servidor.

--- Notas históricas de versiones beta ---
GEP Editor Beta v9.25.0

Estado: Beta. Editor guiado para Inicio, Quiénes somos, Contacto, Docencia, Proyectos, Servicios, Publicaciones, Comunicaciones y Noticias.

Novedades de v9.18.1: "Ver página real" y las vistas previas llevan a /GEP_LEP/.
Novedades de v9.19.0 (detalle en CHANGELOG.md): respaldo completo del contenido (descargar y restaurar), rol Editor GEP y corrección de comillas y saltos de línea que vaciaban listas al guardar.
Novedades de v9.20.0: editor y página coinciden (Quiénes somos, Docencia, Proyectos, Comunicaciones, Noticias) e imagen en Mensaje de paisaje.
Novedades de v9.21.0: importar publicaciones por DOI, pantalla Descripción de imágenes (texto alternativo) y datos estructurados para Google (JSON-LD).
Funciona con el sitio GEP_LEP v9.21.0 (archivos nuevos includes/image-alt.php e includes/structured-data.php; cambian header.php, wp-bootstrap.php y las páginas con imágenes).

Novedades de v9.25.0 (detalle en CHANGELOG.md): ir al apartado recién creado, editor visual de botones, título y descripción opcionales, encuadre de la foto de Mensaje de paisaje y video de portada más rápido en iPhone. Funciona con el sitio GEP_LEP v9.25.0 (archivo nuevo includes/landscape-frame.php; cambian index.php, includes/hero-media.php, assets/js/site.js y assets/css/style.css).

Criterios de v9.16.0:
- La versión del plugin y de sus assets se controla desde GEP_EDITOR_VERSION.
- No se crean páginas ni se reinyecta contenido provisional al entrar al administrador.
- Los datos provisionales se conservan como respaldo editable, pero una eliminación del usuario se respeta.
- Las imágenes de contenido se seleccionan desde la Mediateca de WordPress; no se fuerzan imágenes locales como respaldo editorial.
- Las rutas públicas ya no dependen del nombre /wordpress/: se resuelven desde la ubicación de WordPress y admiten GEP_EDITOR_PUBLIC_BASE_URL/filtro.
- El CSS visual principal usa el nombre estable assets/editor.css.
- El historial de cambios se concentra en CHANGELOG.md.

Incluye editores guiados para Inicio, Quiénes somos, Contacto, Docencia, Proyectos, Publicaciones, Comunicaciones y Noticias.

Base heredada de v9.2:
- el editor abre en Inicio por defecto
- todos los apartados principales comienzan contraídos
- logo GEP integrado en la cabecera y en el menú lateral de WordPress
- CSS y JavaScript externos y cacheables
- Especialización editable para integrantes de Personas
- estados históricos D = en desarrollo y T = terminado
- recuperación de especializaciones y años históricos conocidos

Cambios v9.3:
- el color de selección/preview pasa de verde a azul para no confundirse con el color institucional de la web
- la preview de Quiénes somos muestra el número de la zona activa (Zona 1, Zona 2, etc.)
- cada apartado dentro de Personas y grupos del directorio se puede contraer y expandir individualmente
- los apartados internos del directorio también comienzan contraídos
- Contraer todo / Expandir todo controla también los apartados internos del directorio
- Becarios permite editar Año / período de forma visible
- si un apartado Becarios antiguo estaba creado como tipo Personas, también recibe un campo Año / período compatible
- los años históricos conocidos se recuperan automáticamente cuando faltan
- Estudiantes de postgrado muestra un campo claro Programa / subapartado
- los programas (por ejemplo Doctorado CSAV o Magíster MASCN) se ven en el resumen del editor y separados del título principal en la preview
- se mantiene la clave histórica status para años de Becarios creados como Estados / tesis, evitando romper la plantilla pública

Compatibilidad:
- el JSON anterior sigue siendo válido
- specialization y year son claves opcionales
- los apartados Estados / tesis continúan usando group + status + name + text
- no requiere migración manual de base de datos

Cambios v9.3.1:
- Corrige el icono de Contenido GEP en el menú lateral de WordPress.
- Usa un icono dedicado de 20 x 20 px para evitar que el logo invada otros elementos del menú.
- Mantiene el logo GEP de mayor tamaño dentro de la cabecera del editor.


Cambios v9.4.0:
- Quiénes somos pasa a ser el patrón visual de todas las previews.
- Inicio, Contacto, Docencia, Proyectos y Publicaciones muestran Zona N en la cabecera.
- El título y la explicación de la preview siguen automáticamente el apartado seleccionado.
- Todos los botones PC / Tablet / Móvil comparten exactamente el estilo de Quiénes somos.
- Todas las zonas activas usan el mismo marco azul, separado del verde institucional.
- Las previews largas tienen scroll propio para que el panel sticky siga visible al bajar por el editor.
- Al seleccionar una zona, la preview se desplaza internamente hasta el bloque correspondiente.


Cambios v9.4.1:
- Todas las pestañas usan el mismo ancho de preview que Contacto en escritorio.
- El reparto editor/preview queda unificado para Inicio, Quiénes somos, Docencia, Proyectos, Publicaciones y Contacto.
- La cabecera de preview se compactó: se conserva el icono, Zona N, nombre del bloque y PC/Tablet/Móvil.
- Se eliminó “Preview en vivo ·” y el texto descriptivo secundario de la cabecera.
- Se unificaron padding, comportamiento responsive y ancho máximo de las previews.


Cambios v9.5.1:
- Historial visual simplificado: se eliminaron tarjetas y mensajes decorativos que no aportaban a la tarea.
- Encabezado compacto con solo métricas útiles: versiones, editores y protegidas.
- Rollback con estados claros: la versión actual ya no muestra un botón deshabilitado ambiguo.
- Las versiones históricas válidas muestran un botón de restauración activo y el motivo queda claramente marcado como opcional.
- Las versiones con integridad inválida explican por qué no pueden restaurarse.

Cambios v9.5.0:
- Nuevo apartado Contenido GEP → Historial de cambios.
- Cada guardado de Inicio, Quiénes somos, Docencia, Proyectos, Publicaciones y Contacto crea una revisión recuperable.
- Punto inicial automático por sección antes del primer cambio registrado.
- Rollback completo de una versión, con verificación de integridad SHA-256.
- Antes de cada rollback se crea un punto de seguridad automático para poder deshacer también la restauración.
- Diferencias visuales campo por campo con vista Antes / Después.
- Las imágenes modificadas muestran miniatura y URL en el diff.
- Filtros por sección, acción, usuario, fecha y texto buscado.
- Comparador entre dos versiones de una misma sección.
- Versionado independiente por sección (v1, v2, v3...).
- Indicador de qué versión coincide con el estado actual.
- Resumen del impacto del cambio: texto, imágenes, enlaces y estructura.
- Notas editables para documentar el motivo o contexto de una versión.
- Versiones protegidas que no se eliminan durante la limpieza automática.
- Exportación de cualquier versión como JSON con hash de integridad.
- Retención automática de 100 versiones normales por sección, más todas las versiones protegidas.
- Panel de actividad por sección y estadísticas rápidas del historial.
- Ayuda actualizada con flujo de historial y rollback.

Arquitectura del historial:
- Las revisiones se guardan como registros internos no públicos (post type gep_change).
- Los snapshots se comprimen cuando zlib está disponible.
- El historial solo captura metadatos gep_* administrados por el plugin.
- No crea rutas públicas ni cambia la estructura visual del sitio.
- No requiere migración manual de base de datos.


Cambios v9.5.2:
- Rework completo de Ayuda: interfaz más compacta y visual.
- Nuevo acceso directo a Editor e Historial.
- Tutorial reducido a 4 pasos y referencia rápida de controles.
- Guía visual completa del Historial: diferencias, comparación, rollback seguro, estado actual, filtros, notas, protección, exportación JSON, SHA-256, retención y tipos de versión.
- Secciones de listas y dudas rápidas simplificadas para reducir texto.



Cambios v9.8.0:
- Publicaciones reorganizadas en: Libros, Capítulos de libro, Artículos, Congresos y Otros; “Todos” conserva los 93 registros.
- Compatibilidad automática con las categorías históricas Internacionales, Nacionales, Libros y Materiales.
- Docencia incorpora imagen opcional por curso, usa la etiqueta “Sigla” y separa Pregrado, Postgrado, Diplomado y Cursos/talleres.
- Proyectos incorpora estado Vigente/Cerrado y campos base para ficha: imagen, institución, responsable, descripción ampliada, galería y URL de ficha.
- Nuevas secciones administrables Comunicaciones (Prensa, Eventos, Videos) y Noticias (Entrevistas, Noticias).
- Comunicaciones y Noticias participan también del Historial de cambios y rollback.

Cambios v9.6.0:
- Gestor de grupos dentro de apartados Personas y Estados / tesis.
- Los grupos se pueden crear, renombrar, eliminar y reordenar sin repetir el nombre en cada ficha.
- Personas agrupadas visualmente dentro del editor, con contador por grupo.
- Movimiento directo entre grupos mediante arrastrar y soltar.
- Selector de grupo dentro de la ficha individual para mover una persona sin arrastrar.
- Botón “Añadir persona” dentro de cada grupo para crear registros ya asignados.
- Grupo “Sin grupo” automático para registros pendientes de clasificar.
- Los grupos antiguos se detectan desde la clave `group` existente y se incorporan sin migración manual.
- Se conserva `group` en cada persona para mantener compatibilidad con la plantilla pública actual.
- El orden de grupos y grupos vacíos se guarda en el JSON del directorio y queda incluido en Historial de cambios.

Cambios v9.8.0:
- Corrige el formulario de Comunicaciones y Noticias en ventanas estrechas: etiquetas y campos ya no se pisan ni quedan comprimidos.
- Los campos del encabezado pasan a una sola columna cuando el ancho disponible es reducido.
- Comunicaciones parte con tres plantillas administrativas: Prensa, Evento y Video.
- Noticias parte con dos plantillas administrativas: Entrevista y Noticia.
- Las plantillas son editables/eliminables y no aparecen en la web pública hasta guardar.


- v9.8.0: Comunicaciones y Noticias reciben preview por zonas y dispositivos, cabecera con imagen persistente y formularios más claros.
- Proyectos: ficha individual simple disponible con texto e imagen/placeholder; se mantiene preparada para ampliación posterior.


Cambios v9.8.1:
- Navegación pública reorganizada para ubicar Quiénes somos antes de Docencia.
- Proyectos incorpora identificador estable para las fichas individuales, manteniendo compatibilidad con las URLs numéricas antiguas.
- La galería de proyecto deja de ser un campo reservado: ahora permite seleccionar varias imágenes desde la biblioteca multimedia, eliminar, reordenar y marcar una como imagen principal.
- La ficha individual de proyecto se amplía con estado, período, descripción, institución, responsable, galería y resultados/enlaces.
- Las tarjetas de proyectos vigentes son completamente clickeables y mantienen compatibilidad con fichas personalizadas externas.
- No requiere migración manual de base de datos; los identificadores se generan y persisten en el siguiente guardado del editor.


Cambios v9.8.2:
- Comunicaciones y Noticias reciben contenido inicial real basado en publicaciones oficiales de MapBiomas Chile y Universidad de Chile.
- Comunicaciones incluye prensa, eventos y videos con fechas, fuente, descripción y enlace.
- Noticias incluye novedades entrevistas/reportajes vinculados a MapBiomas Chile y al GEP.
- La actualización solo rellena estas secciones cuando no existen registros guardados; nunca sobreescribe contenido del usuario.
- Las imágenes quedan vacías de forma intencional para evitar hotlinking; pueden añadirse desde la biblioteca de medios de WordPress.


Cambios v9.9.0:
- Proyectos recibe contenido inicial vigente basado en iniciativas MapBiomas Chile cuando no existe ningún proyecto vigente guardado.
- Se mantienen intactas las instalaciones que ya tengan proyectos vigentes definidos.
- Docencia completa Cursos y talleres con dos materiales históricos cuando la colección está vacía.
- Personas incorpora selector rápido de grupo directamente en cada fila.
- Mover personas entre grupos ya no obliga a abrir el editor individual.
- Se mantienen drag & drop, gestor de grupos, historial y compatibilidad con JSON anterior.

Cambios v9.10.0:
- Panel de salud del contenido con conteo, cobertura de imágenes y estado general.
- Indicadores de contenido incompleto y avisos cuando falta imagen.
- Mejor feedback al guardar y toast de confirmación.
- Responsive del editor reforzado para escritorio amplio, portátil, tablet y móvil.
- Previews protegidas frente a desbordes y botones/textos largos.
- Datos de demostración seguros: proyectos vigentes, noticias, comunicaciones, docencia e imágenes de equipo, solo en campos vacíos.
- Resolución correcta de imágenes locales dentro de previews del admin.
- No requiere SQL ni migración manual.

v9.11.0 — Estructura modular
----------------------------
El editor incorpora una capa de “Apartados” en todas las páginas. Permite ordenar, retirar/restaurar y añadir bloques reutilizables sin modificar el PHP del plugin para cada cambio de estructura.

Los apartados nuevos disponibles son Texto, Imagen + texto, Tarjetas, Cifras, Video y Llamado / CTA. Los apartados históricos conservan sus formularios especializados; el botón Editar de la estructura lleva directamente a ese editor.

Para que esta estructura se aplique en las páginas PHP públicas, utilizar GEP_LEP v9.11.0 o posterior. La integración pública usa data-gep-section y do_action('wp_footer').


v9.11.1 — Estructura más limpia y preview al vuelo
- Los controles Editar / Eliminar / Restaurar quedan ocultos dentro de cada apartado y se muestran al desplegarlo.
- Los controles de estructura usan la paleta verde/neutra del GEP, sin acciones rojas permanentes.
- Al pasar el mouse por un apartado aparece una tarjeta flotante con una vista previa visual del bloque.
- La biblioteca “Añadir apartado” también muestra una vista previa de cada tipo al pasar el mouse.
- No requiere cambios adicionales en GEP_LEP: funciona con GEP_LEP v9.11.0 o posterior.


v9.11.2 — Orden sincronizado con la preview
- Arrastrar un apartado en Estructura de página reordena inmediatamente la preview principal de la derecha.
- El bloque movido se resalta y la preview se desplaza hacia su nueva posición.
- Añadir, ocultar/restaurar y editar apartados personalizados también se refleja en la preview sin guardar.


v9.12.0 — Editor unificado de apartados
- Estructura de página pasa a ser el único listado visible del editor.
- Al abrir un apartado se muestra una ficha visual con mini-preview, estado y resumen del contenido.
- Editar sección abre los formularios existentes dentro de la misma tarjeta, sin duplicar el listado debajo.
- Contacto, Docencia, Proyectos y Publicaciones agrupan sus subeditores relacionados dentro del apartado correspondiente.
- Los apartados nuevos también usan edición integrada.


v9.12.1 — Preview unificada por apartado
- Todas las páginas imitan el comportamiento de preview de Quiénes somos: un solo apartado visible a la vez.


v9.12.2 — Dos modos de preview
- Apartado: solo el bloque seleccionado.
- General: página completa en el orden actual.

v9.12.3 — Imágenes completas en preview
- Las imágenes hero/cabecera se muestran en las previews de todas las páginas, usando la imagen pública predeterminada cuando el campo está vacío.
- Las rutas relativas assets/... se resuelven contra la página pública y ya no desaparecen dentro del administrador.
- Inicio recupera la imagen hero y la franja de paisaje en preview.
- Quiénes somos muestra la cabecera y la imagen destacada opcional del directorio.
- Docencia muestra miniaturas de cursos y la imagen de Campos de formación.
- Comunicaciones y Noticias muestran también sus imágenes de respaldo en cabecera y tarjetas.
- Los apartados personalizados con imágenes usan el mismo resolver de rutas.



v9.12.4 — Corrección de imágenes en Quiénes somos
- La cabecera del directorio usa una URL pública absoluta para la fotografía predeterminada.
- La preview de Quiénes somos muestra la misma imagen de cabecera que la página real incluso si no hay una imagen personalizada guardada.
- Las mini-previews de la estructura heredan la cabecera ya renderizada.


v9.12.5 — Corrección de preview duplicada
- La vista previa flotante toma los bloques únicamente desde la preview principal.
- Las mini-previews ya no conservan identificadores data-* de los bloques reales, evitando clonación recursiva.
- Se mantiene el selector Apartado / General y el comportamiento de hover existente.


v9.13.0 — Nueva identidad visual
- Interfaz lateral estilo MapBiomas/GEP.
- Barra superior de usuario y ayuda.
- Encabezado, estado, acciones, estructura y preview rediseñados.
- Conserva todas las funciones v9.12.x sin cambios de lógica.
