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

    module.controller('RegistrationPayments',['$scope', 'RegistrationPaymentsService','$window', function($scope, RegistrationPaymentsService, $window){
        
        $scope.data = {
            payments: [],
            editPayment: null,
            editMultiplePayments: null,
            apiMetadata: {},
            search: "",
            statusFilter: [
                {value: null, label: "Todos"},
                {value: 0, label: "Pendente"},
                {value: 1, label: "Processando"},
                {value: 2, label: "Falha"},
                {value: 3, label: "Exportado"},
                {value: 8, label: "Disponível"},
                {value: 10, label: "Pago"},
            ],
            getStatus: "",
            filterDate: "",
            selectAll: false,
            multiplePayments: false
        };
        
        RegistrationPaymentsService.find({opportunity_id:MapasCulturais.entity.id, search:"", page:1}).success(function (data, status, headers){

            $scope.data.apiMetadata = JSON.parse(headers()['api-metadata']);
           
            if(search != ""){
                $scope.data.payments = data;
            }else{
                $scope.data.payments = $scope.data.payments.concat(data);
            }
        });

        $scope.$watch('data.statusFilter[value]', function(new_val, old_val) {
            var search = $scope.data.search;
            $scope.data.status = new_val;
            var paymentDate = $scope.data.filterDate ? $scope.data.filterDate : null;            

            RegistrationPaymentsService.find({opportunity_id:MapasCulturais.entity.id, search:search, status:new_val, paymentDate:paymentDate}).success(function (data, status, headers){

                $scope.data.apiMetadata = JSON.parse(headers()['api-metadata']);

                $scope.data.payments = data;
                
            });
        });
        
        $scope.searchTimeOut = null;
        $scope.search = function (){            
            clearTimeout($scope.searchTimeOut);
            $scope.searchTimeOut = setTimeout(function(){
                var search = $scope.data.search;
                var paymentDate = $scope.data.filterDate ? $scope.data.filterDate : null;
                var status = $scope.data.status ? $scope.data.status : null;
    
                RegistrationPaymentsService.find({opportunity_id:MapasCulturais.entity.id, search:search, status:status, paymentDate:paymentDate}).success(function (data, status, headers){
    
                    $scope.data.apiMetadata = JSON.parse(headers()['api-metadata']);
    
                    if(search != ""){
                        $scope.data.payments = data;
                    }else{
                        $scope.data.payments = $scope.data.payments.concat(data);
                    }
                });
            }, 2000);
        }

        $scope.loadMore = function(){            
            var search = $scope.data.search;
            var page = $scope.data.apiMetadata.page ? parseInt($scope.data.apiMetadata.page) +1 : 1;           
            var status = $scope.data.status ? $scope.data.status : null;
            var paymentDate = $scope.data.filterDate ? $scope.data.filterDate : null;       
            
            if($scope.data.apiMetadata.numPages && parseInt($scope.data.apiMetadata.page) >= parseInt($scope.data.apiMetadata.numPages)){
                return;
            }
            
            $scope.data.findingPayments = true;
            RegistrationPaymentsService.find({opportunity_id:MapasCulturais.entity.id, search:search, "@page":page, status:status, paymentDate:paymentDate}).success(function (data, status, headers){

                $scope.data.apiMetadata = JSON.parse(headers()['api-metadata']);

                if(search != ""){
                    $scope.data.payments = data;
                }else{
                    $scope.data.payments = $scope.data.payments.concat(data);
                }
                $scope.data.findingPayments = false;
            });
        }

        angular.element($window).bind("scroll", function(){
            if(document.location.hash.indexOf("tab=payments") >= 0){
                if(!$scope.data.findingPayments){                   
                    if(document.body.offsetHeight - $window.pageYOffset <  $window.innerHeight){ 
                        $scope.loadMore();
                    }
                }
            }
        });
        
        $scope.$watch('data.filterDate', function(new_val, old_val) {            
            var search = $scope.data.search;
            $scope.data.filterDate = new_val;            
            var status = $scope.data.status ? $scope.data.status : null;
            
            RegistrationPaymentsService.find({opportunity_id:MapasCulturais.entity.id, search:search, status:status, paymentDate:new_val}).success(function (data, status, headers){

                $scope.data.apiMetadata = JSON.parse(headers()['api-metadata']);

                $scope.data.payments = data;
                
            });
        });
        
        $scope.savePayment = function (payment) {
            RegistrationPaymentsService.update(payment).success(function () {
                MapasCulturais.Messages.success("Pagamento editado com sucesso");                
                var index = $scope.data.payments.findIndex(function(value){ 
                    $scope.data.editPayment = null;
                    return payment.id === value.id;
                });
                $scope.data.payments[index] = payment;
            });
        }

        $scope.deletePayment = function(payment){
            if(!confirm("Você tem certeza que deseja deletar esse pagamento?")){
                return;
            }

            RegistrationPaymentsService.remove(payment).success(function (){
                MapasCulturais.Messages.success("Pagamento deletado com sucesso");
                var index = $scope.data.payments.indexOf(payment);
                
                $scope.data.payments.splice(index,1);
            });
        }
        
        $scope.deleteSelectedPayments = function(){
            if(!confirm("Você tem certeza que deseja deletar os pagamentos selecionados?")){
                return;
            }
            
            var payments = $scope.getSelectedPayments();
            
            var noDelected = false;
            payments.forEach(function(payment){                
                RegistrationPaymentsService.remove(payment).success(function (){
                    var index = $scope.data.payments.indexOf(payment);
                    $scope.data.payments.splice(index,1);                             
                }).error(function(){                    
                    noDelected = true;  
                    return;                 
                });
            });

            if(!noDelected){
                MapasCulturais.Messages.success("Pagamentos deletados com sucesso"); 
            }
        }
        
        $scope.updateSelectedPayments = function(){
           if($scope.data.editMultiplePayments){
                var dataView = $scope.data.editMultiplePayments;
                var payments = $scope.getSelectedPayments();               
                var noDelected = false;
                payments.forEach(function(payment){
                    var result = angular.copy(payment);

                    result.payment_date = dataView.payment_date ? dataView.payment_date : payment.payment_date;                
                    result.amount =  dataView.amount ? parseFloat(dataView.amount) : payment.amount;
                    result.status = dataView.status ? dataView.status : payment.status
                    result.metadata.csv_line.OBSERVACOES = dataView.obs ? dataView.obs : payment.metadata.csv_line.OBSERVACOES;
                    
                    RegistrationPaymentsService.update(result).success(function (){ 
                        var index = $scope.data.payments.indexOf(payment);                      
                        $scope.data.payments[index] = result; 
                        $scope.data.payments[index].checked = false;                                                                         
                    }).error(function(){                    
                        noDelected = true;  
                        return;                 
                    });
                });               

                if(!noDelected){
                    $scope.data.multiplePayments = false;
                    $scope.data.editMultiplePayments = null;                                                 
                    MapasCulturais.Messages.success("Pagamentos editados com sucesso");
                }
           }else{
                $scope.data.multiplePayments = false;
                MapasCulturais.Messages.error("Preencha os campos para editar"); 
           }
        }
        
        $scope.getDatePaymentString = function (valor){
            return moment(valor).format('DD/MM/YYYY');
        }

        $scope.getAmountPaymentString = function (amount){          
            return (new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(amount));
        }

        $scope.startEdition = function (payment){
            var result = angular.copy(payment);            
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

        $scope.clearChecked = function(){
            $scope.data.payments.forEach(function(payment){
                payment.checked = false;
            }); 
        }
        
        $scope.selectAll = function(){            
            $scope.data.selectAll = !$scope.data.selectAll;            
            $scope.data.payments.forEach(function(payment){
                payment.checked = $scope.data.selectAll;
            });
        }

        $scope.getSelectedPayments = function(){
            return $scope.data.payments.filter(function(payment){
                return payment.checked;
            });
        }
    }]);

    module.factory('RegistrationPaymentsService', ['$http', '$rootScope', 'UrlService', function ($http, $rootScope, UrlService) {  
        return {
            find: function (data) { 
                if(!data['@limit']){
                    data['@limit'] = 50;
                }

                var url = MapasCulturais.createUrl('payment', 'findPayments', {opportunity:MapasCulturais.entity.id, search:data.search});
                
                return $http.get(url, {params:data}).
                    success(function (data, status, headers) {
                        for(var i = 0; i < data.length; i++) {
                            data[i].payment_date = new Date(data[i].payment_date+ " 12:00");
                        }
                        
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
                    amount:payment.amount,
                    registration_id: payment.registration_id,
                    paymentDate: moment(payment.payment_date).format('YYYY-MM-DD'),
                    metadata: payment.metadata
                    
                }
                
                var url = MapasCulturais.createUrl('payment', 'single', [payment.id]);                
                return $http.patch(url, result);
            },
            finish: function(){
                var meta = $scope.data[meta_key];
                return $scope.data.apiMetadata.numPages && parseInt($scope.data.apiMetadata.page) >= parseInt($scope.data.apiMetadata.numPages);
            }
        };
    }]);

})(angular);