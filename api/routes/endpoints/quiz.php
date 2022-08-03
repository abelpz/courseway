<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

use Symfony\Component\Validator\Constraints as Assert;
use CourseWay\Validation\Validator;

/**
 * @OA\Get(
 *     path="/course/{course_code}/tests", tags={"Tests"},
 *     summary="Get list of tests in course",
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

$endpoint->get('/course/{course_code}/tests', function (Request $req, Response $res, $args) use ($endpoint) {

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

    $quizList = getExercises($course);

    $res->getBody()
        ->write(json_encode($quizList, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return $res
        ->withHeader("Content-Type", "application/json")
        ->withStatus(200);
});


/**
 * @OA\Post(
 *     path="/course/{course_code}/test", tags={"Tests"},
 *     summary="Create a test in a course",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the test will be located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\RequestBody(
 *          @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"title"},
 *                 @OA\Property(
 *                     property="title",
 *                     type="string",
 *                     description="<small>Unique string identifier for this test</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="description",
 *                     type="string",
 *                     description="<small>This test description. Can be HTML</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="type",
 *                     type="integer",
 *                     description="<small>Test type.</small>
 *  1: all questions on one page
 *  2: one question by page"
 *                 ),
 *                 @OA\Property(
 *                     property="feedback_type",
 *                     type="integer",
 *                     description="<small>the exercise feedback type</small>
 *  0: At end of test
 *  1: Adaptative test with immediate feedback
 *  2: Exam (no feedback)
 *  3: Direct feedback as pop-up"
 *                 ),
 *                 @OA\Property(
 *                     property="attempts",
 *                     type="integer",
 *                     description="<small>the exercise max attempts.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="random",
 *                     type="integer",
 *                     description="<small>sets to 0 if questions are not selected randomly if questions are selected randomly, sets to 1.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="random_answers",
 *                     type="integer",
 *                     description="<small>sets to 0 if answers are not selected randomly, set to 1 if answers are selected randomly.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="results_disabled",
 *                     type="integer",
 *                     description="<small></small>
 *  0: Auto-evaluation mode: show score and expected answers
 *  1: Exam mode: Do not show score nor answers
 *  2: Practice mode: Show score only, by category if at least one is used
 *  4: Show score on every attempt, show correct answers only on last attempt (only works with an attempts limit)
 *  5: Do not show the score (only when user finishes all attempts) but show feedback for each attempt.
 *  6: Ranking mode: Do not show results details question by question and show a table with the ranking of all other users.
 *  7: Show only global score (not question score) and show only the correct answers, do not show incorrect answers at all
 *  8: Auto-evaluation mode and ranking
 *  9: Only show a radar of scores by category, instead of a table of categories. Do not show individual scores or feedback.
 *  10: Show the result to the learner: Show the score, the learner's choice and his feedback on each attempt, add the correct answer and his feedback when the chosen limit of attempts is reached."
 *                 ),
 *                 @OA\Property(
 *                     property="expired_time",
 *                     type="integer",
 *                     description="<small>The expired time of the quiz</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="propagate_neg",
 *                     type="integer",
 *                     description="<small>set to 1 if should propagate negative results between questions.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="saveCorrectAnswers",
 *                     type="integer",
 *                     description="<small>Set to one if should save the correct answer for the next attempt</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="randomByCat",
 *                     type="integer",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="text_when_finished",
 *                     type="string",
 *                     description="<small>Text appearing at the end of the test.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="display_category_name",
 *                     type="integer",
 *                     description="<small>is an integer 0 or 1.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="review_answers",
 *                     type="integer",
 *                     description="<small>is an integer 0 or 1.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="pass_percentage",
 *                     type="integer",
 *                     description="<small>Pass percentage.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="categories",
 *                     type="array",
 *                     description="<small>Array of test categories</small>",
 *                     @OA\Items(
 *                         type="integer"
 *                     )
 *                 ),
 *                 @OA\Property(
 *                     property="onSuccessMessage",
 *                     type="string",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="onFailedMessage",
 *                     type="string",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="emailNotificationTemplate",
 *                     type="string",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="emailNotificationTemplateToUser",
 *                     type="string",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="notifyUserByEmail",
 *                     type="integer",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="modelType",
 *                     type="integer",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="questionSelectionType",
 *                     type="integer",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="hideQuestionTitle",
 *                     type="integer",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="scoreTypeModel",
 *                     type="integer",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="globalCategoryId",
 *                     type="integer",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="showPreviousButton",
 *                     type="integer",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="exerciseCategoryId",
 *                     type="integer",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="hideQuestionNumber",
 *                     type="integer",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="preventBackwards",
 *                     type="integer",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="activate_start_date_check",
 *                     type="integer",
 *                     description="<small>if set to 1 sets value provided in start_time property.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="start_time",
 *                     type="string",
 *                     description="<small>Date to be converted (can be a string supported by date() or a timestamp).</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="activate_end_date_check",
 *                     type="integer",
 *                     description="<small>if set to 1 sets value provided in end_time property.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="end_time",
 *                     type="string",
 *                     description="<small>Date to be converted (can be a string supported by date() or a timestamp).</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="enabletimercontrol",
 *                     type="integer",
 *                     description="<small>if set to 1 sets value provided in expired_time property.</small>"
 *                 )
 *             )
 *         )
 *     ),
 *     @OA\Response(response="201", description="Created"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

// [[TO DO: add new config params]]

$endpoint->post('/course/{course_code}/test', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = json_decode($req->getBody()->getContents(), true);

    Validator::validate($req, array_merge($data, $args), new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'title' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'description' => new Assert\Optional([new Assert\Type('string')]),
            'type' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'feedback_type' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'attempts' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'random' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'random_answers' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'results_disabled' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'expired_time' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'propagate_neg' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'saveCorrectAnswers' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'randomByCat' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'text_when_finished' => new Assert\Optional([new Assert\Type('string')]),
            'display_category_name' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'review_answers' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'pass_percentage' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'categories' => new Assert\Optional([
                new Assert\Type('array'),
                new Assert\All([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            ]),
            'onSuccessMessage' => new Assert\Optional([new Assert\Type('string')]),
            'onFailedMessage' => new Assert\Optional([new Assert\Type('string')]),
            'emailNotificationTemplate' => new Assert\Optional([new Assert\Type('string')]),
            'emailNotificationTemplateToUser' => new Assert\Optional([new Assert\Type('string')]),
            'notifyUserByEmail' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'modelType' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'questionSelectionType' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'hideQuestionTitle' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'scoreTypeModel' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'globalCategoryId' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'showPreviousButton' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'exerciseCategoryId' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'hideQuestionNumber' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'preventBackwards' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'activate_start_date_check' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'start_time' => new Assert\Optional([new Assert\Type('string')]),
            'activate_end_date_check' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'end_time' => new Assert\Optional([new Assert\Type('string')]),
            'enabletimercontrol' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
        ],
        'allowExtraFields' => true
    ]));

    if(in_array($data['feedback_type'], [0,1,3])){
        Validator::validate($req, $data, new Assert\Collection([
            'fields' => [
                'results_disabled' => new Assert\Optional([new Assert\Choice([0,4,6,7,8,9,10])])
            ],
            'allowExtraFields' => true
        ]));
        $data['results_disabled'] = $data['results_disabled'] ?: 0;
    }


    if($data['feedback_type'] === 1){
        Validator::validate($req, $data, new Assert\Collection([
            'fields' => [
                'type' => new Assert\Optional([new Assert\IdenticalTo([
                    'value' => 2,
                    'message' => 'If `feedback_type` is set to 1, `type` should be set to 2.'
                ])])
            ],
            'allowExtraFields' => true
        ]));
        $data['type'] = $data['type'] ?: 2;
    }
    
    $course = api_get_course_info($args['course_code']);
    if (!$course)
        throwException($req, '404', "Course with code `{$args['course_code']}` not found.");

    $courseId = $course['real_id'];

    $exerciseId = createExercise($courseId, $data, null, '');
    
    //Set exercise properties
    // $exercise->exercise = $data['title'];
    // $exercise->description = $data['description'] ?: '';
    
    // $exerciseId = $exercise->save();
    
    if (!$exerciseId)
        throwException($req, '422', 'Test could not be created.');

    $excercise = getExercise($course, $exerciseId);
    
    $res->getBody()
        ->write(json_encode($excercise[0], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    return $res
        ->withHeader("Content-Type", "application/json")
        ->withStatus(201);
});

/**
 * @OA\Get(
 *     path="/course/{course_code}/test/{test_id}", tags={"Tests"},
 *     summary="Get a test from a course",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the test is located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique int identifier of the requested test.",
 *          in="path",
 *          name="test_id",
 *          required=true,
 *          @OA\Schema(type="integer"),
 *     ),
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->get('/course/{course_code}/test/{test_id}', function (Request $req, Response $res, $args) use ($endpoint) {

    Validator::validate($req, $args, new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'test_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('numeric')
            ]),
        ]
    ]));

    $course = api_get_course_info($args['course_code']);
    if (!$course)
        throwException($req, '404', "Course with code `{$args['course_code']}` not found.");

    $excercise = getExercise($course, $args['test_id']);
    if(!$excercise)
        throwException($req, '404', "Test not found.");

    $res->getBody()
        ->write(json_encode($excercise[0], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    return $res
        ->withHeader("Content-Type", "application/json")
        ->withStatus(200);
});

/**
 * @OA\Delete(
 *     path="/course/{course_code}/test/{test_id}", tags={"Tests"},
 *     summary="Delete a test from a course",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the test is located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique int identifier of the requested test.",
 *          in="path",
 *          name="test_id",
 *          required=true,
 *          @OA\Schema(type="integer"),
 *     ),
 *     @OA\Response(response="204", description="Success"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->delete('/course/{course_code}/test/{test_id}', function (Request $req, Response $res, $args) use ($endpoint) {

    Validator::validate($req, $args, new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'test_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('numeric')
            ]),
        ]
    ]));

    $course = api_get_course_info($args['course_code']);
    if (!$course)
        throwException($req, '404', "Course with code `{$args['course_code']}` not found.");

    $excercise = getExercise($course, $args['test_id']);
    if (!$excercise)
        throwException($req, '404', "Test not found.");

    $exercise = new Exercise($course['real_id']);
    $exercise->read($args['test_id'], false);
    $deleted = $exercise->delete();
    if(!$deleted)
        throwException($req, '422', "Test could not be deleted.");

    return $res
        ->withHeader("Content-Type", "application/json")
        ->withStatus(200);
});

/**
 * @OA\Post(
 *     path="/course/{course_code}/learningpath/{learningpath_id}/test", tags={"Tests"},
 *     summary="Create a test in a learningpath",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the test will be located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique integer identifier of the learningpath in which the test will be located.",
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
 *                     description="<small>Unique string identifier for this test</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="description",
 *                     type="string",
 *                     description="<small>This test description. Can be HTML</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="parent_id",
 *                     type="integer",
 *                     description="<small>The Id of the parent item from the LP items list. default: 0</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="previous_id",
 *                     type="integer",
 *                     description="<small>The Id of the previous item in the LP items list. default: id of last item in list.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="type",
 *                     type="integer",
 *                     description="<small>Test type.</small>
 *  1: all questions on one page
 *  2: one question by page"
 *                 ),
 *                 @OA\Property(
 *                     property="feedback_type",
 *                     type="integer",
 *                     description="<small>the exercise feedback type</small>
 *  0: At end of test
 *  1: Adaptative test with immediate feedback
 *  2: Exam (no feedback)
 *  3: Direct feedback as pop-up"
 *                 ),
 *                 @OA\Property(
 *                     property="attempts",
 *                     type="integer",
 *                     description="<small>the exercise max attempts.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="random",
 *                     type="integer",
 *                     description="<small>sets to 0 if questions are not selected randomly if questions are selected randomly, sets to 1.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="random_answers",
 *                     type="integer",
 *                     description="<small>sets to 0 if answers are not selected randomly, set to 1 if answers are selected randomly.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="results_disabled",
 *                     type="integer",
 *                     description="<small>No documentation</small>
 *  0: Auto-evaluation mode: show score and expected answers
 *  1: Exam mode: Do not show score nor answers
 *  2: Practice mode: Show score only, by category if at least one is used
 *  4: Show score on every attempt, show correct answers only on last attempt (only works with an attempts limit)
 *  5: Do not show the score (only when user finishes all attempts) but show feedback for each attempt.
 *  6: Ranking mode: Do not show results details question by question and show a table with the ranking of all other users.
 *  7: Show only global score (not question score) and show only the correct answers, do not show incorrect answers at all
 *  8: Auto-evaluation mode and ranking
 *  9: Only show a radar of scores by category, instead of a table of categories. Do not show individual scores or feedback.
 *  10: Show the result to the learner: Show the score, the learner's choice and his feedback on each attempt, add the correct answer and his feedback when the chosen limit of attempts is reached."
 *                 ),
 *                 @OA\Property(
 *                     property="expired_time",
 *                     type="integer",
 *                     description="<small>The expired time of the quiz</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="propagate_neg",
 *                     type="integer",
 *                     description="<small>set to 1 if should propagate negative results between questions.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="saveCorrectAnswers",
 *                     type="integer",
 *                     description="<small>Set to one if should save the correct answer for the next attempt</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="randomByCat",
 *                     type="integer",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="text_when_finished",
 *                     type="string",
 *                     description="<small>Text appearing at the end of the test.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="display_category_name",
 *                     type="integer",
 *                     description="<small>is an integer 0 or 1.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="review_answers",
 *                     type="integer",
 *                     description="<small>is an integer 0 or 1.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="pass_percentage",
 *                     type="integer",
 *                     description="<small>Pass percentage.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="categories",
 *                     type="array",
 *                     description="<small>Array of test categories</small>",
 *                     @OA\Items(
 *                         type="integer"
 *                     )
 *                 ),
 *                 @OA\Property(
 *                     property="onSuccessMessage",
 *                     type="string",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="onFailedMessage",
 *                     type="string",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="emailNotificationTemplate",
 *                     type="string",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="emailNotificationTemplateToUser",
 *                     type="string",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="notifyUserByEmail",
 *                     type="integer",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="modelType",
 *                     type="integer",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="questionSelectionType",
 *                     type="integer",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="hideQuestionTitle",
 *                     type="integer",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="scoreTypeModel",
 *                     type="integer",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="globalCategoryId",
 *                     type="integer",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="showPreviousButton",
 *                     type="integer",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="exerciseCategoryId",
 *                     type="integer",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="hideQuestionNumber",
 *                     type="integer",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="preventBackwards",
 *                     type="integer",
 *                     description="<small>No documentation.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="activate_start_date_check",
 *                     type="integer",
 *                     description="<small>if set to 1 sets value provided in start_time property.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="start_time",
 *                     type="string",
 *                     description="<small>Date to be converted (can be a string supported by date() or a timestamp).</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="activate_end_date_check",
 *                     type="integer",
 *                     description="<small>if set to 1 sets value provided in end_time property.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="end_time",
 *                     type="string",
 *                     description="<small>Date to be converted (can be a string supported by date() or a timestamp).</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="enabletimercontrol",
 *                     type="integer",
 *                     description="<small>if set to 1 sets value provided in expired_time property.</small>"
 *                 ),
 *             )
 *         )
 *     ),
 *     @OA\Response(response="201", description="Created"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

// [[TO DO: add new config params]]

$endpoint->post('/course/{course_code}/learningpath/{learningpath_id}/test', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = json_decode($req->getBody()->getContents(), true);

    Validator::validate($req, array_merge($data, $args), new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'title' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'description' => new Assert\Optional([new Assert\Type('string')]),
            'type' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'feedback_type' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'attempts' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'random' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'random_answers' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'results_disabled' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'expired_time' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'propagate_neg' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'saveCorrectAnswers' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'randomByCat' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'text_when_finished' => new Assert\Optional([new Assert\Type('string')]),
            'display_category_name' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'review_answers' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'pass_percentage' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'categories' => new Assert\Optional([
                new Assert\Type('array'),
                new Assert\All([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            ]),
            'onSuccessMessage' => new Assert\Optional([new Assert\Type('string')]),
            'onFailedMessage' => new Assert\Optional([new Assert\Type('string')]),
            'emailNotificationTemplate' => new Assert\Optional([new Assert\Type('string')]),
            'emailNotificationTemplateToUser' => new Assert\Optional([new Assert\Type('string')]),
            'notifyUserByEmail' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'modelType' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'questionSelectionType' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'hideQuestionTitle' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'scoreTypeModel' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'globalCategoryId' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'showPreviousButton' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'exerciseCategoryId' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'hideQuestionNumber' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'preventBackwards' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'activate_start_date_check' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'start_time' => new Assert\Optional([new Assert\Type('string')]),
            'activate_end_date_check' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'end_time' => new Assert\Optional([new Assert\Type('string')]),
            'enabletimercontrol' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
        ],
        'allowExtraFields' => true
    ]));

    if (in_array($data['feedback_type'], [0, 1, 3])) {
        Validator::validate($req, $data, new Assert\Collection([
            'fields' => [
                'results_disabled' => new Assert\Optional([new Assert\Choice([0, 4, 6, 7, 8, 9, 10])])
            ],
            'allowExtraFields' => true
        ]));
        $data['results_disabled'] = $data['results_disabled'] ?: 0;
    }


    if ($data['feedback_type'] === 1) {
        Validator::validate($req, $data, new Assert\Collection([
            'fields' => [
                'type' => new Assert\Optional([new Assert\IdenticalTo([
                    'value' => 2,
                    'message' => 'If `feedback_type` is set to 1, `type` should be set to 2.'
                ])])
            ],
            'allowExtraFields' => true
        ]));
        $data['type'] = $data['type'] ?: 2;
    }

    $course = api_get_course_info($args['course_code']);
    if (!$course)
        throwException($req, '404', "Course with code `{$args['course_code']}` not found.");

    $courseId = $course['real_id'];
    $token = $req->getAttribute("token");
    $userId = $data['user_id'] ?: $token['uid'];
    $lp = new learnpath($args['course_code'], $args['learningpath_id'], $userId);

    if (!$lp->name)
        throwException($req, '404', "Learning path with id {$args['learningpath_id']} not found.");

    $exerciseId = createExercise($courseId, $data, null, '');
    if (!$exerciseId)
        throwException($req, '422', 'Test could not be created.');

    $itemId = $lp->add_item(
        $data['parent_id'] ?: 0,
        $data['previous_id'] ?: $lp->getLastInFirstLevel(),
        'quiz',
        $exerciseId,
        $data['title'],
        $data['description'],
    );
    if (!$itemId)
        throwException($req, '422', "Exercise created with id: $exerciseId, but coldn't be added to learning path.");

    $excercise = getExercise($course, $exerciseId);
    $res->getBody()
        ->write(json_encode($excercise[0], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    return $res
        ->withHeader("Content-Type", "application/json")
        ->withStatus(201);
});

/**
 * @OA\Get(
 *     path="/course/{course_code}/test/{test_id}/questions", tags={"Tests"},
 *     summary="Get questions from a test",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the tests are located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique integer identifier of the test you wish to retrieve.",
 *          in="path",
 *          name="test_id",
 *          required=true,
 *          @OA\Schema(type="integer"),
 *     ),
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */


