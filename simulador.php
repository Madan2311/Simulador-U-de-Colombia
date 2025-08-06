<?php
/**
 * Plugin Name: Simulador
 * Description: Simulador de crédito, plan de pagos, y navegación tipo SPA.
 * Mode de uso: [simulador interestrate="2"]
 * Version: 1.0.25
 * 
 */

if (!defined('ABSPATH'))
    exit; // Evita el acceso directo al archivo

require_once plugin_dir_path(__FILE__) . 'vendor/autoload.php'; // Autoload de dependencias (PhpSpreadsheet)
require_once plugin_dir_path(__FILE__) . 'pdFiles/pagare.php'; // Archivo que contiene la función para generar el pagaré

use PhpOffice\PhpSpreadsheet\IOFactory;

date_default_timezone_set('America/Bogota');

/**
 * Carga los scripts y estilos necesarios para el simulador en el frontend.
 * Incluye estilos personalizados, jQuery, Inputmask y el script principal del simulador.
 * También carga el archivo Excel si existe y lo convierte a un arreglo para usar en JS.
 * Pasa la URL de AJAX y los datos del Excel al script principal.
 */
/**
 * Encola los scripts y estilos necesarios para el frontend del simulador.
 */
function simulador_enqueue_scripts()
{

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
    ]);
}
add_action('wp_enqueue_scripts', 'simulador_enqueue_scripts');

/**
 * simulador_login_firma
 */

add_action('wp_ajax_simulador_login_firma', 'simulador_login_firma');
add_action('wp_ajax_nopriv_simulador_login_firma', 'simulador_login_firma');

function simulador_login_firma()
{
    $wsdl = 'https://test-circuitodefirmado.andesscd.com.co/WS/FE/wsdl.php?wsdl';

    try {

        // Credenciales WS-Security (debes poner las reales si cambian dinámicamente)
        $username = "ximena.garcía";
        $password = "SXdPaUEzRzhqR2pDcGtTZ0J3ZXllR21vcDFpUkxsWmxobHR1Vlo5NXpHaFNISlNOVks1eVdwYkJBd2Z4NmNDTEQ5RU9oWE8vMU1aU0pKQ3ZUUzVCdUkza2VCUT0=";

        // --- Generar dinámicamente el Created y el Nonce ---
        $created = gmdate('Y-m-d\TH:i:s.\0\0\0\Z'); // ISO8601 en UTC
        $nonceRaw = openssl_random_pseudo_bytes(16);
        $nonceBase64 = base64_encode($nonceRaw);

        // WS-Security Header (con formato XML manual)
        $headerXml = '
            <wsse:Security 
                xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd" 
                xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
                <wsse:UsernameToken wsu:Id="UsernameToken-1">
                    <wsse:Username>' . htmlspecialchars($username) . '</wsse:Username>
                    <wsse:Password 
                        Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . htmlspecialchars($password) . '</wsse:Password>
                    <wsse:Nonce 
                        EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">' . $nonceBase64 . '</wsse:Nonce>
                    <wsu:Created>' . $created . '</wsu:Created>
                </wsse:UsernameToken>
            </wsse:Security>
        ';

        $soapVarHeader = new SoapVar($headerXml, XSD_ANYXML);
        $soapHeader = new SoapHeader(
            'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd',
            'Security',
            $soapVarHeader,
            false
        );

        // Crear cliente SOAP con WS-Security
        $client = new SoapClient($wsdl, [
            'trace' => true,
            'exceptions' => true,
            'cache_wsdl' => WSDL_CACHE_NONE
        ]);


        // Parámetro requerido por el método LoginRequest
        $params = [
            'identificador' => 'prueba'
        ];

        // Llamada al método Login
        $response = $client->__soapCall("login", [$params], null, $soapHeader);

        return [
            'estado' => $response->estado ?? null,
            'mensaje' => $response->mensaje ?? 'Sin mensaje'
        ];
    } catch (SoapFault $e) {
        echo "Error: " . $e->getMessage();
    }
}

/**
 * Simulador certificado
 */

add_action('wp_ajax_simulador_certificado_handler', 'solicitud_certificado_handler');
add_action('wp_ajax_nopriv_simulador_certificado_handler', 'solicitud_certificado_handler');

