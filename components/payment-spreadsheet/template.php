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
    mc-file
');
?>
<div class="create-payment">
    <mc-modal title="<?= i::__('Criar pagamentos via planilha') ?>">
        <template #actions="modal">
            <button class="button button--primary button--icon" @click="exportValidator(modal)">
                <?= i::__('Baixar validador') ?> <mc-icon name="download"></mc-icon>
            </button>

            <button class="button button--primary--button button--icon" @click="upload()">
                <?= i::__('Enviar') ?> <mc-icon name="upload"></mc-icon>
            </button>

            <button v-if="process.active" class="button button--primary--button button--icon" @click="processFile(modal)">
                <?= i::__('Processar') ?> <mc-icon name="process"></mc-icon>
            </button>
        </template>

        <template #default>
            <div class="create-payment__modal-content">
                <div class="grid-12">
                    <span v-if="hasErrors" class="field col-12">
                        <small v-for="item in response?.data" class="field__error">* {{ item }}</small>
                    </span>
                    <div class="create-payment__filters col-12">
                        
                        <div class="create-payment__filter-field field field--horizontal">
                            <label><?= i::__('Inscrições com data de envio inicial') ?></label>
                            <input type="date" name="envioInicial" v-model="dataExport.from">
                        </div>
        
                        <div class="create-payment__filter-field field field--horizontal">
                            <label><?= i::__('Inscrições com data de envio Final') ?></label>
                            <input type="date" name="envioFinal" v-model="dataExport.to">
                        </div>

                        <small class="create-payment__note"><?= i::__("*Caso não queira filtrar entre datas, deixe os campos vazios.") ?></small>
                    </div>

                    <mc-file @file-selected="setFile" class="col-12"></mc-file>
                </div>
            </div>
        </template>

        <template #button="modal">
            <button class="button button--primary button--icon button--large" @click="modal.open()"><?= i::__('Pagamento via planilha') ?> <mc-icon name="external"></mc-icon></button>
        </template>
    </mc-modal>
</div>