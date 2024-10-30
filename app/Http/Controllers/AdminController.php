<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;

class AdminController extends Controller
{
    public function profilepage()
    {
        // Get the authenticated user
        $user = Auth::user();

        // Pass the user data to the profile view
        return view('profile', compact('user'));
    }
    public function updateProfile(Request $request)
    {
        // Get the authenticated user
        $user = User::find(Auth::user()->id);
        // $user = Auth::user();

        // Validate the incoming request
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'profile_picture' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'current_password' => 'required|string|min:6', // Ensure this is always required
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        // Check if the current password is correct
        if (!Hash::check($request->current_password, $user->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect.']);
        }

        // Update the user's name and email
        $user->name = $request->name;
        $user->email = $request->email;

        // Update profile picture if provided
        if ($request->hasFile('profile_picture')) {
            // Delete the old profile picture if it exists
            if ($user->image) {
                Storage::delete($user->image);
            }

            // Store the new profile picture
            $path = $request->file('profile_picture')->store('profile_pictures', 'public');
            $user->image = $path;  // Update the image path on the user object
        }

        // Update the user's password if a new one is provided
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        try {
            // Save changes to the user model
            $user->save();

            // dd ($user);

            // Optionally, return a success message or redirect
            return redirect()->route('admin/profile')->with('success', 'Profile updated successfully!');
        } catch (\Exception $e) {
            // Handle any exceptions that may occur during saving
            return back()->withErrors(['error' => 'Failed to update profile. Please try again.']);
        }
    }

}
