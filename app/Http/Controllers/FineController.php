<?php

namespace App\Http\Controllers;

use App\Http\Requests\UpdateFineRequest;
use App\Models\Fine;
use App\Services\FineService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FineController extends Controller
{
    public function __construct(private FineService $service)
    {
    }

    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $search = isset($validated['search']) ? trim(strip_tags($validated['search'])) : null;

        return Inertia::render('fines/index', [
            'fines' => $this->service->index($search),
            'filters' => ['search' => $search ?? ''],
        ]);
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
        ]);
    }

    public function update(UpdateFineRequest $request, Fine $fine): RedirectResponse
    {
        $this->service->update($fine, $request->validated());

        return redirect()->route('fines.index')->with('success', 'Fine updated.');
    }
}
