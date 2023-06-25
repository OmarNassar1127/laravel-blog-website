<?php

namespace App\Http\Controllers;

use App\Jobs\SendNewPostEmail;
use App\Models\Post;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;

class PostController extends Controller
{

    public function search($term)
    {
        // Query for posts with matching title or username
        $posts = Post::query()
            ->where('title', 'LIKE', '%' . $term . '%') // Search by post title
            ->orWhereHas('user', function ($query) use ($term) {
                $query->where('username', 'LIKE', '%' . $term . '%'); // Search by username
            })
            ->get();
    
        // Eager load the user relationship with specific columns
        $posts->load('user:id,username,avatar');
    
        // Return the collection of posts
        return $posts;
    }
    

    public function actuallyUpdate(Post $post, Request $request)
    {
        // Validate the incoming fields
        $incomingFields = $request->validate([
            'title' => 'required',
            'body' => 'required'
        ]);

        // Strip HTML tags from the title and body fields
        $incomingFields['title'] = strip_tags($incomingFields['title']);
        $incomingFields['body'] = strip_tags($incomingFields['body']);

        // Update the post with the validated and sanitized fields
        $post->update($incomingFields);

        // Redirect back with a success message
        return back()->with('success', 'Post successfully updated');
    }

    public function editApi(Post $post, Request $request)
    {
        // Validate the incoming fields
        $incomingFields = $request->validate([
            'title' => 'required',
            'body' => 'required'
        ]);

        // Strip HTML tags from the title and body fields
        $incomingFields['title'] = strip_tags($incomingFields['title']);
        $incomingFields['body'] = strip_tags($incomingFields['body']);

        // Update the post with the validated and sanitized fields
        $post->update($incomingFields);

        // Return a success message
        return 'Post successfully updated broski';
    }

    public function showEditForm(Post $post) {
        return view('/edit-post', ['post' => $post]);
    }
    public function deleteApi(Post $post){
        $post->delete();
        return 'Post successfully deleted';
    }
    public function delete(Post $post){
        // if (auth()->user()->cannot('delete', $post)) {
        //     return 'You cannot delete this post';
        // }
        $post->delete();
        return redirect('/profile/' . auth()->user()->username)->with('success', 'Post succesfully deleted');
    }
    public function viewSinglePost(Post $post) {
        $post['body'] = strip_tags(Str::markdown($post->body), '<p><ul><ol><li><strong><em><h3><br>');
        return view('single-post', ['post' => $post]);
    }
    public function showCreateForm() {
        // if(!auth()->check()) {
        //     return redirect('/')->with('error', 'You are not logged in');
        // }
        
        return view('create-post');
    }
    
    public function storeNewPost(Request $request)
    {
        // Validate the incoming request fields
        $incomingFields = $request->validate([
            'title' => 'required',
            'body' => 'required'
        ]);

        // Strip HTML tags from the title and body fields
        $incomingFields['title'] = strip_tags($incomingFields['title']);
        $incomingFields['body'] = strip_tags($incomingFields['body']);

        // Set the user_id field to the ID of the authenticated user
        $incomingFields['user_id'] = auth()->id();

        // Create a new Post using the validated and modified fields
        $newPost = Post::create($incomingFields);

        // Dispatch a job to send a new post email notification
        dispatch(new SendNewPostEmail([
            'sendTo' => auth()->user()->email,
            'name' => auth()->user()->username,
            'title' => $newPost->title
        ]));

        // Redirect to the newly created post with a success message
        return redirect("/post/{$newPost->id}")->with('success', 'New post successfully created');
    }

    public function storeNewPostApi(Request $request)
    {
        // Validate the incoming request fields
        $incomingFields = $request->validate([
            'title' => 'required',
            'body' => 'required'
        ]);

        // Strip HTML tags from the title and body fields
        $incomingFields['title'] = strip_tags($incomingFields['title']);
        $incomingFields['body'] = strip_tags($incomingFields['body']);

        // Set the user_id field to the ID of the authenticated user
        $incomingFields['user_id'] = auth()->id();

        // Create a new Post using the validated and modified fields
        $newPost = Post::create($incomingFields);

        // Dispatch a job to send a new post email notification
        dispatch(new SendNewPostEmail([
            'sendTo' => auth()->user()->email,
            'name' => auth()->user()->username,
            'title' => $newPost->title
        ]));

        // Return the ID of the newly created post
        return $newPost->id;
    }

}
