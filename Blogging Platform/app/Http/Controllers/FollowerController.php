<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use OpenApi\Attributes as OA;

class FollowerController extends Controller
{
    #[OA\Post(
        path: "/follow/{user}",
        summary: "Follow or unfollow a user",
        tags: ["Followers"],
        security: [["csrf_token" => []]],
        parameters: [
            new OA\Parameter(
                name: "user",
                in: "path",
                required: true,
                description: "The ID of the user to follow/unfollow",
                schema: new OA\Schema(type: "integer")
            )
        ],
        responses: [
            new OA\Response(
                response: 200,
                description: "Successfully toggled follow status",
                content: new OA\JsonContent(
                    properties: [
                        new OA\Property(property: "followersCount", type: "integer", example: 5)
                    ]
                )
            )
        ]
    )]
    public function followUnfollow(User $user)
    {
        $user->followers()->toggle(Auth::user());

        return response()->json([
            'followersCount' => $user->followers()->count(),
        ]);
    }
}
