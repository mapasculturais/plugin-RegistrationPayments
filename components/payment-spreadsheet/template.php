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
            <span v-if="hasErrors" class="col-12">
                <p v-for="item in response?.data" class="field__error">* {{ item }}</p>
            </span>
            <div>
                <label><?= i::__('Inscrições com data de envio inicial') ?></label>
                <input type="date" v-model="dataExport.from">
            </div>

            <div>
                <label><?= i::__('Inscrições com data de envio Final') ?></label>
                <input type="date" v-model="dataExport.to">
            </div>
            <small><?= i::__("# Caso não queira filtrar entre datas, deixe os campos vazios.") ?></small>
        </template>

        <template #button="modal">
            <button class="button button--primary button--icon" @click="modal.open()"><?= i::__('Pagamento via planilha') ?> <mc-icon name="external"></mc-icon></button>
        </template>
    </mc-modal>
</div>