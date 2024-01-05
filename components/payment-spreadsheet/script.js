app.component('payment-spreadsheet', {
    template: $TEMPLATES['payment-spreadsheet'],

    props: {
        entity: {
            type: Entity,
            required: true,
        },

    },

    computed: {},

    data() {
        return {
            dataExport: {}
        }
    },

    methods: {
        exportValidator() {
            const api = new API();
            let args = {
                opportunity_id: this.entity.id
            };
            let url = Utils.createUrl('payment', 'export', args);
            api.POST(url, this.dataExport).then(res => res.json()).then(data => {
                window.open(data.url, '_blank');
            });
        }
    },
});