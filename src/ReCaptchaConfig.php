<?php
/**
 * @link https://github.com/himiklab/yii2-recaptcha-widget
 * @copyright Copyright (c) 2014-2019 HimikLab
 * @license http://opensource.org/licenses/MIT MIT
 */

namespace himiklab\yii2\recaptcha;

/**
 * Yii2 Google reCAPTCHA widget global config.
 *
 * @see https://developers.google.com/recaptcha
 * @author HimikLab
 * @package himiklab\yii2\recaptcha
 */
class ReCaptchaConfig
{
    const JS_API_URL_DEFAULT = '//www.google.com/recaptcha/api.js';
    const JS_API_URL_ALTERNATIVE = '//www.recaptcha.net/recaptcha/api.js';

    const SITE_VERIFY_URL_DEFAULT = 'https://www.google.com/recaptcha/api/siteverify';
    const SITE_VERIFY_URL_ALTERNATIVE = 'https://www.recaptcha.net/recaptcha/api/siteverify';

    /** @var string Your sitekey for reCAPTCHA v2. */
    public $siteKeyV2;

    /** @var string Your secret for reCAPTCHA v2. */
    public $secretV2;

    /** @var string Your v3 sitekey for reCAPTCHA v3. */
    public $siteKeyV3;

    /** @var string Your secret for reCAPTCHA v3. */
    public $secretV3;

    /** @var string Use [[JS_API_URL_ALTERNATIVE]] when [[JS_API_URL_DEFAULT]] is not accessible. */
    public $jsApiUrl;

    /** @var string Use [[SITE_VERIFY_URL_ALTERNATIVE]] when [[SITE_VERIFY_URL_DEFAULT]] is not accessible. */
    public $siteVerifyUrl;

    /** @var boolean Check host name. */
    public $checkHostName;

    /** @var \yii\httpclient\Request */
    public $httpClientRequest;
}
