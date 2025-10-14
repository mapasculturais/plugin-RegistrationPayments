<?php
if(!$this instanceof \MapasCulturais\Theme) {
    return;
}
$this->import('registration-payment-form');
?>
<registration-payment-form :entity="entity"></registration-payment-form>