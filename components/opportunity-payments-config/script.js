app.component('opportunity-payments-config', {
    template: $TEMPLATES['opportunity-payments-config'],

    created() {
        if(!this.entity.paymentsTabEnabled){
            this.entity.paymentsTabEnabled = "0";
        }
    },
    props: {
        entity:{
            type: Entity,
            required: true,
        }
    },

    data() {
        return {

        }
    },

    methods: {
       
    },
});
