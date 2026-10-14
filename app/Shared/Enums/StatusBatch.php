<?php

namespace App\Shared\Enums;

enum StatusBatch: string
{
    case Planned = 'Planned';
    case Ongoing = 'Ongoing';
    case Completed = 'Completed';
    case Cancelled = 'Cancelled';
}
