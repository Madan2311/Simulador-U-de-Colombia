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

    $term = [

    ];

    //ordenar array
    asort($programs);
    asort($days);
    asort($mode);
    asort($typeOfStudent);
    asort($typeOfScholarship);
    asort($term);

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
                    <label for="name" required >Nombre completo</label>
                    <input type="text" id="name" />
                </div>
                
                <div>
                    <label for="id" required >Número de indentificación</label>
                    <input type="number" id="id" />
                </div>
            </div>
            
            <div class="content-inputs">
                <div>
                    <label for="celPhone" required >Célular</label>
                    <input type="text" id="celPhone" />
                </div>

                <div>
                    <label for="email" required >Correo</label>
                    <input type="email" id="email" />
                </div>
            </div>
            
            <div class="content-inputs">
                <div class="file-component">
                    <input type="file" id="employmentLetter" required />
                    <label for="employmentLetter"><span class="material-symbols-outlined">upload</span><span class="text">Carta laboral</span></label>
                    <span class="material-symbols-outlined close">close</span>
                </div>

                <div class="file-component">
                    <input type="file" id="paymentStubs" required />
                    <label for="paymentStubs"><span class="material-symbols-outlined">upload</span><span class="text">Colillas de pago</span></label>
                    <span class="material-symbols-outlined close">close</span>
                </div>
            </div>
            
            <div class="content-inputs">
                <div class="file-component">
                    <input type="file" id="proofOfScholarship" required />
                    <label for="proofOfScholarship"><span class="material-symbols-outlined">upload</span><span class="text">Comprobante de beca</span></label>
                    <span class="material-symbols-outlined close">close</span>
                </div>

                <div class="file-component">
                    <input type="file" id="document" required />
                    <label for="document"><span class="material-symbols-outlined">upload</span><span class="text">Documento</span></label>
                    <span class="material-symbols-outlined close">close</span>
                </div>
            </div>

        </div>
    </div>

    <div class="content-btns">
        <button class="continue" id="previous-student">Anterior</button>
        <button class="continue" id="send" disabled>Enviar</button>
    </div>
</div>