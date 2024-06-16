<?php
// Incluir archivos necesarios
include_once plugin_dir_path(__FILE__) . 'includes/woocommerce-hokks.php';
include_once plugin_dir_path(__FILE__) . 'includes/functions.php';

// Enqueue el archivo JavaScript
wp_enqueue_script('dpc-admin-script', plugin_dir_url(__FILE__) . 'js/admin-scripts.js', array('jquery'), null, true);
wp_localize_script('dpc-admin-script', 'ajaxurl', admin_url('admin-ajax.php'));

// Enqueue el archivo CSS
wp_enqueue_style('dpc-admin-style', plugin_dir_url(__FILE__) . 'css/admin-styles.css');
wp_enqueue_style('dpc-badge-style', plugin_dir_url(__FILE__) . 'css/badge-styles.css');

function dpc_pagina_admin()
{
?>

    <div class="wrap dpc-plugin-panel">
        <!-- Bloque de las insignias  -->
        <div class="dpc-section active">
            <h2 class="wp-heading-inline">Insignias</h2>
            <button class="button button-primary dpc-add-insignia">Añadir nueva insignia</button>
            <form id="nueva-insignia-form" style="display: none;" enctype="multipart/form-data" class="dpc-form">
                <?php wp_nonce_field('dpc_manage_insignias', 'dpc_nonce'); ?>
                <label for="insignia_nombre">Nombre de la Insignia:</label>
                <input type="text" name="insignia_nombre" id="insignia_nombre" required>
                <label for="insignia_imagen">Seleccionar imagen:</label>
                <input type="file" name="insignia_imagen" id="insignia_imagen" accept="image/*" required>
                <button type="submit" class="button button-primary">Guardar Insignia</button>
            </form>
            <table class="wp-list-table widefat fixed striped">
                <thead>
                    <tr>
                        <th class="manage-column column-title">Nombre</th>
                        <th class="manage-column column-preview">Vista previa</th>
                        <th class="manage-column column-actions">Acciones</th>
                    </tr>
                </thead>
                <tbody id="insignias-table-body">
                    <?php
                    // Obtener todas las insignias guardadas en el directorio de imágenes del plugin
                    $insignias_dir = plugin_dir_path(__FILE__) . 'images/';
                    $insignias_url = plugin_dir_url(__FILE__) . 'images/';

                    $insignias = scandir($insignias_dir);
                    foreach ($insignias as $insignia) {
                        if ($insignia != '.' && $insignia != '..') {
                            $insignia_name = pathinfo($insignia, PATHINFO_FILENAME);
                            $insignia_url = $insignias_url . $insignia;
                            echo '<tr>';
                            echo '<td>' . esc_html($insignia_name) . '</td>';
                            echo '<td><img src="' . esc_url($insignia_url) . '" alt="' . esc_attr($insignia_name) . '" style="max-width: 50px; max-height: 50px;"></td>';
                            echo '<td><button class="button delete-insignia" data-insignia="' . esc_attr($insignia) . '">Eliminar</button></td>';
                            echo '</tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>

        <!-- Bloque de las ofertas  -->
        <div class="dpc-section">
            <h2 class="wp-heading-inline">Categorías con descuentos</h2>
            <p>Para actualizar o editar el porcentaje de descuento, seleccionamos la categoría y le aplicamos el nuevo descuento.</p>
            <?php
            global $wpdb;
            $table_name = $wpdb->prefix . 'category_discounts';
            $discounts = $wpdb->get_results("SELECT * FROM $table_name");

            if (!empty($discounts)) : ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th class="manage-column column-primary">ID</th>
                            <th class="manage-column column-name">Categoría</th>
                            <th class="manage-column column-discount">Descuento (%)</th>
                            <th class="manage-column column-badge">Insignia</th>
                            <th class="manage-column column-actions">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($discounts as $discount) : ?>
                            <tr>
                                <td><?php echo esc_html($discount->id); ?></td>
                                <td><?php echo esc_html(get_term_by('id', $discount->category_id, 'product_cat')->name); ?></td>
                                <td><?php echo esc_html($discount->discount); ?>%</td>
                                <td><?php echo esc_html($discount->badge); ?></td>
                                <td>
                                    <button class="button delete-discount" data-id="<?php echo esc_attr($discount->id); ?>" data-category-id="<?php echo esc_attr($discount->category_id); ?>">Eliminar</button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else : ?>
                <p>No hay descuentos configurados.</p>
                <a href="#" id="nueva-oferta" class="button button-primary dpc-add-offer">Añadir nueva Oferta</a>
            <?php endif; ?>
        </div>

        <div class="dpc-section nueva-oferta" style="margin-top: 20px;">
            <h2 class="wp-heading-inline">Nueva Oferta</h2>
            <form method="POST" action="#" class="dpc-form">
                <div class="dpc-form-row">
                    <div class="dpc-form-group">
                        <label for="categoria">Categoría:</label>
                        <?php dpc_display_categories_dropdown(); ?>
                    </div>
                    <div class="dpc-form-group">
                        <label for="descuento">Descuento (%):</label>
                        <input type="number" id="descuento" name="descuento" min="0" max="100" step="1" required>
                    </div>
                </div>
                <div class="dpc-form-row">
                    <div class="dpc-form-group">
                        <label for="badge">Insignia:</label>
                        <select name="badge" id="badge-select" required>
                            <?php
                            $insignias_dir = plugin_dir_path(__FILE__) . 'images/';
                            $insignias_url = plugin_dir_url(__FILE__) . 'images/';
                            $insignias = scandir($insignias_dir);
                            foreach ($insignias as $insignia) {
                                if ($insignia != '.' && $insignia != '..') {
                                    $insignia_name = pathinfo($insignia, PATHINFO_FILENAME);
                                    $insignia_url = $insignias_url . $insignia;
                                    echo '<option value="' . esc_attr($insignia_name) . '" data-img="' . esc_url($insignia_url) . '">' . esc_html($insignia_name) . '</option>';
                                }
                            }
                            ?>
                        </select>
                    </div>
                    <div class="dpc-form-group badge-preview-container">
                        <div id="badge-preview" class="badge-preview">
                            <img src="" alt="Vista previa de la insignia" />
                        </div>
                    </div>
                </div>
                <?php wp_nonce_field('dpc_descuento_nonce', 'dpc_nonce'); ?>
                <div class="dpc-form-group">
                    <input type="submit" value="Aplicar Descuento" class="button button-primary">
                </div>
            </form>
        </div>


    </div>
<?php }
?>