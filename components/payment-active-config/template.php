<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    payment-config
');
?>

<payment-config v-if="entity.active_payment_phase" :toggleConfigPayment="toggleConfigPayment" :entity="firstPhase"></payment-config>
<button v-if="!entity.active_payment_phase" class="button button--primary w-100" @click="toggleConfigPayment()"><?= i::__('Configurar pagamentos')?></button>