<?php

namespace App\Http\Controllers;

use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class ClapController extends Controller
{
    #[OA\Post(
        path: "/clap/{post}",
        summary: "Clap or unclap a post",
        tags: ["Posts"],
        security: [["csrf_token" => []]],
        parameters: [
            new OA\Parameter(name: "post", in: "path", required: true, schema: new OA\Schema(type: "integer"))
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successfully toggled clap status",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "clapsCount", type: "integer", example: 10)
                    ]
                )
            )
        ]
    )]
    public function clap(Post $post)
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $hasClapped = $user->hasClapped($post);

        if ($hasClapped) {
            $post->claps()->where('user_id', Auth::id())->delete();
        } else {
            $post->claps()->create([
                'user_id' => Auth::id(),
            ]);
        }

        return response()->json([
            'clapsCount' => $post->claps()->count(),
        ]);
    }
}
