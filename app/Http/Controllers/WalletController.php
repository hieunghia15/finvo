<?php

namespace App\Http\Controllers;

use App\Http\Requests\Wallet\IndexWalletRequest;
use App\Services\WalletService;
use Inertia\Inertia;
use Inertia\Response;

class WalletController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @param  WalletService  $walletService  Reads and writes the user's wallets.
     */
    public function __construct(
        protected WalletService $walletService
    ) {}

    /**
     * Display the current user's wallets.
     *
     * Every mutation redirects back to this screen, which keeps the filter
     * in the query string intact.
     *
     * @param  IndexWalletRequest  $request  The validated list filters.
     */
    public function index(IndexWalletRequest $request): Response
    {
        $filters = $this->walletService->normalizeFilters($request->validated());
        $wallets = $this->walletService->listFor($request->user(), $filters);
        $currencies = $this->walletService->currencies();

        return Inertia::render('Wallets/Index', [
            'wallets' => $wallets,
            'currencies' => $currencies,
            'filters' => $filters,
        ]);
    }
}
