<?php

namespace App\Enums\Manager;

enum UserMenuEnum: int
{
    case PERSONAL_DISCOUNT = 1;
    case BAN = 2;
    case UNBAN = 3;
}
