<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;


?>
<div class="item">
    <div class="item__dot"> <span class="dot"></span> </div>
    <div class="item__content">
        <div class="item__content--title">
            <?= i::__('Dados de pagamento') ?>
        </div>
        <div class="item__content--description">
            <h5 class="semibold"> 
                <?= i::__('de') ?> 
                <span>{{opportunity.payment_registration_from.date('numeric year')}} {{opportunity.payment_registration_from.time('numeric')}}</span>  
                <?= i::__('a') ?>
                <span>{{opportunity.payment_registration_to.date('numeric year')}} {{opportunity.payment_registration_to.time('numeric')}}</span>  
        </div>

        <div>
            <button v-if="!isOpportunity && isPaymentDataOpen()" class="button button--primary" @click="changeTab"><?= i::__('Inserir dados de pagamento') ?></button>
        </div>
    </div>
</div>