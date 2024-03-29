
<?php 

use MapasCulturais\i;
$app = MapasCulturais\App::i();

$url = $app->createUrl('payment', 'generateCnab');

?>
<div class="widget">
    <h3 class="editando">Exportador CNAB240</h3>
    <a class="btn btn-default download btn-export-cancel"  ng-click="editbox.open('form-parameters', $event)" rel="noopener noreferrer">Exportar TXT BB</a>

    <!-- Formulário -->
    <edit-box id="form-parameters" position="top" cancel-label="Cancelar" close-on-cancel="true">
        <form class="form-export-dataprev" action="<?=$url?>" method="POST">
    
            <label for="identifier">Identificação do lote de pagamento</label>
            <input type="number" name="identifier" id="identifier" placeholder="EX.: 001">

            <label for="identifier">Filtrar pela data de pagamento</label>
            <input type="date" name="paymentDate" id="paymentDate">

            
            <label for="lotType">Tipo de exportação</label>
            <select name="lotType" id="lotType">
                <option value="1">Corrente BB</option>
                <option value="2">Poupança BB</option>
                <option value="3">Outros Bancos</option>
            </select>

            <label for="registrationFilter">Filtrar somenta as inscrições</label>
            <textarea name="registrationFilter" id="registrationFilter" cols="30" rows="3" placeholder="Insira aqui a lista de inscrições que deseja exportar"></textarea>

            <input type="hidden" name="opportunity_id" value="<?=$entity->id?>">
            <button class="btn btn-primary download" type="submit">Exportar</button><br>

            <label for="">
                <input type="checkbox", name="ts_lot" id="ts_lot">
                Exportar lote de teste
            </label> <br>

            <small>OBS.: Caso a data de pagamento não for informada, será exportado todos os pagamentos</small>
        </form>
    </edit-box>
</div>

