<?php
/**
 * @link https://github.com/himiklab/yii2-recaptcha-widget
 * @copyright Copyright (c) 2014-2018 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\yii2\recaptcha;

use Yii;
use yii\base\Exception;
use yii\base\InvalidConfigException;
use yii\helpers\Json;
use yii\validators\Validator;

/**
 * ReCaptcha widget validator.
 *
 * @author HimikLab
 * @package himiklab\yii2\recaptcha
 */
class ReCaptchaValidator extends Validator
{
    const GRABBER_PHP = 1; // file_get_contents
    const GRABBER_CURL = 2; // CURL, because sometimes file_get_contents is deprecated

    const SITE_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

    /** @var boolean Whether to skip this validator if the input is empty. */
    public $skipOnEmpty = false;

    /** @var string The shared key between your site and ReCAPTCHA. */
    public $secret;

    /**
     * @var int Choose your grabber for getting JSON,
     * self::GRABBER_PHP = file_get_contents, self::GRABBER_CURL = CURL
     */
    public $grabberType = self::GRABBER_PHP;

    /** @var string */
    public $uncheckedMessage;

    /** @var boolean */
    protected $isValid = false;

    public function init()
    {
        parent::init();

        if (empty($this->secret)) {
            /** @var ReCaptcha $reCaptcha */
            $reCaptcha = Yii::$app->reCaptcha;
            if ($reCaptcha && !empty($reCaptcha->secret)) {
                $this->secret = $reCaptcha->secret;
            } else {
                throw new InvalidConfigException('Required `secret` param isn\'t set.');
            }
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
            if (!empty($value)) {
                $request = self::SITE_VERIFY_URL . '?' . http_build_query([
                        'secret' => $this->secret,
                        'response' => $value,
                        'remoteip' => Yii::$app->request->userIP
                    ]);
                $response = $this->getResponse($request);
                if (!isset($response['success'])) {
                    throw new Exception('Invalid recaptcha verify response.');
                }

                $this->isValid = (boolean)$response['success'];
            } else {
                $this->isValid = false;
            }
        }

        return $this->isValid ? null : [$this->message, []];
    }

    /**
     * @param string $request
     * @return mixed
     * @throws Exception
     * @throws \yii\base\InvalidParamException
     */
    protected function getResponse($request)
    {
        if ($this->grabberType === self::GRABBER_PHP) {
            $response = @file_get_contents($request);

            if ($response === false) {
                throw new Exception('Unable connection to the captcha server.');
            }
        } else {
            $options = array(
                CURLOPT_CUSTOMREQUEST => 'GET',
                CURLOPT_POST => false,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HEADER => false,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_ENCODING => '',
                CURLOPT_AUTOREFERER => true,
                CURLOPT_CONNECTTIMEOUT => 120,
                CURLOPT_TIMEOUT => 120,
                CURLOPT_MAXREDIRS => 10,
            );

            $curlResource = curl_init($request);
            curl_setopt_array($curlResource, $options);
            $response = curl_exec($curlResource);
            $errno = curl_errno($curlResource);
            $errmsg = curl_error($curlResource);
            curl_close($curlResource);

            if ($errno !== 0) {
                throw new Exception(
                    'Unable connection to the captcha server. Curl error #' . $errno . ' ' . $errmsg
                );
            }
        }

        return Json::decode($response);
    }
}
