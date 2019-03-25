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
use yii\httpclient\Client as HttpClient;
use yii\httpclient\Request as HttpClientRequest;
use yii\validators\Validator;

/**
 * ReCaptcha widget validator.
 *
 * @author HimikLab
 * @package himiklab\yii2\recaptcha
 */
class ReCaptchaValidator extends Validator
{
    const SITE_VERIFY_URL_DEFAULT = 'https://www.google.com/recaptcha/api/siteverify';
    const SITE_VERIFY_URL_ALTERNATIVE = 'https://www.recaptcha.net/recaptcha/api/siteverify';

    /** @var boolean Whether to skip this validator if the input is empty. */
    public $skipOnEmpty = false;

    /** @var string The shared key between your site and ReCAPTCHA. */
    public $secret;

    /** @var string */
    public $uncheckedMessage;

    /** @var \yii\httpclient\Request */
    public $httpClientRequest;

    /** @var string Use [[SITE_VERIFY_URL_ALTERNATIVE]] when [[SITE_VERIFY_URL_DEFAULT]] is not accessible. */
    public $siteVerifyUrl;

    /** @var boolean */
    protected $isValid = false;

    public function init()
    {
        parent::init();
        /** @var self $reCaptchaComponent */
        $reCaptchaComponent = Yii::$app->reCaptcha;

        if (!$this->secret) {
            if ($reCaptchaComponent && $reCaptchaComponent->secret) {
                $this->secret = $reCaptchaComponent->secret;
            } else {
                throw new InvalidConfigException('Required `secret` param isn\'t set.');
            }
        }

        if (!$this->siteVerifyUrl) {
            if ($reCaptchaComponent && $reCaptchaComponent->siteVerifyUrl) {
                $this->siteVerifyUrl = $reCaptchaComponent->siteVerifyUrl;
            } else {
                $this->siteVerifyUrl = self::SITE_VERIFY_URL_DEFAULT;
            }
        }

        if (!$this->httpClientRequest || !($this->httpClientRequest instanceof HttpClientRequest)) {
            $this->httpClientRequest = (new HttpClient())->createRequest();
        }

        if ($this->message === null) {
            $this->message = Yii::t('yii', 'The verification code is incorrect.');
        }
    }

    /**
     * @param \yii\base\Model $model
     * @param string $attribute
     * @param \yii\web\View $view
     * @return string
     */
    public function clientValidateAttribute($model, $attribute, $view)
    {
        $message = addslashes($this->uncheckedMessage ?: Yii::t(
            'yii',
            '{attribute} cannot be blank.',
            ['attribute' => $model->getAttributeLabel($attribute)]
        ));

        return <<<JS
if (!value) {
     messages.push("{$message}");
}
JS;
    }

    /**
     * @param string|array $value
     * @return array|null
     * @throws Exception
     * @throws \yii\base\InvalidParamException
     */
    protected function validateValue($value)
    {
        if (!$this->isValid) {
            $response = $this->getResponse($value);
            if (!isset($response['success'])) {
                throw new Exception('Invalid recaptcha verify response.');
            }

            $this->isValid = $response['success'] === true;
        }

        return $this->isValid ? null : [$this->message, []];
    }

    /**
     * @param string $value
     * @return array
     * @throws Exception
     * @throws \yii\base\InvalidParamException
     */
    protected function getResponse($value)
    {
        $response = $this->httpClientRequest
            ->setMethod('GET')
            ->setUrl($this->siteVerifyUrl)
            ->setData(['secret' => $this->secret, 'response' => $value, 'remoteip' => Yii::$app->request->userIP])
            ->send();
        if (!$response->isOk) {
            throw new Exception('Unable connection to the captcha server. Status code ' . $response->statusCode);
        }

        return $response->data;
    }
}
