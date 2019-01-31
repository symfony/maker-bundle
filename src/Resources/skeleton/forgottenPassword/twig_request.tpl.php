<?= $helper->getHeadPrintCode('Recover your password'); ?>

{% block body %}
    <h1>Recover your password</h1>

    {{ form_start(requestForm) }}
        {{ form_row(requestForm.<?= $email_field ?>) }}

        <button class="btn btn-primary">Send e-mail</button>
    {{ form_end(requestForm) }}
{% endblock %}
