app.component('registration-payment-timeline', {
    template: $TEMPLATES['registration-payment-timeline'],

    props: {
        opportunity: {
            type: Entity,
            required: true,
        },
        isOpportunity: {
            type: Boolean,
            required: true,
        },
    },

    setup() {
        const tabsState = Vue.inject('tabsProvider');
        
        const changeTab = () => {
            const paymentDataTab = tabsState.tabs.find(tab => tab.slug === 'payment');
            
            if(paymentDataTab) {
                tabsState.activeTab = paymentDataTab;
            }
        }

        return {
            changeTab
        };
    },


    computed: {

    },


    data() {
        return {

        }
    },

    methods: {
        isPaymentDataOpen() {
            if((this.opportunity.payment_registration_from.isPast() || this.opportunity.payment_registration_from.isToday()) && this.opportunity.payment_registration_to.isFuture()) {
                return true;
            }

            return false;
        }
    },
});