// [[TO DO: create get functionality]]

$endpoint->get('/course/{course_code}/test/{test_id}/questions', function (Request $req, Response $res, $args) use ($endpoint) {

    //Validate params
    Validator::validate($req, $args, new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'test_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('numeric')
            ]),
        ]
    ]));

    $course = api_get_course_info($args['course_code']);
    if (!$course)
        throwException($req, '404', "Course with code `{$args['course_code']}` not found.");

    $courseId = $course['real_id'];
    $exercise = new Exercise($courseId);
    if(!$exercise->read($args['test_id']))
        throwException($req, '404', "Exercise with id `{$args['test_id']}` not found.");

    $questionsIds = $exercise->getQuestionOrderedList();
    $questions = [];
    foreach ($questionsIds as $key => $questionId) {
        $questions[] = get_object_vars(Question::read($questionId, $course));
        //$questions[$key] = get_class_vars(get_class(Question::read($questionId, $course)));
    }

    $res->getBody()->write(json_encode($questions, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    return
        $res->withHeader("Content-Type", "application/json")
        ->withStatus(200);
});

/**
 * @OA\Post(
 *     path="/course/{course_code}/test/{test_id}/question",
 *     tags={"Tests"},
 *     summary="Create a question in a test",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the test will be located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique int identifier of the test in which the question will be added.",
 *          in="path",
 *          name="test_id",
 *          required=true,
 *          @OA\Schema(type="integer"),
 *     ),
 *     @OA\RequestBody(
 *          @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"title", "type"},
 *                 @OA\Property(
 *                     property="title",
 *                     type="string",
 *                     description="<small>Unique string identifier for this question</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="type",
 *                     type="integer",
 *                     description="<small>The type of question</small>
 *  1: Multiple choice
 *  2: Multiple answers
 *  3: Fill blanks or form
 *  4: Matching
 *  5: Open question
 *  13: Oral expression
 *  6: Image zones
 *  8: Hotspot delineation (Use only if test type is 1: Adaptative test with immediate feedback, or 3: Direct feedback as pop-up)
 *  9: Exact Selection
 *  10: Unique answer with unknown
 *  11: Multiple answer true/false/don't know
 *  22: Multiple answer true/false/degree of certainty
 *  12: Combination true/false/don't-know
 *  14: Global multiple answer
 *  16: Calculated question
 *  17: Unique answer image
 *  18: Sequence ordering
 *  19: Match by dragging
 *  20: Annotation
 *  21: Reading comprehension"
 *                 ),
 *                 @OA\Property(
 *                     property="description",
 *                     type="string",
 *                     description="<small>This question description. Can be HTML</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="ponderation",
 *                     type="integer",
 *                     description="<small>This question ponderation. (Some question types get this calculated automatically based on the correct answer ponderation.)</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="picture",
 *                     type="string",
 *                     description="<small>The numeric id of the document/image as string</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="level",
 *                     type="integer",
 *                     description="<small>level of difficulty</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="extra",
 *                     type="string",
 *                     description="<small>This variable is used when loading an exercise like an scenario with an special hotspot: final_overlap, final_missing, final_excess</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="category",
 *                     type="integer",
 *                     description="<small>Question category id</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="feedback",
 *                     type="string",
 *                     description="<small>Feedback for this question (just if question feedback is enabled in the chamilo installaton)</small>"
 *                 ),
 * 
 *             )
 *         )
 *     ),
 *     @OA\Response(response="201", description="Created"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

/* 
Question types:
'UNIQUE_ANSWER': 1
'MULTIPLE_ANSWER': 2
'FILL_IN_BLANKS': 3
'MATCHING': 4
'FREE_ANSWER': 5
'HOT_SPOT': 6
'HOT_SPOT_ORDER': 7
'HOT_SPOT_DELINEATION': 8
'MULTIPLE_ANSWER_COMBINATION': 9
'UNIQUE_ANSWER_NO_OPTION': 10
'MULTIPLE_ANSWER_TRUE_FALSE': 11
'MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE': 12
'ORAL_EXPRESSION': 13
'GLOBAL_MULTIPLE_ANSWER': 14
'MEDIA_QUESTION': 15
'CALCULATED_ANSWER': 16
'UNIQUE_ANSWER_IMAGE': 17
'DRAGGABLE': 18
'MATCHING_DRAGGABLE': 19
'ANNOTATION': 20
'READING_COMPREHENSION': 21
'MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY': 22
*/

