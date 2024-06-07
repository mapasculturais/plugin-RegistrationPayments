<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;


?>
<div class="item">
    <div class="item__dot"> <span class="dot"></span> </div>
    <div class="item__content--title">
        <div>
            <?= i::__('Dados de pagamento') ?>
        </div>
        <div>
            
            <button v-if="!isOpportunity && isPaymentDataOpen()" class="button button--primary" @click="changeTab"><?= i::__('Inserir dados de pagamento') ?></button>
        </div>
    </div>
</div>
