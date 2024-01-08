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
    
    <template #title>
        <h3 class="bold"><?= i::__('Pagamentos') ?></h3>
    </template>

    <template #actions="{entities}">
        <div class="opportunity-payment-table__actions">
            <h4 class="bold"><?= i::__('Ações:') ?></h4>

            <div class="opportunity-payment-table__actions grid-12">
                <!-- <entity-files-list :entity="opportunity" group="export-financial-validator-files" title="" editable></entity-files-list> -->

                <create-payment class="col-4 sm:col-12" :entity="opportunity" :entities="entities"></create-payment>
                
                <payment-spreadsheet class="col-4 sm:col-12" :entity="opportunity"></payment-spreadsheet>
                
                <?php if($cnab_enabled($entity)):  ?>
                    <extraction-cnab class="col-4 sm:col-12" :entity="opportunity"></extraction-cnab>
                <?php endif ?>
            </div>
        </div>
    </template>

    <template #advanced-actions="{entities}">
        Opções avançadas vão aqui
    </template>

    <template #filters="{entities}">
        <div class="grid-12">
            <div class="field field--horizontal col-6 sm:col-12">
                <label><?= i::__('Data inicial')?></label>
                <input v-model="filters.paymentFrom" @change="change($event,entities)" type="date">
            </div>
            <div class="field field--horizontal col-6 sm:col-12">
                <label><?= i::__('Data final')?></label>
                <input v-model="filters.paymentTo" @change="change($event,entities)" type="date">
            </div>
        </div>
    </template>
    
    <template #advanced-filters="{entities}">
        <div class="field">
            <label><?= i::__('Status')?></label>
            <div class="field__group">
                <label class="field__checkbox" v-for="status in statusList">
                    <input :checked="filters.status?.includes(status.value)" type="checkbox" :value="status.value" @input="statusFilter($event,entities)"> {{status.label}} 
                </label>
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