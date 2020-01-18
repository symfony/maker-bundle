Hello,

To reset your password, please visit {{ url('app_reset_password', {tokenAndSelector: token.asString}) }}
This link will expire in {{ constant('LIFETIME_HOURS', token) }} hours.

Regards,
the Team.
