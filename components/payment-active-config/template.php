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

<payment-config v-if="entity.active_payment_phase" :entity="firstPhase"></payment-config>
<button v-if="!entity.active_payment_phase" class="button button--primary w-100" @click="configPayment()"><?= i::__('Configurar pagamentos')?></button>
<button v-if="entity.active_payment_phase" class="button button--primary w-100" @click="configPayment()"><?= i::__('Desativar configuração de pagamentos')?></button>