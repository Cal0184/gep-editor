<?php
require_once __DIR__ . '/includes/wp-bootstrap.php';
gep_site_bootstrap_wordpress();

$pageTitle = 'GEP · Quiénes somos';
$pageDescription = 'Personas y trayectoria del Laboratorio GEP.';
$bodyClass = 'page-quienes-somos';
$activePage = 'quienes-somos';
$isHome = false;
require __DIR__ . '/includes/header.php';



$wpTitle = 'Quiénes somos';
$wpContent = '<p>Edita esta página desde WordPress para mostrar contenido aquí.</p>';
$wpExcerpt = 'Investigadores, profesionales, estudiantes, colaboradores y becarios vinculados a la trayectoria del laboratorio.';
$gepMeta = array();
$wpPageId = 0;

if ( function_exists( 'gep_demo_get_page_navigation' ) ) {
    $gep_navigation = gep_demo_get_page_navigation();
    if ( ! empty( $gep_navigation['quienes-somos']['page']->ID ) ) $wpPageId = (int) $gep_navigation['quienes-somos']['page']->ID;
}
if ( ! $wpPageId && function_exists( 'get_page_by_path' ) ) {
    $gep_about_page = get_page_by_path( 'quienes-somos', OBJECT, 'page' );
    if ( $gep_about_page && ! empty( $gep_about_page->ID ) ) $wpPageId = (int) $gep_about_page->ID;
}
// Compatibilidad con la instalación histórica, sin obligar a usar ese ID.
if ( ! $wpPageId && function_exists( 'get_post' ) && get_post( 9 ) ) $wpPageId = 9;

if ( $wpPageId && function_exists( 'get_post' ) ) {
    $gep_about_post = get_post( $wpPageId );
    if ( $gep_about_post ) {
        if ( '' !== trim( (string) $gep_about_post->post_title ) ) $wpTitle = $gep_about_post->post_title;
        if ( '' !== trim( (string) $gep_about_post->post_content ) ) $wpContent = $gep_about_post->post_content;
        if ( '' !== trim( (string) $gep_about_post->post_excerpt ) ) $wpExcerpt = $gep_about_post->post_excerpt;
    }

    $metaKeys = array(
        'gep_team_enabled','gep_team_eyebrow','gep_team_title','gep_team_description','gep_team_cards_json','gep_about_hero_image','gep_about_hero_video',
        'gep_directory_eyebrow','gep_directory_title','gep_directory_description','gep_directory_legend','gep_directory_image_url',
        'gep_directory_items_json','gep_directory_sections_json','gep_directory_replace_static'
    );
    if ( function_exists( 'get_post_meta' ) ) {
        foreach ( $metaKeys as $metaKey ) {
            if ( function_exists( 'metadata_exists' ) && metadata_exists( 'post', $wpPageId, $metaKey ) ) {
                $gepMeta[ $metaKey ] = get_post_meta( $wpPageId, $metaKey, true );
            }
        }
    }
}

$teamEnabled = !array_key_exists('gep_team_enabled', $gepMeta) || $gepMeta['gep_team_enabled'] === '1';
$aboutHeroImage = !empty($gepMeta['gep_about_hero_image']) ? $gepMeta['gep_about_hero_image'] : '';
$aboutHeroVideo = !empty($gepMeta['gep_about_hero_video']) ? $gepMeta['gep_about_hero_video'] : '';

$teamEyebrow = !empty($gepMeta['gep_team_eyebrow'])
    ? $gepMeta['gep_team_eyebrow']
    : 'Equipo y disciplinas';

$teamTitle = !empty($gepMeta['gep_team_title'])
    ? $gepMeta['gep_team_title']
    : "Personas detrás\nde los datos,\nmapas y paisajes.";

$teamDescription = !empty($gepMeta['gep_team_description'])
    ? $gepMeta['gep_team_description']
    : 'Estas imágenes son referenciales y se reemplazarán por fotografías institucionales del equipo antes de la publicación definitiva.';

$defaultTeamCards = [
    [
        'image' => '',
        'kicker' => 'GEP · EQUIPO',
        'badge' => 'IMAGEN REFERENCIAL',
        'title' => 'Geomática y paisaje',
        'text' => 'Análisis espacial · Ecología del paisaje',
    ],
    [
        'image' => '',
        'kicker' => 'GEP · EQUIPO',
        'badge' => 'IMAGEN REFERENCIAL',
        'title' => 'Mensura forestal',
        'text' => 'Recursos forestales · Inventario',
    ],
    [
        'image' => '',
        'kicker' => 'GEP · EQUIPO',
        'badge' => 'IMAGEN REFERENCIAL',
        'title' => 'Conservación y territorio',
        'text' => 'Planificación · Biodiversidad',
    ],
    [
        'image' => '',
        'kicker' => 'GEP · EQUIPO',
        'badge' => 'IMAGEN REFERENCIAL',
        'title' => 'Investigación en formación',
        'text' => 'Tesis · Proyectos · Herramientas geoespaciales',
    ],
];

$teamCards = $defaultTeamCards;
if (array_key_exists('gep_team_cards_json', $gepMeta)) {
    $decodedTeamCards = json_decode($gepMeta['gep_team_cards_json'], true);
    $teamCards = is_array($decodedTeamCards) ? $decodedTeamCards : [];
}

$directoryEyebrow = !empty($gepMeta['gep_directory_eyebrow'])
    ? $gepMeta['gep_directory_eyebrow']
    : 'Directorio GEP';

$directoryTitle = !empty($gepMeta['gep_directory_title'])
    ? $gepMeta['gep_directory_title']
    : 'Trayectoria de investigadores y estudiantes.';

