<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    entity-table
');
?>

<entity-table type="payment" :select="select" :query="query" :headers= "headers" endpint  required="registration" visible="registration,paymentDate,amount,status" >
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
