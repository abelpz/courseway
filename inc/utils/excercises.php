<?php

class CWQuestion extends Question
{
  public function createAnswersForm($form)
  {
  }
  public function processAnswersCreation($form, $exercise)
  {
  }
}

function getExercises($course)
{

  $courseId = $course['real_id'];

  $table = Database::get_course_table(TABLE_QUIZ_TEST);

  $conditions = [
    'where' => ["active IN (1, 0) AND (session_id = 0 OR session_id IS NULL) AND c_id = ?" => [$courseId]],
    'order' => 'title',
  ];

  return Database::select('*', $table, $conditions);
}

function getExercise($course, $id)
{
  $table = Database::get_course_table(TABLE_QUIZ_TEST);
  $courseId = $course['real_id'];

  $id = (int) $id;
  if (!$courseId) {
    return false;
  }

  $conditions = [
    'where' => ["active IN (1, 0) AND (session_id = 0 OR session_id IS NULL) AND c_id = $courseId AND iid = ?" => [$id]],
    'order' => 'iid',
  ];

  return Database::select('*', $table, $conditions);
}

function createExercise($courseId, $data, $id = null, $type = '')
{
  $exercise = new Exercise($courseId);

  if ($id) {
    $found = $exercise->read($id, false);
    if (!$found) {
      throw new Exception("Error: There is no test with the id: $id");
      return slim_msg('Error', "There is no test with the id: $id");
    }
  }

  if ($data['title']) $exercise->updateTitle(Exercise::format_title_variable($data['title']));
  if ($data['description']) $exercise->updateDescription($data['description']);
  if ($data['attempts']) $exercise->updateAttempts($data['attempts'] ?? 0);
  if ($data['feedback_type']) $exercise->updateFeedbackType($data['feedback_type'] ?? 0);
  if ($data['type']) $exercise->updateType($data['type'] ?? ONE_PER_PAGE); //exercise type

  // If direct feedback then force to One per page
  if (EXERCISE_FEEDBACK_TYPE_DIRECT == $data['feedback_type']) {
    $exercise->updateType(ONE_PER_PAGE);
  }

  if ($data['random']) $exercise->setRandom($data['random'] ?? 0); //random questions
  if ($data['random_answers']) $exercise->updateRandomAnswers($data['random_answers'] ?? 0);
  if ($data['results_disabled']) $exercise->updateResultsDisabled($data['results_disabled'] ?? 0);
  if ($data['expired_time']) $exercise->updateExpiredTime($data['expired_time'] ?? 0);
  if ($data['propagate_neg']) $exercise->updatePropagateNegative($data['propagate_neg'] ?? 0);
  if ($data['saveCorrectAnswers']) $exercise->updateSaveCorrectAnswers($data['saveCorrectAnswers'] ?? 0);
  if ($data['randomByCat']) $exercise->updateRandomByCat($data['randomByCat'] ?? 0);
  if ($data['text_when_finished']) $exercise->updateTextWhenFinished($data['text_when_finished'] ?? '');
  if ($data['display_category_name']) $exercise->updateDisplayCategoryName($data['display_category_name'] ?? 1);
  if ($data['review_answers']) $exercise->updateReviewAnswers($data['review_answers'] ?? 0);
  if ($data['pass_percentage']) $exercise->updatePassPercentage($data['pass_percentage'] ?? 0);
  if ($data['categories']) $exercise->updateCategories($data['categories']);
  if ($data['endButton']) $exercise->updateEndButton($data['endButton']);
  if ($data['onSuccessMessage']) $exercise->setOnSuccessMessage($data['onSuccessMessage']);
  if ($data['onFailedMessage']) $exercise->setOnFailedMessage($data['onFailedMessage']);
  if ($data['emailNotificationTemplate']) $exercise->updateEmailNotificationTemplate($data['emailNotificationTemplate']);
  if ($data['emailNotificationTemplateToUser']) $exercise->setEmailNotificationTemplateToUser($data['emailNotificationTemplateToUser']);
  if ($data['notifyUserByEmail']) $exercise->setNotifyUserByEmail($data['notifyUserByEmail']);
  if ($data['modelType']) $exercise->setModelType($data['modelType'] ?? 1);
  if ($data['questionSelectionType']) $exercise->setQuestionSelectionType($data['questionSelectionType'] ?? 0);
  if ($data['hideQuestionTitle']) $exercise->setHideQuestionTitle($data['hideQuestionTitle'] ?? 0);
  $exercise->sessionId = api_get_session_id() ?? 0;
  if ($data['questionSelectionType']) $exercise->setQuestionSelectionType($data['questionSelectionType']);
  if ($data['scoreTypeModel']) $exercise->setScoreTypeModel($data['scoreTypeModel']);
  if ($data['globalCategoryId']) $exercise->setGlobalCategoryId($data['globalCategoryId']);
  if ($data['showPreviousButton']) $exercise->setShowPreviousButton($data['showPreviousButton']);
  if ($data['notifications']) $exercise->setNotifications($data['notifications']);
  if ($data['exerciseCategoryId']) $exercise->setExerciseCategoryId($data['exerciseCategoryId']);
  $exercise->setPageResultConfiguration($data);
  $showHideConfiguration = api_get_configuration_value('quiz_hide_question_number');
  if ($showHideConfiguration) {
    if ($data['hideQuestionNumber']) $exercise->setHideQuestionNumber($data['hideQuestionNumber']);
  }
  if ($data['preventBackwards']) $exercise->preventBackwards = (int) $data['preventBackwards'];

  $exercise->start_time = null;
  if ($data['activate_start_date_check'] == 1) {
    $start_time = $data['start_time'];
    $exercise->start_time = api_get_utc_datetime($start_time);
  }

  $exercise->end_time = null;
  if ($data['activate_end_date_check'] == 1) {
    $end_time = $data['end_time'];
    $exercise->end_time = api_get_utc_datetime($end_time);
  }

  $exercise->expired_time = 0;
  if ($data['enabletimercontrol'] == 1) {
    $expired_total_time = $data['expired_time'];
    if ($exercise->expired_time == 0) {
      $exercise->expired_time = $expired_total_time;
    }
  }

  // Update title in all LPs that have exercise quiz added
  if ($data['update_title_in_lps'] == 1) {
    $courseId = api_get_course_int_id();
    $table = Database::get_course_table(TABLE_LP_ITEM);
    $sql = "SELECT * FROM $table
    WHERE
    c_id = $courseId AND
    item_type = 'quiz' AND
    path = '" . $exercise->iid . "'
    ";
    $result = Database::query($sql);
    $items = Database::store_result($result);
    if (!empty($items)) {
      foreach ($items as $item) {
        $itemId = $item['iid'];
        $sql = "UPDATE $table SET title = '" . $exercise->title . "'
        WHERE iid = $itemId AND c_id = $courseId ";
        Database::query($sql);
      }
    }
  }

  $iid = $exercise->save($type);
  if (!empty($iid)) {
    $values = $data;
    $values['item_id'] = $iid;
    $extraFieldValue = new ExtraFieldValue('exercise');
    $extraFieldValue->saveFieldValues($values);
    return $iid;

    // Skill::saveSkills($data, ITEM_TYPE_EXERCISE, $iid);
  }
}
