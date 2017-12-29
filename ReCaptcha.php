<?php
/**
 * @link https://github.com/himiklab/yii2-recaptcha-widget
 * @copyright Copyright (c) 2014-2017 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\yii2\recaptcha;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\helpers\Inflector;
use yii\widgets\InputWidget;

/**
 * Yii2 Google reCAPTCHA widget.
 *
 * For example:
 *
 * ```php
 * <?= $form->field($model, 'reCaptcha')->widget(
 *  ReCaptcha::className(),
 *  ['siteKey' => 'your siteKey']
 * ) ?>
 * ```
 *
 * or
 *
 * ```php
 * <?= ReCaptcha::widget([
 *  'name' => 'reCaptcha',
 *  'siteKey' => 'your siteKey',
 *  'widgetOptions' => ['class' => 'col-sm-offset-3']
 * ]) ?>
 * ```
 *
 * @see https://developers.google.com/recaptcha
 * @author HimikLab
 * @package himiklab\yii2\recaptcha
 */
class ReCaptcha extends InputWidget
{
    const JS_API_URL = '//www.google.com/recaptcha/api.js';

    const THEME_LIGHT = 'light';
    const THEME_DARK = 'dark';

    const TYPE_IMAGE = 'image';
    const TYPE_AUDIO = 'audio';

    const SIZE_NORMAL = 'normal';
    const SIZE_COMPACT = 'compact';

    /** @var string Your sitekey. */
    public $siteKey;

    /** @var string Your secret. */
    public $secret;

    /** @var string The color theme of the widget. [[THEME_LIGHT]] (default) or [[THEME_DARK]] */
    public $theme;

    /** @var string The type of CAPTCHA to serve. [[TYPE_IMAGE]] (default) or [[TYPE_AUDIO]] */
    public $type;

    /** @var string The size of the widget. [[SIZE_NORMAL]] (default) or [[SIZE_COMPACT]] */
    public $size;

    /** @var integer The tabindex of the widget */
    public $tabIndex;

    /** @var string Your JS callback function that's executed when the user submits a successful CAPTCHA response. */
    public $jsCallback;

    /**
     * @var string Your JS callback function that's executed when the recaptcha response expires and the user
     * needs to solve a new CAPTCHA.
     */
    public $jsExpiredCallback;

    /** @var array Additional html widget options, such as `class`. */
    public $widgetOptions = [];

    protected static $firstWidget = true;

    public function run()
    {
        if (empty($this->siteKey)) {
            /** @var ReCaptcha $reCaptcha */
            $reCaptcha = Yii::$app->reCaptcha;
            if ($reCaptcha && !empty($reCaptcha->siteKey)) {
                $this->siteKey = $reCaptcha->siteKey;
            } else {
                throw new InvalidConfigException('Required `siteKey` param isn\'t set.');
            }
        }

        if (self::$firstWidget) {
            $view = $this->view;
            $arguments = http_build_query([
                'hl' => $this->getLanguageSuffix(),
                'render' => 'explicit',
                'onload' => 'recaptchaOnloadCallback',
            ]);
            $view->registerJsFile(
                self::JS_API_URL . '?' . $arguments,
                ['position' => $view::POS_END, 'async' => true, 'defer' => true]
            );
            $view->registerJs(
                <<<'JS'
var recaptchaOnloadCallback = function() {
    jQuery(".g-recaptcha").each(function(index) {
        var reCaptcha = jQuery(this);
        var recaptchaClientId = grecaptcha.render(reCaptcha.attr("id"), {
            "sitekey": reCaptcha.attr("data-sitekey"),
            "callback": eval(reCaptcha.attr("data-callback")),
            "theme": reCaptcha.attr("data-theme"),
            "type": reCaptcha.attr("data-type"),
            "size": reCaptcha.attr("data-size"),
            "tabindex": reCaptcha.attr("data-tabindex")
        });
        reCaptcha.data("recaptcha-client-id", recaptchaClientId);
    });
};
JS
                , $view::POS_END);

            self::$firstWidget = false;
        }

        $this->customFieldPrepare();
        echo Html::tag('div', '', $this->buildDivOptions());
    }

