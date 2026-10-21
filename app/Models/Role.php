<?php

namespace App\Models;

use Database\Factories\RoleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

#[Fillable(['name'])]
class Role extends Model
{
    /** @var array<int, string> */
    public const array DOCUMENTED = [
        'super_admin',
        'provost',
        'hall_office_staff',
        'house_tutor',
        'club_admin',
        'student',
    ];

    /** @use HasFactory<RoleFactory> */
    use HasFactory;

    /** @return BelongsToMany<User, $this> */
    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class);
    }
}
