<?php

use MapasCulturais\i;

if ($this->isEditable()) : ?>

    <div id="payments-configuration" class="registration-fieldset project-edit-mode">
        <h4><?php i::_e("Pagamentos"); ?></h4>
        <p class="payments-help">
            <?php \MapasCulturais\i::_e("Selecione se deseja exibir a aba de pagamentos para essa oportunidade."); ?>
        </p>

        <select name="paymentsTabEnabled" id="paymentsTabEnabled" class="form-control">
            <option value="0" <?php echo ($entity->paymentsTabEnabled == 0 || $entity->paymentsTabEnabled == null) ? 'selected' : ''; ?>><?php i::_e("Desabilitar"); ?></option>
            <option value="1" <?php echo ($entity->paymentsTabEnabled == 1) ? 'selected' : ''; ?>><?php i::_e("Habilitar"); ?></option>
        </select>

        <script>
            $(document).ready(function() {
                $("#paymentsTabEnabled").change(function(e) {
                    e.preventDefault();
                    var valueSelected = $(this).find(":selected").val();
                    $.ajax({
                        type: "PATCH",
                        url: MapasCulturais.baseURL + 'payment/savePaymentConfig',
                        data: {
                            opportunity: MapasCulturais.entity.id,
                            key: 'paymentsTabEnabled',
                            value: valueSelected
                        },
                        dataType: "json",
                        success: function(response) {
                            console.log('Edições salvas.');
                        }
                    });
                });
            });
        </script>
    </div>

<?php endif; ?>