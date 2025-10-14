<?php
if(!$this instanceof \MapasCulturais\Theme) {
    return;
}
?>

<registration-payment-form :step="step" :entity="registration"></registration-payment-form>