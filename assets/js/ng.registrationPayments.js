(function (angular) {
    "use strict";
    var module = angular.module('ng.registrationPayments', []);
    
    module.config(['$httpProvider', function ($httpProvider) {
        $httpProvider.defaults.headers.post['Content-Type'] = 'application/x-www-form-urlencoded;charset=utf-8';
        $httpProvider.defaults.headers.common["X-Requested-With"] = 'XMLHttpRequest';
        $httpProvider.defaults.transformRequest = function (data) {
            var result = angular.isObject(data) && String(data) !== '[object File]' ? $.param(data) : data;

            return result;
        };
    }]);

    module.controller('RegistrationPayments',['$scope', 'RegistrationPaymentsService', function($scope, RegistrationPaymentsService){

        $scope.data = {
            payments: [],
            editPayment: null
        };

        RegistrationPaymentsService.find({opportunity_id:MapasCulturais.entity.id}).success(function (data){
            $scope.data.payments = data;
        });

        
        $scope.savePayment = function (payment) {            
            RegistrationPaymentsService.update(payment).success(function () {
                MapasCulturais.Messages.success("Pagamento editado com sucesso");
            });
        }

        $scope.deletePayment = function(payment){
            if(!confirm("Você tem certeza que quer deletar esse pagamento?")){
                return;
            }

            RegistrationPaymentsService.remove(payment).success(function (){
                MapasCulturais.Messages.success("Pagamento deletado com sucesso");
                var index = $scope.data.payments.indexOf(payment);

                $scope.data.payments.splice(index,1);
            });
        }

        $scope.getPaymentStatusString = function(status){
            switch (parseInt(status)) {
                case 0:
                    return "Pendente"
                    break;
                case 1:
                    return "Processando"
                    break;
                case 2:
                    return "Falha"
                    break;
                case 3:
                    return "Exportado"
                    break;
                case 8:
                    return "Disponível"
                    break;
                case 10:
                    return "Pago"
                    break;
            
                default:
                    return status
                    break;
            }
        }

    }]);

    module.factory('RegistrationPaymentsService', ['$http', '$rootScope', 'UrlService', function ($http, $rootScope, UrlService) {
        return {
            find: function (data) {
                var url = MapasCulturais.createUrl('payment', 'findPayments', {opportunity:MapasCulturais.entity.id});
                
                return $http.get(url, data).
                    success(function (data, status) {
                        $rootScope.$emit('registration.create', {message: "Payments found", data: data, status: status});
                    }).
                    error(function (data, status) {
                        $rootScope.$emit('error', {message: "Payments not found for this opportunity", data: data, status: status});
                    });
            },

            remove: function(payment){
                var url = MapasCulturais.createUrl('payment', 'single', [payment.id]);
                return $http.delete(url, {});
            },

            update: function(payment){
                var url = MapasCulturais.createUrl('payment', 'single', [payment.id]);
                console.log(payment);
                return $http.patch(url, {status:payment.status});
            }
        
        };
    }]);

})(angular);