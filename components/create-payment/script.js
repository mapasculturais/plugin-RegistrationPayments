app.component('create-payment', {
    template: $TEMPLATES['create-payment'],

    props: {
        entity: {
            type: Entity,
            required: true,
        },
        entities: {
            type: Array,
        }
    },

    created() {
        this.payment.registrationStatus = "";
        this.payment.category = "";
    },

    computed: {
        registrationStatus() {
            return $MAPAS.config.payment.registrationStatus;
        },
        statusList() {
            return $MAPAS.config.payment.statusDic;
        },
        categories() {
            return this.entity.registrationCategories;
        }
    },

    setup() {
        // os textos estão localizados no arquivo texts.php deste componente 
        const text = Utils.getTexts('create-payment')
        return { text }
    },

    data() {
        return {
            response: {},
            payment: this.skeleton(),
        }
    },

    methods: {
        skeleton() {
            return {
                metadata: {
                    csv_line: {
                        OBSERVACOES: ''
                    }
                },
                createType: 'registration_id',
            }
        },
        hasErrors() {
            return this.response?.error ? true : false;
        },
        setPaymentStatus(selected) {
            this.payment.status = selected.value
        },
        setRegistrationsStatus(selected) {
            this.payment.registrationStatus = selected.value;
        },
        setCategory(selected) {
            this.payment.category = selected.value;
        },
        save(modal) {
            const messages = useMessages();
            this.payment.amount = this.payment.amount?.replace(/\s/g, '').replace(',', '.');
            const api = new API('payment');
            let url = Utils.createUrl('payment', 'createMultiple', { opportunity: this.entity.id });

            if(this.payment.createType === "registrationStatus") {
                this.payment.registrationStatus = 10;
            }

            api.POST(url, this.payment).then(res => res.json()).then(data => {
                if (data?.error) {
                    messages.error(this.text('createPaymentsError'));
                    this.response = data
                } else {
                    modal.close()
                    messages.success(this.text('createPaymentsSuccess'));
                    this.payment = this.skeleton();
                    this.response = {}
                    this.entities.refresh();
                }
            });
        },

    },
});