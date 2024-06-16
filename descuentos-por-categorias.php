<?php
/*
Plugin Name: Descuentos por Categorías
Plugin URI: https://cubetic.com
Description: Aplica descuentos por categoría en WooCommerce.
Version: 1.3
Author: Nico Demarchi
Author URI: https://nicodemarchi.ovh
*/

// Hook para añadir la página de menú
add_action('admin_menu', 'dpc_agregar_menu');
function dpc_agregar_menu() {
    add_submenu_page(
        'woocommerce', // Slug del menú padre (WooCommerce)
        'Descuentos por Categorías', // Título de la página
        'Descuentos', // Título del menú
        'manage_options', // Capacidad
        'descuentos-por-categorias', // Slug del menú
        'dpc_pagina_admin' // Función que muestra la página
    );
}

// Añadir enlace directo a ajustes desde plugins
add_filter('plugin_action_links_' . plugin_basename(__FILE__), 'dpc_enlace_ajustes');

include_once plugin_dir_path(__FILE__) . 'dashboard-page.php';


// Enqueue el archivo JavaScript
wp_enqueue_script('dpc-admin-script', plugin_dir_url(__FILE__) . 'js/admin-scripts.js', array('jquery'), null, true);

include_once plugin_dir_path(__FILE__) . 'includes/install.php';
include_once plugin_dir_path(__FILE__) . 'includes/unistall.php';



// Hook para instalar todo lo necesario del plugins
register_activation_hook(__FILE__, 'dpc_create_discount_table');
register_activation_hook(__FILE__, 'my_discount_plugin_activate');


//Funcion que se ejecuta cuando desintalamos el plugins y elimina cualquier rastro del plugins
 
register_deactivation_hook(__FILE__, 'dpc_deactivate_plugin'); 
register_uninstall_hook(__FILE__, 'dpc_plugin_uninstall');





