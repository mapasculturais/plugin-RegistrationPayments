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
        <?= i::__('Dados de pagamento') ?>
    </div>
    <div class="item__content--description">
        <button class="button button--primary" @click="changeTab"><?= i::__('Inserir dados de pagamento') ?></button>
    </div>
</div>
