<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use OpenApi\Attributes as OA;

class ContactApiController extends Controller
{
    /**
     * Get Contact details for edit modal.
     * Endpoint: GET /api/contacts/{id}
     */
    #[
        OA\Get(
            path: "/contacts/{id}",
            summary: "Get Contact Details",
            description: "Retrieve contact details, address, and associated tags for edit modal.",
            security: [["bearerAuth" => []]],
            tags: ["Contacts"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, description: "Contact ID", schema: new OA\Schema(type: "integer"))
            ]
        ),
        OA\Response(response: 200, description: "Contact details"),
        OA\Response(response: 404, description: "Contact not found")
    ]
    public function show(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;

        $contact = Contact::where('tenant_id', $companyId)
            ->with('tags')
            ->find($id);

        if (!$contact) {
            return response()->json([
                'status'  => false,
                'message' => 'Contact not found.',
            ], 404);
        }

        $phoneNumber = $contact->phone_number;
        if ($phoneNumber && str_starts_with($phoneNumber, 'web:')) {
            $phoneNumber = null;
        }

        return response()->json([
            'status' => true,
            'data'   => [
                'id'                    => $contact->id,
                'custom_name'           => $contact->custom_name,
                'whatsapp_profile_name' => str_starts_with($contact->whatsapp_profile_name ?? '', 'web:') ? null : $contact->whatsapp_profile_name,
                'phone_number'          => $phoneNumber,
                'email'                 => $contact->email,
                'country'               => $contact->country,
                'state'                 => $contact->state,
                'city'                  => $contact->city,
                'pincode'               => $contact->pincode,
                'tags'                  => $contact->tags->map(function ($t) {
                    return [
                        'id'    => $t->id,
                        'name'  => $t->tag_name,
                    ];
                }),
            ],
        ]);
    }

    /**
     * Update Contact information.
     * Endpoint: POST /api/contacts/{id} or PUT /api/contacts/{id}
     */
    #[
        OA\Post(
            path: "/contacts/{id}",
            summary: "Update Contact Information",
            description: "Update customer name, phone number, email and location details.",
            security: [["bearerAuth" => []]],
            tags: ["Contacts"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, description: "Contact ID", schema: new OA\Schema(type: "integer"))
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "name", type: "string", example: "Beatrix Kiddo"),
                        new OA\Property(property: "phone_number", type: "string", example: "9876543210"),
                        new OA\Property(property: "email", type: "string", example: "beatrix@example.com"),
                        new OA\Property(property: "country", type: "string", example: "India"),
                        new OA\Property(property: "state", type: "string", example: "Maharashtra"),
                        new OA\Property(property: "city", type: "string", example: "Mumbai"),
                        new OA\Property(property: "pincode", type: "string", example: "400001")
                    ]
                )
            )
        ),
        OA\Response(response: 200, description: "Contact updated successfully"),
        OA\Response(response: 422, description: "Validation error")
    ]
    public function update(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;

        $contact = Contact::where('tenant_id', $companyId)->find($id);

        if (!$contact) {
            return response()->json([
                'status'  => false,
                'message' => 'Contact not found.',
            ], 404);
        }

        $isWidgetContact = str_starts_with($contact->phone_number ?? '', 'web:')
            || str_starts_with($contact->whatsapp_profile_name ?? '', 'web:');

        $validator = Validator::make($request->all(), [
            'name'         => 'nullable|string|max:255',
            'custom_name'  => 'nullable|string|max:255',
            'email'        => 'nullable|email|max:255',
            'phone_number' => $isWidgetContact ? 'nullable|string|max:255' : 'required|string|max:255',
            'country'      => 'nullable|string|max:255',
            'state'        => 'nullable|string|max:255',
            'city'         => 'nullable|string|max:255',
            'pincode'      => 'nullable|string|max:20',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $customName = $request->filled('name') ? $request->name : $request->custom_name;
        $phoneNumber = $request->phone_number;
        $whatsappProfileName = $contact->whatsapp_profile_name;

        // If this is a widget contact, preserve the synthetic session ID
        if ($isWidgetContact) {
            $syntheticId = str_starts_with($contact->whatsapp_profile_name ?? '', 'web:')
                ? $contact->whatsapp_profile_name
                : $contact->phone_number;

            $whatsappProfileName = $syntheticId;

            if (empty($phoneNumber)) {
                $phoneNumber = $syntheticId;
            }
        }

        if (!empty($phoneNumber) && !str_starts_with($phoneNumber, 'web:')) {
            $phoneNumber = preg_replace('/\D/', '', $phoneNumber);
        }

        $contact->update([
            'custom_name'           => $customName,
            'whatsapp_profile_name' => $whatsappProfileName,
            'phone_number'          => $phoneNumber,
            'email'                 => $request->email,
            'country'               => $request->country,
            'state'                 => $request->state,
            'city'                  => $request->city,
            'pincode'               => $request->pincode,
        ]);

        $cleanPhoneNumber = $contact->phone_number;
        if ($cleanPhoneNumber && str_starts_with($cleanPhoneNumber, 'web:')) {
            $cleanPhoneNumber = null;
        }

        return response()->json([
            'status'  => true,
            'message' => 'Contact updated successfully.',
            'data'    => [
                'id'           => $contact->id,
                'name'         => $contact->custom_name ?: ($contact->whatsapp_profile_name ?: ($cleanPhoneNumber ?: 'Website Visitor')),
                'custom_name'  => $contact->custom_name,
                'phone_number' => $cleanPhoneNumber,
                'email'        => $contact->email,
                'country'      => $contact->country,
                'state'        => $contact->state,
                'city'         => $contact->city,
                'pincode'      => $contact->pincode,
            ],
        ], 200);
    }

    /**
     * Get tags:
     * - If contact_id query param is provided: GET /api/tags?contact_id=14 -> returns only that contact's tags
     * - Otherwise returns all available company tags.
     * Endpoint: GET /api/tags
     */
    #[
        OA\Get(
            path: "/tags",
            summary: "Get All Company Tags or Filter by Contact",
            description: "Retrieve available active tags or specific contact's tags via contact_id param.",
            security: [["bearerAuth" => []]],
            tags: ["Tags"],
            parameters: [
                new OA\Parameter(name: "contact_id", in: "query", required: false, description: "Contact ID to filter tags", schema: new OA\Schema(type: "integer"))
            ]
        ),
        OA\Response(response: 200, description: "List of tags")
    ]
    public function getTags(Request $request): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;

        // If contact_id is provided, return only that contact's tags
        if ($request->filled('contact_id')) {
            $contact = Contact::where('tenant_id', $companyId)->find($request->contact_id);

            if (!$contact) {
                return response()->json([
                    'status'  => false,
                    'message' => 'Contact not found.',
                ], 404);
            }

            return response()->json([
                'status' => true,
                'data'   => $contact->tags->map(function ($t) {
                    return [
                        'id'       => $t->id,
                        'tag_id'   => $t->tag_id,
                        'tag_name' => $t->tag_name,
                    ];
                }),
            ]);
        }

        $tags = Tag::where('tenant_id', $companyId)
            ->where('status', 1)
            ->orderBy('tag_name')
            ->get(['id', 'tag_id', 'tag_name', 'description']);

        return response()->json([
            'status' => true,
            'data'   => $tags->map(function ($t) {
                return [
                    'id'       => $t->id,
                    'tag_id'   => $t->tag_id,
                    'tag_name' => $t->tag_name,
                ];
            }),
        ]);
    }

    /**
     * Get specific contact's tags directly.
     * Endpoint: GET /api/contacts/{id}/tags
     */
    #[
        OA\Get(
            path: "/contacts/{id}/tags",
            summary: "Get Contact's Associated Tags",
            description: "Directly retrieve all tags associated with a specific contact.",
            security: [["bearerAuth" => []]],
            tags: ["Tags"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, description: "Contact ID", schema: new OA\Schema(type: "integer"))
            ]
        ),
        OA\Response(response: 200, description: "Contact's tags list"),
        OA\Response(response: 404, description: "Contact not found")
    ]
    public function getContactTags(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;

        $contact = Contact::where('tenant_id', $companyId)->find($id);

        if (!$contact) {
            return response()->json([
                'status'  => false,
                'message' => 'Contact not found.',
            ], 404);
        }

        return response()->json([
            'status' => true,
            'data'   => $contact->tags->map(function ($t) {
                return [
                    'id'       => $t->id,
                    'tag_id'   => $t->tag_id,
                    'tag_name' => $t->tag_name,
                ];
            }),
        ]);
    }

    /**
     * Update/Add tags to a contact (accepts tag names or IDs, creates tag if not exists).
     * Endpoint: POST /api/contacts/{id}/tags
     * Body: { "tags": ["VIP", "Lead", 2] }
     */
    #[
        OA\Post(
            path: "/contacts/{id}/tags",
            summary: "Add / Update Tags for Contact",
            description: "Sync tags for a contact. Accepts tag IDs or new tag names (auto-creates if name does not exist).",
            security: [["bearerAuth" => []]],
            tags: ["Tags"],
            parameters: [
                new OA\Parameter(name: "id", in: "path", required: true, description: "Contact ID", schema: new OA\Schema(type: "integer"))
            ],
            requestBody: new OA\RequestBody(
                required: true,
                content: new OA\JsonContent(
                    required: ["tags"],
                    properties: [
                        new OA\Property(
                            property: "tags",
                            type: "array",
                            items: new OA\Items(type: "string"),
                            example: ["VIP Customer", "Urgent Lead"]
                        )
                    ]
                )
            )
        ),
        OA\Response(response: 200, description: "Tags updated successfully"),
        OA\Response(response: 422, description: "Validation error")
    ]
    public function updateTags(Request $request, $id): JsonResponse
    {
        $user = $request->user();
        $companyId = $user->company_id;

        $contact = Contact::where('tenant_id', $companyId)->find($id);

        if (!$contact) {
            return response()->json([
                'status'  => false,
                'message' => 'Contact not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'tags'   => 'present|array',
            'tags.*' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $tagIdsToSync = [];

        foreach ($request->tags as $tagInput) {
            if (is_numeric($tagInput)) {
                $existingTag = Tag::where('tenant_id', $companyId)->find($tagInput);
                if ($existingTag) {
                    $tagIdsToSync[] = $existingTag->tag_id;
                }
            } else {
                $tagName = trim($tagInput);
                if (empty($tagName)) {
                    continue;
                }

                $existingTagByName = Tag::where('tenant_id', $companyId)
                    ->where('tag_name', 'like', $tagName)
                    ->first();

                if ($existingTagByName) {
                    $tagIdsToSync[] = $existingTagByName->tag_id;
                } else {
                    // Create new tag for this company
                    $lastTag = Tag::orderBy('id', 'desc')->first();
                    $nextId = $lastTag ? $lastTag->id + 1 : 1;
                    $newTagIdStr = '#TAG-' . str_pad($nextId, 3, '0', STR_PAD_LEFT);

                    $newTag = Tag::create([
                        'tenant_id'   => $companyId,
                        'tag_id'      => $newTagIdStr,
                        'tag_name'    => $tagName,
                        'status'      => 1,
                        'created_by'  => $user->id,
                    ]);

                    $tagIdsToSync[] = $newTag->tag_id;
                }
            }
        }

        // Sync tags with contact
        $contact->tags()->sync($tagIdsToSync);

        // Fetch refreshed tags
        $updatedTags = $contact->fresh(['tags'])->tags->map(function ($t) {
            return [
                'id'       => $t->id,
                'tag_id'   => $t->tag_id,
                'tag_name' => $t->tag_name,
            ];
        });

        return response()->json([
            'status'  => true,
            'message' => 'Tags updated successfully.',
            'data'    => [
                'contact_id' => $contact->id,
                'tags'       => $updatedTags,
            ],
        ], 200);
    }
}

