<h1>Hi!</h1>

<p>
    To reset your password, please visit
    <a href="{{ url('app_reset_password', {token: resetToken.token}) }}">here</a>
    This link will expire in {{ tokenLifetime|date('g') }} hour(s)..
</p>

<p>
    Cheers!
</p>