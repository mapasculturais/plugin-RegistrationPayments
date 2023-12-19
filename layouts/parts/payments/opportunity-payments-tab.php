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

$plugin = Plugin::getInstance();
$cnab_enabled = $plugin->config['cnab240_enabled'];
?>

<div class="payment-tab__container">
    <mc-tab label="<?= i::__('Pagamentos') ?>" slug="payment">
        <?php  if($cnab_enabled($entity)):  ?>
            <!-- Componente do botão CNAB -->
        <?php endif ?>
        <opportunity-payment-table :entity="entity"></opportunity-payment-table>
    </mc-tab>
</div>