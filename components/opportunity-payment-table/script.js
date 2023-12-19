app.component('opportunity-payment-table', {
    template: $TEMPLATES['opportunity-payment-table'],

    props: {
        entity: {
            type: Entity,
            required: true,
        }
    },
    
    computed: {
        statusList() {
            return $MAPAS.config.payment.statusDic;
        },
        query() {
            const args = {
                opportunity: `EQ(${this.entity.id})`,
                status: 'GTE(0)',
            }
            return args
        },
        select() {
            return "id,registration.{id,number},paymentDate,amount,metadata,status"
        },
        headers() {
            return [
                { text: "Inscrição", value: "registration.number", slug:"registration" },
                { text: "Previsão de pagamento", value: "paymentDate"},
                { text: "Valor", value: "amount"},
                { text: "Status", value: "status"},
            ]
        }
    },

    data() {
        return {}
    },

    methods: {
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
    },
});
