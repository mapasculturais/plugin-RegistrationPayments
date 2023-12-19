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
    <mc-modal title="<?= i::__('Criar pagamentos:') ?>">
        <span v-if="hasErrors">
            <template v-for="item in response?.data">
                <p class="field__error">* {{item}}</p>
            </template>
        </span>

        <template #actions="modal">
            <button class="button button--primary" @click="save(modal)"><?= i::__('Salvar') ?></button>
            <button class="button button--text button--text-del" @click="modal.close()"><?= i::__('cancelar') ?></button>
        </template>
        <div>
            <div>
                <label><?= i::__('Inscrições') ?></label>
            </div>
            <div>
                <textarea v-model="payment.registration_id" name="" id="" cols="30" rows="10"></textarea>
            </div>
        </div>

        <div>
            <div>
                <div>
                    <label><?= i::__('Previsão de pagamento') ?></label>
                </div>
                <div>
                    <input v-model="payment.payment_date" type="date">
                </div>
            </div>
            <div>
                <div>
                    <label><?= i::__('Valor') ?></label>
                </div>
                <div>
                    R$<input v-model="payment.amount" v-maska data-maska="9 99#,##" data-maska-tokens="9:[0-9]:repeated" data-maska-reversed type="text">
                </div>
            </div>
            <div>
                <div>
                    <label><?= i::__('Status') ?></label>

                </div>
                <div>

                    <select>
                        <option v-for="item in status" :value="item.value">{{item.label}}</option>
                    </select>
                </div>
            </div>
            <div>
                <div>
                    <div>
                        <label><?= i::__('Observações') ?></label>
                    </div>
                    <div>
                        <textarea v-model="payment.metadata.csv_line.OBSERVACOES" name="" id="" cols="30" rows="10"></textarea>
                    </div>
                </div>
            </div>
        </div>

        <template #button="modal">
            <button type="button" @click="modal.open()" class="button button--primary-outline"><?= i::__('Adicionar pagamento') ?></button>
        </template>
    </mc-modal>
</div>