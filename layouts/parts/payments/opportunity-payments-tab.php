<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-tab
    opportunity-payment-table
');
?>
<div class="payment-tab__container">
    <mc-tab label="<?= i::__('Pagamentos') ?>" slug="payment">
        <opportunity-payment-table :entity="entity"></opportunity-payment-table>
    </mc-tab>
</div>