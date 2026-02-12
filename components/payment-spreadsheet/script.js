app.component('payment-spreadsheet', {
    template: $TEMPLATES['payment-spreadsheet'],

    props: {
        entity: {
            type: Entity,
            required: true,
        },
        entities: {
            type: Array,
        }

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
        hasErrors() {
            return this.response?.error ? true : false;
        },
    },

    setup() {
        // os textos estão localizados no arquivo texts.php deste componente 
        const text = Utils.getTexts('payment-spreadsheet')
        return { text }
    },

    data() {
        return {
            uploadLoading: false,
            processFileLoading: false,
            response: {},
            dataExport: {},
            newFile: null,
            process: {
                active: false
            }
        }
    },

    methods: {
        exportValidator() {
            const messages = useMessages();
            const api = new API();
            let args = {
                opportunity_id: this.entity.id
            };
            let url = Utils.createUrl('payment', 'export', args);
            api.POST(url, this.dataExport).then(res => res.json()).then(data => {
                if (data?.error) {
                    messages.error(this.text('exportError'));
                    this.response = data
                } else {
                    messages.success(this.text('exportSuccess'));
                    const downloadUrl = Utils.createUrl('payment', 'downloadFile', { file_id: data.id });
                    window.open(downloadUrl, '_blank');
                }
            });
        },
        setFile(file) {
            this.newFile = file;
        },
        upload() {
            let data = {
                description: this.newFile.name,
                group: 'import-financial-validator-files',   
            };
            
            this.uploadLoading = true;
            this.lastPhase.upload(this.newFile, data).then((response) => {
                window.dispatchEvent(new CustomEvent('mcFileClear', {detail:null}));
                this.process.id = response.id;
                this.process.active = true;
                this.uploadLoading = false;
            });
        },
        processFile(modal) {
            const messages = useMessages();
            const api = new API();
            let args = {
                opportunity_id: this.lastPhase.id,
                file_id: this.process.id
            };
            let url = Utils.createUrl('payment', 'import', args);
            
            this.processFileLoading = true; 
            api.POST(url).then(res => res.json()).then(data => {
                this.processFileLoading = false;
                const hasError = data?.error === true || Array.isArray(data?.error);
                const errorPayload = hasError && (Array.isArray(data) || (data && '0' in data))
                    ? (data[0] ?? data['0'])
                    : data;
                if (hasError) {
                    const notFound = errorPayload?.registration_not_found;
                    if (Array.isArray(notFound) && notFound.length > 0) {
                        const list = notFound.join('<br>');
                        messages.warning(this.text('registrationNotFound') + ' <br>' + list);
                    }
                    this.response = errorPayload;
                } else {
                    modal.close();
                    this.entities.refresh();
                    this.process.active = false;
                    messages.success(this.text('importSuccess'));
                }
            });
        }
    },
});