<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-modal
    mc-icon
    mc-select
');
?>
<div class="create-payment">
    <mc-modal title="<?= i::__('Criar pagamentos:') ?>">
        <template #actions="modal">
            <button class="button button--primary" @click="save(modal)"><?= i::__('Salvar') ?></button>
            <button class="button button--text button--text-del" @click="modal.close()"><?= i::__('cancelar') ?></button>
        </template>

        <template #default>
            <div class="create-payment__modal-content">
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
                        <textarea v-model="payment.registration_id" name="regId" id="" cols="30" rows="5" placeholder="<?= i::__('Informe a lista de ID\'s das inscrições que deseja criar o pagamento, separadas por vírgula, ou um ID em cada linha') ?>"></textarea>
                    </div>

                    <div v-if="payment.createType == 'registrationStatus'" class="field col-12">
                        <label for="paymentStatus"><?= i::__('Status') ?></label>

                        <mc-select :default-value="payment.registrationStatus" placeholder="<?= i::__('Selecione o status das inscrições que deseja criar o pagamento') ?>" @change-option="setRegistrationsStatus">
                            <mc-status v-for="item in registrationStatus" :value="item.value" :status-name="item.label"></mc-status>
                        </mc-select>
                    </div>

                    <div v-if="payment.createType == 'category'" class="field col-12">
                        <label for="paymentCategory"><?= i::__('Categorias') ?></label>
                        <mc-select :default-value="payment.category" placeholder="<?= i::__('Selecione a categoria das inscrições que deseja criar o pagamento') ?>" @change-option="setCategory">
                            <option v-for="category in categories" :value="category">{{category}}</option>
                        </mc-select>
                    </div>

                    <div :class="['field', {'col-12' : payment.createType !== 'registration_id'}, {'col-12' : payment.createType == 'registration_id'}]">
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
        
                    <div class="field col-6">
                        <label for="status"><?= i::__('Status') ?></label>
                        <mc-select :default-value="payment.status" placeholder="Selecione um status" @change-option="setPaymentStatus">
                            <mc-status v-for="item in statusList" :value="item.value" :status-name="item.label"></mc-status>
                        </mc-select>
                    </div>

                    <div class="field col-12">
                        <label for="obs"><?= i::__('Observações') ?></label>
                        <textarea v-model="payment.metadata.csv_line.OBSERVACOES" name="obs" id="" cols="30" rows="5"></textarea>
                    </div>
                </div>
            </div>
        </template>

        <template #button="modal">
            <button type="button" @click="modal.open()" class="button button--primary button--icon button--large"><mc-icon name="add"></mc-icon><?= i::__('Adicionar pagamento') ?></button>
        </template>
    </mc-modal>
</div>