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

Resources
---------
* [Google reCAPTCHA](https://developers.google.com/recaptcha)
