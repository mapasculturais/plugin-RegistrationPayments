<?php

use RegistrationPayments\Plugin;

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */


$entity = $this->controller->requestedEntity;

if (isset($this->controller->data['user']) && $entity->opportunity->canUser("@control")) {
  $user = $app->repo("User")->find($this->controller->data['user']);
} else {
  $user = $app->user;
}

$evaluation_type =  null;
if ($em = $entity->getEvaluationMethod()) {
  $evaluation_type = $em->slug;
}

$paymentData = $entity->firstPhase->simplify("id,payment_social_type,payment_proponent_name,payment_proponent_document,payment_account_type,payment_bank,payment_branch,payment_branch_dv,payment_account,payment_account_dv,payment_sent_timestamp");
$opportunity = $entity->firstPhase->opportunity->simplify('id,active_payment_phase,payment_step_form,has_payment_phase');

$this->jsObject['config']['registrationPaymentForm'] = [
  'paymentData' => $paymentData,
  'opportunity' => $opportunity,
  'evaluationType' => $evaluation_type,
  'currentEvaluation' => $entity->getUserEvaluation($user),
];
