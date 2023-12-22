<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;
use RegistrationPayments\Plugin;

$this->import('
    create-payment
    entity-table
    extraction-cnab
    mc-icon
');

$plugin = Plugin::getInstance();
$cnab_enabled = $plugin->config['cnab240_enabled'];
$entity = $this->controller->requestedEntity;
?>

<entity-table type="payment" :select="select" :query="query" :headers="headers" endpint required="registration,options,agent" visible="registration,paymentDate,amount,status,options">
    <template #actions-table="{entities}">
        <?php  if($cnab_enabled($entity)):  ?>
            <extraction-cnab :entity="opportunity"></extraction-cnab>
        <?php endif ?>

        <create-payment :entity="opportunity" :entities="entities"></create-payment>
    </template>

   <template #status="entity">
        <select v-model="entity.status" @change="setStatus(entity)">
            <template v-for="item in statusList">
                <option :value="item.value">{{item.label}}</option>
            </template>
        </select>
    </template>

    <template #amount="entity">
        {{amountToString(entity.amount)}}
    </template>

    <template #options="entity">
        <mc-icon name="edit"></mc-icon>
        <mc-icon name="delete"></mc-icon>
        <mc-icon name="history"></mc-icon>
    </template>

</entity-table>