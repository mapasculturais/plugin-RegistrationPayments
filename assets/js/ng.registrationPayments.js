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
            apiMetadata: {},
            search: ""
        };
       
        RegistrationPaymentsService.find({opportunity_id:MapasCulturais.entity.id, search:""}).success(function (data, status, headers){

            $scope.data.apiMetadata = JSON.parse(headers()['api-metadata']);
           
            if(search != ""){
                $scope.data.payments = data;
            }else{
                $scope.data.payments = $scope.data.payments.concat(data);
            }
        });
        

        $scope.search = function (){
            var search = $scope.data.search;

            RegistrationPaymentsService.find({opportunity_id:MapasCulturais.entity.id, search:search}).success(function (data, status, headers){

                $scope.data.apiMetadata = JSON.parse(headers()['api-metadata']);

                if(search != ""){
                    $scope.data.payments = data;
                }else{
                    $scope.data.payments = $scope.data.payments.concat(data);
                }
                
            });
        }

        $scope.loadMore = function(){            
            var search = $scope.data.search;
            var page = parseInt($scope.data.apiMetadata.page) +1;           
            
            if($scope.data.apiMetadata.numPages && parseInt($scope.data.apiMetadata.page) >= parseInt($scope.data.apiMetadata.numPages)){
                return;
            }
            
            $scope.data.findingPayments = true;
            RegistrationPaymentsService.find({opportunity_id:MapasCulturais.entity.id, search:search, "@page":page}).success(function (data, status, headers){

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
    
        
        $scope.savePayment = function (payment) {             
            RegistrationPaymentsService.update(payment).success(function () {
                MapasCulturais.Messages.success("Pagamento editado com sucesso");                
                var index = $scope.data.payments.findIndex(function(value){ 
                return payment.id === value.id;
                });
                
                $scope.data.payments[index].amount = payment.amount;
                $scope.data.payments[index].status = payment.status;
                $scope.data.payments[index].payment_date = payment.payment_date;
                
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
            
            var checked =  new Array(); 
            $("input[name='checkedPayment[]']:checked").each(function (){
                checked.push($(this).val()); 
            });
            
            for(var i = 0; i < checked.length; i++){
                var payment = $scope.data.payments[checked[i]];
                RegistrationPaymentsService.remove(payment);
                
            }

            for(var i = 0; i < checked.length; i++){                
                $scope.data.payments.splice(i,checked.length);
                if(i == (checked.length-1)){
                    MapasCulturais.Messages.success("Pagamentos deletados com sucesso"); 
                    setTimeout(function(){ 
                        window.location.reload(true); 
                    }, 1200);
                }
            }
        }

        $scope.updateSelectedPayments = function(){
            var checked =  new Array(); 
            $("input[name='checkedPayment[]']:checked").each(function (){
                checked.push($(this).val()); 
            });

            if(!confirm("Você confirma que deseja editar " + checked.length + (checked.length < 2 ? " pagamento ?" : " pagamentos ?"))){
                return;
            };
            
            var amount = $("#amount").val(),
                payment_date = $("#date_payment").val(),
                status = $("#payment_status").val();                
               
            for(var i = 0; i < checked.length; i++){                             
                var payment = $scope.data.payments[checked[i]];
                
                payment.amount = amount ? parseFloat(amount) : payment.amount;
                payment.payment_date = payment_date ? payment_date : payment.payment_date;
                payment.status = status ? status : payment.status;                
                RegistrationPaymentsService.update(payment);
            }
            
            MapasCulturais.Messages.success("Pagamentos deletados com sucesso"); 
            $(".payment-item").prop("checked", false);
            $(".outher-actions").fadeOut(300);
        }
        
        $scope.getDatePaymentString = function (valor){
            return moment(valor).format('DD/MM/YYYY');
        }

        $scope.getAmountPaymentString = function (amount){          
            return (new Intl.NumberFormat('pt-BR', { style: 'currency', currency: 'BRL' }).format(amount));
        }

        $scope.startEdition = function (payment){
            var result = angular.copy(payment);
            $('#amount').mask('#.##0,00', { reverse: true });
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

        $scope.openModal = function(single,payment){            
            if(!single){
                $("#date_payment").val("");
                $("#amount").val("");
                $("#payment_status").val("");
                $("#payment-obs").val("");
                $( "#btn-save-single" ).fadeOut(5);
                $( "#btn-save-selected" ).fadeIn(5);

                var checked =  new Array(); 
                $("input[name='checkedPayment[]']:checked").each(function (){
                    checked.push($(this).val()); 
                });
                $(".payment-modal-title").html("Editar " + checked.length + (checked.length < 2 ? " pagamento" : " pagamentos"))
            }else{
                $( "#btn-save-selected" ).fadeOut(5);
                $( "#btn-save-single" ).fadeIn(5);
                $(".payment-modal-title").html("Editar pagamento: " + payment.number);
            }
            
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
            
            $('#blockdiv').show();
        }

        $scope.selectPayment = function(){      
            if($(".payment-item").is(':checked')){               
                $(".outher-actions").fadeIn(300);
            }else{                
                $(".outher-actions").fadeOut(300);
            }
        }
        
        $scope.selectAll = function(){      
            if($("#selectAll").is(':checked')){
                $(".payment-item").prop("checked", true);
                $(".outher-actions").fadeIn(300);
            }else{
                $(".payment-item").prop("checked", false);
                $(".outher-actions").fadeOut(300);
            }
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