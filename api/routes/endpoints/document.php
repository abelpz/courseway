<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Symfony\Component\Validator\Constraints as Assert;
use CourseWay\Validation\Validator;

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
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="path to retrieve documents from (i.e. '/images/gallery')",
 *          in="query",
 *          name="path",
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->get('/course/{course_code}/documents', function (Request $req, Response $res, $args) use ($endpoint) {
    $params = $req->getQueryParams();
    //Validate params
    Validator::validate($req, array_merge($params, $args), new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'path' => new Assert\Optional([
                new Assert\Type('string')
            ]),
        ]
    ]));

    $course = api_get_course_info($args['course_code']);
    if (!$course)
        throwException($req, '404', "Course with code {$args['course_code']} not found.");

    $path = $params['path'];

    $documents = DocumentManager::getAllDocumentData($course, $path);

    $res->getBody()
        ->write(json_encode($documents, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return
        $res->withHeader("Content-Type", "application/json")
        ->withStatus(200);
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
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\RequestBody(
 *          @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                  required={"imageUpload"},
 *                  @OA\Property(
 *                     description="image to upload",
 *                     property="imageUpload",
 *                     type="string",
 *                     format="binary",
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
 *     ),
 *     @OA\Response(response="201", description="Created"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->post('/course/{course_code}/documents/image', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = $req->getParsedBody();
    $uploadedFiles = $req->getUploadedFiles() ? $_FILES : [];

    Validator::validate($req, array_merge($data, $args, $uploadedFiles), new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'imageUpload' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('array'),
                new Assert\All([
                    new Assert\NotBlank()
                ]),
            ]),
            'title' => new Assert\Optional([new Assert\Type('string')]),
            'comment' => new Assert\Optional([new Assert\Type('string')]),
        ]
    ]));

    $course = api_get_course_info($args['course_code']);
    if (!$course)
        throwException($req, '404', "Course with code {$args['course_code']} not found.");

    $token = $req->getAttribute("token");
    $userId = $token['uid'];
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

    var_dump($uploadedFiles);

    if (!$result)
        throwException($req, '422', "Image coud not be uploaded.");

    $res->getBody()
        ->write(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    return
        $res->withHeader("Content-Type", "application/json")
        ->withStatus(201);
});


