<?php

namespace RegistrationPayments;

use Normalizer;
use CnabPHP\Remessa;
use MapasCulturais\i;
use MapasCulturais\App;
use  MapasCulturais\Definitions;
use RegistrationPayments\Payment;
use MapasCulturais\Entities\Registration;
use BankValidator\classes\BankCodeMapping;
use BankValidator\Validator as BankValidator;
use RegistrationPayments\JobTypes\GenerateCnab;
use BankValidator\classes\exceptions\NotRegistredBankCode;

require_once 'vendor/autoload.php';
class Plugin extends \MapasCulturais\Plugin{

    protected static $instance = null;

    function __construct(array $config = [])
    {
        $self = $this;
        $config += [
            'fields' => [],
            'fields_tratament' => function ($registration, $field) {
                if(!$this->config['fields']){return;}
                $result = [                   
                    'CPF' => function () use ($registration, $field) {
                        
                        $field = $this->prefixFieldId($field);
                        return preg_replace('/[^0-9]/i', '', $registration->$field);
                    },
                    'NOME_COMPLETO' => function () use ($registration, $field) {                        
                        $field = $this->prefixFieldId($field);
                        return $registration->$field;
                    },
                ];

                $callable = $result[$field] ?? null;

                return $callable ? $callable() : null;
            },
            'cnab240_enabled' => function($entity) use($self){
                if(in_array($entity->id, $self->config['opportunitys_cnab_active']) || $entity->paymentCnabEnabled == '1'){
                    return true;
                }

                return false;
            },
            'opportunitys_cnab_active' => [],
            'opportunitysCnab' => [],
            'cnab240_company_data' => [],
            'fromToDvBranch' => [
                '0' => '3',
                '1' => '4',
                '2' => '5',
                '3' => '6',
                '4' => '7',
                '5' => '8',
                '6' => '9',
                '7' => 'X',
                '8' => '0',
                '9' => '1',
                'X' => '2',
                'x' => '2',
            ],
            'file_type' => [
                1 => 'Corrente BB', // Corrente BB
                2 => 'Poupança BB', // Poupança BB
                3 => 'Outros bancos' // Outros bancos
            ],
            'treatments' => [
                'social_type' => function($registration, $field, $settings, $metadata, $dependence){
                    if($field =="category"){
                        $id = $registration->$field;                        
                        return $settings['social_type'][$id];
                    }

                    $field_id = "field_".$field;
                    $id = $registration->$field_id;
                    return $settings['social_type'][$id];
                },
                'proponent_name' => function($registration, $field, $settings,$metadata, $dependence) use ($self){
                    if($field =="category"){
                        $id = $registration->$field;  
                        $field_id = "field_".$this->config['opportunitysCnab'][$registration->opportunity->id]['proponent_name'][$settings['social_type'][$id]];
                        $_value = $registration->$field_id ?: $metadata[$field_id] ?? null;;
                    } else {
                        $field_id = "field_".$field;
                        $_value = $registration->$field_id ?: $metadata[$field_id] ?? null;;
                    }
                    
                    if($self->isJson($_value)){
                        return $self->normalizeString(json_decode($_value));
                    }else{
                        return $self->normalizeString($_value);
                    }
                   
                },
                'proponent_document' => function($registration, $field, $settings,$metadata, $dependence){
                    if($field =="category"){                      
                        $id = $registration->$field;                          
                        $field_id = "field_".$this->config['opportunitysCnab'][$registration->opportunity->id]['proponent_document'][$settings['social_type'][$id]];
                        return $registration->$field_id ?: $metadata[$field_id] ?? null;;
                    }

                    $field_id = "field_".$field;
                    return $registration->$field_id ?: $metadata[$field_id] ?? null;;
                },
                'address' => function($registration, $field, $settings,$metadata, $dependence){
                    $field_id = "field_".$field;
                    return $registration->$field_id ?: $metadata[$field_id] ?? null;;
                },
                'number' => function($registration, $field, $settings,$metadata, $dependence){
                    $field_id = "field_".$field;
                    return $registration->$field_id ?: $metadata[$field_id] ?? null;;
                },
                'complement' => function($registration, $field, $settings,$metadata, $dependence){
                    $field_id = "field_".$field;
                    return $registration->$field_id ?: $metadata[$field_id] ?? null;;
                },
                'zipcode' => function($registration, $field, $settings,$metadata, $dependence){
                    $field_id = "field_".$field;
                    return $registration->$field_id ?: $metadata[$field_id] ?? null;;
                },
                'city' => function($registration, $field, $settings,$metadata, $dependence){
                    $field_id = "field_".$field;
                    return $registration->$field_id ?: $metadata[$field_id] ?? null;;
                },
                'account_type' => function($registration, $field, $settings,$metadata, $dependence){
                    $field_id = "field_".$field;
                    return $registration->$field_id ?: $metadata[$field_id] ?? null;;
                },
                'bank' => function($registration, $field, $settings,$metadata, $dependence){
                    $field_id = "field_".$field;
                    return $registration->$field_id ?: $metadata[$field_id] ?? null;;
                },
                'branch' => function($registration, $field, $settings,$metadata, $dependence){
                    $field_id = "field_".$field;
                    return $registration->$field_id ?: $metadata[$field_id] ?? null;;
                },
                'branch_dv' => function($registration, $field, $settings,$metadata, $dependence){
                    $field_id = "field_".$field;
                    return $registration->$field_id ?: $metadata[$field_id] ?? null;;
                },
                'account' => function($registration, $field, $settings,$metadata, $dependence){

                    $field_id = "field_".$field;
                    $data = $registration->$field_id ?: $metadata[$field_id] ?? null;;

                    $account_type_field_id = "field_".$this->config['opportunitysCnab'][$registration->opportunity->id]['account_type'];
                    $bank_field_id = "field_".$this->config['opportunitysCnab'][$registration->opportunity->id]['bank'];
                    $search = "banco do brasil";
                    
                    if(in_array($metadata[$account_type_field_id], ['Conta poupança']) && preg_match("/{$search}/", mb_strtolower($metadata[$bank_field_id])) && substr($data, 0, 2) != "51"){
                       
                        $account_temp = "51" . $data;

                        if(strlen($account_temp) < 9){
                            $result = "51".str_pad($data, 7, 0, STR_PAD_LEFT);
                        }else{
                            $result = "51" . $data;
                        }

                    }else{
                        $result = $data;
                    }

                   return $result;
                },
                'account_dv' => function($registration, $field, $settings,$metadata, $dependence){
                    $field_id = "field_".$field;
                    $data = $metadata[$field_id] ?? 0;
                    
                    if(!is_int($data) && (strlen($data) > 2)){
                        if(preg_match("/x/", mb_strtolower($data))){
                            $data = "X";
                        }else{
                            $data = 0;
                        }
                    }
                    
                    $account_type_field_id = "field_".$this->config['opportunitysCnab'][$registration->opportunity->id]['account_type'];
                    $bank_field_id = "field_".$this->config['opportunitysCnab'][$registration->opportunity->id]['bank'];
                    $search = "banco do brasil";

                    if(in_array($metadata[$account_type_field_id], ['Conta poupança']) && preg_match("/{$search}/", mb_strtolower($metadata[$bank_field_id]))){
                        return $this->config['fromToDvBranch'][$data];
                    }else{
                        $result =  $data;
                    }

                    return $result;

                }
            ],
        ];

        parent::__construct($config);

        self::$instance = $this;
    }

