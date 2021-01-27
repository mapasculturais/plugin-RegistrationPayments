<?php

namespace RegistrationPayments;

use DateTime;
use MapasCulturais\App;
use MapasCulturais\i;
use MapasCulturais\Traits;
use RegistrationPayments\Payment;

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
        $this->entityClassName = '\RegistrationPayments\Payment';

    }

    /**
     * Busca pagamentos da oportunidade
     */
    public function GET_findPayments($opportunity_id = null)
    {
        $this->requireAuthentication();

        $opportunity_id = $this->data['opportunity'];
        $data = $this->data;
        $complement = "";

        $app = App::i();
        $conn = $app->em->getConnection();

        $limit = isset($data['@limit']) ? $data['@limit'] : 50;
        $page = isset($data['@page']) ? (int) $data['@page'] : 1;
        $search = isset($data['search']) ? $data['search'] : "";
        $status = isset($data['status']) ? $data['status'] : null;

        $paymentDate = (isset($data['paymentDate']) && !empty($data['paymentDate'])) ? new DateTime($data['paymentDate']) : null;
        $offset = ($page - 1) * $limit;

        //Parametros basicos de pesquisa
        $params = [
            "opp" => $opportunity_id,
            "search" => "%" . $search . "%",
            "nomeCompleto" => '%"nomeCompleto":"' . $search . '%',
            "documento" => '%"documento":"' . $search . '%',
            "limit" => $limit,
            'offset' => $offset,
        ];

        //incrementa parametros caso exista um filtro por status
        if (is_numeric($status)) {
            $complement .= " AND p.status = :status";
            $params['status'] = $status;
        }

        //incrementa parametros caso exista um filtro por data
        if (isset($data['from']) && !empty($data['from'])) {
            $from = new DateTime($data['from']);
            $params['from'] = $from->format('Y-m-d');
            $complement .= " AND p.payment_date >= :from";

            if (isset($data['to']) && !empty($data['to'])) {
                $to = new DateTime($data['to']);
                $params['to'] = $to->format('Y-m-d');
                $complement .= " AND p.payment_date <= :to";
            }
        }

        //Busca os ids das inscrições
        $query = " SELECT p.id, p.registration_id, r.number, p.payment_date, p.amount, p.metadata, p.status
        FROM registration r
        RIGHT JOIN payment p ON r.id = p.registration_id  WHERE p.opportunity_id = :opp AND
        (r.number like :search OR r.agents_data like :nomeCompleto OR r.agents_data like :documento) {$complement}
        LIMIT :limit OFFSET :offset";

        $payments = $conn->fetchAll($query, $params);

        //Remonta o retorno de pagamentos para fazer tratamentos nos valores
        $paymentsResultString = array_map(function ($payment) {
            return [
                "id" => $payment['id'],
                "registration_id" => $payment['registration_id'],
                "number" => $payment['number'],
                "payment_date" => $payment['payment_date'],
                "amount" => (float) $payment['amount'],
                "metadata" => json_decode($payment['metadata']),
                "status" => $payment['status'],
            ];
        }, $payments);

        //Pega o total de pagamentos cadastrados
        $filter = [
            "opp" => $opportunity_id,
        ];

        //incrementa parametros caso exista um filtro por status
        if (is_numeric($status)) {
            $filter['status'] = $status;
        }

        //incrementa parametros caso exista um filtro por pagamento
        if (isset($data['from']) && !empty($data['from'])) {
            $from = new DateTime($data['from']);
            $filter['from'] = $from->format('Y-m-d');
            $complement .= " AND p.payment_date >= :from";

            if (isset($data['to']) && !empty($data['to'])) {
                $to = new DateTime($data['to']);
                $filter['to'] = $to->format('Y-m-d');
                $complement .= " AND p.payment_date <= :to";
            }
        }

        //Faz a contabilização de resultados e devolve uma soma total de registros encontrados
        $query = "SELECT count(p) as total
        FROM registration r
        RIGHT JOIN payment p
        ON r.id = p.registration_id WHERE
        p.opportunity_id = :opp {$complement}";
        $total = $conn->fetchAll($query, $filter);

        //Retorna os dados
        $this->apiAddHeaderMetadata($this->data, $payments, $total[0]['total']);
        $this->apiResponse($paymentsResultString);
    }

    /**
     *
     * @apiDefine APIPatch
     * @apiDescription Atualiza parcialmente uma entidade.
     * @apiParam {Array} [data] Array com valores para popular os atributos da entidade. Use o método describe para descobrir os atributos.
     */
    public function PATCH_single($data = null)
    {

        $this->requireAuthentication();

        if (is_null($data)) {
            $data = $this->postData;
        }

        $app = App::i();

        $app->applyHookBoundTo($this, "PATCH({$this->id}.single):data", ['data' => &$data]);

        $entity = $this->requestedEntity;

        if (!$entity) {
            $app->pass();
        }

        $function = null;

        //Atribui a propriedade editada
        foreach ($data as $field => $value) {
            $entity->$field = $value;
        }

        if ($_errors = $entity->validationErrors) {
            $errors = [];
            foreach ($this->postData as $field => $value) {
                if (key_exists($field, $_errors)) {
                    $errors[$field] = $_errors[$field];
                }
            }

            if ($errors) {
                $this->errorJson($errors, 400);
            }
        }
        $this->_finishRequest($entity, true, $function);
    }

    /**
     *
     * @apiDefine APIPost
     * @apiDescription Cria uma entidade.
     * @apiParam {Array} [data] Array com valores para popular os atributos da entidade. Use o método describe para descobrir os atributos.
     */
    public function POST_createMultiple($data = null)
    {

        $this->requireAuthentication();

        $app = App::i();

        $user = $app->getUser();

        $data = $this->data;
        $ids = explode(",", $data['registration_id']);

        $ids = array_map(function ($id) {
            return trim(preg_replace('/[^0-9]/i', '', $id));
        }, $ids);

        $ids = array_filter($ids);

        $errors = [];

        $registrations = $app->repo('Registration')->findBy(['id' => $ids]);

        if (!$registrations) {
            $this->errorJson($errors, 400);
        }

        $opportunity = $app->repo('Opportunity')->find($data['opportunity']);

        foreach ($registrations as $registration) {
            $payment = new Payment();
            $payment->opportunity = $opportunity;
            $payment->createdByUser = $user;
            $payment->registration = $registration;
            $payment->paymentDate = new DateTime($data['payment_date']);
            $payment->amount = (float) $data['amount'];
            $payment->status = $data['status'] ?? 1;
            $payment->metadata = $data['metadata'] ?? (object) [];
            if ($errors = $payment->getValidationErrors()) {
                $this->errorJson($errors, 400);
            }
            $payment->save();
        }

        $app->em->flush();

        $this->finish($payment);
    }

    /**
     * 
     * @apiDefine APIPATCH
     * @apiDescription Atualiza as configurações de exibição dos pagamentos.
     * @apiParam {Array} [data] Array com valores para atualizar os atributos da entidade. Use o método describe para descobrir os atributos. 
     */
    function PATCH_savePaymentConfig() {

        $this->requireAuthentication();

        $app = App::i();
        $data = $this->data;

        $opportunity = $app->repo('Opportunity')->find($data['opportunity']);
        $opportunity->paymentsTabEnabled = $data['value'];
        $opportunity->save(true);

        $this->_finishRequest($opportunity, true);

    }
}
