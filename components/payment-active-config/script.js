app.component('payment-active-config', {
    template: $TEMPLATES['payment-active-config'],

    props: {
        entity: {
            type: Entity,
            required: true,
        },
        phases: {
            type: Array,
            required: true
        }
    },

    computed: {
        firstPhase(){
            return this.phases[0];
        }
    },

    methods: {
        configPayment(){
            let toggle = !this.entity.active_payment_phase
            this.entity.active_payment_phase = toggle;

            if (this.entity.active_payment_phase !== toggle) {
                this.entity.active_payment_phase = toggle;
                this.entity.save();
            }
        },
    },
});