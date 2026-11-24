<?php
/**
 * GEP · Inicio
 * Compatible con sitio PHP externo + WordPress detectado automáticamente.
 * Lee GEP Editor Beta v8.5 cuando está disponible y conserva el contenido original como respaldo.
 */
require_once __DIR__ . '/includes/wp-bootstrap.php';
require_once __DIR__ . '/includes/landscape-frame.php';
gep_site_bootstrap_wordpress();
function gep_home_h($v){return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function gep_home_url($v){$v=trim((string)$v);if($v===''||preg_match('/^(?:javascript|data):/i',$v))return '';return gep_home_h($v);}
function gep_home_external($v){return (bool)preg_match('#^https?://#i',(string)$v);}
$gep_home_defaults=json_decode(<<<'JSON'
{
  "hero_eyebrow": "Universidad de Chile · Geomática · Ecología del paisaje",
  "hero_title": "Geomática para comprender el paisaje.",
  "hero_text": "Investigación y formación para leer el territorio, analizar sus transformaciones y conectar datos espaciales con procesos ecológicos.",
  "hero_image": "",
  "hero_video": "",
  "hero_primary_text": "Conocer el laboratorio ↓",
  "hero_primary_url": "#laboratorio",
  "hero_secondary_text": "Explorar proyectos",
  "hero_secondary_url": "proyectos.php",
  "hero_geo_a": "GEP / LANDSCAPE SYSTEMS",
  "hero_geo_b": "33° S · 70° W",
  "intro_eyebrow": "Laboratorio GEP",
  "intro_title": "Investigación del territorio desde la geomática y la ecología del paisaje.",
  "intro_p1": "El Laboratorio de Geomática y Ecología del Paisaje de la Facultad de Ciencias Forestales y Conservación de la Naturaleza, de la Universidad de Chile, es un laboratorio de investigación en donde comparten académicos y estudiantes.",
  "intro_p2": "Fue creado el 2005 por Cristián Estades M. y Jaime Hernández P. para ser una estación de investigación en geomática aplicada y ecología del paisaje, en la cual investigadores y estudiantes colaboran en proyectos específicos. Además, el GEP es responsable de un conjunto de cursos de pregrado y postgrado asociados.",
  "pills": [
    "Geomática",
    "Ecología del paisaje",
    "Teledetección",
    "SIG",
    "Análisis espacial",
    "Geoestadística"
  ],
  "stats": [
    {
      "value": "2005",
      "label": "Año de creación",
      "text": "Trayectoria del laboratorio"
    },
    {
      "value": "16",
      "label": "Proyectos registrados",
      "text": "Investigación aplicada 2006–2016"
    },
    {
      "value": "93",
      "label": "Registros de producción",
      "text": "Publicaciones y materiales reunidos"
    },
    {
      "value": "44",
      "label": "Personas registradas",
      "text": "Investigadores, estudiantes y colaboradores"
    }
  ],
  "definitions": [
    {
      "eyebrow": "Concepto clave",
      "title": "Geomática",
      "text": "Conjunto de ciencias que establecen las bases para la captura, tratamiento, análisis, interpretación, difusión y almacenamiento de información geográfica. Incluye elementos de geodesia, fotogrametría, sistemas de posicionamiento global, teledetección, tratamiento digital de imágenes, sistemas de información geográfica, análisis espacial y geoestadística, entre otras."
    },
    {
      "eyebrow": "Concepto clave",
      "title": "Ecología del Paisaje",
      "text": "Se dedica al estudio de la variación o heterogeneidad espacial de los paisajes que se pueden definir a diferentes escalas. Los paisajes contienen patrones espaciales —estructuras— relacionados con procesos ecológicos —funciones— en forma dinámica —cambios—, considerando las causas biofísicas y sociales de dicha heterogeneidad y sus consecuencias."
    }
  ],
  "field_image": "",
  "field_alt": "Vista aérea de un paisaje natural con extensa cobertura vegetal en flor",
  "field_index": "GEP / FIELD 01",
  "field_eyebrow": "Territorio en observación",
  "field_title": "El paisaje también se lee desde el terreno.",
  "field_text": "Registro visual incorporado como parte de la nueva identidad fotográfica del GEP.",
  "research_eyebrow": "Líneas de trabajo",
  "research_title": "Territorio, vegetación y biodiversidad.",
  "research_text": "Una mirada que integra cartografía, percepción remota, biodiversidad, biomasa, conservación, ecología urbana y planificación territorial.",
  "research_cards": [
    {
      "image": "",
      "alt": "Mapa temático de coberturas de suelo",
      "kicker": "01 · Geomática",
      "title": "Análisis espacial y territorial",
      "text": "Cartografía, SIG, geodesia, fotogrametría, geoestadística y bases de datos espaciales."
    },
    {
      "image": "",
      "alt": "Mapa de áreas urbanas y agropecuarias",
      "kicker": "02 · Sensores remotos",
      "title": "Teledetección y coberturas",
      "text": "Landsat, LiDAR, hiperespectral, radiometría de campo y tratamiento digital de imágenes."
    },
    {
      "image": "",
      "alt": "Paisaje montañoso chileno",
      "kicker": "03 · Ecología del paisaje",
      "title": "Patrones, procesos y conservación",
      "text": "Biodiversidad, conectividad, movimiento animal, cambio climático y planificación."
    }
  ],
  "portal_eyebrow": "Explorar GEP",
  "portal_title": "Una entrada clara a cada área del laboratorio.",
  "portal_text": "Docencia, investigación, producción científica, personas y contacto organizados en páginas independientes.",
  "portal_cards": [
    {
      "icon": "D",
      "title": "Docencia",
      "text": "2 cursos de pregrado y 5 registros de postgrado/postítulo.",
      "url": "docencia.php",
      "button": "Ver docencia →"
    },
    {
      "icon": "P",
      "title": "Proyectos",
      "text": "16 proyectos y estudios registrados entre 2006 y 2016.",
      "url": "proyectos.php",
      "button": "Ver proyectos →"
    },
    {
      "icon": "R",
      "title": "Publicaciones",
      "text": "Revistas internacionales, nacionales, libros, capítulos y congresos.",
      "url": "publicaciones.php",
      "button": "Ver publicaciones →"
    },
    {
      "icon": "Q",
      "title": "Quiénes somos",
      "text": "Investigadores, profesionales, estudiantes, colaboradores y becarios.",
      "url": "quienes-somos.php",
      "button": "Conocer el equipo →"
    },
    {
      "icon": "C",
      "title": "Contacto",
      "text": "Dirección, campus y vías de contacto del laboratorio.",
      "url": "contactanos.php",
      "button": "Ver contacto →"
    }
  ],
  "landscape_label": "Escala · procesos · cambio",
  "landscape_title": "Comprender los paisajes para entender sus transformaciones.",
  "landscape_image": "",
  "landscape_focus": "",
  "landscape_fit": "",
  "resources_eyebrow": "Plataformas y proyectos",
  "resources_title": "Recursos destacados del GEP.",
  "resources_text": "Una selección de plataformas, iniciativas y materiales vinculados a la trayectoria del laboratorio.",
  "resources": [
    {
      "kicker": "13–17 noviembre 2017",
      "title": "Taller de Google Earth Engine",
      "url": "https://www.gep.uchile.cl/Proyectos/Summer_School/index.html",
      "button": "Abrir recurso ↗"
    },
    {
      "kicker": "Proyecto / recurso GEP",
      "title": "ECOURBE",
      "url": "https://www.gep.uchile.cl/ecourbe/index.html",
      "button": "Abrir recurso ↗"
    },
    {
      "kicker": "Biblioteca espectral GEP",
      "title": "Firmas Espectrales",
      "url": "https://www.gep.uchile.cl/SPEC/firmas.html",
      "button": "Abrir recurso ↗"
    },
    {
      "kicker": "Cobertura de suelo",
      "title": "Landcover de Chile 2014",
      "url": "https://www.gep.uchile.cl/Landcover_CHILE.html",
      "button": "Abrir recurso ↗"
    },
    {
      "kicker": "Proyecto y publicación",
      "title": "Biodiversidad Terrestre de Arica y Parinacota",
      "url": "https://www.gep.uchile.cl/Biodiversidad/home.html",
      "button": "Abrir recurso ↗"
    },
    {
      "kicker": "Proyecto territorial",
      "title": "Piedemonte de Santiago (FPA)",
      "url": "https://www.gep.uchile.cl/Piedemonte_stgo/index.html",
      "button": "Abrir recurso ↗"
    }
  ],
  "topics_eyebrow": "Biblioteca temática",
  "topics_title": "Conocimiento organizado por áreas.",
  "topics_text": "Temas de apoyo a docencia e investigación que forman parte del repositorio recuperado del GEP.",
  "topics": [
    "Ambientes Urbanos",
    "Análisis Espacial",
    "Biodiversidad",
    "Biomasa y Carbono",
    "Ecología del Paisaje",
    "Estadística Espacial",
    "Fotogrametría",
    "Fundamentos de Teledetección",
    "Hiperespectral",
    "LiDAR",
    "Movimiento Animal",
    "Radar",
    "Radiometría de Campo",
    "Sensores Remotos",
    "Sistemas de Información Geográfica",
    "Sistemas de Coordenadas y Geodesia",
    "Softwares SIG y Teledetección",
    "Tratamiento Digital de Imágenes"
  ],
  "network_image": "",
  "network_alt": "MapBiomas Chile",
  "network_eyebrow": "Herramientas y datos",
  "network_title": "Herramientas y fuentes para el análisis geoespacial.",
  "network_text": "Software, sensores, herramientas de biomasa y fuentes de biodiversidad y clima vinculadas al ecosistema de trabajo del GEP.",
  "network_links": [
    {
      "title": "QGIS",
      "url": "https://www.qgis.org/"
    },
    {
      "title": "Landsat 8",
      "url": "https://landsat.gsfc.nasa.gov/satellites/landsat-8/"
    },
    {
      "title": "GlobAllomeTree",
      "url": "http://www.globallometree.org/"
    },
    {
      "title": "Datos climáticos GEP",
      "url": "https://www.gep.uchile.cl/Links/Datos_climaticos.html"
    },
    {
      "title": "InfoNatura / NatureServe",
      "url": "https://www.natureserve.org/"
    },
    {
      "title": "WorldClim",
      "url": "https://www.worldclim.org/"
    },
    {
      "title": "GBIF",
      "url": "https://www.gbif.org/"
    }
  ],
  "note": "",
  "cta_eyebrow": "Trayectoria y conocimiento",
  "cta_title": "Explora los proyectos y la producción científica del GEP.",
  "cta_button": "Ver proyectos",
  "cta_url": "proyectos.php"
}
JSON
,true);
$gep_home=$gep_home_defaults;
$gep_home_page_id=0;
if(function_exists('get_post_meta')){
    $front=(int)get_option('page_on_front');
    if($front && get_post_type($front)==='page')$gep_home_page_id=$front;
    if(!$gep_home_page_id && function_exists('get_page_by_path')){foreach(array('inicio','home','portada') as $slug){$p=get_page_by_path($slug,OBJECT,'page');if($p){$gep_home_page_id=(int)$p->ID;break;}}}
    if(!$gep_home_page_id){$ids=get_posts(array('post_type'=>'page','post_status'=>array('publish','draft','private'),'meta_key'=>'gep_home_hero_title','fields'=>'ids','numberposts'=>1));if($ids)$gep_home_page_id=(int)$ids[0];}
    if($gep_home_page_id){
        $map=array(
            'hero_eyebrow'=>'gep_home_hero_eyebrow','hero_title'=>'gep_home_hero_title','hero_text'=>'gep_home_hero_text','hero_image'=>'gep_home_hero_image','hero_video'=>'gep_home_hero_video','hero_primary_text'=>'gep_home_hero_primary_text','hero_primary_url'=>'gep_home_hero_primary_url','hero_secondary_text'=>'gep_home_hero_secondary_text','hero_secondary_url'=>'gep_home_hero_secondary_url','hero_geo_a'=>'gep_home_hero_geo_a','hero_geo_b'=>'gep_home_hero_geo_b',
            'intro_eyebrow'=>'gep_home_intro_eyebrow','intro_title'=>'gep_home_intro_title','intro_p1'=>'gep_home_intro_p1','intro_p2'=>'gep_home_intro_p2',
            'field_image'=>'gep_home_field_image','field_alt'=>'gep_home_field_alt','field_index'=>'gep_home_field_index','field_eyebrow'=>'gep_home_field_eyebrow','field_title'=>'gep_home_field_title','field_text'=>'gep_home_field_text',
            'research_eyebrow'=>'gep_home_research_eyebrow','research_title'=>'gep_home_research_title','research_text'=>'gep_home_research_text','portal_eyebrow'=>'gep_home_portal_eyebrow','portal_title'=>'gep_home_portal_title','portal_text'=>'gep_home_portal_text',
            'landscape_label'=>'gep_home_landscape_label','landscape_title'=>'gep_home_landscape_title','landscape_image'=>'gep_home_landscape_image','landscape_focus'=>'gep_home_landscape_focus','landscape_fit'=>'gep_home_landscape_fit','resources_eyebrow'=>'gep_home_resources_eyebrow','resources_title'=>'gep_home_resources_title','resources_text'=>'gep_home_resources_text',
            'topics_eyebrow'=>'gep_home_topics_eyebrow','topics_title'=>'gep_home_topics_title','topics_text'=>'gep_home_topics_text','network_image'=>'gep_home_network_image','network_alt'=>'gep_home_network_alt','network_eyebrow'=>'gep_home_network_eyebrow','network_title'=>'gep_home_network_title','network_text'=>'gep_home_network_text',
            'note'=>'gep_home_note','cta_eyebrow'=>'gep_home_cta_eyebrow','cta_title'=>'gep_home_cta_title','cta_button'=>'gep_home_cta_button','cta_url'=>'gep_home_cta_url',
            // v9.26.0: botones que el editor marcó como ocultos (conservan texto y enlace).
            'hero_primary_off'=>'gep_home_hero_primary_off','hero_secondary_off'=>'gep_home_hero_secondary_off','cta_off'=>'gep_home_cta_off'
        );
        foreach($map as $k=>$meta){if(metadata_exists('post',$gep_home_page_id,$meta))$gep_home[$k]=get_post_meta($gep_home_page_id,$meta,true);}
        foreach(array('pills','stats','definitions','research_cards','portal_cards','resources','topics','network_links') as $k){$raw=get_post_meta($gep_home_page_id,'gep_home_'.$k.'_json',true);if($raw!==''){$decoded=json_decode($raw,true);if(is_array($decoded))$gep_home[$k]=$decoded;}}
    }
}
// v9.21.4: sin foto elegida se usa una del propio sitio, así no queda un recuadro vacío.
if(trim((string)$gep_home['field_image'])==='')$gep_home['field_image']='assets/field/trabajo-terreno-araucarias.jpg';
if(trim((string)$gep_home['network_image'])===''){$gep_home['network_image']='assets/images/branding/mapbiomas.png';if(trim((string)$gep_home['network_alt'])==='')$gep_home['network_alt']='Logo de MapBiomas';}
$pageTitle = 'GEP · Geomática y Ecología del Paisaje';
$pageDescription = 'Laboratorio de Geomática y Ecología del Paisaje de la Universidad de Chile.';
$bodyClass = 'page-index';
$activePage = 'inicio';
$isHome = true;
require __DIR__ . '/includes/header.php';
?>
<main>
<?php $gep_home_hero_style=!empty($gep_home['hero_image'])?' style="background-image:linear-gradient(90deg,rgba(5,20,11,.82) 0%,rgba(5,20,11,.54) 38%,rgba(5,20,11,.08) 70%),linear-gradient(0deg,rgba(5,20,11,.48) 0%,transparent 40%),url(' . gep_home_url($gep_home['hero_image']) . ');"':''; ?>
<section data-gep-section="hero" class="hero<?php echo !empty($gep_home['hero_video'])?' has-video-bg':''; ?>"<?php echo $gep_home_hero_style; ?>><?php gep_theme_render_hero_video($gep_home['hero_video']??'',$gep_home['hero_image']??'','Video de cabecera de Inicio'); ?><div aria-hidden="true" class="hero-geo-ui"><div class="geo-frame"></div><span class="geo-label geo-label-a"><?php echo gep_home_h($gep_home['hero_geo_a']); ?></span><span class="geo-label geo-label-b"><?php echo gep_home_h($gep_home['hero_geo_b']); ?></span></div><div class="container hero-inner"><p class="eyebrow"><?php echo gep_home_h($gep_home['hero_eyebrow']); ?></p><h1><?php echo gep_home_h($gep_home['hero_title']); ?></h1><p><?php echo gep_home_h($gep_home['hero_text']); ?></p><div class="hero-actions"><?php if($gep_home['hero_primary_text']!==''&&empty($gep_home['hero_primary_off'])): ?><a class="btn btn-primary" href="<?php echo gep_home_url($gep_home['hero_primary_url']); ?>"><?php echo gep_home_h($gep_home['hero_primary_text']); ?></a><?php endif; ?><?php if($gep_home['hero_secondary_text']!==''&&empty($gep_home['hero_secondary_off'])): ?><a class="btn btn-ghost" href="<?php echo gep_home_url($gep_home['hero_secondary_url']); ?>"><?php echo gep_home_h($gep_home['hero_secondary_text']); ?></a><?php endif; ?></div></div></section>
<section data-gep-section="intro" class="section" id="laboratorio"><div class="container intro-grid"><div><p class="eyebrow"><?php echo gep_home_h($gep_home['intro_eyebrow']); ?></p><h2><?php echo gep_home_h($gep_home['intro_title']); ?></h2></div><div class="intro-copy"><p><?php echo gep_home_h($gep_home['intro_p1']); ?></p><p><?php echo gep_home_h($gep_home['intro_p2']); ?></p><div class="pill-row"><?php foreach((array)$gep_home['pills'] as $pill): if(trim((string)$pill)==='')continue; ?><span class="pill"><?php echo gep_home_h($pill); ?></span><?php endforeach; ?></div></div></div></section>
<section data-gep-section="stats" aria-label="GEP en cifras" class="impact-strip"><div class="container impact-grid"><?php foreach((array)$gep_home['stats'] as $row): ?><article class="impact-item"><strong><?php echo gep_home_h($row['value']??''); ?></strong><span><?php echo gep_home_h($row['label']??''); ?></span><small><?php echo gep_home_h($row['text']??''); ?></small></article><?php endforeach; ?></div></section>
<section data-gep-section="definitions" class="section compact definition-section"><div class="container"><div class="definition-grid"><?php foreach((array)$gep_home['definitions'] as $row): ?><article class="definition-card"><p class="eyebrow"><?php echo gep_home_h($row['eyebrow']??''); ?></p><h2><?php echo gep_home_h($row['title']??''); ?></h2><p><?php echo gep_home_h($row['text']??''); ?></p></article><?php endforeach; ?></div></div></section>
<section data-gep-section="field" aria-label="Registro visual de territorio" class="section field-notes"><div class="container"><figure class="field-photo-feature reveal-item"><?php if($gep_home['field_image']!==''): ?><img alt="<?php echo gep_home_h(gep_site_image_alt($gep_home['field_image'],$gep_home['field_alt'])); ?>" loading="lazy" src="<?php echo gep_home_url($gep_home['field_image']); ?>"/><?php endif; ?><span aria-hidden="true" class="field-photo-index"><?php echo gep_home_h($gep_home['field_index']); ?></span><figcaption class="field-photo-caption"><p class="eyebrow"><?php echo gep_home_h($gep_home['field_eyebrow']); ?></p><strong><?php echo gep_home_h($gep_home['field_title']); ?></strong><small><?php echo gep_home_h($gep_home['field_text']); ?></small></figcaption></figure></div></section>
<section data-gep-section="research" class="section research"><div class="container"><div class="section-head"><div><p class="eyebrow"><?php echo gep_home_h($gep_home['research_eyebrow']); ?></p><h2><?php echo gep_home_h($gep_home['research_title']); ?></h2></div><p><?php echo gep_home_h($gep_home['research_text']); ?></p></div><div class="research-grid"><?php foreach((array)$gep_home['research_cards'] as $row): ?><article class="research-card"><?php if(!empty($row['image'])): ?><img alt="<?php echo gep_home_h(gep_site_image_alt($row['image']??'',$row['alt']??'')); ?>" src="<?php echo gep_home_url($row['image']); ?>"/><?php endif; ?><div class="research-copy"><span><?php echo gep_home_h($row['kicker']??''); ?></span><h3><?php echo gep_home_h($row['title']??''); ?></h3><p><?php echo gep_home_h($row['text']??''); ?></p></div></article><?php endforeach; ?></div></div></section>
<section data-gep-section="portal" class="section"><div class="container"><div class="section-head"><div><p class="eyebrow"><?php echo gep_home_h($gep_home['portal_eyebrow']); ?></p><h2><?php echo gep_home_h($gep_home['portal_title']); ?></h2></div><p><?php echo gep_home_h($gep_home['portal_text']); ?></p></div><div class="portal-grid"><?php foreach((array)$gep_home['portal_cards'] as $row): $href=$row['url']??''; ?><a class="portal-card" href="<?php echo gep_home_url($href); ?>"<?php echo gep_home_external($href)?' target="_blank" rel="noopener"':''; ?>><div><div class="portal-icon"><?php echo gep_home_h($row['icon']??''); ?></div><h3><?php echo gep_home_h($row['title']??''); ?></h3><p><?php echo gep_home_h($row['text']??''); ?></p></div><span><?php echo gep_home_h($row['button']??''); ?></span></a><?php endforeach; ?></div></div></section>
<section data-gep-section="landscape"<?php echo gep_site_landscape_attrs($gep_home,'gep_home_url'); ?>><div class="landscape-content"><p><?php echo gep_home_h($gep_home['landscape_label']); ?></p><h2><?php echo gep_home_h($gep_home['landscape_title']); ?></h2></div></section>
<section data-gep-section="resources" class="section archive-resources"><div class="container"><div class="section-head"><div><p class="eyebrow"><?php echo gep_home_h($gep_home['resources_eyebrow']); ?></p><h2><?php echo gep_home_h($gep_home['resources_title']); ?></h2></div><p><?php echo gep_home_h($gep_home['resources_text']); ?></p></div><div class="resource-grid"><?php foreach((array)$gep_home['resources'] as $row): $href=$row['url']??''; ?><a class="resource-card" href="<?php echo gep_home_url($href); ?>"<?php echo gep_home_external($href)?' rel="noopener" target="_blank"':''; ?>><span><?php echo gep_home_h($row['kicker']??''); ?></span><h3><?php echo gep_home_h($row['title']??''); ?></h3><b><?php echo gep_home_h($row['button']??''); ?></b></a><?php endforeach; ?></div></div></section>
<section data-gep-section="topics" class="section compact"><div class="container"><div class="section-head"><div><p class="eyebrow"><?php echo gep_home_h($gep_home['topics_eyebrow']); ?></p><h2><?php echo gep_home_h($gep_home['topics_title']); ?></h2></div><p><?php echo gep_home_h($gep_home['topics_text']); ?></p></div><div class="topic-cloud"><?php foreach((array)$gep_home['topics'] as $topic): if(trim((string)$topic)==='')continue; ?><span><?php echo gep_home_h($topic); ?></span><?php endforeach; ?></div></div></section>
<section data-gep-section="network" class="section"><div class="container networks-grid"><div class="network-logo"><?php if($gep_home['network_image']!==''): ?><img alt="<?php echo gep_home_h(gep_site_image_alt($gep_home['network_image'],$gep_home['network_alt'])); ?>" src="<?php echo gep_home_url($gep_home['network_image']); ?>"/><?php endif; ?></div><div class="network-copy"><p class="eyebrow"><?php echo gep_home_h($gep_home['network_eyebrow']); ?></p><h2><?php echo gep_home_h($gep_home['network_title']); ?></h2><p><?php echo gep_home_h($gep_home['network_text']); ?></p><div class="network-names"><?php foreach((array)$gep_home['network_links'] as $row): $href=$row['url']??''; ?><a href="<?php echo gep_home_url($href); ?>"<?php echo gep_home_external($href)?' rel="noopener" target="_blank"':''; ?>><?php echo gep_home_h($row['title']??''); ?></a><?php endforeach; ?></div></div></div></section>
<?php $gep_home_note=gep_site_public_note($gep_home['note']); if($gep_home_note!==''): ?><section data-gep-section="final" class="section compact"><div class="container"><div class="source-note"><span class="source-note-icon">i</span><p><?php echo gep_home_h($gep_home_note); ?></p></div></div></section><?php endif; ?>
<section data-gep-section="final" class="cta-band cta-final"><div class="container cta-inner"><div><p class="eyebrow light"><?php echo gep_home_h($gep_home['cta_eyebrow']); ?></p><h2><?php echo gep_home_h($gep_home['cta_title']); ?></h2></div><?php if($gep_home['cta_button']!==''&&empty($gep_home['cta_off'])): ?><a class="btn btn-primary" href="<?php echo gep_home_url($gep_home['cta_url']); ?>"><?php echo gep_home_h($gep_home['cta_button']); ?></a><?php endif; ?></div></section>
</main>
<?php require __DIR__ . '/includes/footer.php'; ?>
