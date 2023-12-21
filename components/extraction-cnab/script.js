app.component('extraction-cnab', {
    template: $TEMPLATES['extraction-cnab'],

    props: {
        entity: {
            type: Entity,
            required: true,
        }
    },

    data() {
        return {
            cnabData: {
                ts_lot: false
            }
        }
    },

    methods: {
        async exportCnab() {
            const api = new API();
            let url = Utils.createUrl('payment', 'generateCnab', { opportunity_id: this.entity.id });
            api.POST(url, this.cnabData).then(res => res.json()).then(data => {
            });
        }

    },
});