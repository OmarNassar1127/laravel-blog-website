<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\User;
use App\Models\Follow;
use Illuminate\Http\Request;
use App\Events\OurExampleEvent;
use Illuminate\Validation\Rule;
use Psy\Command\WhereamiCommand;
use Illuminate\Support\Facades\View;
use Illuminate\Support\Facades\Cache;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Storage;

class UserController extends Controller
{

    
    public function profileFollowers(User $user)
    {
        // Retrieve shared data for the user's profile
        $this->getSharedData($user);

        // Return the 'profile-followers' view with the followers of the user
        return view('profile-followers', [
            'followers' => $user->followers()->latest()->get()
        ]);
    }

    public function profileFollowersRaw(User $user)
    {
        // Return a JSON response containing the rendered HTML of the 'profile-followers-only' view
        // and the document title
        return response()->json([
            'theHTML' => view('profile-followers-only', ['followers' => $user->followers()->latest()->get()])->render(),
            'docTitle' => $user->username . "'s Followers"
        ]);
    }

    public function profileFollowingRaw(User $user)
    {
        // Return a JSON response containing the rendered HTML of the 'profile-following-only' view
        // and the document title
        return response()->json([
            'theHTML' => view('profile-following-only', ['following' => $user->followingTheseUsers()->latest()->get()])->render(),
            'docTitle' => 'Who ' . $user->username . "'s Follows"
        ]);
    }

    public function profileFollowing(User $user)
    {
        // Retrieve shared data for the user's profile
        $this->getSharedData($user);
    
        // Return the 'profile-following' view with the users that the user is following
        return view('profile-following', [
            'following' => $user->followingTheseUsers()->latest()->get()
        ]);
    }
    
    public function storeAvatar(Request $request)
    {
        // Validate the avatar file
        $request->validate([
            'avatar' => 'required|image|max:3000'
        ]);

        // Get the authenticated user
        $user = auth()->user();

        // Generate a unique file name for the avatar
        $fileName = $user->id . '-' . uniqid() . "-$user->username" . '.jpg';

        // Update the avatar
        $imgData = Image::make($request->file('avatar'))->fit(120)->encode('jpg');
        Storage::put('public/avatars/' . $fileName, $imgData);

        // Store the old avatar path
        $oldAvatar = $user->avatar;

        // Set the new avatar path for the user
        $user->avatar = $fileName;
        $user->save();

        // Delete the old avatar if it is different from the fallback avatar
        if ($oldAvatar != "/fallback-avatar.jpg") {
            Storage::delete(str_replace("/storage/", "public/", $oldAvatar));
        }

        // Return back to the previous page with a success message
        return back()->with('success', 'Congrats your account has been edited!');
    }

    public function showAvatarForm(){
        return view('avatar-form');
    }

    public function updateUsername(Request $request)
    {
        // Validate the username field
        $request->validate([
            'username' => [
                'required',
                'min:3',
                'max:20',
                Rule::unique('users', 'username')->ignore(auth()->user()->id),
            ],
        ]);
    
        // Get the authenticated user
        $user = auth()->user();
    
        // Check if the username has changed
        if ($user->username !== $request->username) {
            // Update the username
            $user->username = $request->username;
            $user->save();
    
            // Redirect to the user's profile with a success message
            return redirect('/profile/' . auth()->user()->username)->with('success', 'Congratulations, your username has been edited!');
        } elseif ($user->username == $request->username) {
            // Return back with an error message if the username is the same
            return back()->with('error', 'You already have this name');
        }
    }



    public function showUsernameForm()
    {
        return view('user-form');
    }

    private function getSharedData($user)
    {
        $currentlyFollowing = 0;
    
        // Check if the user is currently authenticated
        if (auth()->check()) {
            // Count the number of follows where the authenticated user is following the specified user
            $currentlyFollowing = Follow::where([
                ['user_id', auth()->user()->id], // ID of the authenticated user
                ['followeduser', $user->id] // ID of the specified user
            ])->count();
        }
    
        // Share the gathered data with the views
        View::share('sharedData', [
            'currentlyFollowing' => $currentlyFollowing,
            'avatar' => $user->avatar,
            'username' => $user->username,
            'postCount' => $user->posts()->count(),
            'followerCount' => $user->followers()->count(),
            'followingCount' => $user->followingTheseUsers()->count()
        ]);
    }
    

