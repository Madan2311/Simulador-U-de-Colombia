<div class="content-text-info-credit">
    <div class="title">
        <h1>Detalles del programa</h1>
        <span>Llena todos los campos, todos son obligatorios</span>
    </div>

    <div class="requirements">
        <h3>Se requiere verificación</h3>
        <p>¡Ya casi terminas! Solo ingresa el código que enviamos a tu correo electrónico o celular. Recuerda que vence
            en 5 minutos.</p>

        <div class="form formValidateCode">
            <div class="content-inputs">
                <div>
                    <input type="text" id="codeSoap" required autocomplete="off" />
                </div>
            </div>
        </div>

        <div id="sendCode" class="sendCode">volver a enviar código</div>
    </div>

    <div class="content-btns">
        <button class="continue" id="previous-confirm-student">Anterior</button>
        <button class="continue" id="send" disabled>Enviar</button>
    </div>
</div>

<input type="hidden" id="simulador_nonce" value="<?php echo wp_create_nonce('simulador_send_form'); ?>">