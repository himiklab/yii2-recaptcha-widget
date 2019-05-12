<?php
/**
 * @link https://github.com/himiklab/yii2-recaptcha-widget
 * @copyright Copyright (c) 2014-2019 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\yii2\recaptcha;

use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;

/**
 * reCaptcha v3 widget validator.
 *
 * @see https://developers.google.com/recaptcha/docs/v3
 * @author HimikLab
 * @package himiklab\yii2\recaptcha
 */
class ReCaptchaValidator3 extends ReCaptchaBaseValidator
{
    /** @var float|callable */
    public $threshold = 0.5;

    /** @var string|boolean Set to false if you don`t need to check action. */
    public $action;

    public function __construct(
        $secret = null,
        $siteVerifyUrl = null,
        $checkHostName = null,
        yii\httpclient\Request $httpClientRequest = null,
        $config = [])
    {
        if ($secret && !$this->secret) {
            $this->secret = $secret;
        }

        parent::__construct($siteVerifyUrl, $checkHostName, $httpClientRequest, $config);
    }

    public function init()
    {
        parent::init();
        $this->configComponentProcess();

        if ($this->action === null) {
            $this->action = \preg_replace('/[^a-zA-Z\d\/]/', '', \urldecode(Yii::$app->request->url));
        }
    }

    /**
     * @param string|array $value
     * @return array|null
     * @throws Exception
     * @throws \yii\base\InvalidParamException
     */
    protected function validateValue($value)
    {
        if ($this->isValid === null) {
            if (!$value) {
                $this->isValid = false;
            } else {
                $response = $this->getResponse($value);
                if (isset($response['error-codes'])) {
                    $this->isValid = false;
                } else {
                    if (!isset($response['success'], $response['action'], $response['hostname'], $response['score']) ||
                        $response['success'] !== true ||
                        ($this->action !== false && $response['action'] !== $this->action) ||
                        ($this->checkHostName && $response['hostname'] !== Yii::$app->request->hostName)
                    ) {
                        throw new Exception('Invalid recaptcha verify response.');
                    }

                    if (\is_callable($this->threshold)) {
                        $this->isValid = (bool)\call_user_func($this->threshold, $response['score']);
                    } else {
                        $this->isValid = $response['score'] >= $this->threshold;
                    }
                }
            }
        }

        return $this->isValid ? null : [$this->message, []];
    }

    protected function configComponentProcess()
    {
        parent::configComponentProcess();

        /** @var ReCaptchaConfig $reCaptchaConfig */
        $reCaptchaConfig = Yii::$app->get($this->configComponentName, false);

        if (!$this->secret) {
            if ($reCaptchaConfig && $reCaptchaConfig->secretV3) {
                $this->secret = $reCaptchaConfig->secretV3;
            } else {
                throw new InvalidConfigException('Required `secret` param isn\'t set.');
            }
        }
    }
}
