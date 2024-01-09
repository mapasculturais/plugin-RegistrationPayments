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
                <div v-for="(revision, id) in revisions.revisions" :key="id">
                    <span><b>{{ formatMessage(revision) }} <?= i::__('em') ?> {{ formatDate(revision.createTimestamp.date) }}</b></span>
                    <br/>
                    <span><?= i::__('Por') ?>: {{ revision.user.profile.name }} </span>
                </div>
            </div>

            <div class="field col-6">
                <h3><?= i::__('Detalhes') ?></h3>
                <div v-for="(dataRevision, id) in revisions.dataRevisions" :key="id" :class="id">
                    <span><b><?= i::__('Data') ?>:</b> {{ formatDate(dataRevision.paymentDate.value.date) }} </span>
                    <br/>
                    <span><b><?= i::__('Valor') ?>:</b> {{ amountToString(dataRevision.amount.value) }} </span>
                    <br/>
                    <span><b><?= i::__('Status') ?>:</b> {{ showStatus(dataRevision.status.value) }} </span>
                </div>

            </div>
        </div>

        <template #button={open}>
            <mc-icon name="history" @click="getRevisions(entity, open)"></mc-icon>
        </template>

        <template #actions="modal">
            <button class="button button" @click="modal.close()"><?= i::__('Cancelar') ?></button>
        </template>
    </mc-modal>
</div>