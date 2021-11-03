<?php

namespace RegistrationPayments;

use CnabPHP\Remessa;
use MapasCulturais\i;
use MapasCulturais\App;
use BankValidator\classes\BankCodeMapping;
use BankValidator\Validator as BankValidator;
use BankValidator\classes\exceptions\NotRegistredBankCode;

require_once 'vendor/autoload.php';
class Plugin extends \MapasCulturais\Plugin{

    protected static $instance = null;

    function __construct(array $config = [])
    {
        $config += [
            'cnab240_enabled' => false,
            'opportunitysCnab' => [],
            'file_type' => [
                1 => 'Corrente BB', // Corrente BB
                2 => 'Poupança BB', // Poupança BB
                3 => 'Outros bancos' // Outros bancos
            ],
            'treatments' => [
                'social_type' => function($registration, $field, $settings, $metadata, $dependence){
                    $field_id = "field_".$field;
                    $id = isset($metadata[$field_id]) ? $metadata[$field_id] : $field;
                    return $settings['social_type'][$id];
                },
                'proponent_name' => function($registration, $field, $settings,$metadata, $dependence){
                    $field_id = "field_".$field;
                    return $metadata[$field_id] ?? null;
                },
                'proponent_document' => function($registration, $field, $settings,$metadata, $dependence){
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
                    return $metadata[$field_id] ?? null;
                },
                'account_dv' => function($registration, $field, $settings,$metadata, $dependence){
                    $field_id = "field_".$field;
                    return $metadata[$field_id] ?? null;
                }
            ],
        ];

        parent::__construct($config);

        self::$instance = $this;
    }

    function _init() {
        $app = App::i();

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

        // Exibe Botão para exportação CNAB240
        $app->hook('template(opportunity.<<single|edit>>.sidebar-right):begin', function () use ($plugin, $app) {

            $entity = $this->controller->requestedEntity;         
            if($entity->canUser('@control')){
                if($plugin->config['cnab240_enabled'] && in_array($entity->id, array_keys($plugin->config['opportunitysCnab']))){
                    $this->part('singles/export-button', ['entity' => $entity]);
                }
            }

        });

        // Adiciona tab de Pagamentos na single da Oportunidade
        $app->hook('template(opportunity.single.tabs):end', function () use ($app) {

            if (!$app->user->is('admin')) {
                return;
            }

            $entity = $this->controller->requestedEntity;
            $paymentsTabEnabled = $entity->paymentsTabEnabled ?? null;

            if ($paymentsTabEnabled) {
                $this->part('singles/opportunity-payments--tab', ['entity' => $entity]);
            }

        });

        // Adiciona o conteúdo da aba Pagamentos na single da Oportunidade
        $app->hook('template(opportunity.single.tabs-content):end', function () use ($app) {

            if (!$app->user->is('admin')) {
                return;
            }

            $entity = $this->controller->requestedEntity;
            $paymentsTabEnabled = $entity->paymentsTabEnabled ?? null;

            if ($paymentsTabEnabled) {
                $this->part('singles/opportunity-payments', ['entity' => $entity]);
            }

        });

        // Adiciona campo para selecionar se a oportunidade usará ou não a aba de pagamentos nas oportunidades
        $app->hook('template(opportunity.edit.tab-about):end', function () use ($app) {

            if (!$app->user->is('admin')) {
                return;
            }

            $entity = $this->controller->requestedEntity;
            $this->part('singles/opportunity-payments-config', ['entity' => $entity]);

        });

        $app->hook('doctrine.emum(object_type).values', function(&$values) {
            $values['Payment'] = Payment::class;
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
        ]);

        $this->registerAgentMetadata('payment_bank_account_type', [
            'label' => i::__('Tipo da conta bancária para pagamentos'),
            'type' => 'string',
            'private' => true,
        ]);

        $this->registerAgentMetadata('payment_bank_account_number', [
            'label' => i::__('Número da conta bancária para pagamentos'),
            'type' => 'string',
            'private' => true,
        ]);

        $this->registerAgentMetadata('payment_bank_branch', [
            'label' => i::__('Agência bancária para pagamentos'),
            'type' => 'string',
            'private' => true,
        ]);

        $this->registerAgentMetadata('payment_bank_number', [
            'label' => i::__('Número do banco para pagamentos'),
            'type' => 'string',
            'private' => true,
        ]);

        $this->registerMetadata('MapasCulturais\Entities\Opportunity',
            'paymentsTabEnabled',
            [
                'label' => 'Habilitar aba de pagamentos',
                'type' => 'select',
                'options' => (object)[
                    "0" => i::__('Desabilitar'),
                    "1" => i::__('Habilitar'),
                ],
                'default_value' => (string) "0",
            ]
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
    
}