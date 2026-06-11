<?php

namespace App\Http\Controllers;

use App\Enums\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class LocaleController extends Controller
{
    public function __invoke(Request $request, Locale $locale): RedirectResponse
    {
        $request->user()->update(['locale' => $locale]);

        return back();
    }
}
