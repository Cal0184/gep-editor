<?php
if(!isset($gep_hub_context))$gep_hub_context='communications';
$gep_hub_is_comms=$gep_hub_context==='communications';
require_once __DIR__ . '/wp-bootstrap.php';
gep_site_bootstrap_wordpress();
if(!function_exists('gep_hub_h')){function gep_hub_h($v){return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}}
if(!function_exists('gep_hub_url')){function gep_hub_url($v){$v=trim((string)$v);if($v==='')return '';return function_exists('esc_url_raw')?esc_url_raw($v):filter_var($v,FILTER_SANITIZE_URL);}}
if(!function_exists('gep_hub_year')){function gep_hub_year($v){return preg_match('/\b(19|20)\d{2}\b/',(string)$v,$m)?$m[0]:'';}}
if(!function_exists('gep_hub_date_timestamp')){
    function gep_hub_date_timestamp($value){
        $raw=trim((string)$value);
        if($raw==='')return false;
        if(preg_match('/^(\d{4})[-\/.](\d{1,2})[-\/.](\d{1,2})/', $raw, $m))return @mktime(12,0,0,(int)$m[2],(int)$m[3],(int)$m[1]);
        if(preg_match('/^(\d{1,2})[-\/.](\d{1,2})[-\/.](\d{4})/', $raw, $m))return @mktime(12,0,0,(int)$m[2],(int)$m[1],(int)$m[3]);
        $s=function_exists('mb_strtolower')?mb_strtolower($raw,'UTF-8'):strtolower($raw);
        $s=strtr($s,array('á'=>'a','é'=>'e','í'=>'i','ó'=>'o','ú'=>'u','ü'=>'u','ñ'=>'n',','=>' ',' de '=>' '));
        $months=array(
            'enero'=>1,'ene'=>1,'febrero'=>2,'feb'=>2,'marzo'=>3,'mar'=>3,'abril'=>4,'abr'=>4,
            'mayo'=>5,'may'=>5,'junio'=>6,'jun'=>6,'julio'=>7,'jul'=>7,'agosto'=>8,'ago'=>8,
            'septiembre'=>9,'setiembre'=>9,'sept'=>9,'sep'=>9,'octubre'=>10,'oct'=>10,'noviembre'=>11,'nov'=>11,'diciembre'=>12,'dic'=>12
        );
        if(preg_match('/\b(\d{1,2})\s+([a-z]+)\s+(\d{4})\b/',$s,$m)&&isset($months[$m[2]]))return @mktime(12,0,0,$months[$m[2]],(int)$m[1],(int)$m[3]);
        $fallback=@strtotime($raw);
        return $fallback!==false?$fallback:false;
    }
}
if(!function_exists('gep_hub_event_date_parts')){
    function gep_hub_event_date_parts($value){
        $ts=gep_hub_date_timestamp($value);
        if($ts===false)return array('timestamp'=>false,'day'=>'—','month'=>'Fecha','year'=>gep_hub_year($value),'status'=>'','status_class'=>'');
        $months=array(1=>'ENE',2=>'FEB',3=>'MAR',4=>'ABR',5=>'MAY',6=>'JUN',7=>'JUL',8=>'AGO',9=>'SEP',10=>'OCT',11=>'NOV',12=>'DIC');
        $today=mktime(0,0,0,(int)date('n'),(int)date('j'),(int)date('Y'));
        $eventDay=mktime(0,0,0,(int)date('n',$ts),(int)date('j',$ts),(int)date('Y',$ts));
        $status=$eventDay===$today?'Hoy':($eventDay>$today?'Próximo':'Finalizado');
        $statusClass=$eventDay===$today?'is-today':($eventDay>$today?'is-upcoming':'is-past');
        return array('timestamp'=>$ts,'day'=>date('d',$ts),'month'=>$months[(int)date('n',$ts)],'year'=>date('Y',$ts),'status'=>$status,'status_class'=>$statusClass);
    }
}
// v9.23.0: el editor guarda la fecha como 2026-10-27 (calendario); aquí se muestra como "27 octubre 2026".
if(!function_exists('gep_hub_date_label')){
    function gep_hub_date_label($value){
        $raw=trim((string)$value);
        if(!preg_match('/^(\d{4})-(\d{2})-(\d{2})$/',$raw,$m))return $raw;
        $months=array(1=>'enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre');
        $mo=(int)$m[2];
        return isset($months[$mo])?((int)$m[3]).' '.$months[$mo].' '.$m[1]:$raw;
    }
}
if(!function_exists('gep_hub_sort_records')){
    // Más recientes primero. Lo que no tiene fecha reconocible queda al final, en su orden.
    function gep_hub_sort_records($records){
        $rows=array();
        foreach(array_values($records) as $i=>$r){$ts=is_array($r)?gep_hub_date_timestamp($r['date']??''):false;$rows[]=array($ts===false?null:$ts,$i,$r);}
        usort($rows,function($a,$b){
            if($a[0]===null&&$b[0]===null)return $a[1]<=>$b[1];
            if($a[0]===null)return 1;
            if($b[0]===null)return -1;
            return $b[0]<=>$a[0]?:$a[1]<=>$b[1];
        });
        return array_map(function($x){return $x[2];},$rows);
    }
}
if(!function_exists('gep_hub_fallback_image')){function gep_hub_fallback_image($record,$index=0){return '';}}

