<?php

namespace App\Http\Controllers\Member;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;

class PopupDismissController extends Controller
{
    public function __invoke(): RedirectResponse
    {
        session(['popup_dismissed' => true]);

        return redirect()->back();
    }
}
