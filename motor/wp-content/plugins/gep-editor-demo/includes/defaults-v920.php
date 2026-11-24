<?php
/**
 * GEP Editor v9.20.0 · Contenido de partida que coincide con la página pública.
 *
 * Cuando una pestaña todavía no tiene nada guardado (por ejemplo, después de
 * reinstalar WordPress), el editor y la página deben mostrar lo mismo. Estas
 * funciones solo se usan para LEER: no escriben nada en la base de datos hasta
 * que alguien pulsa Guardar.
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Directorio original de Quiénes somos: las mismas personas y grupos que
 * muestra quienes-somos.php cuando no hay apartados guardados.
 */
function gep_demo_directory_original_sections() {
    $json = <<<'JSON'
[
  {
    "type": "people",
    "title": "Investigadores",
    "open": 1,
    "groups": [],
    "items": [
      {
        "group": "",
        "specialization": "",
        "year": "",
        "name": "Jaime Hernández Palma",
        "meta": "Ing. Forestal, Dr. Ing. de Montes",
        "text": "Director del GEP; especialista en geomática y ecología del paisaje aplicada a la evaluación y seguimiento de la vegetación.",
        "email": "jhernand@uchile.cl",
        "image": ""
      },
      {
        "group": "",
        "specialization": "",
        "year": "",
        "name": "Patricio Corvalán Vera",
        "meta": "Ing. Forestal, Magíster Manejo Recursos Forestales",
        "text": "Profesor asistente del Dpto. Gestión Forestal y Medio Ambiente, Universidad de Chile; especialista en mensura forestal.",
        "email": "pcorvala@uchile.cl",
        "image": ""
      },
      {
        "group": "",
        "specialization": "",
        "year": "",
        "name": "Cristián Estades Marfán",
        "meta": "Ing. Forestal, MSc, PhD Wildlife Ecology",
        "text": "Profesor Asociado del Dpto. Gestión Forestal y Medio Ambiente, Universidad de Chile; especialista en ecología de vida silvestre y biología de la conservación.",
        "email": "cestades@uchile.cl",
        "image": ""
      }
    ]
  },
  {
    "type": "people",
    "title": "Profesionales",
    "open": 1,
    "groups": [],
    "items": [
      {
        "group": "",
        "specialization": "",
        "year": "",
        "name": "María Paz Acuña Ruz",
        "meta": "Ing. en Recursos Naturales Renovables, MSc. en Áreas Silvestres y Conservación de la Naturaleza, (c) Dra. en Ciencias Silvoagropecuarias y Veterinarias",
        "text": "Investigador asociado y coordinador GEP; especialista en ecología del paisaje y movimiento animal.",
        "email": "mpacuna@u.uchile.cl",
        "image": ""
      },
      {
        "group": "",
        "specialization": "",
        "year": "",
        "name": "Lissette Cortés Serey",
        "meta": "Ing. en Recursos Naturales Renovables, Diplomado en Geomática Aplicada",
        "text": "Investigador asociado; especialista en geomática aplicada a recursos naturales.",
        "email": "lcortes.serey@gmail.com",
        "image": ""
      },
      {
        "group": "",
        "specialization": "",
        "year": "",
        "name": "Taryn Fuentes Castillo",
        "meta": "Ing. en Recursos Naturales Renovables, MSc. en Áreas Silvestres y Conservación de la Naturaleza, (c) Dra. en Ciencias Silvoagropecuarias y Veterinarias",
        "text": "Investigador asociado; especialista en planificación sistemática de la conservación y ecología espacial.",
        "email": "tarfuentes@uchile.cl",
        "image": ""
      }
    ]
  },
  {
    "type": "status",
    "title": "Estudiantes de postgrado",
    "open": 0,
    "groups": [
      "Doctorado Ciencias Silvoagropecuarias y Veterinarias (CSAV)",
      "Magíster en Áreas Silvestres y Conservación de la Naturaleza (MASCN)"
    ],
    "items": [
      {
        "group": "Doctorado Ciencias Silvoagropecuarias y Veterinarias (CSAV)",
        "status": "D",
        "name": "Lissette Cortés",
        "text": "Estimación del carbono almacenado y secuestrado en bosque nativo y plantaciones de Chile."
      },
      {
        "group": "Doctorado Ciencias Silvoagropecuarias y Veterinarias (CSAV)",
        "status": "T",
        "name": "Taryn Fuentes",
        "text": "Planificación para la conservación de la biodiversidad frente al cambio climático en zonas altiplánicas: un enfoque integrado y dinámico."
      },
      {
        "group": "Doctorado Ciencias Silvoagropecuarias y Veterinarias (CSAV)",
        "status": "T",
        "name": "María Paz Acuña Ruz",
        "text": "Poblaciones de aves acuáticas como bioindicadores para la gestión de humedales costeros en Chile central."
      },
      {
        "group": "Doctorado Ciencias Silvoagropecuarias y Veterinarias (CSAV)",
        "status": "T",
        "name": "Paula Santibañez",
        "text": "Análisis de la vulnerabilidad de la silvoagricultura chilena frente a escenarios de cambio climático mediante técnicas de simulación y sistemas de información geográfica."
      },
      {
        "group": "Magíster en Áreas Silvestres y Conservación de la Naturaleza (MASCN)",
        "status": "T",
        "name": "Alba Lucia Marín",
        "text": "Dinámica espacio-temporal del uso y cobertura del suelo en el paisaje cafetero colombiano."
      },
      {
        "group": "Magíster en Áreas Silvestres y Conservación de la Naturaleza (MASCN)",
        "status": "T",
        "name": "Carolina Gallardo",
        "text": "Influencia de las características del paisaje sobre la estructura genética de las poblaciones de Bufo spinulosus en la región de Antofagasta, Chile."
      },
      {
        "group": "Magíster en Áreas Silvestres y Conservación de la Naturaleza (MASCN)",
        "status": "T",
        "name": "Fabiola Gamboa",
        "text": "Caracterización florística, vegetacional y etnobotánica de la Reserva Nacional Alto Loa, II Región de Antofagasta, Chile."
      },
      {
        "group": "Magíster en Áreas Silvestres y Conservación de la Naturaleza (MASCN)",
        "status": "T",
        "name": "Natacha Oyola",
        "text": "Identificación de Humedales del Norte Grande de Chile utilizando técnicas geomáticas en imágenes satelitales Landsat."
      },
      {
        "group": "Magíster en Áreas Silvestres y Conservación de la Naturaleza (MASCN)",
        "status": "D",
        "name": "Leonor Andrade",
        "text": "Propuesta de una red de microreservas para el picaflor de Arica (Eulidia yarrellii)."
      }
    ]
  },
  {
    "type": "status",
    "title": "Estudiantes de pregrado",
    "open": 0,
    "groups": [
      "Ingeniería en Recursos Naturales Renovables (RRNN)",
      "Ingeniería Forestal"
    ],
    "items": [
      {
        "group": "Ingeniería en Recursos Naturales Renovables (RRNN)",
        "status": "D",
        "name": "Ginia Troncoso",
        "text": "Evaluación de la respuesta morfofisiológica de quillay (Quillaja saponaria Molina) a contaminantes atmosféricos en la ciudad de Santiago."
      },
      {
        "group": "Ingeniería en Recursos Naturales Renovables (RRNN)",
        "status": "T",
        "name": "Tomás Acuña",
        "text": "Firmas espectrales de vegetación urbana en Santiago de Chile: caracterización y separabilidad de especies a escala foliar."
      },
      {
        "group": "Ingeniería en Recursos Naturales Renovables (RRNN)",
        "status": "T",
        "name": "Andrés Ceballos",
        "text": "Combinación de información topográfica-estructural LiDAR y teledetección hiperespectral para estimar la diversidad florística vascular de un bosque mediterráneo caducifolio en la precordillera andina del Maule, Chile."
      },
      {
        "group": "Ingeniería en Recursos Naturales Renovables (RRNN)",
        "status": "T",
        "name": "Javier Lopatín",
        "text": "Utilización de datos LiDAR en la modelación del régimen hídrico a escala predial."
      },
      {
        "group": "Ingeniería en Recursos Naturales Renovables (RRNN)",
        "status": "T",
        "name": "Alejandro Miranda",
        "text": "Identificación de sitios favorables para la regeneración de la palma chilena (Jubaea chilensis) a través de modelación espacial."
      },
      {
        "group": "Ingeniería en Recursos Naturales Renovables (RRNN)",
        "status": "T",
        "name": "Carola Villablanca",
        "text": "Estimación de cambios de coberturas vegetales en zonas de Chile central, durante el período 1955–1975."
      },
      {
        "group": "Ingeniería en Recursos Naturales Renovables (RRNN)",
        "status": "D",
        "name": "Cecilia Romero",
        "text": "Evaluación del tamaño poblacional local de palma chilena, Jubaea chilensis, mediante clasificación digital de imágenes satelitales de alta resolución."
      },
      {
        "group": "Ingeniería en Recursos Naturales Renovables (RRNN)",
        "status": "T",
        "name": "Inao Vásquez",
        "text": "Patrones espaciales de reclutamiento y dispersión de semillas del árbol Persea lingue en el bosque del valle del sur de Chile: efectos del hábitat y un corredor."
      },
      {
        "group": "Ingeniería en Recursos Naturales Renovables (RRNN)",
        "status": "T",
        "name": "Valentina Escanilla",
        "text": "Patrones espacio-temporales de la vegetación nativa en cuatro paisajes locales de la depresión intermedia de la Provincia de Osorno, Región de Los Lagos."
      },
      {
        "group": "Ingeniería en Recursos Naturales Renovables (RRNN)",
        "status": "D",
        "name": "Lissette Cortés",
        "text": "Modelos para la predicción de biomasa aérea en plantaciones de Pino insigne usando imágenes LANDSAT ETM+ y variables topográficas."
      },
      {
        "group": "Ingeniería en Recursos Naturales Renovables (RRNN)",
        "status": "D",
        "name": "Raúl Díaz",
        "text": "Análisis de la evolución espacio-temporal de la invasión de Rubus ulmifolius, Aristotelia chilensis y Ugni molinae, en la Isla Robinson Crusoe, período 1983-2010."
      },
      {
        "group": "Ingeniería en Recursos Naturales Renovables (RRNN)",
        "status": "D",
        "name": "Ignacia Nuñez",
        "text": "Modelo predictivo basado en percepción ciudadana de la distribución espacial de vinchucas en Chile central."
      },
      {
        "group": "Ingeniería Forestal",
        "status": "D",
        "name": "Rodrigo Segura",
        "text": "Estructura y composición vegetacional de la ciudad de Santiago en el año 2014."
      },
      {
        "group": "Ingeniería Forestal",
        "status": "T",
        "name": "Diego Valencia",
        "text": "Evaluación de la estructura espacial del volumen estéreo del sotobosque en plantaciones de Pinus radiata utilizando datos LiDAR."
      },
      {
        "group": "Ingeniería Forestal",
        "status": "D",
        "name": "Pablo Montero",
        "text": "Propuesta de criterios silviculturales para la convivencia compatible entre arbolado urbano y alumbrado público, comuna de La Reina."
      },
      {
        "group": "Ingeniería Forestal",
        "status": "T",
        "name": "Daniel Montaner",
        "text": "Segmentación automática de copas de árboles en plantaciones de Pinus radiata usando fotografías aéreas digitales."
      },
      {
        "group": "Ingeniería Forestal",
        "status": "T",
        "name": "Cynnamon Dobbs",
        "text": "Construcción de modelos de estimación de biomasa y área foliar para diez especies arbóreas urbanas de la ciudad de Santiago."
      },
      {
        "group": "Ingeniería Forestal",
        "status": "T",
        "name": "Valeska Pizarro",
        "text": "Desarrollo de un sistema de información de apoyo a la gestión del arbolado público urbano."
      },
      {
        "group": "Ingeniería Forestal",
        "status": "T",
        "name": "Sebastián Varela",
        "text": "Calidad de la vegetación urbana como hábitat para aves: el caso de Santiago de Chile."
      },
      {
        "group": "Ingeniería Forestal",
        "status": "T",
        "name": "Francisca Toro",
        "text": "Áreas potenciales para la aplicación de biosólidos en plantaciones forestales de la VI Región de Chile."
      },
      {
        "group": "Ingeniería Forestal",
        "status": "T",
        "name": "Bianca Castillo",
        "text": "Determinación de la fragilidad adquirida del paisaje en el Parque Nacional Huerquehue, Temuco, IX Región."
      }
    ]
  },
  {
    "type": "people",
    "title": "Colaboradores",
    "open": 0,
    "groups": [],
    "items": [
      {
        "group": "",
        "specialization": "",
        "year": "",
        "name": "Benito González",
        "meta": "Ing. Agrónomo, Dr. Ciencias Silvoagropecuarias y Veterinarias",
        "text": "GECS · LEVS",
        "email": "",
        "image": ""
      },
      {
        "group": "",
        "specialization": "",
        "year": "",
        "name": "Nélida Villaseñor",
        "meta": "Médico Veterinario, Magíster Áreas Silvestres y Conservación de la Naturaleza",
        "text": "LEVS",
        "email": "",
        "image": ""
      },
      {
        "group": "",
        "specialization": "",
        "year": "",
        "name": "María Angélica Vukasovic",
        "meta": "Ecólogo paisajista",
        "text": "LEVS",
        "email": "",
        "image": ""
      },
      {
        "group": "",
        "specialization": "",
        "year": "",
        "name": "Sandra Uribe",
        "meta": "Ing. Forestal",
        "text": "LEVS",
        "email": "",
        "image": ""
      }
    ]
  },
  {
    "type": "status",
    "title": "Becarios",
    "open": 0,
    "groups": [],
    "items": [
      {
        "group": "",
        "status": "2007–2008",
        "name": "María Paz Acuña Ruz",
        "text": "Ing. en Recursos Naturales Renovables, Magíster Áreas Silvestres y Conservación de la Naturaleza"
      },
      {
        "group": "",
        "status": "2007–2008",
        "name": "Andrea Contreras",
        "text": "Ing. en Recursos Naturales Renovables, Magíster Áreas Silvestres y Conservación de la Naturaleza"
      },
      {
        "group": "",
        "status": "2010",
        "name": "Catalina Hernández",
        "text": "Ing. Agrónoma, Magíster Áreas Silvestres y Conservación de la Naturaleza"
      },
      {
        "group": "",
        "status": "2011",
        "name": "Constanza Rodríguez",
        "text": "Médico Veterinario, Magíster Áreas Silvestres y Conservación de la Naturaleza"
      }
    ]
  }
]
JSON;
    $data = json_decode( $json, true );
    return is_array( $data ) ? $data : array();
}

