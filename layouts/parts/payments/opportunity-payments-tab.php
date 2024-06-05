<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */


$this->import('
   payment-tab
');
?>

<!-- <template #tabs-alert>
    <mc-alert type="danger">
        <?php //i::__('Dados bancários ainda não foram configurados.') ?>
    </mc-alert>
</template> -->

<payment-tab :entity="entity"></payment-tab>
