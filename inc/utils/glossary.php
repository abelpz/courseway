<?php
/* For licensing terms, see /license.txt */

/**
 * Class GlossaryManager
 * This library provides functions for the glossary tool.
 * Include/require it in your code to use its functionality.
 *
 * @author Julio Montoya
 * @author Christian Fasanando
 * @author Patrick Cool <patrick.cool@ugent.be>, Ghent University, Belgium januari 2009, dokeos 1.8.6
 */
class CWGlossaryManager
{
  /**
   * Get all glossary terms.
   *
   * @author Isaac Flores <isaac.flores@dokeos.com>
   *
   * @return array Contain glossary terms
   */
  public static function get_glossary_terms($course)
  {
    $glossary_data = [];
    $table = Database::get_course_table(TABLE_GLOSSARY);
    $session_id = api_get_session_id();
    $sql_filter = api_get_session_condition($session_id);
    $course_id = $course['real_id'];

    $sql = "SELECT glossary_id as id, name, description
		        FROM $table
		        WHERE c_id = $course_id $sql_filter";
    $rs = Database::query($sql);
    while ($row = Database::fetch_array($rs)) {
      $glossary_data[] = $row;
    }

    return $glossary_data;
  }

  /**
   * Get glossary description by glossary id.
   *
   * @author Isaac Flores <florespaz@bidsoftperu.com>
   *
   * @param int $glossary_id
   *
   * @return string The glossary description
   */
  public static function get_glossary_term_by_glossary_id($glossary_id, $course)
  {
    $table = Database::get_course_table(TABLE_GLOSSARY);
    $course_id = $course['real_id'];
    $glossary_id = (int) $glossary_id;

    $sql = "SELECT description
                FROM $table
                WHERE c_id = $course_id  AND glossary_id =" . $glossary_id;
    $rs = Database::query($sql);
    if (Database::num_rows($rs) > 0) {
      $row = Database::fetch_array($rs);

      return $row['description'];
    }

    return '';
  }

  /**
   * Get glossary term by glossary id.
   *
   * @author Isaac Flores <florespaz_isaac@hotmail.com>
   *
   * @param string $name The glossary term name
   *
   * @return array The glossary info
   */
  public static function get_glossary_term_by_glossary_name($name, $course)
  {
    $table = Database::get_course_table(TABLE_GLOSSARY);
    $session_id = api_get_session_id();
    $course_id = $course['real_id'];
    $sessionCondition = api_get_session_condition($session_id);

    $glossaryName = Security::remove_XSS($name);
    $glossaryName = api_convert_encoding($glossaryName, 'UTF-8', 'UTF-8');
    $glossaryName = trim($glossaryName);
    $parsed = $glossaryName;

    if (api_get_configuration_value('save_titles_as_html')) {
      $parsed = api_htmlentities($parsed);
      $parsed = "%$parsed%";
    }

    $sql = "SELECT * FROM $table
		        WHERE
		            c_id = $course_id AND
		            (
		                name LIKE '" . Database::escape_string($glossaryName) . "'
		                OR
		                name LIKE '" . Database::escape_string($parsed) . "'
                    )
                    $sessionCondition
                LIMIT 1
                ";
    $rs = Database::query($sql);

    if (Database::num_rows($rs) > 0) {
      return Database::fetch_array($rs, 'ASSOC');
    }

    return [];
  }

  /**
   * This functions stores the glossary in the database.
   *
   * @param array $values Array of title + description (name => $title, description => $comment)
   *
   * @return mixed Term id on success, false on failure
   */
  public static function save_glossary($values, $course, $user_id)
  {
    if (!is_array($values) || !isset($values['name'])) {
      return false;
    }

    // Database table definition
    $table = Database::get_course_table(TABLE_GLOSSARY);

    // get the maximum display order of all the glossary items
    $max_glossary_item = self::get_max_glossary_item($course);

    // session_id
    $session_id = api_get_session_id();

    // check if the glossary term already exists
    if (self::glossary_exists($values['name'], $course)) {
      return ['error' => get_lang('GlossaryTermAlreadyExistsYouShouldEditIt')];
    } else {
      $params = [
        'glossary_id' => 0,
        'c_id' => $course['real_id'],
        'name' => $values['name'],
        'description' => $values['description'],
        'display_order' => $max_glossary_item + 1,
        'session_id' => $session_id,
      ];
      $id = Database::insert($table, $params);

      if ($id) {
        $sql = "UPDATE $table SET glossary_id = $id WHERE iid = $id";
        Database::query($sql);

        //insert into item_property
        api_item_property_update(
          $course,
          TOOL_GLOSSARY,
          $id,
          'GlossaryAdded',
          $user_id
        );
      }

      return $id;
    }
  }

