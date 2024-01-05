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
    entity-files-list
    extraction-cnab
    payment-spreadsheet
    mc-icon
    mc-modal
');

$plugin = Plugin::getInstance();
$cnab_enabled = $plugin->config['cnab240_enabled'];
$entity = $this->controller->requestedEntity;
$url = $app->createUrl('payment', 'export');
?>

<entity-table type="payment" :select="select" :query="query" :headers="headers" endpint required="registration,options" visible="registration,paymentDate,amount,status,options">
    <template #actions-primary="{entities}">
        <payment-spreadsheet :entity="opportunity"></payment-spreadsheet>
        <entity-files-list :entity="opportunity" group="export-financial-validator-files" title="" editable></entity-files-list>
        <create-payment :entity="opportunity" :entities="entities"></create-payment>
    </template>

    <template #actions-secondary="{entities}">
        <?php if($cnab_enabled($entity)):  ?>
            <extraction-cnab :entity="opportunity"></extraction-cnab>
        <?php endif ?>
    </template>

    <template #table-filters="{entities}">
        <div class="opportunity-payment-table__filters">
            <h4 class="bold"><?= i::__('Filtrar:') ?></h4>
            <div class="grid-12">
                <div class="field col-4">
                    <label><?= i::__('Data inicial')?></label>
                    <input v-model="filters.paymentFrom" @change="change($event,entities)" type="date">
                </div>
                <div class="field col-4">
                    <label><?= i::__('Data final')?></label>
                    <input v-model="filters.paymentTo" @change="change($event,entities)" type="date">
                </div>
                <div class="field col-3">
                    <label><?= i::__('Status')?></label>
                    <select v-model="filters.status" @change="change($event,entities)">
                        <option value=""><?= i::__('Selecione')?></option>
                        <template v-for="status in statusList">
                            <option :value="status.value">{{status.label}}</option>
                        </template>
                    </select>
                </div>
            </div>
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
        <div class="opportunity-payment-table__table-actions">
            <mc-modal button-label="abrir" :title="'<?= i::__('Editar pagamento da inscrição') ?> ' + entity.registration.number">

                <div class="grid-12">
                    <div class="field col-12">
                        <label> <?= i::__('Inscrições') ?></label>
                        <textarea v-model="entity.registration_id" name="" id="" cols="30" rows="5"></textarea>
                    </div>

                    <div class="field col-12">
                        <label><?= i::__('Previsão de pagamento') ?></label>
                        <input v-model="entity.paymentDate" type="date">
                    </div>

                    <div class="field col-12">
                        <label><?= i::__('Valor') ?></label>
                        <span class="field__currence">
                            <span class="field__currence-sign">R$</span>
                            <input v-model="entity.amount" v-maska data-maska="9 99#,##" data-maska-tokens="9:[0-9]:repeated" data-maska-reversed type="text">
                        </span> 
                    </div>

                    <div class="field col-12">
                        <label><?= i::__('Observações') ?></label>
                        <textarea v-model="entity.metadata.csv_line.OBSERVACOES" name="" id="" cols="30" rows="5"></textarea>
                    </div>
                </div>


                <template #button={open}>
                    <mc-icon name="edit" @click="open()"></mc-icon>
                </template>

                <template #actions="modal">
                    <button class="button button" @click="modal.close()"><?= i::__('Cancelar') ?></button>
                    <button class="button button--primary" @click="doSomething(modal)"><?= i::__('Salvar') ?></button>
                </template>
            </mc-modal>
            <mc-icon name="trash"></mc-icon>
        </div>
    </template>

</entity-table>