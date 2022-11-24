<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Symfony\Component\Validator\Constraints as Assert;
use CourseWay\Validation\Validator;

/**
 * @OA\Get(
 *     path="/course/{course_code}/learningpaths", tags={"Learning Paths"},
 *     summary="Get list of learning paths from course",
 *     security={{"bearerAuth": {}}},
 *     operationId="lpGetList",
 *     @OA\Parameter(
 *          description="the id of the course from which to list the learning paths",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->get('/course/{course_code}/learningpaths', function (Request $req, Response $res, $args) use ($endpoint) {

    Validator::validate($req, $args, new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
        ]
    ]));

    $course = api_get_course_info($args['course_code']);
    if (!$course)
        throwException($req, '404', "Course with code `{$args['course_code']}` not found.");

    $courseId = $course['real_id'];
    $sessionId = 0;

    $learningpaths = learnpath::getLpList($courseId, $sessionId);

    $res->getBody()->write(json_encode($learningpaths, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return $res
        ->withHeader("Content-Type", "application/json")
        ->withStatus(200);
});

/**
 * @OA\Post(
 *     path="/course/{course_code}/learningpath", tags={"Learning Paths"},
 *     summary="Create a learning path in a course",
 *     security={{"bearerAuth": {}}},
 *     operationId="lpCreate",
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the learning path will be added.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\RequestBody(
 *          @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"name"},
 *                 @OA\Property(
 *                     property="name",
 *                     type="string",
 *                     description="<small>Name of the learning path.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="description",
 *                     type="string",
 *                     description="<small>Description for this learning path.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="category_id",
 *                     type="integer",
 *                     description="<small>The category for this learning path.</small>"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response="201", description="Created"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->post('/course/{course_code}/learningpath', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = json_decode($req->getBody()->getContents(), true);

    Validator::validate($req, array_merge($data, $args), new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'name' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'description' => new Assert\Optional([new Assert\Type('string')]),
            'category_id' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
        ],
        'allowExtraFields' => true
    ]));

    $courseCode = $args['course_code'];
    $course = api_get_course_info($courseCode);
    if (!$course)
        throwException($req, '404', "Course with code `{$courseCode}` not found.");

    $name = $data['name'];
    $description = $data['description'] ?: '';
    $learnpath = 'guess';
    $origin = '';
    $zipname = '';
    $publicated_on = api_get_utc_datetime();
    $expired_on = '';
    $categoryId = $data['category_id'] ?: 0;

    $token = $req->getAttribute("token");
    $userId = $token['uid'] ?: 0;

    if ($categoryId !== 0) {
        $category = learnpath::getCategory($categoryId);
        if (!$category)
            throwException($req, '404', "Category with id {$categoryId} not found in DB.");

        if ($category->getCId() !== $course['real_id'])
            throwException($req, '422', "Category with id {$categoryId} not found in course.");
    }

    $learningpathId = (int) learnpath::add_lp(
        $courseCode,
        $name,
        $description,
        $learnpath,
        $origin,
        $zipname,
        $publicated_on,
        $expired_on,
        $categoryId,
        $userId
    );

    if (!$learningpathId)
        throwException($req, '422', "Learning path could not be created.");

    $lp_table = Database::get_course_table(TABLE_LP_MAIN);
    $sql = "SELECT * FROM $lp_table WHERE iid = $learningpathId";
    $result = Database::query($sql);
    $learningpath = Database::store_result($result, 'ASSOC');

    $res->getBody()->write(json_encode($learningpath, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return $res
        ->withHeader("Content-Type", "application/json")
        ->withStatus(201);
});

/**
 * @OA\Get(
 *     path="/course/{course_code}/learningpath/{learningpath_id}", tags={"Learning Paths"},
 *     summary="Get a learning path from a course",
 *     security={{"bearerAuth": {}}},
 *     operationId="lpGet",
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the learning is located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique int identifier of the learning path that will be deleted.",
 *          in="path",
 *          name="learningpath_id",
 *          required=true,
 *          @OA\Schema(type="integer"),
 *     ),
 *     @OA\Response(response="204", description="No content."),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->get('/course/{course_code}/learningpath/{learningpath_id}', function (Request $req, Response $res, $args) use ($endpoint) {

    Validator::validate($req, $args, new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'learningpath_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('numeric')
            ]),
        ]
    ]));

    $courseCode = $args['course_code'];
    $course = api_get_course_info($courseCode);
    if (!$course)
        throwException($req, '404', "Course with code `{$courseCode}` not found.");

    $lp_table = Database::get_course_table(TABLE_LP_MAIN);
    $sql = "SELECT * FROM $lp_table WHERE iid = {$args['learningpath_id']} AND c_id = {$course['real_id']}";
    $result = Database::query($sql);
    $learningpath = Database::store_result($result, 'ASSOC');
    if (!$learningpath)
        throwException($req, '404', "Learning path not found in course.");


    $res->getBody()->write(json_encode($learningpath, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return $res
        ->withHeader("Content-Type", "application/json")
        ->withStatus(200);
});

/**
 * @OA\Delete(
 *     path="/course/{course_code}/learningpath/{learningpath_id}", tags={"Learning Paths"},
 *     summary="Delete a learning path in a course",
 *     security={{"bearerAuth": {}}},
 *     operationId="lpDelete",
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the learning is located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique int identifier of the learning path that will be deleted.",
 *          in="path",
 *          name="learningpath_id",
 *          required=true,
 *          @OA\Schema(type="integer"),
 *     ),
 *     @OA\Response(response="204", description="No content."),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->delete('/course/{course_code}/learningpath/{learningpath_id}', function (Request $req, Response $res, $args) use ($endpoint) {

    Validator::validate($req, $args, new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'learningpath_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('numeric')
            ]),
        ]
    ]));

    $courseCode = $args['course_code'];
    $course = api_get_course_info($courseCode);
    if (!$course)
        throwException($req, '404', "Course with code `{$courseCode}` not found.");

    $lp_table = Database::get_course_table(TABLE_LP_MAIN);
    $sql = "SELECT * FROM $lp_table WHERE iid = {$args['learningpath_id']} AND c_id = {$course['real_id']}";
    $result = Database::query($sql);
    $learningpath = Database::store_result($result, 'ASSOC');
    if (!$learningpath)
        throwException($req, '422', "Learning path not found in course.");

    $token = $req->getAttribute("token");
    $userId = $token['uid'];
    $learningpath = new learnpath(
        $courseCode,
        $args['learningpath_id'],
        $userId
    );
    if ($learningpath->delete($course) === false)
        throwException($req, '422', "Learning path could not be deleted.");

    return $res->withStatus(204);
});

/**
 * @OA\Get(
 *     path="/course/{course_code}/learningpaths/categories", tags={"Learning Paths"},
 *     summary="List learning paths categories from course",
 *     security={{"bearerAuth": {}}},
 *     operationId="lpGetCategories",
 *      @OA\Parameter(
 *          description="the id of the course from which to list the learning path categories",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->get('/course/{course_code}/learningpaths/categories', function (Request $req, Response $res, $args) use ($endpoint) {
    $token = $req->getAttribute("token");

    Validator::validate($req, $args, new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
        ]
    ]));

    $courseCode = $args['course_code'];
    $course = api_get_course_info($courseCode);
    if (!$course)
        throwException($req, '404', "Course with code `{$courseCode}` not found.");

    $courseId = $course['real_id'];

    $lpCategories = learnpath::getCategories($courseId);
    $categories = [];
    foreach ($lpCategories as $category) {
        array_push($categories, [
            'id' => $category->getId(),
            'name' => $category->getName(),
            'c_id' => $category->getCId(),
            'position' => $category->getPosition()
        ]);
    }

    $res->getBody()->write(json_encode($categories, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return $res
        ->withHeader("Content-Type", "application/json")
        ->withStatus(200);
});

/**
 * @OA\Post(
 *     path="/course/{course_code}/learningpaths/category", tags={"Learning Paths"},
 *     summary="Create a learning path category in a course",
 *     security={{"bearerAuth": {}}},
 *     operationId="lpCreateCategory",
 *     @OA\Parameter(
 *          description="unique int identifier of the course in which the learning path category will be added.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\RequestBody(
 *          @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"name"},
 *                 @OA\Property(
 *                     property="name",
 *                     type="string",
 *                     description="<small>Learning path category name.</small>"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response="201", description="Created"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->post('/course/{course_code}/learningpaths/category', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = json_decode($req->getBody()->getContents(), true);

    Validator::validate($req, array_merge($data, $args), new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'name' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
        ],
    ]));

    $course = api_get_course_info($args['course_code']);
    if (!$course)
        throwException($req, '404', "Course with code `{$args['course_code']}` not found.");

    $data['c_id'] = $course['real_id'];

    $lpCategoryId = learnpath::createCategory($data);
    if (!$lpCategoryId)
        throwException($req, '422', "Learningpath Category could not be created.");

    $lp_table = Database::get_course_table(TABLE_LP_CATEGORY);
    $sql = "SELECT * FROM $lp_table WHERE iid = {$lpCategoryId} AND c_id = {$data['c_id']}";
    $result = Database::query($sql);
    $lpCategory = Database::store_result($result, 'ASSOC');

    $res->getBody()->write(json_encode($lpCategory[0], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return $res
        ->withHeader("Content-Type", "application/json")
        ->withHeader("Location", COURSEWAY_API_URI . "/course/{$args['course_code']}/learningpaths/category/{$lpCategoryId}")
        ->withStatus(201);
});

/**
 * @OA\Get(
 *     path="/course/{course_code}/learningpaths/category/{category_id}", tags={"Learning Paths"},
 *     summary="Get a learning path category from a course",
 *     security={{"bearerAuth": {}}},
 *     operationId="lpGetCategory",
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the learning path category is located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique int identifier of the requested learning path category.",
 *          in="path",
 *          name="category_id",
 *          required=true,
 *          @OA\Schema(type="integer"),
 *     ),
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->get('/course/{course_code}/learningpaths/category/{category_id}', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = json_decode($req->getBody()->getContents(), true);

    Validator::validate($req, array_merge($data, $args), new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'category_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('numeric')
            ]),
        ],
    ]));

    $course = api_get_course_info($args['course_code']);
    if (!$course)
        throwException($req, '404', "Course with code `{$args['course_code']}` not found.");

    $lp_table = Database::get_course_table(TABLE_LP_CATEGORY);
    $sql = "SELECT * FROM $lp_table WHERE iid = {$args['category_id']} AND c_id = {$course['real_id']}";
    $result = Database::query($sql);
    $lpCategory = Database::store_result($result, 'ASSOC');
    if (!$lpCategory)
        throwException($req, '404', "Learningpath Category not found.");


    $res->getBody()->write(json_encode($lpCategory[0], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return $res
        ->withHeader("Content-Type", "application/json")
        ->withStatus(200);
});

/**
 * @OA\Delete(
 *     path="/course/{course_code}/learningpaths/category/{category_id}", tags={"Learning Paths"},
 *     summary="Delete a learning path category from a course",
 *     security={{"bearerAuth": {}}},
 *     operationId="lpDeleteCategory",
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the learning path category is located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique int identifier of the requested learning path category.",
 *          in="path",
 *          name="category_id",
 *          required=true,
 *          @OA\Schema(type="integer"),
 *     ),
 *     @OA\Response(response="204", description="Success"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->delete('/course/{course_code}/learningpaths/category/{category_id}', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = json_decode($req->getBody()->getContents(), true);

    Validator::validate($req, array_merge($data, $args), new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'category_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('numeric')
            ]),
        ],
    ]));

    $course = api_get_course_info($args['course_code']);
    if (!$course)
        throwException($req, '404', "Course with code `{$args['course_code']}` not found.");

    $lp_table = Database::get_course_table(TABLE_LP_CATEGORY);
    $sql = "SELECT * FROM $lp_table WHERE iid = {$args['category_id']} AND c_id = {$course['real_id']}";
    $result = Database::query($sql);
    $lpCategory = Database::store_result($result, 'ASSOC');
    if (!$lpCategory)
        throwException($req, '404', "Learningpath Category not found.");

    $deleted = learnpath::deleteCategory($args['category_id']);
    if (!$deleted)
        throwException($req, '422', "Learning path category could not be deleted.");

    return $res
        ->withHeader("Content-Type", "application/json")
        ->withStatus(204);
});

/**
 * @OA\Get(
 *     path="/course/{course_code}/learningpath/{learningpath_id}/items", tags={"Learning Paths"},
 *     summary="List items (including sections) from course's learning path",
 *     security={{"bearerAuth": {}}},
 *     operationId="lpGetItems",
 *     @OA\Parameter(
 *          description="unique string identifier of the course from which the learning path sections will be listed.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique int identifier of the learning path from which sections will be listed.",
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

$endpoint->get('/course/{course_code}/learningpath/{learningpath_id}/items', function (Request $req, Response $res, $args) use ($endpoint) {

    Validator::validate($req, $args, new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'learningpath_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('numeric')
            ]),
        ]
    ]));

    $course = api_get_course_info($args['course_code']);
    if (!$course)
        throwException($req, '404', "Course with code `{$args['course_code']}` not found.");

    $token = $req->getAttribute("token");
    $userId = $token['uid'];
    $learningpath = new learnpath(
        $args['course_code'],
        $args['learningpath_id'],
        $userId
    );

    if (!$learningpath->name)
        throwException($req, '404', "Learning path with id {$args['learningpath_id']} not found.");

    $lpSections = $learningpath->items;

    $res->getBody()
        ->write(json_encode($lpSections, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    return
        $res->withHeader("Content-Type", "application/json")
        ->withStatus(200);
});

/**
 * @OA\Post(
 *     path="/course/{course_code}/learningpath/{learningpath_id}/section", tags={"Learning Paths"},
 *     summary="Add section to learning path",
 *     security={{"bearerAuth": {}}},
 *     operationId="lpCreateSection",
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
 *                     description="<small>Learning path section title.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="parent_id",
 *                     type="integer",
 *                     description="<small>If this is a subsection, the id of its parent.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="previous_id",
 *                     type="integer",
 *                     description="<small>id of the section that will be before this one. Should be the same as parent if this will be the first subsection of a section.</small>"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response="201", description="Created"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->post('/course/{course_code}/learningpath/{learningpath_id}/section', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = json_decode($req->getBody()->getContents(), true);


    Validator::validate($req, array_merge($args, $data), new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'learningpath_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('numeric')
            ]),
            'title' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'parent_id' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'previous_id' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
        ]
    ]));

    $course = api_get_course_info($args['course_code']);
    if (!$course)
        throwException($req, '404', "Course with code `{$args['course_code']}` not found.");

    $token = $req->getAttribute("token");
    $userId = $token['uid'];
    $learningpath = new learnpath(
        $args['course_code'],
        $args['learningpath_id'],
        $userId
    );
    if (!$learningpath->name)
        throwException($req, '404', "Learning path with id {$args['learningpath_id']} not found.");

    $parent = $data['parent_id'] ?: 0;
    $previous = $data['previous_id'] ?: array_key_last($learningpath->items);
    $type = 'dir';
    $id = 0;
    $title = $data['title'];
    $description = '';
    $prerequisites = 0;
    $max_time_allowed = 0;

    $lpSection = $learningpath->add_item(
        $parent,
        $previous,
        $type,
        $id,
        $title,
        $description,
        $prerequisites,
        $max_time_allowed,
        $userId
    );
    if (!$lpSection)
        throwException($req, '422', "Learningpath section could not be created.");

    $res->getBody()
        ->write(json_encode($lpSection, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    return
        $res
        ->withHeader("Content-Type", "application/json")
        ->withStatus(201);
});

/**
 * @OA\Patch(
 *     path="/course/{course_code}/learningpath/{learningpath_id}/item", tags={"Learning Paths"},
 *     summary="Creates an item in a learning path from a resource",
 *     operationId="lpAddItem",
 *     security={{"bearerAuth": {}}},
 *     operationId="lpCreateItem",
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
 *                 required={"title","resource_id","resource_type"},
 *                  @OA\Property(
 *                     property="resource_id",
 *                     type="integer",
 *                     description="<small>The id of the resource which will be added.</small>"
 *                 ),
 *                  @OA\Property(
 *                     property="resource_type",
 *                     type="string",
 *                     description="<small>The type of resource to be added. (one of: link|student_publication|dir|quiz|document|forum|thread)</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="title",
 *                     type="string",
 *                     description="<small>Learning path item title.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="description",
 *                     type="string",
 *                     description="<small>Learning path item description.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="prerequisite",
 *                     type="integer",
 *                     description="<small>Id of a learning path item that is required to access this item.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="parent_id",
 *                     type="integer",
 *                     description="<small>If this is a child item, the id of its parent.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="previous_id",
 *                     type="integer",
 *                     description="<small>id of the item that will be before this one. Should be the same as parent section id if this will be the first item of a section.</small>"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response="200", description="Added"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->patch('/course/{course_code}/learningpath/{learningpath_id}/item', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = json_decode($req->getBody()->getContents(), true);

    Validator::validate($req, array_merge($args, $data), new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'learningpath_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('numeric')
            ]),
            'resource_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('numeric')
            ]),
            'resource_type' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string'),
                new Assert\Choice([
                    'choices' => ["link", "student_publication", "dir", "quiz", "document", "forum", "thread"],
                    'message' => 'The resource_type must be one of the following: link,student_publication,dir,quiz,document,forum,thread.',
                ])
            ]),
            'title' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'description' => new Assert\Optional([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'prerequisite' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'parent_id' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'previous_id' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
        ]
    ]));

    $course = api_get_course_info($args['course_code']);
    if (!$course)
        throwException($req, '404', "Course with code `{$args['course_code']}` not found.");

    $token = $req->getAttribute("token");
    $userId = $token['uid'];
    $learningpath = new learnpath(
        $args['course_code'],
        $args['learningpath_id'],
        $userId
    );
    if (!$learningpath->name)
        throwException($req, '404', "Learning path with id {$args['learningpath_id']} not found.");

    $parent = $data['parent_id'] ?: 0;
    $previous = $data['previous_id'] ?: array_key_last($learningpath->items);
    $type = $data['resource_type'];
    $id = $data['resource_id'];
    $title = $data['title'];
    $description = $data['description'] ?: "";
    $prerequisites = $data['prerequisite'] ?: 0;
    $max_time_allowed = 0;

    $lpSection = $learningpath->add_item(
        $parent,
        $previous,
        $type,
        $id,
        $title,
        $description,
        $prerequisites,
        $max_time_allowed,
        $userId
    );

    if (!$lpSection)
        throwException($req, '422', "Learningpath item could not be created.");

    $res->getBody()
        ->write(json_encode($lpSection, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    return
        $res
        ->withHeader("Content-Type", "application/json")
        ->withStatus(201);
});

/**
 * @OA\Get(
 *     path="/course/{course_code}/learningpath/{learningpath_id}/section/{section_id}", tags={"Learning Paths"},
 *     summary="Get section from learning path",
 *     security={{"bearerAuth": {}}},
 *     operationId="lpGetSection",
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the learning path section is located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique int identifier of the learning path in which the section is located",
 *          in="path",
 *          name="learningpath_id",
 *          required=true,
 *          @OA\Schema(type="integer"),
 *     ),
 *     @OA\Parameter(
 *          description="unique int identifier of the requested section.",
 *          in="path",
 *          name="section_id",
 *          required=true,
 *          @OA\Schema(type="integer"),
 *     ),
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->post('/course/{course_code}/learningpath/{learningpath_id}/section/{section_id}', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = json_decode($req->getBody()->getContents(), true);


    Validator::validate($req, array_merge($args, $data), new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'learningpath_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('numeric')
            ]),
            'section_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('numeric')
            ]),
        ]
    ]));

    $course = api_get_course_info($args['course_code']);
    if (!$course)
        throwException($req, '404', "Course with code `{$args['course_code']}` not found.");

    $token = $req->getAttribute("token");
    $userId = $token['uid'];
    $learningpath = new learnpath(
        $args['course_code'],
        $args['learningpath_id'],
        $userId
    );
    if (!$learningpath->name)
        throwException($req, '404', "Learning path with id {$args['learningpath_id']} not found.");

    $lpSection = $learningpath->getItem('section_id');
    if (!$lpSection)
        throwException($req, '404', "Learningpath section not found.");

    $res->getBody()
        ->write(json_encode($lpSection, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    return
        $res
        ->withHeader("Content-Type", "application/json")
        ->withStatus(200);
});

/**
 * @OA\Delete(
 *     path="/course/{course_code}/learningpath/{learningpath_id}/section/{section_id}", tags={"Learning Paths"},
 *     summary="Delete section from learning path",
 *     security={{"bearerAuth": {}}},
 *     operationId="lpDeleteSection",
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the learning path section is located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique int identifier of the learning path in which the section is located",
 *          in="path",
 *          name="learningpath_id",
 *          required=true,
 *          @OA\Schema(type="integer"),
 *     ),
 *     @OA\Parameter(
 *          description="unique int identifier of the requested section.",
 *          in="path",
 *          name="section_id",
 *          required=true,
 *          @OA\Schema(type="integer"),
 *     ),
 *     @OA\Response(response="204", description="Success"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->delete('/course/{course_code}/learningpath/{learningpath_id}/section/{section_id}', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = json_decode($req->getBody()->getContents(), true);


    Validator::validate($req, array_merge($args, $data), new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'learningpath_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('numeric')
            ]),
            'section_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('numeric')
            ]),
        ]
    ]));

    $course = api_get_course_info($args['course_code']);
    if (!$course)
        throwException($req, '404', "Course with code `{$args['course_code']}` not found.");

    $token = $req->getAttribute("token");
    $userId = $token['uid'];
    $learningpath = new learnpath(
        $args['course_code'],
        $args['learningpath_id'],
        $userId
    );
    if (!$learningpath->name)
        throwException($req, '404', "Learning path with id {$args['learningpath_id']} not found.");

    $lpSection = $learningpath->getItem('section_id');
    if (!$lpSection)
        throwException($req, '404', "Learningpath section not found.");

    $deleted = $learningpath->delete_item('section_id');
    if ($deleted <= 0)
        throwException($req, '404', "Learningpath section could not be deleted.");

    return
        $res
        ->withHeader("Content-Type", "application/json")
        ->withStatus(204);
});

/**
 * @OA\Get(
 *     path="/course/{course_code}/learningpath/{learningpath_id}/scorm", tags={"Learning Paths"},
 *     summary="Get learningpath as scorm package",
 *     security={{"bearerAuth": {}}},
 *     operationId="lpExportScorm",
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the learning path is located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique int identifier of the learning path.",
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

$endpoint->get('/course/{course_code}/learningpath/{learningpath_id}/scorm', function (Request $req, Response $res, $args) use ($endpoint) {

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

    lpScormExport($args['course_code'], $learningpath);

    // $res->getBody()
    //     ->write(json_encode($documents, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return
        $res->withStatus(200);
});

/**
 * @OA\Post(
 *     path="/course/{course_code}/learningpath/scorm", tags={"Learning Paths"},
 *     summary="Create learningpath from scorm package",
 *     security={{"bearerAuth": {}}},
 *     operationId="lpImportScorm",
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the learning path is located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\RequestBody(
 *          @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                  required={"scormUpload"},
 *                  @OA\Property(
 *                     description="scorm to upload",
 *                     property="scormUpload",
 *                     type="string",
 *                     format="binary",
 *                  ),
 *                 @OA\Property(
 *                     property="use_max_score",
 *                     description="Use default maximum score of 100 (1 or 0)",
 *                     type="integer",
 *                 ),
 *              )
 *         ),
 *     ),
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->post('/course/{course_code}/learningpath/scorm', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = $req->getParsedBody();
    $uploadedFiles = $req->getUploadedFiles() ? $_FILES : [];

    Validator::validate($req, array_merge($data, $args, $uploadedFiles), new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'scormUpload' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('array'),
                new Assert\All([
                    new Assert\NotBlank()
                ]),
            ]),
            'use_max_score' => new Assert\Optional([
                new Assert\AtLeastOneOf([
                    new Assert\Type('numeric'), new Assert\PositiveOrZero(), new Assert\Blank()
                ]),
            ])
        ]
    ]));

    $token = $req->getAttribute("token");
    $userId = $token['uid'];

    $course = api_get_course_info($args['course_code']);

    if (!$course)
        throwException($req, '404', "Course with code '{$args['course_code']}' not found.");

    $manifest = lpScormImport($course, $uploadedFiles);
    if (!$manifest) {
        $error = ["message" => "Scorm coud not be uploaded. Fail in lpScormImport", "last_error" => error_get_last()];
        throwException($req, '422', json_encode($error, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    }

    try {
        $courseId = $course['real_id'];
        $sessionId = 0;

        $learningpaths = learnpath::getLpList($courseId, $sessionId);

        $scormLp = $learningpaths[count($learningpaths) - 1];

        $res->getBody()
            ->write(json_encode($scormLp, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return
            $res->withStatus(200);
    } catch (\Throwable $th) {
        $error = ["error" => "error getting lp list", "th" => $th];
        $res->getBody()
            ->write(json_encode($error, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        return
            $res->withStatus(500);
    }
});


/**
 * @OA\Post(
 *     path="/course/{course_code}/learningpath/{learningpath_id}/item/{lp_item_id}/audio", tags={"Learning Paths"},
 *     summary="Add audio to learning path item",
 *     security={{"bearerAuth": {}}},
 *     operationId="lpItemUploadAudio",
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the learning path is located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique string identifier of the learning path.",
 *          in="path",
 *          name="learningpath_id",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique string identifier of the learning path item.",
 *          in="path",
 *          name="lp_item_id",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\RequestBody(
 *          @OA\MediaType(
 *             mediaType="multipart/form-data",
 *             @OA\Schema(
 *                  required={"file"},
 *                  @OA\Property(
 *                     description="Audio file to upload",
 *                     property="file",
 *                     type="string",
 *                     format="binary",
 *                  )
 *              )
 *         ),
 *     ),
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->post('/course/{course_code}/learningpath/{learningpath_id}/item/{lp_item_id}/audio', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = $req->getParsedBody();
    $uploadedFiles = $req->getUploadedFiles() ? $_FILES : [];

    Validator::validate($req, array_merge($data, $args, $uploadedFiles), new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'learningpath_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'lp_item_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'file' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('array'),
                new Assert\All([
                    new Assert\NotBlank()
                ]),
            ])
        ]
    ]));

    $token = $req->getAttribute("token");
    $userId = $token['uid'];

    $course = api_get_course_info($args['course_code']);

    if (!$course)
        throwException($req, '404', "Course with code '{$args['course_code']}' not found.");

    $learningpath = new learnpath(
        $args['course_code'],
        $args['learningpath_id'],
        $userId
    );

    if (!$learningpath->name)
        throwException($req, '404', "Learning path with id {$args['learningpath_id']} not found.");

    $lp_item_obj = new learnpathItem($args['lp_item_id'], $userId, $course['real_id']);

    if (!$lp_item_obj->title)
        throwException($req, '404', "Learning path item with id {$args['lp_item_id']} not found.");

    $learningpath->set_modified_on();
    $path = $lp_item_obj->addAudio();

    if (!$path)
        throwException($req, '404', "Audio file could not be added to the \"{$lp_item_obj->title}\" lp item.");

    $res->getBody()
        ->write(json_encode($path, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return
        $res->withStatus(200);
});


/**
 * @OA\Patch(
 *     path="/course/{course_code}/learningpath/{learningpath_id}/item/{lp_item_id}/audio", tags={"Learning Paths"},
 *     summary="Add existing audio document to learning path item",
 *     security={{"bearerAuth": {}}},
 *     operationId="lpItemAddExistingAudio",
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the learning path is located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique string identifier of the learning path.",
 *          in="path",
 *          name="learningpath_id",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique string identifier of the learning path item.",
 *          in="path",
 *          name="lp_item_id",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\RequestBody(
 *          @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"document_id"},
 *                 @OA\Property(
 *                     property="document_id",
 *                     type="integer",
 *                     description="<small>The audio document id.</small>"
 *                 ),
 *             )
 *         )
 *     ),
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->patch('/course/{course_code}/learningpath/{learningpath_id}/item/{lp_item_id}/audio', function (Request $req, Response $res, $args) use ($endpoint) {
    $data =
        json_decode($req->getBody()->getContents(), true);

    Validator::validate($req, array_merge($data, $args), new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'learningpath_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'lp_item_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'document_id' => new Assert\Required([new Assert\Type('integer'), new Assert\PositiveOrZero()])
        ]
    ]));

    $token = $req->getAttribute("token");
    $userId = $token['uid'];

    $course = api_get_course_info($args['course_code']);

    if (!$course)
        throwException($req, '404', "Course with code '{$args['course_code']}' not found.");

    $learningpath = new learnpath(
        $args['course_code'],
        $args['learningpath_id'],
        $userId
    );

    if (!$learningpath->name)
        throwException($req, '404', "Learning path with id {$args['learningpath_id']} not found.");

    $lp_item_obj = new learnpathItem($args['lp_item_id'], $userId, $course['real_id']);

    if (!$lp_item_obj->title)
        throwException($req, '404', "Learning path item with id {$args['lp_item_id']} not found.");

    $document = DocumentManager::get_document_data_by_id($data['document_id'], $args['course_code']);
    if (!$document)
        throwException($req, '404', "Document with id {$data['document_id']} not found.");

    $learningpath->set_modified_on();
    $path =
        $lp_item_obj->add_audio_from_documents($data['document_id']);

    if (!$path)
        throwException($req, '404', "Audio file could not be added to the \"{$lp_item_obj->title}\" lp item.");

    $res->getBody()
        ->write(json_encode($path, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return
        $res->withStatus(200);
});
