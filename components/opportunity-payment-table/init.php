<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

$entity = $this->controller->requestedEntity;

$result = [
   'phasesIds' => [],
];
foreach ($entity->firstPhase->allPhases as $phase) {
   $result['phasesIds'][] = $phase->id;
}

$this->jsObject['config']['opportunityPaymentTable'] = $result;