$gep_hub_types=$gep_hub_is_comms
    ? array('prensa'=>'En prensa','eventos'=>'Eventos','videos'=>'Multimedia')
    : array('entrevistas'=>'Entrevistas','noticias'=>'Noticias');
$gep_hub_defaults=array(
    'hero_eyebrow'=>$gep_hub_is_comms?'Difusión':'Actualidad',
    'hero_title'=>$gep_hub_is_comms?'Comunicaciones':'Noticias',
    'hero_text'=>$gep_hub_is_comms?'Prensa, eventos y contenidos audiovisuales vinculados al trabajo del laboratorio.':'Entrevistas y noticias relacionadas con el GEP y su trabajo.',
    'hero_image'=>'',
    'hero_video'=>'',
    'records'=>array()
);
$gep_hub=$gep_hub_defaults;
$gep_hub_post_id=0;
$gep_hub_nav_key=$gep_hub_is_comms?'comunicaciones':'noticias';

if(function_exists('gep_demo_get_page_navigation')){
    $gep_hub_nav=gep_demo_get_page_navigation();
    if(!empty($gep_hub_nav[$gep_hub_nav_key]['page']->ID))$gep_hub_post_id=(int)$gep_hub_nav[$gep_hub_nav_key]['page']->ID;
}
if(!$gep_hub_post_id&&function_exists('get_page_by_path')){
    $gep_hub_page=get_page_by_path($gep_hub_nav_key,OBJECT,'page');
    if($gep_hub_page&&!empty($gep_hub_page->ID))$gep_hub_post_id=(int)$gep_hub_page->ID;
}
if($gep_hub_post_id&&function_exists('gep_demo_hub_values')){
    $gep_hub_saved=gep_demo_hub_values($gep_hub_post_id,$gep_hub_context);
    if(is_array($gep_hub_saved))$gep_hub=array_merge($gep_hub_defaults,$gep_hub_saved);
}
if(!isset($gep_hub['records'])||!is_array($gep_hub['records']))$gep_hub['records']=array();
$gep_hub['records']=gep_hub_sort_records($gep_hub['records']);

if($gep_hub_post_id&&function_exists('get_post_meta')&&function_exists('metadata_exists')&&metadata_exists('post',$gep_hub_post_id,($gep_hub_is_comms?'gep_communications_':'gep_news_').'hero_video')){
    $gep_hub['hero_video']=get_post_meta($gep_hub_post_id,($gep_hub_is_comms?'gep_communications_':'gep_news_').'hero_video',true);
}
$gep_hub_years=array();
$gep_hub_counts=array_fill_keys(array_keys($gep_hub_types),0);
$gep_hub_event_feature_index=null;
$gep_hub_event_feature_time=null;
foreach($gep_hub['records'] as $gep_i=>$gep_hub_record){
    if(!is_array($gep_hub_record))continue;
    $gep_type=isset($gep_hub_record['type'])&&isset($gep_hub_types[$gep_hub_record['type']])?$gep_hub_record['type']:array_key_first($gep_hub_types);
    if(isset($gep_hub_counts[$gep_type]))$gep_hub_counts[$gep_type]++;
    $gep_year=gep_hub_year($gep_hub_record['date']??'');
    if($gep_year!=='')$gep_hub_years[$gep_year]=true;
    if($gep_hub_is_comms&&$gep_type==='eventos'){
        $parts=gep_hub_event_date_parts($gep_hub_record['date']??'');
        if($parts['timestamp']!==false&&$parts['status_class']!=='is-past'){
            if($gep_hub_event_feature_time===null||$parts['timestamp']<$gep_hub_event_feature_time){
                $gep_hub_event_feature_time=$parts['timestamp'];
                $gep_hub_event_feature_index=$gep_i;
            }
        }
    }
}
$gep_hub_years=array_keys($gep_hub_years);
rsort($gep_hub_years,SORT_NUMERIC);

