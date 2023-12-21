<?php

namespace RegistrationPayments;

use Normalizer;
use  MapasCulturais\Definitions;
use CnabPHP\Remessa;
use MapasCulturais\i;
use MapasCulturais\App;
use RegistrationPayments\Payment;
use BankValidator\classes\BankCodeMapping;
use BankValidator\Validator as BankValidator;
use BankValidator\classes\exceptions\NotRegistredBankCode;

require_once 'vendor/autoload.php';
class Plugin extends \MapasCulturais\Plugin{

    protected static $instance = null;

    function __construct(array $config = [])
    {
        $self = $this;
        $config += [
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
                    $id = isset($metadata[$field_id]) ? $metadata[$field_id] : $field;
                    return $settings['social_type'][$id];
                },
                'proponent_name' => function($registration, $field, $settings,$metadata, $dependence) use ($self){
                    if($field =="category"){
                        $id = $registration->$field;  
                        $field_id = "field_".$this->config['opportunitysCnab'][$registration->opportunity->id]['proponent_name'][$settings['social_type'][$id]];
                        $_value = $metadata[$field_id] ?? null;
                    } else {
                        $field_id = "field_".$field;
                        $_value = $metadata[$field_id] ?? null;
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
                        return $metadata[$field_id] ?? null;
                    }

                    $field_id = "field_".$field;
                    return $metadata[$field_id] ?? null;
                },
                'address' => function($registration, $field, $settings,$metadata, $dependence){
                    $field_id = "field_".$field;
                    return $metadata[$field_id] ?? null;
                },
                'number' => function($registration, $field, $settings,$metadata, $dependence){
                    $field_id = "field_".$field;
                    return $metadata[$field_id] ?? null;
                },
                'complement' => function($registration, $field, $settings,$metadata, $dependence){
                    $field_id = "field_".$field;
                    return $metadata[$field_id] ?? null;
                },
                'zipcode' => function($registration, $field, $settings,$metadata, $dependence){
                    $field_id = "field_".$field;
                    return $metadata[$field_id] ?? null;
                },
                'city' => function($registration, $field, $settings,$metadata, $dependence){
                    $field_id = "field_".$field;
                    return $metadata[$field_id] ?? null;
                },
                'account_type' => function($registration, $field, $settings,$metadata, $dependence){
                    $field_id = "field_".$field;
                    return $metadata[$field_id] ?? null;
                },
                'bank' => function($registration, $field, $settings,$metadata, $dependence){
                    $field_id = "field_".$field;
                    return $metadata[$field_id] ?? null;
                },
                'branch' => function($registration, $field, $settings,$metadata, $dependence){
                    $field_id = "field_".$field;
                    return $metadata[$field_id] ?? null;
                },
                'branch_dv' => function($registration, $field, $settings,$metadata, $dependence){
                    $field_id = "field_".$field;
                    return $metadata[$field_id] ?? null;
                },
                'account' => function($registration, $field, $settings,$metadata, $dependence){

                    $field_id = "field_".$field;
                    $data = $metadata[$field_id] ?? null;

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

        $app->hook('mapas.printJsObject:before', function () {            
            $status = [
                ['value' => Payment::STATUS_PENDING, 'label' => i::__("Pendente")],
                ['value' => Payment::STATUS_PROCESSING, 'label' => i::__("Em processo")],
                ['value' => Payment::STATUS_FAILED, 'label' => i::__("Falha")],
                ['value' => Payment::STATUS_EXPORTED, 'label' => i::__("Exportado")],
                ['value' => Payment::STATUS_AVAILABLE, 'label' => i::__("Disponível")],
                ['value' => Payment::STATUS_PAID, 'label' => i::__("Pago")],
            ];
    
            $this->jsObject['config']['payment']['statusDic'] = $status;
            $this->jsObject['EntitiesDescription']['payment'] = Payment::getPropertiesMetadata();
        });

        $app->hook('doctrine.emum(object_type).values', function(&$values) {
            $values['Payment'] = Payment::class;
        });

        $app->hook('template(opportunity.edit.opportunity-data-collection-config):afeter',function(){
                $this->part('payments/payments-config');
        });

        $app->hook("template(opportunity.edit.tabs):end", function() use ($app) {
            $entity = $this->controller->requestedEntity;
            if($entity->paymentsTabEnabled == "1") {
                $this->part("payments/opportunity-payments-tab", ['entity' => $entity]);
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

        $this->registerMetadata('RegistrationPayments\\Payment','payment_identifier', [
            'label' => i::__('Identificação do pagamento'),
            'type' => 'string',
            'private' => true,
        ]);

        $this->registerMetadata('MapasCulturais\Entities\Opportunity','payment_lot_export', [
            'label' => i::__('Lotes exportados'),
            'type' => 'json',
            'private' => true,
            'dafault' => '[]',
        ]);

        $this->registerAgentMetadata('payment_bank_account_type', [
            'label' => i::__('Tipo da conta bancária para pagamentos'),
            'type' => 'string',
            'private' => true,
            'available_for_opportunities' => true
        ]);

        $this->registerAgentMetadata('payment_bank_account_number', [
            'label' => i::__('Número da conta bancária para pagamentos'),
            'type' => 'string',
            'private' => true,
            'available_for_opportunities' => true
        ]);

        $this->registerAgentMetadata('payment_bank_branch', [
            'label' => i::__('Agência bancária para pagamentos'),
            'type' => 'string',
            'private' => true,
            'available_for_opportunities' => true
        ]);

        $this->registerAgentMetadata('payment_bank_number', [
            'label' => i::__('Número do banco para pagamentos'),
            'type' => 'string',
            'private' => true,
            'available_for_opportunities' => true
        ]);

        $this->registerMetadata('MapasCulturais\Entities\Opportunity',
            'paymentsTabEnabled',
            [
                'label' => 'Habilitar aba de pagamentos',
                'type' => 'radio',
                'options' => (object)[
                    "0" => i::__('Desabilitar'),
                    "1" => i::__('Habilitar'),
                ],
                'default_value' => (string) "0",
            ]
        );

        $this->registerMetadata('MapasCulturais\Entities\Opportunity',
            'paymentCnabEnabled',
                [
                'label' => 'Habilita extração do arquivo CNAB240',
                'type' => 'radio',
                'options' => (object)[
                    "0" => i::__('Desabilitar'),
                    "1" => i::__('Habilitar'),
                ],
            ]
        );
        
        $app->registerFileGroup(
            'opportunity',
            new Definitions\FileGroup(
                'export-cnab-files',
                ['text/plain'],
                'O arquivo não e valido'
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
        $valor = Normalizer::normalize($valor, Normalizer::FORM_D);
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
        if(!in_array('registration_id', array_keys($data)) || !$data['registration_id']) {
            $errors[] = i::__('O campo Inscrições é um campo obrigatório');
        }

        if(!in_array('payment_date', array_keys($data)) || !$data['payment_date']) {
            $errors[] = i::__('O campo Previsão de pagamento é um campo obrigatório');
        }

        if(!in_array('amount', array_keys($data)) || !$data['amount']) {
            $errors[] = i::__('O campo Valor é um campo obrigatório');
        }

        return $errors;
    }
    
}