<?php

namespace RegistrationPayments;

use BankValidator\classes\exceptions\NotRegistredBankCode;
use MapasCulturais\App;
use MapasCulturais\i;
use BankValidator\Validator as BankValidator;
use BankValidator\classes\BankCodeMapping;


class Plugin extends \MapasCulturais\Plugin{
    function _init() {
        $app = App::i();

        $driver = $app->em->getConfiguration()->getMetadataDriverImpl();
        $driver->addPaths([__DIR__]);
        
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

    }

    function register () {
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
}