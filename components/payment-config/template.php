<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    opportunity-payment-table
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
        <main class="stepper-main" v-if="step.active">
            <opportunity-payment-table :opportunity="entity"></opportunity-payment-table>
        </main>
    </section>
</li>
