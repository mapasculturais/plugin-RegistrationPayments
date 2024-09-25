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
            return $MAPAS.config.registrationPaymentForm.opportunity;
        },
    },

    created() {
       const fields = this.paymentDataFields();
       fields.forEach(field => {
            this.entity[field] = $MAPAS.config.registrationPaymentForm.paymentData[field] || null
       });
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
            if(this.entity.status == 0 || this.entity.active_payment_phase) {
                return true;
            }

            return false;
        },
        
        showButtons() {
            let show = false
            if(this.opportunity.payment_registration_to) {
                const paymentTo = new McDate(this.opportunity.payment_registration_to)
                const currentDate = new Date();
                show = paymentTo?._date > currentDate
            }
            
            return show;
        },
        paymentDataFields() {
            return [
                'payment_social_type',
                'payment_proponent_name',
                'payment_proponent_document',
                'payment_account_type',
                'payment_bank',
                'payment_branch',
                'payment_branch_dv',
                'payment_account',
                'payment_account_dv',
            ]
        }
    }
});