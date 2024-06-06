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

<li v-if="global.mcTabActive == 'config' " v-for="(step, index) in steps" :class="{active: step.active}" v-if="entity.has_payment_phase">
    <section class="stepper-step">
        <header :class="['stepper-header', {'open':step.active}]">
            <div class="stepper-header__content">
                <h3 class="info__title"><?= i::__('Configuração de pagamentos') ?></h3>
            </div>
            <a class="expand-stepper" v-if="step.active" @click="step.close()"><label><?= i::__('Diminuir') ?></label><mc-icon name="arrowPoint-up"></mc-icon></a>
            <a class="expand-stepper" v-if="!step.active" @click="step.open()"><label><?= i::__('Expandir') ?></label> <mc-icon name="arrowPoint-down"></mc-icon></a>
        </header>
        <main class="stepper-main grid-12" v-if="step.active">
            <article class="mc-card col-12">
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
            <div class="phase-delete col-12">

                <mc-confirm-button message="<?= i::esc_attr__('Confirma a execução da ação?') ?>" @confirm="deletePaymentPhase()">
                    <template #button="modal">
                        <button class="phase-delete__trash button button--text button--sm" @click="modal.open()">
                            <div class="icon">
                                <mc-icon name="trash" class="secondary__color"></mc-icon>
                            </div>
                            <h5><?= i::__("Excluir fase de pagamentos") ?></h5>
                        </button>
                    </template>
                </mc-confirm-button>
            </div>
        </main>
    </section>
</li>