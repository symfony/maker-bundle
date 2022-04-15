{% extends 'base.html.twig' %}

{% block title %}Register{% endblock %}

{% block body %}
<div class="row">
    <div class="col-md-6 offset-md-3">
        <h1>Register</h1>

        {{ form_start(registrationForm) }}
            {{ form_row(registrationForm.name) }}
            {{ form_row(registrationForm.email) }}
            {{ form_row(registrationForm.plainPassword, {
                label: 'Password'
            }) }}

            <button type="submit" class="btn btn-primary">Register</button>
        {{ form_end(registrationForm) }}
    </div>
</div>
{% endblock %}
