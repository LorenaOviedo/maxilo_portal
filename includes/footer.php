<?php
/*Footer, cierre del layout, scripts*/
// Si no está incluido config.php, lo incluimos para tener acceso a constantes y funciones
if (!defined('SITE_URL')) {
    require_once dirname(__DIR__) . '/config/config.php';
}

$scripts_globales = [
    'main.js',
    'burger-menu.js',
    'catalogos-tabla.js',
    'modal.js',
    'OdontogramaModel.js',
    'odontogramaController.js',
];

?>
    </div> <!-- Cierre de dashboard-container -->
    
    <!-- jQuery (pendiente producción) -->
    <script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
    
    <!-- JavaScript general -->
    <script src="<?php echo asset('js/main.js'); ?>?v=<?php echo SITE_VERSION; ?>"></script>
    <script src="<?php echo asset('js/burger-menu.js'); ?>"></script>

    <script src="<?php echo asset('js/catalog-table.js'); ?>?v=<?php echo SITE_VERSION; ?>"></script>
    <script src="<?php echo asset('js/modal.js'); ?>?v=<?php echo SITE_VERSION; ?>"></script>

    <!-- Vue.js — debe cargarse ANTES que OdontogramaModel y OdontogramaController -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/vue/3.3.4/vue.global.prod.min.js"></script>

    <!-- Odontograma MVC — Model siempre ANTES que Controller -->
    <script src="<?php echo asset('js/OdontogramaModel.js'); ?>?v=<?php echo SITE_VERSION; ?>"></script>
    <script src="<?php echo asset('js/OdontogramaController.js'); ?>?v=<?php echo SITE_VERSION; ?>"></script>

    
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

     <!-- Timeout de sesión -->
    <script>
        (function () {
            const TIMEOUT_MS = <?php echo SESSION_LIFETIME * 1000; ?>;
            const LOGOUT_URL = '<?php echo url("index.php?logout=1"); ?>';

            function iniciarTimeout() {
                return setTimeout(function () {
                    alert('Tu sesión ha expirado. Por favor, inicia sesión nuevamente.');
                    window.location.href = LOGOUT_URL;
                }, TIMEOUT_MS);
            }

            let sessionTimeout = iniciarTimeout();

            // Resetear con cada click del usuario
            document.addEventListener('click', function () {
                clearTimeout(sessionTimeout);
                sessionTimeout = iniciarTimeout();
            });
        })();
    </script>
    
    <!-- Script para cierre de sesión, configuración del timeout 
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
    </script>-->
</body>
</html>