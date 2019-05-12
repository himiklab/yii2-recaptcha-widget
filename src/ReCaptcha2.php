<?php
/**
 * @link https://github.com/himiklab/yii2-recaptcha-widget
 * @copyright Copyright (c) 2014-2019 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\yii2\recaptcha;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
use yii\widgets\InputWidget;

/**
 * Yii2 Google reCAPTCHA v2 widget.
 *
 * For example:
 *
 * ```php
 * <?= $form->field($model, 'reCaptcha')->widget(
 *  ReCaptcha::className(),
 *  [
 *   'siteKey' => 'your siteKey' // unnecessary is reCaptcha component was set up
 *  ]
 * ) ?>
 * ```
 *
 * or
 *
 * ```php
 * <?= ReCaptcha::widget([
 *  'name' => 'reCaptcha',
 *  'siteKey' => 'your siteKey', // unnecessary is reCaptcha component was set up
 *  'widgetOptions' => ['class' => 'col-sm-offset-3']
 * ]) ?>
 * ```
 *
 * @see https://developers.google.com/recaptcha
 * @author HimikLab
 * @package himiklab\yii2\recaptcha
 */
class ReCaptcha2 extends InputWidget
{
    const THEME_LIGHT = 'light';
    const THEME_DARK = 'dark';

    const TYPE_IMAGE = 'image';
    const TYPE_AUDIO = 'audio';

    const SIZE_NORMAL = 'normal';
    const SIZE_COMPACT = 'compact';

    /** @var string Your sitekey. */
    public $siteKey;

    /**
     * @var string Use [[ReCaptchaConfig::JS_API_URL_ALTERNATIVE]] when [[ReCaptchaConfig::JS_API_URL_DEFAULT]]
     * is not accessible.
     */
    public $jsApiUrl;

    /** @var string The color theme of the widget. [[THEME_LIGHT]] (default) or [[THEME_DARK]] */
    public $theme;

    /** @var string The type of CAPTCHA to serve. [[TYPE_IMAGE]] (default) or [[TYPE_AUDIO]] */
    public $type;

    /** @var string The size of the widget. [[SIZE_NORMAL]] (default) or [[SIZE_COMPACT]] */
    public $size;

    /** @var integer The tabindex of the widget */
    public $tabIndex;

    /** @var string Your JS callback function that's executed when the user submits a successful reCAPTCHA response. */
    public $jsCallback;

    /**
     * @var string Your JS callback function that's executed when the recaptcha response expires and the user
     * needs to solve a new CAPTCHA.
     */
    public $jsExpiredCallback;

    /** @var string Your JS callback function that's executed when reCAPTCHA encounters an error (usually network
     * connectivity) and cannot continue until connectivity is restored. If you specify a function here, you are
     * responsible for informing the user that they should retry.
     */
    public $jsErrorCallback;

    /** @var string */
    public $configComponentName = 'reCaptcha';

    /** @var array Additional html widget options, such as `class`. */
    public $widgetOptions = [];

    public function __construct($siteKey = null, $jsApiUrl = null, $config = [])
    {
        if ($siteKey && !$this->siteKey) {
            $this->siteKey = $siteKey;
        }
        if ($jsApiUrl && !$this->jsApiUrl) {
            $this->jsApiUrl = $jsApiUrl;
        }

        parent::__construct($config);
    }

    public function init()
    {
        parent::init();
        $this->configComponentProcess();
    }

