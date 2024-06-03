<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

$this->import('
    payment-enable
')
?>

<payment-enable :entity="items[0]"></payment-enable>