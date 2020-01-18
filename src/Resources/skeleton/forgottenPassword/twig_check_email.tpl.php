<?= $helper->getHeadPrintCode('Check your e-mail address'); ?>

{% block body %}
    <p>An email has been sent. It contains a link you must click to reset your password. This link will expire in {{ tokenLifetime }} hours.</p>
<p>If you don't get an email please check your spam folder or <a href="{{ path('app_forgotten_password_request') }}">try again</a>.</p>
{% endblock %}
