<?php
// Funci��n que aplica los descuentos descuentos a los productos

add_action('admin_init', 'dpc_aplicar_descuentos'); 

function dpc_aplicar_descuentos() {



    if (isset($_POST['dpc_nonce']) && wp_verify_nonce($_POST['dpc_nonce'], 'dpc_descuento_nonce')) {

        if (isset($_POST['category_id']) && isset($_POST['descuento']) && current_user_can('manage_woocommerce')) {

            $categoria_id = sanitize_text_field($_POST['category_id']);
            $descuento = sanitize_text_field($_POST['descuento']);
            $insignia = sanitize_text_field($_POST['badge']);

            dpc_insert_category_discount($categoria_id, $descuento, $insignia); //guardamos la categoria en la tabla

                // Aplicar descuento a los productos de la categoría seleccionada
                $args = array(
                    'post_type' => 'product',
                    'posts_per_page' => -1,
                    'tax_query' => array(
                        array(
                            'taxonomy' => 'product_cat',
                            'field'    => 'term_id',
                            'terms'    => $categoria_id,
                        ),
                    ),
                );

        $query = new WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                $product_id = get_the_ID();
                $product = wc_get_product($product_id);

                if ($product->is_type('simple')) {
                    $precio_original = $product->get_regular_price();
                    if ($precio_original != 0) {
                        $precio_descuento = $precio_original - ($precio_original * ($descuento / 100));
                        $product->set_sale_price($precio_descuento);
                    }
                    $product->save();
                  
                } elseif ($product->is_type('variable')) {
                    $variations = $product->get_children();
                    foreach ($variations as $variation_id) {
                        $variation = wc_get_product($variation_id);
                        $precio_original = $variation->get_regular_price();

                        if ($precio_original != 0) {
                            $precio_descuento = $precio_original - ($precio_original * ($descuento / 100));
                            $variation->set_sale_price($precio_descuento);
                        }
                        $variation->save();
                    }
                }

                // Marcar producto como en oferta
                update_post_meta($product_id, '_sale_price_dates_from', '');
                update_post_meta($product_id, '_sale_price_dates_to', '');

                // Guardar la insignia en los metadatos del producto
                update_post_meta($product_id, '_dpc_badge', $insignia);
            }
            wp_reset_postdata();
        }
    }

        add_action('admin_notices', 'dpc_descuento_aplicado_notice');
    
    }
}


function dpc_descuento_aplicado_notice() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e('Descuento aplicado correctamente a la categoría seleccionada.', 'descuentos-por-categorias'); ?></p>
    </div>
    <?php
}

function dpc_badge_upload_notice() {
    ?>
    <div class="notice notice-success is-dismissible">
        <p><?php _e('Insignia cargada correctamente', 'descuentos-por-categorias'); ?></p>
    </div>
    <?php
}



/**
 * Funcion para estructuras las categorias en padres e hijas
 */
function dpc_display_categories_with_hierarchy($parent_id = 0, $level = 0)
{
    $args = array(
        'taxonomy'     => 'product_cat',
        'hide_empty'   => false,
        'parent'       => $parent_id
    );

    $categories = get_terms($args);

    if ($categories) {
        foreach ($categories as $category) {
            $prefix = str_repeat('&nbsp;&nbsp;', $level * 2); // Aumentar el espaciado con cada nivel
            echo '<option value="' . esc_attr($category->term_id) . '">' . $prefix . esc_html($category->name) . '</option>';

            // Recursividad para subcategorías
            dpc_display_categories_with_hierarchy($category->term_id, $level + 1);
        }
    }
}

function dpc_display_categories_dropdown()
{
    echo '<select name="category_id">';
    dpc_display_categories_with_hierarchy(0);  // Comienza con categorías de nivel superior
    echo '</select>';
}

/**
 * Funci��n para a��adir las categorias con descuntos a la tabla para saber que categorias tienen descuentos y cuales no. 
 */
function dpc_insert_category_discount($category_id, $discount, $badge) {
    global $wpdb;
    $table_name = $wpdb->prefix . 'category_discounts';

    // Verificar si ya existe un descuento para esta categoría
    $exists = $wpdb->get_var($wpdb->prepare(
        "SELECT COUNT(*) FROM $table_name WHERE category_id = %d",
        $category_id
    ));

    if ($exists > 0) {
        // Actualizar el descuento existente
        $wpdb->update(
            $table_name,
            array(
                'discount' => $discount,
                'badge' => $badge,
                'active' => 1
            ), // Datos a actualizar
            array('category_id' => $category_id), // Condici��n WHERE
            array('%f', '%s', '%d'), // Formatos de los datos a actualizar
            array('%d') // Formato de la condici��n WHERE
        );
    } else {
        // Insertar un nuevo descuento
        $wpdb->insert(
            $table_name,
            array(
                'category_id' => $category_id,
                'discount' => $discount,
                'badge' => $badge,
                'active' => 1
            ),
            array('%d', '%f', '%s', '%d') // Formatos de los datos a insertar
        );
    }
}

/**
 * Funciones que vinculas las acciones ajax 
 */

