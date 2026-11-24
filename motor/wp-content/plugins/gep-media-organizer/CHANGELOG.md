# GEP Media Organizer

## 1.6.4 (2026-10-27)
- Se quitó "Ordenar fotos del GEP" de Medios > Carpetas GEP: el equipo no lo usaba y confundía. Las carpetas, el selector y "Seleccionar varios" siguen igual.

## 1.6.3 (2026-10-27)
- Nuevo botón "Seleccionar varios" en el selector de imágenes, junto a Renombrar, Mover carpeta y Eliminar. Con él activo, cada clic marca o desmarca una foto; luego "Mover N archivos a…" las mueve juntas. "Listo" vuelve a la selección normal de una sola imagen.

## 1.6.2 (2026-10-27)
- Arreglo: en el selector de imágenes las carpetas a veces necesitaban 2 o más clics. La lista de carpetas se redibujaba sola unas 5 veces por segundo y se perdía el clic. Ahora solo se redibuja cuando cambian las carpetas, y el selector queda más liviano.

## 1.6.1 (2026-10-23)
- Arreglo: en WordPress 7.x el selector de imágenes se veía roto (imágenes bajo la columna Carpetas, buscador y filtro cortados, doble barra de desplazamiento). Ahora solo se desplaza el contenido a la derecha y se respeta el diseño propio de WordPress en 6.x y 7.x.
- Arreglo: la última columna de imágenes ya no queda tapada por el panel de detalles de la derecha.
- Arreglo: en tablet/celular el botón "Carpetas" quedaba oculto; ahora se ve, y la lista se cierra sola al elegir una carpeta.

## 1.6.0 (2026-10-22)
- Arreglo: el rol "Editor GEP" ya puede mover a carpetas fotos subidas por otras personas (antes quedaban "omitidas").
- Lo que se sube con una carpeta abierta queda guardado en esa carpeta (página Carpetas GEP y selector de imágenes).
- La foto recién subida aparece de inmediato en la carpeta abierta.
- El selector de imágenes recuerda la última carpeta usada mientras sigues en la misma pantalla.
- Campo "Carpeta GEP" en la ficha de cada archivo (selector de imágenes y Editar medio).
- Botón "Ordenar fotos del GEP": revisa las páginas del GEP y pone en una carpeta por página las fotos y videos que usa el sitio. Solo toca archivos en Sin clasificar. Requiere GEP Editor 9.21 o superior.
- "Mover carpeta" también en la página Carpetas GEP.
- Tutorial "Encontrar fotos por carpeta" en Contenido GEP > Ayuda.
- Más liviano: ya no carga la Biblioteca de medios en todas las pantallas del escritorio, solo donde se abre el selector.
- Archivos CSS/JS versionados por fecha de modificación (evita caché vieja).
- Arreglo: el aviso "N archivos organizados" se borraba al instante.
