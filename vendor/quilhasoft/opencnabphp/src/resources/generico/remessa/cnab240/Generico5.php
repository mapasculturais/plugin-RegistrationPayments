<?php
/*
* CnabPHP - Gera��o de arquivos de remessa e retorno em PHP
*
* LICENSE: The MIT License (MIT)
*
* Copyright (C) 2013 Ciatec.net
*
* Permission is hereby granted, free of charge, to any person obtaining a copy of this
* software and associated documentation files (the "Software"), to deal in the Software
* without restriction, including without limitation the rights to use, copy, modify,
* merge, publish, distribute, sublicense, and/or sell copies of the Software, and to
* permit persons to whom the Software is furnished to do so, subject to the following
* conditions:
*
* The above copyright notice and this permission notice shall be included in all copies
* or substantial portions of the Software.
*
* THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
* INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A
* PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
* HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION
* OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE
* SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
*/
namespace CnabPHP\resources\generico\remessa\cnab240;
use CnabPHP\RegistroRemAbstract;
use CnabPHP\RemessaAbstract;
use Exception;

class Generico5 extends RegistroRemAbstract
{
	protected function set_codigo_lote($value)
	{
		//ArquivoAbstract::$loteCounter++; 
		$this->data['codigo_lote'] = RemessaAbstract::$loteCounter;
	}
	protected function set_qtd_registros($value)
	{
		$lote  = RemessaAbstract::getLote(RemessaAbstract::$loteCounter);
		$this->data['qtd_registros'] = $lote->get_counter()+1;
	}

	protected function set_soma_valores_lote($value)
	{
		
		$this->data['soma_valores_lote'] = RemessaAbstract::$sumValoesTrailer;
		// RemessaAbstract::$valueSum
		// var_dump();
	}
	
}

?>
