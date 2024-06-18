<?php

use RegistrationPayments\Plugin;

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */


 $entity = $this->controller->requestedEntity;


 $paymentData = $entity->firstPhase->simplify("id,payment_social_type,payment_proponent_name,payment_proponent_document,payment_account_type,payment_bank,payment_branch,payment_branch_dv,payment_account,payment_account_dv,payment_sent_timestamp");
 $opportunity = $entity->opportunity->firstPhase->simplify('id,payment_registration_from,payment_registration_to');

 $this->jsObject['config']['registrationPaymentTab'] = [
    'paymentData' => $paymentData,
    'opportunity' => $opportunity
 ];