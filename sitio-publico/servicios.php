<?php
/**
 * GEP · Servicios
 * Lee la pestaña "Servicios" de GEP Editor (metadatos gep_services_*) cuando WordPress
 * está disponible. Si no, muestra el contenido por defecto de este archivo.
 */
require_once __DIR__ . '/includes/wp-bootstrap.php';
gep_site_bootstrap_wordpress();
function gep_services_h($v){return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function gep_services_url($v){
    $v=trim((string)$v);if($v==='')return '';
    if(preg_match('#^[a-z0-9_\-]+\.php([?\#].*)?$#i',$v)||strpos($v,'#')===0)return $v;
    if(!preg_match('#^(https?:|mailto:|/)#i',$v))return '';
    return function_exists('esc_url_raw')?esc_url_raw($v):filter_var($v,FILTER_SANITIZE_URL);
}
// v9.23.0: las direcciones web escritas dentro de un texto quedan como enlaces.
function gep_services_autolink($text){
    $html=nl2br(gep_services_h($text));
    return preg_replace_callback('#\bhttps?://[^\s<]+#i',function($m){
        $url=rtrim($m[0],'.,;:!?)');$rest=substr($m[0],strlen($url));
        $href=html_entity_decode($url,ENT_QUOTES,'UTF-8');
        if(function_exists('esc_url_raw'))$href=esc_url_raw($href);
        if($href==='')return $m[0];
        return '<a href="'.gep_services_h($href).'" target="_blank" rel="noopener">'.$url.'</a>'.$rest;
    },$html);
}
function gep_services_lines($v){$out=array();foreach(preg_split('/[\r\n]+/',(string)$v) as $l){$l=trim($l);if($l!=='')$out[]=$l;}return $out;}
function gep_services_slug($v){$v=strtolower(trim((string)$v));if(function_exists('sanitize_title'))return sanitize_title($v);$v=strtr($v,array('á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n'));return trim(preg_replace('/[^a-z0-9]+/','-',$v),'-');}

$gep_services_defaults=array(
    'hero_eyebrow'=>'Trabajo aplicado','hero_title'=>'Servicios',
    'hero_text'=>'Levantamiento y análisis de información territorial para el manejo de bosques, vegetación y paisaje.',
    'hero_image'=>'','hero_video'=>'',
    'intro_eyebrow'=>'Qué hacemos','intro_title'=>'Datos de terreno y desde el aire, listos para decidir.',
    'intro_text'=>'Combinamos trabajo de campo, sensores remotos y análisis espacial. Cada servicio se adapta al objetivo, la escala y los plazos de cada proyecto.',
    'items'=>array(
        array('slug'=>'inventarios-forestales','visible'=>'1','tag'=>'Terreno + SIG','nav_label'=>'Inventarios forestales','title'=>'Inventarios forestales',
            'summary'=>'Medición y estimación de variables forestales en terreno, con diseño de muestreo y análisis espacial.',
            'description'=>'Diseñamos y ejecutamos inventarios forestales para conocer la composición, estructura y existencias de un bosque o plantación. Los datos de terreno se integran con cartografía y sensores remotos para obtener estimaciones confiables a escala de rodal o predio.',
            'image'=>'','image_alt'=>'',
            'deliverables'=>"Diseño de muestreo y parcelas\nBase de datos dendrométrica\nEstimación de volumen, biomasa y carbono\nCartografía de rodales y coberturas\nInforme técnico",
            'applications'=>"Planes de manejo\nLíneas base ambientales\nMonitoreo de restauración\nEstudios de biomasa y carbono",
            'equipment'=>'','button'=>'Solicitar este servicio','url'=>'contactanos.php'),
        array('slug'=>'dron-multiespectral','visible'=>'1','tag'=>'Sensor aéreo','nav_label'=>'Dron multiespectral','title'=>'Levantamientos con dron multiespectral',
            'summary'=>'Ortomosaicos e índices espectrales de alta resolución para evaluar el estado y los cambios de la vegetación.',
            'description'=>'Planificamos y ejecutamos vuelos con cámaras multiespectrales para obtener imágenes de muy alta resolución. Con ellas calculamos índices de vegetación y mapas que permiten detectar diferencias de vigor, estrés o daño en la cobertura vegetal.',
            'image'=>'','image_alt'=>'',
            'deliverables'=>"Ortomosaico RGB y multiespectral\nÍndices de vegetación (NDVI, NDRE y otros)\nMapas de vigor y estrés\nModelo digital de superficie\nInforme con interpretación",
            'applications'=>"Seguimiento de plantaciones y restauración\nDetección de estrés y daño\nClasificación de coberturas\nValidación de imágenes satelitales",
            'equipment'=>'','button'=>'Solicitar este servicio','url'=>'contactanos.php'),
        array('slug'=>'lidar-uav','visible'=>'1','tag'=>'Sensor aéreo 3D','nav_label'=>'LiDAR UAV','title'=>'Levantamientos con LiDAR UAV',
            'summary'=>'Nubes de puntos 3D para describir la estructura de la vegetación y el terreno bajo el dosel.',
            'description'=>'El LiDAR montado en dron registra millones de puntos que atraviesan el dosel. Con esa nube de puntos modelamos el terreno, la altura de la vegetación y su estructura vertical con un detalle que no es posible obtener solo con imágenes.',
            'image'=>'','image_alt'=>'',
            'deliverables'=>"Nube de puntos clasificada\nModelo digital de terreno (MDT) y de superficie (MDS)\nModelo de altura de dosel\nMétricas de estructura: altura, cobertura y densidad\nDetección de árboles individuales",
            'applications'=>"Inventario forestal de precisión\nBiomasa y carbono\nTopografía e hidrología\nEvaluación de hábitat",
            'equipment'=>'','button'=>'Solicitar este servicio','url'=>'contactanos.php'),
    ),
    'steps_eyebrow'=>'Cómo trabajamos','steps_title'=>'De la consulta a la entrega.',
    'steps'=>array(
        array('title'=>'Consulta','text'=>'Conversamos sobre el objetivo, el área de estudio y los plazos.'),
        array('title'=>'Planificación','text'=>'Definimos el método, el muestreo, los vuelos y los permisos necesarios.'),
        array('title'=>'Terreno','text'=>'Levantamos los datos en campo y controlamos su calidad.'),
        array('title'=>'Entrega','text'=>'Entregamos datos procesados, cartografía e informe.'),
    ),
    'cta_eyebrow'=>'Trabajemos juntos','cta_title'=>'¿Necesitas alguno de estos servicios?','cta_button'=>'Contactar al GEP','cta_url'=>'contactanos.php',
);

$gep_services=$gep_services_defaults;$gep_services_post_id=0;
if(function_exists('gep_demo_get_page_navigation')){$n=gep_demo_get_page_navigation();if(!empty($n['servicios']['page']->ID))$gep_services_post_id=(int)$n['servicios']['page']->ID;}
if(!$gep_services_post_id&&function_exists('get_page_by_path')){$p=get_page_by_path('servicios',OBJECT,'page');if($p&&!empty($p->ID))$gep_services_post_id=(int)$p->ID;}
if($gep_services_post_id&&function_exists('gep_demo_services_values')){$saved=gep_demo_services_values($gep_services_post_id);if(is_array($saved))$gep_services=array_merge($gep_services_defaults,$saved);}
foreach(array('items','steps') as $k)if(!isset($gep_services[$k])||!is_array($gep_services[$k]))$gep_services[$k]=array();

// Solo servicios visibles, con ancla estable y única.
$gep_service_items=array();$gep_used_anchors=array();
foreach($gep_services['items'] as $i=>$row){
    if(!is_array($row))continue;
    $row+=array('slug'=>'','visible'=>'1','tag'=>'','nav_label'=>'','title'=>'','summary'=>'','description'=>'','image'=>'','image_alt'=>'','deliverables'=>'','applications'=>'','equipment'=>'','button'=>'','url'=>'');
    if((string)$row['visible']==='0'||trim((string)$row['title'])==='')continue;
    $anchor=gep_services_slug($row['slug']!==''?$row['slug']:$row['title']);if($anchor==='')$anchor='servicio-'.($i+1);
    $base=$anchor;$c=2;while(isset($gep_used_anchors[$anchor])){$anchor=$base.'-'.$c;$c++;}
    $gep_used_anchors[$anchor]=true;$row['_anchor']=$anchor;
    $gep_service_items[]=$row;
}
$gep_service_steps=array();
foreach($gep_services['steps'] as $st){if(is_array($st)&&(trim((string)($st['title']??''))!==''||trim((string)($st['text']??''))!==''))$gep_service_steps[]=$st;}

$gep_services_hero_image=!empty($gep_services['hero_image'])?gep_services_url($gep_services['hero_image']):'';
$gep_services_hero_style=$gep_services_hero_image?' style="background-image:linear-gradient(90deg,rgba(5,20,11,.88),rgba(5,20,11,.50) 55%,rgba(5,20,11,.20)),url(' . gep_services_h($gep_services_hero_image) . ');"':'';

$pageTitle='GEP · Servicios';$pageDescription='Inventarios forestales y levantamientos con dron multiespectral y LiDAR UAV del Laboratorio GEP.';$bodyClass='page-servicios';$activePage='servicios';$isHome=false;
$pageStyles=array('assets/css/services.css');
require __DIR__.'/includes/header.php';
?>
<main>
<section data-gep-section="hero" class="page-hero hub-page-hero services-hero<?php echo $gep_services_hero_image?' has-cover':' no-cover'; ?><?php echo !empty($gep_services['hero_video'])?' has-video-bg':''; ?>"<?php echo $gep_services_hero_style; ?>><?php gep_theme_render_hero_video($gep_services['hero_video']??'',$gep_services_hero_image,'Video de cabecera de Servicios'); ?>
  <div class="container page-hero-content">
    <p class="eyebrow"><?php echo gep_services_h($gep_services['hero_eyebrow']); ?></p>
    <h1><?php echo gep_services_h($gep_services['hero_title']); ?></h1>
    <p><?php echo gep_services_h($gep_services['hero_text']); ?></p>
  </div>
</section>

<?php if(count($gep_service_items)>1): ?>
<nav data-gep-section="hero" aria-label="Servicios" class="section-nav"><div class="container section-nav-inner">
  <?php foreach($gep_service_items as $s): ?><a href="#<?php echo gep_services_h($s['_anchor']); ?>"><?php echo gep_services_h($s['nav_label']!==''?$s['nav_label']:$s['title']); ?></a><?php endforeach; ?>
</div></nav>
<?php endif; ?>

<section data-gep-section="services" class="section services-section" id="servicios">
  <div class="container">
    <div class="section-head">
      <div><p class="eyebrow"><?php echo gep_services_h($gep_services['intro_eyebrow']); ?></p><h2><?php echo gep_services_h($gep_services['intro_title']); ?></h2></div>
      <p><?php echo gep_services_h($gep_services['intro_text']); ?></p>
    </div>
    <?php if($gep_service_items): ?>
    <div class="service-list">
      <?php foreach($gep_service_items as $n=>$s):
        $img=$s['image']!==''?gep_services_url($s['image']):'';
        $deliverables=gep_services_lines($s['deliverables']);
        $applications=gep_services_lines($s['applications']);
        $btn_url=gep_services_url($s['url']);
        $external=preg_match('#^https?://#i',$btn_url);
      ?>
      <article class="service-block<?php echo $n%2?' is-reverse':''; ?><?php echo $img?' has-image':' has-no-image'; ?>" id="<?php echo gep_services_h($s['_anchor']); ?>">
        <div class="service-media">
          <?php if($img): ?>
            <img src="<?php echo gep_services_h($img); ?>" alt="<?php echo gep_services_h(gep_site_image_alt($img,$s['image_alt'],$s['title'])); ?>" loading="lazy" decoding="async">
          <?php else: ?>
            <div class="service-media-placeholder" aria-hidden="true"><span class="service-media-no"><?php echo gep_services_h(sprintf('%02d',$n+1)); ?></span><span class="service-media-tag"><?php echo gep_services_h($s['tag']); ?></span></div>
          <?php endif; ?>
        </div>
        <div class="service-body">
          <div class="service-topline"><span class="service-no"><?php echo gep_services_h(sprintf('%02d',$n+1)); ?></span><?php if($s['tag']!==''): ?><span class="service-tag"><?php echo gep_services_h($s['tag']); ?></span><?php endif; ?></div>
          <h3><?php echo gep_services_h($s['title']); ?></h3>
          <?php if($s['summary']!==''): ?><p class="service-summary"><?php echo gep_services_h($s['summary']); ?></p><?php endif; ?>
          <?php if($s['description']!==''): ?><p class="service-description"><?php echo gep_services_autolink($s['description']); ?></p><?php endif; ?>
          <?php if($deliverables||$applications): ?>
          <div class="service-columns">
            <?php if($deliverables): ?><div><h4>Qué entregamos</h4><ul class="service-checklist"><?php foreach($deliverables as $l): ?><li><?php echo gep_services_h($l); ?></li><?php endforeach; ?></ul></div><?php endif; ?>
            <?php if($applications): ?><div><h4>Aplicaciones</h4><div class="chip-list"><?php foreach($applications as $l): ?><span class="chip"><?php echo gep_services_h($l); ?></span><?php endforeach; ?></div></div><?php endif; ?>
          </div>
          <?php endif; ?>
          <?php if($s['equipment']!==''): ?><p class="service-equipment"><span>Equipo</span><?php echo gep_services_h($s['equipment']); ?></p><?php endif; ?>
          <?php if($btn_url!==''): ?><a class="btn btn-green service-button" href="<?php echo gep_services_h($btn_url); ?>"<?php echo $external?' target="_blank" rel="noopener"':''; ?>><?php echo gep_services_h($s['button']!==''?$s['button']:'Más información'); ?></a><?php endif; ?>
        </div>
      </article>
      <?php endforeach; ?>
    </div>
    <?php else: ?>
      <div class="empty-state"><strong>Pronto publicaremos nuestros servicios.</strong></div>
    <?php endif; ?>
  </div>
</section>

<?php if($gep_service_steps): ?>
<section data-gep-section="steps" class="section research services-steps-section" id="como-trabajamos">
  <div class="container">
    <div class="section-head"><div><p class="eyebrow"><?php echo gep_services_h($gep_services['steps_eyebrow']); ?></p><h2><?php echo gep_services_h($gep_services['steps_title']); ?></h2></div></div>
    <div class="learning-path service-steps service-steps-<?php echo count($gep_service_steps); ?>">
      <?php foreach($gep_service_steps as $i=>$st): ?>
      <article class="learning-step"><span class="learning-no"><?php echo gep_services_h(sprintf('%02d',$i+1)); ?></span><h3><?php echo gep_services_h($st['title']??''); ?></h3><p><?php echo gep_services_h($st['text']??''); ?></p></article>
      <?php endforeach; ?>
    </div>
  </div>
</section>
<?php endif; ?>

<?php if(trim((string)$gep_services['cta_title'])!==''): $gep_cta_url=gep_services_url($gep_services['cta_url']); ?>
<section data-gep-section="cta" class="cta-band cta-final"><div class="container cta-inner"><div><p class="eyebrow light"><?php echo gep_services_h($gep_services['cta_eyebrow']); ?></p><h2><?php echo gep_services_h($gep_services['cta_title']); ?></h2></div><?php if($gep_services['cta_button']!==''&&$gep_cta_url!==''&&empty($gep_services['cta_off'])): ?><a class="btn btn-primary" href="<?php echo gep_services_h($gep_cta_url); ?>"><?php echo gep_services_h($gep_services['cta_button']); ?></a><?php endif; ?></div></section>
<?php endif; ?>
</main>
<?php require __DIR__.'/includes/footer.php'; ?>
