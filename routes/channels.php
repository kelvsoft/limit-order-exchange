<?php

use Illuminate\Support\Facades\Broadcast;

// SECURITY CHECK: Only allow the user to listen to their own private data
// $user is the person currently logged in
Broadcast::channel('user.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
