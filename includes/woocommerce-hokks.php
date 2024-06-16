<?php
add_action('wp_enqueue_scripts', 'dpc_custom_badge_styles');

function dpc_custom_badge_styles() {
    wp_enqueue_style('dpc-custom-badge-styles', plugin_dir_url(__FILE__) . '../css/badge-styles.css');
}

// Eliminar la etiqueta de oferta predeterminada de WooCommerce
remove_action('woocommerce_before_shop_loop_item_title', 'woocommerce_show_product_loop_sale_flash', 10);
remove_action('woocommerce_before_single_product_summary', 'woocommerce_show_product_sale_flash', 10);

// Añadir nuestras propias acciones para mostrar las insignias personalizadas
add_action('woocommerce_before_shop_loop_item_title', 'dpc_custom_badge_html_shop', 10);
add_action('woocommerce_before_single_product_summary', 'dpc_custom_badge_html_single', 10);

function dpc_custom_badge_html_shop() {
    dpc_custom_badge_html('shop-loop');
}

// Función para mostrar la insignia de descuento en la página de producto
function dpc_custom_badge_html_single() {
    dpc_custom_badge_html('single-product');
}

// Función para mostrar la insignia de descuento
function dpc_custom_badge_html($context_class) {
    global $product;
    $badge = dpc_get_badge_for_product($product);

    if ($badge) {
        $imgSrc = plugin_dir_url(__FILE__) . '../images/' . $badge . '.svg';
        echo '<div class="wp-block-column is-layout-flow wp-block-column-is-layout-flow"><img class="dpc-badge ' . esc_attr($context_class) . '" src="' . esc_url($imgSrc) . '" alt="Insignia de descuento" /></div>';
    }
}

// Función para obtener la insignia de descuento de un producto
function dpc_get_badge_for_product($product) {
    global $wpdb;
    $category_ids = $product->get_category_ids();

    if ($category_ids) {
        foreach ($category_ids as $category_id) {
            $table_name = $wpdb->prefix . 'category_discounts';
            $badge = $wpdb->get_var($wpdb->prepare(
                "SELECT badge FROM $table_name WHERE category_id = %d",
                $category_id
            ));

            if ($badge) {
                return $badge;
            }
        }
    }

    return false;
}

