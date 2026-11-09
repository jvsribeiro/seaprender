<?php

namespace App\Enums;

enum EnrollmentStatus: string
{
    case Active = 'active';
    case Pending = 'pending';
    case Rejected = 'rejected';
    case Removed = 'removed';
}
