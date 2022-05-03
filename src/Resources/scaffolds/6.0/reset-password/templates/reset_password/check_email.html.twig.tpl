{% extends 'base.html.twig' %}

{% block title %}Password Reset Email Sent{% endblock %}

{% block body %}
<div class="row">
    <div class="col-md-6 offset-md-3">
        <h1>Password Reset Email Sent</h1>
        <p>
            If an account matching your email exists, then an email was just sent that contains a link that you can use to reset your password.
            This link will expire in {{ resetToken.expirationMessageKey|trans(resetToken.expirationMessageData, 'ResetPasswordBundle') }}.
        </p>
        <p>If you don't receive an email please check your spam folder or <a href="{{ path('reset_password_request') }}">try again</a>.</p>
    </div>
</div>
{% endblock %}
