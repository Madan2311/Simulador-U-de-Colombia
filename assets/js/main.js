// Objeto global que mantendrá los datos del formulario
window.formDataState = {};

(function ($) {
  $(document).ready(function () {
    // Cargar la vista inicial
    loadView('details');
    //loadView('messages');
    sessionStorage.clear(); // limpia el storage al cargar el sitio
    const camposAObservar = [
      '#programs',
      '#mode',
      '#days',
      '#typeOfStudent',
      '#term',
      '#typeOfScholarship',
      '#percentage',
      '#scholarshipOrigin',
      "#financing"
    ];

    // Llamar el login al cargar el simulador
    $.ajax({
      url: simulador_ajax.ajaxurl,
      method: 'POST',
      data: {
        action: 'simulador_login_firma'
      },
      success: function (response) {
        console.log('Login firmado OK:', response);
      },
      error: function (error) {
        console.error('Error al hacer login firmado:', error);
      }
    });

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
            syncJornadaWithModality();      // validación de la jornada
            restoreFormData();              // Restaurar los datos de los campos
            initializeFormDataState();      // Inicializa el objeto global con los datos del formulario

            if (viewPath.includes('form-program')) {
              populateTermOptions();        // valida el campo plazo
              toggleScholarshipFields();    // valida el campo beca
            }

            if (viewPath === 'messages') {
              const status = sessionStorage.getItem('formSuccess');
              const errorMsg = sessionStorage.getItem('formErrorMessage');
              if (status === '1') {
                $('#success-message').show();
                $('#error-message').hide();
              } else {
                $('#error-message').show();
                $('#success-message').hide();
                if (errorMsg) {
                  $('#mensajeError').text(errorMsg); // Reemplaza el texto del error
                  sessionStorage.removeItem('formErrorMessage');
                }
              }
              sessionStorage.removeItem('formSuccess'); // limpiar después
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
      $(document).on('click', '#previous-student', () => switchView('form-program'));

      $(document).on('click', '#previous-confirm-student', () => switchView('form-student'));
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
      $('#emailStudent').innertHTML = emailVal;

      // Validar celular con máscara
      if (!$('#celPhone').inputmask('isComplete')) isValid = false;
      $('#cellStudent').innertHTML = $('#celPhone').val();

      $('#next-student').prop('disabled', !isValid)
      $('#send').prop('disabled', !isValid);
    }

    // ------------------- //
    $(document).on('change', camposAObservar.join(','), function () {
      // Limpiar contenido de las tablas
      $('#program-detail').empty();
      $('#table-plan').empty();

      // Ocultar contenedores de tablas
      $('#content-plan').removeClass('show');
      $('#message-program-nofound').removeClass('show');
      $('#payment-plan').removeClass('loaded');
      $('#continue-program').prop('disabled', true);

      // Eliminar los datos del estado
      delete formDataState['program_detail_html'];
      delete formDataState['payment_plan_html'];
      delete formDataState['dataPlan'];
      delete formDataState['fechaFinal'];

      // También limpiar del sessionStorage si se requiere
      sessionStorage.removeItem('program_detail_html');
      sessionStorage.removeItem('payment_plan_html');
      sessionStorage.removeItem('dataPlan');
      sessionStorage.removeItem('fechaFinal');
    });

    // Máscara para el campo celular
    $(document).on('focus', '#celPhone', function () {
      $(this).inputmask('999 999 9999', {
        placeholder: '___ ___ ____',
        showMaskOnHover: false
      });
    });

    // ------------------- //
    // validación de Jornada
    $(document).on('change', '#mode', function () {
      syncJornadaWithModality();
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
      const checked = $('#typeOfScholarship').is(':checked');
      const financingChecked = $('#financing').is(':checked');

      const date = stripTime(new Date());
      const mapData = mapExcelData(simulador_ajax.excelData, program, day, modality, typeOfStudent);
      let valueProgramDiscount = 0;

      if (mapData.length !== 0) {
        const dateDiscountOne = mapData[0][7] || '';
        const dateDiscountTwo = mapData[0][10] || '';

        dateDiscountOne.split('/').reverse().join('-'); // formatear fecha a YYYY-MM-DD
        dateDiscountTwo.split('/').reverse().join('-'); // formatear fecha a YYYY-MM-DD

        if (checked) {
          const discountReplace = mapData[0][13].replace(/[ ,$]/g, "");
          valueProgramDiscount = parseFloat(discountReplace) || 0;
        } else {
          if (date <= parseDateDMY(dateDiscountOne)) {
            const discountReplace = mapData[0][8].replace(/[ ,$]/g, "");
            valueProgramDiscount = parseFloat(discountReplace) || 0;
          } else if (date > parseDateDMY(dateDiscountOne) && date <= parseDateDMY(dateDiscountTwo)) {
            const discountReplace = mapData[0][11].replace(/[ ,$]/g, "");
            valueProgramDiscount = parseFloat(discountReplace) || 0;
          } else {
            const discountReplace = mapData[0][13].replace(/[ ,$]/g, "");
            valueProgramDiscount = parseFloat(discountReplace) || 0;
          }
        }
      }

      if (!valueProgramDiscount || valueProgramDiscount <= 0) {
        $('#content-plan').removeClass('show'); // ocultar plan si no hay valor
        $('#payment-plan').removeClass('loaded');
        $('#continue-program').prop('disabled', true);
        $('#message-program-nofound').addClass('show'); // mostrar mensaje si no hay valor
        return;
      }
      const interestrate = $('.content-all-simulator.simulador-plugin').data('interestrate')
      // Función para calcular la simulación de crédito
      formDataState['interestrate'] = parseFloat(interestrate) || 0; // tasa de interés
      const resultado = calculateCreditSimulation(
        valueProgramDiscount,
        percentage,
        (parseFloat(interestrate) || 0) / 100,
        parseInt(term),
        formatDateToYMD(date),
        program,
        financingChecked
      );

      const $tbody = $('#table-plan');
      $tbody.empty();

      resultado.planPagos.forEach((pago, index) => {
        const { mes, fecha, cuota, interes, abonoCapital, saldo } = pago;
        const formattedDate = fecha
        if (index === resultado.planPagos.length - 1) {
          formDataState['fechaFinal'] = formattedDate;
        }
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
        $('#message-program-nofound').removeClass('show'); // ocultar mensaje si hay filas
        $('#content-plan').addClass('show'); // mostrar plan si tiene clase show
        $('#payment-plan').addClass('loaded');
        $('#continue-program').prop('disabled', false);

        // Guardar tablas en formDataState
        formDataState['program_detail_html'] = $('#program-detail').html();
        formDataState['payment_plan_html'] = $('#table-plan').html();
        formDataState['dataPlan'] = { ...resultado.resumen };
      }
    });

    // ------------------- //
    // Detectar cambios y guardarlos en el storage
    $(document).on('input change', '#view-container input, #view-container select', function () {
      const $field = $(this);
      const id = $field.attr('id');
      const type = $field.attr('type');
      const value = type === 'checkbox' ? $field.is(':checked') : $field.val();

      // Ignora campos sin ID o tipo file
      if (!id || type === 'file') return;

      // Actualiza el objeto global
      formDataState[id] = value;

      // Guarda el valor en sessionStorage
      sessionStorage.setItem(id, value);
    });

    // ------------------- //
    $(document).on('click', '#next-student, #sendCode', function (e) {
      e.preventDefault();

      $(this).prop('disabled', true);
      $('#view-container').hide();   // Oculta el formulario
      $('#loading').show();

      const primerNombre = formDataState['name'];
      const segundoNombre = formDataState['secondName'];
      const primerApellido = formDataState['lastName'];
      const segundoApellido = formDataState['secondLastName'];
      const documento = formDataState['id'];
      const correo = formDataState['email'];
      const celular = parseFloat(formDataState['celPhone'].replace(/[ ,$]/g, ''));

      $.ajax({
        url: simulador_ajax.ajaxurl, // Asegúrate de que esté definido en tu PHP
        method: 'POST',
        dataType: 'json',
        data: {
          action: 'simulador_certificado_handler',
          tipoDocumento: 1,
          primerNombre,
          segundoNombre,
          primerApellido,
          segundoApellido,
          documento,
          correo,
          celular,
          notificacion: 3 //tipo de notificación
        },
        success: function (response) {
          $('#loading').hide();
          if (response && response.data && response.data.estado === 0) {
            switchView('confirm-student');
          } else {
            // Guarda el mensaje de error en sessionStorage
            sessionStorage.setItem('formSuccess', '0');
            sessionStorage.setItem('formErrorMessage', response.data?.mensaje || 'Hubo un problema al solicitar el certificado.');
            switchView('messages');
          }
        },
        error: function (xhr, status, error) {
          $('#loading').hide();
          sessionStorage.setItem('formSuccess', '0');
          sessionStorage.setItem('formErrorMessage', error || 'No se pudo conectar con el servidor.');
          switchView('messages');
        }
      });


    })

    // ------------------- //
    // Envío del formulario
    $(document).on('click', '#send', function (e) {
      e.preventDefault();

      if ($(this).prop('disabled')) return;

      // se ponen el disable para evitar múltiples envíos
      $(this).prop('disabled', true);
      $('#view-container').hide();   // Oculta el formulario
      $('#loading').show();

      $('#previous-studen').prop('disabled', true);

      const formData = new FormData();
      const fullName = construirNombreCompleto(formDataState['name'], formDataState['secondName'], formDataState['lastName'], formDataState['secondLastName']);
      // Datos básicos
      formData.append('action', 'simulador_send_form');
      formData.append('simulador_nonce', $('#simulador_nonce').val());

      // Desde formDataState
      formData.append('name', fullName || '');
      formData.append('id', formDataState['id'] || '');
      formData.append('celPhone', formDataState['celPhone'] || '');
      formData.append('email', formDataState['email'] || '');
      formData.append('programs', formDataState['programs'] || '');
      formData.append('days', formDataState['days'] || '');
      formData.append('mode', formDataState['mode'] || '');
      formData.append('typeOfStudent', formDataState['typeOfStudent'] || '');
      formData.append('term', formDataState['term'] || '');
      formData.append('typeOfScholarship', formDataState['typeOfScholarship'] ? '1' : '0');
      formData.append('percentage', formDataState['percentage'] || '');
      formData.append('scholarshipOrigin', formDataState['scholarshipOrigin'] || '');

      // Contenido HTML de las tablas
      formData.append('program_detail_html', formDataState['program_detail_html'] || '');
      formData.append('payment_plan_html', formDataState['payment_plan_html'] || '');

      formData.append('dataPlan', JSON.stringify(formDataState['dataPlan'] || {}));
      formData.append('fechaFinal', formDataState['fechaFinal'] || '');
      formData.append('interestrate', formDataState['interestrate'] || '');

      formData.append('codeSoap', formDataState['codeSoap'] || '');

      // Adjuntar archivos
      if (formDataState['employmentLetter_temp']) {
        formData.append('employmentLetter_temp', formDataState['employmentLetter_temp']);
      }
      if (formDataState['paymentStubs_temp']) {
        formData.append('paymentStubs_temp', formDataState['paymentStubs_temp']);
      }
      if (formDataState['document_temp']) {
        formData.append('document_temp', formDataState['document_temp']);
      }
      if (formDataState['proofOfScholarship_temp']) {
        formData.append('proofOfScholarship_temp', formDataState['proofOfScholarship_temp']);
      }

      fetch(simulador_ajax.ajaxurl, {
        method: 'POST',
        body: formData
      })
        .then(res => res.json())
        .then(res => {
          $('#loading').hide();
          if (res.success) {
            sessionStorage.clear(); // Limpia otros datos
            sessionStorage.setItem('formSuccess', '1');
            switchView('messages');
          } else {
            sessionStorage.clear(); // Limpia otros datos
            sessionStorage.setItem('formSuccess', '0');
            sessionStorage.setItem('formErrorMessage', res.data?.mensaje || 'Hubo un problema con la autenticación.');
            switchView('messages');
          }
        })
        .catch(err => {
          $('#loading').hide();
          sessionStorage.setItem('formSuccess', '0');
          sessionStorage.setItem('formErrorMessage', `Error en envío: ${err}` || 'Error de red al enviar.');
          switchView('messages');
        });
    });

    $(document).on('click', '#previous-home', function (e) {
      limpiarArchivosTemporales();
      sessionStorage.clear(); // Limpia otros datos
      switchView('details');
    });

    // Detectar cambios para validar formularios dinámicamente
    $(document).on('input change', '#view-container [required], #view-container select', validateForm);
    $(document).on('ajaxComplete', validateForm);

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
          const file = this.files[0];
          const fileName = $input[0].files[0]?.name || '';

          if (file) {
            $labelText.text(fileName);
            $labelIcon.hide();
            $removeIcon.show();
          } else {
            $labelText.text(labelDefault);
            $labelIcon.show();
            $removeIcon.hide();
            return
          }

          const formData = new FormData();
          formData.append('action', 'simulador_upload_temp');
          formData.append('file', file);
          formData.append('field', $input.attr('id'));

          fetch(simulador_ajax.ajaxurl, {
            method: 'POST',
            body: formData
          })
            .then(res => res.json())
            .then(res => {
              if (res.success) {
                // Guarda la ruta temporal en formDataState
                sessionStorage.setItem($input.attr('id'), + "_temp", res.data.filepath);
                formDataState[$input.attr('id') + '_temp'] = res.data.filepath;
              } else {
                console.log('Error al subir archivo: ' + (res.data?.message || ''));
              }
            })
            .catch(() => {
              console.log('Error de red al subir archivo.');
            });
        });

        // Eliminar archivo
        $removeIcon.on('click', function () {
          $input.val('');
          $labelText.text(labelDefault);
          $labelIcon.show();
          $removeIcon.hide();

          validateForm();
        });

        // Estado inicial
        $labelIcon.show();
        $removeIcon.hide();
      });
    }
  });

  // validación de campo plazo
  function populateTermOptions() {
    const today = new Date();
    const day = today.getDate();
    const month = today.getMonth(); // 0 = enero
    const year = today.getFullYear();

    const $termSelect = $('#term');
    $termSelect.empty().append(`<option value="">Selecciona una opción</option>`);

    // Determinar si estamos en el primer o segundo semestre
    const isFirstSemester = month <= 5; // enero (0) a junio (5)

    // Fecha de la primera cuota:
    const firstInstallmentDate = new Date(year, month, 30);
    if (day > 29) {
      // Si se tramita después del día 10, primera cuota es el 30 del siguiente mes
      firstInstallmentDate.setMonth(firstInstallmentDate.getMonth() + 1);
    }

    // Fecha límite de pagos: 30 de junio o 30 de diciembre
    const lastInstallmentDate = isFirstSemester
      ? new Date(year, 5, 30) // junio
      : new Date(year, 11, 30); // diciembre

    // Calcular el número de meses completos disponibles
    let monthsAvailable =
      (lastInstallmentDate.getFullYear() - firstInstallmentDate.getFullYear()) * 12 +
      (lastInstallmentDate.getMonth() - firstInstallmentDate.getMonth()) + 1;

    // Limitar a mínimo 1 y máximo 12 cuotas posibles
    monthsAvailable = Math.max(0, Math.min(monthsAvailable, 12));

    //for (let i = 1; i <= monthsAvailable; i++) {
    //  $termSelect.append(`<option value="${i}">${i} mes${i > 1 ? 'es' : ''}</option>`);
    //}
    for (let i = 1; i <= 4; i++) {
      $termSelect.append(`<option value="${i}">${i} mes${i > 1 ? 'es' : ''}</option>`);
    }
  }

  // validación de campo beca
  function toggleScholarshipFields() {
    const $checked = $('#typeOfScholarship'); // Checkbox para tipo de beca
    const $percentageSelect = $('#percentage'); // Select para porcentaje de beca
    const $originField = $('#origin-scholarship-group'); // Campo de origen de beca

    // si checked no esta seleccionado, no se muestra el campo porcentaje
    if (!$checked.is(':checked')) {
      $('#percentage-scholarship').hide();
      $('#percentage').val(''); // Limpiar el campo al ocultarlo
      $percentageSelect.empty().append('<option value="">Selecciona una opción</option>'); // Limpiar opciones
      $percentageSelect.attr('required', false); // Hacerlo no requerido
      $('#scholarshipOrigin').removeAttr('required');
      $originField.hide().find('input').val('').removeAttr('required');
      return;
    }

    // si checked esta seleccionado, se muestra el campo porcentaje
    $('#percentage-scholarship').show();
    $('#percentage').val(''); // Limpiar el campo al mostrarlo
    $percentageSelect.attr('required', true); // Hacerlo requerido
    $('#scholarshipOrigin').attr('required', true);
    $originField.show().find('input').attr('required', true);
    // llenar el select de porcentaje de beca de 30% a 80% en incrementos de 5% 
    for (let i = 30; i <= 80; i += 5) {
      $('#percentage').append(`<option value="${i}">${i}%</option>`);
    }
    //$('#percentage').append(`<option value="Financiación 100%</option>`)
  }

  function syncJornadaWithModality() {
    const modalidad = $('#mode').val();
    const isDistance = modalidad === 'Distancia'; // Modalidad 'Distancia'

    if (isDistance) {
      $('#days').val('Distancia').prop('disabled', true); // Jornada 'Distancia'
    } else {
      $('#days').prop('disabled', false);
    }
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

    const programReplace = limpiarNombre(program);

    return data.filter(d => {
      return d[1] === programReplace.toUpperCase() && d[2] === day && d[3] === modality && d[4] === student;
    });
  }

  function formatDateToYMD(date) {
    const year = date.getFullYear();
    const month = String(date.getMonth() + 1).padStart(2, '0'); // Mes empieza desde 0
    const day = String(date.getDate()).padStart(2, '0');

    return `${year}-${month}-${day}`;
  }

  function initializeFormDataState() {
    $('#view-container input, #view-container select, #view-container textarea').each(function () {
      const $field = $(this);
      const id = $field.attr('id');
      const type = $field.attr('type');

      if (!id || type === 'file') return;

      const value = type === 'checkbox' ? $field.is(':checked') : $field.val();

      formDataState[id] = value;
    });
  }

  function construirNombreCompleto(primerNombre, segundoNombre, primerApellido, segundoApellido) {
    const partes = [];

    if (primerNombre) partes.push(primerNombre.trim());
    if (segundoNombre) partes.push(segundoNombre.trim());
    if (primerApellido) partes.push(primerApellido.trim());
    if (segundoApellido) partes.push(segundoApellido.trim());

    return partes.join(' ');
  }

  function limpiarArchivosTemporales() {
    $.ajax({
      url: simulador_ajax.ajaxurl,
      method: 'POST',
      dataType: 'json',
      data: {
        action: 'limpiar_temp',
      },
      success: function (res) {
        console.log('Archivos temporales eliminados.');
      },
      error: function (err) {
        console.error('Error al eliminar archivos temporales.', err);
      }
    });
  }

  function parseDateDMY(dateStr) {
    const [month, day, year] = dateStr.split('/');
    return new Date(parseInt(year), parseInt(month) - 1, parseInt(day));
  }

  function stripTime(date) {
    return new Date(date.getFullYear(), date.getMonth(), date.getDate());
  }

  function limpiarNombre(nombre) {
    const casos = [
      "EXPERIENCIA.CO LICENCIATURA EDUCACIÓN FÍSICA",
      "EXPERIENCIA.CO LICENCIATURA MODELOS EDUCATIVOS FLEXIBLES"
    ];

    if (casos.includes(nombre)) {
      return nombre.replace("EXPERIENCIA.CO ", "");
    }

    return nombre; // Si no coincide, lo deja igual
  }

})(jQuery);