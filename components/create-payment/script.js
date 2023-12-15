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
            return $MAPAS.config.createPayment.statusDic;
        },
    },

    data() {
        return {
            payment: {
                metadata: {
                    csv_line: {
                        OBSERVACOES: ''
                    }
                }
            },
        }
    },

    methods: {
        save() {
            this.payment.amount = parseFloat(this.payment.amount);
            const api = new API('payment');
            let url = Utils.createUrl('payment', 'createMultiple', { opportunity: this.entity.id });
            api.POST(url, this.payment).then(res => res.json()).then(data => {
            });
        },

    },
});