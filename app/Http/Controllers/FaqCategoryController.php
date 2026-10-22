<?php

namespace App\Http\Controllers;

use App\Models\Faq;
use App\Models\FaqCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class FaqCategoryController extends Controller
{
    /**
     * Resolve the current tenant ID
     */
    protected function getTenantId()
    {
        return auth()->user()->company_id ?? auth()->user()->tenant_id;
    }

    /**
     * Display a listing of FAQ categories with search, status, and date filters.
     */
    public function index(Request $request)
    {
        $tenantId = $this->getTenantId();

        $query = FaqCategory::query()->with('creator');
        if (!is_null($tenantId)) {
            $query->where('tenant_id', $tenantId);
        }

        if ($search = $request->input('search')) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        if ($request->filled('status')) {
            $statusVal = $request->input('status') === 'active' ? 1 : ($request->input('status') === 'inactive' ? 0 : null);
            if (!is_null($statusVal)) {
                $query->where('status', $statusVal);
            }
        }

        if ($createdDate = $request->input('created_date')) {
            $query->whereDate('created_at', $createdDate);
        }

        $categories = $query->orderBy('id', 'desc')->paginate(15)->withQueryString();

        // Calculate count of FAQs using this category for each category item
        foreach ($categories as $cat) {
            $faqCountQuery = Faq::where('category', $cat->name);
            if (!is_null($tenantId)) {
                $faqCountQuery->where('tenant_id', $tenantId);
            }
            $cat->faqs_count = $faqCountQuery->count();
        }

        return view('pages.faq_categories.index', compact('categories', 'request'));
    }

    /**
     * Store a newly created category in storage.
     */
    public function store(Request $request)
    {
        $tenantId = $this->getTenantId();

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'status' => 'nullable|boolean',
        ]);

        $name = trim($validated['name']);

        // Check uniqueness within the tenant
        $existsQuery = FaqCategory::where('name', $name);
        if (!is_null($tenantId)) {
            $existsQuery->where('tenant_id', $tenantId);
        }
        if ($existsQuery->exists()) {
            return redirect()->back()->withErrors(['name' => 'This category name already exists.'])->withInput();
        }

        FaqCategory::create([
            'tenant_id' => $tenantId,
            'name' => $name,
            'description' => $validated['description'] ?? null,
            'status' => $request->has('status') ? (bool) $request->status : true,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('faq-categories.index')->with('success', 'FAQ Category created successfully.');
    }

    /**
     * Update the specified category in storage.
     */
    public function update(Request $request, $id)
    {
        $tenantId = $this->getTenantId();

        $query = FaqCategory::query();
        if (!is_null($tenantId)) {
            $query->where('tenant_id', $tenantId);
        }
        $category = $query->findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string|max:500',
            'status' => 'nullable|boolean',
        ]);

        $newName = trim($validated['name']);
        $oldName = $category->name;

        // Check uniqueness excluding current record
        $existsQuery = FaqCategory::where('name', $newName)->where('id', '!=', $category->id);
        if (!is_null($tenantId)) {
            $existsQuery->where('tenant_id', $tenantId);
        }
        if ($existsQuery->exists()) {
            return redirect()->back()->withErrors(['name' => 'Another category with this name already exists.'])->withInput();
        }

        $category->update([
            'name' => $newName,
            'description' => $validated['description'] ?? null,
            'status' => $request->boolean('status'),
        ]);

        // If category name was renamed, update existing FAQs referencing it
        if ($oldName !== $newName) {
            $faqUpdateQuery = Faq::where('category', $oldName);
            if (!is_null($tenantId)) {
                $faqUpdateQuery->where('tenant_id', $tenantId);
            }
            $faqUpdateQuery->update(['category' => $newName]);
        }

        return redirect()->route('faq-categories.index')->with('success', 'FAQ Category updated successfully.');
    }

    /**
     * Toggle active/inactive status of a category.
     */
    public function toggleStatus($id)
    {
        $tenantId = $this->getTenantId();

        $query = FaqCategory::query();
        if (!is_null($tenantId)) {
            $query->where('tenant_id', $tenantId);
        }
        $category = $query->findOrFail($id);

        $newStatus = ! (bool) $category->status;
        $category->status = $newStatus;
        $category->save();

        $statusLabel = $newStatus ? 'activated' : 'deactivated';
        return redirect()->route('faq-categories.index')->with('success', "Category '{$category->name}' has been {$statusLabel}.");
    }

    /**
     * Remove the specified category from storage.
     */
    public function destroy($id)
    {
        $tenantId = $this->getTenantId();

        $query = FaqCategory::query();
        if (!is_null($tenantId)) {
            $query->where('tenant_id', $tenantId);
        }
        $category = $query->findOrFail($id);

        // Check if category is used in any FAQ
        $faqCountQuery = Faq::where('category', $category->name);
        if (!is_null($tenantId)) {
            $faqCountQuery->where('tenant_id', $tenantId);
        }
        $usedFaqsCount = $faqCountQuery->count();

        if ($usedFaqsCount > 0) {
            return redirect()->route('faq-categories.index')->with('error', "Cannot delete category '{$category->name}' because it is currently assigned to {$usedFaqsCount} FAQ(s). Please reassign or remove the category from those FAQs first.");
        }

        $category->delete();

        return redirect()->route('faq-categories.index')->with('success', 'FAQ Category deleted successfully.');
    }
}
