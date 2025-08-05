<?php

use PhpOffice\PhpWord\TemplateProcessor;
use PhpOffice\PhpWord\IOFactory;
use Dompdf\Dompdf;
use Dompdf\Options;

function limpiarUTF8($texto) {
    return mb_convert_encoding($texto, 'UTF-8', 'UTF-8');
}

function generarEncabezado($logoBase64, $fecha, $paginaNum) {
    return "
    <header>
    <table style='width:100%; border-collapse:collapse; border:1px solid #000; font-family:Arial, sans-serif; font-size:12px;'>
      <tr>
        <td style='border:1px solid #000; width:65%; text-align:center; padding:10px;'>
          <img src='{$logoBase64}' style='height:60px;' alt='Logo U de Colombia'>
        </td>
        <td style='border:1px solid #000; width:35%; vertical-align:top; padding:5px; font-size:11px;'>
          <div><strong>VERSIÓN:</strong> 4</div>
          <div><strong>Fecha:<br></strong> {$fecha}</div>
          <div><strong>CÓDIGO:</strong> F-VAF 011</div>
          <div><strong>Página:</strong> {$paginaNum} de 3</div>
        </td>
      </tr>
      <tr>
        <td colspan='2' style='border:1px solid #000; text-align:center; background-color:#e0e0e0; font-weight:bold; padding:5px;'>
          FORMATO PAGARÉ
        </td>
      </tr>
    </table>
    </header>
    ";
}