function solicitud_certificado_handler()
{
    $primerNombre = $_POST['primerNombre'] ?? '';
    $segundoNombre = $_POST['segundoNombre'] ?? '';
    $primerApellido = $_POST['primerApellido'] ?? '';
    $segundoApellido = $_POST['segundoApellido'] ?? '';
    $documento = $_POST['documento'] ?? '';
    $correo = $_POST['correo'] ?? '';
    $celular = $_POST['celular'] ?? '';

    // Aquí llamas a la función que ejecuta el SOAP de SolicitudCertificado
    $resultado = simulador_certificado([
        'tipoDocumento' => 1,
        'primerNombre' => $primerNombre,
        'segundoNombre' => $segundoNombre,
        'primerApellido' => $primerApellido,
        'segundoApellido' => $segundoApellido,
        'documento' => $documento,
        'correo' => $correo,
        'celular' => $celular,
        'notificacion' => 3
    ]);

    if ($resultado->estado === 0 || $resultado->estado === '0') {
        wp_send_json_success(['estado' => $resultado->estado, 'mensaje' => 'Certificado solicitado exitosamente.']);
    } else {
        wp_send_json_error(['mensaje' => 'Error al solicitar el certificado.']);
    }
}

function simulador_certificado($data)
{
    $wsdl = "https://test-circuitodefirmado.andesscd.com.co/WS/FE/wsdl.php?wsdl";
    $username = 'ximena.garcía';
    $password = 'SXdPaUEzRzhqR2pDcGtTZ0J3ZXllR21vcDFpUkxsWmxobHR1Vlo5NXpHaFNISlNOVks1eVdwYkJBd2Z4NmNDTEQ5RU9oWE8vMU1aU0pKQ3ZUUzVCdUkza2VCUT0=';

    // Timestamp y Nonce
    $created = gmdate('Y-m-d\TH:i:s\Z');
    $nonce = base64_encode(random_bytes(16));

    // WS-Security Header
    $headerXml = '
    <wsse:Security xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"
                   xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
        <wsse:UsernameToken wsu:Id="UsernameToken-' . uniqid() . '">
            <wsse:Username>' . $username . '</wsse:Username>
            <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . $password . '</wsse:Password>
            <wsse:Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">' . $nonce . '</wsse:Nonce>
            <wsu:Created>' . $created . '</wsu:Created>
        </wsse:UsernameToken>
    </wsse:Security>';

    // Crear el SoapHeader
    $soapVar = new SoapVar($headerXml, XSD_ANYXML);
    $soapHeader = new SoapHeader(
        'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd',
        'Security',
        $soapVar,
        true
    );

    $client = new SoapClient($wsdl, ['trace' => 1]);
    $client->__setSoapHeaders([$soapHeader]);

    // Estructura esperada para el request
    $params = [
        'IdTipoDocumento' => $data['tipoDocumento'],
        'Documento' => $data['documento'],
        'PrimerNombre' => $data['primerNombre'],
        'SegundoNombre' => $data['segundoNombre'],
        'PrimerApellido' => $data['primerApellido'],
        'SegundoApellido' => $data['segundoApellido'],
        'Correo' => $data['correo'],
        'Celular' => $data['celular'],
        'Notificacion' => $data['notificacion']
    ];

    try {
        $response = $client->__soapCall("SolicitudCertificado", [$params]);
        return $response;
    } catch (SoapFault $e) {
        return ['error' => $e->getMessage(), 'trace' => $client->__getLastRequest()];
    }
}

/**
 * Shortcode [simulador] para mostrar el contenedor principal del simulador en el frontend.
 * Incluye un loader y un contenedor para vistas SPA.
 */

