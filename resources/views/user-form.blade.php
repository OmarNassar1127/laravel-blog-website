<x-layout doctitle="Manage your Username">
  <div class="container container--narrow py-md-5">
    <h2 class="text-center mb-3">Update your username</h2>
    <form action="/manage-username" method="POST" enctype="multipart/form-data">
    @csrf
    <div class="mb-3">
      <input type="text" name="username" value="{{ old('username', auth()->user()->username) }}" required>
      @error('username')
          <p class="alert small alert-danger shadow-sm mt-2">{{$message}}</p>
      @enderror
    </div>
    <button class="btn btn-primary">Save</button>
  </form>
  </div>
</x-layout>