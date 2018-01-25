{% extends 'base.html.twig' %}

{% block body %}
    <h1>Edit <?= $entity_class_name ?></h1>

    {{ form_start(form) }}
        {{ form_widget(form) }}
        <input type="submit" value="Edit">
    {{ form_end(form) }}

<ul>
    <li>
        <a href="{{ path('<?= $route_name ?>_index') }}">back to list</a>
    </li>
    <li>
        {{ form_start(delete_form) }}
        <input type="submit" value="Delete">
        {{ form_end(delete_form) }}
    </li>
</ul>

{% endblock %}
