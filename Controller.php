<?php

namespace RegistrationPayments;

use DateTime;
use MapasCulturais\i;
use League\Csv\Reader;
use League\Csv\Writer;
use MapasCulturais\App;
use League\Csv\Statement;
use MapasCulturais\Traits;
use CnabPHP\RemessaAbstract;
use RegistrationPayments\Plugin;
use RegistrationPayments\Payment;
use MapasCulturais\Entities\Opportunity;
use MapasCulturais\Entities\Registration;
use MapasCulturais\Entities\RegistrationFieldConfiguration ;

/**
 * Payment Controller
 *
 *  @property-read \RegistrationPayments\Payment $requestedEntity The Requested Entity
 */
// class Controller extends \MapasCulturais\Controllers\EntityController {
class Controller extends \MapasCulturais\Controllers\EntityController
{

    use Traits\ControllerAPI;

    protected $columns = [
        'NUMERO',
        'NOME_COMPLETO',
        'CPF',
        'VALIDACAO',
        'OBSERVACOES',
        'STATUS',
        'DATA 1',
        'VALOR 1',
        'DATA 2',
        'VALOR 2',
        'DATA 3',
        'VALOR 3',
        'DATA 4',
        'VALOR 4',
        'DATA 5',
        'VALOR 5',
        'DATA 6',
        'VALOR 6',
        'DATA 7',
        'VALOR 7',
        'DATA 8',
        'VALOR 8',
        'DATA 9',
        'VALOR 9',
        'DATA 10',
        'VALOR 10',
        'DATA 11',
        'VALOR 11',
        'DATA 12',
        'VALOR 12'
    ];

    public function __construct()
    {
        parent::__construct();
        $this->entityClassName = '\RegistrationPayments\Payment';

    }

    protected function getValidateErrors($opportunity, $registrations, $request) {
        $errors = [];
        
        $from = isset($request['from']) ? DateTime::createFromFormat('Y-m-d', $request['from']) : null;
        $to = isset($request['to']) ? DateTime::createFromFormat('Y-m-d', $request['to']) : null;
        
        if (!$registrations) {
            $errors[] = i::__("Não foram encontrados registros.");   
        }

        if (!$opportunity->canUser('@control')) {
            $errors[] = i::__("Não autorizado");   
        }

        if ($from > $to) {
            $errors[] = i::__("Data inicial está maior que a data final");
        }

        return $errors;
    }

    public function POST_export() {
        $app = App::i();

        //Oportunidade que a query deve filtrar
        $opportunity_id = $this->data['opportunity_id'];
        $opportunity = $app->repo('Opportunity')->find($opportunity_id);

        $this->exportInit($opportunity);
        
        $registrations = $this->getRegistrations($opportunity);
        
        if($errors = $this->getValidateErrors($opportunity, $registrations, $this->data)) {
            $this->errorJson($errors);
        }
        
        $this->generateCSV($registrations, $opportunity);
    }