  /**
   * update the information of a glossary term in the database.
   *
   * @param array $values an array containing all the form elements
   *
   * @return bool True on success, false on failure
   */
  public static function update_glossary($values, $course, $user_id)
  {
    // Database table definition
    $table = Database::get_course_table(TABLE_GLOSSARY);
    $course_id = $course['real_id'];
    // check if the glossary term already exists
    if (self::glossary_exists($values['name'], $values['glossary_id'])) {
      return ['error' => get_lang('GlossaryTermAlreadyExistsYouShouldEditIt')];
    } else {
      $sql = "UPDATE $table SET
                        name = '" . Database::escape_string($values['name']) . "',
                        description	= '" . Database::escape_string($values['description']) . "'
					WHERE
					    c_id = $course_id AND
					    glossary_id = " . intval($values['glossary_id']);
      $result = Database::query($sql);
      if (false === $result) {
        return ['error' => 'Glossary update query failed.'];
      }

      //update glossary into item_property
      api_item_property_update(
        $course,
        TOOL_GLOSSARY,
        intval($values['glossary_id']),
        'GlossaryUpdated',
        $user_id
      );
    }

    return true;
  }

  /**
   * Get the maximum display order of the glossary item.
   *
   * @return int Maximum glossary display order
   */
  public static function get_max_glossary_item($course)
  {
    // Database table definition
    $table = Database::get_course_table(TABLE_GLOSSARY);
    $course_id = $course['real_id'];
    $get_max = "SELECT MAX(display_order) FROM $table
                    WHERE c_id = $course_id ";
    $res_max = Database::query($get_max);
    if (Database::num_rows($res_max) == 0) {
      return 0;
    }
    $row = Database::fetch_array($res_max);
    if (!empty($row[0])) {
      return $row[0];
    }

    return 0;
  }

