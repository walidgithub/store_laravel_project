<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\View;

class DashboardController extends Controller
{
    public function __construct()
    {
        //$this->middleware(['auth'])->only('index');
    }

    // Actions
    public function index()
    {

        // view is a response and return object

        // return view('dashboard');

        // to path variable to html
        // $user is a variable we will pass to html

        // return view('dashboard', [
        //     'user'=> 'walid barakat',
        // ]);

        // or
        // $user = 'walid barakat';
        // return view('dashboard', compact('user'));

        // or
        // the path of index.php file is dashboard/index

        $title = 'Store';

        $user = Auth::user();

        // Return response: view, josn, redirect, file

        return view('dashboard.index', [
            'user' => 'Mohammed',
            'title' => $title,
        ]);
    }
}