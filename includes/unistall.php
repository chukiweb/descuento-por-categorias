<?php
function dpc_deactivate_plugin() {
    global $wpdb;
    $table_name = $wpdb->prefix . 'category_discounts';

    $sql = "DROP TABLE IF EXISTS $table_name;";
    $wpdb->query($sql);
    
}

function my_discount_plugin_uninstall() {
    $theme_functions_file = get_stylesheet_directory() . '/functions.php';
    $theme_styles_file = get_stylesheet_directory() . '/style.css';
    
        $function_to_remove = "/*DPC Ocultar el botón de oferta/rebajado */
        add_filter('woocommerce_sale_flash', 'woo_custom_hide_sales_flash');

        function woo_custom_hide_sales_flash()
        {
            return false;
        }";

        $css_to_remove ="/*DPC Clases CSS añadidas por el plugin de descuentos */
        span.ast-on-card-button, .ast-onsale-card{
            display: none !important;
        }
        
        .woocommerce-js ul.products li.product a img {
        width: auto;
        }";

    // Eliminar función del functions.php del tema hijo
    if (file_exists($theme_functions_file)) {
        $current_content = file_get_contents($theme_functions_file);

        if (strpos($current_content, 'woo_custom_hide_sales_flash') !== false) {
            $new_content = str_replace($function_to_remove, '', $current_content);
            $result = file_put_contents($theme_functions_file, $new_content);

            if ($result !== false) {
                error_log('La función woo_custom_hide_sales_flash se ha eliminado correctamente del functions.php del tema hijo');
            } else {
                error_log('Error al escribir en el archivo functions.php del tema hijo durante la desinstalación');
            }
        } else {
            error_log('La función woo_custom_hide_sales_flash no se encontró en el archivo functions.php del tema hijo');
        }
    } else {
        error_log('El archivo functions.php no se encuentra en el tema hijo');
    }

    // Eliminar clases CSS del style.css del tema hijo
    if (file_exists($theme_styles_file)) {
        $current_css = file_get_contents($theme_styles_file);

        if (strpos($current_css, '.my-custom-class') !== false) {
            $new_css = str_replace($css_to_remove, '', $current_css);
            $result = file_put_contents($theme_styles_file, $new_css);

            if ($result !== false) {
                error_log('Las clases CSS se han eliminado correctamente del style.css del tema hijo');
            } else {
                error_log('Error al escribir en el archivo style.css del tema hijo durante la desinstalación');
            }
        } else {
            error_log('Las clases CSS no se encontraron en el archivo style.css del tema hijo');
        }
    } else {
        error_log('El archivo style.css no se encuentra en el tema hijo');
    }
}

