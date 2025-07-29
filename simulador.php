<?php
/**
 * Plugin Name: Simulador
 * Description: Simulador de crédito, plan de pagos, y navegación tipo SPA.
 * Version: 1.0.25
 * Author: Daniel Henao y Karen Velilla
 */

if (!defined('ABSPATH')) exit; // Evita el acceso directo al archivo

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php'; // Autoload de dependencias (PhpSpreadsheet)

use PhpOffice\PhpSpreadsheet\IOFactory;

$simulador_interestrate = []; // Variable global para la tasa de interés
/**
 * Carga los scripts y estilos necesarios para el simulador en el frontend.
 * Incluye estilos personalizados, jQuery, Inputmask y el script principal del simulador.
 * También carga el archivo Excel si existe y lo convierte a un arreglo para usar en JS.
 * Pasa la URL de AJAX y los datos del Excel al script principal.
 */
/**
 * Encola los scripts y estilos necesarios para el frontend del simulador.
 */
function simulador_enqueue_scripts() {
    global $simulador_interestrate;

    // Estilos personalizados del simulador
    wp_enqueue_style('simulador-css', plugin_dir_url(__FILE__) . 'assets/css/styles.css');
    // Fuente de iconos de Google
    wp_enqueue_style('material-symbols-outlined', 'https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200');
    // jQuery (incluido por defecto en WP)
    wp_enqueue_script('jquery');
    // Inputmask para máscaras de entrada en formularios
    wp_enqueue_script('inputmask', 'https://cdnjs.cloudflare.com/ajax/libs/jquery.inputmask/5.0.8/jquery.inputmask.min.js', [], null, true);
    // script del calculador de tasas de interés
    wp_enqueue_script('simulador-calculate', plugin_dir_url(__FILE__) . 'assets/js/calculateCreditSimulation.js', ['jquery'], null, true);
    // Script principal del simulador
    wp_enqueue_script('simulador-js', plugin_dir_url(__FILE__) . 'assets/js/main.js', ['jquery', 'simulador-calculate'], null, true);

    // Leer el archivo Excel si existe
    $data_array = [];
    // Obtiene el nombre del archivo Excel guardado en la base de datos
    $filename = get_option('simulador_excel_filename');
    // Ruta completa del archivo Excel en la carpeta uploads del plugin
    $excel_path = plugin_dir_path(__FILE__) . 'uploads/' . $filename;
    // Si hay un archivo Excel y existe, lo carga
    if ($filename && file_exists($excel_path)) {
        try {
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($excel_path);
            $sheet = $spreadsheet->getActiveSheet();
            $data_array = $sheet->toArray();
        } catch (Exception $e) {
            // Si hay error, el arreglo queda vacío
        }
    }

    // Pasa la URL de AJAX al JS
    wp_localize_script('simulador-js', 'simulador_ajax', [
        'ajaxurl' => admin_url('admin-ajax.php'),
        'excelData' => $data_array, // Aquí se pasa el Excel en formato JS
        'interestrate' => $simulador_interestrate // Pasa la tasa de interés
    ]);
}
add_action('wp_enqueue_scripts', 'simulador_enqueue_scripts');

/**
 * Shortcode [simulador] para mostrar el contenedor principal del simulador en el frontend.
 * Incluye un loader y un contenedor para vistas SPA.
 */

function simulador_shortcode($atts) {
    $atts = shortcode_atts([
        'interestrate' => ''
    ], $atts, 'simulador');

    // se crea variable global para la tasa de interés
    global $simulador_interestrate;
    $simulador_interestrate = $atts['interestrate'];

    ob_start(); ?>
    <div class="content-all-simulator">
        <div id="loading" style="display:none;">Cargando...</div>
        <div id="view-container"></div>
    </div>
    <?php return ob_get_clean();
}
add_shortcode('simulador', 'simulador_shortcode');

/**
 * Registra las acciones AJAX para cargar vistas dinámicamente en el simulador (SPA).
 */
add_action('wp_ajax_nopriv_simulador_load_view', 'simulador_load_view');
add_action('wp_ajax_simulador_load_view', 'simulador_load_view');

/**
 * Callback para AJAX: carga la vista solicitada si es válida.
 */