  /**
   * check if the glossary term exists or not.
   *
   * @param string $term   Term to look for
   * @param int    $not_id ID to counter-check if the term exists with this ID as well (optional)
   *
   * @return bool True if term exists
   */
  public static function glossary_exists($term, $course, $not_id = '')
  {
    // Database table definition
    $table = Database::get_course_table(TABLE_GLOSSARY);
    $course_id = $course['real_id'];

    $sql = "SELECT name FROM $table
                WHERE
                    c_id = $course_id AND
                    name = '" . Database::escape_string($term) . "'";
    if ($not_id != '') {
      $sql .= " AND glossary_id <> '" . intval($not_id) . "'";
    }
    $result = Database::query($sql);
    $count = Database::num_rows($result);
    if ($count > 0) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Get one specific glossary term data.
   *
   * @param int $glossary_id ID of the glossary term
   *
   * @return mixed Array(glossary_id,name,description,glossary_display_order) or false on error
   */
  public static function get_glossary_information($glossary_id, $course)
  {
    // Database table definition
    $t_glossary = Database::get_course_table(TABLE_GLOSSARY);
    $t_item_propery = Database::get_course_table(TABLE_ITEM_PROPERTY);
    if (empty($glossary_id)) {
      return false;
    }
    $sql = "SELECT
                    g.glossary_id 		as glossary_id,
                    g.name 				as name,
                    g.description 		as description,
                    g.display_order		as glossary_display_order,
                    ip.insert_date      as insert_date,
                    ip.lastedit_date    as update_date,
                    g.session_id
                FROM $t_glossary g
                INNER JOIN $t_item_propery ip
                ON (g.glossary_id = ip.ref AND g.c_id = ip.c_id)
                WHERE
                    tool = '" . TOOL_GLOSSARY . "' AND
                    g.glossary_id = '" . intval($glossary_id) . "' AND
                    g.c_id = " . $course['real_id'] . " AND
                    ip.c_id = " . $course['real_id'];

    $result = Database::query($sql);
    if ($result === false || Database::num_rows($result) != 1) {
      return false;
    }

    return Database::fetch_array($result);
  }

  /**
   * Delete a glossary term (and re-order all the others).
   *
   * @param int  $glossary_id
   *
   * @return bool True on success, false on failure
   */
  public static function delete_glossary($glossary_id, $course, $user_id)
  {
    // Database table definition
    $table = Database::get_course_table(TABLE_GLOSSARY);
    $course_id = $course['real_id'];
    $glossaryInfo = self::get_glossary_information($glossary_id, $course);

    if (empty($glossaryInfo)) {
      return false;
    }

    $glossary_id = (int) $glossary_id;

    $sql = "DELETE FROM $table
                WHERE
                    c_id = $course_id AND
                    glossary_id='" . $glossary_id . "'";
    $result = Database::query($sql);
    if ($result === false || Database::affected_rows($result) < 1) {
      return false;
    }

    // update item_property (delete)
    api_item_property_update(
      $course,
      TOOL_GLOSSARY,
      $glossary_id,
      'delete',
      $user_id
    );

    // reorder the remaining terms
    self::reorder_glossary($course);

    return true;
  }


  /**
   * Get the number of glossary terms in the course (or course+session).
   *
   * @param  int     Session ID filter (optional)
   *
   * @return int Count of glossary terms
   */
  public static function get_number_glossary_terms($session_id = 0, $course)
  {
    // Database table definition
    $t_glossary = Database::get_course_table(TABLE_GLOSSARY);
    $course_id = $course['real_id'];
    $session_id = (int) $session_id;
    if (empty($session_id)) {
      $session_id = api_get_session_id();
    }
    $sql_filter = api_get_session_condition($session_id, true, true);

    $keyword = isset($_GET['keyword']) ? Database::escape_string($_GET['keyword']) : '';
    $keywordCondition = '';
    if (!empty($keyword)) {
      $keywordCondition = "AND (name LIKE '%$keyword%' OR description LIKE '%$keyword%')";
    }

    $sql = "SELECT count(glossary_id) as total
                FROM $t_glossary
                WHERE c_id = $course_id $sql_filter
                $keywordCondition ";

    $res = Database::query($sql);
    if ($res === false) {
      return 0;
    }

    $obj = Database::fetch_object($res);

    return $obj->total;
  }

  /**
   * Get all the data of a glossary.
   * @param string    $view            table or list
   * @param int    $from            From which item
   * @param int    $number_of_items Number of items to collect
   * @param string $column          Name of column on which to order
   * @param string $direction       Whether to sort in ascending (ASC) or descending (DESC)
   *
   * @return array
   */
  public static function get_glossary_data(
    $course,
    $view,
    $from,
    $number_of_items,
    $column,
    $direction
  ) {
    $_user = api_get_user_info();
    $t_glossary = Database::get_course_table(TABLE_GLOSSARY);
    $t_item_propery = Database::get_course_table(TABLE_ITEM_PROPERTY);

    $col2 = ' ';
    if (api_is_allowed_to_edit(null, true)) {
      $col2 = ' glossary.glossary_id	as col2, ';
    }

    // Condition for the session
    $session_id = api_get_session_id();
    $condition_session = api_get_session_condition(
      $session_id,
      true,
      true,
      'glossary.session_id'
    );

    $column = (int) $column;
    $from = (int) $from;
    $number_of_items = (int) $number_of_items;

    if (!in_array($direction, ['DESC', 'ASC'])) {
      $direction = 'ASC';
    }

    $keyword = isset($_GET['keyword']) ? Database::escape_string($_GET['keyword']) : '';
    $keywordCondition = '';
    if (!empty($keyword)) {
      $keywordCondition = "AND (glossary.name LIKE '%$keyword%' OR glossary.description LIKE '%$keyword%')";
    }
    $sql = "SELECT
                    glossary.name as col0,
					glossary.description as col1,
					$col2
					glossary.session_id
				FROM $t_glossary glossary
				INNER JOIN $t_item_propery ip
				ON (glossary.glossary_id = ip.ref AND glossary.c_id = ip.c_id)
				WHERE
					tool = '" . TOOL_GLOSSARY . "'
					$condition_session AND
					glossary.c_id = " . $course['real_id'] . " AND
					ip.c_id = " . $course['real_id'] . "
					$keywordCondition
		        ORDER BY col$column $direction
		        LIMIT $from, $number_of_items";

    $res = Database::query($sql);

    $return = [];
    $array = [];
    while ($data = Database::fetch_array($res)) {
      // Validation when belongs to a session
      $session_img = api_get_session_image($data['session_id'], $_user['status']);
      $array[0] = Security::remove_XSS($data[0]) . $session_img;

      if (!$view || $view === 'table') {
        $array[1] = Security::remove_XSS(str_replace(['<p>', '</p>'], ['', '<br />'], $data[1]));
      } else {
        $array[1] = $data[1];
      }

      if (isset($_GET['action']) && $_GET['action'] === 'export') {
        $array[1] = api_html_entity_decode($data[1]);
      }

      if (api_is_allowed_to_edit(null, true)) {
        $array[2] = $data[2];
      }
      $return[] = $array;
    }

    return $return;
  }

  /**
   * Re-order glossary.
   */
  public static function reorder_glossary($course)
  {
    // Database table definition
    $table = Database::get_course_table(TABLE_GLOSSARY);
    $course_id = $course['real_id'];
    $sql = "SELECT * FROM $table
                WHERE c_id = $course_id
                ORDER by display_order ASC";
    $res = Database::query($sql);

    $i = 1;
    while ($data = Database::fetch_array($res)) {
      $sql = "UPDATE $table SET display_order = $i
                    WHERE c_id = $course_id AND glossary_id = '" . intval($data['glossary_id']) . "'";
      Database::query($sql);
      $i++;
    }
  }

  /**
   * Move a glossary term.
   *
   * @param string $direction
   * @param string $glossary_id
   */
  public static function move_glossary($direction, $glossary_id, $course)
  {
    // Database table definition
    $table = Database::get_course_table(TABLE_GLOSSARY);

    // sort direction
    if ($direction === 'up') {
      $sortorder = 'DESC';
    } else {
      $sortorder = 'ASC';
    }
    $course_id = $course['real_id'];

    $sql = "SELECT * FROM $table
                WHERE c_id = $course_id
                ORDER BY display_order $sortorder";
    $res = Database::query($sql);
    $found = false;
    while ($row = Database::fetch_array($res)) {
      if ($found && empty($next_id)) {
        $next_id = $row['glossary_id'];
        $next_display_order = $row['display_order'];
      }

      if ($row['glossary_id'] == $glossary_id) {
        $current_id = $glossary_id;
        $current_display_order = $row['display_order'];
        $found = true;
      }
    }

    $sql1 = "UPDATE $table SET display_order = '" . Database::escape_string($next_display_order) . "'
                 WHERE c_id = $course_id  AND glossary_id = '" . Database::escape_string($current_id) . "'";
    $sql2 = "UPDATE $table SET display_order = '" . Database::escape_string($current_display_order) . "'
                 WHERE c_id = $course_id  AND glossary_id = '" . Database::escape_string($next_id) . "'";
    Database::query($sql1);
    Database::query($sql2);

    Display::addFlash(Display::return_message(get_lang('TermMoved')));
  }

  /**
   * Export to pdf.
   */
  public static function export_to_pdf($course)
  {
    $data = self::get_glossary_data(
      $course,
      'table',
      0,
      self::get_number_glossary_terms(api_get_session_id(), $course),
      0,
      'ASC'
    );
    $template = new Template('', false, false, false, true, false, false);
    $layout = $template->get_template('glossary/export_pdf.tpl');
    $template->assign('items', $data);

    $html = $template->fetch($layout);
    $courseCode = api_get_course_id();
    $pdf = new PDF();
    $pdf->content_to_pdf($html, '', get_lang('Glossary') . '_' . $courseCode, $courseCode);
  }

  /**
   * Generate a PDF with all glossary terms and move file to documents.
   *
   * @return bool false if there's nothing in the glossary
   */
  public static function movePdfToDocuments($course)
  {
    $sessionId = api_get_session_id();
    $courseId = $course['real_id'];
    $data = self::get_glossary_data(
      $course,
      'table',
      0,
      self::get_number_glossary_terms($sessionId, $course),
      0,
      'ASC'
    );

    if (!empty($data)) {
      $template = new Template('', false, false, false, true, false, false);
      $layout = $template->get_template('glossary/export_pdf.tpl');
      $template->assign('items', $data);
      $fileName = get_lang('Glossary') . '-' . api_get_local_time();
      $signatures = ['Drh', 'Teacher', 'Date'];

      $pdf = new PDF(
        'A4-P',
        'P',
        [
          'filename' => $fileName,
          'pdf_title' => $fileName,
          'add_signatures' => $signatures,
        ]
      );
      $pdf->exportFromHtmlToDocumentsArea(
        $template->fetch($layout),
        $fileName,
        $courseId
      );

      return true;
    } else {
      Display::addFlash(Display::return_message(get_lang('NothingToAdd')));
    }

    return false;
  }

  /**
   * @param string $format
   */
  public static function exportToFormat($format, $course)
  {
    if ($format == 'pdf') {
      self::export_to_pdf($course);

      return;
    }

    $data = self::get_glossary_data(
      $course,
      'table',
      0,
      self::get_number_glossary_terms(api_get_session_id(), $course),
      0,
      'ASC'
    );
    usort($data, 'sorter');
    $list = [];
    $list[] = ['term', 'definition'];
    $allowStrip = api_get_configuration_value('allow_remove_tags_in_glossary_export');
    foreach ($data as $line) {
      $definition = $line[1];
      if ($allowStrip) {
        // htmlspecialchars_decode replace &#39 to '
        // strip_tags remove HTML tags
        $definition = htmlspecialchars_decode(strip_tags($definition), ENT_QUOTES);
      }
      $list[] = [$line[0], $definition];
    }
    $filename = 'glossary_course_' . api_get_course_id();

    switch ($format) {
      case 'csv':
        Export::arrayToCsv($list, $filename);
        break;
      case 'xls':
        Export::arrayToXls($list, $filename);
        break;
    }
  }
}
