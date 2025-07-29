<?php

    $programs = [
        "Administración financiera",
        "Contaduría pública",
        "Derecho",
        "Comunicación y diseños ambientes digitales",
        "Seguridad y salud en el trabajo",
        "Sistemas de información",
        "Mercadeo e innovación comercial",
        "Tecnología seguridad digital",
        "Licenciatura modelos educativos flexibles",
        "Especialización finanzas y banca",
        "Especialización en NIIF",
        "Especialización gestión tributaria",
        "Especialización derecho daños",
        "Especialización contratación estatal",
        "Especialización derecho informático",
        "Especialización psicopedagogía",
        "Especialización análisis de datos"
    ];

    $days = [
        "Sabatina",
        "Diurna",
        "Nocturna",
        "Distancia",
    ];

    $mode = [
        "Presencial",
        "Distancia",
    ];

    $typeOfStudent = [
        "Antiguo",
        "Nuevo",
    ];

    //ordenar array
    asort($programs);
    asort($days);
    asort($mode);
    asort($typeOfStudent);

?>

<div class="content-text-info-credit">
    <div class="title">
        <h1>Detalles del programa</h1>
        <span>Llena todos los campos, todos son obligatorios</span>
    </div>

    <div class="requirements">
        <h3>Información necesaria</h3>
        <div class="form">
            <div class="content-inputs">
                <div>
                    <label for="programs">Seleccionar programa</label>
                    <select name="select" id="programs" required>
                        <?php foreach ($programs as $value ): ?>
                            <option value="<?= $value ?>" id="programs-<? $value ?>"><?= htmlspecialchars($value) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="days">Jornada</label>
                    <select name="select" id="days" required>
                        <?php foreach ($days as $value): ?>
                            <option value="<?= $value ?>" id="days-<? $value ?>"><?= htmlspecialchars($value) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="content-inputs">
                <div>
                    <label for="mode">Modalidad</label>
                    <select name="select" id="mode" required>
                        <?php foreach ($mode as $value): ?>
                            <option value="<?= $value ?>" id="mode-<? $value ?>"><?= htmlspecialchars($value) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="typeOfStudent">Tipo de estudiante</label>
                    <select name="select" id="typeOfStudent" required>
                        <?php foreach ($typeOfStudent as $value): ?>
                            <option value="<?= $value ?>" id="typeOfStudent-<? $value ?>"><?= htmlspecialchars($value) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div style="margin: 0 5px;">
                <label for="term">Plazo</label>
                <select name="select" id="term" required>
                    <option value="">Selecciona una opción</option>
                </select>
            </div>

            <div class="content-inputs typeOfScholarship">
                <div class="custom-checkbox">
                    <label style="margin:0;" for="typeOfScholarship">
                        <input type="checkbox" id="typeOfScholarship" name="typeOfScholarship">
                        <div class="dot-box">
                            <div class="dot"></div>
                        </div>
                        ¿Tiene beca?
                    </label>
                </div>
                <div class="percentage" id="percentage-scholarship" style="display: none;">
                    <label for="percentage">Porcentaje</label>
                    <div>
                        <select name="select" id="percentage" >
                            <option value="">Selecciona una opción</option>
                        </select>
                        <p>%</p>
                    </div>
                </div>
            </div>
            
        </div>
    </div>

    <div class="plan" id="content-plan">
        <h3>Detalle del programa</h3>
        <table id="program-detail-table">
            <thead>
                <tr>
                    <th scope="col">Valor matrícula</th>
                    <th scope="col">Valor neto matrícula</th>
                    <th scope="col">Tasa de interés</th>
                    <th scope="col">Fecha</th>
                    <th scope="col">Monto del crédito</th>
                    <th scope="col">Cuota inicial</th>
                    <th scope="col">Administración</th>
                    <th scope="col">Cuota mensual</th>
                </tr>
            </thead>
            <tbody id="program-detail">
            </tbody>
        </table>

        <h3>Plan de pagos</h3>
        <table id="payment-plan-table">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">FECHA</th>
                    <th scope="col">CAPITAL</th>
                    <th scope="col">INTERÉS</th>
                    <th scope="col">CUOTA</th>
                    <th scope="col">SALDO</th>
                </tr>
            </thead>
            <tbody id="table-plan">
            </tbody>
        </table>
    </div>

    <div class="message-program-nofound" id="message-program-nofound">
        <h3>¡Ups! Algo salió mal.</h3>
        <p>El programa seleccionado no tiene información disponible. Por favor, elige otro programa.</p>
    </div>
    
    <div class="content-btns">
        <button class="continue" id="previous-program">Anterior</button>
        <button class="continue" id="payment-plan" disabled>Plan de pagos</button>
        <button class="continue" id="continue-program" disabled>Continuar</button>
    </div>
</div>