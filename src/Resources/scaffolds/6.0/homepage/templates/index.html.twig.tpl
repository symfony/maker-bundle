{% extends 'base.html.twig' %}

{% block title %}Home{% endblock %}

{% block body %}
    <h1>Home</h1>

    {% for type, messages in app.flashes %}
        {% for message in messages %}
            <div class="alert alert-{{ type|replace({error: 'danger'}) }}">
                {{ message }}
            </div>
        {% endfor %}
    {% endfor %}
{% endblock %}
