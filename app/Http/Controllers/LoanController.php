<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreLoanRequest;
use App\Http\Requests\UpdateLoanRequest;
use App\Models\Loan;
use App\Services\LoanService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class LoanController extends Controller
{
    public function __construct(private LoanService $service)
    {
    }

    public function index(Request $request): Response
    {
        $validated = $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $search = isset($validated['search']) ? trim(strip_tags($validated['search'])) : null;

        return Inertia::render('loans/index', [
            'loans' => $this->service->index($search),
            'filters' => ['search' => $search ?? ''],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('loans/form', $this->service->formOptions());
    }

    public function store(StoreLoanRequest $request): RedirectResponse
    {
        $this->service->create($request->validated());

        return redirect()->route('loans.index')->with('success', 'Loan recorded.');
    }

    public function show(Loan $loan): Response
    {
        return Inertia::render('loans/show', [
            'loan' => $this->service->show($loan),
        ]);
    }

    public function edit(Loan $loan): Response
    {
        return Inertia::render('loans/form', array_merge(
            ['loan' => $loan],
            $this->service->formOptions($loan),
        ));
    }

    public function update(UpdateLoanRequest $request, Loan $loan): RedirectResponse
    {
        $this->service->update($loan, $request->validated());

        return redirect()->route('loans.index')->with('success', 'Loan updated.');
    }

    public function destroy(Loan $loan): RedirectResponse
    {
        $this->service->delete($loan);

        return redirect()->route('loans.index')->with('success', 'Loan deleted.');
    }
}
