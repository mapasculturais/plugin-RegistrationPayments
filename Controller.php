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

        $app = App::i();
        $conn = $app->em->getConnection();

        $params = ['opp' => $opportunity_id];

        $queryResults = $conn->fetchAll("
            SELECT p.id, p.registration_id, r.number, p.payment_date, p.amount, p.status
            FROM registration r
            RIGHT JOIN payment p
            ON r.id = p.registration_id
            WHERE
                p.opportunity_id = :opp
            ORDER BY r.id
        ", $params);

        foreach($queryResults as $key => $value) {            

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
