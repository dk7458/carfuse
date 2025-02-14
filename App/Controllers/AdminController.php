<?php

namespace App\Controllers;

use App\Models\User;
use App\Models\Admin;
use App\Models\Booking;
use App\Models\Payment;
use App\Models\TransactionLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

/**
 * AdminController - Handles admin user management and dashboard operations.
 */
class AdminController extends Controller
{
    /**
     * ✅ Get a paginated list of all users with their roles.
     */
    public function getAllUsers()
    {
        $users = User::with('roles')->latest()->paginate(10);
        return response()->json(['users' => $users], 200);
    }

    /**
     * ✅ Update a user's role.
     */
    public function updateUserRole(Request $request, $userId)
    {
        $request->validate([
            'role' => 'required|string|in:user,admin,manager'
        ]);

        $user = User::findOrFail($userId);
        $user->update(['role' => $request->role]);

        Log::channel('audit')->info("User role updated: {$user->email} to {$request->role}");
        return response()->json(['message' => 'User role updated successfully'], 200);
    }

    /**
     * ✅ Delete a user (Soft delete).
     */
    public function deleteUser($userId)
    {
        $user = User::findOrFail($userId);
        $user->delete();

        Log::channel('audit')->warning("User deleted: {$user->email}");
        return response()->json(['message' => 'User deleted successfully'], 200);
    }

    /**
     * ✅ Fetch admin dashboard statistics.
     */
    public function getDashboardData()
    {
        $dashboardData = Cache::remember('admin_dashboard', 60, function () {
            return [
                'total_users' => User::count(),
                'total_bookings' => Booking::count(),
                'total_revenue' => Payment::sum('amount'),
                'latest_users' => User::with('roles')->latest()->limit(5)->get(),
                'latest_transactions' => TransactionLog::latest()->limit(5)->get(),
            ];
        });

        return response()->json($dashboardData, 200);
    }

    /**
     * ✅ Create a new admin user.
     */
    public function createAdmin(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users',
            'password' => 'required|min:8',
        ]);

        $admin = Admin::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'role' => 'admin'
        ]);

        Log::channel('audit')->info("New admin created: {$admin->email}");
        return response()->json(['message' => 'Admin created successfully', 'admin' => $admin], 201);
    }
}
