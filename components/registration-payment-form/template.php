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
<div v-if="showForm()" ref="paymentForm" :class="'payment-form evaluation-'+evaluationClass">
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
        <div class="col-6">
            <entity-field :entity="entity" prop="payment_proponent_name" disabled></entity-field>
        </div>

        <div class="col-6">
            <entity-field :entity="entity" prop="payment_proponent_document" disabled></entity-field>
        </div>

        <div class="col-4">
            <entity-field :entity="entity" prop="payment_social_type" disabled></entity-field>
        </div>

        <div class="col-4">
            <entity-field :entity="entity" prop="payment_account_type" disabled></entity-field>
        </div>

        <div class="col-4">
            <entity-field :entity="entity" prop="payment_bank" disabled></entity-field>
        </div>

        <div class="col-6">
            <entity-field :entity="entity" prop="payment_branch" disabled></entity-field>
        </div>

        <div class="col-6">
            <entity-field :entity="entity" prop="payment_branch_dv" disabled></entity-field>
        </div>

        <div class="col-6">
            <entity-field :entity="entity" prop="payment_account" disabled></entity-field>
        </div>

        <div class="col-6">
            <entity-field :entity="entity" prop="payment_account_dv" disabled></entity-field>
        </div>
    </div>
</div>