$endpoint->post('/course/{course_code}/test/{test_id}/question', function (Request $req, Response $res, $args) use ($endpoint) {
    //Validate args
    Validator::validate($req, $args, new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'test_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('numeric')
            ]),
        ]
    ]));
        
    $data = json_decode($req->getBody()->getContents(), true);
    //Validate params
    Validator::validate($req, $data, new Assert\Collection([
        'fields' => [
            'title' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'type' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('integer'), new Assert\PositiveOrZero(),
                new Assert\Range([
                    'min' => 1,
                    'max' => 22,
                ])
            ]),
            'description' => new Assert\Optional([new Assert\Type('string')]),
            'ponderation' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'picture' => new Assert\Optional([new Assert\Type('string')]),
            'level' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'extra' => new Assert\Optional([new Assert\Type('string')]),
            'category' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'feedback' => new Assert\Optional([new Assert\Type('string')]),
        ]
    ]));

    $course = api_get_course_info($args['course_code']);
    if (empty($course))
        throwException($req,'404', 'Could not find course with course code: ' . $args['course_code']);

    $courseId = $course['real_id'];
    $exercise = new Exercise($courseId);
    if(!$exercise->read($args['test_id']))
        throwException($req, '404', "Couldn't find exercise with id = " . $args['test_id']);

    $question = new CWQuestion();
    $question->question = $data['title'];
    $question->description = $data['description'] ?: '';
    $question->weighting = $data['ponderation'] ?: 0;
    $question->type = $data['type'];
    $question->picture = $data['picture'] ?: ''; //TODO: Add function to receive Base64 image and upload it to $question->picture
    $question->level = $data['level'] ?: 1;
    $question->course = $course;
    $question->extra = $data['extra'] ?: '';
    $question->category = $data['category'] ?: 0;
    $question->feedback = $data['feedback'] ?: null;
    $question->save($exercise);

    if (!$question->iid)
        throwException($req, '422', "Question could not be created");


    $res->getBody()
        ->write(json_encode($question, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return
        $res->withHeader("Content-Type", "application/json")
            ->withStatus(200);
});

