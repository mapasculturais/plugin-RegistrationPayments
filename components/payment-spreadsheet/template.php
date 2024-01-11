<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    entity-file
    mc-icon
    mc-modal
');
?>
<div class="create-payment">
    <mc-modal title="<?= i::__('Criar pagamentos via planilha:') ?>">
        <template #actions="modal">
            <button class="button button--primary" @click="exportValidator(modal)"><?= i::__('Baixar validador') ?></button>
            <entity-file :entity="entity" groupName="payment-financial-validador" editable disableName></entity-file>
        </template>

        <template #default>
            <div class="grid-12">
                <span v-if="hasErrors" class="col-12">
                    <p v-for="item in response?.data" class="field__error">* {{ item }}</p>
                </span>
                
                <div class="field col-12">
                    <label><?= i::__('Inscrições com data de envio inicial') ?></label>
                    <input type="date" v-model="dataExport.from">
                </div>

                <div class="field col-12">
                    <label><?= i::__('Inscrições com data de envio Final') ?></label>
                    <input type="date" v-model="dataExport.to">
                </div>

                <small class="col-12"><?= i::__("# Caso não queira filtrar entre datas, deixe os campos vazios.") ?></small>
            </div>
        </template>

        <template #button="modal">
            <button class="button button--primary button--icon button--large" @click="modal.open()"><?= i::__('Pagamento via planilha') ?> <mc-icon name="external"></mc-icon></button>
        </template>
    </mc-modal>
</div>