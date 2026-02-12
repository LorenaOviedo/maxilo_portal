<?php
/*Footer, cierre del layout, scripts*/
// Si no está incluido config.php, lo incluimos para tener acceso a constantes y funciones
if (!defined('SITE_URL')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
?>
    </div> <!-- Cierre de dashboard-container -->
    
    <!-- jQuery (pendiente producción) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- JavaScript general -->
    <script src="<?php echo asset('js/main.js'); ?>?v=<?php echo SITE_VERSION; ?>"></script>
    
    <!-- JavaScript de la página actual -->
    <?php if (isset($page_js)): ?>
        <?php foreach ((array)$page_js as $js): ?>
            <script src="<?php echo asset('js/' . $js); ?>?v=<?php echo SITE_VERSION; ?>"></script>
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- JavaScript extra -->
    <?php if (isset($additional_js)): ?>
        <?php echo $additional_js; ?>
    <?php endif; ?>
    
    <!-- Script para cierre de sesión, configuración del timeout -->
    <script>
        // Timeout de sesión (<?php echo SESSION_LIFETIME; ?> segundos)
        let sessionTimeout = setTimeout(function() {
            alert('Tu sesión ha expirado. Por favor, inicia sesión nuevamente.');
            window.location.href = '<?php echo url("index.php?logout=1"); ?>';
        }, <?php echo SESSION_LIFETIME * 1000; ?>);
        
        // Resetear timeout con cada interacción del usuario
        document.addEventListener('click', function() {
            clearTimeout(sessionTimeout);
            sessionTimeout = setTimeout(function() {
                alert('Tu sesión ha expirado. Por favor, inicia sesión nuevamente.');
                window.location.href = '<?php echo url("index.php?logout=1"); ?>';
            }, <?php echo SESSION_LIFETIME * 1000; ?>);
        });
    </script>
</body>
</html>