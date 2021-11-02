<?php
/*
 * CnabPHP - Geração de arquivos de remessa e retorno em PHP
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
namespace CnabPHP\resources\B104\remessa\cnab240_SIGCB;
use CnabPHP\resources\generico\remessa\cnab240\Generico3;
use Exception;

class Registro3S1e2 extends Generico3
{
	protected $meta = array(
		'codigo_banco'=>array(          // 1.3S
			'tamanho'=>3,
			'default'=>'104',
			'tipo'=>'int',
			'required'=>true),
		'codigo_lote'=>array(           // 2.3S
			'tamanho'=>4,
			'default'=>1,
			'tipo'=>'int',
			'required'=>true),
		'tipo_registro'=>array(         // 3.3S
			'tamanho'=>1,
			'default'=>'3',
			'tipo'=>'int',
			'required'=>true),
		'numero_registro'=>array(       // 4.3S
			'tamanho'=>5,
			'default'=>'0',
			'tipo'=>'int',
			'required'=>true),
		'seguimento'=>array(            // 5.3S
			'tamanho'=>1,
			'default'=>'S',
			'tipo'=>'alfa',
			'required'=>true),
		'filler1'=>array(               // 6.3S
			'tamanho'=>1,
			'default'=>' ',
			'tipo'=>'alfa',
			'required'=>true),
		'codigo_movimento'=>array(      // 7.3S
			'tamanho'=>2,
			'default'=>'01', // entrada de titulo
			'tipo'=>'int',
			'required'=>true),
			
			// - ------------------ até aqui é igual para todo registro tipo 3
			
		'tipo_impressao'=>array(               // 8.3S
			'tamanho'=>1,
			'default'=>'0',
			'tipo'=>'int',
			'required'=>true),
		'filler'=>array(            // 9.3S
			'tamanho'=>2,
			'default'=>'0',
			'tipo'=>'date',
			'required'=>true),
		'mensagem_140'=>array(       //10.3S
			'tamanho'=>140,
			'default'=>' ',
			'tipo'=>'alfa',
			'required'=>true),
		'filler2'=>array(               // 11.3S
			'tamanho'=>2,
			'default'=>'0',
			'tipo'=>'int',
			'required'=>true),
		'filler3'=>array(               //12.3S
			'tamanho'=>78,
			'default'=>' ',
			'tipo'=>'alfa',
			'required'=>true),
	);
}

?>