/**
 * @OA\Post(
 *     path="/course/{course_code}/document", tags={"Documents"},
 *     summary="Upload a document into a course",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the learning path section will be added.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\RequestBody(
 *          @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                  required={"file"},
 *                  @OA\Property(
 *                     description="file to upload",
 *                     property="file",
 *                     type="string",
 *                     format="binary",
 *                  ),
 *                 @OA\Property(
 *                     description="Path to the uploaded file",
 *                     property="path",
 *                     type="string",
 *                 ),
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
 *     ),
 *     @OA\Response(response="201", description="Created"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->post('/course/{course_code}/document', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = $req->getParsedBody();
    $uploadedFiles = $req->getUploadedFiles() ? $_FILES : [];

    Validator::validate($req, array_merge($data, $args, $uploadedFiles), new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'file' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('array'),
                new Assert\All([
                    new Assert\NotBlank()
                ]),
            ]),
            'path' => new Assert\Optional([new Assert\Type('string')]),
            'title' => new Assert\Optional([new Assert\Type('string')]),
            'comment' => new Assert\Optional([new Assert\Type('string')]),
        ]
    ]));

    $course = api_get_course_info($args['course_code']);
    if (!$course)
        throwException($req, '404', "Course with code {$args['course_code']} not found.");

    $token = $req->getAttribute("token");
    $userId = $token['uid'];
    $path = $data['path'] ?: "/";
    $result = DocumentManager::upload_document(
        $uploadedFiles,
        $path,
        $data['title'] ?: '',
        $data['comment'] ?: '',
        0,
        'rename',
        false,
        false,
        'file',
        true,
        $userId,
        $course
    );

    if (!$result)
        throwException($req, '422', "File coud not be uploaded.");

    $res->getBody()
        ->write(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    return
        $res->withHeader("Content-Type", "application/json")
        ->withStatus(201);
});

/**
 * @OA\Get(
 *     path="/course/{course_code}/document/{document_id}", tags={"Documents"},
 *     summary="Get a document from a course.",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the documend is located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique int identifier of the requested document.",
 *          in="path",
 *          name="document_id",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Response(response="200", description="Created"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->get('/course/{course_code}/document/{document_id}', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = $req->getParsedBody();
    $uploadedFiles = $req->getUploadedFiles() ? $_FILES : [];

    Validator::validate($req, array_merge($data, $args, $uploadedFiles), new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string'),
            ]),
            'document_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('numeric'),
            ]),
        ]
    ]));

    $course = api_get_course_info($args['course_code']);
    if (!$course)
        throwException($req, '404', "Course with code {$args['course_code']} not found.");

    $result = DocumentManager::get_document_data_by_id($args['document_id'], $args['course_code']);

    if (!$result)
        throwException($req, '404', "Document not found.");

    $res->getBody()
        ->write(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    return
        $res->withHeader("Content-Type", "application/json")
        ->withStatus(200);
});

/**
 * @OA\Delete(
 *     path="/course/{course_code}/document/{document_id}", tags={"Documents"},
 *     summary="Soft delete a document from a course.",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the documend is located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique int identifier of the requested document.",
 *          in="path",
 *          name="document_id",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Response(response="200", description="Created"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->delete('/course/{course_code}/document/{document_id}', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = $req->getParsedBody();
    $uploadedFiles = $req->getUploadedFiles() ? $_FILES : [];

    Validator::validate($req, array_merge($data, $args, $uploadedFiles), new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string'),
            ]),
            'document_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('numeric'),
            ]),
        ]
    ]));

    $course = api_get_course_info($args['course_code']);
    if (!$course)
        throwException($req, '404', "Course with code {$args['course_code']} not found.");

    $document = DocumentManager::get_document_data_by_id($args['document_id'], $args['course_code']);
    if (!$document)
        throwException($req, '404', "Document not found.");

    $result = DocumentManager::delete_document($course, null, null, null, $args['document_id']);

    if (!$result)
        throwException($req, '422', "Document could not be deleted.");

    return
        $res->withHeader("Content-Type", "application/json")
        ->withStatus(204);
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
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique int identifier of the learning path in which the document is located.",
 *          in="path",
 *          name="learningpath_id",
 *          required=true,
 *          @OA\Schema(type="integer"),
 *     ),
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->get('/course/{course_code}/learningpath/{learningpath_id}/documents', function (Request $req, Response $res, $args) use ($endpoint) {

    Validator::validate($req, $args, new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'learningpath_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('numeric'),
            ]),
        ]
    ]));

    $token = $req->getAttribute("token");
    $userId = $token['uid'];

    $course = api_get_course_info($args['course_code']);


    if (!$course)
        throwException($req, '404', "Course with code {$args['course_code']} not found.");

    $learningpath = new learnpath(
        $args['course_code'],
        $args['learningpath_id'],
        $userId
    );
    if (!$learningpath->name)
        throwException($req, '404', "Learning path with id {$args['learningpath_id']} not found.");

    $items = $learningpath->items;
    if (!$items)
        throwException($req, '422', "Learning path with id {$args['learningpath_id']} is empty.");

    $documents = [];

    foreach ($items as $id => $documentItem) {
        $type = $documentItem->get_type();
        if ($type === 'document' || $type === 'dir') {

            $path = api_get_path(SYS_COURSE_PATH) . $args['course_code'] . '/' . $documentItem->get_file_path();
            $documentItem->set_path($path);
            $ext = pathinfo($path)['extension'];
            $content = $ext === 'pdf' ? "" : $documentItem->output();

            $documents[$id] = [
                "title" => $documentItem->get_title(),
                "content" => $content,
                "display_order" => $documentItem->display_order,
                "parent_id" => $documentItem->get_parent(),
                "path" => $documentItem->get_file_path(),
                "type" => $documentItem->get_type(),
            ];
        }
    }

    if (!$documents)
        throwException($req, '422', "No documents found in learning path with id {$args['learningpath_id']}.");

    $res->getBody()
        ->write(json_encode($documents, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return
        $res->withHeader("Content-Type", "application/json")
        ->withStatus(200);
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
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique int identifier of the learning path in which the section will be added.",
 *          in="path",
 *          name="learningpath_id",
 *          required=true,
 *          @OA\Schema(type="integer"),
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
 *     @OA\Response(response="201", description="Created"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->post('/course/{course_code}/learningpath/{learningpath_id}/document', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = json_decode($req->getBody()->getContents(), true);

    Validator::validate($req, array_merge($args, $data), new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'learningpath_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('numeric'),
            ]),
            'title' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string'),
            ]),
            'content' => new Assert\Optional([new Assert\Type('string')]),
            'parent_id' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'previous_id' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'creator_id' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'prerequisite' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()])
        ]
    ]));

    $course = api_get_course_info($args['course_code']);
    if (!$course)
        throwException($req, '404', "Course with code {$args['course_code']} not found.");

    $token = $req->getAttribute("token");
    $userId = $token['uid'];

    $learningpath = new learnpath(
        $args['course_code'],
        $args['learningpath_id'],
        $userId
    );

    if (!$learningpath->name)
        throwException($req, '404', "Learning path with id {$args['learningpath_id']} not found.");

    //Parameters for document
    $parentId = $data['parent_id'] ?: 0;
    $extension = 'html';
    $creatorId = $data['creator_id'] ?: 0;
    $title = $data['title'];
    $content = $data['content'] ?: '';

    $documentId = $learningpath->create_document(
        $course,
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

    $lpItemId = $learningpath->add_item(
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

    $result = DocumentManager::get_document_data_by_id($documentId, $args['course_code']);
    $result['lp_item'] = $learningpath->getItem($lpItemId);

    if (!$lpItemId)
        throwException($req, '422', "Learningpath document could not be created.");

    $res->getBody()
        ->write(json_encode($result, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return
        $res->withHeader("Content-Type", "application/json")
        ->withStatus(201);
});