/**
 * @OA\Patch(
 *     path="/course/{course_code}/test/{test_id}/question/{question_id}",
 *     tags={"Tests"},
 *     summary="Remove a question from a test.",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the parent course.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique integer identifier of the parent test.",
 *          in="path",
 *          name="test_id",
 *          required=true,
 *          @OA\Schema(type="integer"),
 *     ),
 *     @OA\Parameter(
 *          description="unique integer identifier of the parent question.",
 *          in="path",
 *          name="question_id",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Response(response="204", description="Success"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */


$endpoint->patch('/course/{course_code}/test/{test_id}/question/{question_id}', function (Request $req, Response $res, $args) use ($endpoint) {
    $res->withHeader("Content-Type", "application/json");

    Validator::validate($req, $args, new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Type('string'),
            'test_id' => new Assert\Type('numeric'),
            'question_id' => new Assert\Type('numeric'),
        ]
    ]));

    $course = api_get_course_info($args['course_code']);
    if (empty($course))
        throwException($req,'404', "Could not find course with course code = {$args['course_code']}");

    $courseId = $course['real_id'];
    $exercise = new Exercise($courseId);
    if (!$exercise->read($args['test_id']))
        throwException($req, '404', "Could not find exercise with id =  {$args['test_id']}");

    $question = Question::read($args['question_id'], $course, false);
    if (!$question)
        throwException($req, "404", "Could not find question with id = {$args['question_id']}");

    $deleted = $question->delete($exercise->iid);

    if(!$deleted)
        throwException($req, '0', "Couldn't delete question.");

    return $res->withStatus(204);
});

