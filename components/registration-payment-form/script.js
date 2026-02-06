app.component('registration-payment-form', {
    template: $TEMPLATES['registration-payment-form'],

    props: {
        entity: {
            type: Entity,
            required: true
        },
         step: {
            type: Entity
        },
    },

    setup() {
        const text = Utils.getTexts('registration-payment-tab')
        return { text }
    },

    mounted() {
        const self = this;
        window.addEventListener('message', (event) => {
            if (event.data?.type == 'evaluationForm.openForm' && event.data.fieldName != 'field_payment') {
                this.$refs.paymentForm.classList.remove('field-shadow');
                self.isEvaluationFormOpen = false;
                self.paymentFieldOpen = false
            }

            if (event.data?.type == 'evaluationRegistration.setClass' && event.data.fieldId == 'payment') {
                this.setClass(event.data?.className)
            }
        });

        if (this.evaluationType === "documentary" && $MAPAS.config.documentaryEvaluationForm) {
            if (!$MAPAS.config.documentaryEvaluationForm.fieldsInfo) {
                $MAPAS.config.documentaryEvaluationForm.fieldsInfo = {};
            }
            $MAPAS.config.documentaryEvaluationForm.fieldsInfo['field_payment'] = { label: this.text('informacoes bancarias'), fieldId: "payment" }
        }
    },

    data() {
        const evaluationData = $MAPAS.config.evaluationActions?.currentEvaluation?.evaluationData?.payment;
        return {
            isEvaluationFormOpen: false,
            evaluationClass: evaluationData?.evaluation || 'empty',
            paymentFieldOpen: false
        }
    },

    computed: {
        opportunity() {
            return $MAPAS.config.registrationPaymentForm.opportunity;
        },
        evaluationType() {
            return $MAPAS.config.registrationPaymentForm.evaluationType;
        },
    },

    methods: {
        showForm() {
            if($MAPAS.route.action === "evaluation") {
                return true;
            }

            if(this.entity.opportunity.registrationSteps.length <= 1) {
                return true;
            }

            if((this.entity.opportunity.payment_step_form == 0 || !this.entity.opportunity.payment_step_form) && this.step.id == this.entity.opportunity.registrationSteps[0].id) {
                return true;
            }

            if(this.step?.id == this.entity.opportunity?.payment_step_form) {
                return true;
            }

            return false;
        },

        setClass(className) {
            const $field = this.$refs.paymentForm;

            $field.classList.remove('evaluation-empty');
            $field.classList.remove('evaluation-valid');
            $field.classList.remove('evaluation-invalid');

            $field.classList.add(className);
        },

        isRequired(field) {
            return $DESCRIPTIONS.registration[field].required
        },

        formatDate(value) {
            if (value) {
                let date = new McDate(value);
                return `${date.date('numeric year')} ${this.text('as')} ${date.time('numeric')} `;
            }
        },

        isEditable() {
            if (this.entity.status == 0 || this.entity.active_payment_phase) {
                return true;
            }

            // Verifica se o usuário de suporte tem permissão para editar campos de pagamento
            const editableFields = this.entity.editableFields || [];
            const paymentFields = this.paymentDataFields();
            const hasEditablePaymentField = paymentFields.some(field => editableFields.includes(field));
            
            if (hasEditablePaymentField) {
                return true;
            }

            return false;
        },

        showButtons() {
            let show = false
            if (this.opportunity.payment_registration_to) {
                const paymentTo = new McDate(this.opportunity.payment_registration_to)
                const currentDate = new Date();
                show = paymentTo?._date > currentDate
            }

            return show;
        },

        paymentDataFields() {
            return [
                'payment_social_type',
                'payment_proponent_name',
                'payment_proponent_document',
                'payment_account_type',
                'payment_bank',
                'payment_branch',
                'payment_branch_dv',
                'payment_account',
                'payment_account_dv',
            ]
        },
        toggleEvaluationForm() {
            if (this.evaluationType === "documentary") {
                window.postMessage({
                    type: 'evaluationRegistration.clearStyles',
                });

                if (this.isEvaluationFormOpen && this.paymentFieldOpen) {
                    this.$refs.paymentForm.classList.remove('field-shadow');

                    window.postMessage({
                        type: 'evaluationForm.closeForm',
                        element: '',
                        fieldName: 'field_payment',
                        fieldId: 'payment',
                        fieldType: 'field'
                    })
                } else {
                    this.paymentFieldOpen = true;
                    this.$refs.paymentForm.classList.add('field-shadow');

                    window.postMessage({
                        type: 'evaluationForm.openForm',
                        element: '',
                        fieldName: 'field_payment',
                        fieldId: 'payment',
                        fieldType: 'field'
                    });
                }

                this.isEvaluationFormOpen = !this.isEvaluationFormOpen;
            }

        }
    }
});