<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    entity-field
    mc-card
');
?>
<div class="col-12">
    <div class="grid-12">
        <entity-field classes="col-6 sm:col-12" :entity="entity" prop="paymentsTabEnabled"></entity-field>
        <entity-field v-if="entity.paymentsTabEnabled == '1'" classes="col-6 sm:col-12" :entity="entity" prop="paymentCnabEnabled"></entity-field>
    </div>
</div>