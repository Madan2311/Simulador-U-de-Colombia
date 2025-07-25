<?php

    $programs = [
    1 => "Administración financiera",
    2 => "Contaduría pública",
    3 => "Derecho",
    4 => "Comunicación y diseños ambientes digitales",
    5 => "Seguridad y salud en el trabajo",
    6 => "Sistemas de información",
    7 => "Mercadeo e innovación comercial",
    8 => "Tecnología seguridad digital",
    9 => "Licenciatura modelos educativos flexibles",
    10 => "Especialización finanzas y banca",
    11 => "Especialización en NIIF",
    12 => "Especialización gestión tributaria",
    13 => "Especialización derecho daños",
    14 => "Especialización contratación estatal",
    15 => "Especialización derecho informático",
    16 => "Especialización psicopedagogía",
    17 => "Especialización análisis de datos"
    ];

    $days = [
        1 => "Sabatina",
        2 => "Diurna",
        3 => "Nocturna",
        4 => "Distancia",
    ];

    $mode = [
        1 => "Presencial",
        2 => "Distancia",
    ];

    $typeOfStudent = [
        1 => "Antiguo",
        2 => "Nuevo",
    ];

    $typeOfScholarship = [

    ];

    //ordenar array
    asort($programs);
    asort($days);
    asort($mode);
    asort($typeOfStudent);
    asort($typeOfScholarship);

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
                        <?php foreach ($programs as $value => $label): ?>
                            <option value="<?= $value ?>" id="programs-<? $value ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="days">Jornada</label>
                    <select name="select" id="days" required>
                        <?php foreach ($days as $value => $label): ?>
                            <option value="<?= $value ?>" id="days-<? $value ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            <div class="content-inputs">
                <div>
                    <label for="mode">Modalidad</label>
                    <select name="select" id="mode" required>
                        <?php foreach ($mode as $value => $label): ?>
                            <option value="<?= $value ?>" id="mode-<? $value ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div>
                    <label for="typeOfStudent">Tipo de estudiante</label>
                    <select name="select" id="typeOfStudent" required>
                        <?php foreach ($typeOfStudent as $value => $label): ?>
                            <option value="<?= $value ?>" id="typeOfStudent-<? $value ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            
            
            <div class="content-inputs typeOfScholarship">
                <div>
                    <label for="typeOfScholarship">Tipo de beca</label>
                    <select name="select" id="typeOfScholarship">
                        <?php foreach ($typeOfScholarship as $value => $label): ?>
                            <option value="<?= $value ?>" id="typeOfScholarship-<? $value ?>"><?= htmlspecialchars($label) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="percentage">
                    <label for="percentage">Porcentaje</label>
                    <div>
                        <input type="number" id="percentage" required />
                        <p>%</p>
                    </div>
                </div>
            </div>
            
            <div>
                <label for="term">Plazo</label>
                <select name="select" id="term" required>
                    <option value="">Selecciona una opción</option>
                </select>
            </div>
            
        </div>
    </div>

    <div class="plan" id="content-plan">
        <h3>Plan de pagos</h3>
        <table>
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
    
    <div class="content-btns">
        <button class="continue" id="previous-program">Anterior</button>
        <button class="continue" id="payment-plan" disabled>Plan de pagos</button>
        <button class="continue" id="continue-program" disabled>Continuar</button>
    </div>
</div>