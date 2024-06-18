app.component('opportunity-payment-table', {
    template: $TEMPLATES['opportunity-payment-table'],

    props: {
        opportunity: {
            type: Entity,
            required: true,
        },
    },
    
    computed: {
        lastPhase() {
            let phase = null;
            $MAPAS.opportunityPhases.forEach((item) => {
                if(item.isLastPhase) {
                    phase = item;
                }
            });

            return phase;
        },
        phasesIds() {
            return $MAPAS.config.opportunityPaymentTable.phasesIds;
        },
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
        paymentProcessedFiles() {
            return this.lastPhase.payment_processed_files;
        },
        paymentProcessed() {
            
            if(this.lastPhase?.files && this.lastPhase.files['import-financial-validator-files']){
                let opportunityFiles = this.lastPhase.files['import-financial-validator-files'];

                Object.keys(opportunityFiles).forEach(key => {
                    let index = parseInt(key);
                    let file = opportunityFiles[index];
                    let name = file.name;  
                    let url = file.url;
                    let id = file.id;
                    let processed = false;
                    let entityFile = file;
                
                    this.importedFiles[name] = {id, name, url,processed, entityFile};
                });
                
                if(this.paymentProcessedFiles) {
                    Object.keys(this.paymentProcessedFiles).forEach(name => {
                        let dateTime = this.paymentProcessedFiles[name];
                    
                        if (this.importedFiles[name]) {
                            this.importedFiles[name].dateTime = dateTime;
                            this.importedFiles[name].processed = true;
                        }
                    });
                }
               
                
                return this.importedFiles;
            } else {
                return null;
            }
        },
        cnabProcessed() {
            if(this.lastPhase?.files && this.lastPhase.files['export-cnab-files']){
                let opportunityFiles = this.lastPhase.files['export-cnab-files'];
                let importedFiles = {};
                
                console.log(this.lastPhase);
                
                Object.keys(opportunityFiles).forEach(key => {
                    let index = parseInt(key);
                    let file = opportunityFiles[index];
                    let name = file.name;  
                    let url = file.url;
                    let id = file.id;
                    let processed = false;
                    let dateTime = file.createTimestamp
                
                    importedFiles[name] = {id, name, url,processed,dateTime };
                });

                return importedFiles;
            } else {
                return null;
            }
        },
    },

    setup() {
        // os textos estão localizados no arquivo texts.php deste componente 
        const messages = useMessages();
        const text = Utils.getTexts('opportunity-payment-table')
        return { text, messages }
    },

    data() {
        const phasesId = $MAPAS.config.opportunityPaymentTable.phasesIds;
        const api = new API();
        return {
            processFileLoading: false,
            query: {
                opportunity: `IN(${phasesId})`,
                status:`GTE(0)`,
                '@permissions': 'view'
            },
            filters: {
                paymentFrom: '',
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
            if(event.target.checked) {
                this.filters.status.push(event.target.value)
            }else{
                this.filters.status.splice(this.filters.status.indexOf(event.target.value), 1)
            }

            this.query['status'] = this.filters.status.length > 0 ? `IN(${this.filters.status})` : `GTE(0)`;
            entities.refresh();
        },

        showAllStatus(entities) {
            if (!this.$refs.allStatus.checked) {
                this.filters.status = [];
                this.query['status'] = `GTE(0)`;
            } else {
                for (let status of this.statusList) {
                    if (!this.filters.status?.includes(status.value)) {
                        this.filters.status.push(status.value);
                    }
                }
                this.query['status'] = `IN(${this.filters.status})`;
            }
            
            entities.refresh();
        },

        clearFilters(entities) {
            this.$refs.allStatus.checked = false;
            this.filters.status = [];
            this.filters.paymentFrom = '';
            this.filters.paymentTo = '';
            this.query['status'] = `GTE(0)`;
            delete this.query['paymentDate'];
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
        },
        processFile(file) {
            const messages = useMessages();
            const api = new API();
            let args = {
                opportunity_id: this.opportunity.id,
                file_id: file.id
            };
            let url = Utils.createUrl('payment', 'import', args);
            
            this.processFileLoading = true;
            api.POST(url).then(res => res.json()).then(data => {
                if (data?.error) {
                    messages.error(this.text('processError'));
                    this.response = data
                } else {
                    let date = new McDate(new Date());
                    this.importedFiles[file.name].processed = true;
                    this.importedFiles[file.name].dateTime = date.date('numeric year')+ ' ' + this.text('toThe') + ' ' + date.time('numeric')
                    window.dispatchEvent(new CustomEvent('mcFileClear', {detail:null}));
                    messages.success(this.text('processSuccess'));
                }
                this.processFileLoading = false;
            });
        },
        deletePaymentUnprocessedFile(file) {
            const messages = useMessages();
            delete this.paymentProcessed[file.name];
            file.entityFile.delete();
            messages.success(this.text('successDeletePaymentFile'));
        },

        deletePaymentPhase () {
            this.opportunity.has_payment_phase = !this.opportunity.has_payment_phase;
            this.opportunity.save();
        },
    },
});
