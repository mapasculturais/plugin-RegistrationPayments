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
];
