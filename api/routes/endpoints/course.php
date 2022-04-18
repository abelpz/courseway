<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

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
 *          @OA\Schema(type="integer"),
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
 *     @OA\Response(response="400–499",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="500-599",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->get('/courses', function (Request $req, Response $res, $args) use ($endpoint) {
    $params = $req->getQueryParams();
    $token = $req->getAttribute("token");

    $user = UserManager::getManager()->findUserByUsername($token['uname']);
    if (!$user)
        throwException($req, '404', 'User not found.');
    if (!$user->isSuperAdmin())
        throwException($req, '401', 'User must be an administrator.');

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

    if(!$courses)
        throwException($req, '404', 'Courses not found.');

    $res->withHeader("Content-Type", "application/json");
    $res->withStatus(200);
    $res->getBody()->write(json_encode($courses, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    return $res;
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
 *                 required={"title","user_id"},
 *                 @OA\Property(
 *                     property="user_id",
 *                     type="integer",
 *                     description="<small>course owner id (will be added as teacher)</small>"
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
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="401", description="Unauthorized"),
 *     @OA\Response(response="400", description="Bad request")
 * )
 */

$endpoint->post('/course', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = json_decode($req->getBody()->getContents(), true);
    $token = $req->getAttribute("token");

    $user = UserManager::getManager()->findUserByUsername($token['uname']);

    if ($user->isSuperAdmin()) {
        $course = CourseManager::create_course($data, $data['user_id']);
        if ($course) {
            if(!empty($data['intro_text'])){
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
            $res->withHeader("Content-Type", "application/json");
            $res->withStatus(200);
            $res->getBody()
                ->write(json_encode($course, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        } else {
            $res->withHeader("Content-Type", "application/json");
            $res->withStatus(400);
            $res->getBody()
                ->write(slim_msg('error', 'Course could not be created'));
        }
    } else {
        $res->withHeader("Content-Type", "application/json");
        $res->withStatus(401);
        $res->getBody()
            ->write(slim_msg('error', 'You need to have admin role to access this.'));
    }

    return $res;
});

/**
 * @OA\Get(
 *     path="/courses/categories", tags={"Courses"},
 *     summary="Get list of categories",
 *     security={{"bearerAuth": {}}},
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="401", description="Unauthorized"),
 *     @OA\Response(response="400", description="Bad request")
 * )
 */

$endpoint->get('/courses/categories', function (Request $req, Response $res, $args) use ($endpoint) {
    $params = $req->getQueryParams();

    $token = $req->getAttribute("token");
    $user = UserManager::getManager()->findUserByUsername($token['uname']);

    if ($user->isSuperAdmin()) {
        $category = CourseCategory::getCategories();
        $res->withHeader("Content-Type", "application/json");
        $res->getBody()->write(json_encode($category, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    } else {
        $res->withHeader("Content-Type", "application/json");
        $res->getBody()
            ->write(slim_msg('error', 'You need to have admin role to access this.'));
    }
    return $res;
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
 *                     property="category_code",
 *                     type="string",
 *                     description="<small>unique string identifier for this category</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="name",
 *                     type="string",
 *                     description="<small>category name</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="parent_code",
 *                     type="string",
 *                     description="<small>unique string identifier for the parent category.</small>"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="401", description="Unauthorized"),
 *     @OA\Response(response="400", description="Bad request")
 * )
 */

$endpoint->post('/courses/category', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = json_decode($req->getBody()->getContents(), true);
    $token = $req->getAttribute("token");

    $user = UserManager::getManager()->findUserByUsername($token['uname']);

    if (empty($data['category_code']) or empty($data['name'])) {
        $res->withHeader("Content-Type", "application/json");
        $res->withStatus(400);
        $res->getBody()
            ->write(slim_msg('error', 'You are required to provide: category_code, name'));
        return $res;
    }

    $code = $data['category_code'];
    $name = $data['name'];
    $canHaveCourses = 'TRUE';
    $parent_id =  $data['parent_code'] ?: null;

    if ($user->isSuperAdmin()) {
        $category = CourseCategory::addNode($code, $name, $canHaveCourses, $parent_id);
        if ($category) {
            $res->withHeader("Content-Type", "application/json");
            $res->withStatus(200);
            $res->getBody()
                ->write(json_encode($category, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        } else {
            $res->withHeader("Content-Type", "application/json");
            $res->withStatus(400);
            $res->getBody()
                ->write(slim_msg('error', 'Category could not be created'));
        }
    } else {
        $res->withHeader("Content-Type", "application/json");
        $res->withStatus(401);
        $res->getBody()
            ->write(slim_msg('error', 'You need to have admin role to access this.'));
    }

    return $res;
});

use Chamilo\CourseBundle\Component\CourseCopy\CourseBuilder;

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
 *     ),
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="401", description="Unauthorized"),
 *     @OA\Response(response="400", description="Bad request")
 * )
 */

$endpoint->get('/course/{course_code}/resources', function (Request $req, Response $res, $args) use ($endpoint) {
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

    if ($user->isSuperAdmin()) {
        $courseBuilder = new CourseBuilder('complete');
        $course = $courseBuilder->build(
            0,
            $args['course_code'],
            false
        );
        if (!empty($course)) {
            $res->withHeader("Content-Type", "application/json");
            $res->getBody()->write(json_encode($course, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        } else {
            $res->withHeader("Content-Type", "application/json");
            $res->getBody()
                ->write(slim_msg('error', 'Quizzes could not be listed'));
        }
    } else {
        $res->withHeader("Content-Type", "application/json");
        $res->getBody()
            ->write(slim_msg('error', 'You need to have admin role to access this.'));
    }

    return $res;
});