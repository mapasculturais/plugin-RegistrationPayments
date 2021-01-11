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
        
        RegistrationPaymentsService.find({opportunity_id:MapasCulturais.entity.id, search:""}).success(function (data){
            $scope.data.payments = data;
        });

       $scope.search = function (){
           var search = $("#search").val()
           RegistrationPaymentsService.find({opportunity_id:MapasCulturais.entity.id, search:search}).success(function (data){
                $scope.data.payments = data;
            });

       }
        
        $scope.savePayment = function (payment) {                        
            RegistrationPaymentsService.update(payment).success(function () {
                MapasCulturais.Messages.success("Pagamento editado com sucesso");                
                var index = $scope.data.payments.findIndex(function(value){ 
                   return payment.id === value.id;
                }) 
                
                $scope.data.payments[index].amount = parseFloat(payment.amount);
                $scope.data.payments[index].status = payment.status;
                $scope.data.payments[index].payment_date = payment.payment_date;
                
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

        $scope.getDatePaymentString = function (valor){
            return moment(valor).format('DD/MM/YYYY');
        }

        $scope.getAmountPaymentString = function (amount){          
            return (new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' })).format(amount);
        }

        $scope.startEdition = function (payment){
            var dataPayment = new Date(payment.payment_date);
            var result = {
                id: payment.id,
                number: payment.number,
                status:payment.status,
                amount: payment.amount,
                registration_id: payment.registration_id,
                payment_date: dataPayment
            }         
            return result;
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

        $scope.openModal = function(string){
            $('#blockdiv').show();
            $('body').css('overflow','hidden');

            var $dialog = $('.payment-modal');

            var top = $dialog.height() + 100 > $(window).height() ? $(window).scrollTop() + 100 : $(window).scrollTop() + ( $(window).height() - $dialog.height()) / 2 - 100;
            
            $dialog.css({
                top: top,
                left: '50%',
                marginLeft: -$dialog.width() / 2,
                opacity: 1,
                display: 'block'
            });

        }

        $scope.closeModal = function(){            
            $('#blockdiv').hide();
            $('body').css('overflow','auto');
        }

    }]);

    module.factory('RegistrationPaymentsService', ['$http', '$rootScope', 'UrlService', function ($http, $rootScope, UrlService) {
        return {
            find: function (data) {                
                var url = MapasCulturais.createUrl('payment', 'findPayments', {opportunity:MapasCulturais.entity.id, search:data.search});                
                
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
                var result = {
                    id: payment.id,
                    number: payment.number,
                    status:payment.status,
                    amount: (payment.amount.replace('.', '').replace(',', '') / 100),
                    registration_id: payment.registration_id,
                    paymentDate: moment(payment.payment_date).format('YYYY-MM-DD')
                }               
                var url = MapasCulturais.createUrl('payment', 'single', [payment.id]);                
                return $http.patch(url, result);
            },

            searsh: function (data) {
                
            },
        
        };
    }]);

})(angular);