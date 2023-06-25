<?php

use App\Events\ChatMessage;
use Illuminate\Http\Request;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PostController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\FollowController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

Route::get('/admins-only', function(){
    return 'Only admins should be able to see this page.';
})->middleware('can:visitAdminPages');

//User related Routes
Route::get('/', [UserController::class, "showCorrectHomePage"])->name('login');
//Registration
Route::post('/register', [UserController::class, "register"])->middleware('guest');

//Login
Route::post('/login', [UserController::class, "login"])->middleware('guest');
//Logut
Route::post('/logout', [UserController::class, "logout"])->middleware('mustBeLoggedIn');
//Post related Crud actions
Route::delete('/post/{post}', [PostController::class, "delete"])->middleware('can:delete,post');
Route::get('/post/{post}/edit', [PostController::class, 'showEditForm'])->middleware('can:update,post');
Route::PUT('/post/{post}', [PostController::class, 'actuallyUpdate'])->middleware('can:update,post');
//User related Crud actions
Route::get('/manage-avatar', [UserController::class, 'showAvatarForm'])->middleware('mustBeLoggedIn');
Route::post('/manage-avatar', [UserController::class, 'storeAvatar'])->middleware('mustBeLoggedIn');
Route::get('/manage-username', [UserController::class, 'showUsernameForm'])->middleware('mustBeLoggedIn');
Route::post('/manage-username', [UserController::class, 'updateUsername'])->middleware('mustBeLoggedIn');

//Follow related routes
Route::post('/create-follow/{user:username}', [FollowController::class, 'createFollow'])->middleware('mustBeLoggedIn');
Route::post('/remove-follow/{user:username}', [FollowController::class, 'removeFollow'])->middleware('mustBeLoggedIn');

//Blog post related routes
Route::get('/create-post', [PostController::class, 'showCreateForm'])->middleware('mustBeLoggedIn');
Route::post('/create-post', [PostController::class, 'storeNewPost'])->middleware('mustBeLoggedIn');
Route::get('/post/{post}', [PostController::class, 'viewSinglePost']);
Route::get('/search/{term}', [PostController::class, 'search']);

// Profile related routes
Route::get('/profile/{user:username}', [UserController::class, 'profile']);
Route::get('/profile/{user:username}/followers', [UserController::class, 'profileFollowers']);
Route::get('/profile/{user:username}/following', [UserController::class, 'profileFollowing']);

// Profile related routes in JSON formats
Route::get('/profile/{user:username}/raw', [UserController::class, 'profileRaw']);
Route::get('/profile/{user:username}/followers/raw', [UserController::class, 'profileFollowersRaw']);
Route::get('/profile/{user:username}/following/raw', [UserController::class, 'profileFollowingRaw']);

//Chat routes
Route::post('/send-chat-message', function (Request $request) {
    $formFields = $request->validate([
      'textvalue' => 'required'
    ]);
  
    if (!trim(strip_tags($formFields['textvalue']))) {
      return response()->noContent();
    }
  
    broadcast(new ChatMessage(['username' =>auth()->user()->username, 'textvalue' => strip_tags($request->textvalue), 'avatar' => auth()->user()->avatar]))->toOthers();
    return response()->noContent();
  
  })->middleware('mustBeLoggedIn');