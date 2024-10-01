<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    entity-field
    mc-card
');
?>
<div ref="paymentForm" :class="'payment-form evaluation-'+evaluationClass">
    <h3><?= i::__('Informações bancárias') ?></h3>

    <div v-if="isEditable()" class="grid-12 payment-form__edit">
        <div class="col-12">
            <entity-field :entity="entity" prop="payment_proponent_name"></entity-field>
        </div>

        <div class="col-6">
            <entity-field :entity="entity" prop="payment_social_type"></entity-field>
        </div>

        <div class="col-6">
            <entity-field :entity="entity" prop="payment_proponent_document" mask="['###.###.###-##', '##.###.###/####-##']"></entity-field>
        </div>

        <div class="col-12">
            <entity-field :entity="entity" prop="payment_account_type"></entity-field>
        </div>

        <div class="col-12">
            <entity-field :entity="entity" prop="payment_bank"></entity-field>
        </div>

        <div class="col-6">
            <entity-field :entity="entity" prop="payment_branch"></entity-field>
        </div>

        <div class="col-6">
            <entity-field :entity="entity" prop="payment_branch_dv" mask="**"></entity-field>
        </div>

        <div class="col-6">
            <entity-field :entity="entity" prop="payment_account"></entity-field>
        </div>

        <div class="col-6">
            <entity-field :entity="entity" prop="payment_account_dv"></entity-field>
        </div>
    </div>
    
    <div v-else @click="toggleEvaluationForm()" class="grid-12 payment-form__view">
        <div class="col-6 field">
            <label class="field__title" :class="{'is-required': isRequired('payment_proponent_name')}" for="payment_proponent_name"><?= i::__('Nome do proponente') ?>:</label>
            <span>{{entity.payment_proponent_name}}</span>
        </div>

        <div class="col-6 field">
            <label class="field__title" :class="{'is-required': isRequired('payment_proponent_document')}" for="payment_proponent_document"><?= i::__('Documento do proponente') ?>:</label>
            <span>{{entity.payment_proponent_document}}</span>
        </div>


        <div class="col-4 field">
            <label class="field__title" :class="{'is-required': isRequired('payment_social_type')}" for="payment_social_type"><?= i::__('Tipo social') ?>:</label>
            <span>{{entity.getHumanReadable('payment_social_type')}}</span>
        </div>


        <div class="col-4 field">
            <label class="field__title" :class="{'is-required': isRequired('payment_account_type')}" for="payment_account_type"><?= i::__('Tipo de conta') ?>:</label>
            <span>{{entity.getHumanReadable('payment_account_type')}}</span>
        </div>

        <div class="col-4 field">
            <label class="field__title" :class="{'is-required': isRequired('payment_bank')}" for="payment_bank"><?= i::__('Banco') ?>:</label>
            <span>{{entity.getHumanReadable('payment_bank')}}</span>
        </div>

        <div class="col-6 field">
            <label class="field__title" :class="{'is-required': isRequired('payment_branch')}" for="payment_branch"><?= i::__('Agência sem o dígito') ?>:</label>
            <span>{{entity.payment_branch}}</span>
        </div>

        <div class="col-6 field">
            <label class="field__title" :class="{'is-required': isRequired('payment_branch_dv')}" for="payment_branch_dv"><?= i::__('Dígito verificador da agência') ?>:</label>
            <span>{{entity.payment_branch_dv}}</span>
        </div>

        <div class="col-6 field">
            <label class="field__title" :class="{'is-required': isRequired('payment_account')}" for="payment_account"><?= i::__('Conta sem o dígito') ?>:</label>
            <span>{{entity.payment_account}}</span>
        </div>

        <div class="col-6 field">
            <strong class="field__title" :class="{'is-required': isRequired('payment_account_dv')}" for="payment_account_dv"><?= i::__('Dígito verificador da conta') ?>:</strong>
            <span>{{entity.payment_account_dv}}</span>
        </div>
    </div>
</div>