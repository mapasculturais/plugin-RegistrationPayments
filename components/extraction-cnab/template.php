<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-modal
    mc-icon
');
?>

<div class="extraction-cnab">
    <mc-modal title="<?= i::__('Exportar TXT CNAB') ?>">
        <div class="grid-12 extraction-cnab__content">
            <span v-if="hasErrors" class="col-12">
                <p v-for="item in response?.data" class="field__error">* {{item}}</p>
            </span>

            <div class="field col-12" :class="{'error': fieldError('identifier')}">
                <label> <?= i::__('Identificação do lote de pagamento') ?> <span class="required">* <?= i::__('Obrigatório') ?></span></label>
                <input v-model="cnabData.identifier" type="text" >
            </div>

            <div class="field col-6">
                <label><?= i::__('Data de pagamento') ?></label>
                <input v-model="cnabData.paymentDate" type="date">
            </div>

            <div class="field col-6" :class="{'error': fieldError('lotType')}">
                <label><?= i::__('Tipo de exportação') ?> <span class="required">* <?= i::__('Obrigatório') ?></span></label>
                <mc-select :default-value="cnabData.lotType" @change-option="setCnabType" class="col-6" :class="{'error': fieldError('lotType')}">
                    <option value="1"><?= i::__('Corrente BB') ?></option>
                    <option value="2"><?= i::__('Poupança BB') ?></option>
                    <option value="3"><?= i::__('Outros Bancos') ?></option>
                </mc-select>
            </div>

            <div class="field col-12">
                <label><?= i::__('Filtrar somente as Inscrições') ?></label>
                <textarea v-model="cnabData.registrationFilter"></textarea>
            </div>

            <div class="field col-12">
                <label>
                    <input v-model="cnabData.ts_lot" type="checkbox">
                    <?= i::__('Exportar lote de teste') ?>
                </label>
            </div>

            <div class="col-12">
                <small><?= i::__('OBS: caso a data de pagamento não for informada, sera inserido a data que esta cadastrada junto aos dados de pagamento') ?></small>
            </div>
        </div>

        <template #actions="modal">
            <button class="button button--primary" @click="exportCnab()"><?= i::__('Exportar') ?></button>
            <button class="button button--text button--text-del" @click="modal.close()"><?= i::__('cancelar') ?></button>
        </template>

        <template #button="modal">
            <button type="button" @click="modal.open()" class="button button--primary button--icon button--large"><?= i::__('Extrair CNAB') ?> <mc-icon name="download"></mc-icon></button>
        </template>
    </mc-modal>
</div>