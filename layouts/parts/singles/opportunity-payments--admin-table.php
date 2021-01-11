<?php

namespace RegistrationPayments;

use MapasCulturais\i;

?>

<div ng-controller='RegistrationPayments'>

    <header id="header-inscritos" class="clearfix">
        <h3><?php \MapasCulturais\i::_e("Pagamentos"); ?></h3>
    </header>

    <div id="payments-filter">
        <span class="label"> <?php i::_e("Filtrar pagamento:"); ?> </span>
        <input ng-model="data.registrationsFilter" placeholder="<?php i::_e('Busque pelo número de inscrição') ?>" />
    </div>

    <table class="js-registration-list registrations-table" ng-class="{'no-options': data.entity.registrationCategories.length === 0, 'no-attachments': data.entity.registrationFileConfigurations.length === 0, 'registrations-results': data.entity.published}">
        <thead>
            <tr>
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
                <td colspan='5'>
                    <span ng-if="data.payments.length === 0"><?php \MapasCulturais\i::_e("Nenhum pagamento registrado."); ?></span>
                    <span ng-if="data.payments.length === 1"><?php \MapasCulturais\i::_e("1 pagamento encontrado."); ?></span>
                    <span ng-if="data.payments.length > 1">{{data.payments.length}}
                        <span ng-if="data.payments.count > 0">
                            <i> de {{ data.payments.count }}</i>
                        </span>
                        <?php \MapasCulturais\i::_e("Pagamentos"); ?>
                    </span>
                </td>
            </tr>

            <tr ng-repeat="payment in data.payments" id="payment-{{payment.id}}">

                <td class="registration-id-col">
                    <a href='{{payment.url}}' rel='noopener noreferrer'>
                        <strong>{{payment.number}}</strong>
                    </a>
                </td>

                <td class="registration-id-col">
                    {{getDatePaymentString(payment.payment_date)}}
                </td>

                <td class="registration-id-col">
                    {{getAmountPaymentString(payment.amount) }}

                </td>

                <td class="registration-id-col">
                    {{getPaymentStatusString(payment.status)}}
                </td>
                <td class="registration-id-col actions-icons">
                    <a ng-click="data.editPayment=startEdition(payment); openModal()" title="Editar pagamento"><i class="fas fa-edit"></i></a>
                    <a ng-click="deletePayment(payment)" title="Excluir pagamento"><i class="far fa-trash-alt"></i></a>
                </td>
            </tr>
        </tbody>
    </table>

    <div ng-class="{hidden:!data.editPayment}" class="payment-modal js-dialog">
        <h2>Editar pagamento: {{data.editPayment.number}}</h2>
        <input type="date" ng-model="data.editPayment.payment_date" id="date_payment">
        <input type="text" ng-model="data.editPayment.amount" id="amount">
        <select ng-model="data.editPayment.status">
            <option value="0" ng-selected="data.editPayment.status === 0">Pendente</option>
            <option value="1" ng-selected="data.editPayment.status === 1">Em processo</option>
            <option value="2" ng-selected="data.editPayment.status === 2">Falha</option>
            <option value="3" ng-selected="data.editPayment.status === 3">Exportado</option>
            <option value="8" ng-selected="data.editPayment.status === 8">Disponível</option>
            <option value="10" ng-selected="data.editPayment.status === 10">Pago</option>
        </select>
        <button ng-click="savePayment(data.editPayment);" class="js-close">Salvar</button>
        <button ng-click="data.editPayment = null;" class="js-close">Cancelar</button>
    </div>

</div>
<div id="paymentModal" style="background-color: rgba(0, 0, 0, 0.6); width: 100%; height: 100%; position: absolute; z-index: 1800; top: 0px; left: 0px; display: none;"></div>