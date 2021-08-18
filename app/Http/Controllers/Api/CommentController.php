<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Blog;
use Illuminate\Support\Facades\Validator;
use App\Models\Comment;
class CommentController extends Controller
{

    public function list($blog_id,Request $request){
        $blog=Blog::where('id',$blog_id)->first();
        if($blog){
            $perPage=($request->perPage)?$request->perPage:5;
            $comments=Comment::with(['user'])->where('blog_id',$blog_id)->orderBy('id','desc')->paginate($perPage);
            
            return response()->json([
                'message'=>'Comment successfully fetched',
                'data'=>$comments
            ],200); 

        }else{
            return response()->json([
                'message'=>'No blog found',
            ],400); 
        }  
    }
    public function create($blog_id,Request $request){
        $blog=Blog::where('id',$blog_id)->first();
        if($blog){
            $validator = Validator::make($request->all(), [
                'message'=>'required',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message'=>'Validation errors',
                    'errors'=>$validator->messages()
                ],422);
            }

            $comment=Comment::create([
                'message'=>$request->message,
                'blog_id'=>$blog->id,
                'user_id'=>$request->user()->id
            ]);
            $comment->load('user');
            return response()->json([
                'message'=>'Comment successfully created',
                'data'=>$comment
            ],200);


        }else{
            return response()->json([
                'message'=>'No blog found',
            ],400); 
        }
    }

    public function update($comment_id,Request $request){
        $comment=Comment::with(['user'])->where('id',$comment_id)->first();
        if($comment){
            if($comment->user_id==$request->user()->id){

                $validator = Validator::make($request->all(), [
                    'message'=>'required',
                ]);

                if ($validator->fails()) {
                    return response()->json([
                        'message'=>'Validation errors',
                        'errors'=>$validator->messages()
                    ],422);
                }   

                $comment->update([
                    'message'=>$request->message
                ]);
                return response()->json([
                    'message'=>'Comment successfully updated',
                    'data'=>$comment
                ],200);  

            }else{
                return response()->json([
                    'message'=>'Access denied',
                ],403);    
            }

        }else{
            return response()->json([
                'message'=>'No comment found',
            ],400);   
        }
    }

    public function delete($comment_id,Request $request){
        $comment=Comment::where('id',$comment_id)->first();
        if($comment){
            if($comment->user_id==$request->user()->id){
                $comment->delete();
                return response()->json([
                    'message'=>'Comment successfully deleted'
                ],200);  

            }else{
                return response()->json([
                    'message'=>'Access denied',
                ],403);    
            }

        }else{
            return response()->json([
                'message'=>'No comment found',
            ],400);   
        }
    }
}
