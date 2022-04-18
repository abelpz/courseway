<?php

    function createCourse($data){
        $title = isset($data['title']) ? $data['title'] : '';
        $wantedCode = isset($data['wanted_code']) ? $data['wanted_code'] : null;
        $diskQuota = isset($data['disk_quota']) ? $data['disk_quota'] : '100';
        $visibility = isset($data['visibility']) ? (int) $data['visibility'] : null;
        $language = $data['language'] ?? '';
        $uid = $data['user_id'];

        if (isset($data['visibility'])) {
            if (
                $data['visibility'] &&
                $data['visibility'] >= 0 &&
                $data['visibility'] <= 3
            ) {
                $visibility = (int) $data['visibility'];
            }
        }

        $params = [];
        $params['title'] = $title;
        $params['wanted_code'] = $wantedCode;
        $params['user_id'] = $uid;
        $params['visibility'] = $visibility;
        $params['disk_quota'] = $diskQuota;
        $params['course_language'] = $language;

        foreach ($data as $key => $value) {
            if (substr($key, 0, 6) === 'extra_') { //an extra field
                $params[$key] = $value;
            }
        }
        
        $courseInfo = CourseManager::create_course($params, $params['user_id']);
        $results = [];
        if (!empty($courseInfo)) {
            $results['status'] = true;
            $results['code_course'] = $courseInfo['code'];
            $results['title_course'] = $courseInfo['title'];
            $extraFieldValues = new ExtraFieldValue('course');
            $extraFields = $extraFieldValues->getAllValuesByItem($courseInfo['real_id']);
            $results['extra_fields'] = $extraFields;
            $results['message'] = sprintf(get_lang('CourseXAdded'), $courseInfo['code']);
        } else {
            $results['status'] = false;
            $results['message'] = get_lang('CourseCreationFailed', true, 'english');
        }

        return $results;
    }

?>