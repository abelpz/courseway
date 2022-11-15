<?php

use Chamilo\CourseBundle\Component\CourseCopy\CourseArchiver;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Symfony\Component\Validator\Constraints as Assert;
use CourseWay\Validation\Validator;

/**
 * @OA\Get(
 *     path="/courses",
 *     tags={"Courses"},
 *     summary="Get list of courses",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          name="from",
 *          description="Offset (from the 7th = '6'). Optional.",
 *          in="query",
 *          @OA\Schema(type="integer"),
 *     ),
 *     @OA\Parameter(
 *          name="howmany",
 *          description="Number of results we want. Optional.",
 *          in="query",
 *          @OA\Schema(type= "integer"),
 *     ),
 *     @OA\Parameter(
 *          name="visibility",
 *          description="the visibility of the course, or all by default. Optional.",
 *          in="query",
 *          @OA\Schema(type="integer"),
 *     ),
 *     @OA\Parameter(
 *          name="startwith",
 *          description="If defined, only return results for which the course *title* begins with this string. Optional.",
 *          in="query",
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          name="alsoSearchCode",
 *          description="An extension option to indicate that we also want to search for course codes. Optional.",
 *          in="query",
 *          @OA\Schema(type="boolean"),
 *     ),
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->get('/courses', function (Request $req, Response $res, $args) use ($endpoint) {
    $params = $req->getQueryParams();

    Validator::validate($req, $params, new Assert\Collection([
        'fields' => [
            'from' => new Assert\Type('numeric'),
            'howmany' => new Assert\Type('numeric'),
            'visibility' => new Assert\Type('numeric'),
            'startwith' => new Assert\Type('string'),
            'alsoSearchCode' => new  Assert\Choice(['true', 'false']),
        ],
        'allowMissingFields' => true,
    ]));

    $from = (int) $params['from'] ?: 0;
    $howmany = (int) $params['howmany'] ?: 0;
    $orderby = (string) $params['orderby'] ?: 'title';
    $orderdirection = (string) $params['$orderdirection'] ?: 'ASC';
    $visibility = (int) $params['visibility'] ?: -1;
    $startwith = (string) $params['startwith'] ?: '';
    $urlId = null;
    $alsoSearchCode = (bool) $params['alsoSearchCode'] ?: '';

    $courses = CourseManager::get_courses_list(
        $from,
        $howmany,
        $orderby,
        $orderdirection,
        $visibility,
        $startwith,
        $urlId,
        $alsoSearchCode
    );
    if (!$courses)
        throwException($req, '404', 'Courses not found.');

    $res->getBody()->write(json_encode($courses, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    return
        $res->withHeader("Content-Type", "application/json")
        ->withStatus(200);
});

/**
 * @OA\Post(
 *     path="/course", tags={"Courses"},
 *     summary="Add a course",
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *          @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"title"},
 *                 @OA\Property(
 *                     property="user_id",
 *                     type="integer",
 *                     description="<small>course owner id (will be added as teacher), defaults to the requester ID.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="title",
 *                     type="string",
 *                     description="<small>course title.</small>"
 *                 ),
 *                 @OA\Property(
 *                      property="intro_text",
 *                      type="string",
 *                      description="<small>The introduction text in the course homepage. Accepts HTML.</small>"                  
 *                 ),
 *                 @OA\Property(
 *                     property="wanted_code",
 *                     type="string",
 *                     description="<small>unique identifier. will be converted to uppercase and spaces will be removed.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="course_language",
 *                     type="string",
 *                     description="<small>course language. (english, spanish, etc.)</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="disk_quota",
 *                     type="integer",
 *                     description="<small>allocated space in disk (In Bytes)</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="visibility",
 *                     type="integer",
 *                     description="<small>course access code</small>
 *  0:Closed - the course is only accessible to the teachers
 *  1:Private access - access authorized to group members only
 *  2:Open - access allowed for users registered on the platform
 *  3:Public - access allowed for the whole world
 *  4:Hidden - Completely hidden to all users except the administrators"
 *                 ),
 *                 @OA\Property(
 *                     property="course_category",
 *                     type="string",
 *                     description="<small>course category code. unique string identifier of the category. (eg. 'PROJ')</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="department_name",
 *                     type="string",
 *                     description="<small>course department name.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="department_url",
 *                     type="string",
 *                     description="<small>course department url.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="subscribe",
 *                     type="integer",
 *                     description="<small>should users be allowed to subscribe? (1: true, 0:false)</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="unsubscribe",
 *                     type="integer",
 *                     description="<small>should users be allowed to unsubscribe? (1: true, 0:false)</small>"
 *                 ),
 *                 @OA\Property(
 *                      property="teachers",
 *                      type="array",
 *                      description="<small>array with extra user's ids to be assigned as teachers for this course.</small>",
 *                      @OA\Items(
 *                          type="integer"
 *                      )
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response="201", description="Created"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->post('/course', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = json_decode($req->getBody()->getContents(), true);

    //Validate params
    Validator::validate($req, $data, new Assert\Collection([
        'fields' => [
            'title' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'user_id' => new Assert\Optional([
                new Assert\NotBlank(),
                new Assert\Type('integer'), new Assert\PositiveOrZero()
            ]),
            'intro_text' => new Assert\Optional([new Assert\Type('string')]),
            'wanted_code' => new Assert\Optional([new Assert\Type('string')]),
            'course_language' => new Assert\Optional([new Assert\Type('string')]),
            'disk_quota' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero(), new Assert\PositiveOrZero()]),
            'visibility' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'course_category' => new Assert\Optional([new Assert\Type('string')]),
            'department_name' => new Assert\Optional([new Assert\Type('string')]),
            'department_url' => new Assert\Optional([new Assert\Type('string')]),
            'subscribe' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'unsubscribe' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'teachers' => new Assert\Optional([
                new Assert\Type('array'),
                new Assert\All([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            ]),
        ]
    ]));

    //Check if teachers ids exist
    foreach ($data['teachers'] as $key => $value) {
        $teacher = api_get_user_info($value);
        if (!$teacher)
            throwException($req, '400', '[teacher]: The teacher with id ' . $value . ' does not exist.');
    }

    //Check if course code is already in db
    if (empty($data['wanted_code'])) {
        $data['wanted_code'] = $data['title'];
        $substring = api_substr($data['title'], 0, 40);
        if ($substring === false || empty($substring))
            throwException($req, '400', '[title]: value is too long.');
        $data['wanted_code'] = CourseManager::generate_course_code($substring);
    }
    if (api_get_course_info($data['wanted_code']))
        throwException($req, '400', 'The provided or generated `wanted_code` already exists in database. Try adding a custom `wanted_code`.');

    $token = $req->getAttribute("token");
    $data['user_id'] = $data['user_id'] ?: $token['uid'];
    $course = CourseManager::create_course($data, $data['user_id']);
    if (!$course)
        throwException($req, '422', 'Course could not be created.');

    if (!empty($data['intro_text'])) {
        //Add introduction text to course homepage
        $manager = Database::getManager();
        $toolIntro = new Chamilo\CourseBundle\Entity\CToolIntro();
        $toolIntro
            ->setCId($course['real_id'])
            ->setId(TOOL_COURSE_HOMEPAGE)
            ->setSessionId(0)
            ->setIntroText($data['intro_text']);
        $manager->persist($toolIntro);
        $manager->flush();

        $course['intro_text'] = $toolIntro->getIntroText();
    }

    //Send created course data
    $res->getBody()
        ->write(json_encode($course, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    return
        $res->withHeader("Content-Type", "application/json")
        ->withStatus(201);
});

/**
 * @OA\Post(
 *     path="/course/{course_code}/description", tags={"Courses"},
 *     summary="Add a course description",
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *          @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"description_type,title,content"},
 *                 @OA\Property(
 *                     property="description_type",
 *                     type="integer",
 *                     description="<small>course description type</small>
 *  1: GeneralDescription
 *  2: Objectives
 *  3: Topics
 *  4: Methodology
 *  5: CourseMaterial
 *  6: HumanAndTechnicalResources
 *  7: Assessment
 *  8: ThematicAdvance
 *  9: Other"
 *                 ),
 *                 @OA\Property(
 *                     property="title",
 *                     type="string",
 *                     description="<small>course title.</small>"
 *                 ),
 *                 @OA\Property(
 *                      property="content",
 *                      type="string",
 *                      description="<small>The introduction text in the course homepage. Accepts HTML.</small>"                  
 *                 ),
 *             )
 *         )
 *     ),
 *     @OA\Response(response="201", description="Created"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->post('/course/{course_code}/description', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = json_decode($req->getBody()->getContents(), true);

    //Validate params
    Validator::validate($req, array_merge($data, $args), new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'description_type' => new Assert\Required([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'title' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'content' => new Assert\Required([new Assert\Type('string')])
        ]
    ]));

    $course = api_get_course_info($args['course_code']);
    if (!$course)
        throwException($req, '404', 'Course not found.');

    $course_id = api_get_course_int_id($args['course_code']);

    $course_description = new CourseDescription();
    $title = $data['title'];
    $content = $data['content'];
    $description_type = intval($data['description_type']);

    $description = $course_description->get_data_by_description_type(
        $description_type,
        $course_id
    );

    if (count($description) > 0) {
        throwException($req, '422', 'There is already a course description with this type.');
    }

    $course_description->set_course_id($course_id);
    $course_description->set_description_type($description_type);
    $course_description->set_title($title);
    $course_description->set_content($content);
    $course_description->set_progress("");

    $added = $course_description->insert();

    if ($added < 1)
        throwException($req, '422', 'Course description could not be created.');

    $description = $course_description->get_data_by_description_type(
        $description_type,
        $course_id
    );

    //Send created course description data
    $res->getBody()
        ->write(json_encode($description, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    return
        $res->withHeader("Content-Type", "application/json")
        ->withStatus(201);
});

/**
 * @OA\Get(
 *     path="/course/{course_code}", tags={"Courses"},
 *     summary="Get course by course code",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the tests are located.",
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

$endpoint->get('/course/{course_code}', function (Request $req, Response $res, $args) use ($endpoint) {

    //Validate params
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
        throwException($req, '404', 'Course not found.');

    $res->getBody()
        ->write(json_encode($course, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return
        $res->withHeader("Content-Type", "application/json")
        ->withStatus(200);
});

/**
 * @OA\Delete(
 *     path="/course/{course_code}", tags={"Courses"},
 *     summary="Delete course by course code",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the tests are located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Response(response="204", description="Success"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->delete('/course/{course_code}', function (Request $req, Response $res, $args) use ($endpoint) {

    //Validate params
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
        throwException($req, '404', 'Course not found.');

    $deleted = CourseManager::delete_course($args['course_code']);
    if (!$deleted)
        throwException($req, '422', "Course could not be deleted.");

    return $res->withStatus(204);
});

/**
 * @OA\Get(
 *     path="/courses/categories", tags={"Courses"},
 *     summary="Get list of categories",
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->get('/courses/categories', function (Request $req, Response $res, $args) use ($endpoint) {

    $category = CourseCategory::getCategories();

    $res->getBody()
        ->write(json_encode($category, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    return
        $res->withHeader("Content-Type", "application/json")
        ->withStatus(200);
});

/**
 * @OA\Post(
 *     path="/courses/category", tags={"Courses"},
 *     summary="Add a category",
 *     security={{"bearerAuth": {}}},
 *     @OA\RequestBody(
 *          @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"category_code","name"},
 *                 @OA\Property(
 *                     property="name",
 *                     type="string",
 *                     description="<small>category name</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="category_code",
 *                     type="string",
 *                     description="<small>unique string identifier for this category</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="parent_code",
 *                     type="string",
 *                     description="<small>unique string identifier for the parent category.</small>"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response="201", description="Created"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->post('/courses/category', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = json_decode($req->getBody()->getContents(), true);

    //Validate params
    Validator::validate($req, $data, new Assert\Collection([
        'fields' => [
            'name' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'category_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'parent_code' => new Assert\Optional([new Assert\Type('string')]),
        ]
    ]));

    $code = $data['category_code'];
    $name = $data['name'];
    $canHaveCourses = 'TRUE';
    $parent_code =  $data['parent_code'] ?: null;
    $parent_category = $parent_code ? CourseCategory::getCategory($parent_code) : null;
    if (is_array($parent_category) && empty($parent_category))
        throwException($req, '400', 'Category parent with code `' . $parent_code . '` does not exist.');

    $categoryId = CourseCategory::addNode($code, $name, $canHaveCourses, $parent_category['id']);
    if (!$categoryId)
        throwException($req, '422', 'Category could not be created');

    $category = CourseCategory::getCategoryById($categoryId);

    $res->getBody()
        ->write(json_encode($category, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    return
        $res->withHeader("Content-Type", "application/json")
        ->withStatus(201);
});

/**
 * @OA\Get(
 *     path="/courses/category/{category_code}", tags={"Courses"},
 *     summary="Get category by category code",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the category.",
 *          in="path",
 *          name="category_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->get('/courses/category/{category_code}', function (Request $req, Response $res, $args) use ($endpoint) {

    //Validate params
    Validator::validate($req, $args, new Assert\Collection([
        'fields' => [
            'category_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
        ]
    ]));

    $category = CourseCategory::getCategory($args['category_code']);
    if (!$category)
        throwException($req, '404', 'Category not found.');

    $res->getBody()
        ->write(json_encode($category, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return
        $res->withHeader("Content-Type", "application/json")
        ->withStatus(200);
});

/**
 * @OA\Delete(
 *     path="/courses/category/{category_code}", tags={"Courses"},
 *     summary="Delete category by category code",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the category.",
 *          in="path",
 *          name="category_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Response(response="204", description="Success"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->delete('/courses/category/{category_code}', function (Request $req, Response $res, $args) use ($endpoint) {

    //Validate params
    Validator::validate($req, $args, new Assert\Collection([
        'fields' => [
            'category_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
        ]
    ]));

    $category = CourseCategory::getCategory($args['category_code']);
    if (!$category)
        throwException($req, '404', 'Category not found.');

    $deleted = CourseCategory::deleteNode($args['category_code']);
    if (!$deleted)
        throwException($req, '422', 'Could not delete category.');

    return
        $res->withHeader("Content-Type", "application/json")
        ->withStatus(204);
});

/**
 * @OA\Get(
 *     path="/course/{course_code}/resources", tags={"Courses"},
 *     summary="Get list of resources in course",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the tests are located.",
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

use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;

$endpoint->get('/course/{course_code}/resources', function (Request $req, Response $res, $args) use ($endpoint) {

    //Validate params
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
        throwException($req, '404', 'Course not found.');

    $courseBuilder = new CourseBuilder('complete', $course);
    $courseList = $courseBuilder->build(
        0,
        $args['course_code'],
        false
    );
    if (empty($courseList))
        throwException($req, '422', 'Resources could not be listed.');

    $res->getBody()
        ->write(json_encode($courseList, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return
        $res->withHeader("Content-Type", "application/json")
        ->withStatus(200);
});

/**
 * @OA\Get(
 *     path="/course/{course_code}/backup", tags={"Courses"},
 *     summary="Get backup of a course",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the course to backup.",
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

$endpoint->get('/course/{course_code}/backup', function (Request $req, Response $res, $args) use ($endpoint) {

    //Validate params
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
        throwException($req, '404', 'Course not found.');

    $courseBuilder = new CourseBuilder('complete', $course);
    $courseList = $courseBuilder->build(
        0,
        $args['course_code'],
        false
    );
    $zipFile = CourseArchiver::createBackup($courseList);

    $temp_zip_file = CourseArchiver::getBackupDir() . $zipFile;
    DocumentManager::file_send_for_download($temp_zip_file, true, $zipFile);

    if (!$zipFile)
        throwException($req, '422', 'Resources could not be listed.');

    // $res->getBody()
    //     ->write(json_encode($courseList, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return
        $res->withStatus(200);
});

/**
 * @OA\Post(
 *     path="/course/{course_code}/tool/{tool_id}/intro_text", tags={"Courses"},
 *     summary="Adds an introduction text to specified tool",
 *     security={{"bearerAuth": {}}},
 *     operationId="toolAddIntroText",
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the learning path category is located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="The id of the tool.",
 *          in="path",
 *          name="tool_id",
 *          required=true,
 *          @OA\Schema(
 *              type="integer",
 *              enum={
 *                "document",
 *                "final_item",
 *                "readout_text",
 *                "thumbnail",
 *                "hotpotatoes",
 *                "calendar_event",
 *                "link",
 *                "link_category",
 *                "course_description",
 *                "search",
 *                "learnpath",
 *                "learnpath_category",
 *                "agenda",
 *                "announcement",
 *                "forum",
 *                "forum_category",
 *                "forum_thread",
 *                "forum_post",
 *                "forum_attachment",
 *                "forum_thread_qualify",
 *                "thread",
 *                "post",
 *                "dropbox",
 *                "quiz",
 *                "test_category",
 *                "user",
 *                "group",
 *                "blog_management",
 *                "chat",
 *                "student_publication",
 *                "tracking",
 *                "homepage_link",
 *                "course_setting",
 *                "backup",
 *                "copy_course_content",
 *                "recycle_course",
 *                "course_homepage",
 *                "course_rights",
 *                "file_upload",
 *                "course_maintenance",
 *                "survey",
 *                "wiki",
 *                "glossary",
 *                "gradebook",
 *                "notebook",
 *                "attendance",
 *                "course_progress",
 *                "portfolio",
 *                "compilatio",
 *                "xapi"
 *              }
 *          ),
 *     ),
 *     @OA\RequestBody(
 *          @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"intro_text"},
 *                 @OA\Property(
 *                      property="intro_text",
 *                      type="string",
 *                      description="<small>The introduction text for the tool. Accepts HTML.</small>"                  
 *                 ),
 *                 @OA\Property(
 *                      property="session_id",
 *                      type="integer",
 *                      description="<small>Session Id as integer.</small>",
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response="201", description="Created"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->post('/course/{course_code}/tool/{tool_id}/intro_text', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = json_decode($req->getBody()->getContents(), true);

    //Validate params
    Validator::validate($req, array_merge($data, $args), new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'tool_id' => new Assert\Required([new Assert\Choice([
                'document',
                'final_item',
                'readout_text',
                'thumbnail',
                'hotpotatoes',
                'calendar_event',
                'link',
                'link_category',
                'course_description',
                'search',
                'learnpath',
                'learnpath_category',
                'agenda',
                'announcement',
                'forum',
                'forum_category',
                'forum_thread',
                'forum_post',
                'forum_attachment',
                'forum_thread_qualify',
                'thread',
                'post',
                'dropbox',
                'quiz',
                'test_category',
                'user',
                'group',
                'blog_management',
                'chat',
                'student_publication',
                'tracking',
                'homepage_link',
                'course_setting',
                'backup',
                'copy_course_content',
                'recycle_course',
                'course_homepage',
                'course_rights',
                'file_upload',
                'course_maintenance',
                'survey',
                'wiki',
                'glossary',
                'gradebook',
                'notebook',
                'attendance',
                'course_progress',
                'portfolio',
                'compilatio',
                'xapi',
            ])]),
            'intro_text' => new Assert\Required([new Assert\Type('string')]),
            'session_id' => new Assert\Optional([
                new Assert\NotBlank(),
                new Assert\Type('numeric')
            ])
        ]
    ]));

    $course = api_get_course_info($args['course_code']);
    if (!$course)
        throwException($req, '404', "Course with code `{$args['course_code']}` not found.");

    $manager = Database::getManager();
    $toolIntro = new Chamilo\CourseBundle\Entity\CToolIntro();
    $toolIntro
        ->setCId($course['real_id'])
        ->setId($args['tool_id'])
        ->setSessionId($data['session_id'] || 0)
        ->setIntroText($data['intro_text']);
    $manager->persist($toolIntro);
    $manager->flush();

    $intro_text = $toolIntro->getIntroText();

    //Send created course data
    $res->getBody()
        ->write(json_encode($intro_text, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    return
        $res->withHeader("Content-Type", "application/json")
        ->withStatus(201);
});
