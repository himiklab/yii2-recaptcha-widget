<?php
/**
 * @link https://github.com/himiklab/yii2-recaptcha-widget
 * @copyright Copyright (c) 2014-2018 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\yii2\recaptcha;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\Html;
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
    const SIZE_INVISIBLE = 'invisible';

    /** @var string Your sitekey. */
    public $siteKey;

    /** @var string Your secret. */
    public $secret;

    /** @var string The color theme of the widget. [[THEME_LIGHT]] (default) or [[THEME_DARK]] */
    public $theme;

    /** @var string The type of CAPTCHA to serve. [[TYPE_IMAGE]] (default) or [[TYPE_AUDIO]] */
    public $type;

    /** @var string The size of the widget. [[SIZE_NORMAL]] (default) or [[SIZE_COMPACT]] or [[SIZE_INVISIBLE]] */
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

    /** @var string Your JS callback function that's executed when reCAPTCHA encounters an error (usually network
     * connectivity) and cannot continue until connectivity is restored. If you specify a function here, you are
     * responsible for informing the user that they should retry.
     */
    public $jsErrorCallback;

    /** @var array Additional html widget options, such as `class`. */
    public $widgetOptions = [];

    public function run()
    {
        $view = $this->view;
        if (empty($this->siteKey)) {
            /** @var ReCaptcha $reCaptcha */
            $reCaptcha = Yii::$app->reCaptcha;
            if ($reCaptcha && !empty($reCaptcha->siteKey)) {
                $this->siteKey = $reCaptcha->siteKey;
            } else {
                throw new InvalidConfigException('Required `siteKey` param isn\'t set.');
            }
        }

        $arguments = \http_build_query([
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
function recaptchaOnloadCallback() {
    "use strict";
    jQuery(".g-recaptcha").each(function () {
        var reCaptcha = jQuery(this);
        if (reCaptcha.data("recaptcha-client-id") === undefined) {
            var recaptchaClientId = grecaptcha.render(reCaptcha.attr("id"), {
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

            if (reCaptcha.data("size") === "invisible") {
                grecaptcha.execute(recaptchaClientId);
            }
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

        if (!empty($this->jsCallback)) {
            $divOptions['data-callback'] = $this->jsCallback;
        }
        if (!empty($this->jsExpiredCallback)) {
            $divOptions['data-expired-callback'] = $this->jsExpiredCallback;
        }
        if (!empty($this->jsErrorCallback)) {
            $divOptions['data-error-callback'] = $this->jsErrorCallback;
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
        $divOptions['data-input-id'] = $this->getReCaptchaId();

        if ($this->field !== null && $this->field->form !== null) {
            if (!empty($this->field->form->options['id'])) {
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
}