    public function profile(User $user)
    {
        $this->getSharedData($user);
        return view('profile-posts', [
            'posts' => $user->posts()->latest()->get()
        ]);
    }

    public function profileRaw(User $user)
    {
        // Render the 'profile-posts-only' view with the user's latest posts
        $posts = $user->posts()->latest()->get();
        $html = view('profile-posts-only', ['posts' => $posts])->render();

        // Create a JSON response with the rendered HTML and the document title
        $response = response()->json([
            'theHTML' => $html,
            'docTitle' => $user->username . "'s Profile"
        ]);

        // Return the JSON response
        return $response;
    }


    public function logout()
    {
        // Trigger the 'OurExampleEvent' with the user's username and the action 'logout'
        event(new OurExampleEvent(['username' => auth()->user()->username, 'action' => 'logout']));

        // Log out the user
        auth()->logout();

        // Redirect the user to the root URL with a success message
        return redirect('/')->with('success', 'You are now logged out');
    }

    public function showCorrectHomePage()
    {
        if (auth()->check()) {
            // Render the 'homepage-feed' view with the authenticated user's feed posts
            $posts = auth()->user()->feedPosts()->latest()->paginate(6);
            return view('homepage-feed', ['posts' => $posts]);
        } else {
            // Check the cache for the post count or retrieve it from the database if not cached
            $postCount = Cache::remember('postCount', 20, function () {
                return Post::count();
            });
            // Render the 'homepage' view with the post count
            return view('homepage', ['postCount' => $postCount]);
        }
    }

    public function loginApi(Request $request)
    {
        // Validate the incoming fields from the login request
        $incomingFields = $request->validate([
            'username' => 'required',
            'password' => 'required',
        ]);
    
        // Attempt to authenticate the user using the provided credentials
        if (auth()->attempt($incomingFields)) {
            // Retrieve the user record based on the provided username
            $user = User::where('username', $incomingFields['username'])->first();
    
            // Create a new token for the authenticated user
            $token = $user->createToken('ourapptoken')->plainTextToken;
    
            // Return the token as the API response
            return $token;
        }
    
        // If authentication fails, return an error message
        return 'Sorry not sorry';
    }
    public function login(Request $request)
    {
        // Validate the incoming fields from the login form
        $incomingFields = $request->validate([
            'loginusername' => 'required',
            'loginpassword' => 'required',
        ]);
    
        // Attempt to authenticate the user using the provided credentials
        if (auth()->attempt(['username' => $incomingFields['loginusername'], 'password' => $incomingFields['loginpassword']])) {
            // Regenerate the session to prevent session fixation attacks
            $request->session()->regenerate();
            
            // Fire the 'OurExampleEvent' event to log the user login action
            event(new OurExampleEvent(['username' => auth()->user()->username, 'action' => 'login']));
            
            // Redirect the user to the homepage with a success message
            return redirect('/')->with('success', 'You have successfully logged in.');
        } else {
            // If authentication fails, redirect back to the login form with an error message
            return redirect('/')->with('error', 'Invalid login.');
        }
    }
    public function register(Request $request)
    {
        // Validate the incoming registration fields
        $incomingFields = $request->validate([
            'username' => ['required', 'min:3', 'max:20', Rule::unique('users', 'username')],
            'email' => ['required', 'email', Rule::unique('users', 'email')],
            'password' => ['required', 'min:6', 'confirmed'],
            
        ]);

        // Hash the password for security
        $incomingFields['password'] = bcrypt($incomingFields['password']);

        // Create a new user record in the database
        $user = User::create($incomingFields);

        // Log in the newly registered user
        auth()->login($user);

        // Redirect the user to the home page with a success message
        return redirect('/')->with('success', 'Thank you for creating an account');
    }
}