$pageTitle='GEP · '.($gep_hub_is_comms?'Comunicaciones':'Noticias');
$pageDescription=$gep_hub_is_comms?'Prensa, eventos y contenidos audiovisuales del Laboratorio GEP.':'Entrevistas y noticias del Laboratorio GEP.';
$bodyClass=$gep_hub_is_comms?'page-comunicaciones':'page-noticias';
$activePage=$gep_hub_nav_key;
$isHome=false;
$gep_hub_hero_style='';
$gep_hub_hero_image=!empty($gep_hub['hero_image'])?gep_hub_url($gep_hub['hero_image']):'';
if($gep_hub_hero_image){
    $gep_hub_hero_style=' style="background-image:linear-gradient(90deg,rgba(5,20,11,.88),rgba(5,20,11,.50) 55%,rgba(5,20,11,.20)),url(' . gep_hub_h($gep_hub_hero_image) . ');"';
}
require __DIR__.'/header.php';
?>
<main>
<section data-gep-section="hero" class="page-hero hub-page-hero<?php echo $gep_hub_hero_image ? ' has-cover' : ' no-cover'; ?><?php echo !empty($gep_hub['hero_video'])?' has-video-bg':''; ?>"<?php echo $gep_hub_hero_style; ?>><?php gep_theme_render_hero_video($gep_hub['hero_video']??'',$gep_hub_hero_image,'Video de cabecera'); ?>
  <div class="container page-hero-content">
    <p class="eyebrow"><?php echo gep_hub_h($gep_hub['hero_eyebrow']); ?></p>
    <h1><?php echo gep_hub_h($gep_hub['hero_title']); ?></h1>
    <p><?php echo gep_hub_h($gep_hub['hero_text']); ?></p>
  </div>
</section>

