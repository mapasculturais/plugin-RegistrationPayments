app.component('opportunity-payment-table', {
    template: $TEMPLATES['opportunity-payment-table'],

    props: {
        opportunity: {
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
                opportunity: `EQ(${this.opportunity.id})`,
                status: 'GTE(0)',
            }
            return args
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
        }
    },

    setup() {
        // os textos estão localizados no arquivo texts.php deste componente 
        const messages = useMessages();
        const text = Utils.getTexts('opportunity-payment-table')
        return { text, messages }
    },

    data() {
        const api = new API();
        return {
            api
        }
    },

    methods: {
        setStatus(payment) {
            let url = Utils.createUrl('payment', 'single', {id: payment._id});
            this.api.PATCH(url, {status:payment.status}).then(res => res.json()).then(data => {
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
    },
});
