<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use OpenApi\Attributes as OA;

class PublicProfileController extends Controller
{
    #[OA\Get(
        path: "/@{username}",
        summary: "Display user public profile",
        tags: ["Profile"],
        parameters: [
            new OA\Parameter(name: "username", in: "path", required: true, schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Successful operation"),
            new OA\Response(response: 404, description: "User not found")
        ]
    )]
    public function show(User $user)
    {
        $posts = $user->posts()
            ->with(['user', 'media'])
            ->withCount('claps')
            ->where('published_at', '<=', now())
            ->latest()
            ->simplePaginate(5);

        return view('profile.show', [
            'user' => $user,
            'posts' => $posts,
        ]);
    }
}
