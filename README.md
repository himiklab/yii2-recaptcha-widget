Google reCAPTCHA widget for Yii2
================================
Based on reCaptcha API 2.0.

Installation
------------
The preferred way to install this extension is through [composer](http://getcomposer.org/download/).

* Either run

```
php composer.phar require --prefer-dist "himiklab/yii2-recaptcha-widget" "*"
```

or add

```json
"himiklab/yii2-recaptcha-widget" : "*"
```

to the `require` section of your application's `composer.json` file.

* [Sign up for an reCAPTCHA API keys](https://www.google.com/recaptcha/admin#createsite).

* Configure the component in your configuration file (web.php). The parameters siteKey and secret are optional. But if you leave them out you need to set them in every validation rule and every view where you want to use this widget. If a siteKey or secret is set in an individual view or validationrule that would overrule what is set in the config.
```php
'components' => [
    'reCaptcha' => [
        'name' => 'reCaptcha',
        'class' => 'himiklab\yii2\recaptcha\ReCaptcha',
        'siteKey' => '6Le0uwATAAAAAN4QWSko9Dk1zSX_HokHWfrDH0cq',
        'secret' => '6Le0uwATAAAAAARNVzCKlrAPPXlybzvHuoAyCrJa',
    ],
    ...
```

* Add `ReCaptchaValidator` in your model, for example:

```php
public $reCaptcha;

public function rules()
{
  return [
      // ...
      [['reCaptcha'], \himiklab\yii2\recaptcha\ReCaptchaValidator::className(), 'secret' => 'your secret key']
  ];
}
```

or just

```php
public function rules()
{
  return [
      // ...
      [[], \himiklab\yii2\recaptcha\ReCaptchaValidator::className(), 'secret' => 'your secret key']
  ];
}
```

or simply

```php
public function rules()
{
  return [
      // ...
      [[], \himiklab\yii2\recaptcha\ReCaptchaValidator::className()]
  ];
}
```

Usage
-----
For example:

```php
<?= $form->field($model, 'reCaptcha')->widget(
    \himiklab\yii2\recaptcha\ReCaptcha::className(),
    ['siteKey' => 'your siteKey']
) ?>
```

or

```php
<?= \himiklab\yii2\recaptcha\ReCaptcha::widget([
    'name' => 'reCaptcha',
    'siteKey' => 'your siteKey',
    'widgetOptions' => ['class' => 'col-sm-offset-3']
]) ?>
```

or

```php
<?php use himiklab\yii2\recaptcha\ReCaptcha; ?>
<?= $form->field($model, 'reCaptcha')->widget(ReCaptcha::className()) ?>
```

or simply

```php
<?php use himiklab\yii2\recaptcha\ReCaptcha; ?>
<?= ReCaptcha::widget(['name' => 'reCaptcha']) ?>
```

Resources
---------
* [Google reCAPTCHA](https://developers.google.com/recaptcha)
