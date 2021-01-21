<?php

namespace RegistrationPayments;

use MapasCulturais\i;

?>

<div ng-controller='RegistrationPayments'>    
    <header id="header-inscritos" class="clearfix">
        <h3><?php i::_e("Pagamentos"); ?></h3>
    </header>
    <div id="payments-filter">
        <div class="left">
            <span class="label"> <?php i::_e("Filtrar inscrição:"); ?> </span>
            <input ng-model="data.search" id="search" ng-keyup="search()" placeholder="<?php i::_e("Busque pelo número de inscrição"); ?>" class="ng-pristine ng-untouched ng-valid ng-empty"> <br>
        </div>
        <div class="right">
            <span class="label"><?php i::_e("Filtrar por data:"); ?></span>
            <input type="date" ng-model="data.filterDate" placeholder="<?php i::_e("Busque pela data do pagamento"); ?>">
        </div>
    </div>

      <!-- Modal de pagamentos em massa -->
    <div ng-class="{hidden:!data.multiplePayments}" class="payment-modal payment-modal-div hidden">
        <header>
            <h2 class="payment-modal-title"><?php i::_e("Editar pagamentos:"); ?></h2>
        </header>
        
        <div class="fields">
            
            <div>
                <label ng-model="data.editMultiplePayments.payment_date"><?php i::_e("Data"); ?></label>
                <input type="date" ng-model="data.editMultiplePayments.payment_date" value="" />
            </div>

            <div>
                <label ng-model="data.editMultiplePayments.amount"><?php i::_e("Valor"); ?></label>
                <div>
                    <span class="prefix"><?php i::_e("R$"); ?></span> <input type="text" ng-model="data.editMultiplePayments.amount" placeholder="<?php i::_e("Ex.: 3.000,00"); ?>" js-mask="###.###.###.###.##0,00"/>
                </div>
            </div>

            <div>
                <label ng-model="data.editMultiplePayments.status"><?php i::_e("Status"); ?></label>
                <select ng-model="data.editMultiplePayments.status">
                    <option value=""><?php i::_e("Selecione"); ?></option>
                    <option value="0" ng-selected="data.editMultiplePayments.status === 0"><?php i::_e("Pendente"); ?></option>
                    <option value="1" ng-selected="data.editMultiplePayments.status === 1"><?php i::_e("Em processo"); ?></option>
                    <option value="2" ng-selected="data.editMultiplePayments.status === 2"><?php i::_e("Falha"); ?></option>
                    <option value="3" ng-selected="data.editMultiplePayments.status === 3"><?php i::_e("Exportado"); ?></option>
                    <option value="8" ng-selected="data.editMultiplePayments.status === 8"><?php i::_e("Disponível"); ?></option>
                    <option value="10" ng-selected="data.editMultiplePayments.status === 10"><?php i::_e("Pago"); ?></option>
                </select>
            </div>
        </div>
        
        <div>
            <?php $this->applyTemplateHook('payment-edit-multiple-modal-metadata','begin'); ?>
            <?php $this->applyTemplateHook('payment-edit-multiple-modal-metadata','end'); ?>
        </div>
        <footer>
            <div ng-if="data.editingPayments > 0">
                <img src="<?php $this->asset('img/spinner_192.gif')?>" width="48">
            </div>  
            <div ng-if="data.editingPayments == 0">
                <button class="btn btn-default" ng-click="data.multiplePayments = false;" class="js-close"><?php i::_e("Cancelar"); ?></button>
                <button class="btn btn-primary" ng-click="updateSelectedPayments();" class="js-close" ><?php i::_e("Editar seleção"); ?></button>
            </div>
        </footer>
             
    </div>

    <!-- Tabela de pagamentos -->    
    <table class="js-registration-list registrations-table">
        <thead>
            <tr> 
                <th class="registration-id-col">
                    <?php i::_e("Selecionar"); ?>
                </th>

                <th class="registration-id-col">
                    <?php i::_e("Inscrição"); ?>
                </th>

                <th class="registration-payment-date-col">
                    <?php i::_e("Previsão de pagamento"); ?>
                </th>

                <th class="registration-amount-col">
                    <?php i::_e("Valor"); ?>
                </th>

                <th class="registration-status-col">                                 
                    <mc-select placeholder="<?php i::_e("Status"); ?>"  model="data.statusFilter[value]" data="data.statusFilter"></mc-select>
                </th>

                <th class="registration-status-col">
                    <?php i::_e("Ações"); ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan='6'>                 
                    <span ng-if="data.apiMetadata.count === 0"><?php i::_e("Nenhum pagamento registrado."); ?></span>
                    <span ng-if="data.apiMetadata.count  === 1"><?php i::_e("1 pagamento encontrado."); ?></span>
                    <span ng-if="data.apiMetadata.count  > 1">{{data.payments.length}}
                        <span ng-if="data.apiMetadata.count > 0">
                            <i><?php i::_e("de"); ?> {{ data.apiMetadata.count }}</i>
                        </span>
                        <?php i::_e("Pagamentos"); ?>
                    </span>
                </td>                
            </tr>

            <tr>
                <td>
                    <span ng-if="data.payments.length > 0"><input type="checkbox" ng-click="selectAll()" title="<?php i::_e("Selecionar todos."); ?>"></span>
                </td>
                <td colspan='5'>
                    <span ng-if="getSelectedPayments().length > 0" class="outher-actions"> 
                        <a  ng-click="data.multiplePayments = true" title="<?php i::_e("Editar pagamentos"); ?>"><i class="fas fa-edit"></i></a>
                        <a ng-click="deleteSelectedPayments(payment)" title="<?php i::_e("Excluir pagamentos selecionados"); ?>"><i class="far fa-trash-alt"></i></a>
                    </span>                    
                </td>
            </tr>

            <tr ng-repeat="payment in data.payments" id="payment-{{payment.id}}">            
               <td>
                    <input ng-model="payment.checked" type="checkbox" id="checkedPayment-{{payment.id}}" class="payment-item" value="{{fIndex}}" title="<?php i::_e("Selecionar pagamento {{payment.number}}"); ?>">
               </td>
                <td class="registration-id-col">
                    <a href='{{payment.url}}' rel='noopener noreferrer'>                       
                        <strong>{{payment.number}}</strong>
                    </a>
                </td>

                <td class="registration-id-col">                
                    {{getDatePaymentString(payment.payment_date)}}                    
                </td>

                <td class="registration-id-col">
                    {{getAmountPaymentString(payment.amount)}}
                </td>
                
                <td class="registration-id-col">
                    {{getPaymentStatusString(payment.status)}}
                </td>
                <td class="registration-id-col actions-icons">
                    <a ng-click="data.editPayment=startEdition(payment);" title="<?php i::_e("Editar pagamento"); ?> {{payment.number}}"><i class="fas fa-edit"></i></a>
                    <a ng-click="deletePayment(payment)" title="<?php i::_e("Excluir pagamento"); ?> {{payment.number}}"><i class="far fa-trash-alt"></i></a>
                </td>
            </tr>           
        </tbody>
        <tfoot>
            <tr>
                <td colspan= "6" align="center">               
                    <div ng-if="data.findingPayments">
                        <img src="<?php $this->asset('img/spinner_192.gif')?>" width="48">
                    </div>
                </td>
            </tr>
        </tfoot>
    </table>
     <!-- Modal de edição de pagamentos únicos-->
    <div ng-class="{hidden:!data.editPayment}" class="payment-modal payment-modal-div hidden">
        <header>
            <h2 class="payment-modal-title"><?php i::_e("Editar pagamento:"); ?> {{data.editPayment.number}}</h2>
        </header>
        
        <div class="fields">

            <div>
                <label ng-model="data.editPayment.payment_date"><?php i::_e("Data"); ?></label>
                <input type="date" ng-model="data.editPayment.payment_date" value="" />
            </div>

            <div>
                <label ng-model="data.editPayment.amount"><?php i::_e("Valor"); ?></label>
                <div>
                    <span class="prefix"><?php i::_e("R$"); ?></span> <input ng-model="data.editPayment.amount" placeholder="<?php i::_e("Ex.: 3.000,00"); ?>" js-mask="###.###.###.###.##0,00"/>
                </div>
            </div>
            
            <div>
                <label ng-model="data.editPayment.status"><?php i::_e("Status"); ?></label>
                <select ng-model="data.editPayment.status">
                    <option value="">Selecione</option>
                    <option value="0" ng-selected="data.editPayment.status === 0"><?php i::_e("Pendente"); ?></option>
                    <option value="1" ng-selected="data.editPayment.status === 1"><?php i::_e("Em processo"); ?></option>
                    <option value="2" ng-selected="data.editPayment.status === 2"><?php i::_e("Falha"); ?></option>
                    <option value="3" ng-selected="data.editPayment.status === 3"><?php i::_e("Exportado"); ?></option>
                    <option value="8" ng-selected="data.editPayment.status === 8"><?php i::_e("Disponível"); ?></option>
                    <option value="10" ng-selected="data.editPayment.status === 10"><?php i::_e("Pago"); ?></option>
                </select>
            </div>
            
        </div>
        
        <div>
            <?php $this->applyTemplateHook('payment-edit-single-modal-metadata','begin'); ?>
            <?php $this->applyTemplateHook('payment-edit-single-modal-metadata','end'); ?>
        </div>
        <footer>
            <button class="btn btn-default" ng-click="data.editPayment = null;" class="js-close"><?php i::_e("Cancelar"); ?></button>            
            <button class="btn btn-primary" ng-click="savePayment(data.editPayment);" class="js-close"><?php i::_e("Salvar"); ?></button> 
        </footer>       
    </div>
    
    <div ng-class="{hidden:!data.editPayment}" class="bg-modal hidden"></div>
    <div ng-class="{hidden:!data.multiplePayments}" class="bg-modal hidden"></div>
</div>