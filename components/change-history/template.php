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
        <div class="grid-12 change-history__modal-content">
            <div class="change-history__reviews col-6 sm:col-12">
                <p class="bold"><?= i::__('Revisões') ?></p>

                <div class="change-history__all-reviews mc-scroll">
                    <div class="change-history__individual-review" v-for="(revision, id) in revisions.revisions" :key="id" @click="getDataRevision(revision.id, revision.user.profile.name)">
                        <span><b>{{ formatMessage(revision) }} <?= i::__('em') ?> {{ formatDate(revision.createTimestamp.date) }}</b></span>
                        <span><?= i::__('Por') ?>: {{ revision.user.profile.name }}</span>
                    </div>
                </div>
            </div>

            <div class="change-history__details col-6 sm:col-12">
                <p class="bold"><?= i::__('Detalhes') ?></p>

                <div class="change-history__review-details mc-scroll">
                    <template v-if="dataRevision && dataRevision.amount">
                        <div class="change-history__review-item">
                            <strong><?= i::__('Por') ?>:</strong> {{dataRevision.agent}}
                        </div>

                        <div class="change-history__review-item">
                            <strong><?= i::__('Data') ?>:</strong> {{dataRevision.date.date('numeric year')}} {{dataRevision.date.time('numeric')}}
                        </div>

                        <div class="change-history__review-item">
                            <strong><?= i::__('Valor') ?>:</strong> {{amountToString(dataRevision.amount)}}
                        </div>

                        <div class="change-history__review-item">
                            <strong><?= i::__('Status') ?>:</strong> {{showStatus(dataRevision.status)}}
                        </div>

                        <div v-if="dataRevision.observation" class="change-history__review-item">
                            <strong><?= i::__('Observação') ?>:</strong> {{dataRevision.observation}}
                        </div>
                    </template>

                    <template v-else>
                        <?= i::__('Selecione uma revisão para visualizar os detalhes.') ?>
                    </template>
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