/**
 * @OA\Post(
 *     path="/course/{course_code}/question",
 *     tags={"Tests"},
 *     summary="Creates a question in a course",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the test will be located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\RequestBody(
 *          @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"title", "type"},
 *                 @OA\Property(
 *                     property="title",
 *                     type="string",
 *                     description="<small>Unique string identifier for this question</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="type",
 *                     type="integer",
 *                     description="<small>The type of question</small>
 *  1: Multiple choice
 *  2: Multiple answers
 *  3: Fill blanks or form
 *  4: Matching
 *  5: Open question
 *  13: Oral expression
 *  6: Image zones
 *  8: Hotspot delineation (Use only if test type is 1: Adaptative test with immediate feedback, or 3: Direct feedback as pop-up)
 *  9: Exact Selection
 *  10: Unique answer with unknown
 *  11: Multiple answer true/false/don't know
 *  22: Multiple answer true/false/degree of certainty
 *  12: Combination true/false/don't-know
 *  14: Global multiple answer
 *  16: Calculated question
 *  17: Unique answer image
 *  18: Sequence ordering
 *  19: Match by dragging
 *  20: Annotation
 *  21: Reading comprehension"
 *                 ),
 *                 @OA\Property(
 *                     property="description",
 *                     type="string",
 *                     description="<small>This question description. Can be HTML</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="ponderation",
 *                     type="integer",
 *                     description="<small>This question ponderation. (Some question types get this calculated automatically based on the correct answer ponderation.)</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="picture",
 *                     type="string",
 *                     description="<small>The numeric id of the document/image as string</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="level",
 *                     type="integer",
 *                     description="<small>level of difficulty</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="extra",
 *                     type="string",
 *                     description="<small>This variable is used when loading an exercise like an scenario with an special hotspot: final_overlap, final_missing, final_excess</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="category",
 *                     type="integer",
 *                     description="<small>Question category id</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="feedback",
 *                     type="string",
 *                     description="<small>Feedback for this question (just if question feedback is enabled in the chamilo installaton)</small>"
 *                 ),
 * 
 *             )
 *         )
 *     ),
 *     @OA\Response(response="201", description="Created"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

// [[TO DO: add new config params]]

/* 
Question types:
'UNIQUE_ANSWER': 1
'MULTIPLE_ANSWER': 2
'FILL_IN_BLANKS': 3
'MATCHING': 4
'FREE_ANSWER': 5
'HOT_SPOT': 6
'HOT_SPOT_ORDER': 7
'HOT_SPOT_DELINEATION': 8
'MULTIPLE_ANSWER_COMBINATION': 9
'UNIQUE_ANSWER_NO_OPTION': 10
'MULTIPLE_ANSWER_TRUE_FALSE': 11
'MULTIPLE_ANSWER_COMBINATION_TRUE_FALSE': 12
'ORAL_EXPRESSION': 13
'GLOBAL_MULTIPLE_ANSWER': 14
'MEDIA_QUESTION': 15
'CALCULATED_ANSWER': 16
'UNIQUE_ANSWER_IMAGE': 17
'DRAGGABLE': 18
'MATCHING_DRAGGABLE': 19
'ANNOTATION': 20
'READING_COMPREHENSION': 21
'MULTIPLE_ANSWER_TRUE_FALSE_DEGREE_CERTAINTY': 22
*/

