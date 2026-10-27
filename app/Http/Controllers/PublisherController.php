<?php

namespace App\Http\Controllers;

use App\Http\Requests\StorePublisherRequest;
use App\Http\Requests\UpdatePublisherRequest;
use App\Models\Publisher;
use App\Services\PublisherService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class PublisherController extends Controller
{
    public function __construct(private PublisherService $service)
    {
    }

    public function index(): Response
    {
        return Inertia::render('publishers/index', [
            'publishers' => $this->service->index(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('publishers/form');
    }

    public function store(StorePublisherRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('publishers.index')->with('success', 'Publisher added.');
    }

    public function show(Publisher $publisher): Response
    {
        return Inertia::render('publishers/show', [
            'publisher' => $this->service->show($publisher),
        ]);
    }

    public function edit(Publisher $publisher): Response
    {
        return Inertia::render('publishers/form', ['publisher' => $publisher]);
    }

    public function update(UpdatePublisherRequest $request, Publisher $publisher): RedirectResponse
    {
        $this->service->update($publisher, $request->validated());

        return redirect()->route('publishers.index')->with('success', 'Publisher updated.');
    }

    public function destroy(Publisher $publisher): RedirectResponse
    {
        $this->service->delete($publisher);

        return redirect()->route('publishers.index')->with('success', 'Publisher deleted.');
    }
}
