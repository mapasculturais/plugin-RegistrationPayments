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

namespace CnabPHP\resources\B001\remessa\cnab240;

use CnabPHP\RemessaAbstract;
use CnabPHP\resources\generico\remessa\cnab240\Generico3;
use Exception;

class Registro3P extends Generico3
{

    /**
     * Códigos dos campos ajustados para Particularidades BB V7 de 2019
     */
    protected $meta = array(
        'codigo_banco' => array( // 1.3A
            'tamanho' => 3,
            'default' => '001',
            'tipo' => 'int',
            'required' => true
        ),
        'codigo_lote' => array( // 2.3A
            'tamanho' => 4,
            'default' => 1,
            'tipo' => 'int',
            'required' => true
        ),
        'tipo_registro' => array( // 3.3A
            'tamanho' => 1,
            'default' => '3',
            'tipo' => 'int',
            'required' => true
        ),
        'numero_registro' => array( // 4.3A
            'tamanho' => 5,
            'default' => '0',
            'tipo' => 'int',
            'required' => true
        ),
        'seguimento' => array( // 5.3A
            'tamanho' => 1,
            'default' => 'A',
            'tipo' => 'alfa',
            'required' => true
        ),
        'filler1' => array( // 6.3A
            'tamanho' => 1,
            'default' => '0',
            'tipo' => 'int',
            'required' => true
        ),
        'codigo_movimento' => array( // 7.3A
            'tamanho' => 2,
            'default' => '00', // entrada de titulo
            'tipo' => 'int',
            'required' => true
        ),
        'camara_centralizadora' => array( // 08.3A
            'tamanho' => 3,
            'default' => '018',
            'tipo' => 'int',
            'required' => true
        ),
        'codigo_banco_favorecido' => array( // 09.3A
            'tamanho' => 3,
            'default' => '001',
            'tipo' => 'int',
            'required' => true
        ),
        // - ------------------ até aqui é igual para todo registro tipo 3
        'agencia_favorecido' => array( // 10.3A
            'tamanho' => 5,
            'default' => '0',
            'tipo' => 'int',
            'required' => true
        ),
        'agencia_favorecido_dv' => array( // 11.3A
            'tamanho' => 1,
            'default' => '',
            'tipo' => 'alfa',
            'required' => false
        ),
        'conta_favorecido' => array( // 12.3A
            'tamanho' => 12,
            'default' => '0',
            'tipo' => 'int',
            'required' => true
        ),
        'conta_favorecido_dv' => array( // 13.3A
            'tamanho' => 1,
            'default' => '0',
            'tipo' => 'alfa',
            'required' => true
        ),
        'filler2' => array( // 14.3A => campo se chama {Dígito Verificador Agência/Conta} no Particularidades V7 2019
            'tamanho' => 1,
            'default' => '0',
            'tipo' => 'alfa',
            'required' => true
        ),
        'nome_favorecido' => array( // 15.3A
            'tamanho' => 30,
            'default' => ' ',
            'tipo' => 'alfa',
            'required' => true
        ),
        'numero_favorecido' => array( // 16.3A
            'tamanho' => 6,
            'default' => ' ',
            'tipo' => 'alfa',
            'required' => true
        ),
        'numero_pagador' => array( // 16.3B
            'tamanho' => 6,
            'default' => ' ',
            'tipo' => 'alfa',
            'required' => true
        ),
        'referencia_pagamento' => array( // 16.3C
            'tamanho' => 8,
            'default' => ' ',
            'tipo' => 'alfa',
            'required' => true
        ),
        'data_pagamento' => array( // 17.3A
            'tamanho' => 8,
            'default' => '',
            'tipo' => 'date',
            'required' => true
        ),
        'tipo_moeda' => array( // 18.3A
            'tamanho' => 3,
            'default' => 'BRL',
            'tipo' => 'alfa',
            'required' => true
        ),
        'quantidade_moeda' => array( // 19.3A
            'tamanho' => 15,
            'default' => '0',
            'tipo' => 'int',
            'required' => true
        ),
        'valor_pagamento' => array( //20.3A
            'tamanho' => 13,
            'default' => '0',
            'tipo' => 'decimal',
            'precision' => 2,
            'required' => true
        ),
        'nosso_numero' => array( //21.3A
            'tamanho' => 20,
            'default' => ' ',
            'tipo' => 'alfa',
            'required' => true
        ),
        'data_real' => array( //22.3A
            'tamanho' => 8,
            'default' => '',
            'tipo' => 'date',
            'required' => false
        ),
        'valor_real' => array( //23.3A
            'tamanho' => 13,
            'default' => '0',
            'tipo' => 'decimal',
            'precision' => 2,
            'required' => true
        ),
        'outras_info' => array( //24.3A
            'tamanho' => 40,
            'default' => ' ',
            'tipo' => 'alfa',
            'required' => true
        ),
        'codigo_finalidade_doc' => array( //25.3A
            'tamanho' => 2,
            'default' => ' ',
            'tipo' => 'alfa',
            'required' => true
        ),
        'codigo_finalidade_ted' => array( //26.3A
            'tamanho' => 5,
            'default' => ' ',
            'tipo' => 'alfa',
            'required' => true
        ),
        'codigo_finalidade_complementar' => array( //27.3A
            'tamanho' => 2,
            'default' => ' ',
            'tipo' => 'alfa',
            'required' => true
        ),
        'uso_banco' => array( //28.3A
            'tamanho' => 3,
            'default' => ' ',
            'tipo' => 'alfa',
            'required' => true
        ),
        'aviso_fornecedor' => array( //29.3A
            'tamanho' => 1,
            'default' => '0',
            'tipo' => 'int',
            'required' => true
        ),
        'ocorrencias' => array( //30.3A
            'tamanho' => 10,
            'default' => ' ',
            'tipo' => 'alfa',
            'required' => true
        ),
    );

    public function __construct($data = null)
    {
        if (empty($this->data)) {
            parent::__construct($data);
        }

        $this->inserirDetalhe($data);
    }


    public function inserirDetalhe($data)
    {

        $class = 'CnabPHP\resources\\B' . RemessaAbstract::$banco . '\remessa\\' . RemessaAbstract::$layout . '\Registro3Q';



        $this->children[] = new $class($data);
        if (isset($data['codigo_desconto2']) || isset($data['codigo_desconto3']) || isset($data['mensagem'])) {
            $class = 'CnabPHP\resources\\B' . RemessaAbstract::$banco . '\remessa\\' . RemessaAbstract::$layout . '\Registro3R';
            $this->children[] = new $class($data);
        }

        RemessaAbstract::$sumValoesTrailer +=  $data['valor_pagamento'];
    }

    /**
     * Cálculo do módulo 11
     * @param int $index
     * @return int
     */
    protected static function modulo11($num, $base = 9, $r = 0)
    {
        $soma = 0;
        $fator = 2;

        // Separacao dos numeros
        for ($i = strlen($num); $i > 0; $i--) {
            // pega cada numero isoladamente
            $numeros[$i] = substr($num, $i - 1, 1);
            // Efetua multiplicacao do numero pelo falor
            $parcial[$i] = $numeros[$i] * $fator;
            // Soma dos digitos
            $soma += $parcial[$i];
            if ($fator == $base) {
                // restaura fator de multiplicacao para 2
                $fator = 1;
            }
            $fator++;
        }

        // Calculo do modulo 11
        if ($r == 0) {
            $soma *= 10;
            $digito = $soma % 11;
            if ($digito == 10) {
                $digito = 0;
            }
            return $digito;
        } elseif ($r == 1) {
            $resto = $soma % 11;
            return $resto;
        }
    }
}
