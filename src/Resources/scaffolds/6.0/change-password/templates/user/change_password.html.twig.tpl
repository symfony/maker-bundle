{% extends 'base.html.twig' %}

{% block title %}Change Password{% endblock %}

{% block body %}
<div class="row">
    <div class="col-md-6 offset-md-3">
        <h1>Change Password</h1>

        {{ form_start(changePasswordForm) }}
            {{ form_row(changePasswordForm.currentPassword, {label: 'Current Password'}) }}
            {{ form_row(changePasswordForm.plainPassword.first, {label: 'New Password'}) }}
            {{ form_row(changePasswordForm.plainPassword.second, {label: 'Repeat New Password'}) }}

            <button type="submit" class="btn btn-primary">Change Password</button>
        {{ form_end(changePasswordForm) }}
    </div>
</div>
{% endblock %}
