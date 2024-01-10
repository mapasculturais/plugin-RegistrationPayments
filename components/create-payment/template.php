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
<div class="create-payment">
    <mc-modal title="<?= i::__('Criar pagamentos:') ?>">
        <template #actions="modal">
            <button class="button button--primary" @click="save(modal)"><?= i::__('Salvar') ?></button>
            <button class="button button--text button--text-del" @click="modal.close()"><?= i::__('cancelar') ?></button>
        </template>

        <template #default>
            <div class="grid-12">
                <span v-if="hasErrors" class="col-12">
                    <p v-for="item in response?.data" class="field__error">* {{item}}</p>
                </span>

                <div class="col-12">
                    <div class="grid-12">
                        <div class="field col-4">
                            <label class="input__label input__radioLabel" for="registrations">
                                <input type="radio" v-model="payment.createType" id="registrations" name="fav_language" value="registration_id"> <?= i::__('Lista de inscrições') ?>
                            </label>                            
                        </div>                        
                        <div class="field col-4" v-if="categories.length > 0">
                            <label class="input__label input__radioLabel" for="category">
                                <input type="radio" v-model="payment.createType" id="category" name="fav_language" value="category"> <?= i::__('Categoria') ?>
                            </label>
                        </div>
                        <div class="field col-4">
                            <label class="input__label input__radioLabel" for="status">
                                <input type="radio" v-model="payment.createType" id="status" name="fav_language" value="registrationStatus"> <?= i::__('Status') ?>
                            </label>
                        </div>
                    </div>
                </div>

                <div v-if="payment.createType == 'registration_id'" class="field col-12">
                    <label for="regId"><?= i::__('Inscrições') ?></label>
                    <textarea v-model="payment.registration_id" name="regId" id="" cols="30" rows="5"></textarea>
                </div>

                <div :class="['field', {'col-12' : payment.createType !== 'registration_id'}, {'col-6' : payment.createType == 'registration_id'}]">
                    <label for="paymentDate"><?= i::__('Previsão de pagamento') ?></label>
                    <input v-model="payment.payment_date" name="paymentDate" type="date">
                </div>

                <div class="field col-6">
                    <label for="paymentAmount"><?= i::__('Valor') ?></label>
                    <span class="field__currence">
                        <span class="field__currence-sign">R$</span>
                        <input name="paymentAmount" v-model="payment.amount" v-maska data-maska="9 99#,##" data-maska-tokens="9:[0-9]:repeated" data-maska-reversed type="text">
                    </span>
                </div>

                <div v-if="payment.createType == 'registrationStatus'" class="field col-6">
                    <label for="paymentStatus"><?= i::__('Status') ?></label>
                    <select v-model="payment.registrationStatus" name="paymentStatus">
                        <option v-for="item in status" :value="item.value">{{item.label}}</option>
                    </select>
                </div>

                <div v-if="payment.createType == 'category'" class="field col-6">
                    <label for="paymentCategory"><?= i::__('Categorias') ?></label>
                    <select v-model="payment.category" name="paymentCategory">
                        <option v-for="category in categories" :value="category">{{category}}</option>
                    </select>
                </div>

                <div class="field col-12">
                    <label for="obs"><?= i::__('Observações') ?></label>
                    <textarea v-model="payment.metadata.csv_line.OBSERVACOES" name="obs" id="" cols="30" rows="5"></textarea>
                </div>
            </div>
        </template>

        <template #button="modal">
            <button type="button" @click="modal.open()" class="button button--primary button--icon button--large"><mc-icon name="add"></mc-icon><?= i::__('Adicionar pagamento') ?></button>
        </template>
    </mc-modal>
</div>