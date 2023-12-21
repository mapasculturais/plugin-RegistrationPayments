<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-modal
');
?>

<div>
    <mc-modal title="<?= i::__('Exportar TXT CNAB') ?>">
        <div>
            <div>
                <div>
                    <label> <?= i::__('Identificação do lote de pagamento') ?></label>
                </div>
                <div>
                    <input v-model="cnabData.identifier" type="text">
                </div>
            </div>

            <div>
                <div>
                    <label><?= i::__('Data de pagamento') ?></label>
                </div>
                <div>
                    <input v-model="cnabData.paymentDate" type="date">
                </div>
            </div>

            <div>
                <div>
                    <label><?= i::__('Tipo de exportação') ?></label>
                </div>
                <div>
                    <select v-model="cnabData.lotType">
                        <option value="1"><?= i::__('Corrente BB') ?></option>
                        <option value="2"><?= i::__('Poupança BB') ?></option>
                        <option value="3"><?= i::__('Outros Bancos') ?></option>
                    </select>
                </div>
            </div>

            <div>
                <div>
                    <label><?= i::__('Filtrar somente as Inscrições') ?></label>
                </div>
                <div>
                    <textarea v-model="cnabData.registrationFilter"></textarea>
                </div>
            </div>

            <div>
                <div>
                    <input v-model="cnabData.ts_lot" type="checkbox">
                    <label><?= i::__('Exportar lote de teste') ?></label>
                </div>
            </div>
            <div>
                <small><?= i::__('OBS: caso a data de pagamento não for informada, sera inserido a data que esta cadastrada junto aos dados de pagamento') ?></small>
            </div>
        </div>

        <template #actions="modal">
            <button class="button button--primary" @click="exportCnab()"><?= i::__('Exportar') ?></button>
            <button class="button button--text button--text-del" @click="modal.close()"><?= i::__('cancelar') ?></button>
        </template>

        <template #button="modal">
            <button type="button" @click="modal.open()" class="button button--primary-outline"><?= i::__('Exportar TXT CNAB') ?></button>
        </template>
    </mc-modal>
</div>