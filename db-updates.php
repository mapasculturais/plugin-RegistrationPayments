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
];
