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
        $this->entityClassName = '\RegistrationPayments\Payment';

    }

    /**
     * Busca pagamentos da oportunidade
     */
    function GET_findPayments($opportunity_id =  null)
    {
        $this->requireAuthentication();

        $opportunity_id = $this->data['opportunity'];
        $data = $this->data;
        $complement = "";       
        
        $app = App::i();
        $conn = $app->em->getConnection();
        
        $limit = isset($data['@limit']) ? $data['@limit'] : 50;
        $page = isset($data['@page'] ) ? $data['@page'] : 1;
        $search = isset($data['search']) ? $data['search'] : "";
        $status = isset($data['status']) ? $data['status'] : null;
        $paymentDate = (isset($data['paymentDate']) && !empty($data['paymentDate'])) ? new DateTime($data['paymentDate']) : null;         
        $offset = ($page -1) * $limit;
        
        //Parametros basicos de pesquisa
        $params = [
            "opp" => $opportunity_id, 
            "search" => "%".$search."%", 
            "limit" => $limit,
            'offset' => $offset
        ]; 
        
        //incrementa parametros caso exista um filtro por status
        if(is_numeric($status)){
            $complement .= " AND p.status = :status";
            $params['status']  = $status;
        }

        //incrementa parametros caso exista um filtro por data
        if($paymentDate){
            $complement .= " AND p.payment_date = :paymentDate";
            $params['paymentDate']  = $paymentDate->format('Y-m-d');
        }
        
        //Busca os ids das inscrições
        $query = " SELECT p.id, p.registration_id, r.number, p.payment_date, p.amount, p.status
        FROM registration r
        RIGHT JOIN payment p
        ON r.id = p.registration_id WHERE
        p.opportunity_id = :opp AND
        (r.number like :search ) {$complement}
        LIMIT :limit
        OFFSET :offset";       
        $payments = $conn->fetchAll($query, $params); 

        //Remonta o retorno de pagamentos para fazer tratamentos nos valores
        $paymentsResultString = array_map(function($payment) {
            return [
                "id" => $payment['id'],
                "registration_id" => $payment['registration_id'],
                "number" => $payment['number'],
                "payment_date" => $payment['payment_date'],
                "amount" => (float) $payment['amount'],
                "status" => $payment['status']
            ];
        },$payments);
        
        //Pega o total de pagamentos cadastrados
        $filter = [
            "opp" => $opportunity_id
        ];

        //incrementa parametros caso exista um filtro por status
        if(is_numeric($status)){
            $filter['status']  = $status;
        }

        //incrementa parametros caso exista um filtro por pagamento
        if($paymentDate){          
            $filter['paymentDate']  = $paymentDate->format('Y-m-d');
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
    function PATCH_single($data = null) {
       
        $this->requireAuthentication();

        if (is_null($data)) {
            $data = $this->postData;
        }

        $app = App::i();

        $app->applyHookBoundTo($this, "PATCH({$this->id}.single):data", ['data' => &$data]);

        $entity = $this->requestedEntity;

        if(!$entity)
            $app->pass();
        
        $function = null;

        //Atribui a propriedade editada
        foreach($data as $field => $value){
            $entity->$field = $value;
        }

        if($_errors = $entity->validationErrors){
            $errors = [];
            foreach($this->postData as $field=>$value){
                if(key_exists($field, $_errors)){
                    $errors[$field] = $_errors[$field];
                }
            }
            
            if($errors){
                $this->errorJson($errors, 400);
            }
        }        
        $this->_finishRequest($entity, true, $function);
    }

}
