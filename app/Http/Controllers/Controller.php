<?php

namespace App\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;

abstract class Controller
{
    /**
     * Gives controllers $this->authorize(), which every admin action uses to
     * run the matching policy check.
     */
    use AuthorizesRequests;
}
