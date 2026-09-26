<?php

namespace App\Http\Middleware;

use App\Services\LocaleService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Middleware;
use Inertia\OnceProp;

class HandleInertiaRequests extends Middleware
{
    /**
     * @param  LocaleService  $localeService  Loads the translation dictionary.
     */
    public function __construct(
        protected LocaleService $localeService
    ) {}

    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     *
     * @param  Request  $request  The current request.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @param  Request  $request  The current request, used for the user and flashed session data.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return array_merge(parent::share($request), [
            'auth' => [
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'email_verified_at' => $user->email_verified_at,
                    'created_at' => $user->created_at,
                ] : null,
            ],
            'flash' => [
                'status' => fn () => $request->session()->get('status'),
                // For failures that belong to no form field, such as deleting
                // a record that is still in use.
                'error' => fn () => $request->session()->get('error'),
            ],
            'locale' => fn () => app()->getLocale(),
        ]);
    }

    /**
     * Define the props that are shared once and remembered across navigations.
     *
     * The dictionary is only sent on the first load: the client keeps it and
     * reports it back. Its key carries the locale, so switching language makes
     * the server send the new one, and switching back reuses the kept copy.
     *
     * @param  Request  $request  The current request.
     *
     * @return array<string, OnceProp>
     */
    public function shareOnce(Request $request): array
    {
        $locale = app()->getLocale();

        return [
            'translations' => Inertia::once(fn () => $this->localeService->dictionary($locale))
                ->as("translations.{$locale}"),
        ];
    }
}
