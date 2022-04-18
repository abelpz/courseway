<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

/**
 * @OA\Get(
 *     path="/course/{course_code}/learningpaths", tags={"Learning Paths"},
 *     summary="Get list of learning paths from course",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="the id of the course from which to list the learning paths",
 *          in="path",
 *          name="course_code",
 *          required=false,
 *     ),
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="401", description="Unauthorized"),
 *     @OA\Response(response="400", description="Bad request")
 * )
 */ 

$endpoint->get('/course/{course_code}/learningpaths', function (Request $req, Response $res, $args) use ($endpoint) {

    $token = $req->getAttribute("token");
   
    if (empty($args['course_code'])) {
        $res->withHeader("Content-Type", "application/json");
        $res->withStatus(400);
        $res->getBody()
            ->write(slim_msg('error', 'You are required to provide: course_code'));
        return $res;
    }

    $user = UserManager::getManager()->findUserByUsername($token['uname']);

    if ($user->isSuperAdmin()) {

        $course = api_get_course_info($args['course_code']);

        if (empty($course)) {
            $res->withHeader("Content-Type", "application/json");
            $res->withStatus(400);
            $res->getBody()
                ->write(slim_msg('error', 'Could not find course with course code: ' . $args['course_code']));
            return $res;
        }

        $courseId = $course['real_id'];;
        $sessionId = 0;

        $learningpaths = learnpath::getLpList($courseId, $sessionId);
        $res->withHeader("Content-Type", "application/json");
        $res->getBody()->write(json_encode($learningpaths, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    } else {
        $res->withHeader("Content-Type", "application/json");
        $res->getBody()
            ->write(slim_msg('error', 'You need to have admin role to access this.'));
    }

    return $res;
});

/**
 * @OA\Post(
 *     path="/course/{course_code}/learningpath", tags={"Learning Paths"},
 *     summary="Create a learning path in a course",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the learning path will be added.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *     ),
 *     @OA\RequestBody(
 *          @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"name","user_id"},
 *                 @OA\Property(
 *                     property="user_id",
 *                     type="integer",
 *                     description="<small>Learning path creator id (this is for the logs).</small>"
 *                 ),
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
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="401", description="Unauthorized"),
 *     @OA\Response(response="400", description="Bad request")
 * )
 */

$endpoint->post('/course/{course_code}/learningpath', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = json_decode($req->getBody()->getContents(), true);
    $token = $req->getAttribute("token");

    if(empty($data['user_id']) or empty($data['name']) or empty($args['course_code'])){
        $res->withHeader("Content-Type", "application/json");
        $res->withStatus(400);
        $res->getBody()
            ->write(slim_msg('error', 'You are required to provide: user_id, name, coursecode.'));
        return $res;
    }

    $user = UserManager::getManager()->findUserByUsername($token['uname']);

    if ($user->isSuperAdmin()) {

        $courseCode = $args['course_code'];
        $name = $data['name'];
        $description = '';
        $learnpath = 'guess';
        $origin = '';
        $zipname = '';
        $publicated_on = api_get_utc_datetime();
        $expired_on = '';
        $categoryId = $data['category_id'] ?: 0;
        $userId = $data['user_id'];

        $learningpath = learnpath::add_lp(
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

        if ($learningpath) {
            $res->withHeader("Content-Type", "application/json");
            $res->withStatus(200);
            $res->getBody()
                ->write(json_encode($learningpath, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        } else {
            $res->withHeader("Content-Type", "application/json");
            $res->withStatus(400);
            $res->getBody()
                ->write(slim_msg('error', 'Learningpath could not be created'));
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
 *     path="/course/{course_code}/learningpaths/categories", tags={"Learning Paths"},
 *     summary="List learning paths categories from course",
 *     security={{"bearerAuth": {}}},
 *      @OA\Parameter(
 *          description="the id of the course from which to list the learning path categories",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *     ),
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="401", description="Unauthorized"),
 *     @OA\Response(response="400", description="Bad request")
 * )
 */

$endpoint->get('/course/{course_code}/learningpaths/categories', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = json_decode($req->getBody()->getContents(), true);
    $token = $req->getAttribute("token");

    if (empty($args['course_code'])) {
        $res->withHeader("Content-Type", "application/json");
        $res->withStatus(400);
        $res->getBody()
            ->write(slim_msg('error', 'You are required to provide: course_id.'));
        return $res;
    }

    $user = UserManager::getManager()->findUserByUsername($token['uname']);

    if ($user->isSuperAdmin()) {

        $course = api_get_course_info($args['course_code']);
        
        if(empty($course)) {
            $res->withHeader("Content-Type", "application/json");
            $res->withStatus(400);
            $res->getBody()
            ->write(slim_msg('error', 'Could not find course with course code: '. $args['course_code']));
            return $res;
        }

        $courseId = $course['real_id'];

        $lpCategories = learnpath::getCategories($courseId);
        if ($lpCategories) {
            $categories = array();
            foreach ($lpCategories as $category) {
                array_push($categories, [
                    'id' => $category->getId(),
                    'name' => $category->getName(),
                    'c_id' => $category->getCId(),
                    'position' => $category->getPosition()
                ]);
            }
            $res->withHeader("Content-Type", "application/json");
            $res->withStatus(200);
            $res->getBody()
                ->write(json_encode($categories, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        } else {
            $res->withHeader("Content-Type", "application/json");
            $res->withStatus(400);
            $res->getBody()
                ->write(slim_msg('error', 'Could not get list of learningpaths.'));
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
 * @OA\Post(
 *     path="/course/{course_code}/learningpaths/category", tags={"Learning Paths"},
 *     summary="Create a learning path category in a course",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique int identifier of the course in which the learning path category will be added.",
 *          in="path",
 *          name="course_code",
 *          required=true,
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
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="401", description="Unauthorized"),
 *     @OA\Response(response="400", description="Bad request")
 * )
 */

$endpoint->post('/course/{course_code}/learningpaths/category', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = json_decode($req->getBody()->getContents(), true);
    $token = $req->getAttribute("token");

    if (empty($data['name']) or empty($args['course_code'])) {
        $res->withHeader("Content-Type", "application/json");
        $res->withStatus(400);
        $res->getBody()
            ->write(slim_msg('error', 'You are required to provide: course_code, name.'));
        return $res;
    }

    $user = UserManager::getManager()->findUserByUsername($token['uname']);    

    if ($user->isSuperAdmin()) {

        $course = api_get_course_info($args['course_code']);

        if (empty($course)) {
            $res->withHeader("Content-Type", "application/json");
            $res->withStatus(400);
            $res->getBody()
                ->write(slim_msg('error', 'Could not find course with course code: ' . $args['course_code']));
            return $res;
        }

        $data['c_id'] = $course['real_id'];

        $lpCategory = learnpath::createCategory($data);
        if ($lpCategory) {
            $res->withHeader("Content-Type", "application/json");
            $res->withStatus(200);
            $res->getBody()
                ->write(json_encode($lpCategory, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
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

/**
 * @OA\Get(
 *     path="/course/{course_code}/learningpath/{learningpath_id}/sections", tags={"Learning Paths"},
 *     summary="List sections from course's learning path",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the course from which the learning path sections will be listed.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *     ),
 *     @OA\Parameter(
 *          description="unique int identifier of the learning path from which sections will be listed.",
 *          in="path",
 *          name="learningpath_id",
 *          required=true,
 *     ),
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="401", description="Unauthorized"),
 *     @OA\Response(response="400", description="Bad request")
 * )
 */

$endpoint->get('/course/{course_code}/learningpath/{learningpath_id}/sections', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = json_decode($req->getBody()->getContents(), true);
    $token = $req->getAttribute("token");
    $userId = $token['uid'];

    if (empty($args['course_code']) or empty($args['learningpath_id'])) {
        $res->withHeader("Content-Type", "application/json");
        $res->withStatus(400);
        $res->getBody()
        ->write(slim_msg('error', 'You are required to provide: course_code, learningpath_id.'));
        return $res;
    }

    $user = UserManager::getManager()->findUserByUsername($token['uname']);
    
    if ($user->isSuperAdmin()) {
        
        $learningpath = new learnpath(
            $args['course_code'],
            $args['learningpath_id'],
            $userId
        );

        $lpSections = $learningpath->items;
        if ($lpSections) {
            $res->withHeader("Content-Type", "application/json");
            $res->withStatus(200);
            $res->getBody()
                ->write(json_encode($lpSections, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
        } else {
            $res->withHeader("Content-Type", "application/json");
            $res->withStatus(400);
            $res->getBody()
                ->write(slim_msg('error', 'Could not get list of learningpaths.'));
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
 * @OA\Post(
 *     path="/course/{course_code}/learningpath/{learningpath_id}/section", tags={"Learning Paths"},
 *     summary="Add section to learning path",
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
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="401", description="Unauthorized"),
 *     @OA\Response(response="400", description="Bad request")
 * )
 */

$endpoint->post('/course/{course_code}/learningpath/{learningpath_id}/section', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = json_decode($req->getBody()->getContents(), true);
    $token = $req->getAttribute("token");
    $userId = $token['uid'];

    if (empty($args['course_code']) or empty($args['learningpath_id'])) {
        $res->withHeader("Content-Type", "application/json");
        $res->withStatus(400);
        $res->getBody()
            ->write(slim_msg('error', 'You are required to provide: course_code, learningpath_id.'));
        return $res;
    }
    
    $user = UserManager::getManager()->findUserByUsername($token['uname']);

    if ($user->isSuperAdmin()) {

        $learningpath = new learnpath(
            $args['course_code'],
            $args['learningpath_id'],
            $userId
        );

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
        if ($lpSection) {
            $res->withHeader("Content-Type", "application/json");
            $res->withStatus(200);
            $res->getBody()
                ->write(json_encode($lpSection, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
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

