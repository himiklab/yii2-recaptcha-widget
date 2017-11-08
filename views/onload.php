var recaptchaOnloadCallback = function() {
    jQuery(".g-recaptcha").each(function(index) {
        var reCaptcha = jQuery(this);
        grecaptcha.render(reCaptcha.attr("id"), {
            'sitekey': reCaptcha.attr("data-sitekey"),
            'callback': eval(reCaptcha.attr("data-callback")),
            'theme': reCaptcha.attr("data-theme"),
            'type': reCaptcha.attr("data-type"),
            'size': reCaptcha.attr("data-size"),
            'tabindex': reCaptcha.attr("data-tabindex"),
        });
    });
};
