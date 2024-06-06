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

    data() {
        return {
            dataSent: false,
        }
    },

    methods: {
        sendPaymentData() { 
            this.entity.payment_sent_timestamp = new Date();
            this.entity.save();

            this.dataSent = true;
        }
    },

    mounted() {
        if(this.entity.payment_sent_timestamp !== null) {
            this.dataSent = true;
        }
    }
});