<?php echo $helper->getHeadPrintCode('Register'); ?>

{% block body %}
    <h1>Register</h1>

    {{ form_start(registrationForm) }}
        {{ form_row(registrationForm.<?php echo $username_field ?>) }}
        {{ form_row(registrationForm.plainPassword) }}
        {{ form_row(registrationForm.agreeTerms) }}

        <button class="btn">Register</button>
    {{ form_end(registrationForm) }}
{% endblock %}
