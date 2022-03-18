<?= $helper->getHeadPrintCode('Register'); ?>

{% block body %}
<?php if ($will_verify_email): ?>
    {% for flash_error in app.flashes('verify_email_error') %}
        <div class="alert alert-danger" role="alert">{{ flash_error }}</div>
    {% endfor %}

<?php endif; ?>
    <h1>Register</h1>

    {{ form_start(registrationForm) }}
        {{ form_row(registrationForm.<?= $username_field ?>) }}
        {{ form_row(registrationForm.plainPassword, {
            label: 'Password'
        }) }}
        {{ form_row(registrationForm.agreeTerms) }}

        <button type="submit" class="btn">Register</button>
    {{ form_end(registrationForm) }}
{% endblock %}
