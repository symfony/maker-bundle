{% extends 'base.html.twig' %}

{% block title %}Hello <?= $app_name ?>Controller!{% endblock %}

{% block stylesheets %}
    {{ encore_entry_link_tags('<?= $app_name ?>') }}
{% endblock %}

{% block body %}
    <div id="<?= $app_name ?>"></div>
{% endblock %}

{% block javascripts %}
    {{ encore_entry_script_tags('<?= $app_name ?>') }}
{% endblock %}
