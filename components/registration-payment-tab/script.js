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
        async sendPaymentData() {
            const messages = useMessages();
            try {
                this.entity.payment_sent_timestamp = new Date();
                await this.entity.save();
                const success = await this.entity.POST('validateEntity', {});
                if (success) {
                    this.dataSent = true;
                    messages.success(this.text('Validado'));
                }
            } catch (error) {
                console.error(error);
            }
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