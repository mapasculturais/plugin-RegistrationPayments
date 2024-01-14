<?php

/**
 * @var MapasCulturais\App $app
 * @var MapasCulturais\Themes\BaseV2\Theme $this
 */

use MapasCulturais\i;

$this->import('
    mc-icon
    mc-modal
');
?>
<div class="change-history">
    <mc-modal button-label="abrir" :title="'<?= i::__('Histórico de alterações:') ?>'">
        <div class="grid-12">
            <div class="field col-6">
                <h3><?= i::__('Revisões') ?></h3>
                <div v-for="(revision, id) in revisions.revisions" :key="id" @click="getDataRevision(revision.id, revision.user.profile.name)">
                    <span><b>{{ formatMessage(revision) }} <?= i::__('em') ?> {{ formatDate(revision.createTimestamp.date) }}</b></span>
                    <br/>
                    <span><?= i::__('Por') ?>: {{ revision.user.profile.name }}</span>
                </div>
            </div>

            <div class="field col-6">
                <h3><?= i::__('Detalhes') ?></h3>
                <div v-if="dataRevision && dataRevision.amount">
                    <div>
                        <div><?= i::__('Por') ?>: {{dataRevision.agent}}</div>
                        <div><?= i::__('Data') ?>: {{formatDate(dataRevision.date)}}</div>
                        <div><?= i::__('Valor') ?>: {{amountToString(dataRevision.amount)}}</div>
                        <div><?= i::__('Status') ?>: {{showStatus(dataRevision.status)}}</div>
                        <div><?= i::__('Observação') ?>: {{dataRevision.observation}}</div>
                    </div>
                </div>
                <div v-else>
                    <?= i::__('Selecione uma revisão para visualizar os detalhes.') ?>
                </div>
            </div>
        </div>

        <template #button={open}>
            <mc-icon name="history" @click="getRevisions(open)"></mc-icon>
        </template>

        <template #actions="{close}">
            <button class="button button" @click="closeModal(close)"><?= i::__('Cancelar') ?></button>
        </template>
    </mc-modal>
</div>