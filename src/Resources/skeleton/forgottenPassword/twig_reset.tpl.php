<?= $helper->getHeadPrintCode('Reset your password'); ?>

{% block body %}
    <h1>Reset your password</h1>

    {{ form_start(resetForm) }}
        {{ form_row(resetForm.plainPassword) }}

        <button class="btn btn-primary">Reset password</button>
    {{ form_end(resetForm) }}
{% endblock %}
