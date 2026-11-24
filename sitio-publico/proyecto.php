<?php
/**
 * GEP · Ficha individual de proyecto.
 * v10.1.0: ficha ampliada, galería interactiva y contenido visual de respaldo.
 */
require_once __DIR__ . '/includes/wp-bootstrap.php';
gep_site_bootstrap_wordpress();
function gep_project_detail_h($v){return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function gep_project_detail_url($v){$v=trim((string)$v);if($v==='')return '';return function_exists('esc_url_raw')?esc_url_raw($v):filter_var($v,FILTER_SANITIZE_URL);}
function gep_project_detail_slug($value){$value=trim((string)$value);if($value==='')return '';if(function_exists('sanitize_title'))return sanitize_title($value);$ascii=function_exists('iconv')?@iconv('UTF-8','ASCII//TRANSLIT//IGNORE',$value):$value;if($ascii!==false)$value=$ascii;$value=strtolower($value);$value=preg_replace('/[^a-z0-9]+/','-',$value);return trim((string)$value,'-');}
function gep_project_detail_gallery($value){
    if(is_array($value))$raw=$value;else $raw=preg_split('/[\r\n]+/',(string)$value);
    $out=array();
    foreach($raw as $item){$url=trim((string)$item);if($url===''||in_array($url,$out,true))continue;$clean=gep_project_detail_url($url);if($clean!=='')$out[]=$clean;}
    return $out;
}
function gep_project_detail_fallback_media($slug){ return array('main'=>'','gallery'=>array()); }

$request_key=isset($_GET['proyecto'])?trim((string)$_GET['proyecto']):'';
$project=null;$project_index=-1;$post_id=0;$projects=array();
if(function_exists('gep_demo_get_page_navigation')){
    $nav=gep_demo_get_page_navigation();
    if(!empty($nav['proyectos']['page']->ID))$post_id=(int)$nav['proyectos']['page']->ID;
}
if(!$post_id&&function_exists('get_page_by_path')){
    $p=get_page_by_path('proyectos',OBJECT,'page');if($p&&!empty($p->ID))$post_id=(int)$p->ID;
}
if($post_id&&function_exists('gep_demo_projects_values')){
    $values=gep_demo_projects_values($post_id);
    if(!empty($values['projects'])&&is_array($values['projects']))$projects=$values['projects'];
}

// Compatibilidad con URLs antiguas ?proyecto=3 y nuevas URLs estables por slug.
$used_slugs=array();
foreach($projects as $idx=>$row){
    if(!is_array($row))continue;
    $row+=array('slug'=>'','title'=>'','year'=>'');
    $slug=gep_project_detail_slug($row['slug']);
    if($slug==='')$slug=gep_project_detail_slug(trim((string)$row['title']).'-'.trim((string)$row['year']));
    if($slug==='')$slug='proyecto-'.($idx+1);
    $base=$slug;$n=2;
    while(isset($used_slugs[$slug])){$slug=$base.'-'.$n;$n++;}
    $used_slugs[$slug]=true;
    if($request_key!==''&&($request_key===$slug||(ctype_digit($request_key)&&(int)$request_key===(int)$idx))){$project=$row;$project_index=(int)$idx;$project['_slug']=$slug;break;}
}

if(!$project){
    // v9.21.4: dirección que no corresponde a ningún proyecto -> 404 (Google no la indexa como página real).
    http_response_code(404);
    $project=array(
        'slug'=>'','status'=>'vigente','year'=>'','theme'=>'Proyecto','title'=>'Proyecto no encontrado','text'=>'Este proyecto no existe o cambió de dirección. Puedes verlos todos en la página Proyectos.',
        'details'=>'','institution'=>'','lead'=>'','image'=>'','image_alt'=>'','gallery'=>'','url'=>'proyectos.php','button'=>'Ver todos los proyectos','tag'=>''
    );
}
$project+=array('slug'=>'','status'=>'vigente','year'=>'','theme'=>'','title'=>'Proyecto GEP','text'=>'','details'=>'','institution'=>'','lead'=>'','image'=>'','image_alt'=>'','gallery'=>'','url'=>'','button'=>'','tag'=>'');
$project_slug=isset($project['_slug'])?gep_project_detail_slug($project['_slug']):gep_project_detail_slug($project['slug']);
$fallback_media=gep_project_detail_fallback_media($project_slug);
$title=trim((string)$project['title'])?:'Proyecto GEP';
$summary=trim((string)$project['text']);
$details=trim((string)$project['details']);
$body=$details!==''?$details:$summary;
$gallery=gep_project_detail_gallery($project['gallery']);
$using_fallback_gallery=false;
$main_image=!empty($project['image'])?gep_project_detail_url($project['image']):'';
$using_fallback_image=empty($project['image'])&&$main_image!=='';
$status=strtolower(trim((string)$project['status']))==='cerrado'?'Cerrado':'Vigente';
$pageTitle='GEP · '.$title;
$pageDescription=$summary!==''?$summary:($body!==''?$body:'Ficha de proyecto del Laboratorio GEP.');
$bodyClass='page-proyecto-detalle';$activePage='proyectos';$isHome=false;
require __DIR__.'/includes/header.php';
?>
<main>
<section class="project-detail-hero">
  <div class="container project-detail-hero-inner">
    <a class="project-detail-back" href="proyectos.php#<?php echo strtolower($status)==='vigente'?'vigentes':'trayectoria'; ?>">← Volver a proyectos</a>
    <div class="project-detail-meta">
      <span class="project-detail-status <?php echo strtolower($status)==='vigente'?'is-current':'is-closed'; ?>"><?php echo gep_project_detail_h($status); ?></span>
      <?php if($project['year']): ?><span><?php echo gep_project_detail_h($project['year']); ?></span><?php endif; ?>
      <?php if($project['theme']): ?><span><?php echo gep_project_detail_h($project['theme']); ?></span><?php endif; ?>
    </div>
    <h1><?php echo gep_project_detail_h($title); ?></h1>
    <?php if($summary): ?><p class="project-detail-hero-summary"><?php echo gep_project_detail_h($summary); ?></p><?php endif; ?>
  </div>
</section>

<section class="section project-detail-overview">
  <div class="container project-detail-grid">
    <div class="project-detail-media">
      <?php if($main_image): ?>
        <img src="<?php echo gep_project_detail_h($main_image); ?>" alt="<?php echo gep_project_detail_h(gep_site_image_alt($main_image,$project['image_alt'],'Imagen territorial asociada a '.$title)); ?>" decoding="async">
        <?php if($using_fallback_image): ?><span class="project-detail-media-note">Imagen referencial</span><?php endif; ?>
      <?php else: ?>
        <div class="project-detail-placeholder"><span>GEP</span><strong>Imagen del proyecto</strong><small>Selecciona una imagen principal desde el editor.</small></div>
      <?php endif; ?>
    </div>

    <aside class="project-detail-summary-card" aria-label="Resumen del proyecto">
      <p class="eyebrow">Ficha de proyecto</p>
      <h2>Información general</h2>
      <div class="project-detail-facts">
        <div><span>Estado</span><strong><?php echo gep_project_detail_h($status); ?></strong></div>
        <?php if($project['year']): ?><div><span>Período</span><strong><?php echo gep_project_detail_h($project['year']); ?></strong></div><?php endif; ?>
        <?php if($project['institution']): ?><div><span>Institución / financiamiento</span><strong><?php echo gep_project_detail_h($project['institution']); ?></strong></div><?php endif; ?>
        <?php if($project['lead']): ?><div><span>Responsable</span><strong><?php echo gep_project_detail_h($project['lead']); ?></strong></div><?php endif; ?>
      </div>
      <?php if(!empty($project['url'])): ?>
        <a class="btn project-detail-primary-link" href="<?php echo gep_project_detail_h(gep_project_detail_url($project['url'])); ?>" target="_blank" rel="noopener"><?php echo gep_project_detail_h(trim((string)$project['button'])?:'Abrir enlace relacionado ↗'); ?></a>
      <?php endif; ?>
    </aside>
  </div>
</section>

<section class="section project-detail-content-section">
  <div class="container project-detail-content-grid">
    <article class="project-detail-copy">
      <p class="eyebrow">Sobre el proyecto</p>
      <h2>Descripción</h2>
      <?php if($body): ?><div class="project-detail-text"><?php echo nl2br(gep_project_detail_h($body)); ?></div><?php else: ?><p class="project-detail-text">Añade la descripción completa desde el editor de Proyectos.</p><?php endif; ?>
    </article>

    <div class="project-detail-side-sections">
      <?php if($project['institution']): ?>
      <section class="project-detail-info-card"><span class="project-detail-info-kicker">Instituciones</span><h3>Institución / financiamiento</h3><p><?php echo gep_project_detail_h($project['institution']); ?></p></section>
      <?php endif; ?>
      <?php if($project['lead']): ?>
      <section class="project-detail-info-card"><span class="project-detail-info-kicker">Equipo</span><h3>Responsable</h3><p><?php echo gep_project_detail_h($project['lead']); ?></p></section>
      <?php endif; ?>
      <?php if(!empty($project['url'])): ?>
      <section class="project-detail-info-card"><span class="project-detail-info-kicker">Resultados / enlaces</span><h3>Material relacionado</h3><p>Consulta documentos, mapas u otros resultados asociados al proyecto.</p><a class="record-link" href="<?php echo gep_project_detail_h(gep_project_detail_url($project['url'])); ?>" target="_blank" rel="noopener"><?php echo gep_project_detail_h(trim((string)$project['button'])?:'Abrir recurso ↗'); ?></a></section>
      <?php endif; ?>
    </div>
  </div>
</section>

<?php if($gallery): ?>
<section class="section project-detail-gallery-section">
  <div class="container">
    <div class="section-head project-detail-gallery-head"><div><p class="eyebrow">Galería</p><h2>Imágenes del proyecto</h2></div><p><?php echo count($gallery); ?> <?php echo count($gallery)===1?'imagen':'imágenes'; ?> asociadas a esta ficha.<?php echo $using_fallback_gallery?' Material visual referencial.':''; ?></p></div>
    <div class="project-detail-gallery">
      <?php foreach($gallery as $i=>$image_url): ?>
        <a class="project-detail-gallery-item" href="<?php echo gep_project_detail_h($image_url); ?>" data-project-lightbox data-lightbox-index="<?php echo (int)$i; ?>" aria-label="Ampliar imagen <?php echo (int)$i+1; ?> de <?php echo gep_project_detail_h($title); ?>">
          <img src="<?php echo gep_project_detail_h($image_url); ?>" alt="<?php echo gep_project_detail_h(gep_site_image_alt($image_url,'',($project['image_alt']?:$title).' · imagen '.((int)$i+1))); ?>" loading="lazy" decoding="async">
        </a>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>
</main>
<div class="project-lightbox" id="projectLightbox" hidden role="dialog" aria-modal="true" aria-label="Galería ampliada">
  <button type="button" class="project-lightbox-close" aria-label="Cerrar galería">×</button>
  <button type="button" class="project-lightbox-nav is-prev" aria-label="Imagen anterior">←</button>
  <figure><img alt=""><figcaption></figcaption></figure>
  <button type="button" class="project-lightbox-nav is-next" aria-label="Imagen siguiente">→</button>
</div>
<?php require __DIR__.'/includes/footer.php'; ?>
