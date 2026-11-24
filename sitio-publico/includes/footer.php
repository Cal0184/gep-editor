<footer>
    <div class="container">
        <div class="footer-grid">
            <div>
                <?php if (!empty($logoPath)): ?>
                    <a class="footer-brand-link" href="index.php" aria-label="GEP · Inicio">
                        <img class="footer-brand-logo" src="<?= gep_escape($logoPath) ?>" alt="GEP - Laboratorio de Geomática y Ecología del Paisaje"/>
                    </a>
                <?php else: ?>
                    <div class="footer-brand-small">GEP</div>
                <?php endif; ?>
                <h3>Laboratorio de Geomática y Ecología del Paisaje</h3>
                <p>Facultad de Ciencias Forestales y de la Conservación de la Naturaleza · Universidad de Chile.</p>
            </div>
            <div>
                <h4>Explorar</h4>
                <ul>
                    <li><a href="docencia.php">Docencia</a></li>
                    <li><a href="proyectos.php">Proyectos</a></li>
                    <li><a href="servicios.php">Servicios</a></li>
                    <li><a href="publicaciones.php">Publicaciones</a></li>
                    <li><a href="comunicaciones.php">Comunicaciones</a></li>
                    <li><a href="noticias.php">Noticias</a></li>
                    <li><a href="quienes-somos.php">Quiénes somos</a></li>
                </ul>
            </div>
            <div>
                <h4>Campus Antumapu</h4>
                <p>Av. Santa Rosa 11315<br/>La Pintana · Santiago de Chile</p>
                <p class="footer-contact-link"><a href="contactanos.php">Ver contacto →</a></p>
            </div>
            <div class="footer-social">
                <h4>REDES SOCIALES</h4>
                <div class="footer-social-icons">

                    <!-- Instagram -->
                    <a
                        href="https://www.instagram.com/mapbiomas_chile/"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="footer-social-link"
                        aria-label="Instagram GEP"
                    >
                        <svg
                            class="footer-social-instagram"
                            viewBox="0 0 24 24"
                            aria-hidden="true"
                        >
                            <rect
                                x="3"
                                y="3"
                                width="18"
                                height="18"
                                rx="5"
                                ry="5"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            />

                            <circle
                                cx="12"
                                cy="12"
                                r="4"
                                fill="none"
                                stroke="currentColor"
                                stroke-width="2"
                            />

                            <circle
                                cx="17.5"
                                cy="6.5"
                                r="1"
                                fill="currentColor"
                            />
                        </svg>
                    </a>


                    <!-- MapBiomas -->
                    <a
                        href="https://chile.mapbiomas.org/"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="footer-social-link"
                        aria-label="MapBiomas"
                    >
                        <img
                            src="assets/images/branding/mapbiomas.png"
                            alt="MapBiomas"
                            class="footer-social-mapbiomas"
                        >
                    </a>

                </div>
            </div>

        </div>
        <div class="footer-bottom">
            <span>GEP · Geomática y Ecología del Paisaje</span>
        </div>
    </div>
</footer>
<script src="assets/js/site.js?v=<?= (int) @filemtime(__DIR__ . '/../assets/js/site.js') ?>"></script>
<?php if ( function_exists( 'do_action' ) ) { do_action( 'wp_footer' ); } ?>
</body>
</html>
