<?php

namespace App\Http\Controllers;

use App\Http\Requests\Router\StoreRouterRequest;
use App\Http\Requests\Router\UpdateRouterRequest;
use App\Models\Router;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class RouterController extends Controller
{
    /**
     * Display a listing of routers.
     */
    public function index(): View
    {
        $routers = Router::latest()->get();

        return view('routers.index', compact('routers'));
    }

    /**
     * Show the form for creating a new router.
     */
    public function create(): View
    {
        return view('routers.create');
    }

    /**
     * Store a newly created router in storage.
     */
    public function store(StoreRouterRequest $request): RedirectResponse
    {
        Router::create($request->validated());

        return redirect()->route('routers.index')
            ->with('success', 'Router created successfully.');
    }

    /**
     * Display the specified router.
     */
    public function show(Router $router): View
    {
        return view('routers.show', compact('router'));
    }

    /**
     * Show the form for editing the specified router.
     */
    public function edit(Router $router): View
    {
        return view('routers.edit', compact('router'));
    }

    /**
     * Update the specified router in storage.
     */
    public function update(UpdateRouterRequest $request, Router $router): RedirectResponse
    {
        $router->update($request->validated());

        return redirect()->route('routers.show', $router)
            ->with('success', 'Router updated successfully.');
    }

    /**
     * Remove the specified router from storage.
     */
    public function destroy(Router $router): RedirectResponse
    {
        $router->delete();

        return redirect()->route('routers.index')
            ->with('success', 'Router deleted successfully.');
    }

    /**
     * Display router sessions.
     */
    public function sessions(Router $router): View
    {
        return view('routers.sessions', compact('router'));
    }

    /**
     * Display router queues.
     */
    public function queues(Router $router): View
    {
        return view('routers.queues', compact('router'));
    }

    /**
     * Display router profiles.
     */
    public function profiles(Router $router): View
    {
        return view('routers.profiles', compact('router'));
    }

    /**
     * Display router interfaces.
     */
    public function interfaces(Router $router): View
    {
        return view('routers.interfaces', compact('router'));
    }

    /**
     * Display router IP pools.
     */
    public function ipPools(Router $router): View
    {
        return view('routers.ip-pools', compact('router'));
    }

    /**
     * Display router logs.
     */
    public function logs(Router $router): View
    {
        return view('routers.logs', compact('router'));
    }
}