function simulador_shortcode($atts)
{
    $atts = shortcode_atts([
        'interestrate' => ''
    ], $atts, 'simulador');
    
    ob_start(); ?>
    <div class="content-all-simulator simulador-plugin" data-interestrate="<?php echo esc_attr($atts['interestrate']); ?>">
        <div id="loading" style="display:none;">
            <div class="circle-loader">
                <div class="circle circle-1"></div>
                <div class="circle circle-2"></div>
                <div class="circle circle-3"></div>
                <div class="circle circle-4"></div>
            </div>
        </div>
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
function simulador_load_view()
{
    $view = sanitize_text_field($_POST['vista']);
    $allowed_views = ['details', 'form-program', 'form-student', 'messages', 'confirm-student'];

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

function simulador_ucol_admin_menu()
{
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
function simulador_admin_page()
{
    ?>
    <div class="wrap">
        <h1>Subir archivo Excel del Simulador</h1>
        <?php if (isset($_GET['success'])): ?>
            <div class="notice notice-success">
                <p>Archivo subido correctamente.</p>
            </div>
        <?php endif; ?>
        <form method="post" enctype="multipart/form-data">
            <?php wp_nonce_field('subir_excel_simulador', 'simulador_excel_nonce'); ?>
            <input type="file" name="simulador_excel" accept=".xlsx" required>
            <br></br>
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
        if (!file_exists($upload_dir))
            mkdir($upload_dir);

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

/**
 * envio del formulario de simulación al correo electrónico del administrador.
 * Recibe los datos del formulario, los valida y envía un correo al administrador.
 */

add_action('wp_ajax_nopriv_simulador_send_form', 'simulador_send_form');
add_action('wp_ajax_simulador_send_form', 'simulador_send_form');

function numero_a_letras($numero)
{
    $f = new NumberFormatter("es", NumberFormatter::SPELLOUT);
    return $f->format($numero);
}

function fecha_mes_en_letras($mesNumero)
{
    $meses = [
        1 => 'ENERO',
        2 => 'FEBRERO',
        3 => 'MARZO',
        4 => 'ABRIL',
        5 => 'MAYO',
        6 => 'JUNIO',
        7 => 'JULIO',
        8 => 'AGOSTO',
        9 => 'SEPTIEMBRE',
        10 => 'OCTUBRE',
        11 => 'NOVIEMBRE',
        12 => 'DICIEMBRE'
    ];
    return $meses[intval($mesNumero)] ?? '';
}

function firma_documento_ws($data)
{
    $wsdl = 'https://test-circuitodefirmado.andesscd.com.co/WS/FE/wsdl.php?wsdl';
    $username = 'ximena.garcía';
    $password = 'SXdPaUEzRzhqR2pDcGtTZ0J3ZXllR21vcDFpUkxsWmxobHR1Vlo5NXpHaFNISlNOVks1eVdwYkJBd2Z4NmNDTEQ5RU9oWE8vMU1aU0pKQ3ZUUzVCdUkza2VCUT0=';

    // WS-Security dinámico
    $nonce = base64_encode(random_bytes(16));
    $created = gmdate('Y-m-d\TH:i:s\Z');

    $headers = '
        <wsse:Security
            xmlns:wsse="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd"
            xmlns:wsu="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-utility-1.0.xsd">
            <wsse:UsernameToken>
                <wsse:Username>' . $username . '</wsse:Username>
                <wsse:Password Type="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-username-token-profile-1.0#PasswordText">' . $password . '</wsse:Password>
                <wsse:Nonce EncodingType="http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-soap-message-security-1.0#Base64Binary">' . $nonce . '</wsse:Nonce>
                <wsu:Created>' . $created . '</wsu:Created>
            </wsse:UsernameToken>
        </wsse:Security>
    ';
    $header = new SoapHeader(
        'http://docs.oasis-open.org/wss/2004/01/oasis-200401-wss-wssecurity-secext-1.0.xsd',
        'Security',
        new SoapVar($headers, XSD_ANYXML),
        true
    );

    $client = new SoapClient($wsdl, [
        'trace' => true,
        'exceptions' => true,
        'cache_wsdl' => WSDL_CACHE_NONE
    ]);

    $client->__setSoapHeaders($header);

    try {
        $response = $client->__soapCall("FirmaDocumento", [$data]);
        return $response;
    } catch (SoapFault $e) {
        error_log('soap' . print_r($e->getMessage()));
    }
}

function limpiar_archivos_temp()
{
    $temp_dir = plugin_dir_path(__FILE__) . 'pdFiles/temp/';

    // Asegurarse de que la carpeta existe
    if (!is_dir($temp_dir))
        return;

    // Buscar todos los archivos dentro de la carpeta temp/
    $archivos = glob($temp_dir . '*');

    foreach ($archivos as $archivo) {
        if (is_file($archivo)) {
            unlink($archivo); // Eliminar archivo
        }
    }
}

function simulador_send_form()
{
    if (!isset($_POST['simulador_nonce']) || !wp_verify_nonce($_POST['simulador_nonce'], 'simulador_send_form')) {
        wp_send_json_error('Nonce inválido', 403);
    }

    $name = sanitize_text_field($_POST['name'] ?? '');
    $id = sanitize_text_field($_POST['id'] ?? '');
    $celPhone = sanitize_text_field($_POST['celPhone'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');

    $programs = sanitize_text_field($_POST['programs'] ?? '');
    $days = sanitize_text_field($_POST['days'] ?? '');
    $mode = sanitize_text_field($_POST['mode'] ?? '');
    $typeOfStudent = sanitize_text_field($_POST['typeOfStudent'] ?? '');
    $term = sanitize_text_field($_POST['term'] ?? '');
    $scholarshipOrigin = sanitize_text_field($_POST['scholarshipOrigin'] ?? '');

    $program_detail_html = wp_kses_post($_POST['program_detail_html'] ?? '');
    $payment_plan_html = wp_kses_post($_POST['payment_plan_html'] ?? '');

    $dataPlan = isset($_POST['dataPlan']) ? json_decode(stripslashes($_POST['dataPlan']), true) : [];
    $fechaFinal = sanitize_text_field($_POST['fechaFinal'] ?? '');
    $interestrate = isset($_POST['interestrate']) ? floatval($_POST['interestrate']) : 0;

    // Convertir $fechaFinal a formato dd/mm/yyyy
    $fechaFinalFormateada = date("d/m/Y", strtotime($fechaFinal));
    $codeSoap = sanitize_text_field($_POST['codeSoap'] ?? '');



    if (empty($name) || empty($id) || empty($email) || !is_email($email)) {
        wp_send_json_error('Datos inválidos');
    }

    $tablaStyle = 'border-collapse: collapse; width: 100%; font-family: Arial, sans-serif; font-size: 14px; margin-bottom: 30px;';
    $thStyle = 'background-color: #f4f6f9; border: 1px solid #dfe3e8; padding: 10px; text-align: left; font-weight: bold;';
    $tdStyle = 'border: 1px solid #dfe3e8; padding: 10px;';
    $cardStyle = 'border: 1px solid #dfe3e8; border-radius: 8px; background-color: #ffffff; padding: 20px; margin-bottom: 20px;';
    $titleStyle = 'font-family: Arial, sans-serif; font-size: 18px; margin-bottom: 10px; color: #2c3e50;';

    // Contenido HTML del mensaje
    // 🔹 Información personal (con "tarjeta")
    $message = "<div style='$cardStyle'>";
    $message .= "<div style='$titleStyle'>📄 Información del Estudiante</div>";
    $message .= "<table style='$tablaStyle'>";
    $message .= "<tr><td style='$thStyle'>Nombre</td><td style='$tdStyle'>{$name}</td></tr>";
    $message .= "<tr><td style='$thStyle'>Cédula</td><td style='$tdStyle'>{$id}</td></tr>";
    $message .= "<tr><td style='$thStyle'>Celular</td><td style='$tdStyle'>{$celPhone}</td></tr>";
    $message .= "<tr><td style='$thStyle'>Email</td><td style='$tdStyle'>{$email}</td></tr>";
    $message .= "<tr><td style='$thStyle'>Programa</td><td style='$tdStyle'>{$programs}</td></tr>";
    $message .= "<tr><td style='$thStyle'>Jornada</td><td style='$tdStyle'>{$days}</td></tr>";
    $message .= "<tr><td style='$thStyle'>Modalidad</td><td style='$tdStyle'>{$mode}</td></tr>";
    $message .= "<tr><td style='$thStyle'>Tipo de estudiante</td><td style='$tdStyle'>{$typeOfStudent}</td></tr>";
    $message .= "<tr><td style='$thStyle'>Plazo</td><td style='$tdStyle'>{$term} meses</td></tr>";
    $message .= "<tr><td style='$thStyle'>Origen de beca o descuento especial</td><td style='$tdStyle'>{$scholarshipOrigin}</td></tr>";
    $message .= "</table></div>";

    // 🔹 Detalle del Programa
    $styledProgramDetail = "<div style='$cardStyle'>";
    $styledProgramDetail .= "<div style='$titleStyle'>📊 Detalle del Programa</div>";
    $styledProgramDetail .= "<table style='$tablaStyle'>";
    $styledProgramDetail .= "<thead><tr>";
    $styledProgramDetail .= "<th style='$thStyle'>Valor matrícula</th>";
    $styledProgramDetail .= "<th style='$thStyle'>Valor neto matrícula</th>";
    $styledProgramDetail .= "<th style='$thStyle'>Fecha</th>";
    $styledProgramDetail .= "<th style='$thStyle'>Monto del crédito</th>";
    $styledProgramDetail .= "<th style='$thStyle'>Cuota inicial</th>";
    $styledProgramDetail .= "<th style='$thStyle'>Administración</th>";
    $styledProgramDetail .= "<th style='$thStyle'>Cuota mensual</th>";
    $styledProgramDetail .= "</tr></thead><tbody>";
    $styledProgramDetail .= str_replace('<td>', "<td style='$tdStyle'>", $program_detail_html);
    $styledProgramDetail .= "</tbody></table></div>";

    // 🔹 Plan de pagos
    $styledPaymentPlan = "<div style='$cardStyle'>";
    $styledPaymentPlan .= "<div style='$titleStyle'>💰 Plan de Pagos</div>";
    $styledPaymentPlan .= "<table style='$tablaStyle'>";
    $styledPaymentPlan .= "<thead><tr>";
    $styledPaymentPlan .= "<th style='$thStyle'>#</th>";
    $styledPaymentPlan .= "<th style='$thStyle'>Fecha</th>";
    $styledPaymentPlan .= "<th style='$thStyle'>Capital</th>";
    $styledPaymentPlan .= "<th style='$thStyle'>Interés</th>";
    $styledPaymentPlan .= "<th style='$thStyle'>Cuota</th>";
    $styledPaymentPlan .= "<th style='$thStyle'>Saldo</th>";
    $styledPaymentPlan .= "</tr></thead><tbody>";
    $styledPaymentPlan .= str_replace('<td>', "<td style='$tdStyle'>", $payment_plan_html);
    $styledPaymentPlan .= "</tbody></table></div>";

    // Unir todo
    $message .= $styledProgramDetail;
    $message .= $styledPaymentPlan;

    $to = 'analistacontable@udecolombia.edu.co'; // Correo del administrador
    $subject = '💰 Simulación de Crédito';
    $headers = [
        'Content-Type: text/html; charset=UTF-8',
        "From: 💲 Simulación de Crédito para <{$name}> 💲"
    ];

    // 1. Capturar datos del formulario
    $nombre = sanitize_text_field($_POST['name']);
    $cedula = sanitize_text_field($_POST['id']);
    $plazo = floatval($_POST['term']);

    // 2. Convertir plazo a letras
    $plazoStr = numero_a_letras($plazo);

    // 3. Obtener cuota mensual desde tabla HTML
    $cuota = floatval(str_replace('.', '', $dataPlan['cuotaMensual']));
    $cuotaStr = strtoupper(numero_a_letras($cuota)) . " PESOS";

    // 4. Obtener valor matrícula desde tabla HTML
    $valor = floatval(str_replace('.', '', $dataPlan['valorMatricula']));
    $valorStr = strtoupper(numero_a_letras($valor)) . " PESOS";

    // 6. Día, mes, año actual
    $dia = date('j');
    $diaStr = strtoupper(numero_a_letras($dia));
    $mesStr = fecha_mes_en_letras(date('n'));
    $year = date('Y');

    // 7. Generar pagaré en PDF (base64)
    $pagare = generarPagarePDFBase64([
        'valorMatricula' => '$' . $dataPlan['valorMatricula'],
        'valorMatriculaSTR' => $valorStr,
        'cuotaMensual' => '$' . $dataPlan['cuotaMensual'],
        'cuotaMensualSTR' => $cuotaStr,
        'interesrate' => $interestrate,
        'plazo' => $plazo,
        'plazoStr' => $plazoStr,
        'fechaFinal' => $fechaFinalFormateada,
        'dia' => $dia,
        'diaStr' => $diaStr,
        'mesStr' => $mesStr,
        'year' => $year,
        'nombre' => $nombre,
        'cedula' => $cedula,
        'urlImg' => plugin_dir_path(__FILE__) . 'assets/img/logo-u-de-colombia.png',
    ]);

    // firma de documento
    $data = [
        'IdTipoDocumento' => '1',
        'Documento' => $id,
        'CodigoOTP' => $codeSoap,
        'Adjunto' => $pagare['base64'],  // PDF en base64
        'NombreAdjunto' => 'pagare' . $pagare['consecutivo'],
        'FirmaVisible' => '1',
        'Coordenadas' => '200,60,200,60'
    ];
    $response_firma = firma_documento_ws($data);

    $upload_dir = wp_upload_dir();
    $temp_dir = plugin_dir_path(__FILE__) . 'pdFiles/temp/';
    $pdf_path = $temp_dir . 'pagare' . $pagare['consecutivo'] . '.pdf';

    if ($response_firma->estado === 0 || $response_firma->estado === '0') {
        // Guarda el PDF firmado decodificando el base64 de la respuesta
        file_put_contents($pdf_path, base64_decode($response_firma->mensaje));
    } else {
        wp_send_json_error('Ha ocurrido un error al autenticar, intente nuevamente más tarde');
        return;
    }

    // Adjuntos
    $attachments = [];
    $attachments[] = $pdf_path; // Pagaré en PDF

    $temp_dir = plugin_dir_path(__FILE__) . 'pdFiles/temp/';
    $archivos_temp = [
        'employmentLetter_temp',
        'paymentStubs_temp',
        'document_temp'
    ];

    foreach ($archivos_temp as $campo) {
        if (!empty($_POST[$campo])) {
            $ruta = $temp_dir . basename($_POST[$campo]);
            if (file_exists($ruta)) {
                $attachments[] = $ruta;
            } else {
                error_log("No existe el archivo: $ruta");
            }
        }
    }

    // Enviar el correo
    $sent = wp_mail($to, $subject, $message, $headers, $attachments);

    if ($sent) {
        limpiar_archivos_temp();
        wp_send_json_success('Solicitud enviada correctamente');
    } else {
        limpiar_archivos_temp();
        wp_send_json_error('Error al enviar el correo');
    }

    wp_die();
}

add_action('wp_ajax_limpiar_temp', 'limpiar_archivos_temp_handler');
add_action('wp_ajax_nopriv_limpiar_temp', 'limpiar_archivos_temp_handler');

function limpiar_archivos_temp_handler()
{
    limpiar_archivos_temp(); // Pásalos a tu función

    wp_send_json_success(['message' => 'Archivos temporales eliminados.']);
}


add_action('wp_ajax_simulador_upload_temp', 'simulador_upload_temp');
add_action('wp_ajax_nopriv_simulador_upload_temp', 'simulador_upload_temp');

function simulador_upload_temp()
{
    if (!isset($_FILES['file'])) {
        wp_send_json_error(['message' => 'No se recibió archivo.']);
    }

    $id = sanitize_text_field($_POST['field'] ?? '');
    if (empty($id)) {
        wp_send_json_error(['message' => 'ID de campo no válido.']);
    }

    $file = $_FILES['file'];
    $upload_dir = plugin_dir_path(__FILE__) . 'pdFiles/temp/';
    if (!file_exists($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    // Reemplaza espacios y caracteres especiales en el nombre original
    $clean_filename = sanitize_file_name($file['name']);
    $filename = $id . '_' . $clean_filename;
    $filepath = $upload_dir . $filename;

    // Elimina archivo anterior si ya existe con el mismo nombre
    if (file_exists($filepath)) {
        unlink($filepath);
    }

    if (move_uploaded_file($file['tmp_name'], $filepath)) {
        // Ruta relativa que se puede guardar o enviar por AJAX
        $relative_path = 'pdFiles/temp/' . $filename;
        wp_send_json_success(['filepath' => $relative_path]);
    } else {
        wp_send_json_error(['message' => 'No se pudo guardar el archivo.']);
    }
}