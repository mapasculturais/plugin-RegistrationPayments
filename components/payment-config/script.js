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
        },
    },
});