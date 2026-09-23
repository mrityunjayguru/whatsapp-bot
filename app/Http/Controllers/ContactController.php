<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;

class ContactController extends Controller
{
    public function index(Request $request): View
    {
        $companyId = auth()->user()->company_id;

        $query = Contact::where('tenant_id', $companyId)
            ->withCount(['conversations', 'messages'])
            ->with(['tags', 'conversations' => function($q) {
                $q->latest()->limit(1);
            }])
            ->latest();

        if (auth()->id() !== 1) {
            $employee = \App\Models\Employee::where('email', auth()->user()->email)->first();
            if ($employee && $employee->role !== 'ADMIN') {
                $query->whereHas('conversations', function($q) use ($employee) {
                    $q->where('assigned_tenant_user_id', $employee->id);
                });
            } elseif (!$employee) {
                $query->whereHas('conversations', function($q) {
                    $q->where('assigned_tenant_user_id', auth()->id());
                });
            }
        }

        // 1. Search
        if ($search = $request->input('search')) {
            $query->where(function($q) use ($search) {
                $q->where('custom_name', 'like', "%{$search}%")
                  ->orWhere('whatsapp_profile_name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        // 2. Tags
        if ($tags = $request->input('tags')) {
            $query->whereHas('tags', function($q) use ($tags) {
                $q->whereIn('tags.id', (array) $tags);
            });
        }

        // 3. Created Date
        if ($createdDate = $request->input('created_date')) {
            $query->whereDate('created_at', $createdDate);
        }

        // 4. Last Conversation Date
        if ($lastConversation = $request->input('last_conversation')) {
            $query->whereHas('conversations', function($q) use ($lastConversation) {
                $q->whereDate('created_at', $lastConversation);
            });
        }

        // 5. Has Email
        if ($request->has('has_email') && filter_var($request->input('has_email'), FILTER_VALIDATE_BOOLEAN)) {
            $query->whereNotNull('email')->where('email', '!=', '');
        }

        // 6. Has Tags
        if ($request->has('has_tags') && filter_var($request->input('has_tags'), FILTER_VALIDATE_BOOLEAN)) {
            $query->has('tags');
        }

        $contacts = $query->paginate(15)->withQueryString();
        $allTags = Tag::where('tenant_id', $companyId)->orderBy('tag_name')->get();

        return view('contacts.index', compact('contacts', 'allTags', 'request'));
    }

    public function show(Request $request, $id): View
    {
        $companyId = auth()->user()->company_id;
        $contactQuery = Contact::where('tenant_id', $companyId)
            ->with(['tags', 'conversations' => function($q) {
            $q->with('assignedUser')->latest();
        }])->withCount(['conversations', 'messages']);

        if (auth()->id() !== 1) {
            $employee = \App\Models\Employee::where('email', auth()->user()->email)->first();
            if ($employee && $employee->role !== 'ADMIN') {
                $contactQuery->whereHas('conversations', function($q) use ($employee) {
                    $q->where('assigned_tenant_user_id', $employee->id);
                });
            } elseif (!$employee) {
                $contactQuery->whereHas('conversations', function($q) {
                    $q->where('assigned_tenant_user_id', auth()->id());
                });
            }
        }

        $contact = $contactQuery->findOrFail($id);
        
        $allTags = Tag::where('tenant_id', $companyId)->where('status', 1)->orderBy('tag_name')->get();
        $countries = \App\Models\Country::all();
        return view('contacts.show', compact('contact', 'allTags', 'countries'));
    }

    public function update(Request $request, $id): RedirectResponse
    {
        $contact = Contact::where('tenant_id', auth()->user()->company_id)->findOrFail($id);

        $request->validate([
            'custom_name' => 'nullable|string|max:255',
            'whatsapp_profile_name' => 'nullable|string|max:255',
            'phone_number' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
            'whatsapp_phone_number_id' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'state' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'pincode' => 'nullable|string|max:20',
        ]);

        $contact->update([
            'custom_name' => $request->custom_name,
            'whatsapp_profile_name' => $request->whatsapp_profile_name,
            'phone_number' => $request->phone_number,
            'email' => $request->email,
            'whatsapp_phone_number_id' => $request->whatsapp_phone_number_id,
            'country' => $request->country,
            'state' => $request->state,
            'city' => $request->city,
            'pincode' => $request->pincode,
        ]);

        return redirect()->back()->with('success', 'Contact updated successfully.');
    }

    public function updateTags(Request $request, $id): RedirectResponse
    {
        $companyId = auth()->user()->company_id;
        $contact = Contact::where('tenant_id', $companyId)->findOrFail($id);
        $tagIdsToSync = [];

        if ($request->has('tags')) {
            foreach ($request->tags as $tagInput) {
                // If it's numeric, it's an existing tag ID
                if (is_numeric($tagInput)) {
                    $existingTag = Tag::where('tenant_id', $companyId)->find($tagInput);
                    if ($existingTag) {
                        $tagIdsToSync[] = $existingTag->tag_id;
                    }
                } else {
                    $existingTagByName = Tag::where('tenant_id', $companyId)->where('tag_name', 'like', $tagInput)->first();
                    if ($existingTagByName) {
                        $tagIdsToSync[] = $existingTagByName->tag_id;
                    } else {
                        $lastTag = Tag::orderBy('id', 'desc')->first();
                        $nextId = $lastTag ? $lastTag->id + 1 : 1;
                        $newTagIdStr = '#TAG-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

                        $newTag = Tag::create([
                            'tag_name' => $tagInput,
                            'tag_id' => $newTagIdStr,
                            'status' => 1,
                            'created_by' => auth()->id() ?? 1,
                            'tenant_id' => $companyId
                        ]);
                        $tagIdsToSync[] = $newTag->tag_id;
                    }
                }
            }
        }

        $tagIdsToSync = array_unique($tagIdsToSync);
        $contact->tags()->sync($tagIdsToSync);

        return redirect()->back()->with('success', 'Tags updated successfully.');
    }
}
