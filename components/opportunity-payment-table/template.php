<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    create-payment
    entity-table
');
?>

<entity-table type="payment" :select="select" :query="query" :headers= "headers" endpint  required="registration" visible="registration,paymentDate,amount,status">
    <template #actions-table="{entities}" >
            <create-payment :entity="entity" :entities="entities"></create-payment>
    </template>
    
    <template #paymentDate="entity" >
            {{entity.paymentDate.date('numeric year')}}
    </template>

    <template #status="entity" >
            {{statusTostring(entity.status).label}}
    </template>

    <template #amount="entity" >
            {{amountToString(entity.amount)}}
    </template>
   
</entity-table>
