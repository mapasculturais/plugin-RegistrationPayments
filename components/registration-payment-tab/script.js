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
            this.entity.payment_sent_timestamp = new Date();
            this.entity.save();

            this.dataSent = true;
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
        }
    },

    mounted() {
      
    }
});