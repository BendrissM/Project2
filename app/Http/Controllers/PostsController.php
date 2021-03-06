<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use App\Post;
use Response;

class PostsController extends Controller
{

    public function __construct(){
        $this->middleware('auth');
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $posts = Post::orderBy('created_at', 'desc')->paginate(3);
        return view('posts.index')->with('posts', $posts);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('posts.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'title' => 'required|unique:posts|max:30',
            'desc' => 'required|min:10',
            'cover_image' => 'image|nullable|max:1999',
        ]);

        //Handle File upload
        if ($request->hasFile('cover_image')){
            //Get file name with extension
            $filenameWithExt = $request->file('cover_image')->getClientOriginalName();
            //but if another user uploads an image with the same name we get error

            //Get File name
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            //Get File extension
            $extension = $request->file('cover_image')->getClientOriginalExtension();
            //File name to store
            $FileNameToStore = $filename.'_'.time().'.'.$extension ;
            //upload image
            $path = $request->file('cover_image')->storeAs('public/cover_images', $FileNameToStore);
        }else{
            $FileNameToStore = 'noimage.jpg';
        }
        
        //if there is no errors store data in database
        if($validator->passes()){
            $post = new Post;
            $post->title = $request->input('title');
            $post->body = $request->input('desc');
            $post->user_id = auth()->user()->id;
            $post->cover_image = $FileNameToStore;
            if($post->save()){
                return Response::json(['success' => '1']);
            }
        }
        return Response::json(['errors' => $validator->errors()]);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $post = Post::find($id);
        return view('posts.show')->with('post', $post);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $post = Post::find($id);
        if (Auth()->user()->id !== $post->user_id) {
            return redirect('/posts')->with('error', 'Unauthorized page');
        }
        return view('posts.edit')->with('post', $post);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $post = Post::find($id);
        $validator = Validator::make($request->all(), [
            'title' => 'required|max:30|unique:posts,title,'.$post->id,
            'desc' => 'required|min:10',
            'cover_image' => 'image|nullable|max:1999',
        ]);

        //Handle File upload
        if ($request->hasFile('cover_image')){
            //Get file name with extension
            $filenameWithExt = $request->file('cover_image')->getClientOriginalName();
            //but if another user uploads an image with the same name we get error

            //Get File name
            $filename = pathinfo($filenameWithExt, PATHINFO_FILENAME);
            //Get File extension
            $extension = $request->file('cover_image')->getClientOriginalExtension();
            //File name to store
            $FileNameToStore = $filename.'_'.time().'.'.$extension ;
            //upload image
            $path = $request->file('cover_image')->storeAs('public/cover_images', $FileNameToStore);
        }else{
            $FileNameToStore = 'noimage.jpg';
        }

        if($validator->passes()){
            if (Auth()->user()->id !== $post->user_id) {
                return redirect('/posts')->with('error', 'Unauthorized page');
            }
            $post->title = $request->input('title');
            $post->body = $request->input('desc');
            if ($request->hasFile('cover_image')){
                $post->cover_image = $FileNameToStore;
            }
            if($post->save()){
                return Response::json(['success' => '1']);
            }
        }
        return Response::json(['errors' => $validator->errors()]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */

    public function deletePost($id)
    {
        $post = Post::find($id);
        if (Auth()->user()->id !== $post->user_id) {
            return redirect('/posts')->with('error', 'Unauthorized page');
        }
        return view('posts.delete')->with('post', $post);
    }

    public function destroy($id)
    {
        $post = Post::find($id);
        if (Auth()->user()->id !== $post->user_id) {
            return redirect('/posts')->with('error', 'Unauthorized page');
        }
        if ($post->cover_image !== 'noimage.jpg'){
            Storage::delete('public/cover_images/'.$post->cover_image);
        }
        $post->delete();
        return redirect('dashboard');
    }
}
