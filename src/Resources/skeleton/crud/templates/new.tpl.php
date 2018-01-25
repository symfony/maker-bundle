{% extends 'base.html.twig' %}

{% block body %}
    <h1>Create new <?= $entity_class_name ?></h1>
    {{ form_start(form) }}
    {{ form_widget(form) }}
    <input type="submit" value="Save">
    {{ form_end(form) }}
{% endblock %}
