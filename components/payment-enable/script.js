app.component('payment-enable', {
    template: $TEMPLATES['payment-enable'],

    props: {
        entity: {
            type: Entity,
            required: true,
        },
    },

    computed: {
       
    },

    data() {
        return {
        }
    },

    methods: {
        active() {
            this.entity.has_payment_phase = !this.entity.has_payment_phase;
            this.entity.save();
        }
    },
});