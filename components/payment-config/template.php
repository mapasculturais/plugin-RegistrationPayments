<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    opportunity-payment-table
    mc-confirm-button
')

?>

<li v-for="(step, index) in steps" :class="{active: step.active}" v-if="entity.has_payment_phase">
    <section class="stepper-step">
        <header :class="['stepper-header', {'open':step.active}]">
            <div class="stepper-header__content">
                <h3 class="info__title"><?= i::__('Configuração de pagamentos') ?></h3>
            </div>
            <a class="expand-stepper" v-if="step.active" @click="step.close()"><label><?= i::__('Diminuir') ?></label><mc-icon name="arrowPoint-up"></mc-icon></a>
            <a class="expand-stepper" v-if="!step.active" @click="step.open()"><label><?= i::__('Expandir') ?></label> <mc-icon name="arrowPoint-down"></mc-icon></a>  
        </header>
        <main class="stepper-main grid-12" v-if="step.active">
            <div class="phase-delete col-12">
                <mc-confirm-button message="<?= i::esc_attr__('Confirma a execução da ação?')?>" @confirm="deletePaymentPhase()">
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
