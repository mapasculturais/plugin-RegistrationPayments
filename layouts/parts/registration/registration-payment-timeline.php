<?php
$this->import('registration-payment-timeline');
?>

<registration-payment-timeline :opportunity="phases[0]"  :isOpportunity="<?= json_encode($isOpportunity) ?>"></registration-payment-timeline>