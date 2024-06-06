<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

$this->import('
    payment-config
')
?>

<payment-config v-if="global.mcTabActive === 'config'" :entity="items[0]"></payment-config>