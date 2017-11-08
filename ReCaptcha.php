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

    /** @var int The tabindex of the widget */
    public $tabindex;

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
            if (!empty($reCaptcha->siteKey)) {
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

            $view->registerJs($this->render('onload'), $view::POS_BEGIN);

            self::$firstWidget = false;
        }

        $this->customFieldPrepare();
        echo Html::tag('div', '', $this->buildDivOptions());
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
        if (!empty($this->tabindex)) {
            $divOptions['data-tabindex'] = $this->tabindex;
        }

        if (isset($this->widgetOptions['class'])) {
            $divOptions['class'] = "{$divOptions['class']} {$this->widgetOptions['class']}";
        }

        // The id attribute required for explicit reCaptcha initialization
        $divOptions['id'] = $this->getReCaptchaId() . '-recaptcha';

        $divOptions += $this->widgetOptions;

        return $divOptions;
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
        if ($this->hasModel()) {
            $inputName = Html::getInputName($this->model, $this->attribute);
        } else {
            $inputName = $this->name;
        }

        $inputId = $this->getReCaptchaId();
        $verifyCallbackName = lcfirst(Inflector::id2camel($inputId)) . 'Callback';
        $jsCode = $this->render('verify', [
            'verifyCallbackName' => $verifyCallbackName,
            'jsCallback' => $this->jsCallback,
            'inputId' => $inputId,
        ]);
        $this->jsCallback = $verifyCallbackName;

        if (empty($this->jsExpiredCallback)) {
            $jsExpCode = "var recaptchaExpiredCallback = function(){jQuery('#{$inputId}').val('');};";
        } else {
            $jsExpCode = "var recaptchaExpiredCallback = function(){jQuery('#{$inputId}').val(''); " .
                "{$this->jsExpiredCallback}();};";
        }
        $this->jsExpiredCallback = 'recaptchaExpiredCallback';

        $view->registerJs($jsCode, $view::POS_BEGIN);
        $view->registerJs($jsExpCode, $view::POS_BEGIN);

        echo Html::input('hidden', $inputName, null, ['id' => $inputId]);
    }
}
