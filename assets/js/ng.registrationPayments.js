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
            if(search != ""){
                $scope.data.payments = data;
            }else{
                $scope.data.payments = $scope.data.payments.concat(data);
            }
        });
        

        $scope.search = function (){
                var search = $("#search").val()
                RegistrationPaymentsService.find({opportunity_id:MapasCulturais.entity.id, search:search}).success(function (data){
                    if(search != ""){
                        $scope.data.payments = data;
                    }else{
                        $scope.data.payments = $scope.data.payments.concat(data);
                    }
                   
                });
        }

        $scope.loadMore = function(){
            var search = $("#search").val();
            var page = parseInt($("#page").val());
            page++;
            
            RegistrationPaymentsService.find({opportunity_id:MapasCulturais.entity.id, search:search, "@page":page}).success(function (data){
                if(search != ""){
                    $scope.data.payments = data;
                }else{
                    $scope.data.payments = $scope.data.payments.concat(data);
                }
                $("#page").val(page);
            });
        }
        
        $scope.savePayment = function (payment) {             
            
            if((typeof payment.metadata.csv_line !== 'undefined')){
                payment.metadata.csv_line.OBSERVACOES = payment.metadataView;
            }else{
                payment.metadata = payment.metadata;
            }
            

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
                status = $("#payment_status").val(),
                metadataView = $("#payment-obs").val();
               
            for(var i = 0; i < checked.length; i++){                             
                var payment = $scope.data.payments[checked[i]];
                var  metadata = JSON.parse(payment.metadata);
                
                if((typeof metadata.csv_line !== 'undefined')){
                    metadata.csv_line.OBSERVACOES = metadataView;
                    payment.metadata = metadata;
                }else{
                    payment.metadata = metadata;
                }
                
                payment.amount = amount ? parseFloat(amount) : payment.amount;
                payment.payment_date = payment_date ? payment_date : payment.payment_date;
                payment.status = status ? status : payment.status;                
                RegistrationPaymentsService.update(payment);
                payment.metadata = JSON.stringify(payment.metadata);
                
            }
            
            MapasCulturais.Messages.success("Pagamentos editados com sucesso"); 
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
            
            var  metadata = JSON.parse(payment.metadata);
            var csv = metadata.csv_line;
            
            var dataPayment = new Date(payment.payment_date);
            var result = {
                id: payment.id,
                number: payment.number,
                status:payment.status,
                amount: payment.amount,
                registration_id: payment.registration_id,
                payment_date: dataPayment,
                metadataView: (typeof csv !== 'undefined') ? csv.OBSERVACOES : "",
                metadata: metadata
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
                        var metadata = JSON.parse(headers()['api-metadata']);                       
                        if(metadata.page <= metadata.numPages){
                            $("#page").val(metadata.page);
                        }else{
                            $("#btn-load-more").fadeOut(100);
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
        };
    }]);

})(angular);