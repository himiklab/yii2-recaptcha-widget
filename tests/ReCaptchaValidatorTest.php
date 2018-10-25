<?php

namespace himiklab\yii2\recaptcha\tests;

use himiklab\yii2\recaptcha\ReCaptchaValidator;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class ReCaptchaValidatorTest extends TestCase
{
    /** @var \PHPUnit_Framework_MockObject_MockObject */
    private $validatorClass;

    /** @var \ReflectionMethod */
    private $validatorMethod;

    public function testValidateValueSuccess()
    {
        $this->validatorClass
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn(['success' => true]);

        $this->assertNull($this->validatorMethod->invoke($this->validatorClass, 'test'));
        $this->assertNull($this->validatorMethod->invoke($this->validatorClass, 'test'));
    }

    public function testValidateValueFailure()
    {
        $this->validatorClass
            ->expects($this->exactly(2))
            ->method('getResponse')
            ->willReturn(['success' => false]);

        $this->assertNotNull($this->validatorMethod->invoke($this->validatorClass, 'test'));
        $this->assertNotNull($this->validatorMethod->invoke($this->validatorClass, 'test'));
    }

    public function testValidateValueException()
    {
        $this->validatorClass
            ->expects($this->once())
            ->method('getResponse')
            ->willReturn([]);

        $this->setExpectedException('yii\base\Exception');
        $this->validatorMethod->invoke($this->validatorClass, 'test');
    }

    public function setUp()
    {
        parent::setUp();
        $this->validatorClass = $this->getMockBuilder(ReCaptchaValidator::className())
            ->disableOriginalConstructor()
            ->setMethods(['getResponse'])
            ->getMock();

        $this->validatorMethod = (new ReflectionClass(ReCaptchaValidator::className()))->getMethod('validateValue');
        $this->validatorMethod->setAccessible(true);
    }
}
