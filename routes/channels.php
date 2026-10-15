<?php

// PRESENTATION NOTE: channels.
// This file defines application routes or automated tests. The comments explain
// the purpose of the route/test setup; they do not change request handling or assertions.

use Illuminate\Support\Facades\Broadcast;

// Define the authorization rules for the named broadcast channel.
Broadcast::channel('App.Models.User.{id}', function ($user, $id) {
    return (int) $user->id === (int) $id;
});
