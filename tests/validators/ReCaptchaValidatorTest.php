<?php
require(__DIR__ . '/../../vendor/yiisoft/yii2/Yii.php');

use \himiklab\yii2\recaptcha\ReCaptchaValidator;

class ReCaptchaValidatorTest extends \PHPUnit_Framework_TestCase
{
    public function testSuccessGetResponse()
    {
        $method = new ReflectionMethod('\himiklab\yii2\recaptcha\ReCaptchaValidator', 'getResponse');
        $method->setAccessible(true);

        $result = $method->invoke((new ReCaptchaValidator(['secret' => 'bla-bla'])), 'https://graph.facebook.com/fql?q=SELECT%20like_count,%20total_count,%20share_count,%20click_count,%20comment_count%20FROM%20link_stat%20WHERE%20url%20=%20%27http://php.net%27');
        $this->assertEquals(gettype($result), 'array');
    }

    /**
     * Catch unable service
     *
     * @expectedException Exception
     */
    public function testFailGetResponse()
    {
        $method = new ReflectionMethod('\himiklab\yii2\recaptcha\ReCaptchaValidator', 'getResponse');
        $method->setAccessible(true);
        $method->invoke((new ReCaptchaValidator(['secret' => 'bla-bla'])), 'http://kek.kek');
    }

    public function testSuccessCurlGetResponse()
    {
        $method = new ReflectionMethod('\himiklab\yii2\recaptcha\ReCaptchaValidator', 'getResponse');
        $method->setAccessible(true);

        $result = $method->invoke((new ReCaptchaValidator(['secret' => 'bla-bla', 'grabberType' => ReCaptchaValidator::GRABBER_CURL])), 'https://graph.facebook.com/fql?q=SELECT%20like_count,%20total_count,%20share_count,%20click_count,%20comment_count%20FROM%20link_stat%20WHERE%20url%20=%20%27http://php.net%27');
        $this->assertEquals(gettype($result), 'array');
    }

    /**
     * Catch unable service
     *
     * @expectedException Exception
     */
    public function testFailCurlGetResponse()
    {
        $method = new ReflectionMethod('\himiklab\yii2\recaptcha\ReCaptchaValidator', 'getResponse');
        $method->setAccessible(true);
        $method->invoke((new ReCaptchaValidator(['secret' => 'bla-bla', 'grabberType' => ReCaptchaValidator::GRABBER_CURL])), 'http://kek.kek');
    }
}