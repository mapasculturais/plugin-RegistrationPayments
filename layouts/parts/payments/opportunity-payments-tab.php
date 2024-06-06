<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;
use RegistrationPayments\Plugin;

$this->import('
    mc-tab
    opportunity-payment-table
');
?>

<!-- <template #tabs-alert>
    <mc-alert type="danger">
        <?php //i::__('Dados bancários ainda não foram configurados.') ?>
    </mc-alert>
</template> -->

<mc-tab v-if="entity.has_payment_phase" label="<?= i::__('Pagamentos') ?>" slug="payment">
    <opportunity-payment-table :opportunity="entity"></opportunity-payment-table>
</mc-tab>

