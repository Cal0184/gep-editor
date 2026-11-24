<?php
/** GEP · Docencia — compatible con GEP Editor Beta v9.7 */
require_once __DIR__ . '/includes/wp-bootstrap.php';
gep_site_bootstrap_wordpress();
function gep_docencia_h($v){return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function gep_docencia_url($v){$v=trim((string)$v);if($v==='')return '';return function_exists('esc_url_raw')?esc_url_raw($v):filter_var($v,FILTER_SANITIZE_URL);}

$gep_docencia_defaults=array(
 'hero_eyebrow'=>'Formación','hero_title'=>'Docencia','hero_text'=>'Cursos y programas vinculados a cartografía, SIG, teledetección, geomática, ecología del paisaje y análisis estadístico.','hero_image'=>'','hero_video'=>'',
 'nav_pregrado'=>'Pregrado','nav_postgrado'=>'Postgrado','nav_diplomado'=>'Diplomado','nav_cursos'=>'Cursos y talleres','nav_campos'=>'Campos de formación',
 'learning_steps'=>array(array('number'=>'01','title'=>'Representar','text'=>'Cartografía y sistemas de referencia para organizar información territorial.'),array('number'=>'02','title'=>'Analizar','text'=>'SIG, teledetección y estadística para transformar datos en evidencia.'),array('number'=>'03','title'=>'Interpretar','text'=>'Ecología del paisaje para conectar patrones espaciales, procesos y cambio.')),
 'pre_eyebrow'=>'Pregrado','pre_title'=>'Bases para leer y representar el territorio.','pre_text'=>'Cursos de pregrado asociados directamente al trabajo del laboratorio.',
 'pre_courses'=>array(array('code'=>'PRE 01','title'=>'Cartografía y Teledetección','status'=>'Programa disponible','url'=>'https://www.gep.uchile.cl/Docencia/Programa%20Cartografia%20y%20teledeteccion%202011.pdf','button'=>'PDF programa ↗','image'=>'','image_alt'=>''),array('code'=>'PRE 02','title'=>'Sistemas de Información Geográfica','status'=>'Programa disponible','url'=>'https://www.gep.uchile.cl/Docencia/Programa%20SIG%202011.pdf','button'=>'PDF programa ↗','image'=>'','image_alt'=>'')),
 'post_eyebrow'=>'Postgrado','post_title'=>'Profundización y herramientas aplicadas.','post_text'=>'Cursos avanzados vinculados a geomática, ecología del paisaje y análisis territorial.',
 'post_courses'=>array(array('code'=>'ADV 01','title'=>'Ecología del Paisaje','status'=>'Programa disponible','url'=>'https://www.gep.uchile.cl/Docencia/Programa%20EcologiaPaisaje2010.pdf','button'=>'Abrir ↗','image'=>'','image_alt'=>''),array('code'=>'ADV 02','title'=>'Geomática I - Nivel Básico','status'=>'Programa disponible','url'=>'https://www.gep.uchile.cl/Docencia/Programa%20Geom%C3%A1tica%20I%20-%202011.pdf','button'=>'Abrir ↗','image'=>'','image_alt'=>''),array('code'=>'ADV 03','title'=>'Geomática II - Nivel Intermedio','status'=>'Programa disponible','url'=>'https://www.gep.uchile.cl/Docencia/Programa%20Geom%C3%A1tica%20II%20-%20Intermedio%202010.pdf','button'=>'Abrir ↗','image'=>'','image_alt'=>''),array('code'=>'ADV 05','title'=>'Estadística Aplicada con R Project','status'=>'Programa disponible','url'=>'https://www.gep.uchile.cl/Docencia/Programa%20EARN%202010.pdf','button'=>'Abrir ↗','image'=>'','image_alt'=>'')),
 'diploma_eyebrow'=>'Diplomado','diploma_title'=>'Formación profesional especializada.','diploma_text'=>'Programas de diplomado vinculados al trabajo del laboratorio.','diploma_courses'=>array(array('code'=>'DIP 01','title'=>'Diplomado en Geomática Aplicada','status'=>'Referencia de programa','url'=>'http://www.forestal.uchile.cl/cursos/80155/diplomado-en-geomatica-aplicada','button'=>'Abrir ↗','image'=>'','image_alt'=>'')),
 'workshop_eyebrow'=>'Cursos y talleres','workshop_title'=>'Formación breve y actividades aplicadas.','workshop_text'=>'Cursos, talleres y materiales históricos para practicar herramientas geoespaciales.','workshop_courses'=>array(array('code'=>'TALL 01','title'=>'Curso de ArcGIS 9.2','status'=>'Material histórico disponible','url'=>'https://www.gep.uchile.cl/Publicaciones/Curso%20de%20ArcGIS%209.2%20-%20Hern%C3%A1ndez%20y%20Montaner%202008.pdf','button'=>'Abrir material ↗','image'=>'','image_alt'=>''),array('code'=>'TALL 02','title'=>'Tratamiento Digital de Imágenes','status'=>'Material histórico disponible','url'=>'https://www.gep.uchile.cl/Publicaciones/Tratamiento%20Digital%20de%20Im%C3%A1genes%20-%20Jaime%20Hern%C3%A1ndez%202011.pdf','button'=>'Abrir material ↗','image'=>'','image_alt'=>'')),
 'note'=>'',
 'fields_eyebrow'=>'Campos de formación','fields_title'=>'Cartografía, SIG, teledetección, geomática y ecología del paisaje.','fields_text'=>'La docencia histórica del GEP conecta herramientas de información geográfica con análisis ecológico y territorial.','fields_image_url'=>'','fields_image_alt'=>'Bosque con araucarias como paisaje de referencia para formación en ecología y territorio'
);
$gep_docencia=$gep_docencia_defaults;$gep_docencia_post_id=0;
if(function_exists('gep_demo_get_page_navigation')){$n=gep_demo_get_page_navigation();if(!empty($n['docencia']['page']->ID))$gep_docencia_post_id=(int)$n['docencia']['page']->ID;}
if(!$gep_docencia_post_id&&function_exists('get_page_by_path')){$pg=get_page_by_path('docencia',OBJECT,'page');if($pg&&!empty($pg->ID))$gep_docencia_post_id=(int)$pg->ID;}
if($gep_docencia_post_id&&function_exists('gep_demo_docencia_values')){$saved=gep_demo_docencia_values($gep_docencia_post_id);if(is_array($saved))$gep_docencia=array_merge($gep_docencia_defaults,$saved);}
foreach(array('learning_steps','pre_courses','post_courses','diploma_courses','workshop_courses') as $k)if(!isset($gep_docencia[$k])||!is_array($gep_docencia[$k]))$gep_docencia[$k]=$gep_docencia_defaults[$k];
$gep_fields_image=!empty($gep_docencia['fields_image_url'])?gep_docencia_url($gep_docencia['fields_image_url']):'';

function gep_docencia_fallback_image($group,$index){ return ''; }
function gep_docencia_render_courses($courses,$group){
    if(empty($courses)){echo '<div class="course-empty">Aún no hay registros en esta categoría.</div>';return;}
    echo '<div class="course-card-grid">';
    foreach($courses as $c){
        if(!is_array($c))continue;
        $img=!empty($c['image'])?gep_docencia_url($c['image']):'';
        echo '<article class="course-card'.($img?' has-image':' has-no-image').'">';
        if($img){
            echo '<div class="course-card-media"><img loading="lazy" decoding="async" src="'.gep_docencia_h($img).'" alt="'.gep_docencia_h(gep_site_image_alt($img,$c['image_alt']??'',$c['title']??'Curso')).'"></div>';
        }
        echo '<div class="course-card-body"><div class="course-card-topline"><span class="course-code">'.gep_docencia_h(isset($c['code'])?$c['code']:'').'</span>'.(!empty($c['status'])?'<span class="course-status">'.gep_docencia_h($c['status']).'</span>':'').'</div><h3>'.gep_docencia_h(isset($c['title'])?$c['title']:'').'</h3>';
        if(!empty($c['url']))echo '<a class="record-link" target="_blank" rel="noopener" href="'.gep_docencia_h(gep_docencia_url($c['url'])).'">'.gep_docencia_h(!empty($c['button'])?$c['button']:'Abrir ↗').'</a>';
        echo '</div></article>';
    }
    echo '</div>';
}
function gep_docencia_render_group($id,$eyebrow,$title,$text,$courses,$group,$sub=false){?>
    <div class="section-head<?php echo $sub?' subhead':''; ?>" id="<?php echo gep_docencia_h($id); ?>"><div><p class="eyebrow"><?php echo gep_docencia_h($eyebrow); ?></p><h2><?php echo gep_docencia_h($title); ?></h2></div><p><?php echo gep_docencia_h($text); ?></p></div>
    <?php gep_docencia_render_courses($courses,$group);
}
$pageTitle='GEP · Docencia';$pageDescription='Docencia y formación vinculada al Laboratorio GEP de la Universidad de Chile.';$bodyClass='page-docencia';$activePage='docencia';$isHome=false;require __DIR__.'/includes/header.php';
?>
<main>
<?php $gep_docencia_hero_style=!empty($gep_docencia['hero_image'])?' style="background-image:url(' . gep_docencia_h(gep_docencia_url($gep_docencia['hero_image'])) . ');"':''; ?>
<section data-gep-section="hero" class="page-hero hero-docencia<?php echo !empty($gep_docencia['hero_video'])?' has-video-bg':''; ?>"<?php echo $gep_docencia_hero_style; ?>><?php gep_theme_render_hero_video($gep_docencia['hero_video']??'',$gep_docencia['hero_image']??'','Video de cabecera de Docencia'); ?><div class="container page-hero-content"><p class="eyebrow"><?php echo gep_docencia_h($gep_docencia['hero_eyebrow']); ?></p><h1><?php echo gep_docencia_h($gep_docencia['hero_title']); ?></h1><p><?php echo gep_docencia_h($gep_docencia['hero_text']); ?></p></div></section>
<nav data-gep-section="hero" aria-label="Navegación de sección" class="section-nav"><div class="container section-nav-inner"><a href="#pregrado"><?php echo gep_docencia_h($gep_docencia['nav_pregrado']); ?></a><a href="#postgrado"><?php echo gep_docencia_h($gep_docencia['nav_postgrado']); ?></a><a href="#diplomado"><?php echo gep_docencia_h($gep_docencia['nav_diplomado']); ?></a><a href="#cursos-talleres"><?php echo gep_docencia_h($gep_docencia['nav_cursos']); ?></a><a href="#campos"><?php echo gep_docencia_h($gep_docencia['nav_campos']); ?></a></div></nav>
<section data-gep-section="courses" class="section"><div class="container">
<?php if(!empty($gep_docencia['learning_steps'])): ?><div class="learning-path"><?php foreach($gep_docencia['learning_steps'] as $st):if(!is_array($st))continue;?><article class="learning-step"><span class="learning-no"><?php echo gep_docencia_h($st['number']??''); ?></span><h3><?php echo gep_docencia_h($st['title']??''); ?></h3><p><?php echo gep_docencia_h($st['text']??''); ?></p></article><?php endforeach; ?></div><?php endif; ?>
<?php gep_docencia_render_group('pregrado',$gep_docencia['pre_eyebrow'],$gep_docencia['pre_title'],$gep_docencia['pre_text'],$gep_docencia['pre_courses'],'pre'); ?>
<?php gep_docencia_render_group('postgrado',$gep_docencia['post_eyebrow'],$gep_docencia['post_title'],$gep_docencia['post_text'],$gep_docencia['post_courses'],'post',true); ?>
<?php gep_docencia_render_group('diplomado',$gep_docencia['diploma_eyebrow'],$gep_docencia['diploma_title'],$gep_docencia['diploma_text'],$gep_docencia['diploma_courses'],'diploma',true); ?>
<?php gep_docencia_render_group('cursos-talleres',$gep_docencia['workshop_eyebrow'],$gep_docencia['workshop_title'],$gep_docencia['workshop_text'],$gep_docencia['workshop_courses'],'workshop',true); ?>
<?php $gep_docencia['note']=gep_site_public_note($gep_docencia['note']); if(!empty($gep_docencia['note'])): ?><div class="source-note"><span class="source-note-icon">i</span><p><?php echo gep_docencia_h($gep_docencia['note']); ?></p></div><?php endif; ?>
</div></section>
<section data-gep-section="fields" class="section research" id="campos"><div class="container split"><div><p class="eyebrow"><?php echo gep_docencia_h($gep_docencia['fields_eyebrow']); ?></p><h2 class="display-title" style="margin:12px 0 20px"><?php echo gep_docencia_h($gep_docencia['fields_title']); ?></h2><p class="lead"><?php echo gep_docencia_h($gep_docencia['fields_text']); ?></p></div><?php if($gep_fields_image): ?><div class="split-media"><img alt="<?php echo gep_docencia_h(gep_site_image_alt($gep_fields_image,$gep_docencia['fields_image_alt'])); ?>" loading="lazy" src="<?php echo gep_docencia_h($gep_fields_image); ?>"></div><?php endif; ?></div></section>
</main>
<?php require __DIR__.'/includes/footer.php'; ?>
