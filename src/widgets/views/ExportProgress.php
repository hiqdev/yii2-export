<?php

$this->registerCss("
#export-progress-box .progress-bar {
    width: 100%;
    min-width: 30%;
}
");

?>

<div id="export-progress-box" class="box box-solid" style="display: none">
    <div class="box-header with-border">
        <h3 class="box-title"><?= Yii::t('hiqdev.export', 'Export Reports') ?></h3>
    </div>

    <div class="box-body">
        <div class="progress active">
            <div class="progress-bar progress-bar-success progress-bar-striped" role="progressbar">
            </div>
        </div>
    </div>

</div>
