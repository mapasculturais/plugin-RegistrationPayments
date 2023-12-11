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
<div class="grid-12">
    <div class="col-12">
        <entity-field :entity="entity" prop="paymentsTabEnabled"></entity-field>
    </div>
</div>