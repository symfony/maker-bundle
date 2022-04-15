symfonycasts_reset_password:
    request_password_repository: App\Repository\ResetPasswordRequestRepository
    throttle_limit: 900 # 15 minutes
    lifetime: 3600 # 1 hour
