<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;
use RegistrationPayments\Payment;

$status = [
    ['value' => Payment::STATUS_PENDING, 'label' => i::__("Pendente")],
    ['value' => Payment::STATUS_PROCESSING, 'label' => i::__("Em processo")],
    ['value' => Payment::STATUS_FAILED, 'label' => i::__("Falha")],
    ['value' => Payment::STATUS_EXPORTED, 'label' => i::__("Exportado")],
    ['value' => Payment::STATUS_AVAILABLE, 'label' => i::__("Disponível")],
    ['value' => Payment::STATUS_PAID, 'label' => i::__("Pago")],
];

$this->jsObject['config']['createPayment']['statusDic'] = $status;
