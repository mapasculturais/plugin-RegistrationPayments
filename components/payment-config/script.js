app.component('payment-config', {
    template: $TEMPLATES['payment-config'],

    setup() {
        const text = Utils.getTexts('payment-config')
        return { text }
    },

    props: {
        entity: {
            type: Entity,
            required: true,
        },
        toggleConfigPayment: {
            required: true,
        },
        phase: {
            type: Entity,
            required: true
        }
    },

    computed: {
        steps() {
            const items = [this.entity];
            return items.map((item, index) => {
                return {
                    item,
                    index,
                    number: index + 1,
                    open: () => this.open(index),
                    close: () => this.close(index),
                    toggle: () => this.toggle(index),
                    active: !!this.activeItems[index]
                }
            });
        }
    },

    data() {

        this.entity.payment_step_form = this.entity.payment_step_form || this.phase.registrationSteps[0].id;

        const activeItems = {};
        if (this.opened !== undefined) {
            activeItems[this.opened] = true;
        }

        return {
            activeItems
        }
    },

    methods: {
        stepNameResolve(step) {
            for (const [index, item] of this.phase.registrationSteps.entries()) {
                if(step.id === item.id) {
                    return step.name || `${index+1}. ${this.text('etapa')}`
                }
            }
        },

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

        deletePaymentPhase() {
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