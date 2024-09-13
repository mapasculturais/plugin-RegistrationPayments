app.component('registration-payment-form', {
    template: $TEMPLATES['registration-payment-form'],

    props: {
        entity: {
            type: Entity,
            required: true
        }
    },

    computed: {
        opportunity() {
            return $MAPAS.config.registrationPaymentTab.opportunity;
        },
    },

    setup() {
        const text = Utils.getTexts('registration-payment-tab')
        return { text }
    },

    methods: {

        formatDate(value) {
            if(value) {
                let date = new McDate(value);
                return `${date.date('numeric year')} ${this.text('as')} ${date.time('numeric')} `;
            }
        },
        isEditable() {
            if(this.entity?.sentTimestamp == null || this.entity?.sentTimestamp == '') {
                return true;
            }

        },
        
        showButtons() {
            let show = false
            if(this.opportunity.payment_registration_to) {
                const paymentTo = new McDate(this.opportunity.payment_registration_to)
                const currentDate = new Date();
                show = paymentTo?._date > currentDate
            }
            
            return show;
        }
    }
});