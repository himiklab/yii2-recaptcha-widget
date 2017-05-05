<?php

namespace himiklab\yii2\recaptcha;

use himiklab\yii2\recaptcha\ReCaptchaValidator;
use Yii;
use yii\base\Behavior;
use yii\db\ActiveRecord;
use yii\helpers\VarDumper;

class ReCaptchaBehavior extends Behavior
{
    //private $debug_as='firebug';
    private $debug_as = __METHOD__;

    /**
     * @var string attribute name of the owner which uses the reCaptcha widget
     */
    public $attribute;

    /**
     * @var bool whether the reCaptcha validation was already checked
     */
    protected $reCaptcha_checked = false;

    /**
     * @var bool whether the validation should also be checked for guest users
     */
    public $guestsOnly = false;

    /**
     * @var array|string scenarios that the validator can be applied to. For multiple scenarios,
     * please specify them as an array; for single scenario, you may use either a string or an array.
     */
    public $on = [];

    /**
     * @var array|string scenarios that the validator should not be applied to. For multiple scenarios,
     * please specify them as an array; for single scenario, you may use either a string or an array.
     */
    public $except = [];

    /**
     * @var bool whether this validation rule should be skipped if the attribute being validated
     * already has some validation error according to some previous rules. Defaults to true.
     */
    public $skipOnError = true;

    /**
     * @var bool whether this validation rule should be skipped if the attribute value
     * is null or an empty string.
     */
    public $skipOnEmpty = false;

    /**
     * @var string uncheckMessage for the validator.
     */
    public $uncheckedMessage = 'Please confirm that you are not a bot.';

    public function events()
    {
        return [
            ActiveRecord::EVENT_BEFORE_VALIDATE => 'reCaptchaBehaviorValidate',
        ];
    }

    public function setValidationScenario($value)
    {
        return $this->_validationScenario = $value;
    }

    public function getValidationScenario()
    {
        return $this->_validationScenario;
    }

    /**
     * Returns a value indicating whether the validator is active for the given scenario and attribute.
     *
     * A validator is active if
     *
     * - the validator's `on` property is empty, or
     * - the validator's `on` property contains the specified scenario
     *
     * @param string $scenario scenario name
     * @return bool whether the validator applies to the specified scenario.
     */
    public function isActive($scenario)
    {
        return !in_array($scenario, $this->except, true) && (empty($this->on) || in_array($scenario, $this->on, true));
    }

    public function reCaptchaBehaviorValidate($event)
    {
        \Yii::trace("reCaptchaBehaviorAfterValidate ", $this->debug_as);

        if ($this->guestsOnly && !Yii::$app->user->isGuest) {
            \Yii::trace("skip validation for user", $this->debug_as);
            return true;
        }

        $attribute = $this->attribute;
        \Yii::trace("attribute " . \yii\helpers\VarDumper::dumpAsString($attribute), $this->debug_as);

        \Yii::trace("ReCaptcha already validated " . \yii\helpers\VarDumper::dumpAsString($this->reCaptcha_checked), $this->debug_as);

        if (!$this->isActive($this->owner->getScenario())) {
            \Yii::trace("not active scenario therefor skip validation", $this->debug_as);
            return true;
        }

        $validator                   = new ReCaptchaValidator();
        $validator->uncheckedMessage = $this->uncheckMessage;

        // only run validation if not already checked or if the attribute has an error
        $skip = $this->skipOnError && $this->owner->hasErrors($attribute)
        || $this->skipOnEmpty && $validator->isEmpty($this->owner->$attribute)
        || $this->reCaptcha_checked;

        \Yii::trace("skip ReCaptcha validation " . \yii\helpers\VarDumper::dumpAsString($skip), $this->debug_as);

        if (!$skip) {
            if (!$validator->validate($this->owner->$attribute, $error)) {
                \Yii::trace("ReCaptcha add Error  " . \yii\helpers\VarDumper::dumpAsString($attribute), $this->debug_as);
                $this->owner->addError($attribute, $error);
                return false;
            } else {
                \Yii::trace("ReCaptcha validated " . \yii\helpers\VarDumper::dumpAsString($this->owner->$attribute), $this->debug_as);
            }
            $this->reCaptcha_checked = true;
        }
        return true;
    }
}