function simulador_load_view() {
    $view = sanitize_text_field($_POST['vista']);
    $allowed_views = ['details', 'form-program', 'form-student'];

    if (in_array($view, $allowed_views)) {
        include plugin_dir_path(__FILE__) . "templates/{$view}.php";
    } else {
        echo "Vista no válida.";
    }
    wp_die();
}

/**
 * Agrega un menú al administrador de WordPress para cargar el Excel del simulador.
 */
add_action('admin_menu', 'simulador_ucol_admin_menu');

function simulador_ucol_admin_menu() {
    add_menu_page(
        'Cargar Excel',         // Título de la página
        'Simulador',            // Nombre en el menú
        'manage_options',       // Capacidad requerida
        'simulador_excel',      // Slug
        'simulador_admin_page', // Callback de la página
        'dashicons-upload',     // Icono
        20                      // Posición
    );
}

/**
 * Página de administración para subir y previsualizar el archivo Excel.
 * Muestra el formulario de carga y la tabla de vista previa si hay archivo.
 */
function simulador_admin_page() {
    ?>
    <div class="wrap">
        <h1>Subir archivo Excel del Simulador</h1>
        <?php if (isset($_GET['success'])): ?>
            <div class="notice notice-success"><p>Archivo subido correctamente.</p></div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('subir_excel_simulador', 'simulador_excel_nonce'); ?>
            <input type="file" name="simulador_excel" accept=".xlsx" required>
            <br><br>
            <input type="submit" class="button button-primary" value="Subir Excel">
        </form>
    </div>

    <?php
        // Obtiene el nombre del archivo subido o el último guardado en la base de datos
        $filename = isset($_FILES['simulador_excel']['name']) && !empty($_FILES['simulador_excel']['name'])
            ? basename($_FILES['simulador_excel']['name'])
            : get_option('simulador_excel_filename');

        $excel_path = plugin_dir_path(__FILE__) . 'uploads/' . $filename;

        if ($filename && file_exists($excel_path)) {
            echo '<h2>Vista previa del archivo: ' . esc_html($filename) . '</h2>';

            try {
                // Carga y muestra el contenido del Excel como tabla
                $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($excel_path);
                $sheet = $spreadsheet->getActiveSheet();
                $data = $sheet->toArray();

                echo '<table class="widefat striped">';
                foreach ($data as $i => $row) {
                    echo '<tr>';
                    foreach ($row as $cell) {
                        if ($i === 0) {
                            echo '<th>' . esc_html($cell) . '</th>';
                        } else {
                            echo '<td>' . esc_html($cell) . '</td>';
                        }
                    }
                    echo '</tr>';
                }
                echo '</table>';
            } catch (Exception $e) {
                echo '<div class="notice notice-error"><p>Error al leer el archivo Excel: ' . esc_html($e->getMessage()) . '</p></div>';
            }
        } else {
            echo '<p>No hay ningún archivo cargado aún.</p>';
        }
    ?>

    <?php
    
}

/**
 * Hook para procesar la subida del archivo Excel desde el formulario de administración.
 * Guarda el archivo en la carpeta uploads del plugin y almacena el nombre en la base de datos.
 */
add_action('admin_init', function () {
    if (
        isset($_FILES['simulador_excel']) &&
        isset($_POST['simulador_excel_nonce']) &&
        wp_verify_nonce($_POST['simulador_excel_nonce'], 'subir_excel_simulador')
    ) {
        // Carpeta donde se guardan los archivos subidos
        $upload_dir = plugin_dir_path(__FILE__) . 'uploads/';
        if (!file_exists($upload_dir)) mkdir($upload_dir);

        // Obtiene el nombre original del archivo subido
        $original_filename = basename($_FILES['simulador_excel']['name']);

        // Ruta completa de destino
        $destination = $upload_dir . $original_filename;

        // Mueve el archivo subido a la carpeta destino
        if (move_uploaded_file($_FILES['simulador_excel']['tmp_name'], $destination)) {
            // Guarda el nombre del archivo en la base de datos para reutilizarlo
            update_option('simulador_excel_filename', $original_filename);

            // Redirige con mensaje de éxito
            wp_redirect(admin_url('admin.php?page=simulador_excel&success=1'));
            exit;
        } else {
            echo '<div class="notice notice-error"><p>Error al subir el archivo.</p></div>';
        }
    }
});

