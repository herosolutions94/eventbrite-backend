<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Components\Services\OrganizationService;
use App\Models\User;
use App\Models\Country;
use App\Models\Wallet;
use Illuminate\Support\Facades\DB;

class UserController extends Controller
{
    public function index()
    {
        $users = User::latest()->get();

        return view('admin.users.index',
            [
                'data' => $users,
                'title'=>'Users',
                'role'=>'all'
            ]);
    }
    public function players()
    {
        $users = User::where('role','player')->latest()->get();

        return view('admin.users.index',
            [
                'data' => $users,
                'title'=>'Players',
                'role'=>'player'
            ]);
    }
    public function organizers()
    {
        $users = User::where('role','organizer')->latest()->get();

        return view('admin.users.index',
            [
                'data' => $users,
                'title'=>'Organizers',
                'role'=>'organizer'
            ]);
    }
    public function create()
    {
        return view('admin.users.create');
    }
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'phone' => 'required|unique:users',
            'email' => 'required|unique:users',
            'password' => 'required',
            'role' => 'required',
        ]);
        $user = User::create($request->all());
        $wallet = new Wallet();
        $wallet->user_id = $user->id;
        $wallet->save();
        return redirect()->route('admin.users.index')->with('success', 'User created successfully.');
    }
    public function edit_organizer(User $user,$id)
    { 
        if($user = User::with('countryName')->find($id)){
            $user->countries = Country::all();
            $user->states=DB::table('states')->where('country_id', $user->country)->get();
            // return $user->countryName;
            return view('admin.users.edit', compact('user')); 
        }
        
    }
    public function edit_player(User $user,$id)
    { 
        if($user = User::with('countryName')->find($id)){
            $user->countries = Country::all();
            $user->states=DB::table('states')->where('country_id', $user->country)->get();
            // return $user->countryName;
            return view('admin.users.edit_player', compact('user')); 
        }
        
    }
    public function view(User $user,$id)
    { 
        $user = User::with('countryName')->find($id);
        // return $user->countryName;
        return view('admin.users.view', compact('user'));
    }
    public function update(Request $request, User $user,$id)
    {
       if($user = User::with('countryName')->find($id)){
            $userData  =  $request->except(['_token']);
            if ($request->hasFile('user_image')) {
                $user_image = $request->file('user_image')->store('uploads', 'public');
                $userData['user_image'] = basename($user_image);
            }
            if ($request->hasFile('user_cover')) {
                $user_cover = $request->file('user_cover')->store('uploads', 'public');
                $userData['user_cover'] = basename($user_cover);
            }
            // pr($userData);
            User::where('id',$id)->update($userData);
            if($user->role=='player'){
                return redirect()->route('admin.players.edit',$user->id)->with('flash_message_success', 'User updated successfully');
            }
            else{
                return redirect()->route('admin.organizers.edit',$user->id)->with('flash_message_success', 'User updated successfully');
            }
           
       }
       else{
        return redirect()->route('admin.organizers.index')->with('success', 'User deleted successfully');
       }
        
    }
    public function delete(User $user,$id)
    {

        User::where('id',$id)->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully');
    }
}
