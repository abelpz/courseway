<?php

function createExercise($courseId, $data, $id = null, $type = ''){
  $exercise = new Exercise($courseId);

  if($id){
    $found = $exercise->read($id, false);
    if(!$found) {
      throw new Exception("Error: There is no test with the id: $id");
      return slim_msg('Error', "There is no test with the id: $id");
    }
  }

  $exercise->updateTitle(Exercise::format_title_variable($data['title']));
  $exercise->updateDescription($data['description']);
  $exercise->updateAttempts($data['attempts']);
  $exercise->updateFeedbackType($data['feedback_type']);
  $exercise->updateType($data['type']); //exercise type

  // If direct feedback then force to One per page
  if (EXERCISE_FEEDBACK_TYPE_DIRECT == $data['feedback_type']) {
    $exercise->updateType(ONE_PER_PAGE);
  }

  $exercise->setRandom($data['random']);//random questions
  $exercise->updateRandomAnswers($data['random_answers']);
  $exercise->updateResultsDisabled($data['results_disabled']);
  $exercise->updateExpiredTime($data['expired_time']);
  $exercise->updatePropagateNegative($data['propagate_neg']);
  $exercise->updateSaveCorrectAnswers($data['saveCorrectAnswers']);
  $exercise->updateRandomByCat($data['randomByCat']);
  $exercise->updateTextWhenFinished($data['text_when_finished']);
  $exercise->updateDisplayCategoryName($data['display_category_name']);
  $exercise->updateReviewAnswers($data['review_answers']);
  $exercise->updatePassPercentage($data['pass_percentage']);
  $exercise->updateCategories($data['categories']);
  $exercise->updateEndButton($data['endButton']);
  $exercise->setOnSuccessMessage($data['onSuccessMessage']);
  $exercise->setOnFailedMessage($data['onFailedMessage']);
  $exercise->updateEmailNotificationTemplate($data['emailNotificationTemplate']);
  $exercise->setEmailNotificationTemplateToUser($data['emailNotificationTemplateToUser']);
  $exercise->setNotifyUserByEmail($data['notifyUserByEmail']);
  $exercise->setModelType($data['modelType']);
  $exercise->setQuestionSelectionType($data['questionSelectionType']);
  $exercise->setHideQuestionTitle($data['hideQuestionTitle']);
  $exercise->sessionId = api_get_session_id();
  $exercise->setQuestionSelectionType($data['questionSelectionType']);
  $exercise->setScoreTypeModel($data['scoreTypeModel']);
  $exercise->setGlobalCategoryId($data['globalCategoryId']);
  $exercise->setShowPreviousButton($data['showPreviousButton']);
  $exercise->setNotifications($data['notifications']);
  $exercise->setExerciseCategoryId($data['exerciseCategoryId']);
  $exercise->setPageResultConfiguration($data);
  $showHideConfiguration = api_get_configuration_value('quiz_hide_question_number');
  if ($showHideConfiguration) {
    $exercise->setHideQuestionNumber($data['hideQuestionNumber']);
  }
  $exercise->preventBackwards = (int) $data['preventBackwards'];

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
  // if ($data['update_title_in_lps'] == 1) {
  //   $courseId = api_get_course_int_id();
  //   $table = Database::get_course_table(TABLE_LP_ITEM);
  //   $sql = "SELECT * FROM $table
  //   WHERE
  //   c_id = $courseId AND
  //   item_type = 'quiz' AND
  //   path = '".$exercise->iid."'
  //   ";
  //   $result = Database::query($sql);
  //   $items = Database::store_result($result);
  //   if (!empty($items)) {
  //     foreach ($items as $item) {
  //       $itemId = $item['iid'];
  //       $sql = "UPDATE $table SET title = '".$exercise->title."'
  //       WHERE iid = $itemId AND c_id = $courseId ";
  //       Database::query($sql);
  //     }
  //   }
  // }

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