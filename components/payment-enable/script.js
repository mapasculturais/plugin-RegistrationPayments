app.component('payment-enable', {
    template: $TEMPLATES['payment-enable'],

    props: {
        entity: {
            type: Entity,
            required: true,
        },
    },

    computed: {
        
    },

    data() {
        return {

        }
    },

    methods: {
        active() {
            this.entity.has_payment_phase = !this.entity.has_payment_phase;
            this.entity.save();
            this.registerProperties();
        },
        registerProperties() {
            $DESCRIPTIONS.opportunity.payment_company_data_name = {
                "required": true,
                "type": "string",
                "length": null,
                "private": false,
                "available_for_opportunities": false,
                "field_type": "text",
                "label": "Nome ou Razão Social",
                "validations": [],
                "isMetadata": true,
                "isEntityRelation": false
            };
            
            $DESCRIPTIONS.opportunity.payment_company_data_registration_type = {
                "required": true,
                "type": "select",
                "length": null,
                "private": false,
                "available_for_opportunities": false,
                "field_type": "select",
                "options": {
                    "Pessoa física - CPF": "Pessoa física - CPF",
                    "Pessoa jurídica - CNPJ": "Pessoa jurídica - CNPJ"
                },
                "optionsOrder": [
                    "Pessoa física - CPF",
                    "Pessoa jurídica - CNPJ"
                ],
                "label": "Tipo social",
                "validations": [],
                "isMetadata": true,
                "isEntityRelation": false
            };
            
            $DESCRIPTIONS.opportunity.payment_company_data_registration_number = {
                "required": true,
                "type": "string",
                "length": null,
                "private": false,
                "available_for_opportunities": false,
                "field_type": "fieldMask",
                "label": "CPF/CNPJ",
                "validations": [],
                "isMetadata": true,
                "isEntityRelation": false
            };
            
            $DESCRIPTIONS.opportunity.payment_company_data_bank = {
                "required": false,
                "type": "string",
                "length": null,
                "private": false,
                "available_for_opportunities": false,
                "field_type": "text",
                "label": "Banco",
                "default": "001",
                "isMetadata": true,
                "isEntityRelation": false
            };
            
            $DESCRIPTIONS.opportunity.payment_company_data_branch = {
                "required": true,
                "type": "string",
                "length": null,
                "private": false,
                "available_for_opportunities": false,
                "field_type": "text",
                "label": "Agência",
                "validations": [],
                "isMetadata": true,
                "isEntityRelation": false
            };
            
            $DESCRIPTIONS.opportunity.payment_company_data_branch_dv = {
                "required": false,
                "type": "string",
                "length": null,
                "private": false,
                "available_for_opportunities": false,
                "field_type": "text",
                "label": "Dígito verificador da agência",
                "isMetadata": true,
                "isEntityRelation": false
            };
            
            $DESCRIPTIONS.opportunity.payment_company_data_account = {
                "required": true,
                "type": "string",
                "length": null,
                "private": false,
                "available_for_opportunities": false,
                "field_type": "text",
                "label": "Conta",
                "validations": [],
                "isMetadata": true,
                "isEntityRelation": false
            };
            
            $DESCRIPTIONS.opportunity.payment_company_data_account_dv = {
                "required": true,
                "type": "string",
                "length": null,
                "private": false,
                "available_for_opportunities": false,
                "field_type": "text",
                "label": "Dígito verificador da conta",
                "validations": [],
                "isMetadata": true,
                "isEntityRelation": false
            };
            
            $DESCRIPTIONS.opportunity.payment_company_data_agreement = {
                "required": true,
                "type": "string",
                "length": null,
                "private": false,
                "available_for_opportunities": false,
                "field_type": "text",
                "label": "Convênio",
                "validations": [],
                "isMetadata": true,
                "isEntityRelation": false
            };
        },
    },
});