    public function run()
    {
        parent::run();
        $view = $this->view;
        $arguments = \http_build_query([
            'hl' => $this->getLanguageSuffix(),
            'render' => 'explicit',
            'onload' => 'recaptchaOnloadCallback',
        ]);

        $view->registerJsFile(
            $this->jsApiUrl . '?' . $arguments,
            ['position' => $view::POS_END, 'async' => true, 'defer' => true]
        );
        $view->registerJs(
            <<<'JS'
function recaptchaOnloadCallback() {
    "use strict";
    jQuery(".g-recaptcha").each(function () {
        const reCaptcha = jQuery(this);
        if (reCaptcha.data("recaptcha-client-id") === undefined) {
            const recaptchaClientId = grecaptcha.render(reCaptcha.attr("id"), {
                "callback": function (response) {
                    if (reCaptcha.data("form-id") !== "") {
                        jQuery("#" + reCaptcha.data("input-id"), "#" + reCaptcha.data("form-id")).val(response)
                            .trigger("change");
                    } else {
                        jQuery("#" + reCaptcha.data("input-id")).val(response).trigger("change");
                    }

                    if (reCaptcha.attr("data-callback")) {
                        eval("(" + reCaptcha.attr("data-callback") + ")(response)");
                    }
                },
                "expired-callback": function () {
                    if (reCaptcha.data("form-id") !== "") {
                        jQuery("#" + reCaptcha.data("input-id"), "#" + reCaptcha.data("form-id")).val("");
                    } else {
                        jQuery("#" + reCaptcha.data("input-id")).val("");
                    }

                    if (reCaptcha.attr("data-expired-callback")) {
                        eval("(" + reCaptcha.attr("data-expired-callback") + ")()");
                    }
                },
            });
            reCaptcha.data("recaptcha-client-id", recaptchaClientId);
        }
    });
}
JS
            , $view::POS_END);

        if (Yii::$app->request->isAjax) {
            $view->registerJs(<<<'JS'
if (typeof grecaptcha !== "undefined") {
    recaptchaOnloadCallback();
}
JS
                , $view::POS_END
            );
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

        return $this->id . '-' . $this->inputNameToId($this->name);
    }

    protected function getLanguageSuffix()
    {
        $currentAppLanguage = Yii::$app->language;
        $langsExceptions = ['zh-CN', 'zh-TW', 'zh-TW'];

        if (\strpos($currentAppLanguage, '-') === false) {
            return $currentAppLanguage;
        }

        if (\in_array($currentAppLanguage, $langsExceptions)) {
            return $currentAppLanguage;
        }

        return \substr($currentAppLanguage, 0, \strpos($currentAppLanguage, '-'));
    }

    protected function customFieldPrepare()
    {
        $inputId = $this->getReCaptchaId();

        if ($this->hasModel()) {
            $inputName = Html::getInputName($this->model, $this->attribute);
        } else {
            $inputName = $this->name;
        }

        $options = $this->options;
        $options['id'] = $inputId;

        echo Html::input('hidden', $inputName, null, $options);
    }

    protected function buildDivOptions()
    {
        $divOptions = [
            'class' => 'g-recaptcha',
            'data-sitekey' => $this->siteKey
        ];
        $divOptions += $this->widgetOptions;

        if ($this->jsCallback) {
            $divOptions['data-callback'] = $this->jsCallback;
        }
        if ($this->jsExpiredCallback) {
            $divOptions['data-expired-callback'] = $this->jsExpiredCallback;
        }
        if ($this->jsErrorCallback) {
            $divOptions['data-error-callback'] = $this->jsErrorCallback;
        }
        if ($this->theme) {
            $divOptions['data-theme'] = $this->theme;
        }
        if ($this->type) {
            $divOptions['data-type'] = $this->type;
        }
        if ($this->size) {
            $divOptions['data-size'] = $this->size;
        }
        if ($this->tabIndex) {
            $divOptions['data-tabindex'] = $this->tabIndex;
        }

        if (isset($this->widgetOptions['class'])) {
            $divOptions['class'] = "{$divOptions['class']} {$this->widgetOptions['class']}";
        }
        $divOptions['data-input-id'] = $this->getReCaptchaId();

        if ($this->field && $this->field->form) {
            if ($this->field->form->options['id']) {
                $divOptions['data-form-id'] = $this->field->form->options['id'];
            } else {
                $divOptions['data-form-id'] = $this->field->form->id;
            }
        } else {
            $divOptions['data-form-id'] = '';
        }

        $divOptions['id'] = $this->getReCaptchaId() . '-recaptcha' .
            ($divOptions['data-form-id'] ? ('-' . $divOptions['data-form-id']) : '');

        return $divOptions;
    }

    protected function inputNameToId($name)
    {
        return \str_replace(['[]', '][', '[', ']', ' ', '.'], ['', '-', '-', '', '-', '-'], \strtolower($name));
    }

    protected function configComponentProcess()
    {
        /** @var ReCaptchaConfig $reCaptchaConfig */
        $reCaptchaConfig = Yii::$app->get($this->configComponentName, false);

        if (!$this->siteKey) {
            if ($reCaptchaConfig && $reCaptchaConfig->siteKeyV2) {
                $this->siteKey = $reCaptchaConfig->siteKeyV2;
            } else {
                throw new InvalidConfigException('Required `siteKey` param isn\'t set.');
            }
        }
        if (!$this->jsApiUrl) {
            if ($reCaptchaConfig && $reCaptchaConfig->jsApiUrl) {
                $this->jsApiUrl = $reCaptchaConfig->jsApiUrl;
            } else {
                $this->jsApiUrl = ReCaptchaConfig::JS_API_URL_DEFAULT;
            }
        }
    }
}
