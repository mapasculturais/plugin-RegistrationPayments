<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

$opportunity = $this->controller->requestedEntity;

$this->jsObject['config']['paymentConfig'] = [
    'opportunityId' => $opportunity->id,
];
