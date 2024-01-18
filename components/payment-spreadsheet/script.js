app.component('payment-spreadsheet', {
    template: $TEMPLATES['payment-spreadsheet'],

    props: {
        entity: {
            type: Entity,
            required: true,
        },

    },

    computed: {
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
                    window.open(data.url, '_blank');
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
            
            this.entity.upload(this.newFile, data).then((response) => {
                this.process.id = response.id;
                this.process.active = true;
            });
        },
        processFile() {
            const messages = useMessages();
            const api = new API();
            let args = {
                opportunity_id: this.entity.id,
                file_id: this.process.id
            };
            let url = Utils.createUrl('payment', 'import', args);
            
            api.POST(url).then(res => res.json()).then(data => {
                if (data?.error) {
                    messages.error(this.text('importError'));
                    this.response = data
                } else {
                    messages.success(this.text('importSuccess'));
                }
            });
        }
    },
});