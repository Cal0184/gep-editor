<?php
if(!function_exists('gep_theme_hero_video_embed_url')){
function gep_theme_hero_video_embed_url($url){
    $url=trim((string)$url); if($url==='')return '';
    $parts=parse_url($url); $host=strtolower($parts['host']??''); $path=$parts['path']??'';
    if(strpos($host,'youtu.be')!==false){$id=trim(explode('/',trim($path,'/'))[0]??'');if($id!=='')return 'https://www.youtube.com/embed/'.rawurlencode($id).'?autoplay=1&mute=1&controls=0&loop=1&playlist='.rawurlencode($id).'&playsinline=1&rel=0';}
    if(strpos($host,'youtube.com')!==false){parse_str($parts['query']??'',$q);$id=$q['v']??'';if($id===''&&preg_match('#/(?:embed|shorts)/([^/?#]+)#',$path,$m))$id=$m[1];if($id!=='')return 'https://www.youtube.com/embed/'.rawurlencode($id).'?autoplay=1&mute=1&controls=0&loop=1&playlist='.rawurlencode($id).'&playsinline=1&rel=0';}
    if(strpos($host,'vimeo.com')!==false&&preg_match('#/(?:video/)?(\d+)#',$path,$m))return 'https://player.vimeo.com/video/'.$m[1].'?background=1&autoplay=1&muted=1&loop=1';
    return '';
}}
if(!function_exists('gep_theme_hero_video_sibling')){
/**
 * v9.21.1: busca en la Mediateca un archivo "hermano" del video, por nombre.
 * Ej.: para .../Video-Test.mp4 busca Video-Test-movil.mp4 o Video-Test-respaldo.jpg.
 * También acepta el nombre sin -pc / -web / -hd (Video-Test-pc.mp4 -> Video-Test-movil.mp4).
 * Primero mira la misma carpeta de uploads y luego toda la Mediateca.
 */
function gep_theme_hero_video_sibling($url,$suffix,$exts){
    static $cache=array();
    $url=trim((string)$url); if($url==='')return '';
    $key=$url.'|'.$suffix.'|'.implode(',',$exts);
    if(isset($cache[$key]))return $cache[$key];
    $cache[$key]='';
    $path=(string)parse_url($url,PHP_URL_PATH);
    $name=pathinfo($path,PATHINFO_FILENAME);
    if($name==='')return '';
    $names=array($name);
    $base=preg_replace('/-(pc|web|hd|liviano|1080p?|720p?)$/i','',$name);
    if($base!==''&&$base!==$name)$names[]=$base;
    $files=array();
    foreach($names as $n){foreach($exts as $e){$files[]=$n.'-'.$suffix.'.'.$e;}}
    // 1) Misma carpeta del video en el servidor.
    if(function_exists('wp_upload_dir')){
        $up=wp_upload_dir(null,false);
        $baseurl=isset($up['baseurl'])?(string)parse_url($up['baseurl'],PHP_URL_PATH):'';
        if($baseurl!==''&&strpos($path,$baseurl.'/')===0&&!empty($up['basedir'])){
            $rel=substr(dirname($path),strlen($baseurl));
            foreach($files as $f){
                if(is_file(rtrim($up['basedir'],'/').$rel.'/'.$f)){
                    return $cache[$key]=substr($url,0,strrpos($url,'/')+1).rawurlencode($f);
                }
            }
        }
    }
    // 2) Cualquier carpeta de la Mediateca (si se subió otro mes).
    global $wpdb;
    if(isset($wpdb)&&is_object($wpdb)&&function_exists('wp_get_attachment_url')){
        foreach($files as $f){
            $id=$wpdb->get_var($wpdb->prepare("SELECT post_id FROM {$wpdb->postmeta} WHERE meta_key='_wp_attached_file' AND (meta_value=%s OR meta_value LIKE %s) ORDER BY post_id DESC LIMIT 1",$f,'%/'.$wpdb->esc_like($f)));
            if($id){$found=wp_get_attachment_url((int)$id);if($found)return $cache[$key]=$found;}
        }
    }
    return '';
}}
if(!function_exists('gep_theme_render_hero_video')){
function gep_theme_render_hero_video($url,$poster='',$label='Video de cabecera'){
    $url=trim((string)$url); if($url==='')return;
    $esc=function($v){return function_exists('esc_url')?esc_url($v):htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');};
    $embed=gep_theme_hero_video_embed_url($url);
    if($embed!==''){
        // v9.26.0 · YouTube / Vimeo: el reproductor tarda 1-3 s en mostrar el
        // video y mientras tanto se veía negro o verde. Ahora queda debajo una
        // portada (la imagen de cabecera o, en YouTube, la miniatura del propio
        // video) y el reproductor aparece recién cuando avisa que está
        // reproduciendo (mensajes de YouTube/Vimeo en site.js). Sin espera extra.
        $poster=trim((string)$poster);
        if($poster!==''&&function_exists('gep_site_media_light_url'))$poster=gep_site_media_light_url($poster,'1536x1536');
        $is_yt=strpos($embed,'youtube.com/embed/')!==false;
        if($poster===''&&$is_yt&&preg_match('#/embed/([^/?]+)#',$embed,$ym))$poster='https://i.ytimg.com/vi/'.rawurlencode(rawurldecode($ym[1])).'/hqdefault.jpg';
        if($is_yt)$embed.='&enablejsapi=1';
        $wait=$poster!==''?' is-video-waiting':'';
        if($poster!=='')echo '<img class="gep-hero-video-poster" src="'.$esc($poster).'" srcset="'.$esc($poster).' 1x" alt="" aria-hidden="true" decoding="async" fetchpriority="high">';
        echo '<iframe class="gep-hero-video-bg gep-hero-video-iframe'.$wait.'" data-gep-embed="'.($is_yt?'youtube':'vimeo').'" src="'.$esc($embed).'" title="'.htmlspecialchars($label,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8').'" tabindex="-1" aria-hidden="true" allow="autoplay; encrypted-media; fullscreen" frameborder="0"></iframe>';
        return;
    }
    // v9.18.0: atributos que pide iPhone/Safari para reproducir solo (muted + playsinline),
    // tipo del archivo declarado y poster como respaldo si el teléfono no lo reproduce.
    $types=array('mp4'=>'video/mp4','m4v'=>'video/mp4','mov'=>'video/mp4','webm'=>'video/webm','ogv'=>'video/ogg','ogg'=>'video/ogg');
    $ext=strtolower(pathinfo((string)parse_url($url,PHP_URL_PATH),PATHINFO_EXTENSION));
    $type=isset($types[$ext])?' type="'.$types[$ext].'"':'';
    // v9.25.0 · iPhone: el video se pide apenas la página pinta su primera
    // imagen en pantalla (dos cuadros de animación), sin esperar a que terminen
    // de cargar las demás fotos ni a un temporizador.
    // Causa del ~1 s de retraso que quedaba en iPhone (9.21.1-9.24): el video
    // llegaba con preload="none" y sin autoplay, y site.js recién lo pedía al
    // evento "load" de la página (o a los 1,5 s); además el elemento se mostraba
    // antes de tener su primer cuadro (fondo verde/negro un instante).
    // - muted + playsinline: lo que Safari exige para reproducir solo. No se usa
    //   el atributo autoplay en el HTML para que el video no le quite conexión
    //   a la hoja de estilos y la página no tarde más en mostrarse.
    // - <source media>: el teléfono elige NOMBRE-movil.mp4 sin JavaScript.
    // - La imagen de respaldo va como <img> debajo del video; el video queda
    //   transparente hasta que su primer cuadro está pintado y entonces aparece
    //   encima con un fundido corto: sin negro, sin verde y sin parpadeo.
    $poster=trim((string)$poster);
    if($poster==='')$poster=gep_theme_hero_video_sibling($url,'respaldo',array('jpg','jpeg','webp','png'));
    // Copia reducida de la foto (si WordPress la generó) para la imagen y el
    // poster: la misma dirección en ambos, así se descarga una sola vez.
    if($poster!==''&&function_exists('gep_site_media_light_url'))$poster=gep_site_media_light_url($poster,'1536x1536');
    $mobile=gep_theme_hero_video_sibling($url,'movil',array('mp4','webm'));
    $attrs=' data-gep-hero-video data-src="'.$esc($url).'"'.($mobile!==''?' data-src-mobile="'.$esc($mobile).'"':'');
    if($poster!==''){
        // La <img> ya se descubre al leer el HTML; un <link rel=preload> aparte
        // descargaba dos veces la misma foto (original + copia reducida).
        // srcset evita que el filtro de imágenes cambie esta dirección por otra copia.
        echo '<img class="gep-hero-video-poster" src="'.$esc($poster).'" srcset="'.$esc($poster).' 1x" alt="" aria-hidden="true" decoding="async" fetchpriority="high">';
    }
    echo '<video class="gep-hero-video-bg" muted loop playsinline webkit-playsinline disablepictureinpicture disableremoteplayback preload="none" aria-hidden="true" tabindex="-1"'.$attrs.($poster!==''?' poster="'.$esc($poster).'"':'').'>';
    if($mobile!==''){
        $mext=strtolower(pathinfo((string)parse_url($mobile,PHP_URL_PATH),PATHINFO_EXTENSION));
        echo '<source src="'.$esc($mobile).'" type="'.($mext==='webm'?'video/webm':'video/mp4').'" media="(max-width: 767px)">';
    }
    echo '<source src="'.$esc($url).'"'.$type.'></video>';
    // Justo después del video (antes de que el navegador elija el archivo):
    // - lo deja transparente hasta su primer cuadro (site.js lo muestra);
    // - con "reducir movimiento" o "ahorro de datos" no se descarga;
    // - sin imagen de respaldo en el servidor, usa el primer cuadro guardado
    //   en este navegador en la visita anterior.
    echo '<script>(function(v){try{if(!v)return;var m=window.matchMedia,c=navigator.connection;if((m&&m("(prefers-reduced-motion: reduce)").matches)||(c&&c.saveData)){v.preload="none";while(v.firstChild)v.removeChild(v.firstChild);v.classList.add("is-video-off");return;}v.classList.add("is-video-waiting");var go=function(){if(v.getAttribute("data-gep-started"))return;v.setAttribute("data-gep-started","1");v.preload="auto";v.setAttribute("autoplay","");v.muted=true;try{var r=v.play();if(r&&r.catch)r.catch(function(){});}catch(e){}};var raf=window.requestAnimationFrame;if(raf)raf(function(){raf(go);});else setTimeout(go,0);if(!v.poster){var s=(v.getAttribute("data-src")||"").replace(/[?#].*$/,"").replace(/^https?:\/\/[^/]+/i,"");var p=s&&window.localStorage.getItem("gep-hero-poster:"+s);if(p){v.poster=p;var i=document.createElement("img");i.className="gep-hero-video-poster";i.alt="";i.setAttribute("aria-hidden","true");i.src=p;v.parentNode.insertBefore(i,v);}}}catch(e){}})(document.currentScript&&document.currentScript.previousElementSibling);</script>';
}}
