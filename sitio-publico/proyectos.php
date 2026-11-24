<?php
/**
 * GEP · Proyectos
 * Compatible con sitio PHP externo + WordPress detectado automáticamente.
 * Lee GEP Editor Beta v9.9.0 cuando está disponible y conserva el contenido original como respaldo.
 */
require_once __DIR__ . '/includes/wp-bootstrap.php';
gep_site_bootstrap_wordpress();
function gep_projects_h($value){return htmlspecialchars((string)$value,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function gep_projects_url($value){$value=trim((string)$value);if($value==='')return '';return function_exists('esc_url_raw')?esc_url_raw($value):filter_var($value,FILTER_SANITIZE_URL);}
function gep_projects_slug($value){$value=trim((string)$value);if($value==='')return '';if(function_exists('sanitize_title'))return sanitize_title($value);$ascii=function_exists('iconv')?@iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$value):$value;if($ascii!==false)$value=$ascii;$value=strtolower($value);$value=preg_replace('/[^a-z0-9]+/','-',$value);return trim((string)$value,'-');}
function gep_projects_fallback_image($row){ return ''; }
$gep_projects_defaults = json_decode(<<<'JSON'
{
  "hero_eyebrow": "Investigación aplicada",
  "hero_title": "Proyectos",
  "hero_text": "Una trayectoria de investigación aplicada en geomática, biodiversidad, conservación, paisaje, teledetección y planificación territorial.",
  "hero_image": "",
  "hero_video": "",
  "nav_featured": "Vigentes",
  "nav_trajectory": "Trayectoria",
  "nav_areas": "Áreas de trabajo",
  "features": [
    {
      "image": "",
      "alt": "Vista satelital de un mosaico rural con caminos, vegetación y asentamientos",
      "kicker": "2014–2016 · FONDECYT 1140319",
      "title": "Vegetation Knowledge-based Indicators for Urban Sustainable Planning",
      "text": "El proyecto más reciente del trayectoria principal del GEP."
    },
    {
      "image": "",
      "alt": "Vista aérea de un paisaje montañoso atravesado por una carretera",
      "kicker": "2006–2016",
      "title": "Una década de investigación aplicada",
      "text": "Conservación, biodiversidad, sensores remotos, biomasa, cambio climático, fauna, arbolado urbano y planificación territorial."
    }
  ],
  "trajectory_eyebrow": "2006 — 2016",
  "trajectory_title": "Proyectos cerrados · trayectoria",
  "trajectory_text": "Explora los 16 registros del sitio GEP por período o busca por institución, tema o palabra clave.",
  "projects": [
    {
      "year": "2014–2016",
      "era": "2014-2016",
      "theme": "Investigación aplicada",
      "title": "FONDECYT 1140319",
      "text": "Vegetation Knowledge-based Indicators for Urban Sustainable Planning.",
      "url": "https://www.gep.uchile.cl/Proyectos/FONDECYT%20ABSTRACT%20JHP%202014-2016.pdf",
      "button": "Abstract ↗",
      "tag": "Proyecto GEP"
    },
    {
      "year": "2014",
      "era": "2014-2016",
      "theme": "Teledetección",
      "title": "UC Berkeley–Chile Grant",
      "text": "Development of advanced remote sensing methods for mapping and managing plant species diversity in Mediterranean Forests of Chile and California.",
      "url": "https://www.gep.uchile.cl/Proyectos/Brief%20UCB-UC%20proposal%20Biging-Hern%C3%A1ndez.pdf",
      "button": "Brief ↗",
      "tag": "Proyecto GEP"
    },
    {
      "year": "2014–2015",
      "era": "2014-2016",
      "theme": "Ciudad y paisaje",
      "title": "FPA",
      "text": "Bosques de Santiago Andino: Opciones de valoración y recuperación.",
      "url": "https://www.gep.uchile.cl/Proyectos/Ficha%20de%20Proyecto%20-%20Bosques%20de%20Santiago%20Andino%20FPA%202014-2015.pdf",
      "button": "Ficha de proyecto ↗",
      "tag": "Proyecto GEP"
    },
    {
      "year": "2012–2013",
      "era": "2010-2013",
      "theme": "Fauna y biodiversidad",
      "title": "GORE Arica y Parinacota",
      "text": "Diagnóstico Levantamiento Biodiversidad Región Arica y Parinacota.",
      "url": "https://www.gep.uchile.cl/Proyectos/FICHA%20Proyecto%20Arica%20y%20Parinacota.pdf",
      "button": "Ficha de proyecto ↗",
      "tag": "Proyecto GEP"
    },
    {
      "year": "2012–2013",
      "era": "2010-2013",
      "theme": "Fauna y biodiversidad",
      "title": "SAG",
      "text": "Diagnóstico de la ecología poblacional de la vicuña (Vicugna vicugna mensalis), taruka (Hippocamelus anticensis) y guanaco (Lama guanicoe cacsilensis) y medidas de solución al conflicto silvoagropecuario – ungulados silvestres en la Región de Tarapacá.",
      "url": "https://www.gep.uchile.cl/Proyectos/Ficha_Ongulados.pdf",
      "button": "Ficha de proyecto ↗",
      "tag": "Proyecto GEP"
    },
    {
      "year": "2010–2013",
      "era": "2010-2013",
      "theme": "Fauna y biodiversidad",
      "title": "FONDECYT 1100339",
      "text": "Spatial epidemiology of Chagas disease: using environmental variables and reservoirs distribution to predict the distribution of sylvatic foci of Triatoma infestans and Mepraia spinolai.",
      "url": "https://www.gep.uchile.cl/Proyectos/Chagas/Resumen%20FONDECYT%20CHAGAS.pdf",
      "button": "Ficha de proyecto ↗",
      "tag": "Proyecto GEP"
    },
    {
      "year": "2010–2013",
      "era": "2010-2013",
      "theme": "Bosques y biomasa",
      "title": "INNOVA-CORFO · Biocomsa",
      "text": "Evaluación y monitoreo de las existencias de biomasa aérea y optimización de redes de abastecimiento a plantas de biocombustible, a partir de material lignocelulósico.",
      "url": "https://www.gep.uchile.cl/Proyectos/Ficha_proyecto_Biocomsa.pdf",
      "button": "Ficha de proyecto ↗",
      "tag": "Proyecto GEP"
    },
    {
      "year": "2010",
      "era": "2010-2013",
      "theme": "Planificación territorial",
      "title": "Bahía Exploradores · Región de Aysén",
      "text": "Análisis y diagnóstico de componentes ambientales. Plan de manejo y zonificación. Asesoría y gestión Travesías Sociedad por Acciones.",
      "url": "https://www.gep.uchile.cl/Servidor%20de%20mapas/servidos_mapas_Exploradores.html",
      "button": "Ver mapa ↗",
      "tag": "Proyecto GEP"
    },
    {
      "year": "2009–2010",
      "era": "2010-2013",
      "theme": "Fauna y biodiversidad",
      "title": "SAG · Programa de Monitoreo Poblacional del Suri",
      "text": "Programa de Monitoreo Poblacional del Suri (Pterocnemia pennata tarapacensis) en las comunas de Ollagüe, Calama, San Pedro de Atacama y Antofagasta.",
      "url": "https://www.gep.uchile.cl/Proyectos/ESTUDIO%20POBLACIONAL%20DEL%20SURI%20(Pterocnemia%20pennata%20tarapacensis)%20EN%20LAS%20COMUNAS%20DE%20OLLAG%C3%9CE%2c%20SAN%20PEDRO%20DE%20ATACAMA%20Y%20ANTOFAGASTA.pdf",
      "button": "Informe ↗",
      "tag": "Proyecto GEP"
    },
    {
      "year": "2009–2011",
      "era": "2010-2013",
      "theme": "Ciudad y paisaje",
      "title": "FONDECYT 1090199 · Río Mapocho",
      "text": "Corredor fluvial del Río Mapocho siglo XXI: hacia un conjunto de variables para la definición y el manejo de un polígono de intervención multifacética e integral entre Quinta Normal y El Monte. Investigadora Responsable: María Isabel Pavés. El registro histórico incorpora además: 2009 (Marzo–Agosto), Congreso Nacional, Estudio sobre la Gestión Integrada de los Recursos Hídricos en Chile.",
      "url": "https://www.gep.uchile.cl/Servidor%20de%20mapas/servidos_mapocho.html",
      "button": "Ver mapa ↗",
      "tag": "Proyecto GEP"
    },
    {
      "year": "2009 · Ene–Abr",
      "era": "2006-2009",
      "theme": "Planificación territorial",
      "title": "PNUD · Región de Los Ríos",
      "text": "Estudio Diagnóstico y validación: áreas claves de conservación de la depresión intermedia y serranías transversales de la XIV Región de Los Ríos. Co-director.",
      "url": "https://www.gep.uchile.cl/Proyectos/DIAGNOSTICO%20Y%20VALIDACI%C3%93N%20%C3%81REAS%20CLAVES%20REGI%C3%93N%20XIV.pdf",
      "button": "Informe ↗",
      "tag": "Proyecto GEP"
    },
    {
      "year": "2009 · Ene–Abr",
      "era": "2006-2009",
      "theme": "Planificación territorial",
      "title": "CONAMA",
      "text": "Estudio de Valoración de Propuestas de Sistemas de Clasificación de Ecosistemas Terrestres.",
      "url": "",
      "button": "",
      "tag": "Proyecto GEP"
    },
    {
      "year": "2008 · Abr–Oct",
      "era": "2006-2009",
      "theme": "Fauna y biodiversidad",
      "title": "CONAF · Evaluación poblacional del Suri",
      "text": "Evaluación poblacional del Suri (Rhea pennata tarapacensis) en las regiones de Arica y Parinacota, y Tarapacá.",
      "url": "https://www.gep.uchile.cl/Proyectos/EVALUACI%C3%93N%20POBLACIONAL%20DEL%20SURI%20(Pterocnemia%20pennata%20tarapacensis)%20TARAPACA%20y%20PARINACOTA-2008.pdf",
      "button": "Informe ↗",
      "tag": "Proyecto GEP"
    },
    {
      "year": "2008 · May–Nov",
      "era": "2006-2009",
      "theme": "Cambio climático",
      "title": "CONAMA · Co-investigador",
      "text": "Análisis de vulnerabilidad y adaptación del sector silvoagropecuario y de los recursos hídricos y edáficos de Chile frente al cambio climático.",
      "url": "",
      "button": "",
      "tag": "Proyecto GEP"
    },
    {
      "year": "2008–2010",
      "era": "2010-2013",
      "theme": "Fauna y biodiversidad",
      "title": "Celulosa Arauco y Constitución S.A. · Planta Nueva Aldea",
      "text": "Monitoreo de Vegetación, Flora y Fauna en Planta Nueva Aldea.",
      "url": "",
      "button": "",
      "tag": "Proyecto GEP"
    },
    {
      "year": "2006–2007",
      "era": "2006-2009",
      "theme": "Ciudad y paisaje",
      "title": "Municipalidad de San Bernardo",
      "text": "Estudio de Diagnóstico del Estado de Conservación del Arbolado Urbano Viario Céntrico de la Comuna de San Bernardo y Proposición Técnica de Plan de Manejo.",
      "url": "",
      "button": "",
      "tag": "Proyecto GEP"
    }
  ],
  "note": "",
  "areas_eyebrow": "Áreas de trabajo",
  "areas_title": "Una investigación conectada por múltiples escalas.",
  "areas_text": "Los proyectos recuperados muestran cruces entre sistemas naturales, infraestructura, ciudades, biodiversidad y herramientas geoespaciales.",
  "areas": [
    {
      "icon": "＋",
      "title": "Teledetección",
      "text": "Sensores remotos, cartografía y clasificación de coberturas."
    },
    {
      "icon": "＋",
      "title": "Biodiversidad",
      "text": "Fauna, flora, conservación y monitoreo poblacional."
    },
    {
      "icon": "＋",
      "title": "Bosques y biomasa",
      "text": "Estructura de vegetación, biomasa y carbono."
    },
    {
      "icon": "＋",
      "title": "Ciudad y paisaje",
      "text": "Arbolado urbano, corredores y transformación metropolitana."
    },
    {
      "icon": "＋",
      "title": "Cambio climático",
      "text": "Vulnerabilidad, adaptación y escenarios territoriales."
    },
    {
      "icon": "＋",
      "title": "Planificación",
      "text": "Zonificación, manejo y apoyo a decisiones espaciales."
    }
  ]
}
JSON
,true);
$gep_projects=$gep_projects_defaults;$gep_projects_post_id=0;
if(function_exists('gep_demo_get_page_navigation')){$n=gep_demo_get_page_navigation();if(!empty($n['proyectos']['page']->ID))$gep_projects_post_id=(int)$n['proyectos']['page']->ID;}
if(!$gep_projects_post_id&&function_exists('get_page_by_path')){$p=get_page_by_path('proyectos',OBJECT,'page');if($p&&!empty($p->ID))$gep_projects_post_id=(int)$p->ID;}
if($gep_projects_post_id&&function_exists('gep_demo_projects_values')){$saved=gep_demo_projects_values($gep_projects_post_id);if(is_array($saved)){$gep_projects=array_merge($gep_projects_defaults,$saved);foreach(array('features','projects','areas') as $k)if(!isset($gep_projects[$k])||!is_array($gep_projects[$k]))$gep_projects[$k]=$gep_projects_defaults[$k];}}
if(isset($gep_projects['nav_featured'])&&trim((string)$gep_projects['nav_featured'])==='Destacados')$gep_projects['nav_featured']='Vigentes';

$gep_project_slugs=array();
foreach($gep_projects['projects'] as $gep_project_index=>&$gep_project_record){
    if(!is_array($gep_project_record))$gep_project_record=array();
    $gep_project_record+=array(
        'slug'=>'','status'=>'cerrado','image'=>'','image_alt'=>'','details'=>'','institution'=>'','lead'=>'','gallery'=>'','detail_url'=>'',
        'year'=>'','era'=>'','theme'=>'','title'=>'','text'=>'','tag'=>'','url'=>'','button'=>''
    );
    $gep_project_record['status']=strtolower(trim((string)$gep_project_record['status']))==='vigente'?'vigente':'cerrado';
    $gep_project_record['_index']=(int)$gep_project_index;
    $gep_slug=gep_projects_slug($gep_project_record['slug']);
    if($gep_slug==='')$gep_slug=gep_projects_slug(trim((string)$gep_project_record['title']).'-'.trim((string)$gep_project_record['year']));
    if($gep_slug==='')$gep_slug='proyecto-'.($gep_project_index+1);
    $gep_base_slug=$gep_slug;$gep_slug_n=2;
    while(isset($gep_project_slugs[$gep_slug])){$gep_slug=$gep_base_slug.'-'.$gep_slug_n;$gep_slug_n++;}
    $gep_project_slugs[$gep_slug]=true;
    $gep_project_record['_slug']=$gep_slug;
}
unset($gep_project_record);

$gep_current_projects=array();
$gep_closed_projects=array();
foreach($gep_projects['projects'] as $gep_project_record){
    if(!is_array($gep_project_record))continue;
    if(($gep_project_record['status']??'cerrado')==='vigente')$gep_current_projects[]=$gep_project_record;
    else $gep_closed_projects[]=$gep_project_record;
}

$pageTitle='GEP · Proyectos';$pageDescription='Proyectos de investigación aplicada del Laboratorio GEP.';$bodyClass='page-proyectos';$activePage='proyectos';$isHome=false;require __DIR__.'/includes/header.php';
$gep_eras=array();foreach($gep_closed_projects as $r){if(!is_array($r))continue;$e=trim(isset($r['era'])?$r['era']:'');if($e!==''&&!in_array($e,$gep_eras,true))$gep_eras[]=$e;}
?>
<main>
<?php $gep_projects_hero_style=!empty($gep_projects['hero_image'])?' style="background-image:url(' . gep_projects_h(gep_projects_url($gep_projects['hero_image'])) . ');"':''; ?>
<section data-gep-section="hero" class="page-hero hero-proyectos<?php echo !empty($gep_projects['hero_video'])?' has-video-bg':''; ?>"<?php echo $gep_projects_hero_style; ?>><?php gep_theme_render_hero_video($gep_projects['hero_video']??'',$gep_projects['hero_image']??'','Video de cabecera de Proyectos'); ?><div class="container page-hero-content"><p class="eyebrow"><?php echo gep_projects_h($gep_projects['hero_eyebrow']); ?></p><h1><?php echo gep_projects_h($gep_projects['hero_title']); ?></h1><p><?php echo gep_projects_h($gep_projects['hero_text']); ?></p></div></section>

<nav data-gep-section="hero" aria-label="Navegación de sección" class="section-nav">
  <div class="container section-nav-inner">
    <a href="#vigentes"><?php echo gep_projects_h($gep_projects['nav_featured']); ?></a>
    <a href="#trayectoria"><?php echo gep_projects_h($gep_projects['nav_trajectory']); ?></a>
    <a href="#areas"><?php echo gep_projects_h($gep_projects['nav_areas']); ?></a>
  </div>
</nav>

<section data-gep-section="features" class="section" id="vigentes">
  <div class="container">
    <div class="section-head">
      <div><p class="eyebrow">En desarrollo</p><h2>Proyectos vigentes</h2></div>
      <p>Proyectos actualmente activos del laboratorio.</p>
    </div>

    <?php if($gep_current_projects): ?>
      <div class="current-projects-overview" aria-label="Resumen de proyectos vigentes">
        <div><strong><?php echo count($gep_current_projects); ?></strong><span><?php echo count($gep_current_projects)===1?'proyecto vigente':'proyectos vigentes'; ?></span></div>
        <p>Investigación activa, monitoreo territorial y colaboración interinstitucional reunidos en fichas individuales.</p>
      </div>
      <div class="current-projects-grid">
        <?php foreach($gep_current_projects as $r):
          $detail_url=trim((string)($r['detail_url']??''));
          $local_detail='proyecto.php?proyecto='.rawurlencode((string)($r['_slug']??''));
          $card_url=$detail_url!==''?$detail_url:$local_detail;
          $card_external=$detail_url!==''&&preg_match('#^https?://#i',$detail_url);
        ?>
        <?php $card_image=!empty($r['image'])?gep_projects_url($r['image']):''; ?>
        <a class="current-project-card<?php echo !$card_image?' has-no-image':''; ?>" href="<?php echo gep_projects_h(gep_projects_url($card_url)); ?>"<?php echo $card_external?' rel="noopener" target="_blank"':''; ?> aria-label="Abrir ficha: <?php echo gep_projects_h($r['title']); ?>">
          <?php if($card_image): ?><div class="current-project-media"><img src="<?php echo gep_projects_h($card_image); ?>" alt="<?php echo gep_projects_h(gep_site_image_alt($card_image,$r['image_alt'],'Imagen territorial asociada a '.$r['title'])); ?>" loading="lazy" decoding="async"><span class="current-project-media-badge">Imagen del proyecto</span></div><?php endif; ?>
          <div class="current-project-body">
            <div class="current-project-meta"><span class="project-status-pill">Vigente</span><?php if(!empty($r['year'])): ?><span><?php echo gep_projects_h($r['year']); ?></span><?php endif; ?></div>
            <?php if(!empty($r['theme'])): ?><span class="project-theme"><?php echo gep_projects_h($r['theme']); ?></span><?php endif; ?>
            <h3><?php echo gep_projects_h($r['title']); ?></h3>
            <?php if(!empty($r['institution'])): ?><p class="current-project-institution"><?php echo gep_projects_h($r['institution']); ?></p><?php endif; ?>
            <?php if(!empty($r['text'])): ?><p><?php echo gep_projects_h($r['text']); ?></p><?php endif; ?>
            <span class="record-link current-project-link">Ver ficha →</span>
          </div>
        </a>
        <?php endforeach; ?>
      </div>
    <?php else: ?>
      <div class="empty-state project-status-empty"><strong>No hay proyectos marcados como vigentes todavía.</strong><p>Los proyectos existentes se mantienen como cerrados por compatibilidad. Márcalos como “Vigente” desde el editor cuando corresponda.</p></div>
    <?php endif; ?>
  </div>
</section>

<section data-gep-section="trajectory" class="section project-history-section" id="trayectoria">
  <div class="container">
    <div class="section-head"><div><p class="eyebrow"><?php echo gep_projects_h($gep_projects['trajectory_eyebrow']); ?></p><h2><?php echo gep_projects_h($gep_projects['trajectory_title']); ?></h2></div><p><?php echo gep_projects_h($gep_projects['trajectory_text']); ?></p></div>

    <div class="project-tools"><div class="filter-topline"><strong>Filtrar trayectoria</strong><span class="result-count" id="projectCount"><?php echo count($gep_closed_projects); ?> proyectos</span></div><div class="filter-row"><label aria-label="Buscar proyectos" class="search-field"><span>⌕</span><input id="projectSearch" placeholder="Buscar institución, proyecto o tema…" type="search"/></label><div class="filter-pills"><button aria-pressed="true" class="filter-pill active" data-era="all" type="button">Todos</button><?php foreach($gep_eras as $era): ?><button aria-pressed="false" class="filter-pill" data-era="<?php echo gep_projects_h($era); ?>" type="button"><?php echo gep_projects_h(str_replace('-','–',$era)); ?></button><?php endforeach; ?></div></div></div>

    <?php if($gep_closed_projects): ?>
    <div class="project-index project-timeline" id="projectTimeline">
      <?php foreach($gep_closed_projects as $r): if(!is_array($r))continue; $local_detail='proyecto.php?proyecto='.rawurlencode((string)($r['_slug']??'')); ?>
      <article class="project-row" data-era="<?php echo gep_projects_h(isset($r['era'])?$r['era']:''); ?>" data-theme="<?php echo gep_projects_h(isset($r['theme'])?$r['theme']:''); ?>">
        <div class="project-year"><?php echo gep_projects_h(isset($r['year'])?$r['year']:''); ?></div>
        <div class="project-row-main"><span class="project-theme"><?php echo gep_projects_h(isset($r['theme'])?$r['theme']:''); ?></span><h3><?php echo gep_projects_h(isset($r['title'])?$r['title']:''); ?></h3><p><?php echo gep_projects_h(isset($r['text'])?$r['text']:''); ?></p><div class="project-row-links"><a class="record-link" href="<?php echo gep_projects_h($local_detail); ?>">Ver ficha →</a><?php if(!empty($r['url'])): ?><a class="record-link secondary" href="<?php echo gep_projects_h(gep_projects_url($r['url'])); ?>" rel="noopener" target="_blank"><?php echo gep_projects_h(!empty($r['button'])?$r['button']:'Abrir recurso ↗'); ?></a><?php endif; ?></div></div>
        <span class="project-tag"><?php echo gep_projects_h(isset($r['tag'])?$r['tag']:''); ?></span>
      </article>
      <?php endforeach; ?>
    </div>
    <div class="empty-state" hidden id="projectEmpty">No hay proyectos que coincidan con el filtro.</div>
    <?php else: ?>
    <div class="empty-state"><strong>No hay proyectos cerrados.</strong></div>
    <?php endif; ?>

    <?php $gep_projects['note']=gep_site_public_note($gep_projects['note']); if(!empty($gep_projects['note'])): ?><div class="source-note"><span class="source-note-icon">i</span><p><?php echo gep_projects_h($gep_projects['note']); ?></p></div><?php endif; ?>
  </div>
</section>

<section data-gep-section="areas" class="section compact project-themes-section" id="areas"><div class="container"><div class="section-head"><div><p class="eyebrow"><?php echo gep_projects_h($gep_projects['areas_eyebrow']); ?></p><h2><?php echo gep_projects_h($gep_projects['areas_title']); ?></h2></div><p><?php echo gep_projects_h($gep_projects['areas_text']); ?></p></div><div class="theme-grid"><?php foreach($gep_projects['areas'] as $a): if(!is_array($a))continue; ?><article class="theme-card"><span class="theme-icon"><?php echo gep_projects_h(isset($a['icon'])?$a['icon']:'＋'); ?></span><h3><?php echo gep_projects_h(isset($a['title'])?$a['title']:''); ?></h3><p><?php echo gep_projects_h(isset($a['text'])?$a['text']:''); ?></p></article><?php endforeach; ?></div></div></section>
</main>
<script>
(function(){var input=document.getElementById('projectSearch'),timeline=document.getElementById('projectTimeline'),count=document.getElementById('projectCount'),empty=document.getElementById('projectEmpty'),buttons=[].slice.call(document.querySelectorAll('.filter-pill'));if(!timeline)return;var era='all';function norm(v){return (v||'').toString().toLowerCase().normalize? (v||'').toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g,''):(v||'').toString().toLowerCase();}function run(){var q=norm(input?input.value:''),shown=0;[].slice.call(timeline.querySelectorAll('.project-row')).forEach(function(row){var okEra=era==='all'||row.getAttribute('data-era')===era,okSearch=!q||norm(row.textContent+' '+(row.getAttribute('data-theme')||'')).indexOf(q)>=0,show=okEra&&okSearch;row.hidden=!show;if(show)shown++;});if(count)count.textContent=shown+' '+(shown===1?'proyecto':'proyectos');if(empty)empty.hidden=shown!==0;}buttons.forEach(function(btn){btn.addEventListener('click',function(){era=btn.getAttribute('data-era')||'all';buttons.forEach(function(b){var active=b===btn;b.classList.toggle('active',active);b.setAttribute('aria-pressed',active?'true':'false');});run();});});if(input)input.addEventListener('input',run);run();})();
</script>
<?php require __DIR__.'/includes/footer.php'; ?>