function dpc_handle_eliminar_descuento()
{
    /*  if (!check_ajax_referer('dpc_eliminar_descuento_nonce', 'nonce', false)) {
         wp_send_json_error(array('message' => 'Verificaci��n de seguridad fallida.'));
         return;
     }  */

    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(array('message' => 'No tienes permisos suficientes para realizar esta acci��n.'));
        return;
    }

    $categoria_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    if (!$categoria_id) {
        wp_send_json_error(array('message' => 'ID de categoría inv��lido.'));
        return;
    }

    header('Content-Type: application/json');

    dpc_eliminar_descuentos_categoria($categoria_id);
    dpc_eliminar_registro_descuento($categoria_id);

    wp_send_json_success(array('message' => 'Descuento eliminado correctamente.'));
}

add_action('wp_ajax_dpc_eliminar_descuento', 'dpc_handle_eliminar_descuento');

/**
 * Funcion para revertir los descuentos en los poductos 
 */
function dpc_eliminar_descuentos_categoria($categoria_id)
{
    $args = array(
        'post_type' => 'product',
        'posts_per_page' => -1,
        'tax_query' => array(
            array(
                'taxonomy' => 'product_cat',
                'field' => 'id',
                'terms' => $categoria_id
            )
        ),
        'fields' => 'ids'
    );

    $productos = get_posts($args);
    foreach ($productos as $producto_id) {
        $producto = wc_get_product($producto_id);
        if ($producto->is_type('simple')) {
            $producto->set_sale_price('');
            $producto->set_price($producto->get_regular_price());
        } elseif ($producto->is_type('variable')) {
            $variaciones = $producto->get_children();
            foreach ($variaciones as $variacion_id) {
                $variacion = wc_get_product($variacion_id);
                $variacion->set_sale_price('');
                $variacion->set_price($variacion->get_regular_price());
                $variacion->save();
            }
        }
        $producto->save();
        wc_delete_product_transients($producto_id);
    }
}

/**
 * Eliminar los datos de la tabla descuentos
 */
function dpc_eliminar_registro_descuento($categoria_id)
{
    global $wpdb;
    $table_name = $wpdb->prefix . 'category_discounts';
    $wpdb->delete($table_name, array('category_id' => $categoria_id), array('%d'));
}


/**
 * Funci��n para actualizar los decuentos de los productos
 */

function dpc_handle_update_discount()
{
    check_ajax_referer('update_discount_nonce', 'nonce');

    if (!current_user_can('manage_woocommerce')) {
        wp_send_json_error(array('message' => 'No tienes permisos suficientes para realizar esta acci��n.'));
        return;
    }

    $category_id = isset($_POST['category_id']) ? intval($_POST['category_id']) : 0;
    $discount = isset($_POST['discount']) ? floatval($_POST['discount']) : 0;

    if (!$category_id || $discount <= 0) {
        wp_send_json_error(array('message' => 'Datos inv��lidos.'));
        return;
    }

    global $wpdb;
    $table_name = $wpdb->prefix . 'category_discounts';

    // Verifica si el descuento ya existe y actualízalo
    if ($wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM $table_name WHERE category_id = %d", $category_id))) {
        $wpdb->update($table_name, array('discount' => $discount), array('category_id' => $category_id), array('%f'), array('%d'));
        wp_send_json_success(array('message' => 'Descuento actualizado correctamente.'));
    } else {
        wp_send_json_error(array('message' => 'No se encontr�� el descuento para actualizar.'));
    }
}

/**
 * Manejo de las insignias para las ofertas
 */
add_action('wp_ajax_dpc_guardar_insignia', 'dpc_guardar_insignia');


function dpc_guardar_insignia() {
    check_ajax_referer('dpc_manage_insignias', 'dpc_nonce');

    if (!empty($_FILES['insignia_imagen']['name'])) {
        $insignias_dir = plugin_dir_path(__FILE__) . '../images/';
        
        if (!file_exists($insignias_dir)) {
            mkdir($insignias_dir, 0755, true);
        }

        $file = $_FILES['insignia_imagen'];
        $filename = sanitize_file_name($_POST['insignia_nombre']) . '.' . pathinfo($file['name'], PATHINFO_EXTENSION);
        $file_path = $insignias_dir . $filename;

        if (move_uploaded_file($file['tmp_name'], $file_path)) {
            wp_send_json_success(array('message' => 'Insignia guardada correctamente.'));
        } else {
            wp_send_json_error(array('message' => 'Error al subir la imagen.'));
        }
    } else {
        wp_send_json_error(array('message' => 'No se ha seleccionado ninguna imagen.'));
    }
}

add_action('wp_ajax_dpc_eliminar_insignia', 'dpc_eliminar_insignia');


function dpc_eliminar_insignia() {
    check_ajax_referer('dpc_manage_insignias', '_wpnonce');

    $insignia = sanitize_text_field($_POST['insignia']);
    $insignias_dir = plugin_dir_path(__FILE__) . '../images/';
    $file_path = $insignias_dir . $insignia;

    if (file_exists($file_path)) {
        unlink($file_path);
        wp_send_json_success(array('message' => 'Insignia eliminada correctamente.'));
    } else {
        wp_send_json_error(array('message' => 'La insignia no existe.'));
    }
}
