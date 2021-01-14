<?php

namespace RegistrationPayments;

use MapasCulturais\i;

?>

<div ng-controller='RegistrationPayments'>
    
    <header id="header-inscritos" class="clearfix">
        <h3><?php \MapasCulturais\i::_e("Pagamentos"); ?></h3>
    </header>
    <div id="payments-filter">
        <span class="label"> Filtrar inscrição: </span>
        <input ng-model="data.search" id="search" ng-keyup="search()" placeholder="Busque pelo número de inscrição" class="ng-pristine ng-untouched ng-valid ng-empty">
    </div>
    <table class="js-registration-list registrations-table">
        <thead>
            <tr> 
            <th></th>              
                <th class="registration-id-col">
                    <?php \MapasCulturais\i::_e("Inscrição"); ?>
                </th>
                <th class="registration-payment-date-col">
                    <?php \MapasCulturais\i::_e("Data de Pagamento"); ?>
                </th>
                <th class="registration-amount-col">
                    <?php \MapasCulturais\i::_e("Valor"); ?>
                </th>
                <th class="registration-status-col">
                    <?php \MapasCulturais\i::_e("Status"); ?>
                </th>
                <th class="registration-status-col">
                    <?php \MapasCulturais\i::_e("Ações"); ?>
                </th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td></td>
                <td colspan='5'>                 
                    <span ng-if="data.apiMetadata.count === 0"><?php \MapasCulturais\i::_e("Nenhum pagamento registrado."); ?></span>
                    <span ng-if="data.apiMetadata.count  === 1"><?php \MapasCulturais\i::_e("1 pagamento encontrado."); ?></span>
                    <span ng-if="data.apiMetadata.count  > 1">{{data.payments.length}}
                        <span ng-if="data.apiMetadata.count > 0">
                            <i>de {{ data.apiMetadata.count }}</i>
                        </span>
                        <?php \MapasCulturais\i::_e("Pagamentos"); ?>
                    </span>
                </td>                
            </tr>

            <tr>
                <td>
                    <input type="checkbox" id="selectAll" ng-click="selectAll()">
                </td>
                <td colspan='5'>
                    <span class="outher-actions" style="display:none"> 
                        <a ng-click="openModal(false)" title="Editar pagamento"><i class="fas fa-edit"></i></a>
                        <a ng-click="deleteSelectedPayments(payment)" title="Excluir pagamentos selecionados"><i class="far fa-trash-alt"></i></a>
                    </span>                    
                </td>
            </tr>

            <tr ng-repeat="(fIndex, payment) in data.payments" id="payment-{{payment.id}}">
            
               <td>
                    <input ng-click ="selectPayment()" type="checkbox" id="checkedPayment-{{payment.id}}" name="checkedPayment[]" class="payment-item" value="{{fIndex}}">
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
                    <a ng-click="data.editPayment=startEdition(payment); openModal(true,payment)" title="Editar pagamento"><i class="fas fa-edit"></i></a>
                    <a ng-click="deletePayment(payment)" title="Excluir pagamento"><i class="far fa-trash-alt"></i></a>
                </td>
            </tr>

            <tr ng-if="data.apiMetadata.page < data.apiMetadata.numPages">
                <td colspan="6" class="load-more">
                    <button id="btn-load-more" ng-click="loadMore()"><i class="fas fa-plus-circle"></i><?php \MapasCulturais\i::_e("Carregar Mais"); ?></button>
                </td>
            </tr>
        </tbody>
    </table>

        <input type="hidden" id="page" value="1">
      
        
    <div ng-class="{hidden:!data.editPayment}" class="payment-modal js-dialog">
        <h2 class="payment-modal-title">Editar pagamento: {{data.editPayment.number}}</h2>
        <input type="date" ng-model="data.editPayment.payment_date" id="date_payment" >
        R$ <input type="number" ng-model="data.editPayment.amount" id="amount" placeholder="ex.: 3000,00">
        <select ng-model="data.editPayment.status" id="payment_status">
            <option value="">Selecione</option>
            <option value="0" ng-selected="data.editPayment.status === 0">Pendente</option>
            <option value="1" ng-selected="data.editPayment.status === 1">Em processo</option>
            <option value="2" ng-selected="data.editPayment.status === 2">Falha</option>
            <option value="3" ng-selected="data.editPayment.status === 3">Exportado</option>
            <option value="8" ng-selected="data.editPayment.status === 8">Disponível</option>
            <option value="10" ng-selected="data.editPayment.status === 10">Pago</option>
        </select>
        <button id="btn-save-selected" ng-click="updateSelectedPayments();" class="js-close" ><?php \MapasCulturais\i::_e("Editar seleção"); ?></button>
        <button id="btn-save-single" ng-click="savePayment(data.editPayment);" class="js-close"><?php \MapasCulturais\i::_e("Salvar"); ?></button>
        <button ng-click="data.editPayment = null;" class="js-close"><?php \MapasCulturais\i::_e("Cancelar"); ?></button> <br>        
        <!-- <textarea ng-model="data.editPayment.metadataView" name="payment-obs" id="payment-obs" cols="100" rows="10"></textarea> -->
    </div>

</div>
<div id="paymentModal" style="background-color: rgba(0, 0, 0, 0.6); width: 100%; height: 100%; position: absolute; z-index: 1800; top: 0px; left: 0px; display: none;"></div>