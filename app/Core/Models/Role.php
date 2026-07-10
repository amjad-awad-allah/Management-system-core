<?php

namespace App\Core\Models;

use Spatie\Permission\Models\Role as SpatieRole;
use Illuminate\Database\Eloquent\Concerns\HasUlids;

class Role extends SpatieRole
{
    use HasUlids;
}
