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

        }
    },

    methods: {
        async validate() {
            const messages = useMessages();
            try {
                await this.entity.save();
                const success = await this.entity.POST('validateEntity', {});
                if (success) {
                    messages.success(this.text('Validado'));
                }
            } catch (error) {
                console.error(error);
            }
        },

        sendPaymentData() { 
            this.entity.payment_sent_timestamp = new Date();
            this.entity.save();
        }
    },
});