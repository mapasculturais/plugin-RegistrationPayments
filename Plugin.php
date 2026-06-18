<?php

namespace RegistrationPayments;

use DateTime;
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
use RegistrationPayments\JobTypes\CnabDataClone;
use BankValidator\classes\exceptions\NotRegistredBankCode;

require_once 'vendor/quilhasoft/opencnabphp/autoloader.php';
class Plugin extends \MapasCulturais\Plugin{

    protected static $instance = null;
    public static $saved_ids;
    public static $save_first_phase = false;

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
                    return $registration->firstPhase->payment_social_type;
                },
                'proponent_name' => function($registration, $field, $settings,$metadata, $dependence) use ($self){
                    return $registration->firstPhase->payment_proponent_name;                   
                },
                'proponent_document' => function($registration, $field, $settings,$metadata, $dependence){
                    return $registration->firstPhase->payment_proponent_document;
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
                    return $registration->firstPhase->payment_account_type;
                },
                'bank' => function($registration, $field, $settings,$metadata, $dependence){
                    return $registration->firstPhase->payment_bank;
                },
                'branch' => function($registration, $field, $settings,$metadata, $dependence){
                    return $registration->firstPhase->payment_branch;
                },
                'branch_dv' => function($registration, $field, $settings,$metadata, $dependence){
                    return $registration->firstPhase->payment_branch_dv;
                },
                'account' => function($registration, $field, $settings,$metadata, $dependence){
                    $first_phase = $registration->firstPhase;

                    $data = $first_phase->payment_account;
                    if($first_phase->payment_account_type == 2 && $first_phase->payment_bank == 1 && substr($data, 0, 2) != "51"){
                       
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
                    $first_phase = $registration->firstPhase;

                    $data = $first_phase->payment_account_dv ?: 0;
                    
                    if(!is_int($data) && (strlen($data) > 2)){
                        if(preg_match("/x/", mb_strtolower($data))){
                            $data = "X";
                        }else{
                            $data = 0;
                        }
                    }

                    if($first_phase->payment_account_type == 2 && $first_phase->payment_bank && $first_phase->payment_bank == 1){
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
        
        $app->registerJobType(new CnabDataClone(CnabDataClone::SLUG));

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

        $app->hook('mapas.printJsObject:before', function () use ($app, $plugin) {            
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

            // Adiciona opção única "Dados bancários" à lista de campos visíveis para avaliadores
            $plugin->addPaymentFieldsToVisibleEvaluators($this);
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

        $app->hook('repo(RegistrationPayments.Payment).getIdsByKeywordDQL.where', function (&$where, $keyword, $alias) {
            $or = trim($where) ? "OR" : "";
            $where .= " {$or} reg.number LIKE lower(:{$alias}) ";
            $where .= " OR unaccent(lower(owner.name)) LIKE unaccent(lower(:{$alias})) ";
        });

        //Insere botão que ativa o pagamento dentro do mc-stepper-vertical
        $app->hook('template(opportunity.edit.mc-stepper-vertical:after)', function() use ($app) {
           /** @var Theme $this */ 
           $this->part('payments/opportunity-payments-enable');
        });

        $app->hook('component(opportunity-phase-config-data-collection):bottom', function() use ($app) {
            /** @var Theme $this */ 
            $this->part('payments/opportunity-payments-config');
        });

        // Insere os metados payment_processed_files e active_payment_phase no retorno da API 
        $app->hook("module(OpportunityPhases).dataCollectionPhaseData", function(&$mout_simplify) {
            $mout_simplify.=",payment_processed_files,active_payment_phase,payment_step_form";
        });

        // Registra os metadados de pagamento da oportunidade e inscrições em todas as requisições
        $app->hook('<<GET|POST|PUT|PATCH|DELETE>>(<<registration|opportunity>>.<<*>>):before', function() use ($plugin) {
            $plugin->registeredPaymentMetadata();
        });

        // Cria setters na inscrição para os metadados dos dados de pagamento
        $app->hook('entity(registration).set(<<payment_*>>)', function(&$value, $property_name) use($app, $plugin){
            if(!$this->opportunity->isFirstPhase) {
                $plugin->registeredPaymentMetadata();
                $this->save_first_phase = true;
                self::$save_first_phase =  true;
                $first_phase = $this->firstPhase;
                if ($first_phase) {
                    $first_phase->$property_name = $value;
                }
            }
        });
        
        // salva o dados de pagamentos na primeira fase
        $app->hook('entity(registration).save:finish', function() use($app, $plugin){
            if($this->save_first_phase && !isset(self::$saved_ids[$this->id])) {
                self::$saved_ids[$this->id] = true; 
                $first_phase = $this->firstPhase;
                if ($first_phase && $plugin->hasPaymentDataToSync($this, $first_phase)) {
                    $plugin->syncPaymentDataToFirstPhase($this, $first_phase);
                    $plugin->saveFirstPhase($first_phase);
                }
            }
        });

        // Sincroniza dados bancários ao visualizar inscrição
        $app->hook("template(registration.view.registration-form-view):before", function($registration) use ($plugin, $app) {
            if (!$registration->opportunity || $registration->opportunity->isFirstPhase) {
                return;
            }
            
            $first_phase = $registration->firstPhase;
            if (!$first_phase) {
                return;
            }
            
            // Só sincroniza se a oportunidade tem fase de pagamento ativa
            if (!$registration->opportunity->active_payment_phase && !$first_phase->opportunity->active_payment_phase) {
                return;
            }
            
            $plugin->registeredPaymentMetadata();

            if ($plugin->hasPaymentDataToSync($registration, $first_phase)) {
                $plugin->syncPaymentDataToFirstPhase($registration, $first_phase);
                $plugin->saveFirstPhase($first_phase);
            }
        });

        // Sincroniza dados bancários ao editar inscrição
        $app->hook("component(registration-form):before", function() use ($app, $plugin) {
            $registration = $this->controller->requestedEntity;
            
            if (!$registration || !$registration->opportunity || $registration->opportunity->isFirstPhase) {
                return;
            }
            
            $first_phase = $registration->firstPhase;
            if (!$first_phase) {
                return;
            }
            
            // Só sincroniza se a oportunidade tem fase de pagamento ativa
            if (!$registration->opportunity->active_payment_phase && !$first_phase->opportunity->active_payment_phase) {
                return;
            }
            
            $plugin->registeredPaymentMetadata();
            
            if ($plugin->hasPaymentDataToSync($registration, $first_phase)) {
                $plugin->syncPaymentDataToFirstPhase($registration, $first_phase);
                $plugin->saveFirstPhase($first_phase);
            }
        });

        // Carrega o formulario dos dados de pagamento no formulario corrente da inscrição
        $app->hook("component(registration-form):end", function() 
        {
            /** @var Theme $this */
            $registration = $this->controller->requestedEntity;
            
            if($registration->opportunity->active_payment_phase) {
                $this->part("registration/registration-payment-form", ['entity' => $registration]);
            }
        });

        // Insere os dados de pagamento na serialização sem sobrescrever a fase atual com nulos da primeira fase
        $app->hook('entity(registration).jsonSerialize', function(&$result) use($plugin){
            /** @var Registration $registration */
            $registration = $this;

            foreach($plugin->getPaymentDataFields() as $field) {
                $result[$field] = $plugin->getPaymentDataValueForSerialization($registration, $field);
            }
        });

        // Faz o formulário ser exibido no modo de visualização da inscrição
        $app->hook("template(registration.view.registration-form-view):after", function($registration) use ($plugin) {
            /** @var Theme $this */
            if($registration->opportunity->active_payment_phase) {
                $this->part("registration/registration-payment-form-view", ['entity' => $registration]);
            }
        });

        // Faz o formulário ser exibido na tela de avaliação para o avaliador
        $app->hook("template(registration.evaluation.registration-evaluation-view):after", function($registration) use ($plugin) {
            /** @var Theme $this */
            $plugin->registeredPaymentMetadata();

            if ($registration->opportunity->active_payment_phase && $plugin->isPaymentFieldsVisibleForEvaluators($registration->opportunity)) {
                $this->part("registration/registration-payment-form-view", ['entity' => $registration]);
            }
        });

        // Ajusta permissão de modificação do pagamento mesmo depois da inscrição enviada
        $app->hook("entity(Registration).canUser(modify)", function ($user, &$result) use ($plugin) {
            /** @var Opportunity $opportunity */
            $opportunity = $this->opportunity;
            
            $plugin->registeredPaymentMetadata();  
            
            if ($plugin::$save_first_phase OR ($opportunity->active_payment_phase && $opportunity->isRegistrationOpen() && $this->status == 0)) {
                $result = true;
            }
        });

        // Evita que autosaves com valores vazios apaguem dados bancários já preenchidos
        $app->hook("entity(Registration).save:before", function() use($plugin) {
            /** @var Registration $this */
            $plugin->preservePaymentDataFromBlankAutosave($this);
        });

         // Remove os erros de validação dos campos de pagamento para inscrições nao selecionadas na fase final
         $app->hook('entity(Registration).validationErrors', function(&$errors) {
            /** @var Registration $this */
            if(!$this->opportunity->active_payment_phase || $this->isNew()) {
                include __DIR__."/registereds/payment_bank_data.php";
                $fields_meta = array_keys($payment_bank_data);
                foreach($fields_meta as $value) {
                    unset($errors[$value]);
                }
            }
        });

        // Valida os campos obrigatórios de pagamento também no envio da inscrição
        $app->hook('entity(Registration).sendValidationErrors', function(&$errors) {
            /** @var Registration $this */
            if(!$this->opportunity->active_payment_phase) {
                return;
            }

            include __DIR__."/registereds/payment_bank_data.php";

            foreach($payment_bank_data as $field => $definition) {
                $validations = $definition['validations'] ?? [];

                if(!isset($validations['required'])) {
                    continue;
                }

                $value = $this->$field ?? null;
                $is_empty = is_array($value) ? count($value) === 0 : trim((string) $value) === '';

                if($is_empty) {
                    $errors[$field] = [$validations['required']];
                }
            }
        });

        // Remove os erros dos campos de dados da entidade mantenedora na criação da oportunidade
        $app->hook('entity(Opportunity).validationErrors', function(&$errors) {
            if(!$this->active_payment_phase) {
                include __DIR__."/registereds/payment_company_data.php";
                $fields_meta = array_keys($payment_company_data);
                foreach($fields_meta as $value) {
                    unset($errors[$value]);
                }
            }
        });

        // Adiciona campos na exibição de erros do formulario de inscrição
        $app->hook('component(registration-actions).additionalValidateFields', function (&$additionalValidateFields, &$additionalValidateFieldsSteps) {
            $entity = $this->controller->requestedEntity;
            $step_id = $entity->opportunity->payment_step_form;

            include __DIR__ . "/registereds/payment_bank_data.php";
            $fields_meta = array_keys($payment_bank_data);
            foreach ($fields_meta as $value) {
                $additionalValidateFieldsSteps[$value] = $step_id;
                array_push($additionalValidateFields, $value);
            }
        });

        // Inclui os campos de dados bancários na configuração de suporte
        $app->hook('component(opportunity-support-config).fields', function (&$result, $entity) use($plugin) {
            $plugin->registeredPaymentMetadata();

            if (!$entity->active_payment_phase) {
                return;
            }

            include __DIR__ . "/registereds/payment_bank_data.php";
            $displayOrder = 9990;
            
            foreach ($payment_bank_data as $ref => $config) {
                $result[] = [
                    'id' => $ref,
                    'title' => $config['label'] ?? $ref,
                    'ref' => $ref,
                    'type' => $config['type'] ?? 'string',
                    'categories' => [],
                    'proponentTypes' => [],
                    'registrationRanges' => [],
                    'config' => $config['config'] ?? [],
                    'order' => $displayOrder,
                    'conditional' => false,
                    'conditionalField' => null,
                    'required' => !empty($config['validations']['required']),
                    'displayOrder' => $displayOrder,
                    'step' => null,
                ];
                $displayOrder++;
            }
        });

        // Inclui os campos de dados bancários na lista de campos editáveis
        $app->hook('component(registration-editable-fields).fields', function (&$result, $entity) use($plugin) {
            $plugin->registeredPaymentMetadata();

            if (!$entity->active_payment_phase) {
                return;
            }

            include __DIR__ . "/registereds/payment_bank_data.php";
            $displayOrder = 9990;
            foreach ($payment_bank_data as $ref => $config) {
                $result[] = [
                    'id' => $ref,
                    'title' => $config['label'] ?? $ref,
                    'ref' => $ref,
                    'displayOrder' => $displayOrder,
                ];
                $displayOrder++;
            }
            
            usort($result, fn($field1, $field2) => $field1['displayOrder'] <=> $field2['displayOrder']);
        });

        // Sincroniza os dados de pagamento entre as inscrições das fases
        $app->hook('entity(Registration).insert:after', function () use ($plugin) {
            /** @var Opportunity $this */
            if ($this->firstPhase) {
                $plugin->registeredPaymentMetadata(); 
                include __DIR__ . "/registereds/payment_bank_data.php";
                $fields_meta = array_keys($payment_bank_data);
                foreach ($fields_meta as $value) {
                    $this->$value =  $this->firstPhase->$value;
                }
                $this->save(true);
            }
        });

        // Faz o desparo de email quando selecionado na ultima fase
        $app->hook("entity(Registration).status(approved)", function() use ($plugin) {
            $plugin->registeredPaymentMetadata(); 

            $opportunity = $this->opportunity;
            if($opportunity->firstPhase->has_payment_phase 
                && $opportunity->isLastPhase
                && (!$this->firstPhase->payment_sent_timestamp)
            ) {
                // $self->sendEmail($this);
            }
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

        $this->registerOpportunityMetadata('active_payment_phase', [
            'label' => 'Configurar pagamento',
            'type' => 'boolean',
            'default' => false,
            'unserialize' => function($value){
                return $value == 1 ? true : false;
            }
        ]);

        $this->registerOpportunityMetadata('payment_step_form', [
            'label' => 'Etapa que o formulário será exibida',
            'type' => 'int',
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
     
        $app->hook("entity(Opportunity).registrationMetadata", function() use ($self) {
            if($this->active_payment_phase) {
                $self->registeredPaymentMetadata(); 
            }
        });
        
        $app->registerFileGroup(
            'opportunity',
            new Definitions\FileGroup(
                'export-cnab-files',
                ['^(text/plain|text/csv|application/csv|application/vnd.ms-excel)$'],
                'O arquivo não e valido',
                private:true
            )
        );

        $app->registerFileGroup(
            'opportunity',
            new Definitions\FileGroup(
                'export-payments-filters-files',
                ['^(text/plain|text/csv|application/csv|application/vnd.ms-excel)$'],
                'O arquivo não e valido',
                unique:true,
            )
        );

        $app->registerFileGroup(
            'opportunity',
            new Definitions\FileGroup(
                'export-financial-validator-files',
                ['^(text/plain|text/csv|application/csv|application/vnd.ms-excel)$'],
                'O arquivo não e valido',
                unique:true,
            )
        );

        $app->registerFileGroup(
            'opportunity',
            new Definitions\FileGroup(
                'import-financial-validator-files',
                ['^(text/plain|text/csv|application/csv|application/vnd.ms-excel)$'],
                'O arquivo não e valido',
            )
        );
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
                
        $payment_lot_export = json_decode($opportunity->payment_lot_export ?: '[]', true);

        if($request['lotType'] && !$request['ts_lot']) {
            $lot_type = $this->config['file_type'][$request['lotType']];
            $identifier = 'lote-' . str_pad($request['identifier'], 4, '0', STR_PAD_LEFT);
            if(in_array($request['lotType'], $payment_lot_export[$identifier] ?? [])){
                $errors[] = i::__("{$identifier} para o arquivo {$lot_type} Já usado anteriormente.");
            }
        }

        if(!$this->config['cnab240_company_data']){
            $errors[] = i::__("A entidade pagadora nao foi configurada");
        }

        $release_type = null;
        if ($request['lotType'] !== null && $request['lotType'] !== '') {
            if (isset($this->config['opportunitysCnab'][$opportunity->id]['settings']['release_type'][$request['lotType']])) {
                $release_type = $this->config['opportunitysCnab'][$opportunity->id]['settings']['release_type'][$request['lotType']];
            } elseif (isset($this->config['opportunitysCnab']['release_type'][$request['lotType']])) {
                $release_type = $this->config['opportunitysCnab']['release_type'][$request['lotType']];
            }
            if ($release_type === null) {
                $errors['lotType'] = i::__("Tipo de lote não configurado para esta oportunidade. Verifique a configuração do plugin (opportunitysCnab) na config do mapa.");
            }
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

    /**
     * Adiciona os campos de pagamento à lista de campos visíveis para avaliadores
     * 
     * @param Theme $view
     */
    function addPaymentFieldsToVisibleEvaluators($view)
    {
        $app = App::i();
        $entity = $view->controller->requestedEntity ?? null;
        
        // Obtém a oportunidade dependendo do contexto
        $opportunity = null;
        if ($entity instanceof \MapasCulturais\Entities\Opportunity) {
            $opportunity = $entity;
        } elseif ($entity instanceof \MapasCulturais\Entities\Registration) {
            $opportunity = $entity->opportunity;
        }

        if (!$opportunity || !$opportunity->active_payment_phase) {
            return;
        }

        $phases_fields = $view->jsObject['config']['fieldsVisibleEvaluators'] ?? [];
        
        // Adiciona apenas uma opção única "Dados bancários" para controlar todo o bloco
        $payment_fields = [
            [
                'fieldName' => 'paymentData',
                'title' => i::__('Dados bancários'),
            ]
        ];

        // Adiciona os campos de pagamento em todas as fases da oportunidade
        if (isset($phases_fields[$opportunity->id])) {
            $phases_fields[$opportunity->id] = array_merge($phases_fields[$opportunity->id], $payment_fields);
        }

        // Também adiciona nas fases anteriores
        $phase = $opportunity;
        while ($phase = $phase->previousPhase) {
            if (isset($phases_fields[$phase->id])) {
                $phases_fields[$phase->id] = array_merge($phases_fields[$phase->id], $payment_fields);
            }
        }

        $view->jsObject['config']['fieldsVisibleEvaluators'] = $phases_fields;
    }

    /**
     * Verifica se algum campo de pagamento está habilitado para os avaliadores
     * 
     * @param Opportunity $opportunity
     * @return bool
     */
    function isPaymentFieldsVisibleForEvaluators($opportunity)
    {
        $avaliableEvaluationFields = $opportunity->avaliableEvaluationFields ?? [];
        
        if (empty($avaliableEvaluationFields)) {
            return true; // Se não há configuração, mostra por padrão
        }

        // Verifica se a opção única "Dados bancários" está habilitada
        return isset($avaliableEvaluationFields['paymentData']) && $avaliableEvaluationFields['paymentData'] === "true";
    }

    /**
     * Retorna a lista de campos de dados bancários
     * 
     * @return array
     */
    function getPaymentDataFields()
    {
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
        ];
    }

    function isPaymentDataValueEmpty($value)
    {
        if(is_array($value)) {
            return count($value) === 0;
        }

        return trim((string) $value) === '';
    }

    /**
     * Retorna o valor de pagamento usando a fase atual como fonte principal e a primeira fase como fallback.
     */
    function getPaymentDataValueForSerialization(Registration $registration, string $field)
    {
        $current_value = $registration->$field ?? null;

        if($registration->isFirstPhase || !$this->isPaymentDataValueEmpty($current_value)) {
            return $current_value;
        }

        $first_phase = $registration->firstPhase;
        if($first_phase) {
            $first_phase_value = $first_phase->$field ?? null;

            if(!$this->isPaymentDataValueEmpty($first_phase_value)) {
                return $first_phase_value;
            }
        }

        return $current_value;
    }

    /**
     * Protege dados bancários já salvos contra autosaves que enviam valores vazios após a tela receber nulos.
     */
    function preservePaymentDataFromBlankAutosave(Registration $registration)
    {
        if($registration->isNew() || $registration->isFirstPhase || !$registration->opportunity->active_payment_phase) {
            return;
        }

        $first_phase = $registration->firstPhase;

        foreach($this->getPaymentDataFields() as $field) {
            $current_value = $registration->$field ?? null;

            if(!$this->isPaymentDataValueEmpty($current_value)) {
                continue;
            }

            $stored_value = $this->getStoredRegistrationPaymentValue($registration, $field);
            if(!$this->isPaymentDataValueEmpty($stored_value)) {
                $registration->$field = $stored_value;
                continue;
            }

            $first_phase_value = $first_phase ? ($first_phase->$field ?? null) : null;
            if(!$this->isPaymentDataValueEmpty($first_phase_value)) {
                $registration->$field = $first_phase_value;
            }
        }
    }

    function getStoredRegistrationPaymentValue(Registration $registration, string $field)
    {
        $app = App::i();
        $conn = $app->em->getConnection();
        $stmt = $conn->executeQuery(
            'SELECT value FROM registration_meta WHERE object_id = :object_id AND key = :field LIMIT 1',
            [
                'object_id' => $registration->id,
                'field' => $field,
            ]
        );

        return $stmt->fetchOne();
    }

    /**
     * Verifica se há dados de pagamento para sincronizar da fase atual para a primeira fase
     * 
     * @param Registration $registration
     * @param Registration $first_phase
     * @return bool
     */
    function hasPaymentDataToSync($registration, $first_phase)
    {
        $fields = $this->getPaymentDataFields();
        
        foreach ($fields as $field) {
            $current_value = $registration->$field ?? '';
            $first_phase_value = $first_phase->$field ?? '';
            
            // Se o campo atual tem valor e é diferente do valor na primeira fase
            if ($current_value !== '' && $current_value != $first_phase_value) {
                return true;
            }
        }
        
        return false;
    }

    /**
     * Salva a inscrição da primeira fase durante sincronização de dados bancários
     * 
     * @param Registration $first_phase
     * @return void
     */
    function saveFirstPhase($first_phase)
    {
        $app = App::i();

        self::$save_first_phase = true;
        $app->disableAccessControl();

        try {
            $first_phase->save(true);
        } finally {
            $app->enableAccessControl();
            self::$save_first_phase = false;
        }
    }

    /**
     * Sincroniza os dados de pagamento da fase atual para a primeira fase
     * 
     * @param Registration $registration
     * @param Registration $first_phase
     * @return void
     */
    function syncPaymentDataToFirstPhase($registration, $first_phase)
    {
        $fields = $this->getPaymentDataFields();
        
        foreach ($fields as $field) {
            $current_value = $registration->$field ?? '';
            $first_phase_value = $first_phase->$field ?? '';
            
            // Só sincroniza se o valor atual é diferente e não está vazio
            if ($current_value !== '' && $current_value != $first_phase_value) {
                $first_phase->$field = $current_value;
            }
        }
    }

    function registeredPaymentMetadata()
    {
        $app = App::i();

        include __DIR__."/registereds/payment_company_data.php";
        foreach($payment_company_data as $key => $data) {
            $def = new \MapasCulturais\Definitions\Metadata($key, $data);
            $app->registerMetadata($def, 'MapasCulturais\Entities\Opportunity');
        }

        include __DIR__."/registereds/payment_bank_data.php";
        foreach($payment_bank_data as $key => $data) {
            $this->registerRegistrationMetadata($key, $data);
        }
    }

    public function sendEmail(Registration $registration){
        $app = App::i();
        
        $file_name = $app->view->resolveFilename("templates", "registration-selected.html");
        $template = file_get_contents($file_name);
        $params = [
            "siteName" => $app->siteName,
            "user" => $registration->owner->name,
            "idOpportunity" => $registration->firstPhase->id,
            "registrationUrl" => $registration->firstPhase->singleUrl.'#payment',
            "baseUrl" => $app->getBaseUrl(),
        ];

        $mustache = new \Mustache_Engine();
        $content = $mustache->render($template, $params);

        $email_params = [
            "from" => $app->config["mailer.from"],
            "to" => ($registration->owner->emailPrivado ??
                        $registration->owner->emailPublico ??
                        $registration->ownerUser->email),
            "subject" => i::__("Prencher os dados do plugin de pagamentos"),
            "body" => $content
        ];
        if (!isset($email_params["to"])) {
            return;
        }
        $app->createAndSendMailMessage($email_params);
    }
}