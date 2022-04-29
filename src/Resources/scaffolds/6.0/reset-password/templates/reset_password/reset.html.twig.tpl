{% extends 'base.html.twig' %}

{% block title %}Reset your password{% endblock %}

{% block body %}
<div class="row">
    <div class="col-md-6 offset-md-3">
        <h1>Reset your password</h1>

        {{ form_start(resetForm) }}
            {{ form_row(resetForm.plainPassword.first, {label: 'New Password'}) }}
            {{ form_row(resetForm.plainPassword.second, {label: 'Repeat New Password'}) }}
            <button class="btn btn-primary">Reset password</button>
        {{ form_end(resetForm) }}
    </div>
</div>
{% endblock %}
