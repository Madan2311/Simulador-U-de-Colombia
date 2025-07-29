(function ($) {
  $(document).ready(function () {
    // Cargar la vista inicial
    loadView('details');
    sessionStorage.clear(); // limpia el storage al cargar el sitio

    // ------------------- //
    // Función para cargar vistas por AJAX
    // Esta función sirve como un init
    function loadView(viewPath, callback) {
      $.ajax({
        url: simulador_ajax.ajaxurl,
        method: 'POST',
        data: {
          action: 'simulador_load_view',
          vista: viewPath
        },
        success: function (data) {
          $('#view-container').html(data).fadeIn(300, function () {
            bindEvents();                   // Reasigna eventos
            validateForm();                 // Lanza validación general
            initFileInputs();               // Estilo de los tipo file
            syncModalityWithJornada();      // validación de la jornada
            restoreFormData();              // Restaurar los datos de los campos

            if (viewPath.includes('form-program')) {
              populateTermOptions();        // valida el campo plazo
              toggleScholarshipFields();    // valida el campo beca
            }

            if (callback) callback();
          });
        },
        error: function () {
          $('#view-container').html('<p>Error al cargar la vista.</p>');
          if (callback) callback();
        }
      });
    }

    // ------------------- //
    // Eventos de navegación entre vistas
    // Control de los botones de continuar y anterior
    function bindEvents() {
      $(document).on('click', '#continue', () => switchView('form-program'));
      $(document).on('click', '#continue-program', () => switchView('form-student'));
      $(document).on('click', '#previous-program', () => switchView('details'));
      $(document).on('click', '#previous-student', () => switchView('form-program'))
    }

    // Cambio de vista con efecto de carga
    function switchView(path) {
      $('#loading').show();
      $('#view-container').fadeOut(300, () => {
        loadView(path, () => $('#loading').hide());
      });
    }

    // ------------------- //
    // Validaciones generales de formularios
    function validateForm() {
      // validación de los campos de las vistas
      validateRequiredFields();
      validateStudentFields();
    }

    function validateRequiredFields() {
      let isValid = true;

      $('#view-container [required]').each(function () {
        const $field = $(this);
        const value = $field.val();

        if (!value || (value.trim && value.trim() === '')) isValid = false;

        if ($field.attr('type') === 'file' && $field[0].files.length === 0) isValid = false;
        if ($field.attr('type') === 'email' && value && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) isValid = false;
      });

      $('#payment-plan').prop('disabled', !isValid);
    }

    function validateStudentFields() {
      let isValid = true;

      $('#view-container input[required]').each(function () {
        const $field = $(this);
        const val = $field.val();
        const isValidCell = $('#celPhone').inputmask("isComplete");

        if (!val || !isValidCell || (val.trim && val.trim() === '')) isValid = false;

        if ($field.attr('type') === 'file' && $field[0].files.length === 0) isValid = false;
      });

      // Email simple
      const emailVal = $('#email').val();
      if (emailVal && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailVal)) isValid = false;

      // Validar celular con máscara
      if (!$('#celPhone').inputmask('isComplete')) isValid = false;

      // Si hay tipo de beca seleccionado, el archivo es requerido
      const tieneBeca = $('#typeOfScholarship').is(':checked');
      const archivoBeca = $('#proofOfScholarship')[0]?.files.length || 0;

      if (tieneBeca && archivoBeca === 0) {
        isValid = false;
      }

      $('#send').prop('disabled', !isValid);
    }

    // ------------------- //
    // Máscara para el campo celular
    $(document).on('focus', '#celPhone', function () {
      $(this).inputmask('999 999 9999', {
        placeholder: '___ ___ ____',
        showMaskOnHover: false
      });
    });

    // ------------------- //
    // validación de Jornada
    $(document).on('change', '#days', function () {
      syncModalityWithJornada();
      validateForm && validateForm();
    });

    // ------------------- //
    // validación de tipo de beca
    $(document).on('change', '#typeOfScholarship', function () {
      toggleScholarshipFields();
      validateForm && validateForm();
    });

    // ------------------- //
    // ver el plan teniendo encuenta los inputs y la creación de la tabla
    $(document).on('click', '#payment-plan', function () {
      const program = $('#programs').val();
      const day = $('#days').val();
      const modality = $('#mode').val();
      const typeOfStudent = $('#typeOfStudent').val();
      const term = $('#term').val();
      const percentage = (parseInt($('#percentage').val()) || 0) / 100;

      const date = new Date();
      const mapData = mapExcelData(simulador_ajax.excelData, program, day, modality, typeOfStudent);

      const dateDiscountOne = mapData[0][7] || '';
      const dateDiscountTwo = mapData[0][10] || '';
      let valueProgramDiscount = 0;

      dateDiscountOne.split('/').reverse().join('-'); // formatear fecha a YYYY-MM-DD
      dateDiscountTwo.split('/').reverse().join('-'); // formatear fecha a YYYY-MM-DD

      if (date <= new Date(dateDiscountOne)) {
        const discountReplace = mapData[0][8].replace(/[ ,$]/g, "");
        valueProgramDiscount = parseFloat(discountReplace) || 0;
      } else if (date <= new Date(dateDiscountTwo)) {
        const discountReplace = mapData[0][11].replace(/[ ,$]/g, "");
        valueProgramDiscount = parseFloat(discountReplace) || 0;
      } else {
        const discountReplace = mapData[0][13].replace(/[ ,$]/g, "");
        valueProgramDiscount = parseFloat(discountReplace) || 0;
      }

      // valorMatricula=valueProgramDiscount
      // descuentoBeca=percentage
      // tasaInteres=simulador_ajax.interestrate
      // plazoMeses=term
      // fechaInicioStr=date

      // Función para calcular la simulación de crédito
      const resultado = calculateCreditSimulation(
        valueProgramDiscount,
        percentage,
        (parseFloat(simulador_ajax.interestrate) || 0) / 100,
        parseInt(term),
        formatDateToYMD(date)
      );

      const $tbody = $('#table-plan');
      $tbody.empty();

      resultado.planPagos.forEach((pago, index) => {
        const { mes, fecha, cuota, interes, abonoCapital, saldo } = pago;
        const formattedDate = fecha

        $tbody.append(`
          <tr>
              <td>${mes}</td>
              <td>${formattedDate}</td>
              <td>${Math.round(abonoCapital).toLocaleString('es-CO')}</td>
              <td>${Math.round(interes).toLocaleString('es-CO')}</td>
              <td>${Math.round(cuota).toLocaleString('es-CO')}</td>
              <td>${saldo > 0 ? Math.round(saldo).toLocaleString('es-CO') : '0'}</td>
          </tr>
        `);
      });

      const $tbodySummary = $('#program-detail');
      $tbodySummary.empty();
      
      $tbodySummary.append(`
        <tr>
            <td>${resultado.resumen.valorMatricula || 0}</td>
            <td>${resultado.resumen.matriculaNeta || 0}</td>
            <td>${simulador_ajax.interestrate || 0}%</td>
            <td>${resultado.resumen.fechaInicio}</td>
            <td>${resultado.resumen.montoCredito || 0}</td>
            <td>${resultado.resumen.cuotaInicial || 0}</td>
            <td>${resultado.resumen.administracion || 0}</td>
            <td>${resultado.resumen.cuotaMensual || 0}</td>
        </tr>
      `);
              
      // Mostrar contenedor si hay filas
      const rows = $('#table-plan tr').length;
      if (rows > 0) {
        $('#content-plan').addClass('show'); // mostrar plan si tiene clase show
        $('#payment-plan').addClass('loaded');
        $('#continue-program').prop('disabled', false);
      }
    });

    // ------------------- //
    // Detectar cambios y guardarlos en el storage
    $(document).on('input change', '#view-container input, #view-container select', function () {
      const $field = $(this);
      const id = $field.attr('id');
      const type = $field.attr('type');
      const value = $field.val();

      // Ignora campos sin ID o tipo file
      if (!id || type === 'file') return;

      // Guarda el valor en sessionStorage
      sessionStorage.setItem(id, value);
    });

    // Detectar cambios para validar formularios dinámicamente
    $(document).on('input change', '#view-container [required], #view-container select', validateForm);
    $(document).on('ajaxComplete', validateForm);
  });

  // validación de campo plazo
  function populateTermOptions() {
    const today = new Date();
    const limitDate = new Date(2025, 6, 24); // 24 de julio (mes 6 = julio)

    const $termSelect = $('#term');
    $termSelect.empty().append(`<option value="">Selecciona una opción</option>`);

    const maxTerm = today >= limitDate ? 5 : 12; // después de 24/07/2025 sólo 5 meses

    for (let i = 1; i <= maxTerm; i++) {
      $termSelect.append(`<option value="${i}">${i} mes${i > 1 ? 'es' : ''}</option>`);
    }
  }

  // validación de campo beca
  function toggleScholarshipFields() {
    const $checked = $('#typeOfScholarship'); // Checkbox para tipo de beca
    const $percentageSelect = $('#percentage'); // Select para porcentaje de beca

    // si checked no esta seleccionado, no se muestra el campo porcentaje
    if (!$checked.is(':checked')) {
      $('#percentage-scholarship').hide();
      $('#percentage').val(''); // Limpiar el campo al ocultarlo
      $percentageSelect.empty().append('<option value="">Selecciona una opción</option>'); // Limpiar opciones
      $percentageSelect.attr('required', false); // Hacerlo no requerido
      $('#proofOfScholarship').removeAttr('required'); // Quitar requerido del archivo de prueba de beca
      return;
    }

    // si checked esta seleccionado, se muestra el campo porcentaje
    $('#percentage-scholarship').show();
    $('#percentage').val(''); // Limpiar el campo al mostrarlo
    $percentageSelect.attr('required', true); // Hacerlo requerido
    $('#proofOfScholarship').attr('required', true); // Requerir archivo de prueba de beca
    // llenar el select de porcentaje de beca de 30% a 80% en incrementos de 5% 
    for (let i = 30; i <= 80; i += 5) {
      $('#percentage').append(`<option value="${i}">${i}%</option>`);
    }
  }

  // validación del campo jornada
  function syncModalityWithJornada() {
    const jornada = $('#days').val();
    const isDistance = jornada === '4'; // 4 = Jornada 'Distancia'

    if (isDistance) {
      $('#mode').val('2').prop('disabled', true); // 2 = Modalidad 'Distancia'
    } else {
      $('#mode').prop('disabled', false);
    }
  }

  // modificación de los input tipo file que estan dentro del div con la clase file-component
  function initFileInputs() {
    $('.file-component').each(function () {
      const $component = $(this);
      const $input = $component.find('input[type="file"]');
      const $label = $component.find('label');
      const $labelIcon = $label.find('span.material-symbols-outlined'); // ícono 'upload'
      const $labelText = $label.find('span.text'); // texto de etiqueta
      const $removeIcon = $component.find('span.material-symbols-outlined.close'); // ícono 'close'
      const labelDefault = $labelText.text();

      // Cambio de archivo
      $input.on('change', function () {
        const fileName = $input[0].files[0]?.name || '';
        if (fileName) {
          $labelText.text(fileName);
          $labelIcon.hide();
          $removeIcon.show();
        } else {
          $labelText.text(labelDefault);
          $labelIcon.show();
          $removeIcon.hide();
        }
      });

      // Eliminar archivo
      $removeIcon.on('click', function () {
        $input.val('');
        $labelText.text(labelDefault);
        $labelIcon.show();
        $removeIcon.hide();
      });

      // Estado inicial
      $labelIcon.show();
      $removeIcon.hide();
    });
  }

  // para restaurar los datos traidos desde el storage para cuando le den al botón de anterior
  // para limpiar los datos del storage cuando le den al botón enviar y cuando se refresca el sitio sessionStorage.clear();
  function restoreFormData() {
    $('#view-container input, #view-container select').each(function () {
      const $field = $(this);
      const id = $field.attr('id');
      const type = $field.attr('type');
      const saved = sessionStorage.getItem(id);

      if (!id || type === 'file' || saved === null) return;

      // Si es select, asegúrate de que el valor esté disponible como opción
      if ($field.is('select')) {
        const interval = setInterval(() => {
          if ($field.find(`option[value="${saved}"]`).length > 0) {
            $field.val(saved).trigger('change');
            clearInterval(interval);
          }
        }, 50);
        setTimeout(() => clearInterval(interval), 2000); // timeout máximo
      } else {
        $field.val(saved).trigger('input');
      }
    });
  }

  function mapExcelData(excelData, program, day, modality, student) {
    if (!excelData || !Array.isArray(excelData)) return [];
    const data = excelData.slice(3); // Ignorar la primera fila si es encabezado

    return data.filter(d => {
      return d[1] === program.toUpperCase() && d[2] === day && d[3] === modality && d[4] === student;
    });
  }

  function formatDateToYMD(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0'); // Mes empieza desde 0
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
  }
})(jQuery);