function generarPagarePDFBase64(array $datos) {
    // 1. Obtener consecutivo desde opciones de WordPress
    $consecutivo = get_option('simulador_consecutivo_pagare', 1);
    update_option('simulador_consecutivo_pagare', $consecutivo + 1);
    $consecutivoStr = str_pad($consecutivo, 5, '0', STR_PAD_LEFT);

    // 2. Cargar y llenar plantilla DOCX
    $templatePath = plugin_dir_path(__FILE__) . 'plantillas/formato-pagare-y-carta-de-instrucciones.docx';
    $tempDir = plugin_dir_path(__FILE__) . 'temp/';
    if (!file_exists($tempDir)) mkdir($tempDir, 0755, true);
    $tempDocx = $tempDir . "pagare{$consecutivoStr}.docx";

    // Cargar imagen y convertir a base64
    $logoPath = $datos['urlImg'];
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $logoData = file_get_contents($logoPath);
        $logoBase64 = 'data:image/jpeg;base64,' . base64_encode($logoData);
    }

    $fecha = date('d/m/Y');
    $encabezado1 = generarEncabezado($logoBase64, $fecha, 1);
    $encabezado2 = generarEncabezado($logoBase64, $fecha, 2);
    $encabezado3 = generarEncabezado($logoBase64, $fecha, 3);

    $footer = "
        <footer>
          <div style='font-size:10px; text-align:center; color:#333; line-height:1.4; margin-top:40px;'>
              Calle 56 Nº 41-147. Tel: 604 239 80 80. contacto@udecolombia.edu.co.<br>
              www.udecolombia.edu.co - Medellín, Antioquia - Colombia<br>
              Institución de Educación Superior sujeta a inspección y vigilancia por el Ministerio de Educación Nacional
          </div>
        </footer>
    ";

    $pagina1 =<<<HTML
    <p><strong>Pagaré Nº:</strong> {$consecutivoStr} &nbsp;&nbsp;&nbsp; <strong>Fecha:</strong> {$fecha} &nbsp;&nbsp;&nbsp; <strong>Valor:</strong> {$datos['valorMatricula']}</p>
    <p><strong>Interés mensual:</strong> {$datos['interesrate']}% &nbsp;&nbsp;&nbsp; <strong>Cuota:</strong> {$datos['cuotaMensual']}</p>
    <p><strong>Vencimiento primera cuota:</strong> {$fecha} &nbsp;&nbsp;&nbsp; <strong>Vencimiento final:</strong>
    {$datos['fechaFinal']}</p>
    <p><strong>DEUDOR:</strong> {$datos['nombre']}</p>
    <p><strong>DEUDOR SOLIDARIO:</strong> _______________________________________</p>
    <p><strong>ACREEDOR:</strong> Corporación Universitaria U de Colombia</p>
    <p><strong>Ciudad y dirección donde se efectuará el pago:</strong> __________________</p>
    <p style="text-align:justify">Nosotros, {$datos['nombre']} identificados como aparece al pie de nuestras firmas y
    obrando en nombre propio hacemos las siguientes declaraciones:</p>

    <p style="text-align:justify"><strong>PRIMERA. Objeto:</strong> Que por virtud del presente título valor, pagaremos
        incondicionalmente, en la ciudad de MEDELLÍN a la orden de la <strong>CORPORACIÓN UNIVERSITARIA U DE
        COLOMBIA</strong> (NIT 900.378.694) o a quien represente sus derechos, la suma de {$datos['valorMatriculaSTR']}
        ({$datos['valorMatricula']}) junto con los intereses señalados en la cláusula tercera del presente documento. </p>

    <p style="text-align:justify"><strong>SEGUNDA. Plazo:</strong> Que pagaremos la suma indicada en la cláusula anterior
        mediante instalamentos mensuales y en {$datos['plazoStr']} ({$datos['plazo']}) cuotas, correspondientes cada una a
        la cantidad de {$datos['cuotaMensualSTR']} ({$datos['cuotaMensual']}) más los intereses corrientes sobre el saldo,
        a partir del día {$datos['dia']} del mes {$datos['mesStr']} del año {$datos['year']}.</p>

    <p style="text-align:justify">
        <strong>TERCERA: Intereses:</strong> Que sobre la suma debida, se reconocerán intereses equivalentes al
        {$datos['interesrate']}% mensual, sobre el saldo de capital insoluto, los cuales se liquidarán y pagarán mes
        vencido, junto con la cuota mensual correspondiente al mes causado. En caso de mora, reconoceremos intereses
        moratorios de la tasa de usura legal vigente (%) mensual.
        <strong>PARÁGRAFO:</strong> En caso de que la tasa de los intereses corrientes y/o moratorios pactados, sobrepase
        los topes máximos permitidos por las disposiciones legales, dichas tasas se ajustarán mensualmente a los máximos
        legales.
    </p>

    <p style="text-align:justify">
        <strong>CUARTA. Cláusula aceleratoria:</strong> EL ACREEDOR podrá declarar insubsistente los plazos de esta
        obligación o de las cuotas pendientes de pago, estén o no vencidas y exigir el pago total e inmediato judicial o
        extrajudicialmente en los siguientes casos: i) Cuando EL DEUDOR incumpla cualquiera de las obligaciones derivadas
        del presente documento, así sea de manera parcial; ii) por muerte de EL DEUDOR; y iii) Cuando EL DEUDOR se declare
        en proceso de liquidación obligatoria o convoque a concurso de acreedores.
    </p>
    HTML;

    $pagina2 =<<<HTML
    <p style="text-align:justify">
    <strong>QUINTA:</strong> Autorizo a la Corporación Universitaria U DE COLOMBIA o a quien represente sus derechos u
    ostente en el futuro la calidad de acreedor a consultar, reportar, conservar, suministrar, solicitar o divulgar a
    DATACRÉDITO central de información y de riesgo, o a cualquier central de información de riesgo, toda la información
    referente al cumplimiento o incumplimiento de mis obligaciones crediticias, comerciales o de servicios, o de mis
    deberes legales de contenido patrimonial, y mis datos de ubicación y contacto, así como mi comportamiento comercial,
    relaciones comerciales, financieras y socioeconómicas en general que yo haya entregado o que consten en registros
    públicos, bases de datos públicas o documentos públicos. Lo anterior implica que el cumplimiento o incumplimiento de
    mis obligaciones se reflejará en la mencionada base de datos, en donde se consignan de manera completa todos los
    datos referentes a mi actual y pasado comportamiento en general frente al cumplimiento de mis obligaciones.
    </p>

    <p style="text-align:justify">
      <strong>SEXTA:</strong> Expresamente declaramos excusado el protesto del presente pagaré y los requerimientos
      judiciales o extrajudiciales para la constitución en mora.
    </p>

    <p style="text-align:justify">
      <strong>SÉPTIMA:</strong> En caso de que haya lugar al recaudo judicial o extrajudicial de la obligación contenida
      en el presente título valor, serán a nuestro cargo los gastos judiciales y/o los honorarios que se causen por tal
      razón.
    </p>
    <p style="text-align:justify">En constancia de lo anterior, se suscribe en Medellín, a los {$datos['diaStr']}
      ({$datos['dia']}) días del mes de {$datos['mesStr']} del año {$datos['year']}.</p>
    <br><br>
    <table style="width:100%; text-align:center;">
      <tr>
        <td>_____________________</td>
        <td>____________________</td>
        <td>____________________</td>
      </tr>
      <tr>
        <td>Nombre:</td>
        <td>Nombre: {$datos['nombre']}</td>
        <td>Nombre:</td>
      </tr>
      <tr>
        <td>C.C. No.:</td>
        <td>C.C. No. {$datos['cedula']}</td>
        <td>C.C. No.</td>
      </tr>
      <tr>
        <td>EL ACREEDOR</td>
        <td>DEUDOR</td>
        <td>DEUDOR SOLIDARIO</td>
      </tr>
    </table>
    HTML;

    $pagina3 =<<<HTML
    <p style="text-align:center"><strong>CARTA DE INSTRUCCIONES PARA DILIGENCIAR PAGARE No.
        {$consecutivoStr}</strong></p>
      <p style="text-align:justify">
        Nosotros, {$datos['nombre']}, identificado con cédula de ciudadanía número {$datos['cedula']}, y
        __________________________________ identificado con cédula de ciudadanía número _____________________, mayor de
        edad, por medio del presente escrito y de conformidad con lo establecido en el Art. 622 del Código de Comercio,
        autorizamos expresa, permanente e irrevocablemente a Corporación Universitaria U DE COLOMBIA, o a quien
        represente sus intereses o al tenedor legítimo de este instrumento para diligenciar y llenar los espacios en
        blanco en el presente título valor PAGARÉ No. {$datos['consecutivo']} de acuerdo con las siguientes
        instrucciones:
      </p>
      <div style="text-align:justify">
        <ol>
          <li>
            Los espacios en blanco relativos a la cuantía y fecha de vencimiento, podrán ser diligenciados sin necesidad
            de requerimiento alguno, por la ocurrencia de uno cualquiera de los siguientes eventos:
            <ol type="a">
              <li>Incumplimiento en el pago de una o más cuotas de capital o de cualquier otra clase de obligación
                existente con Corporación Universitaria U DE COLOMBIA o quien represente sus derechos o el tenedor de
                este título valor.</li>
              <li>Si cualquiera de los suscriptores llegare a ser investigado o vinculado por cualquier autoridad en
                razón de infracciones o delitos, especialmente en lo que se refiere al movimiento de capitales ilícitos,
                o fuere demandado judicialmente, o se nos embargaren bienes por cualquier clase de acción.</li>
              <li>En caso de fallecimiento, inhabilidad o incapacidad de uno o varios de quienes firmamos el presente
                documento.</li>
              <li>Cuando cualquiera de los otorgantes incumpla el pago de otra(s) obligación(es) adquirida(s) con
                Corporación Universitaria U DE COLOMBIA o quien represente sus derechos o el tenedor legítimo de este
                título.</li>
              <li>Si cualquiera de los otorgantes comete inexactitud en balances, informes, declaraciones o documentos
                que presente o hayamos presentado a Corporación Universitaria U DE COLOMBIA.</li>
              <li>La existencia de cualquier causal establecida en la ley, sus normas reglamentarias, o disposiciones de
                autoridad competente.</li>
            </ol>
          </li>
        </ol>
      </div>
      <p style="text-align:justify">
        2. La cuantía será igual al monto de todas las sumas que por cualquier concepto le estemos debiendo a
        Corporación Universitaria U DE COLOMBIA o a quien represente sus derechos o al tenedor legítimo de este
        instrumento, el día que sea diligenciado el pagaré.
      </p>
      <p style="text-align:justify">
        3. La fecha de vencimiento será el día en que se diligencien los espacios dejados en blanco en el pagaré.
      </p>
      <p style="text-align:justify">
        4. El impuesto de timbre a que haya lugar cuando el título sea llenado, correrá por cuenta nuestra y se
        incorporará, junto con las demás obligaciones, dentro de la suma pagada en el presente pagaré.
      </p>
      <br><br>
      <table style="width:100%; text-align:center;">
        <tr>
          <td>__________________________</td>
          <td>__________________________</td>
        </tr>
        <tr>
          <td>Nombre: {$datos['nombre']}</td>
          <td>Nombre:</td>
        </tr>
        <tr>
          <td>C.C. No. {$datos['cedula']}</td>
          <td>C.C. No.</td>
        </tr>
        <tr>
          <td>EL DEUDOR</td>
          <td>DEUDOR SOLIDARIO</td>
        </tr>
      </table>
    HTML;

    // 5. Ensamblar HTML completo con codificación UTF-8
    $html = "
    <!DOCTYPE html>
    <html lang='es'>
    <head>
        <meta charset='UTF-8'>
        <style>
            @page {
              margin: 200px 70px 100px ;
            }

            body {
              font-family: Arial, sans-serif;
              font-size: 13px;
              margin: 0;
              padding: 0;
            }

            header {
              position: fixed;
              top: -140px;
              left: 0;
              right: 0;
              height: 100px;
              text-align: center;
            }

            footer {
              position: fixed;
              bottom: -80px;
              left: 0;
              right: 0;
              height: 100px;
              font-size: 10px;
              text-align: center;
              line-height: 1.4;
            }

            .content {
              text-align: justify;
            }

            .page-break {
              page-break-after: always;
            }
        </style>
    </head>
    <body>
        <div class='content'>
        <div>
          {$encabezado1}
          {$footer}
          {$pagina1}
        </div>

        <div class='page-break'></div>

        <div>
          {$encabezado2}
          {$footer}
          {$pagina2}
        </div>

        <div class='page-break'></div>

        <div>
          {$encabezado3}
          {$footer}
          {$pagina3}
        </div>
      </div>
        
    </body>
    </html>
    ";

    // 6. Generar PDF con Dompdf
    $dompdf = new Dompdf();
    $dompdf->loadHtml($html, 'UTF-8');
    $dompdf->setPaper('A4');
    $dompdf->render();
    $pdfOutput = $dompdf->output();

    // 7. Guardar temporal y codificar en base64
    $tempPdf = $tempDir . "pagare{$consecutivoStr}.pdf";
    file_put_contents($tempPdf, $pdfOutput);
    $pdfBase64 = base64_encode($pdfOutput);
    
    // 8. Limpiar temporal .docx
    @unlink($tempDocx);

    return [
        'base64' => $pdfBase64,
        'filename' => "pagare{$consecutivoStr}.pdf",
        'consecutivo' => $consecutivoStr,
    ];
}