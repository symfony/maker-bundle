{% extends 'base.html.twig' %}

{% block title %}Manage Profile{% endblock %}

{% block body %}
<div class="row">
    <div class="col-md-6 offset-md-3">
        <h1>Manage Profile</h1>

        {{ form_start(profileForm) }}
            {{ form_row(profileForm.name) }}

            <button type="submit" class="btn btn-primary">Save</button>
        {{ form_end(profileForm) }}
    </div>
</div>
{% endblock %}
