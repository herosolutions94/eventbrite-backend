<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CmsController extends Controller
{
    public function contact()
    {
        $contact = DB::table('contact_us_cms')->where('id', 1)->first();
        return view('admin.cms.contact', compact('contact'));
    }
    public function contactSubmit(Request $request, $id)
    {

        $request->validate([
            'title' => 'required',
            'heading' => 'required',
            'description' => 'required',
            'address' => 'required',
            'email' => 'required',
            'phone' => 'required',
        ]);

        if ($request->hasFile('image')) {
            $imageName = "image_".time().'.'.$request->image->extension();  
            $image=$request->image->move(public_path('uploads'), $imageName);
            $image=basename($image);
            // $image = $request->file('image')->store('uploads', 'public');
        }else{
            $image = DB::table('contact_us_cms')->where('id', $id)->first()->image;
        }
        DB::table('contact_us_cms')->where('id', $id)->update([
            'title' => $request->title,
            'heading' => $request->heading,
            'description' => $request->description,
            'image' => $image,
            'address' => $request->address,
            'email' => $request->email,
            'phone' => $request->phone,
            'meta_title' => $request->meta_title,
            'meta_description' => $request->meta_description,
            'meta_keywords' => $request->meta_keywords,
        ]);
        return redirect()->back()->with('success', 'Your message has been sent successfully.');
    }
    public function about()
    {

        $about = DB::table('about_us')->where('id', 1)->first();
        return view('admin.cms.about', compact('about'));
    }
    public function home()
    { 
        $home = DB::table('home_cms')->where('id', 1)->first();
        return view('admin.cms.home', compact('home'));
    }
     public function homeSubmit(Request $request){
        
        $data = $request->all();

        if ($request->hasFile('resource_img')) {
            // $data['resource_img'] = $request->file('resource_img')->store('uploads', 'public');
            $imageName = "image_".time().'.'.$request->resource_img->extension();  
            $image=$request->resource_img->move(public_path('uploads'), $imageName);
            $data['resource_img']=basename($image);
        }
        if ($request->hasFile('banner_img')) {
            // $data['banner_img'] = $request->file('banner_img')->store('uploads', 'public');
            $banner_img_name = "image_".time().'.'.$request->banner_img->extension();  
            $banner_image=$request->banner_img->move(public_path('uploads'), $banner_img_name);
            $data['banner_img']=basename($banner_image);
        }

        if ($request->hasFile('image')) {
            $contact_imageName = "contact_image_" . time() . '_' . uniqid() . '.' . $request->image->extension();  
            $image = $request->image->move(public_path('uploads'), $contact_imageName);
            $data['image'] = basename($image);
        } else {
            $data['image'] = DB::table('about_us')->where('id', 1)->first()->image;
        }
       
        unset($data['_token']);
        unset($data['avatar_remove']);
        unset($data['1']);

        DB::table('home_cms')->where('id', 1)->update($data);
        return redirect()->back()->with('success', 'Your record has been updated successfully.');
    }

    public function aboutSubmit(Request $request){
        
        $request->validate([
            'title' => 'required',
            'heading' => 'required',
            'description' => 'required',
            'btn_text' => 'required',
            'btn_link' => 'required',
        ]);
        $data = $request->all();

        if ($request->hasFile('image')) {
            $imageName = "image_" . time() . '_' . uniqid() . '.' . $request->image->extension();  
            $image = $request->image->move(public_path('uploads'), $imageName);
            $data['image'] = basename($image);
        } else {
            $data['image'] = DB::table('about_us')->where('id', 1)->first()->image;
        }

        if ($request->hasFile('des_image')) {
            $des_imageName = "des_image_" . time() . '_' . uniqid() . '.' . $request->des_image->extension();  
            $des_image = $request->des_image->move(public_path('uploads'), $des_imageName);
            $data['des_image'] = basename($des_image);
        } else {
            $data['des_image'] = DB::table('about_us')->where('id', 1)->first()->des_image;
        }

        if ($request->hasFile('contact_image')) {
            $contact_imageName = "contact_image_" . time() . '_' . uniqid() . '.' . $request->contact_image->extension();  
            $contact_image = $request->contact_image->move(public_path('uploads'), $contact_imageName);
            $data['contact_image'] = basename($contact_image);
        } else {
            $data['contact_image'] = DB::table('about_us')->where('id', 1)->first()->contact_image;
        }

        // pr($data);
        unset($data['_token']);
        unset($data['avatar_remove']);
        unset($data['1']);

        DB::table('about_us')->where('id', 1)->update($data);
        return redirect()->back()->with('success', 'Your record has been updated successfully.');
    }

}
