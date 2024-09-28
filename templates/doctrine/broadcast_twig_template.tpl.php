{# Learn how to use Turbo Streams: https://github.com/symfony/ux-turbo#broadcast-doctrine-entities-update #}
{% block create %}
    <turbo-stream action="append" target="<?= $class_name_plural ?>">
        <template>
            <div id="{{ '<?= $class_name ?>_' ~ id }}">
                #{{ id }} created
            </div>
        </template>
    </turbo-stream>
{% endblock %}

{% block update %}
    <turbo-stream action="update" target="<?= $class_name ?>_{{ id }}">
        <template>
            #{{ id }} updated
        </template>
    </turbo-stream>
{% endblock %}

{% block remove %}
    <turbo-stream action="remove" target="<?= $class_name ?>_{{ id }}"></turbo-stream>
{% endblock %}
