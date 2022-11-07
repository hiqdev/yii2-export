<?php

$this->registerCss("
#export-progress-box .progress {
    width: 100%;
    min-width: 1%;
    margin-bottom: 1em;
}
");

?>

<div id="export-progress-box" class="box box-solid" style="display: none;">
    <div class="box-header with-border">
        <h3 class="box-title"><?= Yii::t('hiqdev.export', 'Export Reports') ?></h3>
    </div>

    <div class="box-body">
        <div class="progress-group">
            <span class="progress-text"></span>
            <span class="progress-number"></span>
            <div class="progress sm">
                <div class="progress-bar progress-bar-green progress-bar-striped active" role="progressbar"></div>
            </div>
            <span class="progress-description text-muted"></span>
        </div>
    </div>

</div>
