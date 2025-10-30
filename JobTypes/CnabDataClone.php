<?php

namespace RegistrationPayments\JobTypes;

use MapasCulturais\App;
use MapasCulturais\Entities\Job;
use RegistrationPayments\Plugin;
use MapasCulturais\Definitions\JobType;


class CnabDataClone extends JobType
{
    const SLUG = "cnabDataClone";

    protected function _generateId(array $data, string $start_string, string $interval_string, int $iterations)
    {
        return "cnabDataClone:{$data['opportunityId']}".uniqid();
    }

    protected function _execute(Job $job)
    {
        $app = App::i();
        $config = $app->config['plugins']['RegistrationPayments']['config'];
        $opportunitysCnab = $config['opportunitysCnab'];
        $app->disableAccessControl();
        
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

        // $opp_ids = implode(",",$keys);

        Plugin::getInstance()->registeredPaymentMetadata();


        $processValue = function($registration) use ($opportunitysCnab, $app, $banc_data_fields) {
            $current_opportunity = $registration->opportunity;
            
            $opportunity_last_phase = $current_opportunity->lastPhase ?? $current_opportunity;
            $opportunity_last_phase->registerRegistrationMetadata(true);
            
            $reg_first_phase = $registration->firstPhase;
            
            if (!$reg_first_phase) {
                echo "ERRO: Registration {$registration->id} sem firstPhase\n";
                return;
            }

            $config = $opportunitysCnab[$opportunity_last_phase->id] ?? null;
            
            if (!$config) {
                $app->log->debug("ERRO: Sem configuração para opportunity {$opportunity_last_phase->id}\n");
                return;
            }

            if($config['social_type'] == "category") {
                $category = $registration->category;
                $social_type = $config['settings']['social_type'][ $category];
            }else {
                $_field = 'field_'.$config['social_type'];
                if(!($reg_first_phase->$_field)) {
                    $app->log->debug("VAZIO =========================\n======================== \n\n(status: {$registration->status}) {$registration->number} {$registration->id} === $_field\n\n ==========\n");
                }
                $social_type = $config['settings']['social_type'][$reg_first_phase->$_field];
              
            }

            $reg_first_phase->payment_social_type = $social_type;
            $modified = false;
            foreach($banc_data_fields as $ref => $field) {
                if(is_array($config[$ref])) {
                    $_field = 'field_'.$config[$ref][$social_type];
                } else {
                    $_field = 'field_'.$config[$ref];
                }
                
                $value = $reg_first_phase->$_field;

                if($field == 'payment_account_type') {
                    $value = $value === "Conta corrente" ? 1 : 2;
                }

                $value = $value ?? '';
                
                if((!$reg_first_phase->$field && $value) || ($reg_first_phase->$field != $value)) {
                     $app->log->debug("$field ---------> $value\n");
                    $modified = true;
                    $reg_first_phase->$field = $value;
                }
            }
            
            if($modified) {
                $reg_first_phase->payment_sent_timestamp = $registration->sentTimestamp ? $registration->sentTimestamp->format('Y-m-d H:i:s') : (new DateTime('now'))->format('Y-m-d H:i:s');
                $app->log->debug("Opportunidade {$registration->opportunity->id} -- Dados bancários da inscrição {$registration->id} salvos nos novos metadados");
                $app->log->debug("\n\n");

                $reg_first_phase->save(true);
            }
            
        };

        foreach($keys as $opp_id) { 
            if($registrations = $app->repo('MapasCulturais\Entities\Registration')->findBy(['opportunity' => $opp_id, 'status' => 10])) {
                foreach($registrations as $registration) {
                    $processValue($registration);
                }
            }
        }

        $app->enableAccessControl();
    }
}
