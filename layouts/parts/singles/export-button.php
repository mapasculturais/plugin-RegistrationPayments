
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

            
            <label for="lotType">Tipo de exportação</label>
            <select name="lotType" id="lotType">
                <option value="1">Corrente BB</option>
                <option value="2">Poupança BB</option>
                <option value="3">Outros Bancos</option>
            </select>

            <input type="hidden" name="opportunity_id" value="<?=$entity->id?>">
            <button class="btn btn-primary download" type="submit">Exportar</button><br>

            <small>OBS.: Para exportar um arquivo de teste, insira o código 9999 no campo de identificação do lote</small> 

        </form>
    </edit-box>
</div>

