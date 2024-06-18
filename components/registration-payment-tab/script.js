app.component('registration-payment-tab', {
    template: $TEMPLATES['registration-payment-tab'],

    props: {
        
    },

    data() {
        let paymentData = $MAPAS.config.registrationPaymentTab.paymentData;
        const firstPhase = new Entity('registration', paymentData.id);
        firstPhase.populate(paymentData);

        return {
            entity: firstPhase
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