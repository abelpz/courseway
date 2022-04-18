<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * @OA\Get(
 *     path="/course/{course_code}/documents", tags={"Documents"},
 *     summary="Get list of documents in course",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the learning path documents are located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *     ),
 *     @OA\Parameter(
 *          description="path to retrieve documents from (i.e. '/images/gallery')",
 *          in="query",
 *          name="path",
 *     ),
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="401", description="Unauthorized"),
 *     @OA\Response(response="400", description="Bad request")
 * )
 */

$endpoint->get('/course/{course_code}/documents', function (Request $req, Response $res, $args) use ($endpoint) {
    $params = $req->getQueryParams();
    $token = $req->getAttribute("token");
    $user = UserManager::getManager()->findUserByUsername($token['uname']);

    if (empty($args['course_code'])) {
        $res->withHeader("Content-Type", "application/json");
        $res->withStatus(400);
        $res->getBody()
            ->write(slim_msg('error', 'You are required to provide: course_code'));
        return $res;
    }

    if (!$user->isSuperAdmin()) {
        $res->withHeader("Content-Type", "application/json");
        $res->getBody()
            ->write(slim_msg('error', 'You need to have admin role to access this.'));
    }

    $course = api_get_course_info($args['course_code']);
    if (!$course) {
        $res->withHeader("Content-Type", "application/json");
        $res->withStatus(400);
        $res->getBody()
            ->write(slim_msg('error', 'Course code not found'));
        return $res;
    }

    $path = $params['path'];

    $documents = DocumentManager::getAllDocumentData($course, $path);

    $res->withStatus(200);
    $res->withHeader("Content-Type", "application/json");
    $res->getBody()->write(json_encode($documents, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return $res;
});

/**
 * @OA\Post(
 *     path="/course/{course_code}/documents/image", tags={"Documents"},
 *     summary="Upload an image into a course",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the learning path section will be added.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *     ),
 *     @OA\RequestBody(
 *          @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                   @OA\Property(
 *                   description="image to upload",
 *                   property="imageUpload",
 *                   type="string",
 *                   format="binary",
 *                  ),
 *                  @OA\Property(
 *                     property="title",
 *                     type="string",
 *                 ),
 *                 @OA\Property(
 *                     property="comment",
 *                     type="string",
 *                 ),
 *              )
 *         ),
 *          @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={},
 *                 @OA\Property(
 *                     property="title",
 *                     type="string",
 *                     description="<small>Title of the document.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="content",
 *                     type="string",
 *                     description="<small>Content of the document.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="parent_id",
 *                     type="integer",
 *                     description="<small>The parent section for this document.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="previous_id",
 *                     type="integer",
 *                     description="<small>id of the document that will be before this one. Should be the same as parent_id section if this will be the first subsection of a section.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="creator_id",
 *                     type="integer",
 *                     description="<small>The user id of the creator of this document.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="prerequisite",
 *                     type="integer",
 *                     description="<small>Id of document that has to be completed by student to access this one.</small>"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="401", description="Unauthorized"),
 *     @OA\Response(response="400", description="Bad request")
 * )
 */

$endpoint->post('/course/{course_code}/documents/image', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = $req->getParsedBody();
    $token = $req->getAttribute("token");
    $userId = $token['uid'];
    $uploadedFiles = $req->getUploadedFiles() ? $_FILES : null;

    $user = UserManager::getManager()->findUserByUsername($token['uname']);

    if (empty($args['course_code'])) {
        $res->withHeader("Content-Type", "application/json");
        $res->withStatus(400);
        $res->getBody()
        ->write(slim_msg('error', 'You are required to provide: course_code.'));
        return $res;
    }

    if (!$user->isSuperAdmin()) {
        $res->withHeader("Content-Type", "application/json");
        $res->withStatus(401);
        $res->getBody()
        ->write(slim_msg('error', 'You need to have admin role to access this.'));
        return $res;
    }

    $course = api_get_course_info($args['course_code']);
    if(!$course){
        $res->withHeader("Content-Type", "application/json");
        $res->withStatus(400);
        $res->getBody()
            ->write(slim_msg('error', 'Course code not found'));
        return $res;
    }

    $result = DocumentManager::upload_document(
        $uploadedFiles,
        '/images',
        $data['title'] ?: '',
        $data['comment'] ?: '',
        0,
        'rename',
        false,
        false,
        'imageUpload',
        true,
        $userId,
        $course
    );

    if (!$result) {
        $res->withHeader("Content-Type", "application/json");
        $res->withStatus(400);
        $res->getBody()
            ->write(slim_msg('error', 'Image could not be uploaded'));
        return $res;
    }

    $res->withHeader("Content-Type", "application/json");
    $res->withStatus(200);
    $res->getBody()
        ->write(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return $res;
});

/**
 * @OA\Get(
 *     path="/course/{course_code}/learningpath/{learningpath_id}/documents", tags={"Documents"},
 *     summary="Get list of documents in learning path",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the learning path documents are located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *     ),
 *     @OA\Parameter(
 *          description="unique int identifier of the learning path in which the document is located.",
 *          in="path",
 *          name="learningpath_id",
 *          required=true,
 *     ),
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="401", description="Unauthorized"),
 *     @OA\Response(response="400", description="Bad request")
 * )
 */

$endpoint->get('/course/{course_code}/learningpath/{learningpath_id}/documents', function (Request $req, Response $res, $args) use ($endpoint) {
    $params = $req->getQueryParams();

    $token = $req->getAttribute("token");
    $user = UserManager::getManager()->findUserByUsername($token['uname']);

    if (empty($args['course_code'])) {
        $res->withHeader("Content-Type", "application/json");
        $res->withStatus(400);
        $res->getBody()
            ->write(slim_msg('error', 'You are required to provide: course_code'));
        return $res;
    }

    $userId = $token['uid'];
    $learningpath = new learnpath(
        $args['course_code'],
        $args['learningpath_id'],
        $userId
    );

    if ($user->isSuperAdmin()) {
        $items = $learningpath->items;
        foreach ($items as $id => $documentItem) {

            if ($documentItem->get_type() == 'document') {

                $documentItem->set_path(api_get_path(SYS_COURSE_PATH) . $args['course_code'] . '/' . $documentItem->get_file_path());

                // $dom = new DomDocument();
                // $dom->loadHTML($documentItem->output());
                // echo $dom->saveHTML($dom->getElementsByTagName('body')[0]);

                $documents[$id] = [
                    "title" => $documentItem->get_title(),
                    "content" => $documentItem->output(),
                    "display_order" => $documentItem->display_order,
                    "parent_id" => $documentItem->get_parent(),
                    "path" => $documentItem->get_file_path()
                ];
            }
        }
        $res->withHeader("Content-Type", "application/json");
        $res->getBody()->write(json_encode($documents, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    } else {
        $res->withHeader("Content-Type", "application/json");
        $res->getBody()
            ->write(slim_msg('error', 'You need to have admin role to access this.'));
    }

    return $res;
});

/**
 * @OA\Post(
 *     path="/course/{course_code}/learningpath/{learningpath_id}/document", tags={"Documents"},
 *     summary="Create a document in a learning path",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the learning path section will be added.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *     ),
 *     @OA\Parameter(
 *          description="unique int identifier of the learning path in which the section will be added.",
 *          in="path",
 *          name="learningpath_id",
 *          required=true,
 *     ),
 *     @OA\RequestBody(
 *          @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"title"},
 *                 @OA\Property(
 *                     property="title",
 *                     type="string",
 *                     description="<small>Title of the document.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="content",
 *                     type="string",
 *                     description="<small>Content of the document.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="parent_id",
 *                     type="integer",
 *                     description="<small>The parent section for this document.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="previous_id",
 *                     type="integer",
 *                     description="<small>id of the document that will be before this one. Should be the same as parent_id section if this will be the first subsection of a section.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="creator_id",
 *                     type="integer",
 *                     description="<small>The user id of the creator of this document.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="prerequisite",
 *                     type="integer",
 *                     description="<small>Id of document that has to be completed by student to access this one.</small>"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="401", description="Unauthorized"),
 *     @OA\Response(response="400", description="Bad request")
 * )
 */

$endpoint->post('/course/{course_code}/learningpath/{learningpath_id}/document', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = json_decode($req->getBody()->getContents(), true);
    $token = $req->getAttribute("token");
    $userId = $token['uid'];

    $user = UserManager::getManager()->findUserByUsername($token['uname']);

    if (empty($args['course_code']) or empty($args['learningpath_id'] or $data['title'])) {
        $res->withHeader("Content-Type", "application/json");
        $res->withStatus(400);
        $res->getBody()
            ->write(slim_msg('error', 'You are required to provide: course_code, learningpath_id, title.'));
        return $res;
    }

    if ($user->isSuperAdmin()) {

        $learningpath = new learnpath(
            $args['course_code'],
            $args['learningpath_id'],
            $userId
        );

        //Parameters for document
        $courseInfo = api_get_course_info($args['course_code']);
        $parentId = $data['parent_id'] ?: 0;
        $extension = 'html';
        $creatorId = $data['creator_id'] ?: 0;
        $title = $data['title'];
        $content = $data['content'] ?: '';

        $documentId = $learningpath->create_document(
            $courseInfo,
            $content,
            $title,
            $extension,
            $parentId,
            $creatorId
        );

        //Parameters for lp_item
        $previous = $data['previous_id'] ?: $learningpath->getLastInFirstLevel();
        $type = 'document';
        $description = '';
        $prerequisites = $data['prerequisite'] ?: 0;
        $max_time_allowed = 0;

        $lpDocument = $learningpath->add_item(
            $parentId,
            $previous,
            $type,
            $documentId,
            $title,
            $description,
            $prerequisites,
            $max_time_allowed,
            $userId
        );

        if ($lpDocument) {
            $res->withHeader("Content-Type", "application/json");
            $res->withStatus(200);
            $res->getBody()
                ->write(json_encode($lpDocument, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        } else {
            $res->withHeader("Content-Type", "application/json");
            $res->withStatus(400);
            $res->getBody()
                ->write(slim_msg('error', 'Learningpath Category could not be created'));
        }
    } else {
        $res->withHeader("Content-Type", "application/json");
        $res->withStatus(401);
        $res->getBody()
            ->write(slim_msg('error', 'You need to have admin role to access this.'));
    }

    return $res;
});