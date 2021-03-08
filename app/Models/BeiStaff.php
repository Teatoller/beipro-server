<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BeiStaff extends Model
{
    use HasFactory;

    protected $fillable = [
        'firstname',
        'lastname',
        'staff_id',
        'email',
        'role',
        'location',
    ];

    /**
     * Get the beipro super admin that manages the beipro staff.
     */
    public function superAdmins()
    {
        return $this->belongsTo('App\SuperAdmin');
    }
}
