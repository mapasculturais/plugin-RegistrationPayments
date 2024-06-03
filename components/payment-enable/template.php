<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

?>

<div class="payment-enable">
    <button v-if="!entity.has_payment_phase" class="button button--primary w-100" @click="active()"><?= i::__('Habilitar fase de pagamento') ?></button>
</div>