    function _init() {
        $app = App::i();
        $app->view->enqueueStyle('app-v2', 'registrationpayments-v2', 'css/plugin-RegistrationPayments.css');
        $driver = $app->em->getConfiguration()->getMetadataDriverImpl();
        $driver->addPaths([__DIR__]);

        $plugin = $this;
        
        // @todo implementar cache para não fazer essa consulta a cada requisição
        if (!$app->repo('DbUpdate')->findBy(['name' => 'create table payment'])) {
            $conn = $app->em->getConnection();
            $conn->beginTransaction();

            $conn->executeQuery("CREATE SEQUENCE payment_id_seq INCREMENT BY 1 MINVALUE 1 START 1");
            $conn->executeQuery("
                CREATE TABLE payment (
                    id INT NOT NULL, 
                    registration_id INT NOT NULL, 
                    opportunity_id INT NOT NULL, 
                    created_by_user_id INT DEFAULT NULL, 
                    amount DOUBLE PRECISION NOT NULL, 
                    payment_date DATE NOT NULL, 
                    metadata JSON DEFAULT '{}' NOT NULL, 
                    create_timestamp TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, 
                    update_timestamp TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, 
                    status SMALLINT NOT NULL, 
                PRIMARY KEY(id))");
            $conn->executeQuery("CREATE INDEX IDX_6D28840D833D8F43 ON payment (registration_id)");
            $conn->executeQuery("CREATE INDEX IDX_6D28840D9A34590F ON payment (opportunity_id)");
            $conn->executeQuery("CREATE INDEX IDX_6D28840D7D182D95 ON payment (created_by_user_id)");
            $conn->executeQuery("COMMENT ON COLUMN payment.metadata IS '(DC2Type:json_array)'");
            $conn->executeQuery("ALTER TABLE payment ADD CONSTRAINT FK_6D28840D833D8F43 FOREIGN KEY (registration_id) REFERENCES registration (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");
            $conn->executeQuery("ALTER TABLE payment ADD CONSTRAINT FK_6D28840D9A34590F FOREIGN KEY (opportunity_id) REFERENCES opportunity (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE");
            $conn->executeQuery("ALTER TABLE payment ADD CONSTRAINT FK_6D28840D7D182D95 FOREIGN KEY (created_by_user_id) REFERENCES usr (id) NOT DEFERRABLE INITIALLY IMMEDIATE");

            $app->disableAccessControl();
            $db_update = new \MapasCulturais\Entities\DbUpdate;
            $db_update->name = 'create table payment';
            $db_update->save(true);
            $app->enableAccessControl();
            $conn->commit();
        }

        $app->hook("module(OpportunityPhases).dataCollectionPhaseData", function(&$mout_simplify) {
            $mout_simplify.=",payment_processed_files";
        });

        $app->hook('mapas.printJsObject:before', function () {            
            $statusDic = [
                ['value' => Payment::STATUS_PENDING, 'label' => i::__("Pendente")],
                ['value' => Payment::STATUS_PROCESSING, 'label' => i::__("Em processo")],
                ['value' => Payment::STATUS_FAILED, 'label' => i::__("Falha")],
                ['value' => Payment::STATUS_EXPORTED, 'label' => i::__("Exportado")],
                ['value' => Payment::STATUS_AVAILABLE, 'label' => i::__("Disponível")],
                ['value' => Payment::STATUS_PAID, 'label' => i::__("Pago")],
            ];

            $registrations = Registration::getStatusesNames();

            foreach($registrations as $status => $status_name){
                if(in_array($status,[0,1,2,3,8,10])){
                    $registrationStatus[] = ["label" => $status_name, "value" => $status];
                }
            }
    
            $this->jsObject['config']['payment']['registrationStatus'] = $registrationStatus;
            $this->jsObject['config']['payment']['statusDic'] = $statusDic;
            $this->jsObject['EntitiesDescription']['payment'] = Payment::getPropertiesMetadata();
        });

        $app->hook('doctrine.emum(object_type).values', function(&$values) {
            $values['Payment'] = Payment::class;
        });

        $app->hook("template(opportunity.edit.tabs):end", function() use ($app) {
            $entity = $this->controller->requestedEntity;
            $this->part("payments/opportunity-payments-tab", ['entity' => $entity]);
        });

        $app->hook('repo(RegistrationPayments.Payment).getIdsByKeywordDQL.join', function (&$joins, $keyword) {
            $joins .= " LEFT JOIN e.registration reg ";
            $joins .= " LEFT JOIN reg.owner owner ";
        });

        $app->hook('repo(RegistrationPayments.Payment).getIdsByKeywordDQL.where', function (&$where, $keyword) {
            $or = trim($where) ? "OR" : "";
            $where .= " {$or} reg.number LIKE lower(:keyword) ";
            $where .= " OR unaccent(lower(owner.name)) LIKE unaccent(lower(:keyword)) ";
        });

        //Insere botão que ativa o pagamento dentro do mc-stepper-vertical
        $app->hook('template(opportunity.edit.mc-stepper-vertical:after)', function() use ($app) {
           /** @var Theme $this */ 
           $this->part('payments/opportunity-payments-enable');
        });

        $app->hook('template(opportunity.edit.mc-stepper-vertical:end)', function() use ($app) {
            /** @var Theme $this */ 
            $this->part('payments/opportunity-payments-config');
        });

    }

    function enqueueScriptsAndStyles() {
        
        $app = App::i();

        $app->view->enqueueScript('app', 'regitration-payments', 'js/ng.registrationPayments.js', ['entity.module.opportunity']);
        $app->view->enqueueStyle('app', 'fontawesome', 'https://use.fontawesome.com/releases/v5.8.2/css/all.css');
        $app->view->enqueueStyle('app', 'regitration-payments', 'css/payments.css');
        $app->view->jsObject['angularAppDependencies'][] = 'ng.registrationPayments';

    }


    function register () {

        $app = App::i();
        $app->registerController('payment', Controller::class);
        
        $self = $this;
        $app->hook('view.includeAngularEntityAssets:after', function () use ($self) {
            $self->enqueueScriptsAndStyles();
        });

        $this->registerOpportunityMetadata('has_payment_phase', [
            'label' => 'Terá fase de pagamento',
            'type' => 'boolean',
            'default' => false,
        ]);
        
        $this->registerRegistrationMetadata('financial_validator_filename', [
            'label' => 'Nome do arquivo de criação de pagamentos',
            'type' => 'json',
            'private' => true,
            'default_value' => '[]'
        ]);

        $this->registerRegistrationMetadata('financial_validator_raw', [
            'label' => 'Arquivo de pagamento raw data (csv row)',
            'type' => 'json',
            'private' => true,
            'default_value' => '[]'
        ]);

        $this->registerOpportunityMetadata('payment_processed_files', [
            'label' => 'Arquivos do Validador Financeiro Processados',
            'type' => 'json',
            'private' => true,
            'default_value' => '{}'
        ]);

        $this->registerMetadata('RegistrationPayments\\Payment','payment_identifier', [
            'label' => i::__('Identificação do pagamento'),
            'type' => 'string',
            'private' => true,
        ]);

        $this->registerMetadata('MapasCulturais\Entities\Opportunity','payment_lot_export', [
            'label' => i::__('Lotes exportados'),
            'type' => 'json',
            'private' => true,
            'default' => '[]',
        ]);

        $this->registerMetadata(
            'MapasCulturais\Entities\Opportunity',
            'paymentFieldsPending',
            [
                'label' => 'Campos de dados bancarios pendente de criação',
                'type' => 'boolean',
                'default' => false,
            ]
        );

        $app->hook('<<GET|PUT|PATCH|DELETE>>(<<opportunity|payment>>.<<*>>):before', function() use ($self) {
            $opportunity = $this->getRequestedEntity();
            
            if($opportunity->has_payment_phase) {
                $self->registerOpportunityMetadata('payment_company_data_name', [
                    'label' => i::__('Nome ou Razão Social'),
                    'type' => 'string',
                    'validations' => [
                        'required' => \MapasCulturais\i::__("Nome ou Razão Social é obrigatório")
                    ]
                ]);
        
                $self->registerOpportunityMetadata('payment_company_data_registration_type', [
                    'label' => i::__('Tipo social'),
                    'type' => 'select',
                    'options' => [
                        1 => i::__('Pessoa física - CPF'),
                        2 => i::__('Pessoa jurídica - CNPJ'),
                    ],
                    'validations' => [
                        'required' => \MapasCulturais\i::__("Tipo social é obrigatório")
                    ]
                ]);
        
                $self->registerOpportunityMetadata('payment_company_data_registration_number', [
                    'label' => i::__('CPF/CNPJ'),
                    'type' => 'string',
                    'field_type' => 'fieldMask',
                    'validations' => [
                        'required' => \MapasCulturais\i::__("CPF/CNPJ é obrigatório")
                    ]
                ]);
        
                $self->registerOpportunityMetadata('payment_company_data_bank', [
                    'label' => i::__('Banco'),
                    'type' => 'string',
                    'default' => '001'
                ]);
        
                $self->registerOpportunityMetadata('payment_company_data_branch', [
                    'label' => i::__('Agência'),
                    'type' => 'string',
                    'validations' => [
                        'required' => \MapasCulturais\i::__("A dígito agência é obrigatória")
                    ]
                ]);
                
                $self->registerOpportunityMetadata('payment_company_data_branch_dv', [
                    'label' => i::__('Dígito verificador da agência'),
                    'type' => 'string'
                ]);
                $self->registerOpportunityMetadata('payment_company_data_account', [
                    'label' => i::__('Conta'),
                    'type' => 'string',
                    'validations' => [
                        'required' => \MapasCulturais\i::__("A conta é obrigatória")
                    ]
                ]);
                $self->registerOpportunityMetadata('payment_company_data_account_dv', [
                    'label' => i::__('Dígito verificador da conta'),
                    'type' => 'string',
                    'validations' => [
                        'required' => \MapasCulturais\i::__("O dígito verificador da conta é obrigatório")
                    ]
                ]);
               
                $self->registerOpportunityMetadata('payment_company_data_agreement', [
                    'label' => i::__('Convênio'),
                    'type' => 'string',
                    'validations' => [
                        'required' => \MapasCulturais\i::__("O Convênio é obrigatório")
                    ]
                ]);
            }
        });
       

        $app->hook("entity(Opportunity).registrationMetadata", function() use ($self) {
            if($this->has_payment_phase) {

                $self->registerMetadata(
                    'MapasCulturais\Entities\Registration',
                    'payment_social_type',
                    [
                        'label' => 'Tipo social',
                        'type' => 'select',
                        'options' => [
                            1 => i::__('Pessoa física'),
                            2 => i::__('Pessoa jurídica'),
                        ],
                        'validations' => [
                            'required' => \MapasCulturais\i::__("O Tipo social é obrigatório")
                        ]
                    ]
                );

                $self->registerMetadata(
                    'MapasCulturais\Entities\Registration',
                    'payment_proponent_name',
                    [
                        'label' => i::__('Nome do proponente'),
                        'type' => 'string',
                        'validations' => [
                            'required' => \MapasCulturais\i::__("O nome do proponente é obrigatório")
                        ]
                    ]
                );

                $self->registerMetadata(
                    'MapasCulturais\Entities\Registration',
                    'payment_proponent_document',
                    [
                        'label' => i::__('Documento do proponente'),
                        'type' => 'string',
                        'field_type' => 'fieldMask',
                        'validations' => [
                            'required' => \MapasCulturais\i::__("O documento obrigatório")
                        ]
                    ]
                );

                $self->registerRegistrationMetadata('payment_account_type', [
                    'label' => i::__('Tipo de conta'),
                    'type' => 'select',
                    'options' => [
                        1 => i::__('Conta corrente'),
                        2 => i::__('Conta poupança'),
                    ],
                    'validations' => [
                        'required' => \MapasCulturais\i::__("O tipo de conta obrigatório")
                    ]
                ]);
                $self->registerRegistrationMetadata('payment_bank', [
                    'label' => i::__('Banco'),
                    'type' => 'select',
                    'options' => [
                        '1' => i::__("Banco Do Brasil S.A (BB) - 1"),
                        '237' => i::__("Bradesco S.A - 237"),
                        '260' => i::__("Nu Pagamentos S.A (Nubank) - 260"),
                        '290' => i::__("Pagseguro Internet S.A - 290"),
                        '323' => i::__("Mercado Pago – Conta Do Mercado - 323"),
                        '341' => i::__("Itaú Unibanco S.A - 341"),
                        '104' => i::__("Caixa Econômica Federal (CEF) - 104"),
                        '33'  => i::__("Banco Santander Brasil S.A - 33"),
                        '212' => i::__("Banco Original S.A - 212"),
                        '422' => i::__("Banco Safra S.A - 422"),
                        '745' => i::__("Banco Citibank S.A - 745"),
                        '4'   => i::__("Banco Do Nordeste Do Brasil S.A. - 4"),
                        '7'   => i::__("Bndes (Banco Nacional Do Desenvolvimento Social) - 7"),
                        '117' => i::__("Advanced Cc Ltda - 117"),
                        '172' => i::__("Albatross Ccv S.A - 172"),
                        '188' => i::__("Ativa S.A Investimentos - 188"),
                        '280' => i::__("Avista S.A - 280"),
                        '80'  => i::__("B&T Cc Ltda - 80"),
                        '654' => i::__("Banco A.J. Renner S.A - 654"),
                        '246' => i::__("Banco Abc Brasil S.A - 246"),
                        '121' => i::__("Banco Agibank S.A - 121"),
                        '25'  => i::__("Banco Alfa S.A. - 25"),
                        '641' => i::__("Banco Alvorada S.A - 641"),
                        '65'  => i::__("Banco Andbank S.A - 65"),
                        '96'  => i::__("Banco B3 S.A - 96"),
                        '36'  => i::__("Banco Bbi S.A - 36"),
                        '47'  => i::__("Banco Bco Do Estado De Sergipe S.A - 47"),
                        '250' => i::__("Banco Bcv - 250"),
                        '318' => i::__("Banco Bmg S.A - 318"),
                        '107' => i::__("Banco Bocom Bbm S.A - 107"),
                        '63'  => i::__("Banco Bradescard - 63"),
                        '122' => i::__("Banco Bradesco Berj S.A - 122"),
                        '204' => i::__("Banco Bradesco Cartoes S.A - 204"),
                        '394' => i::__("Banco Bradesco Financiamentos S.A - 394"),
                        '218' => i::__("Banco Bs2 S.A - 218"),
                        '208' => i::__("Banco Btg Pactual S.A - 208"),
                        '336' => i::__("Banco C6 S.A – C6 Bank - 336"),
                        '473' => i::__("Banco Caixa Geral Brasil S.A - 473"),
                        '412' => i::__("Banco Capital S.A - 412"),
                        '40'  => i::__("Banco Cargill S.A - 40"),
                        '320' => i::__("Banco Ccb Brasil S.A - 320"),
                        '266' => i::__("Banco Cedula S.A - 266"),
                        '739' => i::__("Banco Cetelem S.A - 739"),
                        '233' => i::__("Banco Cifra - 233"),
                        '241' => i::__("Banco Classico S.A - 241"),
                        '95'  => i::__("Banco Confidence De Câmbio S.A - 95"),
                        '222' => i::__("Banco Crédit Agricole Br S.A - 222"),
                        '505' => i::__("Banco Credit Suisse (Brl) S.A - 505"),
                        '69'  => i::__("Banco Crefisa S.A - 69"),
                        '3'   => i::__("Banco Da Amazonia S.A - 3"),
                        '83'  => i::__("Banco Da China Brasil S.A - 83"),
                        '707' => i::__("Banco Daycoval S.A - 707"),
                        '70'  => i::__("Banco De Brasília (Brb) - 70"),
                        '335' => i::__("Banco Digio S.A - 335"),
                        '37'  => i::__("Banco Do Estado Do Pará S.A - 37"),
                        '196' => i::__("Banco Fair Cc S.A - 196"),
                        '265' => i::__("Banco Fator S.A - 265"),
                        '224' => i::__("Banco Fibra S.A - 224"),
                        '626' => i::__("Banco Ficsa S.A - 626"),
                        '94'  => i::__("Banco Finaxis - 94"),
                        '612' => i::__("Banco Guanabara S.A - 612"),
                        '12'  => i::__("Banco Inbursa - 12"),
                        '604' => i::__("Banco Industrial Do Brasil S.A - 604"),
                        '653' => i::__("Banco Indusval S.A - 653"),
                        '77'  => i::__("Banco Inter S.A - 77"),
                        '630' => i::__("Banco Intercap S.A - 630"),
                        '249' => i::__("Banco Investcred Unibanco S.A - 249"),
                        '184' => i::__("Banco Itaú Bba S.A - 184"),
                        '29'  => i::__("Banco Itaú Consignado S.A - 29"),
                        '479' => i::__("Banco Itaubank S.A - 479"),
                        '376' => i::__("Banco J.P. Morgan S.A - 376"),
                        '217' => i::__("Banco John Deere S.A - 217"),
                        '76'  => i::__("Banco Kdb Brasil S.A. - 76"),
                        '757' => i::__("Banco Keb Hana Do Brasil S.A - 757"),
                        '300' => i::__("Banco La Nacion Argentina - 300"),
                        '600' => i::__("Banco Luso Brasileiro S.A - 600"),
                        '243' => i::__("Banco Máxima S.A - 243"),
                        '389' => i::__("Banco Mercantil Do Brasil S.A - 389"),
                        '389' => i::__("Banco Mercantil Do Brasil S.A. - 389"),
                        '370' => i::__("Banco Mizuho S.A - 370"),
                        '746' => i::__("Banco Modal S.A - 746"),
                        '66'  => i::__("Banco Morgan Stanley S.A - 66"),
                        '456' => i::__("Banco Mufg Brasil S.A - 456"),
                        '169' => i::__("Banco Olé Bonsucesso Consignado S.A - 169"),
                        '111' => i::__("Banco Oliveira Trust Dtvm S.A - 111"),
                        '79'  => i::__("Banco Original Do Agronegócio S.A - 79"),
                        '712' => i::__("Banco Ourinvest S.A - 712"),
                        '623' => i::__("Banco Pan - 623"),
                        '611' => i::__("Banco Paulista - 611"),
                        '643' => i::__("Banco Pine S.A - 643"),
                        '747' => i::__("Banco Rabobank Internacional Do Brasil S.A - 747"),
                        '633' => i::__("Banco Rendimento S.A - 633"),
                        '494' => i::__("Banco Rep Oriental Uruguay - 494"),
                        '741' => i::__("Banco Ribeirão Preto - 741"),
                        '120' => i::__("Banco Rodobens S.A - 120"),
                        '743' => i::__("Banco Semear S.A - 743"),
                        '754' => i::__("Banco Sistema - 754"),
                        '366' => i::__("Banco Societe Generale Brasil - 366"),
                        '637' => i::__("Banco Sofisa S.A (Sofisa Direto) - 637"),
                        '464' => i::__("Banco Sumitomo Mitsui Brasil S.A - 464"),
                        '82'  => i::__("Banco Topázio S.A - 82"),
                        '634' => i::__("Banco Triangulo S.A (Banco Triângulo) - 634"),
                        '18'  => i::__("Banco Tricury S.A - 18"),
                        '655' => i::__("Banco Votorantim S.A - 655"),
                        '610' => i::__("Banco Vr S.A - 610"),
                        '119' => i::__("Banco Western Union - 119"),
                        '124' => i::__("Banco Woori Bank Do Brasil S.A - 124"),
                        '348' => i::__("Banco Xp S/A - 348"),
                        '756' => i::__("Banco (Banco Cooperativo Do Brasil) - 756"),
                        '21'  => i::__("Banco Banestes S.A - 21"),
                        '41'  => i::__("Banrisul – Banco Do Estado Do Rio Grande Do Sul S.A - 41"),
                        '268' => i::__("Barigui Ch - 268"),
                        '81'  => i::__("Bbn Banco Brasileiro De Negocios S.A - 81"),
                        '75'  => i::__("Bco Abn Amro S.A - 75"),
                        '213' => i::__("Bco Arbi S.A - 213"),
                        '24'  => i::__("Bco Bandepe S.A - 24"),
                        '74'  => i::__("Bco. J.Safra S.A - 74"),
                        '144' => i::__("Bexs Banco De Cambio S.A. - 144"),
                        '253' => i::__("Bexs Cc S.A - 253"),
                        '134' => i::__("Bgc Liquidez Dtvm Ltda - 134"),
                        '752' => i::__("Bnp Paribas Brasil S.A - 752"),
                        '17'  => i::__("Bny Mellon Banco S.A - 17"),
                        '755' => i::__("Bofa Merrill Lynch Bm S.A - 755"),
                        '301' => i::__("Bpp Instituição De Pagamentos S.A - 301"),
                        '126' => i::__("Br Partners Bi - 126"),
                        '125' => i::__("Brasil Plural S.A Banco - 125"),
                        '92'  => i::__("Brk S.A - 92"),
                        '173' => i::__("Brl Trust Dtvm Sa - 173"),
                        '142' => i::__("Broker Brasil Cc Ltda - 142"),
                        '292' => i::__("Bs2 Distribuidora De Títulos E Investimentos - 292"),
                        '11'  => i::__("C.Suisse Hedging-Griffo Cv S.A (Credit Suisse) - 11"),
                        '288' => i::__("Carol Dtvm Ltda - 288"),
                        '130' => i::__("Caruana Scfi - 130"),
                        '159' => i::__("Casa Credito S.A - 159"),
                        '97'  => i::__("Ccc Noroeste Brasileiro Ltda - 97"),
                        '16'  => i::__("Ccm Desp Trâns Sc E Rs - 16"),
                        '286' => i::__("Ccr De Ouro - 286"),
                        '279' => i::__("Ccr De Primavera Do Leste - 279"),
                        '273' => i::__("Ccr De São Miguel Do Oeste - 273"),
                        '89'  => i::__("Ccr Reg Mogiana - 89"),
                        '114' => i::__("Central Cooperativa De Crédito No Estado Do Espírito Santo - 114"),
                        '477' => i::__("Citibank N.A - 477"),
                        '180' => i::__("Cm Capital Markets Cctvm Ltda - 180"),
                        '127' => i::__("Codepe Cvc S.A - 127"),
                        '163' => i::__("Commerzbank Brasil S.A Banco Múltiplo - 163"),
                        '60'  => i::__("Confidence Cc S.A - 60"),
                        '85'  => i::__("Coop Central Ailos - 85"),
                        '98'  => i::__("Credialiança Ccr - 98"),
                        '10'  => i::__("Credicoamo - 10"),
                        '133' => i::__("Cresol Confederação - 133"),
                        '182' => i::__("Dacasa Financeira S/A - 182"),
                        '487' => i::__("Deutsche Bank S.A Banco Alemão - 487"),
                        '140' => i::__("Easynvest – Título Cv S.A - 140"),
                        '149' => i::__("Facta S.A. Cfi - 149"),
                        '285' => i::__("Frente Cc Ltda - 285"),
                        '278' => i::__("Genial Investimentos Cvm S.A - 278"),
                        '138' => i::__("Get Money Cc Ltda - 138"),
                        '64'  => i::__("Goldman Sachs Do Brasil Bm S.A - 64"),
                        '177' => i::__("Guide - 177"),
                        '146' => i::__("Guitta Cc Ltda - 146"),
                        '78'  => i::__("Haitong Bi Do Brasil S.A - 78"),
                        '62'  => i::__("Hipercard Bm S.A - 62"),
                        '189' => i::__("Hs Financeira - 189"),
                        '269' => i::__("Hsbc Banco De Investimento - 269"),
                        '271' => i::__("Ib Cctvm Ltda - 271"),
                        '157' => i::__("Icap Do Brasil Ctvm Ltda - 157"),
                        '132' => i::__("Icbc Do Brasil Bm S.A - 132"),
                        '492' => i::__("Ing Bank N.V - 492"),
                        '139' => i::__("Intesa Sanpaolo Brasil S.A - 139"),
                        '652' => i::__("Itaú Unibanco Holding Bm S.A - 652"),
                        '488' => i::__("Jpmorgan Chase Bank - 488"),
                        '399' => i::__("Kirton Bank - 399"),
                        '495' => i::__("La Provincia Buenos Aires Banco - 495"),
                        '293' => i::__("Lastro Rdv Dtvm Ltda - 293"),
                        '105' => i::__("Lecca Cfi S.A - 105"),
                        '145' => i::__("Levycam Ccv Ltda - 145"),
                        '113' => i::__("Magliano S.A - 113"),
                        '128' => i::__("Ms Bank S.A Banco De Câmbio - 128"),
                        '137' => i::__("Multimoney Cc Ltda - 137"),
                        '14'  => i::__("Natixis Brasil S.A - 14"),
                        '655' => i::__("Neon Pagamentos S.A (Mesmo Código Do Banco Votorantim) - 655"),
                        '237' => i::__("Next Bank (Mesmo Código Do Bradesco) - 237"),
                        '191' => i::__("Nova Futura Ctvm Ltda - 191"),
                        '753' => i::__("Novo Banco Continental S.A Bm - 753"),
                        '613' => i::__("Omni Banco S.A - 613"),
                        '254' => i::__("Parana Banco S.A - 254"),
                        '194' => i::__("Parmetal Dtvm Ltda - 194"),
                        '174' => i::__("Pernambucanas Financ S.A - 174"),
                        '100' => i::__("Planner Corretora De Valores S.A - 100"),
                        '93'  => i::__("Pólocred Scmepp Ltda - 93"),
                        '108' => i::__("Portocred S.A - 108"),
                        '283' => i::__("Rb Capital Investimentos Dtvm Ltda - 283"),
                        '101' => i::__("Renascenca Dtvm Ltda - 101"),
                        '270' => i::__("Sagitur Cc Ltda - 270"),
                        '751' => i::__("Scotiabank Brasil - 751"),
                        '276' => i::__("Senff S.A - 276"),
                        '545' => i::__("Senso Ccvm S.A - 545"),
                        '190' => i::__("Servicoop - 190"),
                        '748' => i::__("Sicredi S.A - 748"),
                        '183' => i::__("Socred S.A - 183"),
                        '118' => i::__("Standard Chartered Bi S.A - 118"),
                        '197' => i::__("Stone Pagamentos S.A - 197"),
                        '340' => i::__("Super Pagamentos S/A (Superdital) - 340"),
                        '143' => i::__("Treviso Cc S.A - 143"),
                        '131' => i::__("Tullett Prebon Brasil Cvc Ltda - 131"),
                        '129' => i::__("Ubs Brasil Bi S.A - 129"),
                        '15'  => i::__("Ubs Brasil Cctvm S.A - 15"),
                        '91'  => i::__("Unicred Central Rs - 91"),
                        '136' => i::__("Unicred Cooperativa - 136"),
                        '99'  => i::__("Uniprime Central Ccc Ltda - 99"),
                        '84'  => i::__("Uniprime Norte Do Paraná - 84"),
                        '298' => i::__("Vips Cc Ltda - 298"),
                        '310' => i::__("Vortx Dtvm Ltda - 310"),
                        '102' => i::__("Xp Investimentos S.A - 102"),
                    ],
                    'validations' => [
                        'required' => \MapasCulturais\i::__("O banco obrigatório")
                    ]
                ]);
                $self->registerRegistrationMetadata('payment_branch', [
                    'label' => i::__('Agência sem o dígito'),
                    'type' => 'string',
                    'validations' => [
                        'required' => \MapasCulturais\i::__("O agência obrigatório")
                    ]
                    
                ]);
                $self->registerRegistrationMetadata('payment_branch_dv', [
                    'label' => i::__('Dígito verificador da agência'),
                    'type' => 'string',
                    'field_type' => 'fieldMask',
                    'validations' => [
                        'required' => \MapasCulturais\i::__("O dígito verificador da agência obrigatório")
                    ]
                ]);
                $self->registerRegistrationMetadata('payment_account', [
                    'label' => i::__('Conta sem o dígito'),
                    'type' => 'string',
                    'validations' => [
                        'required' => \MapasCulturais\i::__("A conta obrigatória")
                    ]
                ]);
                $self->registerRegistrationMetadata('payment_account_dv', [
                    'label' => i::__('Dígito verificador da conta'),
                    'type' => 'string',
                    'field_type' => 'fieldMask',
                    'validations' => [
                        'required' => \MapasCulturais\i::__("O Dígito verificador da conta obrigatória")
                    ]
                ]);
            }
        });


        
        $app->registerFileGroup(
            'opportunity',
            new Definitions\FileGroup(
                'export-cnab-files',
                ['text/plain'],
                'O arquivo não e valido',
                private:true
            )
        );

        $app->registerFileGroup(
            'opportunity',
            new Definitions\FileGroup(
                'export-payments-filters-files',
                ['^text/csv$'],
                'O arquivo não e valido',
                unique:true,
            )
        );

        $app->registerFileGroup(
            'opportunity',
            new Definitions\FileGroup(
                'export-financial-validator-files',
                ['^text/csv$'],
                'O arquivo não e valido',
                unique:true,
            )
        );

        $app->registerFileGroup(
            'opportunity',
            new Definitions\FileGroup(
                'import-financial-validator-files',
                ['^text/csv$'],
                'O arquivo não e valido',
            )
        );

        $app->hook("template(registration.view.single-tab):end", function() use ($self) {
            $registration = $this->controller->requestedEntity;
            if($registration->opportunity->firstPhase->has_payment_phase && $registration->lastPhase->status == 10) {
                $this->part("registration/registration-payment-tab", ['entity' => $registration]);
            }
        });

        $app->hook("component(opportunity-phases-timeline).item:after", function() use ($self) {
            $registration_class = $this->controller->requestedEntity;
            
            if($registration_class->opportunity->has_payment_phase && $registration_class->status == 10) {
                $this->part("registration/registration-payment-timeline");
            }
        });

        $app->hook("entity(Registration).canUser(modify)", function($user, &$result) use ($self) {
            $opportunity = $this->opportunity;

            if($opportunity->firstPhase->has_payment_phase && $this->status == 10) {
                $result = true;
            }
        });
    }

    /**
     * Valida a conta bancária 
     * 
     * @param string $bank_number Número do banco ('001' para Banco do Brasil, '341' para Itaú, etc)
     * @param string $account_number 
     * @param string $branch 
     * @param string|null $account_verifying_digit 
     * @param string|null $branch_verifying_digit 
     * @return void 
     * @throws NotRegistredBankCode 
     */
    function validateAccount(string $bank_number, string $account_number, string $branch, string $account_verifying_digit = null, string $branch_verifying_digit = null) {
        $original = [
            'account_number' => $account_number,
            'branch' => $branch,
            'account_verifying_digit' => $account_verifying_digit,
            'branch_verifying_digit' => $branch_verifying_digit,
        ];

        $bank = BankCodeMapping::get_validator($bank_number);
        $valid_chars = $bank::valid_chars;


        $branch_length = $bank::agency_size;
        $branch = preg_replace("#[^{$valid_chars}]*#i", '', $branch);
        $branch_verifying_digit = $branch_verifying_digit ? 
            preg_replace("#[^{$valid_chars}]*#i", '', $branch_verifying_digit) : $branch_verifying_digit;

        $branch_changed = false;
        
        if ($bank->use_agency_digit() && (empty($branch_verifying_digit) || ! BankValidator::validate_agency($bank_number, $branch, $branch_verifying_digit))) {
            $branch_changed = true;
            if ($branch_verifying_digit === '0' && BankValidator::validate_agency($bank_number, $branch, 'X')) {
                $branch_verifying_digit = 'X';
            }else if (in_array($branch_verifying_digit, ['X', 'x']) && BankValidator::validate_agency($bank_number, $branch, '0')) {
                $branch_verifying_digit = '0';
            } else {
                $_branch = substr($branch, 0, -1);
                $_branch = str_pad($_branch, $branch_length, '0', STR_PAD_LEFT);
                $_branch_verifying_digit = substr($branch, -1);

                if (BankValidator::validate_agency($bank_number, $_branch, $_branch_verifying_digit)) {
                    $branch = $_branch;
                    $branch_verifying_digit = $_branch_verifying_digit;
                    
                    // provavelmente a agencia-dv foi informada no campo da agencia antes de existir campo de dv
                    $branch_changed = false;

                } else if($_branch_verifying_digit === '0' && BankValidator::validate_agency($bank_number, $_branch, 'X')) {
                    $branch = $_branch;
                    $branch_verifying_digit = 'X';
                } else if (strlen($branch) == $branch_length) {
                    $branch_verifying_digit = $bank->calculate_agency($branch);
                }
            }
        } 

        $account_length = $bank::account_size;
        $account_number = preg_replace("#[^{$valid_chars}]*#i", '', $account_number);
        $account_verifying_digit = $account_verifying_digit ? 
            preg_replace("#[^{$valid_chars}]*#i", '', $account_verifying_digit) : $account_verifying_digit;

        $account_changed = false;
        
        if ($bank->use_account_digit() && (empty($account_verifying_digit) || !BankValidator::validate_account($bank_number, $branch, $account_number, $account_verifying_digit))) {
            $account_changed = true;

            if ($account_verifying_digit === '0' && BankValidator::validate_account($bank_number, $branch, $account_number, 'X')) {
                $account_verifying_digit = 'X';
            } else if (in_array($account_verifying_digit, ['X', 'x']) && BankValidator::validate_account($bank_number, $branch, $account_number, '0')) {
                $account_verifying_digit = '0';
            } else {
                $_account_number = substr($account_number, 0, -1);
                $_account_verifying_digit = substr($account_number, -1);
    
                if (BankValidator::validate_account($bank_number, $branch, $_account_number, $_account_verifying_digit)) {
                    $account_number = $_account_number;
                    $account_verifying_digit = $_account_verifying_digit;
                    
                    // provavelmente a conta-dv foi informada no campo da conta antes de existir campo de dv
                    $account_changed = false;

                } else if ($_account_verifying_digit === '0' && BankValidator::validate_account($bank_number, $branch, $_account_number, 'X')) {
                    $account_number = $_account_number;
                    $account_verifying_digit = 'X';
                }
            }
        }

        $branch = str_pad($branch, $branch_length, '0', STR_PAD_LEFT);
        $account_number = str_pad($account_number, $account_length, '0', STR_PAD_LEFT);

        $result = (object) [

            'validator' => $bank,
            'bank_number' => $bank_number,
            
            'original' => $original,

            'account_full' => false,
            'account_number' => false,
            'account_verifying_digit' => null,

            'branch_full' => false,
            'branch' => false,
            'branch_verifying_digit' => null,

            'account_changed' => $account_changed,
            'branch_changed' => $branch_changed,
        ];

        if (BankValidator::validate_agency($bank_number, $branch, $branch_verifying_digit)) {
            $result->branch_full = $bank->use_agency_digit() ? "{$branch}-{$branch_verifying_digit}" : $branch;
            $result->branch = $branch;
            $result->branch_verifying_digit = $branch_verifying_digit;
        }

        if (BankValidator::validate_account($bank_number, $branch, $account_number, $account_verifying_digit)) {
            $result->account_number = $account_number;
            $result->account_verifying_digit = $account_verifying_digit;
            $result->account_full = $bank->use_account_digit() ? "{$account_number}-{$account_verifying_digit}" : $account_number;
        }
        
        return $result;
    }

    /**
     * Devolve a instância do plugin
     *
     */
    public static function getInstance()
    {
        return self::$instance;
    }
    
    /**
     * Devolve a instância do CNAB
     *
     * @param  mixed $bank
     * @param  string $layout
     * @param  array $params
     * @return void
     */
    public function getCanbInstace($bank, $layout, array $params)
    {
        return new Remessa($bank, $layout, $params);
    }

     /**
     * Normaliza uma string
     *
     * @param string $valor
     * @return string
     */
    private function normalizeString($valor): string
    {
        $valor = $valor ? Normalizer::normalize($valor, Normalizer::FORM_D) : $valor;
        return preg_replace('/[^A-Za-z0-9 ]/i', '', $valor);
    }

    /**
     * @param string $string
     * @return boolean
     */
    function isJson($string) {
        $decoded = json_decode($string);
    

        if ($decoded === null && json_last_error() !== JSON_ERROR_NONE) {
            return false;
        }
    
        return true;
    }

    /**
     * Validações de erros dos dados recebidos do request
     *
     * @param array $data
     * @return array
     */
    public function errorsRequest(array $data): array
    {
        $errors = [];
        $create_type = $data['createType'];

        switch ($create_type) {
            case 'registration_id':
                $type =  i::__('inscrições');
                break;
            case 'registrationStatus':
                $type =  i::__('status');
                break;
            case 'category':
                $type =  i::__('categoria');
                break;
        }

        if(!in_array($create_type, array_keys($data)) || !$data[$create_type]) {
            $errors[] = i::__("O campo {$type} é obrigatório");
        }

        if(!in_array('payment_date', array_keys($data)) || !$data['payment_date']) {
            $errors[] = i::__('O campo Previsão de pagamento é um campo obrigatório');
        }

        if(!in_array('amount', array_keys($data)) || !$data['amount']) {
            $errors[] = i::__('O campo Valor é um campo obrigatório');
        }

        return $errors;
    }

    public function getCnabValidationErrors($opportunity, $request)
    {
        $errors = [];
        if (!$request['identifier']) {
            $errors["identifier"] = i::__("Informe o número de identificação do lote Ex.: 001");
        }

        if (!$request['lotType']) {
            $errors['lotType'] = i::__("Informe o tipo de lote. Corrente BB, Poupança BB ou Outros Bancos");
        }
        
        if (!$opportunity->canUser('@control')) {
            $errors[] = i::__("Você nao tem permissão para geração do CNAB240 nesta oportunidade");
        }
        
        $identifier = "lote-". str_pad($request['identifier'] , 4 , '0' , STR_PAD_LEFT);
        if (!in_array('opportunitysCnab', array_keys($this->config)) || !in_array($opportunity->id, array_keys($this->config['opportunitysCnab']))) {
            $errors[] = i::__("Os campos para coletar os dados para o CNAB240 não estão configurados nesta oportunidade. Fale com o administrador.");
        }
        
        $payment_lot_export = json_decode($opportunity->payment_lot_export ?: '[]', true);

        if($request['lotType'] && !$request['ts_lot']) {
            $lot_type = $this->config['file_type'][$request['lotType']];
            if(in_array($request['lotType'], $payment_lot_export[$identifier] ?? [])){
                $errors[] = i::__("{$identifier} para o arquivo {$lot_type} Já usado anteriormente.");
            }
        }

        if(!$this->config['cnab240_company_data']){
            $errors[] = i::__("A entidade pagadora nao foi configurada");
        }

        return $errors;
    }

    /**
     * Retorna o usuário autenticado
     *
     */
    public static function getUser()
    {
        $app = App::i();

        return $app->repo('User')->find($app->user->id);
    }

    public function prefixFieldId($value)
    {
        $fields_id = $this->config['fields'];
        return $fields_id[$value] ? "field_".$fields_id[$value] : null;
    }
}