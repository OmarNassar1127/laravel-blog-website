<?php

namespace App\Http\Controllers;

use App\Models\Follow;
use App\Models\User;
use Illuminate\Http\Request;

class FollowController extends Controller
{
    //
    public function createFollow(User $user)
{
    // Check if the user is trying to follow themselves
    if ($user->id == auth()->user()->id) {
        return back()->with('error', 'You cannot follow yourself');
    }
    
    // Check if the user is already following the specified user
    $existCheck = Follow::where([
        ['user_id', '=', auth()->user()->id],
        ['followeduser', '=', $user->id]
    ])->count();
    if ($existCheck) {
        return back()->with('error', 'You are already following this user');
    }

    // Create a new Follow model instance
    $newFollow = new Follow;
    $newFollow->user_id = auth()->user()->id; // Set the authenticated user as the follower
    $newFollow->followeduser = $user->id; // Set the specified user as the followed user
    $newFollow->save(); // Save the new follow record

    return back()->with('success', 'You are now following this user');
}
public function removeFollow(User $user)
{
    // Find and delete the follow relationship between the authenticated user and the specified user
    Follow::where([
        ['user_id', '=', auth()->user()->id],
        ['followeduser', '=', $user->id]
    ])->delete();

    // Return a success message indicating that the user has been unfollowed
    return back()->with('success', 'User successfully unfollowed');
}

}
