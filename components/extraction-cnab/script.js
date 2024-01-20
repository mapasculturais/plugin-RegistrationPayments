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
            return this.response?.error ? true : false;
        },
        fieldError(prop) {
            return this.response?.error && this.response?.data[prop] ? true : false
        },
        async exportCnab() {
            const api = new API();
            const messages = useMessages();
            let url = Utils.createUrl('payment', 'generateCnab', { opportunity_id: this.entity.id });
            api.POST(url, this.cnabData).then(res => res.json()).then(data => {
                if (data?.error) {
                    messages.error(this.text('generateCnabError'));
                    this.response = data
                } else {
                    messages.success(this.text('generateCnabSuccess'));
                    this.cnabData = this.skeleton();
                    window.open(data.url, '_blank');
                    this.response = {}
                }
            });
        },
        setCnabType(option) {
            this.cnabData.lotType = option.value;
        },
    },
});