$endpoint->post('/course/{course_code}/question', function (Request $req, Response $res, $args) use ($endpoint) {
    //Validate args
    Validator::validate($req, $args, new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
        ]
    ]));

    $data = json_decode($req->getBody()->getContents(), true);
    //Validate params
    Validator::validate($req, $data, new Assert\Collection([
        'fields' => [
            'title' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'type' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('integer'), new Assert\PositiveOrZero(),
                new Assert\Range([
                    'min' => 1,
                    'max' => 22,
                ])
            ]),
            'description' => new Assert\Optional([new Assert\Type('string')]),
            'ponderation' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'picture' => new Assert\Optional([new Assert\Type('string')]),
            'level' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'extra' => new Assert\Optional([new Assert\Type('string')]),
            'category' => new Assert\Optional([new Assert\Type('integer'), new Assert\PositiveOrZero()]),
            'feedback' => new Assert\Optional([new Assert\Type('string')]),
        ]
    ]));

    $course = api_get_course_info($args['course_code']);
    if (empty($course))
        throwException($req, '404', 'Could not find course with course code: ' . $args['course_code']);

    $courseId = $course['real_id'];

    $question = new CWQuestion();
    $question->question = $data['title'];
    $question->description = $data['description'] ?: '';
    $question->weighting = $data['ponderation'] ?: 0;
    $question->type = $data['type'];
    $question->picture = $data['picture'] ?: ''; //TODO: Add function to receive Base64 image and upload it to $question->picture
    $question->level = $data['level'] ?: 1;
    $question->course = $course;
    $question->extra = $data['extra'] ?: '';
    $question->category = $data['category'] ?: 0;
    $question->feedback = $data['feedback'] ?: null;
    $question->save(new Exercise($courseId));

    if (!$question->iid)
        throwException($req, '422', "Question could not be created");


    $res->getBody()
        ->write(json_encode($question, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return
        $res->withHeader("Content-Type", "application/json")
        ->withStatus(200);
});


