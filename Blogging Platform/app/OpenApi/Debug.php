<?php

namespace App\OpenApi;

use OpenApi\Annotations as OA;

/**
 * @OA\Info(
 *   title="Laravel Medium Clone API",
 *   version="1.0.0"
 * )
 *
 * @OA\Tag(name="Debug", description="Debug utilities")
 */
final class Debug
{
    /**
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
     *       @OA\Property(property="session_id", type="string"),
     *       @OA\Property(property="driver", type="string"),
     *       @OA\Property(property="connection", type="string", nullable=true),
     *       @OA\Property(property="store", type="string", nullable=true),
     *       @OA\Property(property="redis_prefix", type="string"),
     *       @OA\Property(property="redis_host", type="string"),
     *       @OA\Property(property="redis_port", type="string")
     *     )
     *   )
     * )
     */
    public function debugSessionDocs(): void {}

    /**
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
     *       @OA\Property(property="user_id", type="integer"),
     *       @OA\Property(property="session_id", type="string"),
     *       @OA\Property(property="DB_query_count", type="integer"),
     *       @OA\Property(property="queries", type="array", @OA\Items(type="object"))
     *     )
     *   )
     * )
     */
    public function debugAuthCacheDocs(): void {}
}
