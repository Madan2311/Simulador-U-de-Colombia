$(document).ready(function () {
  // Cargar la vista inicial
  loadView('views/details.php');
  sessionStorage.clear(); // limpia el storage al cargar el sitio

  // ------------------- //
  // Función para cargar vistas por AJAX
  // Esta función sirve como un init
  function loadView(viewPath, callback) {
    $.ajax({
      url: viewPath,
      method: 'GET',
      success: function (data) {
        $('#view-container').html(data).fadeIn(300, function () {
          bindEvents();           // Reasigna eventos
          validateForm();         // Lanza validación general
          initFileInputs();       // Estilo de los tipo file
          syncModalityWithJornada(); // validación de la jornada
          restoreFormData();      // Restaurar los datos de los campos

          if (viewPath.includes('form-program.php')) {
            populateTermOptions();
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
    $(document).on('click', '#continue', () => switchView('views/form-program.php'));
    $(document).on('click', '#continue-program', () => switchView('views/form-student.php'));
    $(document).on('click', '#previous-program', () => switchView('views/details.php'));
    $(document).on('click', '#previous-student', () => switchView('views/form-program.php'))
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

      if ($field.attr('id') === 'percentage' && (value <= 0 || value === '')) isValid = false;
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
    if ($('#typeOfScholarship').val() && $('#proofOfScholarship')[0]?.files.length === 0) {
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
  // ver el plan teniendo encuenta los inputs y la creación de la tabla
  $(document).on('click', '#payment-plan', function () {
    const term = parseInt($('#term').val());
    const percentage = parseFloat($('#percentage').val()) || 0;
    const totalValue = 1500000;
    const interestRate = 0.01;

    const $errorBox = $('#form-error');
    $errorBox.html('');

    if (!term || term <= 0) {
      $errorBox.html('⚠️ Por favor selecciona un plazo válido.');
      return;
    }

    if (percentage < 0 || percentage > 100) {
      $errorBox.html('⚠️ El porcentaje debe estar entre 0 y 100.');
      return;
    }

    const discount = totalValue * (percentage / 100);
    const netValue = totalValue - discount;
    const monthlyCapital = netValue / term;

    const $tbody = $('#table-plan');
    $tbody.empty();

    let balance = netValue;

    for (let i = 0; i < term; i++) {
      const interest = balance * interestRate;
      const capital = monthlyCapital;
      const cuota = capital + interest;
      balance -= capital;

      const currentDate = new Date();
      currentDate.setMonth(currentDate.getMonth() + i + 1);

      const formattedDate = currentDate.toLocaleDateString('es-CO', {
        day: '2-digit', month: '2-digit', year: 'numeric'
      });

      $tbody.append(`
      <tr>
        <td>${i + 1}</td>
        <td>${formattedDate}</td>
        <td>${Math.round(capital).toLocaleString('es-CO')}</td>
        <td>${Math.round(interest).toLocaleString('es-CO')}</td>
        <td>${Math.round(cuota).toLocaleString('es-CO')}</td>
        <td>${balance > 0 ? Math.round(balance).toLocaleString('es-CO') : '0'}</td>
      </tr>
    `);
    }

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
      console.log('entra', fileName)
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