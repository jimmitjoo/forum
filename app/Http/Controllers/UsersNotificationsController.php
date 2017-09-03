<?php

namespace App\Http\Controllers;

use App\User;
use Illuminate\Http\Request;

class UsersNotificationsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index()
    {
        return auth()->user()->unreadNotifications;
    }

    public function destroy($id)
    {
        auth()->user()->notifications()->findOrFail($id)->markAsRead();
    }
}
