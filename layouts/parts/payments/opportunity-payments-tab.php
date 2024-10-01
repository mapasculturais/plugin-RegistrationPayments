<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

$entity = $this->controller->requestedEntity;

$use_payment = false;
if($all_phases = $entity->allPhases) {
    foreach($all_phases as $phase) {
        if($phase->active_payment_phase) {
            $use_payment = true;
            break;
        }
    }
}
use MapasCulturais\i;
use RegistrationPayments\Plugin;

$this->import('
    opportunity-payment-table
');
?>

<opportunity-payment-table use-payment="<?=$use_payment?>" :opportunity="entity"></opportunity-payment-table>


