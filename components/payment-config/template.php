<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    entity-field
    opportunity-payment-table
    mc-card
    mc-confirm-button
')

?>

<div class="stepper-header__content">
    <h3 class="info__title"><?= i::__('Configuração de pagamentos') ?></h3>
    <div class="dates">
        <div class="date">
            <div class="date__title"> <?= i::__('Data de início') ?> </div>
            <div v-if="entity.payment_registration_from" class="date__content">{{entity.payment_registration_from.date('2-digit year')}} {{entity.payment_registration_from.time('numeric')}}</div>
        </div>
        <div class="date">
            <div class="date__title"> <?= i::__('Data final') ?> </div>
            <div v-if="entity.payment_registration_to" class="date__content">{{entity.payment_registration_to.date('2-digit year')}} {{entity.payment_registration_to.time('numeric')}}</div>
        </div>
    </div>
</div>
<article class="mc-card col-12">
    <div class="grid-12">
        <entity-field :entity="entity" prop="payment_registration_from" :autosave="3000" classes="col-6 sm:col-12"></entity-field>
        <entity-field :entity="entity" prop="payment_registration_to" :autosave="3000" classes="col-6 sm:col-12"></entity-field>
    </div><br />

    <h4 class="bold"><?= i::__('Dados da fonte pagadora') ?></h4><br>

    <div class="grid-12">
        <div class="col-4">
            <entity-field :entity="entity" prop="payment_company_data_name" :autosave="3000"></entity-field>
        </div>
        <div class="col-4">
            <entity-field :entity="entity" prop="payment_company_data_registration_type" :autosave="3000"></entity-field>
        </div>
        <div class="col-4">
            <entity-field :entity="entity" prop="payment_company_data_registration_number" :autosave="3000" :mask="documentMask()"></entity-field>
        </div>
    </div><br>

    <h4 class="bold"><?= i::__('Dados bancários da fonte pagadora') ?></h4><br>
    <div class="grid-12">
        <div class="col-6 field">
            <label class="field__title" for="payment_company_data_bank"><?= i::__('Banco') ?></label>
            <input id="payment_company_data_bank" value="Banco Do Brasil S.A (BB) - 1" type="text" autocomplete="off" disabled>
        </div>

        <div class="col-6">
            <entity-field :entity="entity" prop="payment_company_data_agreement" :autosave="3000"></entity-field>
        </div>

        <div class="col-6">
            <entity-field :entity="entity" prop="payment_company_data_branch" :autosave="3000"></entity-field>
        </div>

        <div class="col-6">
            <entity-field :entity="entity" prop="payment_company_data_branch_dv" :autosave="3000"></entity-field>
        </div>

        <div class="col-6">
            <entity-field :entity="entity" prop="payment_company_data_account" :autosave="3000"></entity-field>
        </div>

        <div class="col-6">
            <entity-field :entity="entity" prop="payment_company_data_account_dv" :autosave="3000"></entity-field>
        </div>
    </div>
</article>