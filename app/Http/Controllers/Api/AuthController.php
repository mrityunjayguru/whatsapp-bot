<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Company;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use OpenApi\Attributes as OA;

class AuthController extends Controller
{
    /**
     * Verify Company by Company Code / Company ID.
     */
    #[
        OA\Post(
            path: "/verify-company",
            summary: "Verify Company Code",
            description: "Check if a company code/ID is registered and active before proceeding to login.",
            tags: ["Authentication"]
        ),
        OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["company_code"],
                properties: [
                    new OA\Property(property: "company_code", type: "string", example: "DES9166", description: "Company ID / Code entered by user")
                ]
            )
        ),
        OA\Response(
            response: 200,
            description: "Company is verified and active",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "status", type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string", example: "Company verified successfully."),
                    new OA\Property(
                        property: "data",
                        type: "object",
                        properties: [
                            new OA\Property(property: "id", type: "integer", example: 1),
                            new OA\Property(property: "company_code", type: "string", example: "DES9166"),
                            new OA\Property(property: "name", type: "string", example: "Acme Corp"),
                            new OA\Property(property: "bot_usage_type", type: "string", example: "ALL")
                        ]
                    )
                ]
            )
        ),
        OA\Response(response: 404, description: "Whoops! Company not registered"),
        OA\Response(response: 403, description: "Company account is inactive"),
        OA\Response(response: 422, description: "Validation error")
    ]
    public function verifyCompany(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'company_code' => 'required|string|trim',
        ]);

        // Support both company_code and company_id param in request
        $inputCode = trim((string) ($request->input('company_code') ?? $request->input('company_id') ?? ''));

        if (empty($inputCode)) {
            return response()->json([
                'status'  => false,
                'message' => 'Company ID is required.',
                'errors'  => [
                    'company_code' => ['The company ID / code field is required.']
                ]
            ], 422);
        }

        $company = Company::where('company_code', $inputCode)->first();

        if (!$company) {
            return response()->json([
                'status'  => false,
                'message' => 'Whoops! Company not registered.',
            ], 404);
        }

        if (!$company->is_active) {
            return response()->json([
                'status'  => false,
                'message' => 'Company account is inactive. Please contact support.',
            ], 403);
        }

        return response()->json([
            'status'  => true,
            'message' => 'Company verified successfully.',
            'data'    => [
                'id'             => $company->id,
                'company_code'   => $company->company_code,
                'name'           => $company->name,
                'contact_email'  => $company->contact_email,
                'bot_usage_type' => $company->bot_usage_type,
            ],
        ]);
    }

    /**
     * Handle login for Company Admin or Employee with explicit role parameter.
     * Requires: role (company|employee), company_code, email, password
     */
    #[
        OA\Post(
            path: "/login",
            summary: "Unified Login for Company and Employee",
            description: "Authenticate user and get Sanctum Bearer token along with user/company/employee profile.",
            tags: ["Authentication"]
        ),
        OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["role", "company_code", "email", "password"],
                properties: [
                    new OA\Property(property: "role", type: "string", enum: ["company", "employee"], example: "employee"),
                    new OA\Property(property: "company_code", type: "string", example: "DES9166"),
                    new OA\Property(property: "email", type: "string", example: "employee@example.com", description: "Email or Employee Code"),
                    new OA\Property(property: "password", type: "string", example: "12345678")
                ]
            )
        ),
        OA\Response(
            response: 200,
            description: "Login successful",
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "status", type: "boolean", example: true),
                    new OA\Property(property: "message", type: "string", example: "Login successful."),
                    new OA\Property(property: "data", type: "object")
                ]
            )
        ),
        OA\Response(response: 401, description: "Invalid credentials"),
        OA\Response(response: 404, description: "Invalid company code"),
        OA\Response(response: 422, description: "Validation error")
    ]
    public function login(Request $request): JsonResponse
    {
        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'role'         => 'required|string|in:company,employee,COMPANY,EMPLOYEE',
            'company_code' => 'required|string',
            'email'        => 'required|string',
            'password'     => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status'  => false,
                'message' => 'Validation error.',
                'errors'  => $validator->errors(),
            ], 422);
        }

        $validated = $validator->validated();
        $requestedRole = strtolower($validated['role']);

        // 1. Verify Company exists and is active using company_code
        $company = Company::where('company_code', $validated['company_code'])->first();

        if (!$company) {
            return response()->json([
                'status'  => false,
                'message' => 'Invalid company code.',
            ], 404);
        }

        if (!$company->is_active) {
            return response()->json([
                'status'  => false,
                'message' => 'Company account is inactive.',
            ], 403);
        }

        $user = null;
        $employee = null;

        if ($requestedRole === 'employee') {
            // Employee Login: accepts email or employee_code
            $employee = Employee::where('tenant_id', $company->id)
                ->where(function ($q) use ($validated) {
                    $q->where('email', $validated['email'])
                      ->orWhere('employee_code', $validated['email']);
                })
                ->first();

            // If no employee found with this email/employee_code
            if (!$employee) {
                return response()->json([
                    'status'  => false,
                    'message' => 'These credentials do not match our records.',
                ], 401);
            }

            if ($employee->status !== 'ACTIVE') {
                return response()->json([
                    'status'  => false,
                    'message' => 'Your employee account is ' . strtolower($employee->status) . '. Please contact your company administrator.',
                ], 403);
            }

            // Find or create associated user for employee
            $user = User::where('company_id', $company->id)
                ->where('email', $employee->email)
                ->first();

            $passwordMatches = false;
            if ($user && Hash::check($validated['password'], $user->password)) {
                $passwordMatches = true;
            } elseif (!empty($employee->password_hash) && Hash::check($validated['password'], $employee->password_hash)) {
                $passwordMatches = true;
            }

            if (!$passwordMatches) {
                return response()->json([
                    'status'  => false,
                    'message' => 'These credentials do not match our records.',
                ], 401);
            }

            // If user record doesn't exist yet, create it so Sanctum token can be issued
            if (!$user) {
                $user = User::create([
                    'name'       => $employee->display_name ?? trim($employee->first_name . ' ' . $employee->last_name),
                    'email'      => $employee->email,
                    'password'   => Hash::make($validated['password']),
                    'company_id' => $company->id,
                ]);
            }

            // Update employee login stats
            $employee->update([
                'last_login_at' => now(),
                'is_online'     => true,
            ]);

            $userType = 'employee';
            $tokenRole = $employee->role; // e.g. EMPLOYEE, MANAGER, ADMIN
        } else {
            // Company Admin Login: must be a company admin, not an employee
            $user = User::where('company_id', $company->id)
                ->where('email', $validated['email'])
                ->first();

            if (!$user || !Hash::check($validated['password'], $user->password)) {
                return response()->json([
                    'status'  => false,
                    'message' => 'These credentials do not match our records.',
                ], 401);
            }

            // Ensure this user is NOT an employee logging in as company
            $isEmployee = Employee::where('tenant_id', $company->id)
                ->where('email', $user->email)
                ->exists();

            if ($isEmployee) {
                return response()->json([
                    'status'  => false,
                    'message' => 'These credentials belong to an employee. Please set role to "employee".',
                ], 403);
            }

            $userType = 'company';
            $tokenRole = 'COMPANY_ADMIN';
        }

        // Generate Sanctum token
        $token = $user->createToken('auth-token', [$tokenRole])->plainTextToken;

        return response()->json([
            'status'  => true,
            'message' => 'Login successful.',
            'data'    => [
                'token'        => $token,
                'user_type'    => $userType,
                'user'         => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $tokenRole,
                ],
                'company'      => [
                    'id'             => $company->id,
                    'company_code'   => $company->company_code,
                    'name'           => $company->name,
                    'contact_email'  => $company->contact_email,
                    'bot_usage_type' => $company->bot_usage_type,
                ],
                'employee'     => $employee ? [
                    'id'            => $employee->id,
                    'employee_code' => $employee->employee_code,
                    'display_name'  => $employee->display_name,
                    'designation'   => $employee->designation,
                    'department'    => $employee->department,
                    'role'          => $employee->role,
                    'status'        => $employee->status,
                ] : null,
            ],
        ], 200);
    }

    /**
     * Get authenticated user details.
     */
    #[
        OA\Get(
            path: "/me",
            summary: "Get Current Logged-in Profile",
            description: "Retrieve details of authenticated user, company and employee.",
            security: [["bearerAuth" => []]],
            tags: ["Authentication"]
        ),
        OA\Response(response: 200, description: "Authenticated user details"),
        OA\Response(response: 401, description: "Unauthenticated")
    ]
    public function me(Request $request): JsonResponse
    {
        $user = $request->user();
        $company = Company::find($user->company_id);
        $employee = Employee::where('tenant_id', $user->company_id)
            ->where('email', $user->email)
            ->first();

        $accountType = $employee ? 'employee' : 'company';
        $role = $employee ? $employee->role : 'COMPANY_ADMIN';

        return response()->json([
            'status' => true,
            'data'   => [
                'user_type' => $accountType,
                'user'         => [
                    'id'    => $user->id,
                    'name'  => $user->name,
                    'email' => $user->email,
                    'role'  => $role,
                ],
                'company'      => $company ? [
                    'id'             => $company->id,
                    'company_code'   => $company->company_code,
                    'name'           => $company->name,
                    'contact_email'  => $company->contact_email,
                    'bot_usage_type' => $company->bot_usage_type,
                ] : null,
                'employee'     => $employee ? [
                    'id'            => $employee->id,
                    'employee_code' => $employee->employee_code,
                    'display_name'  => $employee->display_name,
                    'designation'   => $employee->designation,
                    'department'    => $employee->department,
                    'role'          => $employee->role,
                    'status'        => $employee->status,
                ] : null,
            ],
        ]);
    }

    /**
     * Logout and revoke current token.
     */
    #[
        OA\Post(
            path: "/logout",
            summary: "Logout",
            description: "Revoke current Sanctum token and mark employee offline.",
            security: [["bearerAuth" => []]],
            tags: ["Authentication"]
        ),
        OA\Response(response: 200, description: "Logged out successfully"),
        OA\Response(response: 401, description: "Unauthenticated")
    ]
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        // Mark employee as offline if applicable
        if ($user && $user->company_id) {
            Employee::where('tenant_id', $user->company_id)
                ->where('email', $user->email)
                ->update(['is_online' => false]);
        }

        // Revoke token
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status'  => true,
            'message' => 'Logged out successfully.',
        ]);
    }
}
