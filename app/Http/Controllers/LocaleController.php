<?php

namespace App\Http\Controllers;

use App\Http\Requests\Locale\UpdateLocaleRequest;
use App\Services\LocaleService;
use Illuminate\Http\RedirectResponse;

class LocaleController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  LocaleService  $localeService  Builds the language cookie.
     */
    public function __construct(
        protected LocaleService $localeService
    ) {}

    /**
     * Remember the chosen language and reload the page it was chosen on.
     *
     * No flash message: the page coming back in the new language is the feedback.
     *
     * @param  UpdateLocaleRequest  $request  The validated language choice.
     */
    public function update(UpdateLocaleRequest $request): RedirectResponse
    {
        $cookie = $this->localeService->rememberCookie($request->validated());

        return back()->withCookie($cookie);
    }
}
