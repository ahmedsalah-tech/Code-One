<?php

namespace App\Http\Controllers;

use OpenApi\Attributes as OA;
use App\Http\Requests\PostCreateRequest;
use App\Http\Requests\PostUpdateRequest;
use App\Models\Post;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class PostController extends Controller
{
    #[OA\Get(
        path: "/",
        summary: "Display a listing of the posts",
        tags: ["Posts"],
        responses: [
            new OA\Response(response: 200, description: "Successful operation")
        ]
    )]
    public function index()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query =  Post::with(['user', 'media'])
            ->where('published_at', '<=', now())
            ->withCount('claps')
            ->latest();
        if ($user) {
            $ids = $user->following()->pluck('users.id');
            $query->whereIn('user_id', $ids);
        }

        $posts = $query->simplePaginate(5);
        return view('post.index', [
            'posts' => $posts,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::get();

        return view('post.create', [
            'categories' => $categories,
        ]);
    }

    #[OA\Post(
        path: "/post/create",
        summary: "Store a newly created post",
        tags: ["Posts"],
        security: [["csrf_token" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["title", "body", "category_id"],
                properties: [
                    new OA\Property(property: "title", type: "string", example: "My First Post"),
                    new OA\Property(property: "body", type: "string", example: "Content of the post..."),
                    new OA\Property(property: "category_id", type: "integer", example: 1)
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Post created successfully"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function store(PostCreateRequest $request)
    {
        $data = $request->validated();

        // $image = $data['image'];
        // unset($data['image']);
        $data['user_id'] = Auth::id();

        // $imagePath = $image->store('posts', 'public');
        // $data['image'] = $imagePath;

        $post = Post::create($data);

        $post->addMediaFromRequest('image')
            ->toMediaCollection();

        return redirect()->route('dashboard');
    }

    #[OA\Get(
        path: "/@{username}/{slug}",
        summary: "Display the specified post",
        tags: ["Posts"],
        parameters: [
            new OA\Parameter(name: "username", in: "path", required: true, schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "slug", in: "path", required: true, schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
            new OA\Response(response: 404, description: "Post not found")
        ]
    )]
    public function show(string $username, Post $post)
    {
        $post->load(['user', 'category', 'media']);
        return view('post.show', [
            'post' => $post,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Post $post)
    {
        if ($post->user_id !== Auth::id()) {
            abort(403);
        }
        $categories = Category::get();
        return view('post.edit', [
            'post' => $post,
            'categories' => $categories,
        ]);
    }

    #[OA\Put(
        path: "/post/{post}",
        summary: "Update the specified post",
        tags: ["Posts"],
        security: [["csrf_token" => []]],
        parameters: [
            new OA\Parameter(name: "post", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                properties: [
                    new OA\Property(property: "title", type: "string"),
                    new OA\Property(property: "body", type: "string"),
                    new OA\Property(property: "category_id", type: "integer")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Post updated successfully"),
            new OA\Response(response: 403, description: "Forbidden"),
            new OA\Response(response: 404, description: "Post not found")
        ]
    )]
    public function update(PostUpdateRequest $request, Post $post)
    {
        if ($post->user_id !== Auth::id()) {
            abort(403);
        }
        $data = $request->validated();

        $post->update($data);

        if ($data['image'] ?? false) {
            $post->addMediaFromRequest('image')
                ->toMediaCollection();
        }

        return redirect()->route('myPosts');
    }

    #[OA\Delete(
        path: "/post/{post}",
        summary: "Remove the specified post",
        tags: ["Posts"],
        security: [["csrf_token" => []]],
        parameters: [
            new OA\Parameter(name: "post", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(response: 204, description: "Post deleted successfully"),
            new OA\Response(response: 403, description: "Forbidden"),
            new OA\Response(response: 404, description: "Post not found")
        ]
    )]
    public function destroy(Post $post)
    {
        if ($post->user_id !== Auth::id()) {
            abort(403);
        }
        $post->delete();

        return redirect()->route('dashboard');
    }

    #[OA\Get(
        path: "/category/{category}",
        summary: "Display posts by category",
        tags: ["Posts"],
        parameters: [
            new OA\Parameter(name: "category", in: "path", required: true, schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation")
        ]
    )]
    public function category(Category $category)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();

        $query = $category->posts()
            ->where('published_at', '<=', now())
            ->with(['user', 'media'])
            ->withCount('claps')
            ->latest();

        if ($user) {
            $ids = $user->following()->pluck('users.id');
            $query->whereIn('user_id', $ids);
        }
        $posts = $query->simplePaginate(5);

        return view('post.index', [
            'posts' => $posts,
        ]);
    }

    #[OA\Get(
        path: "/my-posts",
        summary: "Display the authenticated user's posts",
        tags: ["Posts"],
        responses: [
            new OA\Response(response: 200, description: "Successful operation")
        ]
    )]
    public function myPosts()
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $posts = $user->posts()
            ->with(['user', 'media'])
            ->withCount('claps')
            ->latest()
            ->simplePaginate(5);

        return view('post.index', [
            'posts' => $posts,
        ]);
    }
}
