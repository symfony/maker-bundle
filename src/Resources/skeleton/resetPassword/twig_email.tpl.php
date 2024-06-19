<h1>Hi!</h1>

<p>To reset your password, please visit the following link</p>

<a href="{{ url('app_reset_password', {token: resetToken.token}) }}">{{ url('app_reset_password', {token: resetToken.token}) }}</a>

<p>This link will expire in {{ resetToken.expirationMessageKey|trans(resetToken.expirationMessageData, 'ResetPasswordBundle') }}.</p>

<p>Cheers!</p>
