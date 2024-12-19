<?php

use MapasCulturais\App;
use MapasCulturais\Entities\Opportunity;
use MapasCulturais\Entities\Registration;
use MapasCulturais\i;
use MapasCulturais\Utils;
use RegistrationPayments\Plugin;

return [
    'Cadastra dados bancários das inscrições elegível ao CNAB240 dos novos metadados' => function() {
        $app = App::i();
        $config = $app->config['plugins']['RegistrationPayments']['config'];
        $opportunitysCnab = $config['opportunitysCnab'];

        $banc_data_fields = [
            'proponent_name' => 'payment_proponent_name',
            'proponent_document' => 'payment_proponent_document',
            'account_type' => 'payment_account_type',
            'bank' => 'payment_bank',
            'branch' => 'payment_branch',
            'branch_dv' => 'payment_branch_dv',
            'account' => 'payment_account',
            'account_dv' => 'payment_account_dv',
        ];

        $keys = array_keys($opportunitysCnab);
        
        // filter numeric values
        $keys = array_filter($keys, function($value) {
            return is_numeric($value);
        });

        $opp_ids = implode(",",$keys);

        Plugin::getInstance()->registeredPaymentMetadata();

        DB_UPDATE::enqueue('Registration', "status = 10 AND opportunity_id in ({$opp_ids})", function (Registration $registration) use ($opportunitysCnab, $app, $banc_data_fields) {
            
            $processValue = function($registration) use ($opportunitysCnab, $app, $banc_data_fields) {
                $opportunity = $registration->opportunity->firstPhase;
                $opportunity->lastPhase->registerRegistrationMetadata(true);
                $reg_first_phase = $registration->firstPhase;
    
                $config = $opportunitysCnab[$opportunity->lastPhase->id];
    
                if($config['social_type'] == "category") {
                    $category = $registration->category;
                    $social_type = $config['settings']['social_type'][ $category];
                }else {
                    $_field = 'field_'.$config['social_type'];
                    if(!($registration->$_field)) {
                        echo "VAZIO =========================\n======================== \n\n(status: {$registration->status}) {$registration->number} {$registration->id} === $_field\n\n ==========\n";
                    }
                    $social_type = $config['settings']['social_type'][$registration->$_field];
                  
                }
    
                $reg_first_phase->payment_social_type = $social_type;
                $modified = false;
                foreach($banc_data_fields as $ref => $field) {
                    $field = $banc_data_fields[$ref];
    
                    if(is_array($config[$ref])) {
                        $_field = 'field_'.$config[$ref][$social_type];
                        $value = $registration->$_field;
                    }else {
                        $_field = 'field_'.$config[$ref];
                        $value = $registration->$_field;
                    }
    
                    if($field == 'payment_account_type') {
                        $value = $value === "Conta corrente" ? 1 : 2;
                    }
    
                    if(!$reg_first_phase->$field && $value || $reg_first_phase->$field != $value) {
                        echo "$field ---------> $value\n";
                        $modified = true;
                        $reg_first_phase->$field = $value;
                    }
                }
                
                if($modified) {
                    $reg_first_phase->payment_sent_timestamp = $registration->sentTimestamp ? $registration->sentTimestamp->format('Y-m-d H:i:s') : (new DateTime('now'))->format('Y-m-d H:i:s');
                    $app->log->debug("Opportunidade {$registration->opportunity->id} -- Dados bancários da inscrição {$registration->id} salvos nos novos metadados");
                    echo "\n\n";

                    $reg_first_phase->save(true);
                }
                
            };

            $processValue($registration);
        });
    }
];
