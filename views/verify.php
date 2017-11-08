var <?= $verifyCallbackName ?> = function(response) {
    jQuery('#<?= $inputId ?>').val(response);
<?php if ($jsCallback) { ?>
    <?= $jsCallback ?>(response);
<?php } ?>
};
