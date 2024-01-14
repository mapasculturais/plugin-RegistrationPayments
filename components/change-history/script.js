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
            revisions: {},
            dataRevision: this.skeleton()
        }
    },

    methods: {
        getRevisions(open) {
            let url = Utils.createUrl('payment', 'revision', {paymentId: this.entity._id});
            this.api.GET(url).then(res => res.json()).then(data => {
                this.revisions = data;
            });
            open();
        },
        getDataRevision(dataId, agent) {
            const revisionData = this.revisions.dataRevisions[dataId];
            this.dataRevision = {
                agent: agent,
                amount: revisionData.amount.value,
                status: revisionData.status.value,
                date: revisionData.paymentDate.value.date,
                observation: revisionData.metadata.value.csv_line.OBSERVACOES
            };
        },
        formatMessage(item) {
            const message = item && item.message ? item.message : '';
            const messageWithoutPeriod = message.replace(/\.$/, '');
            return `${messageWithoutPeriod}`;
        },
        formatDate(dateString) {
            const date = new Date(dateString);
            const day = date.getDate().toString().padStart(2, '0');
            const month = (date.getMonth() + 1).toString().padStart(2, '0');
            const year = date.getFullYear();
            const hours = date.getHours().toString().padStart(2, '0');
            const minutes = date.getMinutes().toString().padStart(2, '0');
            const seconds = date.getSeconds().toString().padStart(2, '0');
        
            return `${day}/${month}/${year} ${hours}:${minutes}:${seconds}`;
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
        skeleton() {
            return {
                amount: null,
                agent: null,
                status: null,
                date: null,
                observation: null
            };
        },
        closeModal(close) {
            this.dataRevision = this.skeleton();
            close();
        }
    }
});