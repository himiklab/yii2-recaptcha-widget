<?php
/** @var yii\web\View $this */
/** @var string $verifyCallbackName */
/** @var string $inputId */
/** @var string $jsCallback */

$this->registerJs("
\"use strict\";
var {$verifyCallbackName} = function(response) {
    jQuery(\"#{$inputId}\").val(response);
};
" . ($jsCallback ? "{$jsCallback}(response);" : ''), $this::POS_BEGIN);
