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
    mc-modal
    payment-spreadsheet
');

$plugin = Plugin::getInstance();
$cnab_enabled = $plugin->config['cnab240_enabled'];
$entity = $this->controller->requestedEntity;
?>

<entity-table type="payment" :select="select" :query="query" :headers="headers" endpint required="registration,options" visible="registration,paymentDate,amount,status,options">
    <template #actions-primary="{entities}">
        <create-payment :entity="opportunity" :entities="entities"></create-payment>
    </template>

    <template #actions-secondary="{entities}">
        <button class="button button--primary button--icon"><?= i::__('Criar pagamento via planilha') ?> <mc-icon name="external"></mc-icon></button>
        <?php if($cnab_enabled($entity)):  ?>
            <extraction-cnab :entity="opportunity"></extraction-cnab>
        <?php endif ?>
    </template>

    <template #table-filters="{entities}">
        <div class="opportunity-payment-table__filters">
            <h4 class="bold"><?= i::__('Filtrar:') ?></h4>
        </div>
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
        <mc-modal button-label="abrir" :title="'<?= i::__('Editar pagamento da inscrição') ?> ' + entity.registration.number">
            <div>
                <label><?= i::__('Inscrições') ?></label>
                <textarea v-model="entity.registration_id" name="" id="" cols="30" rows="10"></textarea>
            </div>

            <div>
                <label><?= i::__('Previsão de pagamento') ?></label>
                <input v-model="entity.paymentDate" type="date">
            </div>

            <div>
                <label><?= i::__('Valor') ?></label>
                R$<input v-model="entity.amount" v-maska data-maska="9 99#,##" data-maska-tokens="9:[0-9]:repeated" data-maska-reversed type="text">
            </div>
            <div>
                <label><?= i::__('Observações') ?></label>
                <textarea v-model="entity.metadata.csv_line.OBSERVACOES" name="" id="" cols="30" rows="10"></textarea>
            </div>

            <template #button={open}>
                <mc-icon name="edit" @click="open()"></mc-icon>
            </template>

            <template #actions="modal">
                <button @click="doSomething(modal)"><?= i::__('Salvar') ?></button>
                <button @click="modal.close()"><?= i::__('Cancelar') ?></button>
            </template>
        </mc-modal>
        <mc-icon name="delete"></mc-icon>
    </template>

</entity-table>