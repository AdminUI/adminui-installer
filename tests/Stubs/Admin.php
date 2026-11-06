<?php

namespace AdminUI\AdminUI\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Stand-in for AdminUI's Admin model, which isn't available under Testbench. Only what the registration guard
 * touches: a model on an `admins` table.
 */
class Admin extends Model
{
    public $timestamps = false;

    protected $guarded = [];
}
