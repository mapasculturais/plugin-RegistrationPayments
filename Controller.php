<?php

namespace RegistrationPayments;

use DateTime;
use MapasCulturais\i;
use League\Csv\Writer;
use MapasCulturais\App;
use MapasCulturais\Traits;
use CnabPHP\RemessaAbstract;
use RegistrationPayments\Plugin;
use RegistrationPayments\Payment;
use MapasCulturais\Entities\Opportunity;

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
        $this->entityClassName = 'RegistrationPayments\Payment';

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

        if ($to > $from) {
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

        $dql_params['from'] = $from ?: '';
        $dql_from = $from ? "e.sentTimestamp >= :from AND" : '';

        $dql_params['to'] = $to ?: '';
        $dql_to = $to ? "e.sentTimestamp >= :to AND" : '';

        $dql = "
            SELECT
                e
            FROM
                MapasCulturais\Entities\Registration e
            WHERE
                $dql_to
                $dql_from
                e.status IN (1,10) AND
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

        // $limit = isset($data['@limit']) ? $data['@limit'] : 50;
        $page = isset($data['@page']) ? (int) $data['@page'] : 1;
        $search = isset($data['search']) ? $data['search'] : "";
        $status = isset($data['status']) ? $data['status'] : null;
        // $offset = ($page - 1) * $limit;

        //Parametros basicos de pesquisa
        $params = [
            "opp" => $opportunity_id,
            "search" => "%" . $search . "%",
            "nomeCompleto" => '%"nomeCompleto":"' . $search . '%',
            "documento" => '%"documento":"' . $search . '%',
            // "limit" => $limit,
            // 'offset' => $offset,
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
        (r.number like :search OR r.agents_data like :nomeCompleto OR r.agents_data like :documento) {$complement}";

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

        $plugin = Plugin::getInstance();

        $data = $this->data;

        if($errors = $plugin->errorsRequest($data)) {
            $this->errorJson($errors, 400);
        }

        $ids = explode(",", $data['registration_id']);

        $ids = array_map(function ($id) {
            return trim(preg_replace('/[^0-9]/i', '', $id));
        }, $ids);

        $ids = array_filter($ids);

        $errors = [];

        $registrations = $app->repo('Registration')->findBy(['id' => $ids]);

        $opportunity = $app->repo('Opportunity')->find($data['opportunity']);

        if (!$registrations) {
            $errors[] = i::__("As iscrições informadas não foram encontradas na oportunidade {$opportunity->name}");
            $this->errorJson($errors, 400);
        }

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
            $payment->save(true);
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

    /**
     *
     * @apiDefine APIGET
     * @apiDescription Exporta um arquivo CSV com os dados de pagamento filtraos.
     */
    public function GET_exportFilter()
    {
        $this->requireAuthentication();
        $app = App::i();
        $conn = $app->em->getConnection();
        $data = $this->data;
        $opportunity_id = $this->data["opportunity"];
        $search = isset($data["search"]) ? $data["search"] : "";
        $complement = "";
        $status = isset($data["status"]) ? $data["status"] : null;
        $params = [
            "opp" => $opportunity_id,
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
        if (is_numeric($status)) {
            $complement .= " AND p.status = :status";
            $params["status"] = $status;
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
        $csv = Writer::createFromString();
        $csv->setDelimiter(";");
        $csv->insertOne($header);
        foreach ($payments as $payment) {
            $csv->insertOne($payment);
        }
        $dateExport = new DateTime("now");
        $fileName = "result-filter-payments-opp-" . $data["opportunity"] . md5(json_encode($payments)) . "-" . $dateExport->format("dmY");
        $csv->output($fileName . ".csv");
        return;
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

    public function ALL_generateCnab()
    {
        $this->requireAuthentication();

         //Seta o timeout
         ini_set('max_execution_time', 0);
         ini_set('memory_limit', '768M');

        $app = App::i();

        // Pega a instancia do plugin
        $plugin = Plugin::getInstance();

       // Varifica se o arquivo é um teste ou se é um arquivo oficial
       $test = false;
       if(isset($this->data['ts_lot']) && $this->data['ts_lot'] == 'on'){
           $test = true;
       }
   
        // Pega a oportunidade
        $opportunity = $app->repo("Opportunity")->find(['id' => $this->data['opportunity_id']]);
        $opportunity->registerRegistrationMetadata($opportunity);

        // Verifica se o usuário te controle da oportunidade para executar a exportação
        if(!$opportunity->canUser('@control')){
            echo "Não Autorizado";
            exit;
        }

        // Veirfica existe identificaçõ do lote
        if(!$test && empty($this->data['identifier'])){
            echo "Informe o número de identificação do lote Ex.: 001";
            exit;
        }

         // Pega o identificador
         $identifier = "lote-". str_pad($this->data['identifier'] , 4 , '0' , STR_PAD_LEFT);
         

        // Pega os ID's das inscrições
        $registration_ids = $this->getRegistrationsIds($opportunity,  $identifier);
        

        // Veirfica se o Lote esta informado
        if(!$registration_ids){
            echo "Nao foram encontrado inscrições";
            exit;
        }
   
        // Verifica se o CNAB esta ativo
        if(!$plugin->config['cnab240_enabled']){
            echo "Habilite o CANB240 nas configurações";
            exit;
        }

        // Veirfica se o Lote esta informado
        if(!isset($this->data['lotType'])){
            echo "Informe o tipo de lote <br> 1 Corrente BB<br> 2 Poupança BB<br> 3 Outros bancos";
            exit;
        }

             
        $payment_lot_export = json_decode($opportunity->payment_lot_export ?: '[]', true);

        $typeLot = [
            1 => "Corrente BB",
            2 => "Poupança BB",
            3 => "Outros Bancos"
        ];

        // Verifica se o lote ja foi exportado anteriormente
        if(!$test && in_array($this->data['lotType'], $payment_lot_export[$identifier] ?? [])){
            echo "{$identifier} para o arquivo {$typeLot[$this->data['lotType']]} Já exportado anteriormente. Caso queira fazer uma confêrencia selecione a caixa Exportar lote de teste.";
            exit;
        }

        

        // Pega o tipo de lote a ser exportado
        $lot = $plugin->config['opportunitysCnab'][$opportunity->id]['settings']['release_type'][$this->data['lotType']];

        // Pega as configurações da entidade pagadora
        if(!($company_data = $plugin->config['cnab240_company_data'])){
            echo "A entidade pagadora nao foi configurada";
            exit;
        }

        // Veirfica a data de pagamento 
        if(!($paymentDate = $this->data['paymentDate'])){
           $paymentDate = null;
        }

       

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
                    $payment->metadata = json_encode($paymentInfo);
                }else{
                    $paymentInfo = (array)$payment->metadata ?? [];
                    $paymentInfo['identifier'][] = $identifier;
                    $payment->metadata = json_encode($paymentInfo);
                
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
        if(!$test){
            $opportunity = $app->repo("Opportunity")->find(['id' => $this->data['opportunity_id']]);
            $payment_lot_export[$identifier][] = $this->data['lotType'];
            $opportunity->payment_lot_export = json_encode($payment_lot_export);
            $opportunity->save(true);
        }

  
        $fileType = str_replace(" ", "_", strtolower($plugin->config['file_type'][$this->data['lotType']]));
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
        $file->description = 'arquivo-caab';   
        $file->owner = $opportunity;
        $file->save(true);
    }

    /**
     * Retorna as inscrições
     *
     * @param mixed $opportunity
     * @return \MapasCulturais\Entities\Registration[]
     */
    public function getRegistrationsIds(Opportunity $opportunity, $identifier)
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
            if($lot == '01'){
                $acount = 'Conta corrente';
            }else if($lot == '05'){
                $acount = 'Conta poupança';
            }

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
    

    
}
