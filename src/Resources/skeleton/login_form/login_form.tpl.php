<?= $helper->getHeadPrintCode('Hello {{ controller_name }}!'); ?>

{% block body %}
{% if error %}
<div>{{ error.messageKey|trans(error.messageData, 'security') }}</div>
{% endif %}

<form action="{{ path('login') }}" method="post">
    <label for="username">Username:</label>
    <input type="text" id="username" name="_username" value="{{ last_username }}"/>

    <label for="password">Password:</label>
    <input type="password" id="password" name="_password"/>

    <button type="submit">login</button>
</form>
{% endblock %}
