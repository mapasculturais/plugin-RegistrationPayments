<?php

namespace RegistrationPayments;

use MapasCulturais\i;
use MapasCulturais\App;
use MapasCulturais\Traits;
use DateTime;

/**
 * Payment Controller
 *
 *  @property-read \RegistrationPayments\Payment $requestedEntity The Requested Entity
 */
// class Controller extends \MapasCulturais\Controllers\EntityController {
class Controller extends \MapasCulturais\Controllers\EntityController
{

    use Traits\ControllerAPI;

    public function __construct()
    {
        parent::__construct();

        $app = App::i();

        $this->entityClassName = '\RegistrationPayments\Payment';

    }

    /**
     * Busca pagamentos da oportunidade
     */
    function GET_findPayments($opportunity_id =  null)
    {
        $this->requireAuthentication();

        $opportunity_id = $this->data['opportunity'];

        $app = App::i();
        $conn = $app->em->getConnection();

        $params = ['opp' => $opportunity_id];

        $queryResults = $conn->fetchAll("
            SELECT r.id, r.number, p.payment_date, p.amount, p.status
            FROM registration r
            RIGHT JOIN payment p
            ON r.id = p.registration_id
            WHERE
                p.opportunity_id = :opp
            ORDER BY r.id
        ", $params);

        foreach($queryResults as $key => $value) {

            if ($value['status'] == 0) {
                $queryResults[$key]['status'] = 'Pendente';
            } elseif ($value['status'] == 1) {
                $queryResults[$key]['status'] = 'Em processo';
            } elseif ($value['status'] == 2) {
                $queryResults[$key]['status'] = 'Recusado';
            } elseif ($value['status'] == 3) {
                $queryResults[$key]['status'] = 'Exportado';
            } elseif ($value['status'] == 8) {
                $queryResults[$key]['status'] = 'Em avaliação';
            } elseif ($value['status'] == 10) {
                $queryResults[$key]['status'] = 'Pago';
            }

            if ($value['payment_date']) {
                $queryResults[$key]['payment_date'] = date("d/m/Y", strtotime($value['payment_date']));
            }

            if ($value['amount']) {
                $queryResults[$key]['amount'] = 'R$ ' . number_format($value['amount'], '2', ',', '.');
            }

            $queryResults[$key]['url'] = $app->createUrl('inscricao', $value['id']);

        }

        //$this->apiAddHeaderMetadata($this->data, $_result, $queryResults);
        $this->apiResponse($queryResults);
        
    }

}