<section data-gep-section="content" class="section hub-list-section <?php echo $gep_hub_is_comms?'is-comms':'is-news'; ?>">
  <div class="container">
    <div class="section-head hub-section-head">
      <div>
        <p class="eyebrow"><?php echo $gep_hub_is_comms?'Centro de comunicación':'Actualidad GEP'; ?></p>
        <h2><?php echo $gep_hub_is_comms?'El GEP hacia afuera':'Noticias y entrevistas'; ?></h2>
      </div>
      <p><?php echo $gep_hub_is_comms?'Apariciones en medios, actividades y contenidos audiovisuales reunidos en un solo espacio.':'Un archivo visual y simple para seguir la actividad, entrevistas y novedades del laboratorio.'; ?></p>
    </div>

    <?php if($gep_hub_is_comms): ?>
      <div class="comms-overview" aria-label="Resumen de comunicaciones">
        <button type="button" class="comms-overview-card is-press" data-hub-filter-target="prensa">
          <span class="comms-overview-icon" aria-hidden="true">↗</span>
          <span class="comms-overview-copy"><strong>En prensa</strong><small>Apariciones y entrevistas en medios</small></span>
          <span class="comms-overview-count"><?php echo (int)($gep_hub_counts['prensa']??0); ?></span>
        </button>
        <button type="button" class="comms-overview-card is-events" data-hub-filter-target="eventos">
          <span class="comms-overview-icon" aria-hidden="true">□</span>
          <span class="comms-overview-copy"><strong>Eventos</strong><small>Agenda y actividades del laboratorio</small></span>
          <span class="comms-overview-count"><?php echo (int)($gep_hub_counts['eventos']??0); ?></span>
        </button>
        <button type="button" class="comms-overview-card is-video" data-hub-filter-target="videos">
          <span class="comms-overview-icon" aria-hidden="true">▶</span>
          <span class="comms-overview-copy"><strong>Multimedia</strong><small>Videos, cápsulas y registros</small></span>
          <span class="comms-overview-count"><?php echo (int)($gep_hub_counts['videos']??0); ?></span>
        </button>
      </div>

      <div class="comms-explorer" aria-label="Buscar y filtrar comunicaciones">
        <div class="comms-explorer-summary">
          <span>Explorar archivo</span>
          <strong><span id="hubVisibleCount"><?php echo count($gep_hub['records']); ?></span> registros</strong>
          <button class="news-reset" type="button" id="hubReset" hidden>Limpiar filtros</button>
        </div>
        <div class="comms-explorer-controls">
          <label class="news-search comms-search" for="hubSearch">
            <span aria-hidden="true">⌕</span>
            <input id="hubSearch" type="search" autocomplete="off" placeholder="Buscar medio, evento, tema o video…">
          </label>
          <div class="news-filter-pills comms-filter-pills" role="group" aria-label="Filtrar por formato">
            <button type="button" class="hub-filter active" data-hub-filter="all" aria-pressed="true">Todo</button>
            <?php foreach($gep_hub_types as $gep_hub_type=>$gep_hub_label): ?>
              <button type="button" class="hub-filter" data-hub-filter="<?php echo gep_hub_h($gep_hub_type); ?>" aria-pressed="false"><?php echo gep_hub_h($gep_hub_label); ?></button>
            <?php endforeach; ?>
          </div>
          <?php if($gep_hub_years): ?>
            <label class="news-year-select comms-year-select">
              <span class="sr-only">Filtrar por año</span>
              <select id="hubYear" aria-label="Filtrar comunicaciones por año">
                <option value="all">Todos los años</option>
                <?php foreach($gep_hub_years as $gep_hub_year): ?><option value="<?php echo gep_hub_h($gep_hub_year); ?>"><?php echo gep_hub_h($gep_hub_year); ?></option><?php endforeach; ?>
              </select>
            </label>
          <?php endif; ?>
        </div>
      </div>
    <?php elseif($gep_hub['records']): ?>
      <div class="news-explorer" aria-label="Buscar y filtrar noticias">
        <div class="news-explorer-topline">
          <div>
            <span class="news-explorer-kicker">Explorar contenidos</span>
            <strong><span id="hubVisibleCount"><?php echo count($gep_hub['records']); ?></span> resultados</strong>
          </div>
          <button class="news-reset" type="button" id="hubReset" hidden>Limpiar filtros</button>
        </div>
        <div class="news-explorer-controls">
          <label class="news-search" for="hubSearch">
            <span aria-hidden="true">⌕</span>
            <input id="hubSearch" type="search" autocomplete="off" placeholder="Buscar noticia, tema o fuente…">
          </label>
          <div class="news-filter-pills" role="group" aria-label="Filtrar por tipo">
            <button type="button" class="hub-filter active" data-hub-filter="all" aria-pressed="true">Todos</button>
            <?php foreach($gep_hub_types as $gep_hub_type=>$gep_hub_label): ?>
              <button type="button" class="hub-filter" data-hub-filter="<?php echo gep_hub_h($gep_hub_type); ?>" aria-pressed="false"><?php echo gep_hub_h($gep_hub_label); ?></button>
            <?php endforeach; ?>
          </div>
          <?php if($gep_hub_years): ?>
            <label class="news-year-select">
              <span class="sr-only">Filtrar por año</span>
              <select id="hubYear" aria-label="Filtrar noticias por año">
                <option value="all">Todos los años</option>
                <?php foreach($gep_hub_years as $gep_hub_year): ?><option value="<?php echo gep_hub_h($gep_hub_year); ?>"><?php echo gep_hub_h($gep_hub_year); ?></option><?php endforeach; ?>
              </select>
            </label>
          <?php endif; ?>
        </div>
      </div>
    <?php endif; ?>

    <?php if($gep_hub['records']): ?>
      <?php if($gep_hub_is_comms): ?>
        <div class="comms-dashboard" id="contentHubGrid">
          <?php if($gep_hub_event_feature_index!==null):
            $gep_event=$gep_hub['records'][$gep_hub_event_feature_index];
            $gep_event_parts=gep_hub_event_date_parts($gep_event['date']??'');
            $gep_event_url=!empty($gep_event['url'])?gep_hub_url($gep_event['url']):'';
            $gep_event_img=!empty($gep_event['image'])?gep_hub_url($gep_event['image']):gep_hub_fallback_image($gep_event,$gep_hub_event_feature_index??0);
            $gep_event_title=trim((string)($gep_event['title']??''));
            $gep_event_year=gep_hub_year($gep_event['date']??'');
            $gep_event_search=trim(implode(' ',array_filter(array('Eventos',$gep_event['date']??'',$gep_event_title,$gep_event['source']??'',$gep_event['place']??'',$gep_event['description']??''))));
          ?>
          <section class="comms-section comms-feature-section" data-comms-section="eventos">
            <div class="comms-section-title">
              <div><span class="comms-section-kicker">Agenda</span><h3>Próximo evento</h3></div>
              <span class="comms-section-note">Lo próximo en el calendario GEP</span>
            </div>
            <article class="comms-record comms-next-event <?php echo gep_hub_h($gep_event_parts['status_class']); ?>" data-hub-type="eventos" data-hub-year="<?php echo gep_hub_h($gep_event_year); ?>" data-hub-search="<?php echo gep_hub_h($gep_event_search); ?>">
              <div class="comms-date-tile" aria-label="<?php echo gep_hub_h(gep_hub_date_label($gep_event['date']??'')); ?>"><span><?php echo gep_hub_h($gep_event_parts['month']); ?></span><strong><?php echo gep_hub_h($gep_event_parts['day']); ?></strong><small><?php echo gep_hub_h($gep_event_parts['year']); ?></small></div>
              <div class="comms-next-event-copy">
                <div class="comms-meta-line"><span class="comms-status <?php echo gep_hub_h($gep_event_parts['status_class']); ?>"><?php echo gep_hub_h($gep_event_parts['status']); ?></span><?php if(!empty($gep_event['place'])): ?><span><?php echo gep_hub_h($gep_event['place']); ?></span><?php endif; ?></div>
                <h4><?php echo gep_hub_h($gep_event_title); ?></h4>
                <?php if(!empty($gep_event['source'])): ?><p class="comms-source"><?php echo gep_hub_h($gep_event['source']); ?></p><?php endif; ?>
                <?php if(!empty($gep_event['description'])): ?><p><?php echo gep_hub_h($gep_event['description']); ?></p><?php endif; ?>
                <?php if($gep_event_url): ?><a class="comms-action" href="<?php echo gep_hub_h($gep_event_url); ?>" target="_blank" rel="noopener"><?php echo gep_hub_h(!empty($gep_event['button'])?$gep_event['button']:'Ver evento ↗'); ?></a><?php endif; ?>
              </div>
              <?php if($gep_event_img): ?><div class="comms-next-event-media"><img loading="lazy" src="<?php echo gep_hub_h($gep_event_img); ?>" alt="<?php echo gep_hub_h(gep_site_image_alt($gep_event_img,$gep_event['image_alt']??'')); ?>"></div><?php endif; ?>
            </article>
          </section>
          <?php endif; ?>

          <section class="comms-section" data-comms-section="prensa">
            <div class="comms-section-title">
              <div><span class="comms-section-kicker">Presencia pública</span><h3>En prensa</h3></div>
              <span class="comms-section-note"><?php echo (int)($gep_hub_counts['prensa']??0); ?> registros</span>
            </div>
            <div class="comms-press-list">
            <?php foreach($gep_hub['records'] as $gep_i=>$gep_record):
              if(!is_array($gep_record)||($gep_record['type']??'')!=='prensa')continue;
              $gep_url=!empty($gep_record['url'])?gep_hub_url($gep_record['url']):'';
              $gep_img=!empty($gep_record['image'])?gep_hub_url($gep_record['image']):gep_hub_fallback_image($gep_record,$gep_i);
              $gep_title=trim((string)($gep_record['title']??''));
              $gep_year=gep_hub_year($gep_record['date']??'');
              $gep_search=trim(implode(' ',array_filter(array('Prensa',$gep_record['date']??'',$gep_title,$gep_record['source']??'',$gep_record['place']??'',$gep_record['description']??''))));
            ?>
              <article class="comms-record press-row" data-hub-type="prensa" data-hub-year="<?php echo gep_hub_h($gep_year); ?>" data-hub-search="<?php echo gep_hub_h($gep_search); ?>">
                <?php if($gep_img): ?><div class="press-row-media"><img loading="lazy" src="<?php echo gep_hub_h($gep_img); ?>" alt="<?php echo gep_hub_h(gep_site_image_alt($gep_img,$gep_record['image_alt']??'')); ?>"></div><?php else: ?><div class="press-row-mark" aria-hidden="true">GEP</div><?php endif; ?>
                <div class="press-row-copy">
                  <div class="comms-meta-line"><strong><?php echo gep_hub_h(!empty($gep_record['source'])?$gep_record['source']:'Medio externo'); ?></strong><?php if(!empty($gep_record['date'])): ?><span><?php echo gep_hub_h(gep_hub_date_label($gep_record['date'])); ?></span><?php endif; ?></div>
                  <h4><?php echo gep_hub_h($gep_title); ?></h4>
                  <?php if(!empty($gep_record['place'])): ?><p class="press-row-context"><?php echo gep_hub_h($gep_record['place']); ?></p><?php endif; ?>
                </div>
                <?php if($gep_url): ?><a class="press-row-link" href="<?php echo gep_hub_h($gep_url); ?>" target="_blank" rel="noopener" aria-label="Abrir <?php echo gep_hub_h($gep_title?:'publicación'); ?>"><span>Ver publicación</span><b aria-hidden="true">↗</b></a><?php endif; ?>
              </article>
            <?php endforeach; ?>
            </div>
          </section>

          <section class="comms-section" data-comms-section="eventos">
            <div class="comms-section-title">
              <div><span class="comms-section-kicker">Agenda y archivo</span><h3>Eventos</h3></div>
              <span class="comms-section-note"><?php echo (int)($gep_hub_counts['eventos']??0); ?> registros</span>
            </div>
            <div class="comms-events-list">
            <?php foreach($gep_hub['records'] as $gep_i=>$gep_record):
              if(!is_array($gep_record)||($gep_record['type']??'')!=='eventos'||$gep_i===$gep_hub_event_feature_index)continue;
              $gep_parts=gep_hub_event_date_parts($gep_record['date']??'');
              $gep_url=!empty($gep_record['url'])?gep_hub_url($gep_record['url']):'';
              $gep_title=trim((string)($gep_record['title']??''));
              $gep_year=gep_hub_year($gep_record['date']??'');
              $gep_search=trim(implode(' ',array_filter(array('Eventos',$gep_record['date']??'',$gep_title,$gep_record['source']??'',$gep_record['place']??'',$gep_record['description']??''))));
            ?>
              <article class="comms-record event-row <?php echo gep_hub_h($gep_parts['status_class']); ?>" data-hub-type="eventos" data-hub-year="<?php echo gep_hub_h($gep_year); ?>" data-hub-search="<?php echo gep_hub_h($gep_search); ?>">
                <div class="comms-date-tile compact"><span><?php echo gep_hub_h($gep_parts['month']); ?></span><strong><?php echo gep_hub_h($gep_parts['day']); ?></strong><small><?php echo gep_hub_h($gep_parts['year']); ?></small></div>
                <div class="event-row-copy">
                  <div class="comms-meta-line"><span class="comms-status <?php echo gep_hub_h($gep_parts['status_class']); ?>"><?php echo gep_hub_h($gep_parts['status']); ?></span><?php if(!empty($gep_record['place'])): ?><span><?php echo gep_hub_h($gep_record['place']); ?></span><?php endif; ?></div>
                  <h4><?php echo gep_hub_h($gep_title); ?></h4>
                  <?php if(!empty($gep_record['source'])): ?><p><?php echo gep_hub_h($gep_record['source']); ?></p><?php endif; ?>
                </div>
                <?php if($gep_url): ?><a class="event-row-link" href="<?php echo gep_hub_h($gep_url); ?>" target="_blank" rel="noopener">Ver evento <span aria-hidden="true">↗</span></a><?php endif; ?>
              </article>
            <?php endforeach; ?>
            </div>
          </section>

          <section class="comms-section" data-comms-section="videos">
            <div class="comms-section-title">
              <div><span class="comms-section-kicker">Biblioteca audiovisual</span><h3>Multimedia</h3></div>
              <span class="comms-section-note"><?php echo (int)($gep_hub_counts['videos']??0); ?> registros</span>
            </div>
            <div class="comms-video-grid">
            <?php foreach($gep_hub['records'] as $gep_i=>$gep_record):
              if(!is_array($gep_record)||($gep_record['type']??'')!=='videos')continue;
              $gep_url=!empty($gep_record['url'])?gep_hub_url($gep_record['url']):'';
              $gep_img=!empty($gep_record['image'])?gep_hub_url($gep_record['image']):gep_hub_fallback_image($gep_record,$gep_i);
              $gep_title=trim((string)($gep_record['title']??''));
              $gep_year=gep_hub_year($gep_record['date']??'');
              $gep_duration=trim((string)($gep_record['duration']??''));
              $gep_search=trim(implode(' ',array_filter(array('Multimedia Videos',$gep_record['date']??'',$gep_title,$gep_record['source']??'',$gep_record['place']??'',$gep_record['description']??'',$gep_duration))));
            ?>
              <article class="comms-record video-card" data-hub-type="videos" data-hub-year="<?php echo gep_hub_h($gep_year); ?>" data-hub-search="<?php echo gep_hub_h($gep_search); ?>">
                <div class="video-card-media<?php echo $gep_img?' has-image':' is-placeholder'; ?>">
                  <?php if($gep_img): ?><img loading="lazy" src="<?php echo gep_hub_h($gep_img); ?>" alt="<?php echo gep_hub_h(gep_site_image_alt($gep_img,$gep_record['image_alt']??'')); ?>"><?php else: ?><span class="video-placeholder-word">GEP</span><?php endif; ?>
                  <span class="video-play" aria-hidden="true">▶</span>
                  <?php if($gep_duration!==''): ?><span class="video-duration"><?php echo gep_hub_h($gep_duration); ?></span><?php endif; ?>
                </div>
                <div class="video-card-copy">
                  <div class="comms-meta-line"><?php if(!empty($gep_record['source'])): ?><strong><?php echo gep_hub_h($gep_record['source']); ?></strong><?php endif; ?><?php if(!empty($gep_record['date'])): ?><span><?php echo gep_hub_h(gep_hub_date_label($gep_record['date'])); ?></span><?php endif; ?></div>
                  <h4><?php echo gep_hub_h($gep_title); ?></h4>
                  <?php if(!empty($gep_record['description'])): ?><p><?php echo gep_hub_h($gep_record['description']); ?></p><?php endif; ?>
                  <?php if($gep_url): ?><a class="video-card-link" href="<?php echo gep_hub_h($gep_url); ?>" target="_blank" rel="noopener" aria-label="Reproducir <?php echo gep_hub_h($gep_title?:'video'); ?>"></a><?php endif; ?>
                </div>
              </article>
            <?php endforeach; ?>
            </div>
          </section>
        </div>
      <?php else: ?>
        <div class="content-hub-grid" id="contentHubGrid">
        <?php $gep_hub_rendered=0; foreach($gep_hub['records'] as $gep_hub_record):
          if(!is_array($gep_hub_record))continue;
          $gep_type=isset($gep_hub_record['type'])&&isset($gep_hub_types[$gep_hub_record['type']])?$gep_hub_record['type']:array_key_first($gep_hub_types);
          $gep_img=!empty($gep_hub_record['image'])?gep_hub_url($gep_hub_record['image']):gep_hub_fallback_image($gep_hub_record,$gep_hub_rendered);
          $gep_url=!empty($gep_hub_record['url'])?gep_hub_url($gep_hub_record['url']):'';
          $gep_year=gep_hub_year($gep_hub_record['date']??'');
          $gep_is_featured=$gep_hub_rendered===0;
          $gep_title=trim((string)($gep_hub_record['title']??''));
          $gep_search=trim(implode(' ',array_filter(array($gep_hub_types[$gep_type]??'',$gep_hub_record['date']??'',$gep_title,$gep_hub_record['source']??'',$gep_hub_record['place']??'',$gep_hub_record['description']??''))));
          $gep_hub_rendered++;
        ?>
          <article class="content-hub-card<?php echo $gep_img?' has-image':' no-image'; ?><?php echo $gep_is_featured?' is-featured':''; ?>" data-hub-type="<?php echo gep_hub_h($gep_type); ?>" data-hub-year="<?php echo gep_hub_h($gep_year); ?>" data-hub-search="<?php echo gep_hub_h($gep_search); ?>">
            <?php if($gep_img): ?>
              <div class="content-hub-media"><img class="content-hub-media-fill" loading="lazy" src="<?php echo gep_hub_h($gep_img); ?>" alt="" aria-hidden="true"><img loading="lazy" src="<?php echo gep_hub_h($gep_img); ?>" alt="<?php echo gep_hub_h(gep_site_image_alt($gep_img,$gep_hub_record['image_alt']??'')); ?>"></div>
            <?php else: ?>
              <div class="content-hub-media content-hub-placeholder" aria-hidden="true"><span><?php echo gep_hub_h($gep_hub_types[$gep_type]); ?></span><strong>GEP</strong><small>Geomática y Ecología del Paisaje</small></div>
            <?php endif; ?>
            <div class="content-hub-body">
              <div class="content-hub-meta">
                <?php if($gep_is_featured): ?><span class="content-hub-featured-label">Destacado</span><?php endif; ?>
                <span class="content-hub-type"><?php echo gep_hub_h($gep_hub_types[$gep_type]); ?></span>
                <?php if(!empty($gep_hub_record['date'])): ?><span class="content-hub-date"><?php echo gep_hub_h(gep_hub_date_label($gep_hub_record['date'])); ?></span><?php endif; ?>
              </div>
              <h3><?php echo gep_hub_h($gep_title); ?></h3>
              <?php if(!empty($gep_hub_record['source'])||!empty($gep_hub_record['place'])): ?><p class="content-hub-source"><?php echo gep_hub_h(trim(($gep_hub_record['source']??'').(!empty($gep_hub_record['source'])&&!empty($gep_hub_record['place'])?' · ':'').($gep_hub_record['place']??''))); ?></p><?php endif; ?>
              <?php if(!empty($gep_hub_record['description'])): ?><p class="content-hub-description"><?php echo gep_hub_h($gep_hub_record['description']); ?></p><?php endif; ?>
              <?php if($gep_url): ?><a class="record-link" href="<?php echo gep_hub_h($gep_url); ?>" target="_blank" rel="noopener"><?php echo gep_hub_h(!empty($gep_hub_record['button'])?$gep_hub_record['button']:'Ver más ↗'); ?></a><a class="content-hub-stretched-link" href="<?php echo gep_hub_h($gep_url); ?>" target="_blank" rel="noopener" aria-label="Abrir <?php echo gep_hub_h($gep_title?:'contenido'); ?>"></a><?php endif; ?>
            </div>
          </article>
        <?php endforeach; ?>
        </div>
      <?php endif; ?>
      <div class="empty-state hub-filter-empty" id="hubEmpty" hidden><strong>No encontramos coincidencias.</strong><p>Prueba con otro término o limpia los filtros.</p></div>
    <?php else: ?>
      <div class="empty-state hub-empty-start"><strong>Este apartado ya está listo para recibir contenido.</strong><p>Agrega registros desde GEP Editor: tipo, fecha, título, imagen, descripción y enlace.</p></div>
    <?php endif; ?>
  </div>
