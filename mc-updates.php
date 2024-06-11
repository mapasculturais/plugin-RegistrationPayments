<?php

use MapasCulturais\App;
use MapasCulturais\Entities\Opportunity;
use MapasCulturais\Entities\Registration;
use MapasCulturais\i;
use MapasCulturais\Utils;

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

        $opp_ids = implode(",",array_keys($opportunitysCnab));
        DB_UPDATE::enqueue('Registration', "status = 10 AND opportunity_id in ({$opp_ids})", function (Registration $registration) use ($opportunitysCnab, $app, $banc_data_fields) {
            
            $processValue = function($registration) use ($opportunitysCnab, $app, $banc_data_fields) {
                $opportunity = $registration->opportunity->firstPhase;
                $opportunity->lastPhase->registerRegistrationMetadata(true);
    
                $config = $opportunitysCnab[$opportunity->lastPhase->id];
    
                if($config['social_type'] == "category") {
                    $category = $registration->category;
                    $social_type = $config['settings']['social_type'][ $category];
                }else {
                    $_field = 'field_'.$config['social_type'];
                    $social_type = $config['settings']['social_type'][$registration->$_field];
                  
                }
    
                $registration->firstPhase->payment_social_type = $social_type;
                
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
    
                    $registration->firstPhase->$field = $value;
                    $registration->payment_sent_timestamp = $registration->sentTimestamp ? $registration->sentTimestamp->format('Y-m-d H:i:s') : (new DateTime('now'))->format('Y-m-d H:i:s');
                }
    
                $app->log->debug("Opportunidade {$registration->opportunity->id} -- Dados bancários da inscrição {$registration->id} salvo nos novos metadados");
                $registration->firstPhase->save(true);
            };

            $processValue($registration);
        });
    }
];
