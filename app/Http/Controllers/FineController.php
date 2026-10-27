<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreFineRequest;
use App\Http\Requests\UpdateFineRequest;
use App\Models\Fine;
use App\Services\FineService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class FineController extends Controller
{
    public function __construct(private FineService $service)
    {
    }

    public function index(): Response
    {
        return Inertia::render('fines/index', [
            'fines' => $this->service->index(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('fines/form', [
            'loans' => $this->service->availableLoans(),
        ]);
    }

    public function store(StoreFineRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('fines.index')->with('success', 'Fine added.');
    }

    public function show(Fine $fine): Response
    {
        return Inertia::render('fines/show', [
            'fine' => $this->service->show($fine),
        ]);
    }

    public function edit(Fine $fine): Response
    {
        return Inertia::render('fines/form', [
            'fine' => $fine,
            'loans' => $this->service->availableLoans($fine),
        ]);
    }

    public function update(UpdateFineRequest $request, Fine $fine): RedirectResponse
    {
        $this->service->update($fine, $request->validated());

        return redirect()->route('fines.index')->with('success', 'Fine updated.');
    }

    public function destroy(Fine $fine): RedirectResponse
    {
        $this->service->delete($fine);

        return redirect()->route('fines.index')->with('success', 'Fine deleted.');
    }
}