</section>
</main>

<?php if($gep_hub['records']): ?>
<script>
(function(){
  var isNews=document.body.classList.contains('page-noticias');
  var isComms=document.body.classList.contains('page-comunicaciones');
  var buttons=[].slice.call(document.querySelectorAll('.hub-filter'));
  var jumpButtons=[].slice.call(document.querySelectorAll('[data-hub-filter-target]'));
  var cards=[].slice.call(document.querySelectorAll(isComms?'.comms-record':'.content-hub-card'));
  var sections=[].slice.call(document.querySelectorAll('.comms-section'));
  var empty=document.getElementById('hubEmpty');
  var count=document.getElementById('hubVisibleCount');
  var search=document.getElementById('hubSearch');
  var year=document.getElementById('hubYear');
  var reset=document.getElementById('hubReset');
  var state={type:'all',query:'',year:'all'};

  function normalize(value){
    return (value||'').toString().normalize('NFD').replace(/[\u0300-\u036f]/g,'').toLowerCase().trim();
  }
  function updateFeatured(visibleCards){
    if(!isNews)return;
    cards.forEach(function(card){
      card.classList.remove('is-featured');
      var label=card.querySelector('.content-hub-featured-label');
      if(label)label.remove();
    });
    if(!visibleCards.length)return;
    var first=visibleCards[0];
    first.classList.add('is-featured');
    var meta=first.querySelector('.content-hub-meta');
    if(meta){
      var badge=document.createElement('span');
      badge.className='content-hub-featured-label';
      badge.textContent='Destacado';
      meta.insertBefore(badge,meta.firstChild);
    }
  }
  function setType(type){
    state.type=type||'all';
    buttons.forEach(function(b){
      var active=(b.getAttribute('data-hub-filter')||'all')===state.type;
      b.classList.toggle('active',active);
      b.setAttribute('aria-pressed',active?'true':'false');
    });
    jumpButtons.forEach(function(b){b.classList.toggle('active',b.getAttribute('data-hub-filter-target')===state.type);});
    apply();
  }
  function apply(){
    var shown=[];
    cards.forEach(function(card){
      var typeOk=state.type==='all'||card.getAttribute('data-hub-type')===state.type;
      var yearOk=state.year==='all'||card.getAttribute('data-hub-year')===state.year;
      var haystack=normalize(card.getAttribute('data-hub-search')||card.textContent);
      var searchOk=!state.query||haystack.indexOf(normalize(state.query))!==-1;
      var visible=typeOk&&yearOk&&searchOk;
      card.hidden=!visible;
      if(visible){shown.push(card);card.classList.remove('hub-card-enter');void card.offsetWidth;card.classList.add('hub-card-enter');}
    });
    updateFeatured(shown);
    if(isComms){
      sections.forEach(function(section){
        var sectionType=section.getAttribute('data-comms-section');
        var visibleInside=[].slice.call(section.querySelectorAll('.comms-record')).some(function(card){return !card.hidden;});
        var typeCompatible=state.type==='all'||state.type===sectionType;
        section.hidden=!(visibleInside&&typeCompatible);
      });
    }
    if(empty)empty.hidden=shown.length!==0;
    if(count)count.textContent=shown.length;
    if(reset)reset.hidden=state.type==='all'&&state.year==='all'&&!state.query;
  }
  buttons.forEach(function(button){button.addEventListener('click',function(){setType(button.getAttribute('data-hub-filter')||'all');});});
  jumpButtons.forEach(function(button){button.addEventListener('click',function(){setType(button.getAttribute('data-hub-filter-target')||'all');var explorer=document.querySelector('.comms-explorer');if(explorer)explorer.scrollIntoView({behavior:'smooth',block:'center'});});});
  if(search)search.addEventListener('input',function(){state.query=search.value;apply();});
  if(year)year.addEventListener('change',function(){state.year=year.value||'all';apply();});
  if(reset){
    reset.addEventListener('click',function(){
      state={type:'all',query:'',year:'all'};
      if(search)search.value='';
      if(year)year.value='all';
      buttons.forEach(function(b){var active=(b.getAttribute('data-hub-filter')||'all')==='all';b.classList.toggle('active',active);b.setAttribute('aria-pressed',active?'true':'false');});
      jumpButtons.forEach(function(b){b.classList.remove('active');});
      apply();
    });
  }
  apply();
})();
</script>
<?php endif; ?>
<?php require __DIR__.'/footer.php'; ?>
