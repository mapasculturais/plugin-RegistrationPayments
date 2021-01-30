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
            createPayment: {
                registration_id: null,
                payment_date: null,
                amount: null,
                status: null,
            },
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
            filterDateFrom: "",
            filterDateTo: "",
            selectAll: false,
            multiplePayments: false,
            editingPayments: 0,
            deletingPayments: 0,
            openModalCreate: false,
            historyPayment: false,
            revisions: [],
            dataRevision: [],
            dataRevsionShow: false
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
            var from = $scope.data.filterDateFrom;
            var to = $scope.data.filterDateTo;            

            RegistrationPaymentsService.find({opportunity_id:MapasCulturais.entity.id, search:search, status:new_val, from:from, to:to}).success(function (data, status, headers){

                $scope.data.apiMetadata = JSON.parse(headers()['api-metadata']);

                $scope.data.payments = data;
                
            });
        });

        function initMasks() {
            $('[js-mask]').each(function() {
                var $this = jQuery(this);
    
                if (!$this.data('js-mask-init')) {
                    $this.mask($this.attr('js-mask'), {reverse:true});
                    $this.data('js-mask-init', true);
                }
            });
        }
        setInterval(initMasks, 1000);
        
        $scope.searchTimeOut = null;
        $scope.search = function (){            
            clearTimeout($scope.searchTimeOut);
            $scope.searchTimeOut = setTimeout(function(){
                $scope.data.payments = [];
                var search = $scope.data.search;
                var from = $scope.data.filterDateFrom;
                var to = $scope.data.filterDateTo;
                var status = $scope.data.status ? $scope.data.status : null;
    
                RegistrationPaymentsService.find({opportunity_id:MapasCulturais.entity.id, search:search, status:status, from:from, to:to}).success(function (data, status, headers){    
                    $scope.data.apiMetadata = JSON.parse(headers()['api-metadata']);    
                    $scope.data.payments = $scope.data.payments.concat(data);
                });
            }, 1500);
        }

        $scope.loadMore = function(){            
            var search = $scope.data.search;
            var page = $scope.data.apiMetadata.page ? parseInt($scope.data.apiMetadata.page) +1 : 1;           
            var status = $scope.data.status ? $scope.data.status : null;
            var from = $scope.data.filterDateFrom;
            var to = $scope.data.filterDateTo;
            
            if($scope.data.apiMetadata.numPages && parseInt($scope.data.apiMetadata.page) >= parseInt($scope.data.apiMetadata.numPages)){
                return;
            }
            
            $scope.data.findingPayments = true;
            RegistrationPaymentsService.find({opportunity_id:MapasCulturais.entity.id, search:search, "@page":page, status:status, from:from, to:to}).success(function (data, status, headers){
                $scope.data.apiMetadata = JSON.parse(headers()['api-metadata']);
                $scope.data.payments = $scope.data.payments.concat(data);
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
        
        $scope.$watchGroup(['data.filterDateFrom','data.filterDateTo'], function(new_val, old_val) {               
            var from = $scope.data.filterDateFrom;
            var to = $scope.data.filterDateTo;
            var search = $scope.data.search;
            $scope.data.filterDate = new_val;            
            var status = $scope.data.status ? $scope.data.status : null;
            
            RegistrationPaymentsService.find({opportunity_id:MapasCulturais.entity.id, search:search, status:status, from:from, to:to}).success(function (data, status, headers){

                $scope.data.apiMetadata = JSON.parse(headers()['api-metadata']);

                $scope.data.payments = data;
                
            });
        });
        

        $scope.createPaymentData = function(){           
            var fieldEmpty = false; 
            Object.values($scope.data.createPayment).forEach(function(field){
                if(!field){
                    fieldEmpty = true;
                }
            });

            if(fieldEmpty){
                MapasCulturais.Messages.error("Preencha todos os campos");   
                return;
            }
            
            var dataView = angular.copy($scope.data.createPayment);
            
            RegistrationPaymentsService.create(dataView).success(function (data, status, headers) {
                $scope.data.payments = [];

                RegistrationPaymentsService.find({opportunity_id:MapasCulturais.entity.id, search:"", page:1}).success(function (data, status, headers){
                    $scope.data.payments = data;
                });

                $scope.data.openModalCreate = false;
                $scope.data.openModalCreate = null;
                MapasCulturais.Messages.success("Pagamento cadastrado com sucesso");

            }).error(function (data, status, headers) {
                MapasCulturais.Messages.error("Pagamento não cadastrado");
                   
            });         
        }
        
        $scope.savePayment = function (payment) {
            payment.amount = (payment.amount.replace(".","").replace(",","") / 100);

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

        $scope.exportPaymentsFilter = function(){
           
            var search = $scope.data.search;
            var status = $scope.data.status ? $scope.data.status : null;
            var from = $scope.data.filterDateFrom ? moment($scope.data.filterDateFrom).format('YYYY-MM-DD') : "";
            var to = $scope.data.filterDateTo ? moment($scope.data.filterDateTo).format('YYYY-MM-DD') : "";
            var url = MapasCulturais.createUrl('payment', 'exportFilter', {opportunity:MapasCulturais.entity.id, search: search, from:from, to:to, status:status});
          
            document.location = url;
        }

        $scope.getHistoryPayment = function(payment){            
            RegistrationPaymentsService.getHistory(payment.id).success(function(data, status, headers){ 
                $scope.data.dataRevision = null;
                $scope.data.dataRevsionShow = false;               
                var result = data.revisions.map(function(revision){
                    revision.message = revision.message.replace(".","");
                    revision.createTimestamp.date = moment(revision.createTimestamp.date).format('DD/MM/YYYY');
                    return revision;
                });
                $scope.data.revisions = result;
                $scope.data.dataRevision = data.dataRevisions;
            });
        }

        $scope.getDataRevisions = function(revisonId){
            $scope.data.dataRevsionShow = true;
            $scope.data.dataRevsionView = $scope.data.dataRevision[revisonId];
        }
        
        $scope.deletingPayemntTimeOut = null;
        $scope.deleteSelectedPayments = function(){
            if(!confirm("Você tem certeza que deseja deletar os pagamentos selecionados?")){
                return;
            }
            
            var payments = $scope.getSelectedPayments();
            payments.forEach(function(payment, i){ 

                setTimeout(function(){
                    $scope.data.deletingPayments ++;
                    RegistrationPaymentsService.remove(payment).success(function (){ 

                        var index = $scope.data.payments.indexOf(payment);
                        $scope.data.payments.splice(index,1);
                        $scope.data.deletingPayments --;

                        clearTimeout($scope.deletingPayemntTimeOut);

                        $scope.deletingPayemntTimeOut = setTimeout(function(){
                            if($scope.data.deletingPayments == 0){
                                $scope.data.multiplePayments = false;
                                $scope.data.editMultiplePayments = null; 
                                $scope.$apply();
                                if(payments.length == $scope.data.apiMetadata.limit){
                                    $scope.loadMore();
                                }
                                MapasCulturais.Messages.success("Pagamentos deletados com sucesso"); 
                            }
                        },200);

                    }).error(function(){
                        $scope.data.deletingPayments --;                    
                        if($scope.data.deletingPayments == 0){
                            $scope.data.multiplePayments = false;
                            $scope.data.editMultiplePayments = null;                             
                            $scope.$apply();
                            MapasCulturais.Messages.error("Erros ocorreram durante o processamento");
                        } 
                        return;                 
                    });
                }, (200*i));
            });            
        }
        $scope.editingPayemntTimeOut = null;
        $scope.updateSelectedPayments = function(){
           if($scope.data.editMultiplePayments){
                var dataView = $scope.data.editMultiplePayments;
                var payments = $scope.getSelectedPayments();
                
                payments.forEach(function(payment, i){
                    setTimeout(function(){                       
                        $scope.data.editingPayments ++;

                        if(!dataView.amount){
                            delete dataView.amount;
                        } 
                        
                        if(!dataView.payment_date){
                            delete dataView.payment_date;
                        }
                        
                        var result = angular.merge(payment, dataView);

                        if(typeof result.amount == "string"){
                            result.amount = (result.amount.replace(".","").replace(",","") / 100);
                        }

                        RegistrationPaymentsService.update(result).success(function (){

                            var index = $scope.data.payments.indexOf(payment);                      
                            $scope.data.payments[index] = result; 
                            $scope.data.payments[index].checked = false;
                            $scope.data.editingPayments --;
                            clearTimeout($scope.editingPayemntTimeOut);

                            $scope.editingPayemntTimeOut = setTimeout(function(){
                                if($scope.data.editingPayments == 0){
                                    $scope.data.multiplePayments = false;
                                    $scope.data.editMultiplePayments = null; 
                                    $scope.$apply();
                                    MapasCulturais.Messages.success("Pagamentos editados com sucesso");
                                }
                            }, 200);
                            

                        }).error(function(){
                            $scope.data.editingPayments --;
                            clearTimeout($scope.editingPayemntTimeOut);

                            $scope.editingPayemntTimeOut = setTimeout(function(){
                                if($scope.data.editingPayments == 0){
                                    $scope.data.multiplePayments = false;
                                    $scope.data.editMultiplePayments = null; 
                                    $scope.$apply();
                                    MapasCulturais.Messages.error("Erros ocorreram durante o processamento");
                                } 
                            },200) 
                            return;                 
                        });
                    }, (200*i));                   
                });
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
            result.amount = (new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(result.amount)).replace("R$","");          
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

                var url = MapasCulturais.createUrl('payment', 'findPayments', {opportunity:MapasCulturais.entity.id});
                
                return $http.get(url, {params:data}).
                    success(function (data, status, headers) {
                       
                        for(var i = 0; i < data.length; i++) {
                             var url = MapasCulturais.createUrl('inscricao',data[i].registration_id);
                            data[i].payment_date = new Date(data[i].payment_date+ " 12:00");
                            data[i].url = url;
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
            },

            create: function(payment){
                var url = MapasCulturais.createUrl('payment', 'createMultiple', {opportunity:MapasCulturais.entity.id});

                payment.amount = (payment.amount.replace(".","").replace(",","") / 100);

                payment.payment_date = moment(payment.payment_date).format('YYYY-MM-DD');

                return $http.post(url, payment).success(function (data, status, headers) {                   
                    $rootScope.$emit('registration.create', {message: "Payments found", data: data, status: status});

                }).
                error(function (data, status) {
                    $rootScope.$emit('error', {message: "Payments not found for this opportunity", data: data, status: status});

                });
            },
            export: function(payments){
                var url = MapasCulturais.createUrl('payment', 'exportFilter', {opportunity:MapasCulturais.entity.id});

                return $http.post(url, {payments:payments}).success(function (data, status, headers) {                   
                    $rootScope.$emit('registration.create', {message: "Payments found", data: data, status: status});
                }).
                error(function (data, status) {
                    $rootScope.$emit('error', {message: "Payments not found for this opportunity", data: data, status: status});

                });
            },
            getHistory: function(paymentId){
                var url = MapasCulturais.createUrl('payment', 'revision', {paymentId:paymentId});

                return $http.get(url, {paymentId:paymentId}).success(function (data, status, headers) {                   
                    $rootScope.$emit('registration.create', {message: "Payments found", data: data, status: status});
                }).
                error(function (data, status) {
                    $rootScope.$emit('error', {message: "Payments not found for this opportunity", data: data, status: status});

                });
            }
        };
    }]);

})(angular);