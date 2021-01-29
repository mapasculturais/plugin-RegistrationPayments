<?php

namespace RegistrationPayments;

use MapasCulturais\i;

?>

<div ng-controller='RegistrationPayments'>

    <header id="header-inscritos" class="clearfix">
        <h3><?php i::_e("Pagamentos"); ?></h3>
        <button ng-click="data.openModalCreate = true" class="btn btn-primary"> <?php i::_e("Adicionar pagamento"); ?></button>
    </header>
    <div id="payments-filter">
        <div class="left">
            <span class="label"> <?php i::_e("Filtrar inscrição:"); ?> </span>
            <input ng-model="data.search" id="search" ng-keyup="search()" placeholder="<?php i::_e("Busque pelo número de inscrição"); ?>" class="ng-pristine ng-untouched ng-valid ng-empty"> <br>
        </div>
        <div class="right">
            <div>
                <span class="label"><?php i::_e("Data inicial:"); ?></span>
                <input type="date" ng-model="data.filterDateFrom" placeholder="<?php i::_e("Informe a data inicial"); ?>">
            </div>

            <div>
                <span class="label"><?php i::_e("Data final:"); ?></span>
                <input type="date" ng-model="data.filterDateTo" placeholder="<?php i::_e("Informe a data final"); ?>">
            </div>
        </div>
        <div>
            <button ng-if="data.payments.length > 0" ng-click="exportPaymentsFilter()" class="btn btn-default download"> <?php i::_e("Exportar resultados"); ?></button>
        </div>
    </div>

    <!-- Modal de pagamentos em massa -->
    <div ng-class="{hidden:!data.multiplePayments}" class="payment-modal payment-modal-div hidden">
        <header>
            <h2 class="payment-modal-title"><?php i::_e("Editar pagamentos:"); ?></h2>
            <p><?php i::_e("Na edição em massa, as alterações serão aplicadas para todos pagamentos selecionados. Os campos que deixar em branco não serão alterados, mantendo os dados anteriores."); ?></p>
        </header>

        <div class="fields">

            <div>
                <label ng-model="data.editMultiplePayments.payment_date"><?php i::_e("Data"); ?></label>
                <input type="date" ng-model="data.editMultiplePayments.payment_date" value="" data-flatpickr="1" />
            </div>

            <div>
                <label ng-model="data.editMultiplePayments.amount"><?php i::_e("Valor"); ?></label>
                <div>
                    <span class="prefix"><?php i::_e("R$"); ?></span> <input type="text" ng-model="data.editMultiplePayments.amount" placeholder="<?php i::_e("Ex.: 3.000,00"); ?>" js-mask="###.###.###.###.##0,00" />
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
            <?php $this->applyTemplateHook('payment-edit-multiple-modal-metadata', 'begin'); ?>
            <?php $this->applyTemplateHook('payment-edit-multiple-modal-metadata', 'end'); ?>
        </div>

        <footer>
            <div ng-if="data.editingPayments > 0">
                <img src="<?php $this->asset('img/spinner_192.gif') ?>" width="48">
            </div>
            <div ng-if="data.editingPayments == 0">
                <button class="btn btn-default" ng-click="data.multiplePayments = false;" class="js-close"><?php i::_e("Cancelar"); ?></button>
                <button class="btn btn-primary" ng-click="updateSelectedPayments();" class="js-close"><?php i::_e("Editar seleção"); ?></button>
            </div>
        </footer>
    </div>

    <!-- Tabela de pagamentos -->
    <table class="js-registration-list registrations-table payments-table">
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

                <th class="registration-status-col payment-status-col">
                    <?php i::_e("Status"); ?>
                    <mc-select placeholder="<?php i::_e("Status"); ?>" model="data.statusFilter[value]" data="data.statusFilter"></mc-select>
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
                        <a ng-click="data.multiplePayments = true" title="<?php i::_e("Editar pagamentos"); ?>"><i class="fas fa-edit"></i></a>
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
                    <a ng-click="data.historyPayment = true; getHistoryPayment(payment)" title="<?php i::_e("Histórico de alterações"); ?> {{payment.number}}"><i class="fas fa-history"></i></a>
                </td>
            </tr>
        </tbody>
        <tfoot>
            <tr>
                <td colspan="6" align="center">
                    <div ng-if="data.findingPayments">
                        <img src="<?php $this->asset('img/spinner_192.gif') ?>" width="48">
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
                <input type="date" ng-model="data.editPayment.payment_date" value="" data-flatpickr="1" />
            </div>

            <div>
                <label ng-model="data.editPayment.amount"><?php i::_e("Valor"); ?></label>
                <div>
                    <span class="prefix"><?php i::_e("R$"); ?></span> <input ng-model="data.editPayment.amount" placeholder="<?php i::_e("Ex.: 3.000,00"); ?>" js-mask="###.###.###.###.##0,00" />
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
            <?php $this->applyTemplateHook('payment-edit-single-modal-metadata', 'begin'); ?>
            <?php $this->applyTemplateHook('payment-edit-single-modal-metadata', 'end'); ?>
        </div>

        <footer>
            <button class="btn btn-default" ng-click="data.editPayment = null;" class="js-close"><?php i::_e("Cancelar"); ?></button>
            <button class="btn btn-primary" ng-click="savePayment(data.editPayment);" class="js-close"><?php i::_e("Salvar"); ?></button>
        </footer>
    </div>

    <!-- Modal de criação de pagamento-->
    <div ng-class="{hidden:!data.openModalCreate}" class="payment-modal create payment-modal-div hidden">
        <header>
            <h2 class="payment-modal-title"><?php i::_e("Criar pagamentos:"); ?></h2>
        </header>

        <div class="fields">
            <div>
                <label ng-model="data.createPayment.registration_id"><?php i::_e("Inscrições"); ?></label>
                <textarea ng-model="data.createPayment.registration_id" cols="100" rows="5" placeholder="<?php i::_e("Informe uma ou mais inscrições separadas por virgula. Ex.: es-123456, es-654321 ou 123456,654321 "); ?>"></textarea>
            </div>

            <div>
                <label ng-model="data.createPayment.payment_date"><?php i::_e("Previsão de pagamento"); ?></label>
                <input type="date" ng-model="data.createPayment.payment_date" value="" data-flatpickr="1" />
            </div>

            <div>
                <label ng-model="data.createPayment.amount"><?php i::_e("Valor"); ?></label>
                <div>
                    <span class="prefix"><?php i::_e("R$"); ?></span> <input ng-model="data.createPayment.amount" placeholder="<?php i::_e("Ex.: 3.000,00"); ?>" js-mask="###.###.###.###.##0,00" />
                </div>
            </div>
            <div>
                <label ng-model="data.createPayment.status"><?php i::_e("Status"); ?></label>
                <select ng-model="data.createPayment.status">
                    <option value="">Selecione</option>
                    <option value="0" ng-selected="data.createPayment.status === 0"><?php i::_e("Pendente"); ?></option>
                    <option value="1" ng-selected="data.createPayment.status === 1"><?php i::_e("Em processo"); ?></option>
                    <option value="2" ng-selected="data.createPayment.status === 2"><?php i::_e("Falha"); ?></option>
                    <option value="3" ng-selected="data.createPayment.status === 3"><?php i::_e("Exportado"); ?></option>
                    <option value="8" ng-selected="data.createPayment.status === 8"><?php i::_e("Disponível"); ?></option>
                    <option value="10" ng-selected="data.createPayment.status === 10"><?php i::_e("Pago"); ?></option>
                </select>
            </div>
        </div>

        <div>
            <?php $this->applyTemplateHook('payment-create-multiple-modal-metadata', 'begin'); ?>
            <?php $this->applyTemplateHook('payment-create-multiple-modal-metadata', 'end'); ?>
        </div>

        <footer>
            <button class="btn btn-default" ng-click="data.openModalCreate = false;" class="js-close"><?php i::_e("Cancelar"); ?></button>
            <button class="btn btn-primary" ng-click="createPaymentData(data.createPayment);" class="js-close"><?php i::_e("Salvar"); ?></button>
        </footer>
    </div>

     <!-- Modal de histórico de pagamento-->
     <div ng-class="{hidden:!data.historyPayment}" class="payment-modal create payment-modal-div hidden">
        <header>
            <h2 class="payment-modal-title"><?php i::_e("Histórico de alteração:"); ?></h2>
        </header>
            <div class="revisions">            
                <div class="widget">
                    <h3></h3>                   
                    <ul class="widget-list js-slimScroll horizontalScroll">               
                        <li ng-repeat="revision in data.revisions" id="revision-{{revision.id}}" class="widget-list-item" >
                            <div class="revision">
                                <a class="js-metalist-item-display" ng-click="getDataRevisions(revision.id)"><span>{{revision.message}}</span></a>                                
                                <small>{{revision.user.profile.name}}</small>
                                <small>{{revision.createTimestamp.date}}</small>
                            </div>                            
                        </li>
                    </ul>
                </div>           
            </div>
            <div ng-class="{hidden:!data.dataRevsionShow}" class="dataRevision">
            <span>Data: {{getDatePaymentString(data.dataRevsionView.paymentDate.value.date)}}</span><br>
            <span>Valor: {{getAmountPaymentString(data.dataRevsionView.amount.value)}}</span> <br>
            <span>Status: {{getPaymentStatusString(data.dataRevsionView.status.value)}}</span><br>
            <span>
            <?php $this->applyTemplateHook('payment-dataRevision-view', 'begin'); ?>
            <?php $this->applyTemplateHook('payment-dataRevision-view', 'end'); ?>
            </span>
            
            </div>            
        <footer>
            <button class="btn btn-default" ng-click="data.historyPayment = false;" class="js-close"><?php i::_e("Cancelar"); ?></button>
        </footer>
    </div>

    <div ng-class="{hidden:!data.editPayment}" class="bg-modal hidden"></div>
    <div ng-class="{hidden:!data.multiplePayments}" class="bg-modal hidden"></div>
    <div ng-class="{hidden:!data.openModalCreate}" class="bg-modal hidden"></div>
    <div ng-class="{hidden:!data.historyPayment}" class="bg-modal hidden"></div>

</div>