<?php
$app = MapasCulturais\App::i();
$em = $app->em;
$conn = $em->getConnection();

return [
    'Create revision Payment' => function () use ($app, $em, $conn) {

        $dql = "SELECT p.id FROM RegistrationPayments\\Payment p";
        $query = $app->em->createQuery($dql);
        $paymentsIds = $query->getResult();

        foreach ($paymentsIds as $id) {
            $id = $id['id'];
            $payment = $app->repo('\\RegistrationPayments\\Payment')->find($id);
            $payment->_newCreatedRevision();
            $app->log->info('Revision criada - ' . $payment->id);
            $app->em->clear();
        }
    },
    'passa pagamentos para a ultima fase' => function () use ($app, $em, $conn) {
        if ($payments = $conn->fetchAll('SELECT * FROM payment')) {
            $app->disableAccessControl();
            $total = count($payments);
            $sync = [];
            foreach ($payments as $key => $payment) {
                $count = $key + 1;
                $_payment = $app->repo("\\RegistrationPayments\\Payment")->find($payment['id']);
                $registration = $app->repo('Registration')->find($payment['registration_id']);

                if (!$registration->opportunity->isLastPhase) {
                    $lastPhase = $registration->opportunity->lastPhase;
                    if ($registration = $app->repo("Registration")->findOneBy(['opportunity' => $lastPhase, 'number' => $registration->number])) {
                        $_payment->registration = $registration;
                        $_payment->opportunity = $lastPhase;


                        $app->log->debug("{$count} de {$total} - Pagamento {$_payment->id} passado para última fase {$lastPhase->id}");

                        $_payment->save(true);
                        $app->em->clear();
                    } else {
                        $sync[$lastPhase->id] = $lastPhase;
                        $app->log->debug("{$count} de {$total} - Pagamento {$_payment->id} SEM INSCRIÇÂO NA ULTIMA FASE");
                    }
                }
            }

            if ($sync) {
                foreach ($sync as $opp) {
                    $opp->syncRegistrations();
                    $app->log->debug("Sincroniza fase");
                }
            }

            $app->enableAccessControl();
        }
    },
    'Ajusta campo Metadada dos pagamentos' => function () use ($app, $em, $conn) {

        if ($payments = $conn->fetchAll('SELECT * FROM payment')) {
            $app->disableAccessControl();
            foreach ($payments as $key => $payment) {
                $_payment = $app->repo("\\RegistrationPayments\\Payment")->find($payment['id']);
                $metadata = $_payment->metadata;

                if (!is_array($metadata)) {
                    $result = str_replace(['\\'], '', $metadata);
                    $padrao = '/,"identifier":\["[^"]+"\]}/';
                    if ($res = json_decode($result, true)) {
                        $_payment->metadata = $res;
                    } else {
                        $result = str_replace(['\\', '{"0":'], '', $metadata);
                        $resultado = preg_replace($padrao, '', $result);
                        $pattern = '/^"*|(?<!")"$/';
                        $str = preg_replace($pattern, '', $resultado);
                        $str = str_replace("\\", "\\\\", $str);
                        $str = str_replace(['"""""', '""'], '', $str) . '"}';
                        $str = str_replace(['","OBSERVACOES":,'], '","OBSERVACOES":"",', $str);
                        $str = str_replace(['""}'], '"}', $str);
                        $str = str_replace(['"}"}'], '"}', $str);

                        if ($res = json_decode($str, true)) {
                            $_payment->metadata = $res;
                        } else {
                            $str = str_replace(['"}"}'], '"}', $str) . '}';
                            if ($res = json_decode($str, true)) {
                                $_payment->metadata = $res;
                            }
                        }
                    }
                    $app->log->debug("Coluna metadada do pagamento {$_payment->id} Ajustada");
                    $_payment->save(true);
                    $app->em->clear();
                }
            }

            $app->enableAccessControl();
        }
    },
    'Cadastra dados da fonte pagadora nas oportunidades com configuração ativa do CNAB240' => function () use ($app, $em, $conn) {
        include __DIR__."/registereds/payment_company_data.php";
        foreach($payment_company_data as $key => $data) {
            $def = new \MapasCulturais\Definitions\Metadata($key, $data);
            $app->registerMetadata($def, 'MapasCulturais\Entities\Opportunity');
        }

        $config = $app->config['plugins']['RegistrationPayments']['config'];
        $cnab240_company_data = $config['cnab240_company_data'];
        $opportunitysCnab = $config['opportunitysCnab'];

        $company_data_fields = [
            'nome_empresa' => 'payment_company_data_name',
            'tipo_inscricao' => 'payment_company_data_registration_type',
            'numero_inscricao' => 'payment_company_data_registration_number',
            'agencia' => 'payment_company_data_branch',
            'agencia_dv' => 'payment_company_data_branch_dv',
            'conta' => 'payment_company_data_account',
            'conta_dv' => 'payment_company_data_account_dv',
            'convenio' => 'payment_company_data_agreement',
        ];
        
        $app->disableAccessControl();
        foreach($opportunitysCnab as $opp_id => $settings) {
            if($opportunity = $app->repo('Opportunity')->find($opp_id)) {
                
                $firstPhase = $opportunity->firstPhase;
                $firstPhase->has_payment_phase = true;
                $firstPhase->payment_registration_from = $firstPhase->registrationFrom->format("Y-m-y H:i:s");
                $firstPhase->payment_registration_to = $firstPhase->registrationTo->format("Y-m-y H:i:s");

                foreach($company_data_fields as $key => $field) {
                    $firstPhase->$field = trim($cnab240_company_data[$key]);

                    if(isset($opportunitysCnab[$firstPhase->lastPhase->id]['company_data'])) {
                        $personal_company_data = $opportunitysCnab[$firstPhase->lastPhase->id]['company_data'];
                        foreach($personal_company_data as $_key => $value) {
                            $_field = $company_data_fields[$_key];
                            $firstPhase->$_field = trim($value);
                        }
                    }
                    
                    $app->log->debug("Atualiza informações da fonte pagadora para o CNAB240 na oportunidade {$firstPhase->id} - $firstPhase->name");
                    $firstPhase->save(true);
                }

            }

        }
        $app->enableAccessControl();
    },
];
