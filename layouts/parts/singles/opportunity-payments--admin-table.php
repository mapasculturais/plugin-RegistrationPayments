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
            <input ng-model="data.search" id="search" ng-keyup="search()" placeholder="<?php i::_e("Pesquise pelo número de inscrição"); ?>" class="ng-pristine ng-untouched ng-valid ng-empty">
        </div>
        <div class="right">
            <span class="label"><?php i::_e("Filtrar por data:"); ?></span>
            <input type="date" ng-model="data.filterDate" placeholder="<?php i::_e("Pesquise por data"); ?>">
        </div>
    </div>
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
                    <?php i::_e("Data de Pagamento"); ?>
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
                    <span ng-if="data.payments.length > 0"><input type="checkbox" id="selectAll" ng-click="selectAll()" title="<?php i::_e("Selecionar todos."); ?>"></span>
                </td>

                <td colspan='5'>
                    <span class="outher-actions" style="display:none"> 
                        <a ng-click="openModal(false)" title="<?php i::_e("Editar pagamentos"); ?>"><i class="fas fa-edit"></i></a>
                        <a ng-click="deleteSelectedPayments(payment)" title="<?php i::_e("Excluir pagamentos selecionados"); ?>"><i class="far fa-trash-alt"></i></a>
                    </span>                    
                </td>
            </tr>

            <tr ng-repeat="(fIndex, payment) in data.payments" id="payment-{{payment.id}}">            
               <td>
                    <input ng-click ="selectPayment()" type="checkbox" id="checkedPayment-{{payment.id}}" name="checkedPayment[]" class="payment-item" value="{{fIndex}}" title="<?php i::_e("Selecionar pagamento {{payment.number}}"); ?>">
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
                    <a ng-click="data.editPayment=startEdition(payment); openModal(true,payment)" title="<?php i::_e("Editar pagamento"); ?> {{payment.number}}"><i class="fas fa-edit"></i></a>
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
    <div ng-class="{hidden:!data.editPayment}" class="payment-modal js-dialog">
        <h2 class="payment-modal-title"><?php i::_e("Editar pagamento:"); ?> {{data.editPayment.number}}</h2>        
        <input type="date" ng-model="data.editPayment.payment_date" id="date_payment"/>
        <?php i::_e("R$"); ?> <input type="text" ng-model="data.editPayment.amount" id="amount" placeholder="ex.: 3000,00"/>
        <select ng-model="data.editPayment.status" id="payment_status">
            <option value="">Selecione</option>
            <option value="0" ng-selected="data.editPayment.status === 0"><?php i::_e("Pendente"); ?></option>
            <option value="1" ng-selected="data.editPayment.status === 1"><?php i::_e("Em processo"); ?></option>
            <option value="2" ng-selected="data.editPayment.status === 2"><?php i::_e("Falha"); ?></option>
            <option value="3" ng-selected="data.editPayment.status === 3"><?php i::_e("Exportado"); ?></option>
            <option value="8" ng-selected="data.editPayment.status === 8"><?php i::_e("Disponível"); ?></option>
            <option value="10" ng-selected="data.editPayment.status === 10"><?php i::_e("Pago"); ?></option>
        </select>
        <button id="btn-save-selected" ng-click="updateSelectedPayments();" class="js-close" ><?php i::_e("Editar seleção"); ?></button>
        <button id="btn-save-single" ng-click="savePayment(data.editPayment);" class="js-close"><?php i::_e("Salvar"); ?></button>
        <button ng-click="data.editPayment = null;" class="js-close"><?php i::_e("Cancelar"); ?></button> <br>
    </div>

</div>