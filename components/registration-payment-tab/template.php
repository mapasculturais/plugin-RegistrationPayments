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
                <div v-if="!dataSent" class="col-12">
                    <entity-field :entity="entity" prop="payment_proponent_name"></entity-field>
                </div>
                <div v-else class="col-12 field">
                    <label class="field__title" for="payment_proponent_name"><?= i::__('Nome do proponente') ?></label>
                    <input id="payment_proponent_name" :value="entity.payment_proponent_name" type="text" autocomplete="off" disabled>
                </div>

                <div v-if="!dataSent" class="col-6">
                    <entity-field :entity="entity" prop="payment_social_type"></entity-field>
                </div>
                <div v-else class="col-6 field">
                    <label class="field__title" for="payment_social_type"><?= i::__('Tipo social') ?></label>
                    <input id="payment_social_type" :value="entity.payment_social_type" type="text" autocomplete="off" disabled>
                </div>

                <div v-if="!dataSent" class="col-6">
                    <entity-field :entity="entity" prop="payment_proponent_document" mask="['###.###.###-##', '##.###.###/####-##']"></entity-field>
                </div>
                <div v-else class="col-6 field">
                    <label class="field__title" for="payment_proponent_document"><?= i::__('Documento do proponente') ?></label>
                    <input id="payment_proponent_document" :value="entity.payment_proponent_document" type="text" autocomplete="off" disabled>
                </div>
            </div>
        </mc-card>

        <mc-card>
            <h3><?= i::__('Dados bancários do proponente') ?></h3>

            <div class="grid-12">
                <div v-if="!dataSent" class="col-12">
                    <entity-field :entity="entity" prop="payment_account_type"></entity-field>
                </div>
                <div v-else class="col-12 field">
                    <label class="field__title" for="payment_account_type"><?= i::__('Tipo de conta') ?></label>
                    <input id="payment_account_type" :value="entity.payment_account_type" type="text" autocomplete="off" disabled>
                </div>

                <div v-if="!dataSent" class="col-12">
                    <entity-field :entity="entity" prop="payment_bank"></entity-field>
                </div>
                <div v-else class="col-12 field">
                    <label class="field__title" for="payment_bank"><?= i::__('Banco') ?></label>
                    <input id="payment_bank" :value="entity.payment_bank" type="text" autocomplete="off" disabled>
                </div>

                <div v-if="!dataSent" class="col-6">
                    <entity-field :entity="entity" prop="payment_branch"></entity-field>
                </div>
                <div v-else class="col-6 field">
                    <label class="field__title" for="payment_branch"><?= i::__('Agência sem o dígito') ?></label>
                    <input id="payment_branch" :value="entity.payment_branch" type="text" autocomplete="off" disabled>
                </div>

                <div v-if="!dataSent" class="col-6">
                    <entity-field :entity="entity" prop="payment_branch_dv" mask="**"></entity-field>
                </div>
                <div v-else class="col-6 field">
                    <label class="field__title" for="payment_branch_dv"><?= i::__('Dígito verificador da agência') ?></label>
                    <input id="payment_branch_dv" :value="entity.payment_branch_dv" type="text" autocomplete="off" disabled>
                </div>

                <div v-if="!dataSent" class="col-6">
                    <entity-field :entity="entity" prop="payment_account"></entity-field>
                </div>
                <div v-else class="col-6 field">
                    <label class="field__title" for="payment_account"><?= i::__('Conta sem o dígito') ?></label>
                    <input id="payment_account" :value="entity.payment_account" type="text" autocomplete="off" disabled>
                </div>

                <div v-if="!dataSent" class="col-6">
                    <entity-field :entity="entity" prop="payment_account_dv"></entity-field>
                </div>
                <div v-else class="col-6 field">
                    <label class="field__title" for="payment_account_dv"><?= i::__('Dígito verificador da conta') ?></label>
                    <input id="payment_account_dv" :value="entity.payment_account_dv" type="text" autocomplete="off" disabled>
                </div>
            </div>
        </mc-card>
        
        <button class="button button--primary" @click="sendPaymentData" :disabled="dataSent"><?= i::__('Enviar') ?></button>
    </div>
</mc-tab>