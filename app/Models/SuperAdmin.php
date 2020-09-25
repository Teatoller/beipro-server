<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use OwenIt\Auditing\Contracts\Auditable;

class SuperAdmin extends Model implements Auditable
{
    use \OwenIt\Auditing\Auditable;
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'firstname',
        'lastname',
        'email',
        'role',
    ];

    /**
     * Get the company under beipro.
     */
    public function companies()
    {
        return $this->hasMany('App\Company');
    }

    /**
     * Get the Admin that manages the oil company admins.
     */
    public function admins()
    {
        return $this->hasMany('App\Admin');
    }

    /**
     * Get the front offices for the oil company.
     */
    public function frontOffices()
    {
        return $this->hasMany('App\FrontOffice');
    }
}