    protected function exportInit(Opportunity $opportunity) {
        $this->requireAuthentication();

        $opportunity->registerRegistrationMetadata();

        //Seta o timeout
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '768M');    

    }

    protected function getRegistrations(Opportunity $opportunity){
        
        $app = App::i();
        
        $dql_params = [ 'opportunity_Id' => $opportunity->id ];

        $from = isset($this->data['from']) ? DateTime::createFromFormat('Y-m-d', $this->data['from']) : null;
        $to = isset($this->data['to']) ? DateTime::createFromFormat('Y-m-d', $this->data['to']) : null;

        $dql_to = "";
        $dql_from = "";
        if($from && $to) {
            $dql_params['from'] = $from->format('Y-m-d') ?: '';
            $dql_from = $from ? " e.sentTimestamp >= :from AND " : '';
    
            $dql_params['to'] = $to->format('Y-m-d') ?: '';
            $dql_to = $to ? " e.sentTimestamp <= :to AND " : '';
        }

        $dql = "
            SELECT
                e
            FROM
                MapasCulturais\Entities\Registration e
            WHERE
                $dql_to
                $dql_from
                e.opportunity = :opportunity_Id";

        $query = $app->em->createQuery($dql);

        $query->setParameters(array_filter($dql_params));

        return $query->getResult();
        
    }

    protected function generateCSV(array $registrations, Opportunity $opportunity):string {
        /**
         * Array com header do documento CSV
         * @var array $headers
         */
        $headers = $this->columns;

        $csv_data = [];
        $plugin = Plugin::getInstance();
        
        $tratament = $plugin->config['fields_tratament']; 

        foreach ($registrations as $i => $registration) {  
            $csv_data[$i] = [
                'NUMERO' => $registration->number,
                'NOME_COMPLETO' => $tratament($registration, 'NOME_COMPLETO'),
                'CPF' => $tratament($registration, 'CPF'),
                'VALIDACAO' => null,
                'OBSERVACOES' => null,
                'DATA 1' => null,
                'VALOR 1' => null,
                'DATA 2' => null,
                'VALOR 2' => null,
                'DATA 3' => null,
                'VALOR 3' => null,
                'DATA 4' => null,
                'VALOR 4' => null,
                'DATA 5' => null,
                'VALOR 5' => null,
                'DATA 6' => null,
                'VALOR 6' => null,
                'DATA 7' => null,
                'VALOR 7' => null,
                'DATA 8' => null,
                'VALOR 8' => null,
                'DATA 9' => null,
                'VALOR 9' => null,
                'DATA 10' => null,
                'VALOR 10' => null,
                'DATA 11' => null,
                'VALOR 11' => null,
                'DATA 12' => null,
                'VALOR 12' => null                
            ];            
        }        
        
        //$validador = $this->plugin->getSlug();
        $hash = md5(json_encode($csv_data));

        $dir = PRIVATE_FILES_PATH . "financeiro/";

        $file_name = "validador-financeiro-{$hash}.csv";
        $path =  $dir . $file_name;

        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        $stream = fopen($path, 'w');

        $csv = Writer::createFromStream($stream);
        $csv->setDelimiter(";");

        // Caso não exista Fields configurados remove os campos de nome e CPF
        if(!$plugin->config['fields']){
            $map_headers = [];
            foreach($headers as $value){
                if(!in_array($value, ['NOME_COMPLETO', 'CPF'])){
                    $map_headers[] = $value;
                }
            }
          
            $map_csv_data = array_map(function($row){                
                unset($row['NOME_COMPLETO']);
                unset($row['CPF']);

                return $row;
            }, $csv_data);

            $csv_data = $map_csv_data;
            $headers = $map_headers;
        }

        $csv->insertOne($headers);

        foreach ($csv_data as $csv_line) {
            $csv->insertOne($csv_line);
        }

        $class_name = $opportunity->fileClassName;
        $file = new $class_name([
            'name' => $file_name,
            'type' => mime_content_type($path),
            'tmp_name' => $path,
            'error' => 0,
            'size' => filesize ($path)
        ]);
        $file->group = 'export-financial-validator-files';
        $file->description = (new DateTime())->format('dmY'). '-' . $file_name;
        $file->owner = $opportunity;
        $file->save(true);

        $this->json($file); 
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

        $errors = [];
        $data = $this->data;
        $create_type = trim($data['createType']);
        $opportunity = $app->repo('Opportunity')->find($data['opportunity']);
        $lastPhase = $opportunity->lastPhase;

        $user = $app->getUser();

        $plugin = Plugin::getInstance();

        if($errors = $plugin->errorsRequest($data)) {
            $this->errorJson($errors, 400);
        }

        if($create_type == "category"){
            $registrations = $app->repo('Registration')->findBy(['category' => $data[$create_type], 'opportunity' => $lastPhase->id]);

            if (!$registrations) {
                $errors[] = i::__("Não foram encontradas inscrições na categoria {$data[$create_type]}");
                $this->errorJson($errors, 400);
            }
        }

        if($create_type == "registration_id"){
            
            $delimiter = "\n";
            if (strpos($data[$create_type], ",") !== false) {
                $delimiter = ",";
            } 

            $_ids = explode($delimiter,$data[$create_type]);

            $ids = array_map(function ($id) use ($app) {
                return $app->config['registration.prefix'].trim(preg_replace('/[^0-9]/i', '', $id));
            }, $_ids);
            
            $ids = array_filter($ids);
            $registrations = $app->repo('Registration')->findBy(['number' => $ids, 'opportunity' => $lastPhase->id]);

            if (!$registrations) {
                $errors[] = i::__('As inscrições fornecidas não foram encontradas. Lembre-se de que, para efetuar o cadastro de um pagamento, as inscrições devem estar na última fase, ou seja, na etapa de "Publicação final do resultado".');
                $this->errorJson($errors, 400);
            }
        }

        if($create_type == "registrationStatus"){
            $registrations = $app->repo('Registration')->findBy(['status' => $data[$create_type], 'opportunity' => $lastPhase->id]);

            if (!$registrations) {
                $status_description = Registration::getStatusNameById($data[$create_type]); 
                $errors[] = i::__("Não foram encontradas inscrições com o status {$status_description}");
                $this->errorJson($errors, 400);
            }
        }

        foreach ($registrations as $registration) {
            $payment = new Payment();
            $payment->opportunity = $lastPhase;
            $payment->createdByUser = $user;
            $payment->registration = $registration;
            $payment->paymentDate = new DateTime($data['payment_date']);
            $payment->amount = (float) $data['amount'];
            $payment->status = $data['status'] ?? 0;
            $payment->metadata = $data['metadata'] ?? (object) [];
            if ($errors = $payment->getValidationErrors()) {
                $this->errorJson($errors, 400);
            }
            $payment->save(true);
        }

        $app->em->flush();

        $this->finish($payment);
    }

    /**
     *
     * @apiDefine APIPOST
     * @apiDescription Exporta um arquivo CSV com os dados de pagamento filtraos.
     */
    public function POST_exportFilter()
    {
        $this->requireAuthentication();
        $app = App::i();
        $conn = $app->em->getConnection();
        $data = $this->data;
        $opportunity_id = $data["opportunity"];

        $opportunity = $app->repo('Opportunity')->find($opportunity_id);
        $lastPhase = $opportunity->lastPhase;

        $search = isset($data["search"]) ? $data["search"] : "";
        $complement = "";
        $status = isset($data["status"]) ? $data["status"] : [];
        $params = [
            "opp" => $lastPhase->id,
            "search" => "%" . $search . "%",
            "nomeCompleto" => '%"nomeCompleto":"' . $search . '%',
            "documento" => '%"documento":"' . $search . '%',
        ];
        //incrementa parametros caso exista um filtro por data
        if (isset($data["from"]) && !empty($data["from"])) {
            $from = new DateTime($data["from"]);
            $params["from"] = $from->format("Y-m-d");
            $complement .= " AND p.payment_date >= :from";
            if (isset($data["to"]) && !empty($data["to"])) {
                $to = new DateTime($data["to"]);
                $params["to"] = $to->format("Y-m-d");
                $complement .= " AND p.payment_date <= :to";
            }
        }
        //incrementa parametros caso exista um filtro por status
       
        if ($status) {
            $complement .= " AND p.status IN (:status)";
            $params["status"] = implode(',',$status);
        }
       
        //Busca os ids das inscrições
       
        $query = " SELECT p.id, p.registration_id, r.number, p.payment_date, p.amount, p.metadata, p.status
        FROM registration r
        RIGHT JOIN payment p ON r.id = p.registration_id  WHERE p.opportunity_id = :opp AND
         (r.number like :search OR r.agents_data like :nomeCompleto OR r.agents_data like :documento) {$complement}";
        $dataPayments = $conn->fetchAll($query, $params);
        $header = [
            "ID",
            "INSCRICAO",
            "PREVISAO_PAGAMENTO",
            "VALOR",
            "STATUS",
        ];
        $payments = array_map(function ($payment) {
            $date = new DateTime($payment["payment_date"]);
            switch ($payment["status"]) {
                case 0:
                    $status = "Pendente";
                    break;
                case 1:
                    $status = "Processando";
                    break;
                case 2:
                    $status = "Falha";
                    break;
                case 3:
                    $status = "Exportado";
                    break;
                case 8:
                    $status = "Disponível";
                    break;
                case 10:
                    $status = "Pago";
                    break;
                default:
                    $status = $payment["paymentDate"];
                    break;
            }
            return [
                "id" => $payment["id"],
                "inscricao" => $payment["number"],
                "previsaoPagamento" => $date->format("d/m/Y"),
                "valor" => $payment["amount"],
                "status" => $status,
            ];
        }, $dataPayments);
        
        $app->applyHook("opportunity.payments.reportCSV", [ & $dataPayments, &$header, &$payments]);

        $dateExport = new DateTime("now");
        $fileName = "result-filter-payments-opp-" . $data["opportunity"] . md5(json_encode($payments)) . "-" . $dateExport->format("dmY").".csv";
        $dir = PRIVATE_FILES_PATH . "financeiro/";
        $path =  $dir . $fileName;

        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        $stream = fopen($path, 'w');
       
        $csv = Writer::createFromStream($stream);
        $csv->setDelimiter(";");
        $csv->insertOne($header);
        foreach ($payments as $payment) {
            $csv->insertOne($payment);
        }
        
        $opportunity = $app->repo("Opportunity")->find($opportunity_id);
        $class_name = $opportunity->fileClassName;
        $file = new $class_name([
            'name' => $fileName,
            'type' => mime_content_type($path),
            'tmp_name' => $path,
            'error' => 0,
            'size' => filesize ($path)
        ]);
        $file->group = 'export-financial-validator-files';
        $file->description = $fileName;
        $file->owner = $opportunity;
        $file->save(true);

        $this->json($file); 
    }

    /**
     * 
     * @apiDefine APIGET
     * @apiDescription Retorna o historico de alterações de um pagamento.
     */
    public function GET_revision(){
       
        $app = App::i();
        
        $paymentId = $this->data['paymentId'];

        $dataRevisions = [];
        $payment = $app->repo('RegistrationPayments\\Payment')->find($paymentId);
        $entityRevisions = $app->repo("EntityRevision")->findEntityRevisions($payment);
        

        foreach($entityRevisions as $value){
            $dataRevisions[$value->id] = $value->getRevisionData();
        }
        
        $return = [
            'revisions' => $entityRevisions,
            'dataRevisions' => $dataRevisions
        ];

        $this->apiResponse($return);
    }
    
    public function POST_generateCnab()
    {
        //Seta o timeout
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '768M');

        $this->requireAuthentication();

        $app = App::i();

        $plugin = Plugin::getInstance();

        $request = $this->data;

        $errors = [];
        $opportunity = $app->repo("Opportunity")->find(['id' => $request['opportunity_id']]);
        $opportunity->registerRegistrationMetadata($opportunity);
        
        if($errors = $plugin->getCnabValidationErrors($opportunity, $request)) {
            $this->errorJson($errors); 
        }

        if (!$registration_ids = $this->getRegistrationsIds($opportunity)) {
            $errors[] = i::__("Nao foram encontrado inscrições");
        }

        if($errors) {
            $this->errorJson($errors); 
        }

        $test = isset( $request['ts_lot']) &&  $request['ts_lot'] ? true : false;
        $payment_lot_export = json_decode($opportunity->payment_lot_export ?: '[]', true);
        $company_data = $plugin->config['cnab240_company_data'];

        $lot = $plugin->config['opportunitysCnab'][$opportunity->id]['settings']['release_type'][ $request['lotType']];

        $paymentDate =  $request['paymentDate'] ?? null;
     
        /** 
         * Instancia o CANB
         * @var Remessa  $arquivo
        */     
        $arquivo = $plugin->getCanbInstace('001', 'Cnab240', [
            'nome_empresa' => $company_data['nome_empresa'],
            'tipo_inscricao' => $company_data['tipo_inscricao'],
            'numero_inscricao' => $company_data['numero_inscricao'],
            'agencia' => $company_data['agencia'], 
            'agencia_dv' => $company_data['agencia_dv'],
            'conta' => $company_data['conta'], 
            'conta_dv' => $company_data['conta_dv'], 
            'numero_sequencial_arquivo' => 1, 
            'convenio' => $company_data['convenio'],
            'carteira' => '',
            'situacao_arquivo' => $test ? 'TS' : ' ', 
            'uso_bb1' => $company_data['convenio'],
            'uso_bb2' => '0126',
            'uso_bb4' => $test ? 'TS' : ' ',
            'operacao' => 'C',
            'tipo_lancamento' => $lot,

        ]);

     

        // Seta o tipo do lote
        $lote = $arquivo->addLote(array('tipo_servico' => '98')); // 98 = Pagamentos diversos
        $identifier = "lote-" . str_pad($request['identifier'], 4, '0', STR_PAD_LEFT);

        foreach($registration_ids as $key => $id){
            $app = App::i();
           
            // Pega a inscrição pelo ID
            $registration = $app->repo("Registration")->find(['id' => $id]);

            // Pega o pagamento
            $payment = $app->repo('RegistrationPayments\\Payment')->findOneBy(['registration' => $registration->id]);

            if(!$test){
                $paymentInfo = [];
                if(!$payment->metadata){
                    $paymentInfo['identifier'][] = $identifier;
                    $payment->metadata = $paymentInfo;
                }else{
                    $paymentInfo = (array)$payment->metadata ?? [];
                    $paymentInfo['identifier'][] = $identifier;
                    $payment->metadata = $paymentInfo;
                
                }
            
                $payment->status = Payment::STATUS_EXPORTED;
                $payment->save(true);
            }
                        
            // Insere 1 lote
            $lote->inserirDetalhe(array(
               
                //Dados pessoais
                'nome_favorecido' => $this->processValues('proponent_name', $registration),
                'endereco_residencia_favorecido' => $this->processValues('address', $registration),
                'numero_residencia_favorecido' => (int) $this->processValues('number', $registration),
                'complemento_residencia_favorecido' => $this->processValues('complement', $registration),
                'cidade_residencia_favorecido' => $this->processValues('city', $registration),
                'cep_residencia_favorecido' => $this->processValues('zipcode', $registration),
                'estado_residencia_favorecido' => 'PE',
                'data_emissao' => (new DateTime('now'))->format("Y-m-d"), 


                //Dados para pagamento
                'codigo_banco_favorecido' => substr(preg_replace('/[^0-9]/', '', $this->processValues('bank', $registration)), 0, 3),
                'agencia_favorecido' => $this->processValues('branch', $registration),
                'agencia_favorecido_dv' => $this->processValues('branch_dv', $registration),
                'conta_favorecido' => preg_replace('/[^0-9]/', '', $this->processValues('account', $registration)),
                'conta_favorecido_dv' => $this->processValues('account_dv', $registration),
                'data_pagamento' => $paymentDate ?? $payment->paymentDate->format("Y-m-d"),
                'valor_pagamento' => $payment->amount, 
                'tipo_inscricao' => $this->processValues('social_type', $registration), 
                'numero_inscricao' => $this->processValues('proponent_document', $registration),
                'referencia_pagamento' => base_convert($registration->id, 10, 36)

            ));

            $complementDebugInfo = $test ? "para teste" : "para pagamento";
            $app->log->debug("#{$key} - CNAB240 Exportando inscrição {$id} {$complementDebugInfo}");
            $app->em->clear();
        }

        // Salva a refêrencia do lote na oportunidade
        if (!$test) {
            $opportunity = $app->repo("Opportunity")->find(['id' => $request['opportunity_id']]);
            $payment_lot_export[$identifier][] = $request['lotType'];
            $opportunity->payment_lot_export = json_encode($payment_lot_export);
            $opportunity->save(true);
        }

        $fileType = str_replace(" ", "_", strtolower($plugin->config['file_type'][$request['lotType']]));
        $name = mb_strtolower(str_replace(" ", "-", mb_substr($opportunity->name, 0, 20)));
        $amount_file = preg_replace('/[^0-9]/i', '', RemessaAbstract::$sumValoesTrailer);
        $file_name = "pagamento-{$amount_file}---{$identifier}-{$name}--opp-{$opportunity->id}-{$fileType}-canb240.txt";


        $dir = BASE_PATH . '/cnab/';

        $patch = $dir . $file_name;

        if (!is_dir($dir)) {
            mkdir($dir, 0700, true);
        }

        $stream = fopen($patch, 'w');

        fwrite($stream, $arquivo->getText());

        fclose($stream);
        
        $class_name = $opportunity->fileClassName;
        $file = new $class_name([
            'name' => $file_name,
            'type' => mime_content_type($patch),
            'tmp_name' => $patch,
            'error' => 0,
            'size' => filesize ($patch)
        ]);
        $file->group = 'export-cnab-files';
        $file->description = $file_name;   
        $file->owner = $opportunity;
        $file->save(true);

        $this->json($file);
    }

    /**
     * Retorna as inscrições
     *
     * @param mixed $opportunity
     * @return \MapasCulturais\Entities\Registration[]
     */
    public function getRegistrationsIds(Opportunity $opportunity)
    {
        $this->requireAuthentication();

        $app = App::i();

        $plugin = Plugin::getInstance();       

      
        $params = [];

        $complement_join = "";
        $complement_where = "";
        $conn = $app->em->getConnection();
        $lot = $plugin->config['opportunitysCnab'][$opportunity->id]['settings']['release_type'][$this->data['lotType']];

        $test = false;
        if(isset($this->data['ts_lot']) && $this->data['ts_lot'] == 'on'){
            $test = true;
        }

        if($this->data['registrationFilter']){

            $registrationFilter = $this->data['registrationFilter'];
            $delimiter = "\n";

            if(count(explode(",", $registrationFilter)) >1){
                $delimiter = "\n"; 
            }
            
            $ids = explode($delimiter, $registrationFilter);

            $result = array_map(function($id){
                return preg_replace('/[^0-9]/i', '', $id);
            },$ids);         
            
            $list = implode(",", array_filter($result));
            $complement_where.= "AND r.id IN ({$list})";

        }

        if($lot == '01' || $lot == '05'){
            $acount = $plugin->config['opportunitysCnab'][$opportunity->id]['settings']['default_lot_type'][$lot];
            $complement_join .= " join registration_meta account on r.id = account.object_id";
            $complement_where .= " AND account.key = :field_type_account";
            $complement_where .= " AND account.value = :account";
            $params['field_type_account'] = "field_".$plugin->config['opportunitysCnab'][$opportunity->id]['account_type'];
            $params['account'] = $acount;


            
            $complement_join .= " join registration_meta bank on r.id = bank.object_id";
            $complement_where .= " AND bank.key = :field_bank";
            $complement_where .= " AND bank.value = :bank_name";
            $params['field_bank'] = "field_".$plugin->config['opportunitysCnab'][$opportunity->id]['bank'];
            $params['bank_name'] = $plugin->config['opportunitysCnab'][$opportunity->id]['canab_bb_default_value'];
            
        }else if($lot == '03'){
            $complement_join .= " join registration_meta bank on r.id = bank.object_id";
            $complement_where .= " AND bank.key = :field_bank";
            $complement_where .= " AND bank.value <> :bank_name";
            $params['field_bank'] = "field_".$plugin->config['opportunitysCnab'][$opportunity->id]['bank'];
            $params['bank_name'] = $plugin->config['opportunitysCnab'][$opportunity->id]['canab_bb_default_value'];
        }

        $complement_where .= " AND p.status >= :p_status";
       
        $query = "SELECT r.id FROM registration r 
                  JOIN payment p on r.id = p.registration_id {$complement_join}
                  WHERE 
                  r.status > :r_status AND 
                  r.opportunity_id = :opportunity_id {$complement_where}";

        $params += [
            'opportunity_id' => $opportunity->id,
            'r_status' => 0,
            'p_status' => 0
        ];

       
       
        $registrations_ids = $conn->fetchAll($query, $params);

        
        $ids = [];
        foreach ($registrations_ids as $value) {
            $ids[] = $value['id'];
        }

        return $ids;
    }
    
    /**
     * Processa os valores devolvendo os dados que devem ser exibidos
     *
     * @param  mixed $value
     * @param  Registration $registration
     * @return string
     */
    public function processValues($value, \MapasCulturais\Entities\Registration $registration)
    {
        if(!$value){
            return "";
        }

        $plugin = Plugin::getInstance();

        $settings = $plugin->config['opportunitysCnab'][$registration->opportunity->id]['settings'];

        $field_id = $plugin->config['opportunitysCnab'][$registration->opportunity->id][$value] ?? null;
       
        $tratament = $plugin->config['treatments'][$value] ?? null;

        $metadata = $registration->getMetadata();

        $dependence = null;
        if(is_array($field_id) && isset($field_id['dependence'])){

            
            if($field_id['dependence'] == "category"){
                $field_id = $field_id['dependence']; 
            }else{
                $id = $plugin->config['opportunitysCnab'][$registration->opportunity->id][$field_id['dependence']];
                $dependence = $settings[$field_id['dependence']][$metadata['field_'.$id]]; 
                $field_id = $field_id[$dependence];  
            }
                      
        }
        
        return $tratament ? $tratament($registration, $field_id, $settings, $metadata, $dependence) : $value;
    }

    public function getImportValidateErros($file) {
        $errors = [];
        $filename = $file->getPath();
        $ext = pathinfo($filename, PATHINFO_EXTENSION);

        if (!file_exists($filename)) {
            $errors[] = i::__("Erro ao processar o arquivo. Arquivo inexistente");
        }
        
        if ($ext != "csv") {
            $errors[] = i::__("Arquivo não permitido.");
        }

        return $errors;
    }
    
    public function POST_import() {
        $this->requireAuthentication();
        
        $app = App::i();
        $file = $app->repo('File')->find($this->data['file_id']);
        $opportunity = $file->owner;
        $lastPhase = $opportunity->lastPhase;

        $lastPhase->checkPermission('@control');

        if($errors = $this->getImportValidateErros($file)) {
            $this->errorJson($errors);
        }

        $this->import($lastPhase, $file->getPath());
    }

    /**
     * Importador para o inciso 1
     *
     * http://localhost:8080/{slug}/import/
     *
     */
    public function import(Opportunity $opportunity, string $filename)
    {

        /**
         * Seta o timeout e limite de memoria
         */
        ini_set('max_execution_time', 0);
        ini_set('memory_limit', '768M');

        $app = App::i();
        
        //Abre o arquivo em modo de leitura
        $stream = fopen($filename, "r");

        //Faz a leitura do arquivo
        $csv = Reader::createFromStream($stream);

        //Define o limitador do arqivo (, ou ;)
        $csv->setDelimiter(";");

        //Seta em que linha deve se iniciar a leitura
        $header_temp = $csv->setHeaderOffset(0);
        
        //Faz o processamento dos dados
        $stmt = (new Statement());
        $results = $stmt->process($csv);

        $header_file = [];
        foreach ($header_temp as $key => $value) {
            $header_file[] = $value;
            break;
        }
        $required_columns = ['NUMERO', 'VALIDACAO', 'OBSERVACOES', 'DATA 1', 'VALOR 1'];

        $columns = '"' . implode('", "', $required_columns) . '"';
        foreach ($required_columns as $column) {
            if (!isset($header_file[0][$column])) {
                die("As colunas {$columns} são obrigatórias");
            }
        }
       
        $slug = (new DateTime())->format('dmY'). '-' . $filename;
        $name = 'plugin_de_pagamento';
        
        $app->disableAccessControl();
        $count = 0;
        foreach ($results as $i => $line) {
            $num = $line['NUMERO'];
            $obs = $line['OBSERVACOES'];
            $eval = $line['VALIDACAO'];
            $column_status = (isset($line['STATUS']) && !empty($line['STATUS'])) ? $line['STATUS'] : null;
            $status = $this->getStatus($column_status);
            
            switch(strtolower($eval)){
                case 'aprovado':
                case 'aprovada':
                case 'selecionado':
                case 'selecionada':
                    $result = '10';
                break;

                case 'negada':
                case 'negado':
                case 'invalido':
                case 'inválido':
                case 'invalida':
                case 'inválida':
                    $result = '2';
                break;

                case 'não selecionado':
                case 'nao selecionado':
                case 'não selecionada':
                case 'nao selecionada':
                    $result = '3';
                break;
                
                case 'suplente':
                    $result = '8';
                break;
                
                default:
                    die("O valor da coluna VALIDACAO da linha $i está incorreto ($eval). Os valores possíveis são 'selecionada' ou 'aprovada', 'invalida', 'nao selecionada' ou 'suplente'");
                
            }
            
            if ($result == '10') {              
                
                for ($i = 1; $i <= 12; $i++) {
                    $data = $line["DATA {$i}"] ?? null;
                    $valor = $line["VALOR {$i}"] ?? null;
                    if ($data && $valor) {
                        $data = (new \DateTime($data))->format('d/m/Y');
                        $valor = number_format($valor, 2);
                        if(empty($obs)){
                            $obs = "Inscrição Aprovada\n------------------";                                               
                        }
                                                
                        $obs .= "\nR$ $valor a serem pagos em {$data}";    
                    }
                }
            }
            
            $registration = $app->repo('Registration')->findOneBy(['number' => $num, 'opportunity' => $opportunity]);


            if(!$registration){
                $app->log->debug($num. " Não encontrada");
                continue;
            }

            $registration->__skipQueuingPCacheRecreation = true;

            $raw_data = $registration->{$slug . '_raw'};
            $filesnames = $registration->{$slug . '_filename'};
            
            /* @TODO: implementar atualização de status?? */
            /*if (in_array($filename, $filesnames)) {
                $app->log->info("$name #{$count} {$registration} $eval - JÁ PROCESSADA");
                continue;
            }*/
            
            $mess = $column_status ?? $eval;

            $app->log->info("$name #{$count} {$registration} $mess");

            $raw_data[] = $line;
            $filesnames[] = $filename;

            $registration->{$slug . '_raw'} = $raw_data;
            $registration->{$slug . '_filename'} = $filesnames;

            $registration->save(true);

            $plugin = Plugin::getInstance();
            
            for ($i = 1; $i <= 5; $i++) {
                $data = $line["DATA {$i}"] ?? null;
                $valor = $line["VALOR {$i}"] ?? null;
                if ($data && $valor) {
                    $payment = new Payment;
                    $payment->createdByUser = $plugin->getUser();
                    $payment->paymentDate = $data;
                    $payment->amount = str_replace(',','.',$valor);
                    $payment->registration = $registration;
                    $payment->metadata->csv_line = $line;
                    $payment->metadata->csv_filename = $filename;
                    $payment->status = $status;

                    $payment->save(true);
                }
            }

            $app->em->clear();
        }

        $app->enableAccessControl();
        
        // por causa do $app->em->clear(); não é possível mais utilizar a entidade para salvar
        $opportunity = $app->repo('Opportunity')->find($opportunity->id);

        $slug = 'import-financial-validator-files';
        //$slug = $this->plugin->getSlug();

        $opportunity->refresh();
        $opportunity->name = $opportunity->name . ' ';
        $files = $opportunity->payment_processed_files;
        $files->{basename($filename)} = date('d/m/Y \à\s H:i');
        $opportunity->payment_processed_files = $files;
        $opportunity->save(true);
        $this->finish('ok');
    }

    public function getStatus($value) {
        switch ($value) {
            case 'pago':
            case 'Pago':
            case 'PAGO':
            case 'paga':
            case 'Paga':
            case 'PAGA':
                $status = Payment::STATUS_PAID;
            break;

            case 'pendente':
            case 'Pendente':
            case 'PENDENTE':          
                $status = Payment::STATUS_PENDING;
            break;

            case 'PROCESSADO':
            case 'processado':
            case 'Processado':
            case 'processada':
            case 'Processada':
            case 'PROCESSADA':                     
                $status = Payment::STATUS_PROCESSING;
            break;

            case 'falha':
            case 'Falha':
                $status = Payment::STATUS_FAILED;
            break;
            
            case 'EXPORTADO':
            case 'EXPORTADA':
            case 'exportado':
            case 'Exportada':          
                $status = Payment::STATUS_EXPORTED;
            break;

            case 'DISPONIVEL':
            case 'Disponivel':
            case 'DISPONÍVEL':
            case 'Disponível':
            case 'disponível':
            case 'disponível':          
                $status = Payment::STATUS_AVAILABLE;
            break;
            
            default:
                $status = Payment::STATUS_PROCESSING;
                break;
        }
        
        return $status;
    }

    public function POST_bankfields() {
        $app = App::i();
        $opportunity_id = $this->data['opportunity_id'];
        $opportunity = $app->repo('Opportunity')->find($opportunity_id);

        if($opportunity) {
            $newField = new RegistrationFieldConfiguration;
            $newField->owner = $opportunity;
            $newField->title = i::__("Dados bancários.");
            $newField->description = i::__("Dados bancários.");
            $newField->categories = '';
            $newField->required = true;
            $newField->fieldType = 'agent-owner-field';
            $newField->fieldOptions = '';
            $newField->maxSize = '';
            $newField->displayOrder = 1;
            $newField->config = ['entityField' => '@bankFields'];
            $newField->conditional = false;
            $newField->conditionalField = '';
            $newField->conditionalValue = '';
            $newField->save(true);
        } else {
            return $this->errorJson(i::__("Oportunidade não encontrada!"));
        }
    }
}
