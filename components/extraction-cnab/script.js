app.component('extraction-cnab', {
    template: $TEMPLATES['extraction-cnab'],

    props: {
        entity: {
            type: Entity,
            required: true,
        }
    },

    setup() {
        // os textos estão localizados no arquivo texts.php deste componente 
        const text = Utils.getTexts('extraction-cnab')
        return { text }
    },

    data() {
        return {
            exportCnabLoading: false,
            response: {},
            cnabData: this.skeleton()
        }
    },

    methods: {
        skeleton() {
            return {
                identifier: "",
                ts_lot: false,
                lotType: 1,
                registrationFilter: "",
            }
        },
        hasErrors() {
            return this.response?.error === true;
        },
        fieldError(prop) {
            const data = this.response?.data;
            return this.response?.error === true && data != null && typeof data === 'object' && data[prop];
        },
        errorMessages() {
            const data = this.response?.data;
            if (!this.response?.error || data == null) return [];
            if (Array.isArray(data)) return data.filter(Boolean);
            return Object.values(data).filter(Boolean);
        },
        async exportCnab() {
            const api = new API();
            const messages = useMessages();
            let url = Utils.createUrl('payment', 'generateCnab', { opportunity_id: this.entity.id });
            this.exportCnabLoading = true;
            api.POST(url, this.cnabData).then(res => {
                return res.json().then(data => {
                    if (!res.ok || data?.error) {
                        messages.error(this.text('generateCnabError'));
                        this.response = (data && typeof data === 'object') ? data : { error: true, data: [] };
                    } else {
                        messages.success(this.text('generateCnabSuccess'));
                        const downloadUrl = Utils.createUrl('payment', 'downloadFile', { file_id: data.id });
                        window.open(downloadUrl, '_blank');
                        this.response = {};
                    }
                    this.exportCnabLoading = false;
                });
            }).catch(() => {
                messages.error(this.text('generateCnabError'));
                this.response = { error: true, data: [] };
                this.exportCnabLoading = false;
            });
        },
        setCnabType(option) {
            this.cnabData.lotType = parseInt(option.value);
        },
    },
});