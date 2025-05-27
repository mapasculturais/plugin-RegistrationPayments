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
        },
        opportunityPhase() {
            return this.entity;
        }
    },

    methods: {
        toggleConfigPayment(){
            let toggle = !this.entity.active_payment_phase
            this.entity.active_payment_phase = toggle;
            this.entity.save();
            window.dispatchEvent(new CustomEvent('activePaymentPhase', {detail:{toggle:toggle}}));
        },
    },
});