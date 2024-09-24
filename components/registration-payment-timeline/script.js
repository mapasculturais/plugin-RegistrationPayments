app.component('registration-payment-timeline', {
    template: $TEMPLATES['registration-payment-timeline'],

    props: {
        registration: {
            type: Entity,
            required: true,
        },
        opportunity: {
            type: Entity,
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
        isDataCollectionPhase(opportunity) {
            return opportunity.isDataCollection && opportunity.active_payment_phase && !this.registration.sentTimestamp;
		},
    },
});