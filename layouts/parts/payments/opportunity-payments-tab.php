<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-tab
    create-payment
');
?>

<mc-tab label="<?= i::__('Pagamentos') ?>" slug="payment">
    <create-payment :entity="entity"></create-payment>
</mc-tab>