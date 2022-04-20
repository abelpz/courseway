<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * @OA\Get(
 *     path="/users", tags={"Users"},
 *     summary="Get list of users",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="The user id",
 *          in="query",
 *          name="id",
 *          @OA\Schema(
 *              type="integer"
 *          ),
 *          required=false,
 *     ),
 *     @OA\Parameter(
 *          description="The username",
 *          in="query",
 *          name="username",
 *          required=false,
 *          @OA\Schema(
 *              type="string"
 *          ),
 *     ),
 *     @OA\Parameter(
 *          description="The user email",
 *          in="query",
 *          name="email",
 *          required=false,
 *          @OA\Schema(
 *              type="string"
 *          ),
 *     ),
 *     @OA\Parameter(
 *          description="The user status.
 *   1: Trainer
 *   5: Learner
 *   4: Human Resources Manager
 *   3: Sessions administrator
 *   17: Student's superior
 *   20: Invitee",
 *          in="query",
 *          name="status",
 *          required=false,
 *          @OA\Schema(
 *              type="integer"
 *          ),
 *     ),
 *     @OA\Response(response="201", description="Created"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->get('/users', function (Request $req, Response $res, $args) use ($endpoint) {
    $params = $req->getQueryParams();
    $token = $req->getAttribute("token");
    $user = UserManager::getManager()->findUserByUsername($token['uname']);

    if ($user->isSuperAdmin()) {

        $users = UserManager::get_user_list($params);
        foreach ($users as $key => $user) {
            $users_info[$key] = array_filter($user, function ($user_field) {
                return in_array($user_field, ["user_id", "username", "firstname", "lastname", "email"]) && $user_field != NULL;
            }, ARRAY_FILTER_USE_KEY);
        }
        $res->withHeader("Content-Type", "application/json");
        $res->getBody()->write(json_encode($users_info, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    } else {
        $res->withHeader("Content-Type", "application/json");
        $res->getBody()
            ->write(slim_msg('error', 'You need to have admin role to access this.'));
    }
    return $res;
});
