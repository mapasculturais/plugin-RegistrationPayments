app.component('opportunity-payment-table', {
    template: $TEMPLATES['opportunity-payment-table'],

    props: {
        opportunity: {
            type: Entity,
            required: true,
        }
    },
    
    computed: {
        statusList() {
            return $MAPAS.config.payment.statusDic;
        },
        select() {
            return "id,registration.{id,number,category,owner.{id,name,cpf,cnpj,documento}},paymentDate,amount,metadata,status"
        },
        headers() {
            return [
                { text: "Inscrição", value: "registration.number", slug:"registration" },
                { text: "Categoria", value: "registration.category", slug:"category" },
                { text: "Agente", value: "registration.owner.name", slug: "agent"},
                { text: "Documento", value: "registration.owner.documento", slug: "documento"},
                { text: "Previsão de pagamento", value: "paymentDate"},
                { text: "Valor", value: "amount"},
                { text: "Status", value: "status"},
                { text: "Opções", value: "options"},
            ]
        },
        paymentProcessed() {
            
            if(this.opportunity?.files && this.opportunity.files['import-financial-validator-files']){
                let opportunityFiles = this.opportunity.files['import-financial-validator-files'];
                let dateTimeData = $MAPAS.requestedEntity.payment_processed_files;
                
                Object.keys(opportunityFiles).forEach(key => {
                    let index = parseInt(key);
                    let file = opportunityFiles[index];
                    let name = file.name;  
                    let url = file.url;
                
                    this.importedFiles[name] = { name, url };
                });
                
                Object.keys(dateTimeData).forEach(name => {
                    let dateTime = dateTimeData[name];
                
                    if (this.importedFiles[name]) {
                        this.importedFiles[name].dateTime = dateTime;
                    }
                });
                
                return this.importedFiles;
            } else {
                return null;
            }
        }
    },

    setup() {
        // os textos estão localizados no arquivo texts.php deste componente 
        const messages = useMessages();
        const text = Utils.getTexts('opportunity-payment-table')
        return { text, messages }
    },

    data() {
        const api = new API();
        return {
            query: {
                opportunity: `EQ(${this.opportunity.id})`,
                status:`GTE(0)`,
            },
            filters: {
                paymentFrom:'',
                paymentTo: '',
                status: []
            },
            api,
            revisions: {},
            amount: 0.00,
            importedFiles: {}
        }
    },

    methods: {
        formatDateInput(date) {
            return new Date(date);
        },
        updatePayment(payment, refresh) {
            let url = Utils.createUrl('payment', 'single', {id: payment._id});
            let args = {
                amount: this.amount?.replace(/\s/g, '').replace(',', '.'),
                paymentDate: payment.__originalValues.paymentDate,
                metadata: {
                    csv_line: {
                        OBSERVACOES: payment.metadata.csv_line.OBSERVACOES
                    }
                },
            };
            this.api.PATCH(url, args).then(res => res.json()).then(data => {
                if (data?.error) {
                    this.messages.error(this.text('editPaymentError'));
                } else {
                    refresh();
                    this.messages.success(this.text('editPaymentSuccess'));
                }
            });
        },
        editPayment(open, entity) {
            this.amount = this.amountToString(entity.amount);
            open();
        },
        delPayment(payment, refresh) {
            let url = Utils.createUrl('payment', 'single', {id: payment._id});
            this.api.DELETE(url).then(res => res.json()).then(data => {
                if (data?.error) {
                    this.messages.error(this.text('deletePaymentError'));
                } else {
                    refresh();
                    this.messages.success(this.text('deletePaymentSuccess'));
                }
            });
        },

        setStatus(selected, entity) {
            let url = Utils.createUrl('payment', 'single', {id: entity._id});
            this.api.PATCH(url, {status:selected.value}).then(res => res.json()).then(data => {
                if (data?.error) {
                    this.messages.error(this.text('setStatusError'));
                } else {
                    this.messages.success(this.text('setStatusSuccess'));
                }
            });
        },

        amountToString(amount) {
            return parseFloat(amount).toLocaleString($MAPAS.config.locale, { style: 'currency', currency: __('currency', 'opportunity-payment-table')  });
        },

        statusTostring(status) {
            let result = null;
            Object.values(this.statusList).forEach((item) => {
                if(item.value == status) {
                    result = item;
                }
            });

            return result;
        },

        statusFilter(event,entities) {            
            this.filters.status?.includes(event.target.value) ? this.filters.status.splice(this.filters.status.indexOf(event.target.value), 1) : this.filters.status.push(event.target.value);
            this.query['status'] = this.filters.status.length > 0 ? `IN(${this.filters.status})` :  `GTE(0)`;
            entities.refresh();
        },

        change(event,entities) {            
            if(this.filters.paymentFrom && this.filters.paymentTo){
                this.query['paymentDate'] = `BET(${this.filters.paymentFrom},${this.filters.paymentTo})`
            }
            entities.refresh();
        },
        downloadFile(url) {
            window.open(url, '_blank');
        }
    },
});
