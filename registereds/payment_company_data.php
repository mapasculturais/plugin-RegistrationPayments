<?php

use MapasCulturais\i;

$payment_company_data = [
    'payment_company_data_name' => [
        'label' => i::__('Nome ou Razão Social'),
        'type' => 'string',
        'validations' => [
            'required' => \MapasCulturais\i::__("Nome ou Razão Social é obrigatório")
        ]
    ],
    'payment_company_data_registration_type' => [
        'label' => i::__('Tipo social'),
        'type' => 'select',
        'options' => [
            1 => i::__('Pessoa física - CPF'),
            2 => i::__('Pessoa jurídica - CNPJ'),
        ],
        'validations' => [
            'required' => \MapasCulturais\i::__("Tipo social é obrigatório")
        ]
    ],
    'payment_company_data_registration_number' => [
        'label' => i::__('CPF/CNPJ'),
        'type' => 'string',
        'field_type' => 'fieldMask',
        'validations' => [
            'required' => \MapasCulturais\i::__("CPF/CNPJ é obrigatório")
        ]
    ],
    'payment_company_data_bank' => [
        'label' => i::__('Banco'),
        'type' => 'string',
        'default' => '001'
    ],
    'payment_company_data_branch' => [
        'label' => i::__('Agência'),
        'type' => 'string',
        'validations' => [
            'required' => \MapasCulturais\i::__("A agência agência é obrigatória")
        ]
    ],
    'payment_company_data_branch_dv' => [
        'label' => i::__('Dígito verificador da agência é obrigatório'),
        'type' => 'string'
    ],
    'payment_company_data_account' => [
        'label' => i::__('Conta'),
        'type' => 'string',
        'validations' => [
            'required' => \MapasCulturais\i::__("A conta é obrigatória")
        ]
    ],
    'payment_company_data_account_dv' => [
        'label' => i::__('Dígito verificador da conta'),
        'type' => 'string',
        'validations' => [
            'required' => \MapasCulturais\i::__("O dígito verificador da conta é obrigatório")
        ]
    ],
    'payment_company_data_agreement' => [
        'label' => i::__('Convênio'),
        'type' => 'string',
        'validations' => [
            'required' => \MapasCulturais\i::__("O Convênio é obrigatório")
        ]
    ],
    'payment_registration_from' => [
        'label' => i::__('Data de início'),
        'type' => 'datetime',
        'validations' => [
            'required' => \MapasCulturais\i::__("Data de início é obrigatório")
        ]
    ],
    'payment_registration_to' => [
        'label' => i::__('Data final'),
        'type' => 'datetime',
        'validations' => [
            'required' => \MapasCulturais\i::__("Data final é obrigatório")
        ]
    ]
];
