<?php

namespace App\Entity\Enum;

enum Role: string
{
    case ADMIN = 'admin';
    case USER = 'user';
}
