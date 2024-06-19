{% extends 'base.html.twig' %}

{% block title %}Reset your password{% endblock %}

{% block body %}
    {% for flash_error in app.flashes('reset_password_error') %}
        <div class="alert alert-danger" role="alert">{{ flash_error }}</div>
    {% endfor %}
    <h1>Reset your password</h1>

    {{ form_start(requestForm) }}
        {{ form_row(requestForm.<?= $email_field ?>) }}
        <div>
            <small>
                Enter your email address, and we will send you a
                link to reset your password.
            </small>
        </div>

        <button class="btn btn-primary">Send password reset email</button>
    {{ form_end(requestForm) }}
{% endblock %}