    protected function getReCaptchaId()
    {
        if (isset($this->widgetOptions['id'])) {
            return $this->widgetOptions['id'];
        }

        if ($this->hasModel()) {
            return Html::getInputId($this->model, $this->attribute);
        }

        return $this->id . '-' . $this->name;
    }

    protected function getLanguageSuffix()
    {
        $currentAppLanguage = Yii::$app->language;
        $langsExceptions = ['zh-CN', 'zh-TW', 'zh-TW'];

        if (strpos($currentAppLanguage, '-') === false) {
            return $currentAppLanguage;
        }

        if (in_array($currentAppLanguage, $langsExceptions)) {
            return $currentAppLanguage;
        }

        return substr($currentAppLanguage, 0, strpos($currentAppLanguage, '-'));
    }

    protected function customFieldPrepare()
    {
        $view = $this->view;
        $inputId = $this->getReCaptchaId();

        $verifyCallbackName = 'recVerCall' . Inflector::id2camel($inputId);
        if (empty($this->jsCallback)) {
            $jsVerifyCallbackCode = <<<JS
var {$verifyCallbackName} = function(response) {
    jQuery("#{$inputId}").val(response);
    jQuery("#{$inputId}").trigger("change");
}
JS;
        } else {
            $jsVerifyCallbackCode = <<<JS
var {$verifyCallbackName} = function(response) {
    jQuery("#{$inputId}").val(response);
    jQuery("#{$inputId}").trigger("change");
    {$this->jsCallback}(response);
}
JS;
        }
        $view->registerJs($jsVerifyCallbackCode, $view::POS_END);
        $this->jsCallback = $verifyCallbackName;

        $expiredCallbackName = 'recExpCall' . Inflector::id2camel($inputId);
        if (empty($this->jsExpiredCallback)) {
            $jsExpiredCallbackCode = <<<JS
var {$expiredCallbackName} = function(){
    jQuery("#{$inputId}").val("");
};
JS;
        } else {
            $jsExpiredCallbackCode = <<<JS
var {$expiredCallbackName} = function(){
    jQuery("#{$inputId}").val("");
    {$this->jsExpiredCallback}();
};
JS;
        }
        $view->registerJs($jsExpiredCallbackCode, $view::POS_END);
        $this->jsExpiredCallback = $expiredCallbackName;

        if ($this->hasModel()) {
            $inputName = Html::getInputName($this->model, $this->attribute);
        } else {
            $inputName = $this->name;
        }
        echo Html::input('hidden', $inputName, null, ['id' => $inputId]);
    }

    protected function buildDivOptions()
    {
        $divOptions = [
            'class' => 'g-recaptcha',
            'data-sitekey' => $this->siteKey
        ];
        if (!empty($this->jsCallback)) {
            $divOptions['data-callback'] = $this->jsCallback;
        }
        if (!empty($this->jsExpiredCallback)) {
            $divOptions['data-expired-callback'] = $this->jsExpiredCallback;
        }
        if (!empty($this->theme)) {
            $divOptions['data-theme'] = $this->theme;
        }
        if (!empty($this->type)) {
            $divOptions['data-type'] = $this->type;
        }
        if (!empty($this->size)) {
            $divOptions['data-size'] = $this->size;
        }
        if (!empty($this->tabIndex)) {
            $divOptions['data-tabindex'] = $this->tabIndex;
        }

        if (isset($this->widgetOptions['class'])) {
            $divOptions['class'] = "{$divOptions['class']} {$this->widgetOptions['class']}";
        }

        $divOptions['id'] = $this->getReCaptchaId() . '-recaptcha';
        $divOptions += $this->widgetOptions;

        return $divOptions;
    }
}
