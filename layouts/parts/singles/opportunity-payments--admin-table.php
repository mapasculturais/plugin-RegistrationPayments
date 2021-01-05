<div ng-controller='RegistrationPayments'>

    <header id="header-inscritos" class="clearfix">
        <h3><?php \MapasCulturais\i::_e("Pagamentos"); ?></h3>
    </header>
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
            </tr>
        </thead>
        <tbody>
            <tr>
                <td colspan='4'>
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
                    {{ payment.payment_date }}
                </td>

                <td class="registration-id-col">
                    {{ payment.amount }}

                </td>

                <td class="registration-id-col">
                    {{getEvaluationResultString(payment)}}
                </td>

            </tr>
        </tbody>
    </table>
</div>