$directoryDescription = !empty($gepMeta['gep_directory_description'])
    ? $gepMeta['gep_directory_description']
    : 'El directorio mantiene el contenido nominal recuperado, pero lo organiza en grupos desplegables para una lectura más clara y móvil.';

$directoryLegend = !empty($gepMeta['gep_directory_legend'])
    ? $gepMeta['gep_directory_legend']
    : 'Estados del registro original: D = en desarrollo · T = terminado.';

$directoryImage = !empty($gepMeta['gep_directory_image_url'])
    ? $gepMeta['gep_directory_image_url']
    : '';

$directoryItems = [];
if (!empty($gepMeta['gep_directory_items_json'])) {
    $decodedItems = json_decode($gepMeta['gep_directory_items_json'], true);
    if (is_array($decodedItems)) {
        $directoryItems = $decodedItems;
    }
}

$directorySections = [];
if (!empty($gepMeta['gep_directory_sections_json'])) {
    $decodedSections = json_decode($gepMeta['gep_directory_sections_json'], true);
    if (is_array($decodedSections)) {
        $directorySections = $decodedSections;
    }
}

// v9.20.0: si el editor guardó apartados, se publican. El interruptor antiguo
// "Usar el contenido creado" ya no se ve en el editor, por eso no se exige.
$replaceStaticDirectory = !empty($directorySections);

// Si el plugin pide reemplazar el directorio estático pero no hay apartados dinámicos válidos,
// mantenemos el directorio original como respaldo para evitar una página vacía.
if ($replaceStaticDirectory && empty($directorySections)) {
    $replaceStaticDirectory = false;
}
?>


