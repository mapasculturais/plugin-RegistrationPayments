app.component('create-payment', {
    template: $TEMPLATES['create-payment'],

    props: {
        entity: {
            type: Entity,
            required: true,
        }
    },

    computed: {
        status() {
            return $MAPAS.config.payment.statusDic;
        },
    },

    setup() {
        // os textos estão localizados no arquivo texts.php deste componente 
        const text = Utils.getTexts('create-payment')
        return { text }
    },

    data() {
        return {
            response: {},
            payment: this.skeleton(),
        }
    },

    methods: {
        skeleton() {
            return {
                metadata: {
                    csv_line: {
                        OBSERVACOES: ''
                    }
                }
            }
        },
        hasErrors() {
            return this.response?.error ? true : false;
        },
        save(modal) {
            const messages = useMessages();
            this.payment.amount = parseFloat(this.payment.amount);
            const api = new API('payment');
            let url = Utils.createUrl('payment', 'createMultiple', { opportunity: this.entity.id });
            api.POST(url, this.payment).then(res => res.json()).then(data => {
                if (data?.error) {
                    messages.error(this.text('createPaymentsError'));
                    this.response = data
                } else {
                    messages.success(this.text('createPaymentsSuccess'));
                    this.payment = this.skeleton();
                    this.response = {}
                    modal.close()
                }
            });
        },

    },
});