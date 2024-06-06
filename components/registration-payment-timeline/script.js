app.component('registration-payment-timeline', {
    template: $TEMPLATES['registration-payment-timeline'],

    props: {
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

    },
});