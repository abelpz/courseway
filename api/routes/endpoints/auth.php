<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Firebase\JWT\JWT;

use Symfony\Component\Validator\Constraints as Assert;
use CourseWay\Validation\Validator;

/**
 * @OA\Post(
 *     path="/auth", tags={"Auth"},
 *     summary="Authorize user",
 *     operationId="userCreateToken",
 *     @OA\RequestBody(
 *          @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"username","password"},
 *                 @OA\Property(
 *                     property="username",
 *                     type="string",
 *                     description="Unique user name"
 *                 ),
 *                 @OA\Property(
 *                     property="password",
 *                     type="string",
 *                     description="Unique user password"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response="201", description="Created"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->post('/auth', function (Request $req, Response $res, array $args) {
    $body = $req->getBody()->getContents();
    $data  = json_decode($body, true);

    //Validate params
    Validator::validate($req, $data, new Assert\Collection([
        'fields' => [
            'username' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'password' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
        ]
    ]));

    $username = $data['username'];
    $password = $data['password'];

    $user = UserManager::getManager()->findUserByUsername($username);
    if (!$user)
        throwException($req, '404', 'User not found.');

    $isUserValid = UserManager::isPasswordValid(
        $user->getPassword(),
        $password,
        $user->getSalt()
    );
    if (!$isUserValid)
        throwException($req, '401', 'Invalid password.');

    if (!$user->isSuperAdmin())
        throwException($req, '401', 'User must be an administrator.');

    $now = new DateTime();
    $future = new DateTime("now +12 hours");

    $jti = base64_encode(random_bytes(16));

    $payload = [
        "iat" => $now->getTimeStamp(),
        "exp" => $future->getTimeStamp(),
        "jti" => $jti,
        "uname" => $username,
        "uid" => $user->getId(),
    ];

    $secret = $_ENV["JWT_SECRET"];
    $token = JWT::encode($payload, $secret, "HS256");

    $data["token"] = $token;
    $data["expires"] = $future->getTimeStamp();

    $res->getBody()
        ->write(json_encode($data, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    return
        $res->withStatus(201)
        ->withHeader("Content-Type", "application/json");
});
