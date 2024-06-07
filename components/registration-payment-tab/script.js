app.component('registration-payment-tab', {
    template: $TEMPLATES['registration-payment-tab'],

    props: {
        entity: {
            type: Entity,
            required: true,
        },
    },

    computed: {

    },

    setup() {
        const text = Utils.getTexts('registration-payment-tab')
        return { text }
    },

    methods: {
        sendPaymentData() { 
            const properties = {
                payment_social_type: this.entity.social_type,
                payment_proponent_name: this.entity.payment_proponent_name,
                payment_proponent_document: this.entity.payment_proponent_document,
                payment_account_type: this.entity.payment_account_type,
                payment_bank: this.entity.payment_bank,
                payment_branch: this.entity.payment_branch,
                payment_branch_dv: this.entity.payment_branch_dv,
                payment_account: this.entity.payment_account,
                payment_account_dv: this.entity.payment_account_dv,
            };
            
            this.entity.POST('validateProperties', {data: properties, callback:(response) => {
                if(!response.error) {
                    this.entity.payment_sent_timestamp = new Date();
                    this.entity.save();
                }
            }})
        },

        formatDate(value) {
            if(value) {
                let date = new McDate(value);
                return `${date.date('numeric year')} ${this.text('as')} ${date.time('numeric')} `;
            }
        },
        isEditable() {
            if(this.entity.payment_sent_timestamp == null || this.entity.payment_sent_timestamp == '') {
                return true;
            }

            return false;
        },
        
        showButtons() {
            const paymentTo = this.entity.opportunity.payment_registration_to
            const currentDate = new Date();
            
            return paymentTo._date > currentDate;
        }
    }
});