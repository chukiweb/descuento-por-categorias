<?php
// Archivo que se ejecuta al activar el plugins

/**
 * Función que crea la base de datos para guardar los decuentos 
 */
function dpc_create_discount_table()
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'category_discounts';  // El nombre de la tabla con el prefijo de WordPress

    // SQL para crear la tabla
    $sql = "CREATE TABLE $table_name (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        category_id mediumint(9) NOT NULL,
        discount float NOT NULL,
        badge VARCHAR(255) NOT NULL,
        active tinyint(1) DEFAULT 1 NOT NULL,
        PRIMARY KEY  (id)
    );";

    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    dbDelta($sql);
}


/***
 * Funcion que elimina las etiquetas de oferta por defecto
 */
function my_discount_plugin_activate()
{
    $theme_functions_file = get_stylesheet_directory() . '/functions.php';

    $theme_styles_file = get_stylesheet_directory() . '/style.css';

            $function_to_add = "/* Ocultar el botón de oferta/rebajado */
                    add_filter('woocommerce_sale_flash', 'woo_custom_hide_sales_flash');

                    function woo_custom_hide_sales_flash()
                    {
                        return false;
                    }";

            $css_to_add = "/* Clases CSS añadidas por el plugin de descuentos */
            span.ast-on-card-button, .ast-onsale-card{
                display: none !important;
            }
            
            .woocommerce-js ul.products li.product a img {
            width: auto;
            }";

    // Verificar si el archivo functions.php del tema hijo existe
    if (file_exists($theme_functions_file)) {
        // Leer el contenido actual del archivo functions.php del tema hijo
        $current_content = file_get_contents($theme_functions_file);

        // Verificar si la función ya está presente
        if (strpos($current_content, 'woo_custom_hide_sales_flash') === false) {
            // Añadir la función al final del archivo functions.php del tema hijo
            $new_content = $current_content . PHP_EOL . $function_to_add;
            $result = file_put_contents($theme_functions_file, $new_content);

            // Comprobar si la escritura fue exitosa
            if ($result !== false) {
                error_log('La función woo_custom_hide_sales_flash se ha añadido correctamente al functions.php del tema hijo');
            } else {
                error_log('Error al escribir en el archivo functions.php del tema hijo');
            }
        } else {
            error_log('La función woo_custom_hide_sales_flash ya existe en el archivo functions.php del tema hijo');
        }
    } else {
        // Intentar con el tema padre solo si el archivo functions.php no existe en el tema hijo
        $parent_theme_functions_file = get_template_directory() . '/functions.php';

        if (file_exists($parent_theme_functions_file)) {
            $current_content = file_get_contents($parent_theme_functions_file);

            // Verificar si la función ya está presente
            if (strpos($current_content, 'woo_custom_hide_sales_flash') === false) {
                // Añadir la función al final del archivo functions.php del tema padre
                $new_content = $current_content . PHP_EOL . $function_to_add;
                $result = file_put_contents($parent_theme_functions_file, $new_content);

                // Comprobar si la escritura fue exitosa
                if ($result !== false) {
                    error_log('La función woo_custom_hide_sales_flash se ha añadido correctamente al functions.php del tema padre');
                } else {
                    error_log('Error al escribir en el archivo functions.php del tema padre');
                }
            } else {
                error_log('La función woo_custom_hide_sales_flash ya existe en el archivo functions.php del tema padre');
            }
        } else {
            error_log('No se encontró el archivo functions.php en el tema activo');
        }
    }
    // Añadir clases CSS al style.css del tema hijo
    if (file_exists($theme_styles_file)) {
        $current_css = file_get_contents($theme_styles_file);

        if (strpos($current_css, '.my-custom-class') === false) {
            $new_css = $current_css . PHP_EOL . $css_to_add;
            $result = file_put_contents($theme_styles_file, $new_css);

            if ($result !== false) {
                error_log('Las clases CSS se han añadido correctamente al style.css del tema hijo');
            } else {
                error_log('Error al escribir en el archivo style.css del tema hijo');
            }
        } else {
            error_log('Las clases CSS ya existen en el archivo style.css del tema hijo');
        }
    } else {
        error_log('El archivo style.css no se encuentra en el tema hijo');
    }
}

function dpc_enlace_ajustes($links)
{
    $settings_link = '<a href="admin.php?page=descuentos-por-categorias">' . __('Settings') . '</a>';
    array_unshift($links, $settings_link);
    return $links;
}