/**
 * @OA\Get(
 *     path="/course/{course_code}/question/{question_id}",
 *     tags={"Tests"},
 *     summary="Get a question from a course",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the test will be located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique int identifier of the test in which the question will be added.",
 *          in="path",
 *          name="question_id",
 *          required=true,
 *          @OA\Schema(type="integer"),
 *     ),
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->get('/course/{course_code}/question/{question_id}', function (Request $req, Response $res, $args) use ($endpoint) {
    //Validate args
    Validator::validate($req, $args, new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'question_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('numeric')
            ]),
        ]
    ]));

    $course = api_get_course_info($args['course_code']);
    if (empty($course))
        throwException($req, '404', 'Could not find course with course code: ' . $args['course_code']);

    $question = Question::read($args['question_id'], $course, true);
    if (!$question)
        throwException($req, "404", "Could not find question with id = {$args['question_id']}");

    $res->getBody()
        ->write(json_encode($question, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return
        $res->withHeader("Content-Type", "application/json")
        ->withStatus(200);
});

/**
 * @OA\Delete(
 *     path="/course/{course_code}/question/{question_id}",
 *     tags={"Tests"},
 *     summary="Delete a question and remove it from all tests.",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the test will be located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique int identifier of the test in which the question will be added.",
 *          in="path",
 *          name="question_id",
 *          required=true,
 *          @OA\Schema(type="integer"),
 *     ),
 *     @OA\Response(response="204", description="Success"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->delete('/course/{course_code}/question/{question_id}', function (Request $req, Response $res, $args) use ($endpoint) {
    //Validate args
    Validator::validate($req, $args, new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'question_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('numeric')
            ]),
        ]
    ]));

    $course = api_get_course_info($args['course_code']);
    if (empty($course))
        throwException($req, '404', 'Could not find course with course code: ' . $args['course_code']);

    $question = Question::read($args['question_id'], $course, true);
    if (!$question)
        throwException($req, "404", "Could not find question with id = {$args['question_id']}");

    $deleted = $question->delete();
    if (!$deleted)
        throwException($req, "422", "Question could not be deleted.");

    $res->getBody()
        ->write(json_encode($question, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return
        $res->withHeader("Content-Type", "application/json")
        ->withStatus(200);
});

/**
 * @OA\Post(
 *     path="/course/{course_code}/question/{question_id}/image",
 *     tags={"Tests"},
 *     summary="Upload an image into a question",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the parent course.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique string identifier of the parent question.",
 *          in="path",
 *          name="question_id",
 *          required=true,
 *          @OA\Schema(type="integer"),
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
 *              )
 *         ),
 *     ),
 *     @OA\Response(response="201", description="Created"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

$endpoint->post('/course/{course_code}/question/{question_id}/image', function (Request $req, Response $res, $args) use ($endpoint) {
    $uploadedFiles = $req->getUploadedFiles() ? $_FILES : [];

    Validator::validate($req, array_merge($args, $uploadedFiles), new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'question_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('numeric')
            ]),
            'imageUpload' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('array'),
                new Assert\All([
                    new Assert\NotBlank()
                ]),
            ]),
        ]
    ]));


    $course = api_get_course_info($args['course_code']);
    if (!$course)
        throwException($req, '404', "Course with code `{$args['course_code']}` not found.");

    $question = Question::read($args['question_id'], $course, false);
    if (!$question)
        throwException($req, "404", "Could not find question with id = {$args['question_id']}");

    $uploaded = $question->uploadPicture($uploadedFiles['imageUpload']['tmp_name']);
    $question->save(new Exercise());

    if (!$uploaded)
        throwException($req, '422', "Image coud not be uploaded.");

    $res->getBody()
        ->write(json_encode($question, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));

    return
        $res->withHeader("Content-Type", "application/json")
            ->withStatus(201);
});

/**
 * @OA\Get(
 *     path="/course/{course_code}/test/{test_id}/question/{question_id}/answers", tags={"Tests"},
 *     summary="Get answers from a question",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the tests are located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique integer identifier of the test.",
 *          in="path",
 *          name="test_id",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique integer identifier of the question you wish to retrieve answers from.",
 *          in="path",
 *          name="question_id",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Response(response="200", description="Success"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */


// [[TO DO: create get functionality]]

$endpoint->get('/course/{course_code}/test/{test_id}/question/{question_id}/answers', function (Request $req, Response $res, $args) use ($endpoint) {

    Validator::validate($req, $args, new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Type('string'),
            'test_id' => new Assert\Type('numeric'),
            'question_id' => new Assert\Type('numeric'),
        ]
    ]));

    $course = api_get_course_info($args['course_code']);
    if (!$course)
        throwException($req, '404', "Course with code `{$args['course_code']}` not found.");

    $courseId = $course['real_id'];
    $exercise = new Exercise($courseId);
    if (!$exercise->read($args['test_id']))
        throwException($req, '404', "Could not find exercise with id =  {$args['test_id']}");

    $question = Question::read($args['question_id'], $course, false);
    if (!$question)
        throwException($req, '404', "Could not find question with id =  {$args['question_id']}");

    $answer = new Answer(
        $args['question_id'],
        $courseId,
        $exercise
    );
    $answers = $answer->getAnswers();

    $res->getBody()->write(json_encode($answers, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return
        $res->withHeader("Content-Type", "application/json")
        ->withStatus(200);
});

