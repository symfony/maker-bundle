<form name="form" method="post" action="{{ path('<?= $route_name; ?>_delete', {'<?= $entity_identifier; ?>':identifier}) }}">
    <input type="hidden" name="_method" value="_DELETE">
    <input type="hidden" name="_token" value="{{ csrf_token('delete' ~ identifier) }}">
    <input type="submit" value="Delete">
</form>