<hr>
<main>
    <?php $aboutHeroStyle=$aboutHeroImage!==''?' style="background-image:url(' . htmlspecialchars($aboutHeroImage,ENT_QUOTES,'UTF-8') . ');"':''; ?>
    <section data-gep-section="hero" class="page-hero hero-equipo<?php echo $aboutHeroVideo!==''?' has-video-bg':''; ?>"<?php echo $aboutHeroStyle; ?>><?php gep_theme_render_hero_video($aboutHeroVideo,$aboutHeroImage,'Video de cabecera de Quiénes somos'); ?>
        <div class="container page-hero-content">
            <p class="eyebrow">Personas</p>
            <h1><?php echo htmlspecialchars($wpTitle, ENT_QUOTES, 'UTF-8'); ?></h1>
            <p><?php echo htmlspecialchars($wpExcerpt, ENT_QUOTES, 'UTF-8'); ?></p>
        </div>
    </section>
    <nav data-gep-section="hero" aria-label="Navegación de sección" class="section-nav">
        <div class="container section-nav-inner"><a href="#investigadores">Investigadores</a><a
                href="#profesionales">Profesionales</a><a href="#postgrado">Postgrado</a><a
                href="#pregrado">Pregrado</a><a href="#colaboradores">Colaboradores</a><a href="#becarios">Becarios</a>
        </div>
    </nav>

    <style id="gep-team-disciplines-style">
        .gep-team-disciplines {
            padding: 66px 0 72px;
            background: #eef3ef;
        }

        .gep-team-disciplines-heading {
            display: grid;
            grid-template-columns: minmax(0, .95fr) minmax(0, 1.05fr);
            gap: clamp(40px, 8vw, 120px);
            align-items: end;
            margin-bottom: 36px;
        }

        .gep-team-disciplines-title .eyebrow {
            margin-bottom: 10px;
        }

        .gep-team-disciplines-title h2 {
            max-width: 620px;
            margin: 0;
            color: #0d2c1c;
            font-size: clamp(46px, 4.25vw, 66px);
            line-height: .99;
            letter-spacing: -.045em;
        }

        .gep-team-disciplines-title::after {
            content: "";
            display: block;
            width: 48px;
            height: 2px;
            margin-top: 18px;
            background: #4e7f61;
        }

        .gep-team-disciplines-description {
            max-width: 670px;
            margin: 0 0 3px;
            color: #66766d;
            font-size: 16px;
            line-height: 1.55;
        }

        .gep-team-disciplines-grid {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .gep-discipline-card {
            min-width: 0;
            overflow: hidden;
            border: 1px solid #d8e1db;
            border-radius: 18px;
            background: #fff;
            box-shadow: 0 10px 28px rgba(24, 53, 36, .035);
        }

        .gep-discipline-media {
            position: relative;
            aspect-ratio: .82 / 1;
            overflow: hidden;
            background:
                linear-gradient(rgba(62, 94, 75, .07) 1px, transparent 1px),
                linear-gradient(90deg, rgba(62, 94, 75, .07) 1px, transparent 1px),
                #e5ede8;
            background-size: 36px 36px;
        }

        .gep-discipline-media > img {
            position: absolute;
            inset: 0;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .gep-discipline-kicker {
            position: absolute;
            z-index: 3;
            top: 18px;
            left: 18px;
            color: #587466;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: .16em;
            text-transform: uppercase;
        }

        .gep-discipline-badge {
            position: absolute;
            z-index: 3;
            left: 12px;
            bottom: 10px;
            max-width: calc(100% - 24px);
            padding: 7px 10px;
            overflow: hidden;
            border-radius: 999px;
            background: rgba(22, 58, 38, .94);
            color: #fff;
            font-size: 10px;
            font-weight: 800;
            line-height: 1;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .gep-discipline-placeholder {
            position: absolute;
            inset: 0;
        }

        .gep-discipline-placeholder::before,
        .gep-discipline-placeholder::after {
            content: "";
            position: absolute;
            left: 50%;
            transform: translateX(-50%);
            background: #97b39f;
        }

        .gep-discipline-placeholder::before {
            top: 24%;
            width: 34%;
            aspect-ratio: 1;
            border-radius: 50%;
            box-shadow:
                0 12px 0 -3px rgba(87, 123, 99, .42),
                0 0 0 10px rgba(255,255,255,.22),
                0 0 0 11px rgba(64, 96, 76, .25);
        }

        .gep-discipline-placeholder::after {
            bottom: -8%;
            width: 58%;
            height: 48%;
            border-radius: 50% 50% 10% 10% / 55% 55% 8% 8%;
            background: #4c735d;
        }

        .gep-discipline-card:nth-child(2) .gep-discipline-media {
            background-color: #e8eee9;
        }
        .gep-discipline-card:nth-child(2) .gep-discipline-placeholder::before {
            background: #a8b8aa;
        }
        .gep-discipline-card:nth-child(2) .gep-discipline-placeholder::after {
            background: #607a68;
        }
        .gep-discipline-card:nth-child(3) .gep-discipline-media {
            background-color: #e2eceb;
        }
        .gep-discipline-card:nth-child(3) .gep-discipline-placeholder::before {
            background: #91b2aa;
        }
        .gep-discipline-card:nth-child(3) .gep-discipline-placeholder::after {
            background: #47776d;
        }
        .gep-discipline-card:nth-child(4) .gep-discipline-media {
            background-color: #ebeee2;
        }
        .gep-discipline-card:nth-child(4) .gep-discipline-placeholder::before {
            background: #adb69d;
        }
        .gep-discipline-card:nth-child(4) .gep-discipline-placeholder::after {
            background: #6a785d;
        }

        .gep-discipline-copy {
            min-height: 106px;
            padding: 18px 16px 20px;
        }

        .gep-discipline-copy h3 {
            margin: 0 0 7px;
            color: #152d20;
            font-size: 16px;
            line-height: 1.25;
        }

        .gep-discipline-copy p {
            margin: 0;
            color: #68766e;
            font-size: 12px;
            line-height: 1.5;
        }

        @media (max-width: 1050px) {
            .gep-team-disciplines-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .gep-discipline-media {
                aspect-ratio: 1.08 / 1;
            }
        }

        @media (max-width: 700px) {
            .gep-team-disciplines {
                padding: 42px 0 50px;
            }

            .gep-team-disciplines-heading {
                grid-template-columns: 1fr;
                gap: 22px;
                margin-bottom: 28px;
            }

            .gep-team-disciplines-title h2 {
                font-size: clamp(38px, 12vw, 52px);
            }

            .gep-team-disciplines-description {
                font-size: 14px;
            }

            .gep-team-disciplines-grid {
                grid-template-columns: 1fr;
            }

            .gep-discipline-media {
                aspect-ratio: 1.25 / 1;
            }
        }
    </style>

    <?php if ($teamEnabled && !empty($teamCards)): ?>
        <section data-gep-section="team" class="gep-team-disciplines" id="equipo-disciplinas">
            <div class="container">
                <div class="gep-team-disciplines-heading">
                    <div class="gep-team-disciplines-title">
                        <p class="eyebrow"><?php echo htmlspecialchars($teamEyebrow, ENT_QUOTES, 'UTF-8'); ?></p>
                        <h2><?php echo nl2br(htmlspecialchars($teamTitle, ENT_QUOTES, 'UTF-8')); ?></h2>
                    </div>
                    <p class="gep-team-disciplines-description"><?php echo nl2br(htmlspecialchars($teamDescription, ENT_QUOTES, 'UTF-8')); ?></p>
                </div>

                <div class="gep-team-disciplines-grid">
                    <?php foreach ($teamCards as $teamCard): ?>
                        <?php
                        $teamImage = isset($teamCard['image']) ? trim((string)$teamCard['image']) : '';
                        $teamKicker = isset($teamCard['kicker']) ? trim((string)$teamCard['kicker']) : '';
                        $teamBadge = isset($teamCard['badge']) ? trim((string)$teamCard['badge']) : '';
                        $teamCardTitle = isset($teamCard['title']) ? trim((string)$teamCard['title']) : '';
                        $teamCardText = isset($teamCard['text']) ? trim((string)$teamCard['text']) : '';
                        ?>
                        <article class="gep-discipline-card">
                            <div class="gep-discipline-media">
                                <?php if ($teamImage !== ''): ?>
                                    <img
                                        src="<?php echo htmlspecialchars($teamImage, ENT_QUOTES, 'UTF-8'); ?>"
                                        alt="<?php echo htmlspecialchars(gep_site_image_alt($teamImage, '', $teamCardTitle !== '' ? $teamCardTitle : 'Equipo GEP'), ENT_QUOTES, 'UTF-8'); ?>"
                                        loading="lazy"
                                    >
                                <?php else: ?>
                                    <div class="gep-discipline-placeholder" aria-hidden="true"></div>
                                <?php endif; ?>

                                <?php if ($teamKicker !== ''): ?>
                                    <span class="gep-discipline-kicker"><?php echo htmlspecialchars($teamKicker, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>

                                <?php if ($teamBadge !== ''): ?>
                                    <span class="gep-discipline-badge"><?php echo htmlspecialchars($teamBadge, ENT_QUOTES, 'UTF-8'); ?></span>
                                <?php endif; ?>
                            </div>

                            <div class="gep-discipline-copy">
                                <?php if ($teamCardTitle !== ''): ?>
                                    <h3><?php echo htmlspecialchars($teamCardTitle, ENT_QUOTES, 'UTF-8'); ?></h3>
                                <?php endif; ?>
                                <?php if ($teamCardText !== ''): ?>
                                    <p><?php echo nl2br(htmlspecialchars($teamCardText, ENT_QUOTES, 'UTF-8')); ?></p>
                                <?php endif; ?>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>
    <?php endif; ?>

    <style>
        /* Personas administradas desde el plugin: foto opcional + contenido. */
        .directory-dynamic-section .people-entry.has-photo {
            display: grid;
            grid-template-columns: 118px minmax(0, 1fr);
            gap: 22px;
            align-items: start;
        }

        .directory-dynamic-section .people-entry-photo {
            width: 118px;
            aspect-ratio: 1 / 1;
            overflow: hidden;
            border-radius: 16px;
            background: #eef3ef;
            border: 1px solid rgba(31, 90, 59, .12);
        }

        .directory-dynamic-section .people-entry-photo img {
            display: block;
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .directory-dynamic-section .people-entry-copy {
            min-width: 0;
        }

        .directory-dynamic-section .people-entry-copy h3 {
            margin-top: 0;
        }

        @media (max-width: 700px) {
            .directory-dynamic-section .people-entry.has-photo {
                grid-template-columns: 88px minmax(0, 1fr);
                gap: 14px;
            }

            .directory-dynamic-section .people-entry-photo {
                width: 88px;
                border-radius: 12px;
            }
        }
    </style>

    <section data-gep-section="directory" class="section">
        <div class="container">
            <div class="people-directory">
                <div class="directory-intro">
                    <p class="eyebrow"><?php echo htmlspecialchars($directoryEyebrow, ENT_QUOTES, 'UTF-8'); ?></p>
                    <h2><?php echo htmlspecialchars($directoryTitle, ENT_QUOTES, 'UTF-8'); ?></h2>
                    <p><?php echo nl2br(htmlspecialchars($directoryDescription, ENT_QUOTES, 'UTF-8')); ?></p>
                    <div class="directory-legend"><?php echo htmlspecialchars($directoryLegend, ENT_QUOTES, 'UTF-8'); ?></div>
                </div>

                <?php if ($directoryImage !== ''): ?>
                    <figure class="directory-feature-image">
                        <img src="<?php echo htmlspecialchars($directoryImage, ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars(gep_site_image_alt($directoryImage, '', $directoryTitle), ENT_QUOTES, 'UTF-8'); ?>">
                    </figure>
                <?php endif; ?>

                <?php if (!empty($directoryItems)): ?>
                    <div class="directory-dynamic-grid" aria-label="Elementos adicionales">
                        <?php foreach ($directoryItems as $item): ?>
                            <article class="directory-dynamic-card">
                                <?php if (!empty($item['title'])): ?>
                                    <h3><?php echo htmlspecialchars($item['title'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                <?php endif; ?>
                                <?php if (!empty($item['text'])): ?>
                                    <p><?php echo nl2br(htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8')); ?></p>
                                <?php endif; ?>
                            </article>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
                <?php if (!empty($directorySections)): ?>
                    <?php foreach ($directorySections as $sectionIndex => $section):
                        $sectionType = isset($section['type']) ? $section['type'] : 'people';
                        $sectionTitle = isset($section['title']) ? $section['title'] : 'Apartado';
                        $sectionItems = isset($section['items']) && is_array($section['items']) ? $section['items'] : [];
                        $sectionOpen = !empty($section['open']);

                        // Mantiene los enlaces de la barra superior funcionando también
                        // cuando los apartados provienen del plugin.
                        $sectionTitleKey = strtolower(trim($sectionTitle));
                        $sectionTitleKey = strtr($sectionTitleKey, [
                            'á' => 'a', 'é' => 'e', 'í' => 'i',
                            'ó' => 'o', 'ú' => 'u', 'ñ' => 'n',
                        ]);

                        if (strpos($sectionTitleKey, 'investigador') !== false) {
                            $sectionId = 'investigadores';
                        } elseif (strpos($sectionTitleKey, 'profesional') !== false) {
                            $sectionId = 'profesionales';
                        } elseif (strpos($sectionTitleKey, 'postgrado') !== false) {
                            $sectionId = 'postgrado';
                        } elseif (strpos($sectionTitleKey, 'pregrado') !== false) {
                            $sectionId = 'pregrado';
                        } elseif (strpos($sectionTitleKey, 'colaborador') !== false) {
                            $sectionId = 'colaboradores';
                        } elseif (strpos($sectionTitleKey, 'becario') !== false) {
                            $sectionId = 'becarios';
                        } else {
                            $sectionId = 'gep-apartado-' . ($sectionIndex + 1);
                        }
                    ?>
                        <details class="directory-section directory-dynamic-section" id="<?php echo htmlspecialchars($sectionId, ENT_QUOTES, 'UTF-8'); ?>" <?php echo $sectionOpen ? 'open="open"' : ''; ?>>
                            <summary>
                                <span>
                                    <span class="directory-kicker"><?php echo count($sectionItems); ?> registros</span>
                                    <strong><?php echo htmlspecialchars($sectionTitle, ENT_QUOTES, 'UTF-8'); ?></strong>
                                </span>
                                <span class="summary-plus">＋</span>
                            </summary>
                            <div class="directory-content">

                                <?php if ($sectionType === 'people'): ?>
                                    <div class="people-list">
                                        <?php foreach ($sectionItems as $item): ?>
                                            <?php $hasPersonImage = !empty($item['image']); ?>
                                            <article class="people-entry<?php echo $hasPersonImage ? ' has-photo' : ''; ?>">
                                                <?php if ($hasPersonImage): ?>
                                                    <div class="people-entry-photo">
                                                        <img
                                                            src="<?php echo htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8'); ?>"
                                                            alt="<?php echo htmlspecialchars(gep_site_image_alt($item['image'], '', isset($item['name']) ? $item['name'] : 'Persona del GEP'), ENT_QUOTES, 'UTF-8'); ?>"
                                                            loading="lazy"
                                                        >
                                                    </div>
                                                <?php endif; ?>

                                                <div class="people-entry-copy">
                                                    <?php if (!empty($item['name'])): ?>
                                                        <h3><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                                    <?php endif; ?>
                                                    <p>
                                                        <?php if (!empty($item['meta'])): ?>
                                                            <strong><?php echo htmlspecialchars($item['meta'], ENT_QUOTES, 'UTF-8'); ?></strong><br>
                                                        <?php endif; ?>
                                                        <?php if (!empty($item['text'])): ?>
                                                            <?php echo nl2br(htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8')); ?>
                                                        <?php endif; ?>
                                                        <?php if (!empty($item['email'])): ?>
                                                            <br><a class="record-link" href="mailto:<?php echo htmlspecialchars($item['email'], ENT_QUOTES, 'UTF-8'); ?>"><?php echo htmlspecialchars($item['email'], ENT_QUOTES, 'UTF-8'); ?></a>
                                                        <?php endif; ?>
                                                    </p>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>

                                <?php elseif ($sectionType === 'status'): ?>
                                    <?php
                                    $currentGroup = null;
                                    $listOpen = false;
                                    foreach ($sectionItems as $item):
                                        $itemGroup = isset($item['group']) ? trim($item['group']) : '';
                                        if ($itemGroup !== $currentGroup):
                                            if ($listOpen) {
                                                echo '</div>';
                                            }
                                            if ($itemGroup !== '') {
                                                echo '<h3 class="subgroup-title">' . htmlspecialchars($itemGroup, ENT_QUOTES, 'UTF-8') . '</h3>';
                                            }
                                            echo '<div class="student-list">';
                                            $listOpen = true;
                                            $currentGroup = $itemGroup;
                                        endif;
                                    ?>
                                        <article class="student-row">
                                            <span class="status-badge"><?php echo htmlspecialchars(isset($item['status']) ? $item['status'] : '', ENT_QUOTES, 'UTF-8'); ?></span>
                                            <div>
                                                <?php if (!empty($item['name'])): ?>
                                                    <h3><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                                <?php endif; ?>
                                                <?php if (!empty($item['text'])): ?>
                                                    <p><?php echo nl2br(htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8')); ?></p>
                                                <?php endif; ?>
                                            </div>
                                        </article>
                                    <?php endforeach; ?>
                                    <?php if ($listOpen) echo '</div>'; ?>

                                <?php elseif ($sectionType === 'photos'): ?>
                                    <div class="team-grid">
                                        <?php foreach ($sectionItems as $item): ?>
                                            <article class="person">
                                                <div class="person-photo">
                                                    <?php if (!empty($item['image'])): ?>
                                                        <img src="<?php echo htmlspecialchars($item['image'], ENT_QUOTES, 'UTF-8'); ?>" alt="<?php echo htmlspecialchars(gep_site_image_alt($item['image'], '', isset($item['name']) ? $item['name'] : ''), ENT_QUOTES, 'UTF-8'); ?>">
                                                    <?php endif; ?>
                                                    <?php if (!empty($item['label'])): ?>
                                                        <span class="placeholder-tag"><?php echo htmlspecialchars($item['label'], ENT_QUOTES, 'UTF-8'); ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <div class="person-copy">
                                                    <?php if (!empty($item['name'])): ?>
                                                        <h3><?php echo htmlspecialchars($item['name'], ENT_QUOTES, 'UTF-8'); ?></h3>
                                                    <?php endif; ?>
                                                    <?php if (!empty($item['text'])): ?>
                                                        <p><?php echo nl2br(htmlspecialchars($item['text'], ENT_QUOTES, 'UTF-8')); ?></p>
                                                    <?php endif; ?>
                                                </div>
                                            </article>
                                        <?php endforeach; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </details>
                    <?php endforeach; ?>
                <?php endif; ?>

                <?php if (!$replaceStaticDirectory): ?>
                <details class="directory-section" id="investigadores" open="open">
                    <summary><span><span class="directory-kicker">3
                                registros</span><strong>Investigadores</strong></span><span
                            class="summary-plus">＋</span></summary>
                    <div class="directory-content">
                        <div class="people-list">
                            <article class="people-entry">
                                <h3>Jaime Hernández Palma</h3>
                                <p><strong>Ing. Forestal, Dr. Ing. de Montes</strong><br />Director del GEP;
                                    especialista en geomática y ecología del paisaje aplicada a la evaluación y
                                    seguimiento de la vegetación.<br /><a class="record-link"
                                        href="mailto:jhernand@uchile.cl">jhernand@uchile.cl</a></p>
                            </article>
                            <article class="people-entry">
                                <h3>Patricio Corvalán Vera</h3>
                                <p><strong>Ing. Forestal, Magíster Manejo Recursos Forestales</strong><br />Profesor
                                    asistente del Dpto. Gestión Forestal y Medio Ambiente, Universidad de Chile;
                                    especialista en mensura forestal.<br /><a class="record-link"
                                        href="mailto:pcorvala@uchile.cl">pcorvala@uchile.cl</a></p>
                            </article>
                            <article class="people-entry">
                                <h3>Cristián Estades Marfán</h3>
                                <p><strong>Ing. Forestal, MSc, PhD Wildlife Ecology</strong><br />Profesor Asociado del
                                    Dpto. Gestión Forestal y Medio Ambiente, Universidad de Chile; especialista en
                                    ecología de vida silvestre y biología de la conservación.<br /><a
                                        class="record-link" href="mailto:cestades@uchile.cl">cestades@uchile.cl</a></p>
                            </article>
                        </div>
                    </div>
                </details>
                <details class="directory-section" id="profesionales" open="open">
                    <summary><span><span class="directory-kicker">3
                                registros</span><strong>Profesionales</strong></span><span class="summary-plus">＋</span>
                    </summary>
                    <div class="directory-content">
                        <div class="people-list">
                            <article class="people-entry">
                                <h3>María Paz Acuña Ruz</h3>
                                <p><strong>Ing. en Recursos Naturales Renovables, MSc. en Áreas Silvestres y
                                        Conservación de la Naturaleza, (c) Dra. en Ciencias Silvoagropecuarias y
                                        Veterinarias</strong><br />Investigador asociado y coordinador GEP; especialista
                                    en ecología del paisaje y movimiento animal.<br /><a class="record-link"
                                        href="mailto:mpacuna@u.uchile.cl">mpacuna@u.uchile.cl</a></p>
                            </article>
                            <article class="people-entry">
                                <h3>Lissette Cortés Serey</h3>
                                <p><strong>Ing. en Recursos Naturales Renovables, Diplomado en Geomática
                                        Aplicada</strong><br />Investigador asociado; especialista en geomática aplicada
                                    a recursos naturales.<br /><a class="record-link"
                                        href="mailto:lcortes.serey@gmail.com">lcortes.serey@gmail.com</a></p>
                            </article>
                            <article class="people-entry">
                                <h3>Taryn Fuentes Castillo</h3>
                                <p><strong>Ing. en Recursos Naturales Renovables, MSc. en Áreas Silvestres y
                                        Conservación de la Naturaleza, (c) Dra. en Ciencias Silvoagropecuarias y
                                        Veterinarias</strong><br />Investigador asociado; especialista en planificación
                                    sistemática de la conservación y ecología espacial.<br /><a class="record-link"
                                        href="mailto:tarfuentes@uchile.cl">tarfuentes@uchile.cl</a></p>
                            </article>
                        </div>
                    </div>
                </details>
                <details class="directory-section" id="postgrado">
                    <summary><span><span class="directory-kicker">9 registros</span><strong>Estudiantes de
                                postgrado</strong></span><span class="summary-plus">＋</span></summary>
                    <div class="directory-content">
                        <h3 class="subgroup-title">Doctorado Ciencias Silvoagropecuarias y Veterinarias (CSAV)</h3>
                        <div class="student-list">
                            <article class="student-row"><span class="status-badge">D</span>
                                <div>
                                    <h3>Lissette Cortés</h3>
                                    <p>Estimación del carbono almacenado y secuestrado en bosque nativo y plantaciones
                                        de Chile.</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">T</span>
                                <div>
                                    <h3>Taryn Fuentes</h3>
                                    <p>Planificación para la conservación de la biodiversidad frente al cambio climático
                                        en zonas altiplánicas: un enfoque integrado y dinámico.</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">T</span>
                                <div>
                                    <h3>María Paz Acuña Ruz</h3>
                                    <p>Poblaciones de aves acuáticas como bioindicadores para la gestión de humedales
                                        costeros en Chile central.</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">T</span>
                                <div>
                                    <h3>Paula Santibañez</h3>
                                    <p>Análisis de la vulnerabilidad de la silvoagricultura chilena frente a escenarios
                                        de cambio climático mediante técnicas de simulación y sistemas de información
                                        geográfica.</p>
                                </div>
                            </article>
                        </div>
                        <h3 class="subgroup-title">Magíster en Áreas Silvestres y Conservación de la Naturaleza (MASCN)
                        </h3>
                        <div class="student-list">
                            <article class="student-row"><span class="status-badge">T</span>
                                <div>
                                    <h3>Alba Lucia Marín</h3>
                                    <p>Dinámica espacio-temporal del uso y cobertura del suelo en el paisaje cafetero
                                        colombiano.</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">T</span>
                                <div>
                                    <h3>Carolina Gallardo</h3>
                                    <p>Influencia de las características del paisaje sobre la estructura genética de las
                                        poblaciones de Bufo spinulosus en la región de Antofagasta, Chile.</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">T</span>
                                <div>
                                    <h3>Fabiola Gamboa</h3>
                                    <p>Caracterización florística, vegetacional y etnobotánica de la Reserva Nacional
                                        Alto Loa, II Región de Antofagasta, Chile.</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">T</span>
                                <div>
                                    <h3>Natacha Oyola</h3>
                                    <p>Identificación de Humedales del Norte Grande de Chile utilizando técnicas
                                        geomáticas en imágenes satelitales Landsat.</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">D</span>
                                <div>
                                    <h3>Leonor Andrade</h3>
                                    <p>Propuesta de una red de microreservas para el picaflor de Arica (Eulidia
                                        yarrellii).</p>
                                </div>
                            </article>
                        </div>
                    </div>
                </details>
                <details class="directory-section" id="pregrado">
                    <summary><span><span class="directory-kicker">21 registros</span><strong>Estudiantes de
                                pregrado</strong></span><span class="summary-plus">＋</span></summary>
                    <div class="directory-content">
                        <h3 class="subgroup-title">Ingeniería en Recursos Naturales Renovables (RRNN)</h3>
                        <div class="student-list">
                            <article class="student-row"><span class="status-badge">D</span>
                                <div>
                                    <h3>Ginia Troncoso</h3>
                                    <p>Evaluación de la respuesta morfofisiológica de quillay (Quillaja saponaria
                                        Molina) a contaminantes atmosféricos en la ciudad de Santiago.</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">T</span>
                                <div>
                                    <h3>Tomás Acuña</h3>
                                    <p>Firmas espectrales de vegetación urbana en Santiago de Chile: caracterización y
                                        separabilidad de especies a escala foliar.</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">T</span>
                                <div>
                                    <h3>Andrés Ceballos</h3>
                                    <p>Combinación de información topográfica-estructural LiDAR y teledetección
                                        hiperespectral para estimar la diversidad florística vascular de un bosque
                                        mediterráneo caducifolio en la precordillera andina del Maule, Chile.</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">T</span>
                                <div>
                                    <h3>Javier Lopatín</h3>
                                    <p>Utilización de datos LiDAR en la modelación del régimen hídrico a escala predial.
                                    </p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">T</span>
                                <div>
                                    <h3>Alejandro Miranda</h3>
                                    <p>Identificación de sitios favorables para la regeneración de la palma chilena
                                        (Jubaea chilensis) a través de modelación espacial.</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">T</span>
                                <div>
                                    <h3>Carola Villablanca</h3>
                                    <p>Estimación de cambios de coberturas vegetales en zonas de Chile central, durante
                                        el período 1955–1975.</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">D</span>
                                <div>
                                    <h3>Cecilia Romero</h3>
                                    <p>Evaluación del tamaño poblacional local de palma chilena, Jubaea chilensis,
                                        mediante clasificación digital de imágenes satelitales de alta resolución.</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">T</span>
                                <div>
                                    <h3>Inao Vásquez</h3>
                                    <p>Patrones espaciales de reclutamiento y dispersión de semillas del árbol Persea
                                        lingue en el bosque del valle del sur de Chile: efectos del hábitat y un
                                        corredor.</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">T</span>
                                <div>
                                    <h3>Valentina Escanilla</h3>
                                    <p>Patrones espacio-temporales de la vegetación nativa en cuatro paisajes locales de
                                        la depresión intermedia de la Provincia de Osorno, Región de Los Lagos.</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">D</span>
                                <div>
                                    <h3>Lissette Cortés</h3>
                                    <p>Modelos para la predicción de biomasa aérea en plantaciones de Pino insigne
                                        usando imágenes LANDSAT ETM+ y variables topográficas.</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">D</span>
                                <div>
                                    <h3>Raúl Díaz</h3>
                                    <p>Análisis de la evolución espacio-temporal de la invasión de Rubus ulmifolius,
                                        Aristotelia chilensis y Ugni molinae, en la Isla Robinson Crusoe, período
                                        1983-2010.</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">D</span>
                                <div>
                                    <h3>Ignacia Nuñez</h3>
                                    <p>Modelo predictivo basado en percepción ciudadana de la distribución espacial de
                                        vinchucas en Chile central.</p>
                                </div>
                            </article>
                        </div>
                        <h3 class="subgroup-title">Ingeniería Forestal</h3>
                        <div class="student-list">
                            <article class="student-row"><span class="status-badge">D</span>
                                <div>
                                    <h3>Rodrigo Segura</h3>
                                    <p>Estructura y composición vegetacional de la ciudad de Santiago en el año 2014.
                                    </p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">T</span>
                                <div>
                                    <h3>Diego Valencia</h3>
                                    <p>Evaluación de la estructura espacial del volumen estéreo del sotobosque en
                                        plantaciones de Pinus radiata utilizando datos LiDAR.</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">D</span>
                                <div>
                                    <h3>Pablo Montero</h3>
                                    <p>Propuesta de criterios silviculturales para la convivencia compatible entre
                                        arbolado urbano y alumbrado público, comuna de La Reina.</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">T</span>
                                <div>
                                    <h3>Daniel Montaner</h3>
                                    <p>Segmentación automática de copas de árboles en plantaciones de Pinus radiata
                                        usando fotografías aéreas digitales.</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">T</span>
                                <div>
                                    <h3>Cynnamon Dobbs</h3>
                                    <p>Construcción de modelos de estimación de biomasa y área foliar para diez especies
                                        arbóreas urbanas de la ciudad de Santiago.</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">T</span>
                                <div>
                                    <h3>Valeska Pizarro</h3>
                                    <p>Desarrollo de un sistema de información de apoyo a la gestión del arbolado
                                        público urbano.</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">T</span>
                                <div>
                                    <h3>Sebastián Varela</h3>
                                    <p>Calidad de la vegetación urbana como hábitat para aves: el caso de Santiago de
                                        Chile.</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">T</span>
                                <div>
                                    <h3>Francisca Toro</h3>
                                    <p>Áreas potenciales para la aplicación de biosólidos en plantaciones forestales de
                                        la VI Región de Chile.</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">T</span>
                                <div>
                                    <h3>Bianca Castillo</h3>
                                    <p>Determinación de la fragilidad adquirida del paisaje en el Parque Nacional
                                        Huerquehue, Temuco, IX Región.</p>
                                </div>
                            </article>
                        </div>
                    </div>
                </details>
                <details class="directory-section" id="colaboradores">
                    <summary><span><span class="directory-kicker">4
                                registros</span><strong>Colaboradores</strong></span><span class="summary-plus">＋</span>
                    </summary>
                    <div class="directory-content">
                        <div class="people-list">
                            <article class="people-entry">
                                <h3>Benito González</h3>
                                <p><strong>Ing. Agrónomo, Dr. Ciencias Silvoagropecuarias y
                                        Veterinarias</strong><br />GECS · LEVS</p>
                            </article>
                            <article class="people-entry">
                                <h3>Nélida Villaseñor</h3>
                                <p><strong>Médico Veterinario, Magíster Áreas Silvestres y Conservación de la
                                        Naturaleza</strong><br />LEVS</p>
                            </article>
                            <article class="people-entry">
                                <h3>María Angélica Vukasovic</h3>
                                <p><strong>Ecólogo paisajista</strong><br />LEVS</p>
                            </article>
                            <article class="people-entry">
                                <h3>Sandra Uribe</h3>
                                <p><strong>Ing. Forestal</strong><br />LEVS</p>
                            </article>
                        </div>
                    </div>
                </details>
                <details class="directory-section" id="becarios">
                    <summary><span><span class="directory-kicker">4
                                registros</span><strong>Becarios</strong></span><span class="summary-plus">＋</span>
                    </summary>
                    <div class="directory-content">
                        <div class="student-list">
                            <article class="student-row"><span class="status-badge">2007–2008</span>
                                <div>
                                    <h3>María Paz Acuña Ruz</h3>
                                    <p>Ing. en Recursos Naturales Renovables, Magíster Áreas Silvestres y Conservación
                                        de la Naturaleza</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">2007–2008</span>
                                <div>
                                    <h3>Andrea Contreras</h3>
                                    <p>Ing. en Recursos Naturales Renovables, Magíster Áreas Silvestres y Conservación
                                        de la Naturaleza</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">2010</span>
                                <div>
                                    <h3>Catalina Hernández</h3>
                                    <p>Ing. Agrónoma, Magíster Áreas Silvestres y Conservación de la Naturaleza</p>
                                </div>
                            </article>
                            <article class="student-row"><span class="status-badge">2011</span>
                                <div>
                                    <h3>Constanza Rodríguez</h3>
                                    <p>Médico Veterinario, Magíster Áreas Silvestres y Conservación de la Naturaleza</p>
                                </div>
                            </article>
                        </div>
                    </div>
                </details>
                <?php endif; ?>
            </div>
        </div>
    </section>
</main>

<style id="gep-directory-nav-behaviour">
/* Evita que el header/barra superior tape el apartado al navegar por anclas. */
body.page-quienes-somos .directory-section {
    scroll-margin-top: 118px;
}

@media (max-width: 700px) {
    body.page-quienes-somos .directory-section {
        scroll-margin-top: 92px;
    }
}
</style>

<script id="gep-directory-nav-script">
(function () {
    var nav = document.querySelector('body.page-quienes-somos .section-nav');
    if (!nav) return;

    function normalize(value) {
        return (value || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    var aliases = {
        investigadores: ['investigador', 'investigadores'],
        profesionales: ['profesional', 'profesionales'],
        postgrado: ['postgrado', 'estudiantes de postgrado'],
        pregrado: ['pregrado', 'estudiantes de pregrado'],
        colaboradores: ['colaborador', 'colaboradores'],
        becarios: ['becario', 'becarios']
    };

    function findDirectorySection(key) {
        var direct = document.getElementById(key);
        if (direct && direct.classList.contains('directory-section')) return direct;

        var wanted = aliases[key] || [key];
        var sections = Array.prototype.slice.call(document.querySelectorAll('.directory-section'));
        var found = sections.find(function (section) {
            var title = section.querySelector('summary strong');
            var text = normalize(title ? title.textContent : '');
            return wanted.some(function (term) {
                return text.indexOf(normalize(term)) !== -1;
            });
        });

        // Compatibilidad temporal con la configuración actual del plugin,
        // donde Postgrado y Pregrado pueden estar reunidos como “Estudiantes”.
        if (!found && (key === 'postgrado' || key === 'pregrado')) {
            found = sections.find(function (section) {
                var title = section.querySelector('summary strong');
                var text = normalize(title ? title.textContent : '');
                return text === 'estudiantes' || text.indexOf('estudiantes') !== -1;
            });
        }

        return found || null;
    }

    function goToSection(key, updateHash) {
        var target = findDirectorySection(key);
        if (!target) return false;

        if (target.tagName && target.tagName.toLowerCase() === 'details') {
            target.open = true;
        }

        if (updateHash && window.history && window.history.replaceState) {
            window.history.replaceState(null, '', '#' + key);
        }

        window.requestAnimationFrame(function () {
            target.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });

        return true;
    }

    nav.addEventListener('click', function (event) {
        var link = event.target.closest('a[href^="#"]');
        if (!link || !nav.contains(link)) return;

        var key = link.getAttribute('href').slice(1);
        if (!key) return;

        if (goToSection(key, true)) {
            event.preventDefault();
        }
    });

    function openHashTarget() {
        var key = window.location.hash.replace(/^#/, '');
        if (!key || !aliases[key]) return;
        goToSection(key, false);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', openHashTarget);
    } else {
        openHashTarget();
    }

    window.addEventListener('hashchange', openHashTarget);
})();
</script>

<style id="gep-quienes-mobile-safety">
@media (max-width: 700px) {
    body.page-quienes-somos .directory-intro,
    body.page-quienes-somos .directory-section,
    body.page-quienes-somos .directory-section.reveal-item,
    body.page-quienes-somos .directory-content,
    body.page-quienes-somos .people-entry,
    body.page-quienes-somos .student-row,
    body.page-quienes-somos .person {
        opacity: 1 !important;
        visibility: visible !important;
        transform: none !important;
        filter: none !important;
    }
}
</style>
<script>
(function () {
    function showDirectoryOnMobile() {
        if (!window.matchMedia || !window.matchMedia('(max-width: 700px)').matches) return;
        document.querySelectorAll('body.page-quienes-somos .directory-section').forEach(function (el) {
            el.classList.add('in-view');
        });
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', showDirectoryOnMobile);
    } else {
        showDirectoryOnMobile();
    }
    window.addEventListener('pageshow', showDirectoryOnMobile);
})();
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>