<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Models\Blog;
use App\Models\BlogLike;
use File;
class BlogController extends Controller
{
    public function create(Request $request){
        $validator = Validator::make($request->all(), [
            'title'=>'required|max:250',
            'short_description'=>'required',
            'long_description'=>'required',
            'category_id'=>'required',
            'image'=>'required|image|mimes:jpg,bmp,png'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message'=>'Validation errors',
                'errors'=>$validator->messages()
            ],422);
        }

        $image_name=time().'.'.$request->image->extension();
        $request->image->move(public_path('/uploads/blog_images'),$image_name);

        $blog=Blog::create([
            'title'=>$request->title,
            'short_description'=>$request->short_description,
            'long_description'=>$request->long_description,
            'user_id'=>$request->user()->id,
            'category_id'=>$request->category_id,
            'image'=>$image_name
        ]);

        $blog->load('user:id,name,email','category:id,name');
        
        return response()->json([
            'message'=>'Blog successfully created',
            'data'=>$blog
        ],200);

    }

    public function list(Request $request){
        $blog_query=Blog::withCount(['comments','likes'])->with(['user','category']);
        if($request->keyword){
            $blog_query->where('title','LIKE','%'.$request->keyword.'%');
        }

        if($request->category){
            $blog_query->whereHas('category',function($query) use($request){
                $query->where('slug',$request->category);
            });
        }
        if($request->user_id){
            $blog_query->where('user_id',$request->user_id);
        }

        if($request->sortBy && in_array($request->sortBy,['id','created_at','comments_count','likes_count'])){
            $sortBy=$request->sortBy;

        }else{
            $sortBy='id';
        }

        if($request->sortOrder && in_array($request->sortOrder,['asc','desc'])){
            $sortOrder=$request->sortOrder;

        }else{
            $sortOrder='desc';
        }

        if($request->perPage){
            $perPage=$request->perPage;
        }else{
            $perPage=5;
        }
        
        if($request->paginate){

           $blogs=$blog_query->orderBY($sortBy,$sortOrder)->paginate($perPage); 

        }else{
          $blogs=$blog_query->orderBY($sortBy,$sortOrder)->get();  
        }

        
        return response()->json([
            'message'=>'Blog successfully fetched',
            'data'=>$blogs
        ],200);
    }

    public function details($id,Request $request){
        $blog=Blog::withCount(['comments','likes'])->with(['user','category'])->where('id',$id)->first();
        if($blog){
            $user=auth('sanctum')->user();
            if($user){
              $blog_like=BlogLike::where('blog_id',$blog->id)->where('user_id',$user->id)->first();
              if($blog_like){
                $blog->liked_by_current_user=true;
              } else{
                $blog->liked_by_current_user=false;
              }
            }else{
                $blog->liked_by_current_user=false;
            }
            
            
            return response()->json([
                'message'=>'Blog successfully fetched',
                'data'=>$blog
            ],200);
        }else{
            return response()->json([
                'message'=>'No blog found',
            ],400);   
        }
    }

    public function update($id,Request $request){
        $blog=Blog::with(['user','category'])->where('id',$id)->first();
        if($blog){
            if($blog->user_id==$request->user()->id){
                $validator = Validator::make($request->all(), [
                    'title'=>'required|max:250',
                    'short_description'=>'required',
                    'long_description'=>'required',
                    'category_id'=>'required',
                    'image'=>'nullable|image|mimes:jpg,bmp,png'
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'message'=>'Validation errors',
                        'errors'=>$validator->messages()
                    ],422);
                }

                if($request->hasFile('image')){
                    $image_name=time().'.'.$request->image->extension();
                    $request->image->move(public_path('/uploads/blog_images'),$image_name);
                    $old_path=public_path().'/uploads/blog_images/'.$blog->image;
                    if(File::exists($old_path)){
                        File::delete($old_path);
                    }
                }else{
                    $image_name=$blog->image;
                }

                $blog->update([
                    'title'=>$request->title,
                    'short_description'=>$request->short_description,
                    'long_description'=>$request->long_description,
                    'category_id'=>$request->category_id,
                    'image'=>$image_name
                ]);

                
                return response()->json([
                    'message'=>'Blog successfully updated',
                    'data'=>$blog
                ],200);


            }else{
                return response()->json([
                    'message'=>'Access denied',
                ],403);    
            }
        }else{
            return response()->json([
                'message'=>'No blog found',
            ],400);   
        }
    }

    public function delete($id,Request $request){
        $blog=Blog::where('id',$id)->first();
        if($blog){
            if($blog->user_id==$request->user()->id){

                $old_path=public_path().'/uploads/blog_images/'.$blog->image;
                if(File::exists($old_path)){
                    File::delete($old_path);
                } 

                $blog->delete();

                return response()->json([
                    'message'=>'Blog successfully deleted',
                ],200);


            }else{
                return response()->json([
                    'message'=>'Access denied',
                ],403);    
            }
        }else{
            return response()->json([
                'message'=>'No blog found',
            ],400);   
        }
    }

    public function toggle_like($id,Request $request){
        $blog=Blog::where('id',$id)->first();
        if($blog){
           $user= $request->user();
           $blog_like=BlogLike::where('blog_id',$blog->id)->where('user_id',$user->id)->first();
           if($blog_like){
                $blog_like->delete();
                return response()->json([
                    'message'=>'Like successfully removed',
                ],200); 
           }else{
                BlogLike::create([
                    'blog_id'=>$blog->id,
                    'user_id'=>$user->id
                ]);
                return response()->json([
                    'message'=>'Blog successfully liked',
                ],200);   
           }
        }else{
            return response()->json([
                'message'=>'No blog found',
            ],400);   
        }
    }

}
