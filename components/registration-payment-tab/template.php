<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    entity-field
    mc-card
    mc-tab
');
?>

<mc-tab label="<?= i::_e('Dados de pagamento') ?>" slug="payment">
    <div class="registration__content">
        <mc-card>
            <h3><?= i::__('Dados do proponente') ?></h3>

            <div class="grid-12">

                <div class="col-12">
                    <entity-field :entity="entity" prop="payment_proponent_name" :autosave="3000"></entity-field>
                </div>
                <div class="col-6">
                    <entity-field :entity="entity" prop="payment_social_type" :autosave="3000"></entity-field>
                </div>

                <div class="col-6">
                    <entity-field :entity="entity" prop="payment_proponent_document" :autosave="3000" mask="['###.###.###-##', '##.###.###/####-##']"></entity-field>
                </div>
            </div>
        </mc-card>

        <mc-card>
            <h3><?= i::__('Dados bancários do proponente') ?></h3>

            <div class="grid-12">
                <!-- Faze v-if para todos os campos e desativar o botão enviar -->
                <div class="col-12">
                    <entity-field :entity="entity" prop="payment_account_type" :autosave="3000"></entity-field>
                </div>

                <div class="col-12">
                    <entity-field :entity="entity" prop="payment_bank" :autosave="3000"></entity-field>
                </div>

                <div class="col-6">
                    <entity-field :entity="entity" prop="payment_branch" :autosave="3000"></entity-field>
                </div>
                <div class="col-6">
                    <entity-field :entity="entity" prop="payment_branch_dv" :autosave="3000" mask="**"></entity-field>
                </div>

                <div class="col-6">
                    <entity-field :entity="entity" prop="payment_account" :autosave="3000"></entity-field>

                </div>
                <div class="col-6">
                    <entity-field :entity="entity" prop="payment_account_dv" :autosave="3000"></entity-field>
                </div>
            </div>
        </mc-card>
    </div>
</mc-tab>