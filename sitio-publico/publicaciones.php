<?php
/**
 * GEP · Publicaciones
 * Compatible con sitio PHP externo + WordPress detectado automáticamente.
 * Lee GEP Editor Beta v9.7 cuando está disponible y conserva el contenido original como respaldo.
 */
require_once __DIR__ . '/includes/wp-bootstrap.php';
gep_site_bootstrap_wordpress();
function gep_publications_h($value){return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function gep_publications_url($value){$value=trim((string)$value);if($value==='')return '';return function_exists('esc_url_raw')?esc_url_raw($value):filter_var($value,FILTER_SANITIZE_URL);}
$gep_publications_defaults=json_decode(<<<'JSON'
{
  "hero_eyebrow": "Producción científica",
  "hero_title": "Publicaciones",
  "hero_text": "Publicaciones, capítulos, libros, congresos y materiales que documentan la trayectoria académica del laboratorio.",
  "hero_image": "",
  "hero_video": "",
  "nav_search": "Buscar y filtrar",
  "nav_internacionales": "Internacionales",
  "nav_nacionales": "Nacionales",
  "nav_libros": "Libros",
  "nav_congresos": "Congresos",
  "explorer_title": "Explorar publicaciones",
  "note": "",
  "sections": {
    "libros-free": {
      "label": "Materiales",
      "eyebrow": "Materiales abiertos",
      "title": "Libros y recursos de consulta"
    },
    "internacionales": {
      "label": "Internacionales",
      "eyebrow": "Revistas",
      "title": "Publicaciones internacionales"
    },
    "nacionales": {
      "label": "Nacionales",
      "eyebrow": "Revistas",
      "title": "Publicaciones nacionales"
    },
    "libros": {
      "label": "Libros y capítulos",
      "eyebrow": "Producción editorial",
      "title": "Libros, capítulos y manuales"
    },
    "congresos": {
      "label": "Congresos",
      "eyebrow": "Divulgación académica",
      "title": "Congresos y encuentros científicos"
    }
  },
  "records": [
    {
      "category": "libros-free",
      "year": "2014",
      "title": "Biodiversidad de Arica y Parinacota",
      "text": "Recurso gratuito enlazado desde el sitio histórico.",
      "url": "https://www.gep.uchile.cl/Biodiversidad/img/Biodiversidad%20Terrestre%20de%20la%20Regi%C3%B3n%20de%20Arica%20y%20Parinacota.pdf",
      "button": "Abrir PDF ↗"
    },
    {
      "category": "libros-free",
      "year": "2008",
      "title": "Curso de ArcGIS 9.2",
      "text": "Recurso gratuito enlazado desde el sitio histórico.",
      "url": "https://www.gep.uchile.cl/Publicaciones/Curso%20de%20ArcGIS%209.2%20-%20Hern%C3%A1ndez%20y%20Montaner%202008.pdf",
      "button": "Abrir PDF ↗"
    },
    {
      "category": "libros-free",
      "year": "2011",
      "title": "Tratamiento Digital de Imágenes",
      "text": "Recurso gratuito enlazado desde el sitio histórico.",
      "url": "https://www.gep.uchile.cl/Publicaciones/Tratamiento%20Digital%20de%20Im%C3%A1genes%20-%20Jaime%20Hern%C3%A1ndez%202011.pdf",
      "button": "Abrir PDF ↗"
    },
    {
      "category": "libros-free",
      "year": "2000",
      "title": "Evaluación y Monitoreo de la Flora y la Vegetación",
      "text": "Recurso gratuito enlazado desde el sitio histórico.",
      "url": "https://www.gep.uchile.cl/Publicaciones/Manual%20de%20M%C3%A9todos%20y%20Criterios%20para%20la%20Evaluaci%C3%B3n%20y%20Monitoreo%20%20de%20la%20Flora%20y%20la%20Vegetaci%C3%B3n.pdf",
      "button": "Abrir PDF ↗"
    },
    {
      "category": "libros-free",
      "year": "2011",
      "title": "Tablas de biomasa aérea de pino",
      "text": "Recurso gratuito enlazado desde el sitio histórico.",
      "url": "https://www.gep.uchile.cl/Publicaciones/Tablas%20de%20estimaci%C3%B3n%20de%20biomasa%20a%C3%A9rea%20bruta%20en%20pie%20para%20plantaciones%20de%20Pino%20Insigne%20en%20Chile.pdf",
      "button": "Abrir PDF ↗"
    },
    {
      "category": "libros-free",
      "year": "2012",
      "title": "Tablas de biomasa aérea de eucalipto",
      "text": "Recurso gratuito enlazado desde el sitio histórico.",
      "url": "https://www.gep.uchile.cl/Publicaciones/Tabla%20de%20rendimiento%20en%20biomasa%20a%C3%A9rea%20en%20pie%20para%20plantaciones%20de%20Eucalyptus%20globulus%20en%20Chile.pdf",
      "button": "Abrir PDF ↗"
    },
    {
      "category": "internacionales",
      "year": "2016",
      "title": "",
      "text": "Miranda, A., Hernández, H.J., Bustamante, R., Díaz, M., González, L. & Altamirano, A. Regeneración natural y patrones de distribución espacial de la palma chilena Jubaea chilensis en los bosques mediterráneos de Chile central. Gayana Botánica 73(1): 26-35.",
      "url": "",
      "button": ""
    },
    {
      "category": "internacionales",
      "year": "2016",
      "title": "",
      "text": "Escobedo, F., Palmas-Perez, S., Dobbs, C., Gezan, S. & Hernández, H.J. Spatio-Temporal Changes in Structure for a Mediterranean Urban Forest: Santiago, Chile 2002 to 2014. Forest 7, 121. doi:10.3390/f7060121.",
      "url": "",
      "button": ""
    },
    {
      "category": "internacionales",
      "year": "2016",
      "title": "",
      "text": "Zhao, Y.; Feng, D.; Yua, L.; Wang, X.; Chen, Y.; Hernández, H.J.; Galleguillos, M.; Estades, C.; Biging, G.; Radke, J. & Gong, P. Detailed dynamic land cover mapping of Chile: accuracy improvement by integrating multi-seasonal land cover data. Remote Sensing of Environment 183, 170–185.",
      "url": "",
      "button": ""
    },
    {
      "category": "internacionales",
      "year": "2015",
      "title": "",
      "text": "Lopatin, J., Dolos, K., Hernández, H.J., Galleguillos, M., Fassnacht, F. Comparing generalized linear models and random forest to model vascular plant species richness using LiDAR data in a natural forest in central Chile. Remote Sensing of Environment, Vol. 73: 200–210.",
      "url": "",
      "button": ""
    },
    {
      "category": "internacionales",
      "year": "2015",
      "title": "",
      "text": "Ávalos, V. & Hernández, H.J. Distribution shifts of range-restricted Andean birds from Bolivia and southeastern Peru under climate change: representation in protected areas and conservation. Global Ecology and Conservation 4:459-469.",
      "url": "",
      "button": ""
    },
    {
      "category": "internacionales",
      "year": "2015",
      "title": "",
      "text": "Maack, J., Kattenborn, T., Fassnacht, F., Hernández, H.J., Corvalán, P. & Koch, B. Modeling forest biomass using Very-High-Resolution and photogrammetric predictors derived from data - Combining textural, spectral spaceborne stereo images. European Journal of Remote Sensing 48:245-261.",
      "url": "",
      "button": ""
    },
    {
      "category": "internacionales",
      "year": "2015",
      "title": "",
      "text": "Ceballos, A., Hernández, H.J., Corvalán, P. & Galleguillos, M. Comparison of Airborne LiDAR and Satellite Hyperspectral Remote Sensing to Estimate Vascular Plant Richness in Deciduous Mediterranean Forests of Central Chile. Remote Sensing 7, 2692-2714.",
      "url": "",
      "button": ""
    },
    {
      "category": "internacionales",
      "year": "2015",
      "title": "",
      "text": "Latifi, H., Fassnacht, F., Hartig, F., Bergere, C., Hernández, H.J., Corvalán, P. & Koch, B. Stratified aboveground forest biomass estimation by remote sensing data. International Journal of Applied Earth Observation and Geoinformation 38:229–241.",
      "url": "",
      "button": ""
    },
    {
      "category": "internacionales",
      "year": "2015",
      "title": "",
      "text": "Lopatin, J., Ceballos, A., Galleguillos, M., Hernández, H.J. Using a multi-structural object-based LiDAR approach to estimate vascular plant richness in Mediterranean forests with complex structure. Geoscience and Remote Sensing Letters 12(5):1008-1012.",
      "url": "",
      "button": ""
    },
    {
      "category": "internacionales",
      "year": "2014",
      "title": "",
      "text": "Pérez-Hernández, C.G., Vergara, P.M., Saura, S. & Hernández, H.J. Do corridors promote connectivity for bird-dispersed trees? The case of Persea lingue in Chilean fragmented landscapes. Landscape Ecology 30(1):77-90.",
      "url": "",
      "button": ""
    },
    {
      "category": "internacionales",
      "year": "2014",
      "title": "",
      "text": "Fassnacht, F., Hartig, F., Latifi, H., Berger, C., Hernández, H.J., Corvalán, P. & Koch, B. Importance of sample size, data type and prediction method for remote sensing-based estimations of aboveground forest biomass. Remote Sensing of Environment 154:102–114.",
      "url": "",
      "button": ""
    },
    {
      "category": "internacionales",
      "year": "2014",
      "title": "",
      "text": "Corvalán, C.P., Galleguillos, M. & Hernández, H.J. Presencia, abundancia y asociatividad de Citronella mucronata en bosques secundarios de Nothofagus obliqua en la precordillera de Curicó, Región del Maule, Chile. Bosque 35(3):269-278.",
      "url": "",
      "button": ""
    },
    {
      "category": "internacionales",
      "year": "2014",
      "title": "",
      "text": "Grez, A., Zaviezo, T., Hernández, J., Rodríguez-San Pedro, A. & Acuña, P. The heterogeneity and composition of agricultural landscapes influence native and exotic coccinellids in alfalfa fields. Agricultural and Forest Entomology.",
      "url": "",
      "button": ""
    },
    {
      "category": "internacionales",
      "year": "2014",
      "title": "",
      "text": "Mattar, C., Hernández, J., Santamaría-Artigas, A., Durán-Alarcón, C., Olivera-Guerra, M., Inzunza, D., Tapia, D. & Escobar-Lavín, E. A first in-flight absolute calibration of the Chilean Earth Observation Satellite. ISPRS Journal of Photogrammetry and Remote Sensing 92:16–25.",
      "url": "",
      "button": ""
    },
    {
      "category": "internacionales",
      "year": "2014",
      "title": "",
      "text": "Cortés, L., Hernández, J., Valencia, D. & Corvalán, P. Estimation of Above-Ground Forest Biomass Using Landsat ETM+, Aster GDEM and LiDAR. Forest Research 3:117.",
      "url": "",
      "button": ""
    },
    {
      "category": "internacionales",
      "year": "2013",
      "title": "",
      "text": "Hernández, J.; Nuñez, I.; Bacigalupo, A. & Cattan, P. Modeling the spatial distribution of Chagas disease vectors using environmental variables and people's knowledge. International Journal of Health Geographics 12:29.",
      "url": "",
      "button": ""
    },
    {
      "category": "internacionales",
      "year": "2011",
      "title": "",
      "text": "Gallardo, C., Hernández, J., Diniz-Filho, J., Palma, J. & Méndez, M. Relationship between the genetic structure of the Andean toad Rhinella spinulosa (Anura: Bufonidae) and the northern Chile landscape (21°–24° S). Revista Chilena de Historia Natural 84(3):391-406.",
      "url": "",
      "button": ""
    },
    {
      "category": "internacionales",
      "year": "2011",
      "title": "",
      "text": "Dobbs, C., Hernández, J. & Escobedo, F. Above ground biomass and leaf area equations for urban trees of two communes in central Chile. Bosque 32(3):287-296.",
      "url": "",
      "button": ""
    },
    {
      "category": "internacionales",
      "year": "2011",
      "title": "",
      "text": "Acuña, M.P. & Estades, C.F. Plantation clearcut size and the persistence of early-successional wildlife populations. Biological Conservation 144(5):1577-1584.",
      "url": "",
      "button": ""
    },
    {
      "category": "internacionales",
      "year": "2010",
      "title": "",
      "text": "Emery, X. & Hernández, H.J. A computer package for modeling and simulating regionalized count variables. Computers & Geosciences 36(1):24-33.",
      "url": "",
      "button": ""
    },
    {
      "category": "internacionales",
      "year": "2009",
      "title": "",
      "text": "Hernández, H.J. & Emery, X. A geostatistical approach to optimize sampling designs for local forest inventories. Canadian Journal of Forest Research 39(8):1465–1474.",
      "url": "",
      "button": ""
    },
    {
      "category": "internacionales",
      "year": "2006",
      "title": "",
      "text": "Escobedo, F., Nowak, D., Wagner, D., De la Maza, C., Rodriguez, M., Crane, D. & Hernández, J. The socioeconomics and management of Santiago de Chile’s public urban forest. Urban Forestry & Urban Greening 4:105-114.",
      "url": "",
      "button": ""
    },
    {
      "category": "internacionales",
      "year": "2002",
      "title": "",
      "text": "Luz de la Maza, C.; Rodríguez, M.; Bown, H.; Hernández, J. & Escobedo, F. Vegetation diversity in the Santiago de Chile urban ecosystem. Arboricultural Journal 26(4):347-357.",
      "url": "",
      "button": ""
    },
    {
      "category": "nacionales",
      "year": "2014",
      "title": "",
      "text": "Valencia, D., Hernández, H.J., Fassnacht, F.E., Cortez, L., Lopatín, J. & Corvalán, P. Estimation of aerial biomass using discrete-wave LiDAR data in combination with different vegetation indices in plantations of Pinus radiata (D. Don), Región del Maule, Chile. Sustainability, Agri, Food and Environmental Research 2(3):30–49.",
      "url": "",
      "button": ""
    },
    {
      "category": "nacionales",
      "year": "2013",
      "title": "",
      "text": "Pavéz, M.I., Ferrando, F. & Hernández, J. Río Mapocho Tramo Inferior siglo XXI: avances para la definición y manejo de un polígono multifacético. Revista de Urbanismo N°28:79-93.",
      "url": "",
      "button": ""
    },
    {
      "category": "nacionales",
      "year": "2010",
      "title": "",
      "text": "Hernández, H.J. y Gutierrez, M. Patrones espaciales de expansión urbana de Santiago de Chile, período 1975-2003. Revista de Urbanismo.",
      "url": "",
      "button": ""
    },
    {
      "category": "nacionales",
      "year": "2008",
      "title": "",
      "text": "Hernández, H.J. La situación del arbolado urbano en Santiago. Revista de Urbanismo N°18, FAU, Universidad de Chile.",
      "url": "",
      "button": ""
    },
    {
      "category": "nacionales",
      "year": "2007",
      "title": "",
      "text": "Hernández, H.J. La situación del arbolado urbano de Santiago. Revista Ambiente Forestal 2(2).",
      "url": "",
      "button": ""
    },
    {
      "category": "nacionales",
      "year": "2004",
      "title": "",
      "text": "Lastra, O., Corvalán, P., Hernández, J. & Rodríguez, M. Evaluación nutricional de plantaciones de Pinus radiata D.Don en la VI Región. Boletín Nº20. Simposio de Residuos Orgánicos y su Uso en Sistemas Agroforestales, Sociedad Chilena de las Ciencias del Suelo, Universidad de la Frontera, pp.381-387.",
      "url": "",
      "button": ""
    },
    {
      "category": "nacionales",
      "year": "2004",
      "title": "",
      "text": "Hernández, J. & Corvalán, P. Conceptos y métodos para identificación y delimitación de árboles para pino radiata a partir de imágenes aéreas. Revista Mundo Forestal Nº3:42-44.",
      "url": "",
      "button": ""
    },
    {
      "category": "nacionales",
      "year": "2001",
      "title": "",
      "text": "Faúndez, L., Girón, G. & Hernández, J. Poblational caracterization of A. compacta Phil. (Llareta) on Cerros Pajonal-Llaretas System, El Loa, II Región, Chile. Gayana Botánica 58(1):47-101.",
      "url": "",
      "button": ""
    },
    {
      "category": "nacionales",
      "year": "2000",
      "title": "",
      "text": "Hernández, J. & Corvalán, P. Uso de variogramas para la determinación del tamaño medio de las copas de pinos. Ciencias Forestales 14(1-2):3-18.",
      "url": "",
      "button": ""
    },
    {
      "category": "nacionales",
      "year": "1999",
      "title": "",
      "text": "Hernández, J. Medición de fotografías aéreas usando fractales. Quebracho Nº7:61-66.",
      "url": "",
      "button": ""
    },
    {
      "category": "nacionales",
      "year": "1999",
      "title": "",
      "text": "Corvalán, P. & Hernández, J. Predicción del diámetro normal de individuos de pino insigne usando atributos locales de imágenes aéreas digitales. XIII Silvotecna, Concepción.",
      "url": "",
      "button": ""
    },
    {
      "category": "libros",
      "year": "2014",
      "title": "",
      "text": "Hernández, H.J. & Fuentes, T. La Región de Arica y Parinacota. En: Biodiversidad Terrestre de la Región de Arica y Parinacota, pp.14-21. Maval Ltda., Santiago, 412 pp.",
      "url": "",
      "button": ""
    },
    {
      "category": "libros",
      "year": "2014",
      "title": "",
      "text": "Hernández, H.J. & Acuña, M.P. Ambientes de la Región de Arica y Parinacota. En: Biodiversidad Terrestre de la Región de Arica y Parinacota, pp.114-121.",
      "url": "",
      "button": ""
    },
    {
      "category": "libros",
      "year": "2014",
      "title": "",
      "text": "Hernández, H.J., Estades, C., Faúndez, L. & Fuentes, T. Desafíos y Oportunidades. En: Biodiversidad Terrestre de la Región de Arica y Parinacota, pp.344-353.",
      "url": "",
      "button": ""
    },
    {
      "category": "libros",
      "year": "2014",
      "title": "",
      "text": "Lobos, G., Hernández, J. & Jaksic, F. Antropización del paisaje e invasión de vertebrados acuáticos exóticos. En: Invasiones Biológicas en Chile, Ediciones UC, pp.393–410.",
      "url": "",
      "button": ""
    },
    {
      "category": "libros",
      "year": "2013",
      "title": "",
      "text": "Hernández, J., Corvalán, P., Valencia, D., Cortés, L. & Acuña, M.P. Tecnología LiDAR en Evaluación y Monitoreo de Recursos Forestales. En: Geoinformación para el Ordenamiento Territorial, pp.158-167.",
      "url": "",
      "button": ""
    },
    {
      "category": "libros",
      "year": "2013",
      "title": "",
      "text": "De la Maza, C., Cerda, C., Hernández, H.J. y Rodríguez, M. Chile. En: Ecología Urbana: Experiencias en América Latina.",
      "url": "",
      "button": ""
    },
    {
      "category": "libros",
      "year": "2012",
      "title": "",
      "text": "Hernández, J., Corvalán, P., Emery, X., Peña, K. & Donoso, S. Geostatistical Estimation of Biomass Stock in Chilean Native Forests and Plantations. En: Remote Sensing of Biomass: Principles and Applications.",
      "url": "",
      "button": ""
    },
    {
      "category": "libros",
      "year": "2010",
      "title": "",
      "text": "Lobos, G., Hernández, H.J., Méndez, M., Cattan, P., Diniz-Filho, J. y Gallardo, C. Atlas de Biodiversidad de Anfibios y Reptiles de la Región Metropolitana de Chile: Una herramienta para la gestión de los recursos naturales. FPA, Ministerio del Medio Ambiente, 74 p.",
      "url": "",
      "button": ""
    },
    {
      "category": "libros",
      "year": "2009",
      "title": "",
      "text": "Hernández, H.J. & Montaner, D. Patrones de Respuesta Espectral (Cap. 3). En: Tecnologías Geoespaciales: Experiencias Aplicadas al Estudio y Gestión del Territorio.",
      "url": "",
      "button": ""
    },
    {
      "category": "libros",
      "year": "2008",
      "title": "",
      "text": "Hernández, H.J. & Montaner, D. Manual de ArcGIS 9.2. Tutoriales y base de datos. Publicaciones Docentes, Universidad de Chile, 101 págs.",
      "url": "",
      "button": ""
    },
    {
      "category": "libros",
      "year": "2007",
      "title": "",
      "text": "Hernández, P. Geomática Forestal. En: Biodiversidad: Manejo y Conservación de Recursos Forestales, pp.101-145.",
      "url": "",
      "button": ""
    },
    {
      "category": "libros",
      "year": "2007",
      "title": "",
      "text": "Hernández, J., Serra, M.T. & Araya, J. Manejo de Vegetación Urbana. En: Biodiversidad: Manejo y Conservación de Recursos Forestales, pp.693-719.",
      "url": "",
      "button": ""
    },
    {
      "category": "libros",
      "year": "2007",
      "title": "",
      "text": "Corvalán, P. & Hernández, J. Mediciones Forestales, Dendrometría. En: Biodiversidad: Manejo y Conservación de Recursos Forestales, pp.169-222.",
      "url": "",
      "button": ""
    },
    {
      "category": "libros",
      "year": "2007",
      "title": "",
      "text": "Corvalán, P. & Hernández, J. Dasometría. En: Biodiversidad: Manejo y Conservación de Recursos Forestales, pp.224-259.",
      "url": "",
      "button": ""
    },
    {
      "category": "libros",
      "year": "2006",
      "title": "",
      "text": "Velazquez, R., Hernández, J. & De la Maza, C. Evaluación de parches de áreas verdes en el paisaje urbano de Santiago de Chile: Aspectos de calidad ambiental. En actas del Segundo Seminario Internacional de Planificación Territorial y sus Efectos sobre Contaminación Ambiental.",
      "url": "",
      "button": ""
    },
    {
      "category": "libros",
      "year": "2005",
      "title": "",
      "text": "Aguilera, S., Rodriguez, M., Borie, G., Lastra, O., Hernández, J. & Corvalán, P. Desarrollo de Prácticas Sustentables de Aplicación de Biosólidos en Plantaciones Forestales. Pirámide Editions, Santiago de Chile, 205 p.",
      "url": "",
      "button": ""
    },
    {
      "category": "libros",
      "year": "2004",
      "title": "",
      "text": "Escobedo, F., Hernández, J., De la Maza, C.L., Rodríguez, M., Nowak, D. & Crane, D. Determinando los efectos del arbolado urbano sobre la calidad de aire. Publicaciones Misceláneas Nº5, Facultad de Cs. Forestales, U. de Chile, pp.19-25.",
      "url": "",
      "button": ""
    },
    {
      "category": "libros",
      "year": "2004",
      "title": "",
      "text": "Hernández, J., Bown, H., De la Maza, C.L. & Raby, D. La necesidad de inventariar el arbolado urbano: El caso de la Comuna de La Reina. Publicaciones Misceláneas Nº5, pp.26-49.",
      "url": "",
      "button": ""
    },
    {
      "category": "libros",
      "year": "2004",
      "title": "",
      "text": "Hernández, J., Escobedo, F., Pizarro, V. & De la Maza, C.L. Diagnóstico de la gestión municipal del arbolado urbano público en la ciudad de Santiago. Publicaciones Misceláneas Nº5, pp.141-164.",
      "url": "",
      "button": ""
    },
    {
      "category": "libros",
      "year": "2004",
      "title": "",
      "text": "Escobedo, F., Hernández, J., De la Maza, C.L., Rodríguez, M., Nowak, D. & Crane, D. El Arbolado urbano de Santiago de Chile. Publicaciones Misceláneas Nº5, pp.50-54.",
      "url": "",
      "button": ""
    },
    {
      "category": "libros",
      "year": "2004",
      "title": "",
      "text": "De la Maza, C.L., Hernández, J., Bown, H. & Rodríguez, M. Diversidad vegetacional en Santiago y su relación con niveles socioeconómicos. Publicaciones Misceláneas Nº5, pp.109-120.",
      "url": "",
      "button": ""
    },
    {
      "category": "libros",
      "year": "2004",
      "title": "",
      "text": "Lastra, O., Corvalán, P., Hernández, J. & Rodríguez, M. Evaluación nutricional de plantaciones de Pinus radiata D.Don en la VI Región. Boletín Nº20, Simposio Residuos Orgánicos y su uso en sistemas agroforestales, pp.381-387.",
      "url": "",
      "button": ""
    },
    {
      "category": "libros",
      "year": "2000",
      "title": "",
      "text": "Hernández, J. Manual de Métodos y Criterios para la Evaluación y Monitoreo de la Flora y la Vegetación. Manual para el Servicio Agrícola y Ganadero, Gobierno de Chile, 37 p.",
      "url": "",
      "button": ""
    },
    {
      "category": "libros",
      "year": "1999",
      "title": "",
      "text": "Hernández, J. Análisis de la variación espacial de pinares usando datos de campo, fotografías aéreas 1:6500 e imágenes SPOT HRV. Tesis Doctoral, Ingeniería de Montes, Universidad Politécnica de Madrid, España, 170 p.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2014",
      "title": "",
      "text": "Dobbs C., Hernández, H.J. & Cortés, L. Urbanization and its effect on carbon storage and diversity of the urban forest. MEDECOS XIII International Conference, Olmué, 6-9 Octubre 2014.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2014",
      "title": "",
      "text": "Bacigalupo A., Arroyo P., Hernández H.J. & Cattan P.E. Modelo predictivo a escala local de la distribución espacial de triatominos silvestres en Chile. III Encuentro Nacional de Investigación en Enfermedades Infecciosas y Medicina Tropical. Quito, Ecuador, 21-23 Julio 2014.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2014",
      "title": "",
      "text": "Corvalán P., Galleguillos M. & Hernández H.J. Impacto de la declinación de la precipitación en el crecimiento diametral del roble (Nothofagus obliqua) en la precordillera de Maule, Chile Central. International Expert Symposium Coping with Droughts, UNESCO, Santiago, 19-21 Noviembre 2014.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2014",
      "title": "",
      "text": "Fuentes-Castillo T., Moreira-Muñoz A. & Hernández, H.J. Distribución de especies de plantas vasculares pertenecientes a géneros endémicos del altiplano sudamericano y su representatividad en áreas silvestres protegidas en Chile. Puerto Varas, 24-28 Noviembre 2014.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2014",
      "title": "",
      "text": "Acuña M., Vukasovic M., Hernández H.J. & Estades C.F. Productividad primaria como factor modelador de la dinámica espacio-temporal de poblaciones de Gaviota de Franklin (Leucophaeus pipixcan) en Chile central. XI Congreso Chileno de Ornitología, La Serena, 13-17 Octubre 2014.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2014",
      "title": "",
      "text": "Vallejos G., Campos L., Garrido P., Hernández, H.J. & Gallardo, C. Primer registro de reproducción de Campephilus magellanicus en su límite norte de distribución, Chile central. XI Congreso Chileno de Ornitología, La Serena, 13-17 Octubre 2014.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2014",
      "title": "",
      "text": "Abarca, B.M., Magni, C.R. & Hernández, H.J. Modelación de la distribución potencial pasada, presente y futura de Quillaja saponaria Mol. basado en variables bioclimáticas. VI Congreso Chileno de Ciencias Forestales, Temuco, 9-11 Abril 2014.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2013",
      "title": "",
      "text": "Lopatin J., Hernández H.J., Galleguillos M. & Mancilla G. An empirical modeling of runoff in small watersheds using LiDAR data. AGU American Geophysical Union, San Francisco, USA, 9-13 Diciembre 2013.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2013",
      "title": "",
      "text": "Grez A.A., Zaviezo T., Rodríguez-San Pedro A., Hernández H.J. & Acuña, P. Effects of landscape composition and heterogeneity on the abundance of native coccinellids and biological control of aphids in alfalfa fields. 12th International Symposium Ecology of Aphidophaga, Belgrado, Serbia, 9-13 Septiembre 2013.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2013",
      "title": "",
      "text": "Grez, A.A., Zaviezo, T., Rodríguez, A., Hernández, H.J. & Acuña, M.P. Conservation of native coccinellids and biological control of aphids in alfalfa: a landscape perspective. Fourth International Symposium on Biological Control of Arthropods, Pucón, 4-8 Marzo 2013.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2013",
      "title": "",
      "text": "Faßnacht F., Hernández H.J., Corvalán P. & Koch B. Combination of EO1-Hyperion and LiDAR to estimate biomass in a highly complex second growth native forest in central Chile. Latin American Remote Sensing Week LARS 2013, Santiago, 23-25 Octubre 2013.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2013",
      "title": "",
      "text": "Ceballos A., Galleguillos M., Hernández H.J. Combining LiDAR topographic-structural information and Hyperspectral remote sensing to estimate plant vascular richness of deciduous mediterranean forests of central Chile. LARS 2013, Santiago, 23-25 Octubre 2013.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2013",
      "title": "",
      "text": "Cortés L., Valencia D., Hernández H.J. & Corvalán P. Utilidad de datos espectrales ETM+, ASTER GDEM y LiDAR en la estimación de Biomasa Aérea en plantaciones y bosques caducifolios mediterráneos. LARS 2013, Santiago, 23-25 Octubre 2013.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2013",
      "title": "",
      "text": "Mattar, C., Hernández, H.J., Santa María-Artigas, A., Durán-Alarcón, C., Olivera-Guerra, M., Inzunza, D., Tapia, D. & Escobar-Lavín, E. First in-flight absolute calibration of the Chilean Earth Observation Satellite. LARS 2013, Santiago, 23-25 Octubre 2013.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2012",
      "title": "",
      "text": "Hernández H.J., Núñez I., Bacigalupo A. & Cattan, P.E. Prediction of triatomines presence: using people's knowledge for Chagas disease risk assessment in Central Chile. Frontiers in Spatial Epidemiology, Londres, 5-6 Noviembre 2012.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2012",
      "title": "",
      "text": "Estades C.F., Acuña M.P., González B., Hernández H.J., Villaseñor N. & Vukasovic M. Abundancia y distribución del Suri en el Norte de Chile. X Congreso Internacional de Manejo de Fauna Silvestre en la Amazonía y Latinoamérica, Salta, 14-18 Mayo 2012.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2012",
      "title": "",
      "text": "Grez A.A., Zaviezo T., Rodríguez-San Pedro A., Hernández H.J. & Acuña P. Do the heterogeneity and composition of agricultural landscapes in Central Chile influence native and exotic coccinellids in alfalfa fields? IOBC/WPRS Working Group Landscape Management for Functional Biodiversity, Lleida, 7-10 Mayo 2012.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2012",
      "title": "",
      "text": "González R., Bacigalupo A., Hernández H.J. & Cattan P.E. Modelo de riesgo de infestación por triatominos basado en percepción de la población rural en Chile. III Congreso Internacional de Parasitología Neotropical, Lima, 5-11 Noviembre 2012.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2012",
      "title": "",
      "text": "Bacigalupo A., Sandoval P., Escobar M., Hernández H.J. & Cattan, P.E. Investigaciones sobre la dispersión del roedor Octodon sp. en un área que cohabita con vectores de la Enfermedad de Chagas. III Congreso Internacional de Parasitología Neotropical, Lima, 5-11 Noviembre 2012.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2012",
      "title": "",
      "text": "Cornejo B., Bacigalupo A., Hernández H.J. & Cattan P.E. Abundancias y prevalencias a Trypanosoma cruzi de triatominos y micro-mamíferos durante el invierno en Chile. III Congreso Internacional de Parasitología Neotropical, Lima, 5-11 Noviembre 2012.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2012",
      "title": "",
      "text": "Bacigalupo A., Arroyo P., Hernández H.J. & Cattan, P.E. First capture of dispersal adults of Triatoma infestans and Mepraia spinolai by black light traps from sylvatic foci of Chile. II International Workshop on Chagas disease, Cochabamba, 17-20 Septiembre 2012.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2012",
      "title": "",
      "text": "Bacigalupo A., Estadella V., Cornejo B., Hernández, H.J. & Cattan, P.E. Trypanosoma cruzi prevalence variation between summer and winter in the sylvatic vector Mepraia spinolai. II International Workshop on Chagas disease, Cochabamba, 17-20 Septiembre 2012.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2012",
      "title": "",
      "text": "Grez, A.A., Zaviezo, T., Rodríguez, A., Castillo, F., Hernández, H.J. & Acuña, M.P. Heterogeneidad del paisaje agrícola y la composición, abundancia y distribución de coccinélidos nativos y exóticos en Chile central. Octavo Congreso Nacional de Entomología, Bariloche, 17-20 Abril 2012.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2012",
      "title": "",
      "text": "Grez, A.A., Zaviezo, T., Rodríguez, A., Hernández, H.J. & Acuña, M.P. La composición del paisaje afecta el ensamble de coccinélidos nativos asociados a alfalfa en Chile central. XXXIV Congreso Nacional de Entomología, Santiago, 28-30 Noviembre 2012.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2012",
      "title": "",
      "text": "Escanilla, V. & Hernández, H.J. Influencia de la escala de análisis en la detección de cambios en la fragmentación de paisajes. IUFRO Landscape Ecology Conference, Concepción, noviembre 2012.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2012",
      "title": "",
      "text": "Bacigalupo A., Estadella V., Hernández H.J. & Cattan, P.E. Asociación entre la densidad relativa del vector silvestre de la enfermedad de Chagas, Mepraia spinolai, y su prevalencia a Trypanosoma cruzi. 17º Congreso Chileno de Medicina Veterinaria, Valdivia, 18-20 Noviembre 2012.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2012",
      "title": "",
      "text": "Bacigalupo, A., Estadella, V., Cornejo, B., Mena, R., Hernández, H.J. y Cattan, P.E. Análisis preliminar de la distribución de vectores y reservorios de Trypanosoma cruzi en focos silvestres de la zona endémica de Chile. XIV Jornadas Anuales de Parasitología, Santiago, 2012.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2012",
      "title": "",
      "text": "González R., Bacigalupo A., Landaeta-Aqueveque C., Hernández, H.J. & Cattan, P.E. Conocimiento de la población rural de la enfermedad de Chagas y propuesta de modelos de riesgo de infestación e infección. Simposio Internacional Enfermedad de Chagas, Santiago, 13 Noviembre 2012.",
      "url": "",
      "button": ""
    },
    {
      "category": "congresos",
      "year": "2012",
      "title": "",
      "text": "Cornejo B., Bacigalupo A., Hernández H.J. & Cattan P.E. Evaluación de la abundancia y prevalencia de vectores y reservorios de la enfermedad de Chagas en época invernal. Simposio Internacional Enfermedad de Chagas, Santiago, 13 Noviembre 2012.",
      "url": "",
      "button": ""
    }
  ]
}
JSON
,true);
$gep_publications=$gep_publications_defaults;$gep_publications_post_id=0;
if(function_exists('gep_demo_get_page_navigation')){$n=gep_demo_get_page_navigation();if(!empty($n['publicaciones']['page']->ID))$gep_publications_post_id=(int)$n['publicaciones']['page']->ID;}
if(!$gep_publications_post_id&&function_exists('get_page_by_path')){$p=get_page_by_path('publicaciones',OBJECT,'page');if($p&&!empty($p->ID))$gep_publications_post_id=(int)$p->ID;}
if($gep_publications_post_id&&function_exists('gep_demo_publications_values')){$saved=gep_demo_publications_values($gep_publications_post_id);if(is_array($saved)){$gep_publications=array_merge($gep_publications_defaults,$saved);foreach(array('sections','records') as $k)if(!isset($gep_publications[$k])||!is_array($gep_publications[$k]))$gep_publications[$k]=$gep_publications_defaults[$k];}}
/* Normalización compatible con el esquema v9.7. */
$gep_pub_sections=array(
 'libros'=>array('label'=>'Libros','eyebrow'=>'Producción editorial','title'=>'Libros'),
 'capitulos'=>array('label'=>'Capítulos de libro','eyebrow'=>'Producción editorial','title'=>'Capítulos de libro'),
 'articulos'=>array('label'=>'Artículos','eyebrow'=>'Revistas','title'=>'Artículos científicos'),
 'congresos'=>array('label'=>'Congresos','eyebrow'=>'Divulgación académica','title'=>'Congresos y encuentros científicos'),
 'otros'=>array('label'=>'Otros','eyebrow'=>'Otros formatos','title'=>'Otros materiales y publicaciones'),
);
if(!empty($gep_publications['sections']['libros'])&&is_array($gep_publications['sections']['libros']))$gep_pub_sections['libros']=array_merge($gep_pub_sections['libros'],$gep_publications['sections']['libros']);
if(!empty($gep_publications['sections']['congresos'])&&is_array($gep_publications['sections']['congresos']))$gep_pub_sections['congresos']=array_merge($gep_pub_sections['congresos'],$gep_publications['sections']['congresos']);
foreach($gep_publications['records'] as &$r){if(!is_array($r))continue;$original=isset($r['category'])?$r['category']:'';if($original==='internacionales'||$original==='nacionales')$r['category']='articulos';elseif($original==='libros-free')$r['category']='otros';elseif($original==='libros'){$hay=(isset($r['title'])?$r['title']:'').' '.(isset($r['text'])?$r['text']:'');$r['category']=preg_match('/\bEn:\s|\(Cap\.|Cap[ií]tulo/i',$hay)?'capitulos':'libros';}elseif(!in_array($original,array('libros','capitulos','articulos','congresos','otros'),true))$r['category']='otros';}unset($r);
$gep_publications['sections']=$gep_pub_sections;
require_once __DIR__.'/includes/structured-data.php';$pageSchema=gep_site_publications_schema($gep_publications['records']);$pageTitle='GEP · Publicaciones';$pageDescription='Producción científica y materiales del Laboratorio GEP.';$bodyClass='page-publicaciones';$activePage='publicaciones';$isHome=false;require __DIR__.'/includes/header.php';
$gep_pub_order=array('libros','capitulos','articulos','congresos','otros');
$gep_pub_counts=array();foreach($gep_pub_order as $c)$gep_pub_counts[$c]=0;$gep_pub_years=array();
foreach($gep_publications['records'] as $r){if(!is_array($r))continue;$c=isset($r['category'])?$r['category']:'';if(isset($gep_pub_counts[$c]))$gep_pub_counts[$c]++;$y=trim(isset($r['year'])?$r['year']:'');if($y!==''&&!in_array($y,$gep_pub_years,true))$gep_pub_years[]=$y;}
usort($gep_pub_years,function($a,$b){$ma=preg_match('/(19|20)\d{2}/',$a,$aa)?(int)$aa[0]:0;$mb=preg_match('/(19|20)\d{2}/',$b,$bb)?(int)$bb[0]:0;return $mb<=>$ma;});
?>
<main>
<?php $gep_publications_hero_style=!empty($gep_publications['hero_image'])?' style="background-image:url(' . gep_publications_h(gep_publications_url($gep_publications['hero_image'])) . ');"':''; ?>
<section data-gep-section="hero" class="page-hero hero-publicaciones<?php echo !empty($gep_publications['hero_video'])?' has-video-bg':''; ?>"<?php echo $gep_publications_hero_style; ?>><?php gep_theme_render_hero_video($gep_publications['hero_video']??'',$gep_publications['hero_image']??'','Video de cabecera de Publicaciones'); ?><div class="container page-hero-content"><p class="eyebrow"><?php echo gep_publications_h($gep_publications['hero_eyebrow']); ?></p><h1><?php echo gep_publications_h($gep_publications['hero_title']); ?></h1><p><?php echo gep_publications_h($gep_publications['hero_text']); ?></p></div></section>
<nav data-gep-section="hero" aria-label="Navegación de sección" class="section-nav"><div class="container section-nav-inner"><a href="#publicaciones">Todos</a><?php foreach($gep_pub_order as $cat): ?><a href="#<?php echo gep_publications_h($cat); ?>"><?php echo gep_publications_h($gep_publications['sections'][$cat]['label']); ?></a><?php endforeach; ?></div></nav>
<section data-gep-section="content" class="section"><div class="container">
<div class="publication-stats"><div class="mini-stat"><strong><?php echo count($gep_publications['records']); ?></strong><span>registros reunidos</span></div><div class="mini-stat"><strong><?php echo (int)$gep_pub_counts['articulos']; ?></strong><span>artículos</span></div><div class="mini-stat"><strong><?php echo (int)($gep_pub_counts['libros']+$gep_pub_counts['capitulos']); ?></strong><span>libros y capítulos</span></div><div class="mini-stat"><strong><?php echo (int)$gep_pub_counts['congresos']; ?></strong><span>congresos</span></div></div>
<div class="publication-tools" id="publicaciones"><div class="filter-topline"><strong><?php echo gep_publications_h($gep_publications['explorer_title']); ?></strong><span class="result-count" id="publicationCount"><?php echo count($gep_publications['records']); ?> resultados</span></div><div class="publication-filter-row"><div class="filter-pills publication-categories"><button aria-pressed="true" class="filter-pill active" data-pub-category="all" type="button">Todos</button><?php foreach($gep_pub_order as $cat): $m=$gep_publications['sections'][$cat]; ?><button aria-pressed="false" class="filter-pill" data-pub-category="<?php echo gep_publications_h($cat); ?>" type="button"><?php echo gep_publications_h($m['label']); ?></button><?php endforeach; ?></div><div class="publication-search-controls"><label class="search-field"><span>⌕</span><input id="publicationSearch" placeholder="Buscar autor, título, revista…" type="search"/></label><select aria-label="Filtrar por año" id="publicationYear"><option value="all">Todos los años</option><?php foreach($gep_pub_years as $year): ?><option value="<?php echo gep_publications_h($year); ?>"><?php echo gep_publications_h($year); ?></option><?php endforeach; ?></select></div></div></div>
<?php foreach($gep_pub_order as $cat): $meta=$gep_publications['sections'][$cat];$rows=array_values(array_filter($gep_publications['records'],function($r)use($cat){return is_array($r)&&isset($r['category'])&&$r['category']===$cat;})); ?>
<section class="publication-section" data-category="<?php echo gep_publications_h($cat); ?>" id="<?php echo gep_publications_h($cat); ?>"><p class="eyebrow"><?php echo gep_publications_h($meta['eyebrow']); ?></p><h2><?php echo gep_publications_h($meta['title']); ?> <span class="section-count"><?php echo count($rows); ?></span></h2>
<div class="pub-list"><?php foreach($rows as $r): $search=trim(($r['year']??'').' '.($r['title']??'').' '.($r['text']??'').' '.($r['doi']??'')); ?><article class="pub" data-category="<?php echo gep_publications_h($cat); ?>" data-search="<?php echo gep_publications_h(strtolower($search)); ?>" data-year="<?php echo gep_publications_h($r['year']??''); ?>"><div class="pub-year"><?php echo gep_publications_h($r['year']??''); ?></div><div><?php if(!empty($r['title'])): ?><h3 class="publication-title"><?php echo gep_publications_h($r['title']); ?></h3><?php endif; ?><p class="publication-record"><?php echo gep_publications_h($r['text']??''); ?></p><?php if(!empty($r['url'])): ?><a class="record-link" href="<?php echo gep_publications_h(gep_publications_url($r['url'])); ?>" target="_blank" rel="noopener"><?php echo gep_publications_h(!empty($r['button'])?$r['button']:'Abrir ↗'); ?></a><?php endif; ?></div><div class="pub-arrow">•</div></article><?php endforeach; ?></div>
</section><?php endforeach; ?>
<div class="empty-state" hidden id="publicationEmpty">No hay publicaciones que coincidan con la búsqueda.</div><?php $gep_publications['note']=gep_site_public_note($gep_publications['note']); if(!empty($gep_publications['note'])): ?><div class="source-note"><span class="source-note-icon">i</span><p><?php echo gep_publications_h($gep_publications['note']); ?></p></div><?php endif; ?>
</div></section></main>
<script>
(function(){var search=document.getElementById('publicationSearch'),year=document.getElementById('publicationYear'),count=document.getElementById('publicationCount'),empty=document.getElementById('publicationEmpty'),buttons=[].slice.call(document.querySelectorAll('[data-pub-category]')),items=[].slice.call(document.querySelectorAll('.publication-section .pub')),sections=[].slice.call(document.querySelectorAll('.publication-section')),category='all';
function norm(v){v=(v||'').toString().toLowerCase();return v.normalize?v.normalize('NFD').replace(/[\u0300-\u036f]/g,''):v;}function run(){var q=norm(search?search.value:''),y=year?year.value:'all',shown=0;items.forEach(function(item){var show=(category==='all'||item.dataset.category===category)&&(y==='all'||item.dataset.year===y)&&(!q||norm(item.dataset.search||item.textContent).indexOf(q)>=0);item.hidden=!show;if(show)shown++;});sections.forEach(function(sec){var visible=sec.querySelectorAll('.pub:not([hidden])').length;sec.hidden=visible===0;var c=sec.querySelector('.section-count');if(c)c.textContent=visible;});if(count)count.textContent=shown+' '+(shown===1?'resultado':'resultados');if(empty)empty.hidden=shown!==0;}buttons.forEach(function(btn){btn.addEventListener('click',function(){category=btn.dataset.pubCategory||'all';buttons.forEach(function(b){var a=b===btn;b.classList.toggle('active',a);b.setAttribute('aria-pressed',a?'true':'false');});run();});});if(search)search.addEventListener('input',run);if(year)year.addEventListener('change',run);run();})();
</script>
<?php require __DIR__.'/includes/footer.php'; ?>
