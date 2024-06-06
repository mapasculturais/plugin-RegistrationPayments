app.component('payment-config', {
    template: $TEMPLATES['payment-config'],

    props: {
        entity: {
            type: Entity,
            required: true,
        },
    },

    computed: {
        steps() {
            const items = [this.entity];
            return items.map((item, index) => {
                return {
                    item,
                    index,
                    number: index+1,
                    open: () => this.open(index),
                    close: () => this.close(index),
                    toggle: () => this.toggle(index),
                    active: !!this.activeItems[index]
                }
            });
        }
    },

    data() {
        const activeItems = {};
        if (this.opened !== undefined) {
            activeItems[this.opened] = true;
        }

        return {
            activeItems
        }
    },

    methods: {
        documentMask() {
            let social_type = this.entity.payment_company_data_registration_type;
            return !social_type || social_type == 1 ? "###.###.###-##" : "##.###.###/####-##"
        },

        open(index) {
            if (!this.allowMultiple) {
                this.activeItems = {};
            }
            this.activeItems[index] = true;
        },

        close(index) {
            delete this.activeItems[index];
        },

        toggle(index) {
            if (this.activeItems[index]) {
                this.close(index);
            } else {
                this.open(index);
            }
        },

        deletePaymentPhase () {
            this.entity.has_payment_phase = !this.entity.has_payment_phase;
            this.entity.save();
            this.unregisterProperties();
        },
        unregisterProperties() {
            delete $DESCRIPTIONS.opportunity.payment_company_data_name
            delete $DESCRIPTIONS.opportunity.payment_company_data_registration_type
            delete $DESCRIPTIONS.opportunity.payment_company_data_registration_number
            delete $DESCRIPTIONS.opportunity.payment_company_data_bank
            delete $DESCRIPTIONS.opportunity.payment_company_data_branch
            delete $DESCRIPTIONS.opportunity.payment_company_data_branch_dv
            delete $DESCRIPTIONS.opportunity.payment_company_data_account
            delete $DESCRIPTIONS.opportunity.payment_company_data_account_dv
            delete $DESCRIPTIONS.opportunity.payment_company_data_agreement
        }
    },
});