<?php

$this->registerCss("
#export-progress-box .progress {
    width: 100%;
    min-width: 1%;
}
");

?>

<div id="export-progress-box" class="box box-solid" style="display: none">
    <div class="box-header with-border">
        <h3 class="box-title"><?= Yii::t('hiqdev.export', 'Export Reports') ?></h3>
    </div>

    <div class="box-body">
        <div class="progress-group">
            <span class="progress-text"></span>
            <span class="progress-number"></span>
            <div class="progress sm">
                <div class="progress-bar progress-bar-green progress-bar-striped"></div>
            </div>
        </div>
    </div>

</div>
