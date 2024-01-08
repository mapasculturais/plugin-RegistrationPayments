<?php


/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

?>
<button class="button button--primary button--icon" @click="exportSheet()" :prop="entity"><?= i::__('download da tabela') ?> <mc-icon name="download"></mc-icon> </button>