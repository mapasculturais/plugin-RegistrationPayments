# plugin-RegistrationPayments
Plugin que agrega funcionalidade de gerenciamento dos pagamentos

## Configuração CNAB 240

```
     'RegistrationPayments' => [
            'namespace' => 'RegistrationPayments',
            'config' => [
                'cnab240_enabled' => true, // Habilita ou Desabilita exportação do CNAB240
                'opportunitysCnab' => [ // Configurações de oportunidades
                    '820' => [
                        'settings' => [ // Configurações padrões
                            'social_type' => [ // Tipo de proponente (Pessoa Fisica ou Pessoa Jurídica) Pessoa Fisica = 1 Pessoa Jurídica = 2
                                'PF (Pessoa Física)' => '1',
                                'Grupo/coletivo sem constituição jurídica (representado por Pessoa Física)' => '1',
                                'PJ (Pessoa Jurídica), incluindo MEI (Microempreendedor Individual)' => '2',
                            ],
                            'release_type' => [
                                1 => '01', // Corrente BB
                                2 => '05', // Poupança BB
                                3 => '03' // Outros bancos
                            ]

                        ],
                        'social_type' => 17169, // ID campo que define o tipo de ptoponente, (Pessoa Fisica ou Pessoa Jurídica)
                        'proponent_name' => [ // Chave 1 Pessoa física Chave 2 Pessoa Jurídica 
                            'dependence' => 'social_type',
                            1 => 17183,
                            2 => 17176
                        ],
                        'proponent_document' => [ // Chave 1 Pessoa física Chave 2 Pessoa Jurídica
                            'dependence' => 'social_type',
                            1 => 17159,
                            2 => 17174
                        ],
                        'address' => 17206, // ID campo que define o endereço do proponente
                        'number' => 17220, // ID campo que define o numero da residência do proponente
                        'complement' => 17221, // ID campo que define o complemento do endereço do proponente
                        'zipcode' => 17222, // ID campo que define o CEP do proponente
                        'city' => 17157, // ID campo que define a cidade do proponente
                        'account_type' => 17214, // ID campo que define o tipo de conta bancária do proponente
                        'bank' => 17213, // ID campo que define a o banco do proponente
                        'branch' => 17216, // ID campo que define a agência bancária do proponente
                        'branch_dv' => 17217, // ID campo que define o DV da agência bancária do proponente
                        'account' => 17218, // ID campo que define a conta bancária do proponente
                        'account_dv' => 17219 // ID campo que define o DV da conta bancária do proponente
                    ]
                ]
            ]
        ],
```