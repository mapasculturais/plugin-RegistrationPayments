<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

$this->import('
    payment-active-config
');
?>

<payment-active-config :entity="phase" :phases="phases"></payment-active-config>