/**
 * @OA\Post(
 *     path="/course/{course_code}/test/{test_id}/question/{question_id}/answer",
 *     tags={"Tests"},
 *     summary="Add an answer to a question. _TODO: Add suport for 'Scenario' question types (https://docs.chamilo.org/teacher-guide/interactivity_tests/creating_a_new_test)_",
 *     security={{"bearerAuth": {}}},
 *     @OA\Parameter(
 *          description="unique string identifier of the course in which the answer will be located.",
 *          in="path",
 *          name="course_code",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique int identifier of the test the answer will belong to.",
 *          in="path",
 *          name="test_id",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\Parameter(
 *          description="unique int identifier of the question in which the answer will be added.",
 *          in="path",
 *          name="question_id",
 *          required=true,
 *          @OA\Schema(type="string"),
 *     ),
 *     @OA\RequestBody(
 *          @OA\MediaType(
 *             mediaType="application/json",
 *             @OA\Schema(
 *                 required={"title"},
 *                 @OA\Property(
 *                     property="title",
 *                     type="string",
 *                     description="<small>Unique string identifier for this question</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="correct",
 *                     type="integer",
 *                     description="<small>1 or 0 if this is a correct answer. In case the question type is 'Matching' this should be the id of the answer option that matches with this one. In a 'matching' question type you have pairs of answers, just one answer of that pair should have the 'correct' and 'ponderation' fields filled.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="ponderation",
 *                     type="integer",
 *                     description="<small>This answer ponderation. It can be negative.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="comment",
 *                     type="string",
 *                     description="<small>This answer feedback or comment.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="position",
 *                     type="integer",
 *                     description="<small>The position of this answer in the list of possible answers. Unconfirmed: For 'matching', the options should skip one position. (match: 1,2,3 options: 5,6,7)</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="hotspot_coordinates",
 *                     type="string",
 *                     description="<small>Example from database: 86;508|15|31</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="hotspot_type",
 *                     type="string",
 *                     description="<small>One of: square, circle, poly.</small>"
 *                 ),
 *                 @OA\Property(
 *                     property="destination",
 *                     type="string",
 *                     description="<small>Not documented yet. Not necessary for most questions. It is used in 'scenario' question types ('inmmediate feedback') for just two question types (multiple choice, and Hotspot delineation). For multiple choice defaults to 0@@0@@0@@0</small>"
 *                 ),
 *             )
 *         )
 *     ),
 *     @OA\Response(response="201", description="Created"),
 *     @OA\Response(response="4XX",ref="#/components/responses/ClientError"),
 *     @OA\Response(response="5XX",ref="#/components/responses/ServerError"),
 * )
 */

// [[TO DO: add new config params]]

$endpoint->post('/course/{course_code}/test/{test_id}/question/{question_id}/answer', function (Request $req, Response $res, $args) use ($endpoint) {
    $data = json_decode($req->getBody()->getContents(), true);
    
    Validator::validate($req, array_merge($args, $data), new Assert\Collection([
        'fields' => [
            'course_code' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'test_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('numeric')
            ]),
            'question_id' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('numeric')
            ]),
            'title' => new Assert\Required([
                new Assert\NotBlank(),
                new Assert\Type('string')
            ]),
            'correct' => new Assert\Optional([ new Assert\Type('integer') ]),
            'ponderation' => new Assert\Optional([new Assert\Type('integer')]),
            'comment' => new Assert\Optional([new Assert\Type('string')]),
            'position' => new Assert\Optional([new Assert\Type('integer')]),
            'hotspot_coordinates' => new Assert\Optional([new Assert\Type('string')]),
            'hotspot_type' => new Assert\Optional([
                new Assert\Type('string'),
                new Assert\Choice(['square', 'circle', 'poly']),
            ]),
            'destination' => new Assert\Optional([new Assert\Type('string')]),
        ]
    ]));

    $course = api_get_course_info($args['course_code']);
    if (!$course)
        throwException($req, '404', "Course with code `{$args['course_code']}` not found.");

    $courseId = $course['real_id'];
    $exercise = new Exercise($courseId);
    if (!$exercise->read($args['test_id']))
        throwException($req, '404', "Could not find exercise with id =  {$args['test_id']}");

    $question = Question::read($args['question_id'], $course, false);
    if (!$question)
        throwException($req, '404', "Could not find question with id =  {$args['question_id']}");

    $answer = new Answer(
        $args['question_id'],
        $courseId,
        $exercise
    );
    $oldAnswers = $answer->getAnswers();
    $answersCount = $answer->selectNbrAnswers();
    for ($i=0; $i < $answersCount; $i++) {
        $answer->createAnswer(
            $oldAnswers[$i]['answer'],
            $oldAnswers[$i]['correct'],
            $oldAnswers[$i]['comment'],
            $oldAnswers[$i]['ponderation'],
            $oldAnswers[$i]['position'],
            $oldAnswers[$i]['hotspot_coordinates'],
            $oldAnswers[$i]['hotspot_type'],
            $oldAnswers[$i]['destination']
        );
    }
    $answer->createAnswer(
        $data['title'],
        $data['correct'] ?: 0,
        $data['comment'],
        $data['ponderation'],
        $data['position'] ?: max($answer->position) + 1,
        $data['hotspot_coordinates'] ?: null,
        $data['hotspot_type'] ?: null,
        $data['destination'] ?: ($exercise->selectType() === 1 ? '0@@0@@0@@0' : '') //TODO: Add suport for "Scenario" question types (https://docs.chamilo.org/teacher-guide/interactivity_tests/creating_a_new_test)
    );
    $answer->save();

    if ($answersCount >= $answer->getAnswers())
        throwException($req, '422', "Answer could not be created.");

    $res->getBody()
        ->write(json_encode($answer->getAnswers(), JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT));
    return
        $res->withHeader("Content-Type", "application/json")
        ->withStatus(201);
});