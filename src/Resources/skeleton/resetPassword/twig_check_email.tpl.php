{% extends 'base.html.twig' %}

{% block title %}Password Reset Email Sent{% endblock %}

{% block body %}
    <p>An email has been sent that contains a link that you can click to reset your password. This link will expire in {{ tokenLifetime|date('g') }} hour(s).</p>
    <p>If you don't receive an email please check your spam folder or <a href="{{ path('app_forgot_password_request') }}">try again</a>.</p>
{% endblock %}
