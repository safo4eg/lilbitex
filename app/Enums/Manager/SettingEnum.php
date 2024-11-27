<?php

/**
 * Enum типов изменений настроек через Manager чат
 */

namespace App\Enums\Manager;

enum SettingEnum: int
{
    case EXCHANGER_FEE = 1; // изменение комиссии
    case MIN_AMOUNT = 2; // изменение минимальной суммы
    case MAX_AMOUNT = 3; // изменений максимальной суммы
}
