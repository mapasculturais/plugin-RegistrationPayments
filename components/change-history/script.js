app.component('change-history', {
    template: $TEMPLATES['change-history'],

    props: {
        entity: {
            type: Entity,
            required: true,
        }
    },

    computed: {},

    data() {
        const api = new API();
        return {
            api,
            revisions: {}
        }
    },

    methods: {
        getRevisions(payment, open) {
            let url = Utils.createUrl('payment', 'revision', {paymentId: payment._id});
            this.api.GET(url).then(res => res.json()).then(data => {
                this.revisions = data;
            });
            open();
        },
        formatMessage(item) {
            const message = item && item.message ? item.message : '';
            const messageWithoutPeriod = message.replace(/\.$/, '');
            return `${messageWithoutPeriod}`;
        },
        formatDate(dateString) {
            // DD/MM/YYYY
            const date = new Date(dateString);
            const day = date.getDate().toString().padStart(2, '0');
            const month = (date.getMonth() + 1).toString().padStart(2, '0');
            const year = date.getFullYear();
            return `${day}/${month}/${year}`;
        },
        showStatus(val) {
            let value = val;
            if(value === 0) {
                return 'Pendente';
            } else if(value === 1) {
                return 'Em processo';
            } else if(value === 2) {
                return 'Falha';
            } else if(value === 3) {
                return 'Exportado';
            } else if(value === 8) {
                return 'Disponível';
            } else if(value === 10) {
                return 'Pago';
            }
        },
        amountToString(amount) {
            return parseFloat(amount).toLocaleString($MAPAS.config.locale, { style: 'currency', currency: __('currency', 'opportunity-payment-table')  });
        },
    },
});