/**
 * Proyectos vigentes de MapBiomas (Agua, Fuego y Chile), con la foto que
 * tenían antes del 12-10 si el archivo sigue en uploads/2026/09.
 */
function gep_demo_projects_current_starters() {
        $showcase = array(
            'mapbiomas-chile' => array(
                'slug'=>'mapbiomas-chile','status'=>'vigente','year'=>'2021–actualidad','era'=>'2020-actualidad','theme'=>'Monitoreo territorial','title'=>'MapBiomas Chile',
                'text'=>'Monitoreo anual de la cobertura y uso del suelo de Chile mediante imágenes Landsat y procesamiento colaborativo en Google Earth Engine.',
                'details'=>'Chile forma parte de la Red MapBiomas desde 2021. La iniciativa nacional reúne especialistas de la Universidad de Chile, Universidad de Concepción y Universidad de La Frontera para producir información territorial pública y reproducible. La Colección 2 cubre el período 1999–2024.',
                'institution'=>'GEP · Universidad de Chile · LEP UdeC · LEPCON UFRO','lead'=>'Jaime Hernández · Coordinación general',
                'image'=>'','image_alt'=>'Imagen territorial referencial para MapBiomas Chile.',
                'gallery'=>"",
                'detail_url'=>'','tag'=>'Proyecto colaborativo','url'=>'https://chile.mapbiomas.org/iniciativas-y-productos/cobertura-y-uso-del-suelo/cobertura-30m/','button'=>'Explorar MapBiomas Chile ↗'
            ),
            'mapbiomas-agua-chile' => array(
                'slug'=>'mapbiomas-agua-chile','status'=>'vigente','year'=>'2026–actualidad','era'=>'2020-actualidad','theme'=>'Agua y criósfera','title'=>'MapBiomas Agua Chile',
                'text'=>'Serie nacional para analizar la dinámica de superficies de agua y glaciares en Chile entre 1998 y 2025.',
                'details'=>'MapBiomas Agua Chile amplía el monitoreo territorial hacia agua superficial y criósfera. La Colección 1 incluye productos anuales y mensuales de superficie de agua, además de mapas anuales de glaciares, frecuencia y transiciones para el período 1998–2025.',
                'institution'=>'MapBiomas Chile · universidades co-creadoras','lead'=>'Equipo MapBiomas Chile',
                'image'=>'','image_alt'=>'Paisaje glaciar y de montaña utilizado como imagen referencial para MapBiomas Agua Chile.',
                'gallery'=>"",
                'detail_url'=>'','tag'=>'Monitoreo nacional','url'=>'https://chile.mapbiomas.org/metodologia-mapbiomas-agua/','button'=>'Conocer MapBiomas Agua ↗'
            ),
            'mapbiomas-fuego-chile' => array(
                'slug'=>'mapbiomas-fuego-chile','status'=>'vigente','year'=>'2026–actualidad','era'=>'2020-actualidad','theme'=>'Fuego y paisaje','title'=>'MapBiomas Fuego Chile',
                'text'=>'Mapeo nacional de áreas quemadas y de la cobertura afectada por fuego, con productos anuales y mensuales para analizar su dinámica territorial.',
                'details'=>'La Colección 1 de MapBiomas Fuego Chile pone a disposición mapas de área quemada, cobertura quemada, frecuencia y última ocurrencia de fuego. La serie disponible cubre 2013–2025 y permite estudiar patrones espaciales y temporales a escala nacional.',
                'institution'=>'MapBiomas Chile · universidades co-creadoras','lead'=>'Equipo MapBiomas Chile',
                'image'=>'','image_alt'=>'Paisaje forestal utilizado como imagen referencial para MapBiomas Fuego Chile.',
                'gallery'=>"",
                'detail_url'=>'','tag'=>'Monitoreo nacional','url'=>'https://chile.mapbiomas.org/iniciativas-y-productos/fuego/','button'=>'Explorar MapBiomas Fuego ↗'
            ),
        );
    $photos = array(
        'mapbiomas-agua-chile'  => '2026/09/DSC_0674-1-scaled.jpg',
        'mapbiomas-fuego-chile' => '2026/09/DSC_0676-scaled.jpg',
        'mapbiomas-chile'       => '2026/09/DSC_0588-scaled.jpg',
    );
    $uploads = function_exists( 'wp_get_upload_dir' ) ? wp_get_upload_dir() : array();
    $out     = array();
    foreach ( array( 'mapbiomas-agua-chile', 'mapbiomas-fuego-chile', 'mapbiomas-chile' ) as $slug ) {
        $record = $showcase[ $slug ];
        if ( ! empty( $uploads['basedir'] ) && file_exists( trailingslashit( $uploads['basedir'] ) . $photos[ $slug ] ) ) {
            $record['image'] = trailingslashit( $uploads['baseurl'] ) . $photos[ $slug ];
        }
        $out[] = $record;
    }
    return $out;
}
