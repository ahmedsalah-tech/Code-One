<?php

namespace App\Http\Controllers\Docs;

use OpenApi\Annotations as OA;

/**
 * @OA\Tag(name="Debug")
 */
class DebugDocs
{
    /**
     * Show current session information.
     *
     * @OA\Get(
     *   path="/debug/session",
     *   tags={"Debug"},
     *   summary="Inspect session configuration and ID",
     *   description="Returns the current session ID and session/redis configuration.",
     *   @OA\Response(
     *     response=200,
     *     description="Successful response",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="session_id", type="string", example="bfSzs6M2bBejv5lCw3xZP5kZp5QzqKpFhG6h1z3A"),
     *       @OA\Property(property="driver", type="string", example="redis"),
     *       @OA\Property(property="connection", type="string", nullable=true, example="default"),
     *       @OA\Property(property="store", type="string", nullable=true, example=null),
     *       @OA\Property(property="redis_prefix", type="string", example="laravel_database_"),
     *       @OA\Property(property="redis_host", type="string", example="127.0.0.1"),
     *       @OA\Property(property="redis_port", type="string", example="6379")
     *     )
     *   )
     * )
     */
    public function debugSessionDocs()
    {
        // Documentation-only class; no runtime code.
    }

    /**
     * Show authenticated cache lookup status.
     *
     * @OA\Get(
     *   path="/debug/auth-cache",
     *   tags={"Debug"},
     *   summary="Check DB query count for auth user",
     *   description="Returns the authenticated user's ID, current session ID, and number of DB queries executed when resolving the user.",
     *   @OA\Response(
     *     response=200,
     *     description="Successful response",
     *     @OA\JsonContent(
     *       type="object",
     *       @OA\Property(property="user_id", type="integer", example=42),
     *       @OA\Property(property="session_id", type="string", example="bfSzs6M2bBejv5lCw3xZP5kZp5QzqKpFhG6h1z3A"),
     *       @OA\Property(property="query_count", type="integer", example=0),
     *       @OA\Property(
     *         property="queries",
     *         type="array",
     *         @OA\Items(type="object")
     *       )
     *     )
     *   )
     * )
     */
    public function debugAuthCacheDocs()
    {
        // Documentation-only class; no runtime code.
    }
}
