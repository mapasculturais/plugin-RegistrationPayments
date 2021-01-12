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

    static $changeStatusMap = [
        Payment::STATUS_PENDING => [
            Payment::STATUS_PENDING => null,
            Payment::STATUS_PROCESSING => 'save',
            Payment::STATUS_FAILED => 'save',
            Payment::STATUS_EXPORTED => 'save',
            Payment::STATUS_AVAILABLE => 'save',
            Payment::STATUS_PAID => 'save'
        ],
        Payment::STATUS_PROCESSING => [
            Payment::STATUS_PENDING => 'save',
            Payment::STATUS_PROCESSING => null,
            Payment::STATUS_FAILED => 'save',
            Payment::STATUS_EXPORTED => 'save',
            Payment::STATUS_AVAILABLE => 'save',
            Payment::STATUS_PAID => 'save'
        ],
        Payment::STATUS_FAILED => [
            Payment::STATUS_PENDING => 'save',
            Payment::STATUS_PROCESSING => 'save',
            Payment::STATUS_FAILED => null,
            Payment::STATUS_EXPORTED => 'save',
            Payment::STATUS_AVAILABLE => 'save',
            Payment::STATUS_PAID => 'save'
        ],
        Payment::STATUS_EXPORTED => [
            Payment::STATUS_PENDING => 'save',
            Payment::STATUS_PROCESSING => 'save',
            Payment::STATUS_FAILED => 'save',
            Payment::STATUS_EXPORTED => null,
            Payment::STATUS_AVAILABLE => 'save',
            Payment::STATUS_PAID => 'save'
        ],
        Payment::STATUS_AVAILABLE => [
            Payment::STATUS_PENDING => 'save',
            Payment::STATUS_PROCESSING => 'save',
            Payment::STATUS_FAILED => 'save',
            Payment::STATUS_EXPORTED => 'save',
            Payment::STATUS_AVAILABLE => null,
            Payment::STATUS_PAID => 'save'
        ],
        Payment::STATUS_PAID => [
            Payment::STATUS_PENDING => 'save',
            Payment::STATUS_PROCESSING => 'save',
            Payment::STATUS_FAILED => 'save',
            Payment::STATUS_EXPORTED => 'save',
            Payment::STATUS_AVAILABLE => 'save',
            Payment::STATUS_PAID => null
        ]
    ];
  
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
        $data = $this->data;       

        $app = App::i();
        $conn = $app->em->getConnection();

        
        $limit = isset($data['@limit']) ? $data['@limit'] : 50;
        $page = isset($data['@page'] ) ? $data['@page'] : 1;
        $search = isset($data['search']) ? $data['search'] : "";
        $max = ($page * $limit);
        $offset = ($page -1) * $limit;    

        //Busca os ids das inscrições
        $payments = $conn->fetchAll("
            SELECT p.id, p.registration_id, r.number, p.payment_date, p.amount, p.status
            FROM registration r
            RIGHT JOIN payment p
            ON r.id = p.registration_id WHERE
            p.opportunity_id = :opp AND
            r.number like :search
            LIMIT :limit
            OFFSET :offset", [
                "opp" => $opportunity_id, 
                "search" => "%".$search."%", 
                "limit" => $limit,
                'offset' => $offset
            ]); 

            $paymentsResultString = array_map(function($payment) {
                return [
                    "id" => $payment['id'],
                    "registration_id" => $payment['registration_id'],
                    "number" => $payment['number'],
                    "payment_date" => $payment['payment_date'],
                    "amount" => (float) $payment['amount'],
                    "status" => $payment['status'],
                   
                ];
            },$payments);            
        
          
      
        //Pega o total de pagamentos cadastrados
        $total = $conn->fetchAll("
        SELECT count(p) as total
        FROM registration r
        RIGHT JOIN payment p
        ON r.id = p.registration_id WHERE
        p.opportunity_id = :opp ", ["opp" => $opportunity_id]);  
        
       

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
