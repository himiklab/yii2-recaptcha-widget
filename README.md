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

* Configure the component in your configuration file (web.php). The parameters siteKey and secret are optional.
But if you leave them out you need to set them in every validation rule and every view where you want to use this widget.
If a siteKey or secret is set in an individual view or validation rule that would overrule what is set in the config.

```php
'components' => [
    'reCaptcha' => [
        'name' => 'reCaptcha',
        'class' => 'himiklab\yii2\recaptcha\ReCaptcha',
        'siteKey' => 'your siteKey',
        'secret' => 'your secret key',
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
      [['reCaptcha'], \himiklab\yii2\recaptcha\ReCaptchaValidator::className(), 'secret' => 'your secret key', 'uncheckedMessage' => 'Please confirm that you are not a bot.']
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

To validate by behavior e.g. to validate only "once" because validate() is also called several times in controller or external traits
Add the behavior to your model
```php

public $reCaptcha;

public function behaviors()
{
    return array_merge(parent::behaviors(), [
      ...
        'reCaptcha' => [
            'class' => ReCaptchaBehavior::className(),
            'attribute' => 'reCaptcha',
            'on' => ['default'],  // array of scenarios where the validation should run (optional)
            'except' => [], // array of scenarios where the validation should not run (optional)
            'guestsOnly' => false,  // valdation only for guests? if false also users will be checked
            'uncheckedMessage' => 'Please confirm that you are not a bot.'
        ],
      ...
    ]);
}


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
<?= $form->field($model, 'reCaptcha')->widget(\himiklab\yii2\recaptcha\ReCaptcha::className()) ?>
```

or simply

```php
<?= \himiklab\yii2\recaptcha\ReCaptcha::widget(['name' => 'reCaptcha']) ?>
```

Multiple reCaptcha on a one page
-----
Each the reCaptcha instance must have unique id
```php
<?= $form1->field($modelForm1, 'reCaptcha')
    ->widget(\himiklab\yii2\recaptcha\ReCaptcha::className(), [
        'widgetOptions' => [
            'id' => 're-captcha-form1',
        ]
    ]) ?>

<?= $form2->field($modelForm2, 'reCaptcha')
    ->widget(\himiklab\yii2\recaptcha\ReCaptcha::className(), [
        'widgetOptions' => [
            'id' => 're-captcha-form2',
        ]
    ]) ?>
```
If you use one model in a few forms (ex. feedback form) must use unique ids.

Notes
-----
Exclude a reCaptcha field from ajax validation. It creates problem.

Resources
---------
* [Google reCAPTCHA](https://developers.google.com/recaptcha)
