<?php

namespace App\Shared\Enums;

enum StatusDaftar: string
{
    case Registered = 'Registered';
    case Validated = 'Validated';
    case Ongoing = 'Ongoing';
    case Completed = 'Completed';
    case Dropped = 'Dropped';
}
