<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;


?>
<div class="item">
    <div v-if="isDataCollectionPhase(opportunity)">
        <mc-link :entity="registration" route="edit" class="button button--primary"><?= i::__('Preencher dados de pagamento') ?></mc-link>
    </div>
</div>