app.component('export-filters-spreadsheet', {
    template: $TEMPLATES['export-filters-spreadsheet'],
    mounted() {
        window.addEventListener("entityTableSearchText", this.setKeyWords);
    },
    props: {
        entity: {
            type: Entity,
            required: true,
        },
        filters: {
            type: Object,
            required: true
        },
    },
    setup() {
        // os textos estão localizados no arquivo texts.php deste componente 
        const text = Utils.getTexts('export-filters-spreadsheet')
        return { text }
    },
    data() {
        return {}
    },
    methods: {
        setKeyWords(data) {
            this.filters.search = data.detail.searchText;
        },
        async exportSheet() {
            const api = new API();
            const messages = useMessages();
            this.filters.opportunity = this.entity.id

            let url = Utils.createUrl('payment', 'exportFilter', { opportunity: this.entity.id });
            api.POST(url, this.filters).then(res => res.json()).then(data => {

                messages.success(this.text('exportSuccess'));
                window.open(data.url, '_blank');
            }
            );
        }
    }
});
