<?php
    // mode (json)
    $error_mode = "json";
    
    // libraries & acl
    require_once "common.php";
    
    // include specific libraries for quizzes
    // require_once realpath(ROOT . ".." . DIRECTORY_SEPARATOR . ".." . DIRECTORY_SEPARATOR . "mod"  . DIRECTORY_SEPARATOR . "quiz" . DIRECTORY_SEPARATOR . "lib.php");
    
    // check input data
    if (!isset($_REQUEST["q"]) OR
        !isset($_REQUEST["from"]) OR
        !isset($_REQUEST["to"])) {
        GISMOutil::gismo_error('err_missing_parameters', $error_mode);
        exit;
    } else {
        $query = addslashes($_REQUEST["q"]);
        $course_id = intval($srv_data->course_id);
        $from = intval($_REQUEST["from"]);
        $to = intval($_REQUEST["to"]);
    }
    
    // SECURITY (prevent users hacks)
    $query = explode("@", $query);
    $query = $actor . "@" . $query[1];
    
    // details
    $subtype = (isset($_REQUEST["subtype"])) ? $_REQUEST["subtype"] : "";
    
    // current user id
    $current_user_id = intval($USER->id);
    
    // GET CONTEXT DATA (course, students)
    
    
    // get course
    $course = $DB->get_record("course", array("id" => $course_id));
    if ($course === FALSE) {
        GISMOutil::gismo_error('err_course_not_set', $error_mode);
        exit;
    }
    
    // get users
    $context = get_context_instance(CONTEXT_COURSE, $course->id);
    if ($context === FALSE) {
        GISMOutil::gismo_error('err_missing_course_students', $error_mode);
        exit;
    }
    $users = get_users_by_capability($context, "block/gismo:track-user");
    if ($users === FALSE) {
        GISMOutil::gismo_error('err_missing_course_students', $error_mode);
        exit;
    }
    
    
    // SQL FILTERS OFTEN/ALWAYS USED
    
    
    // elaborate course filter
    $course_sql = "course = ?";
    $course_params = array($course_id);
    
    // elaborate time filter
    $time_sql = "time BETWEEN ? AND ?";
    $time_params = array($from, $to);
    
    // elaborate userid filter
    $userid_sql = "";
    $userid_params = array();
    if (is_array($users) AND count($users) > 0) {
        list($userid_sql, $userid_params) = $DB->get_in_or_equal(array_keys($users));
        $userid_sql = "userid " . $userid_sql;
    }
    
    // course, time and users filters combined
    $ctu_filters = implode(" AND ", array_filter(array($course_sql, $time_sql, $userid_sql)));  // remove null values / empty strings / ... before imploding
    $ctu_params = array_merge($course_params, $time_params, $userid_params);
    
    
    // BUILD RESULT
    
    
    // result
    $result = new stdClass();
    $result->name = '';
    $result->links = null;
    $result->data = array();
    $result->error = "";
    $result->arr = array();
    $result->context = $context;
    $result->users = $users;
    
    // extract data
    switch ($query) {
        case "teacher@student-accesses":
        case "teacher@student-accesses-overview":
            // chart title
            switch($query) {
//LkM79 - error: it was the followinbg: case "student-accesses-overview":
                case "teacher@student-accesses-overview":
                    $lang_index = "student_accesses_overview_chart_title";
                    break;
//LkM79 - error: it was the followinbg: case "student-accesses":
                case "teacher@student-accesses":
                default:
                    $lang_index = "student_accesses_chart_title";
                    break;
            }
            $result->name = get_string($lang_index, "block_gismo");
            // links
            $result->links = null;
            // chart data
            $student_resource_access = $DB->get_records_select("block_gismo_sl", $ctu_filters, $ctu_params, "time ASC");
            // build result 
            if ($student_resource_access !== false) {
                // evaluate start date and end date
                // 1. get min date and max date
                // 2. from min date to first of the month
                //    from max date to last of the month
                // 3. evaluate difference in days between the two dates
                if (is_array($student_resource_access) AND count($student_resource_access) > 0) {
                    // 1. min and max date
                    $keys = array_keys($student_resource_access);
                    $min_date = $student_resource_access[$keys[0]]->timedate;
                    $max_date = $student_resource_access[$keys[count($student_resource_access)-1]]->timedate;
                    // adjust values
                    $mid = explode("-", $min_date);
                    $mad = explode("-", $max_date);
                    $min_date = date("Y-m-d", mktime(0, 0 ,0 , $mid[1], 1, $mid[0]));
                    $min_datetime = date("Y-m-d H:i:s", mktime(0, 0 ,0 , $mid[1], 1, $mid[0]));
                    $max_date = date("Y-m-d", mktime(0, 0 ,0 , $mad[1] + 1, 0, $mad[0]));
                    $max_datetime = date("Y-m-d H:i:s", mktime(0, 0 ,0 , $mad[1] + 1, 0, $mad[0]));
                    // diff
                    $days = intval(GISMOutil::days_between_dates($max_datetime, $min_datetime));
                    // save results
                    $extra_info = new stdClass();
                    $extra_info->min_date = $min_date;
                    $extra_info->max_date = $max_date;
                    $extra_info->num_days = $days;
                    $result->extra_info = $extra_info;
                }
                $result->data = $student_resource_access;
            }
            break;
        case "teacher@student-resources-access":
            switch ($subtype) {
                case "users-details":
                    // check student id
                    if (isset($_REQUEST["id"])) {
                        // chart title
                        $result->name = get_string("student_resources_details_chart_title", "block_gismo");
                        //$result->name = get_string("student_resources_overview_chart_title", "block_gismo");
                        // links
                        $result->links = "<a href='javascript:void(0);' onclick='javascript:g.analyse(\"student-resources-access\");'><img src=\"images/back.png\" alt=\"Close details\" title=\"Close details\" /></a>";
                        // filters
                        $filters = implode(" AND ", array_filter(array($course_sql, $time_sql, "userid = ?")));  // remove null values / empty strings / ... before imploding
                        $params = array_merge($course_params, $time_params, array(intval($_REQUEST["id"])));
                        // get data
                        $student_resource_access = $DB->get_records_select("block_gismo_resource", $filters . " AND restype NOT IN ('book')", $params, "time ASC");
                        // build result
                        if ($student_resource_access !== false) {
                            // evaluate start date and end date
                            // 1. get min date and max date
                            // 2. from min date to first of the month
                            //    from max date to last of the month
                            // 3. evaluate difference in days between the two dates
                            if (is_array($student_resource_access) AND count($student_resource_access) > 0) {
                                // 1. min and max date
                                $keys = array_keys($student_resource_access);
                                $min_date = $student_resource_access[$keys[0]]->timedate;
                                $max_date = $student_resource_access[$keys[count($student_resource_access)-1]]->timedate;
                                // adjust values
                                $mid = explode("-", $min_date);
                                $mad = explode("-", $max_date);
                                $min_date = date("Y-m-d", mktime(0, 0 ,0 , $mid[1], 1, $mid[0]));
                                $min_datetime = date("Y-m-d H:i:s", mktime(0, 0 ,0 , $mid[1], 1, $mid[0]));
                                $max_date = date("Y-m-d", mktime(0, 0 ,0 , $mad[1] + 1, 0, $mad[0]));
                                $max_datetime = date("Y-m-d H:i:s", mktime(0, 0 ,0 , $mad[1] + 1, 0, $mad[0]));
                                // diff
                                $days = intval(GISMOutil::days_between_dates($max_datetime, $min_datetime));
                                // save results
                                $extra_info = new stdClass();
                                $extra_info->min_date = $min_date;
                                $extra_info->max_date = $max_date;
                                $extra_info->num_days = $days;
                                $result->extra_info = $extra_info;
                            }
                            $result->data = $student_resource_access;
                        }
                    }
                    break;
                default:
                    // chart title
                    $result->name = get_string("student_resources_overview_chart_title", "block_gismo");
                    // get data
                    $student_resource_access = $DB->get_records_select("block_gismo_resource", $ctu_filters . " AND restype NOT IN ('book')", $ctu_params, "time ASC");
                    // build result
                    if ($student_resource_access !== false) {
                        $result->data = $student_resource_access;
                    }
                    break;
            }
            break;
        case "student@resources-students-overview":
            // overwrite userid filter (for max value)
            $userid_sql = "userid = ?";
            $userid_params = array($current_user_id);
            // overwrite ctu filters
            $ctu_filters = implode(" AND ", array_filter(array($course_sql, $time_sql, $userid_sql)));  // remove null values / empty strings / ... before imploding
            $ctu_params = array_merge($course_params, $time_params, $userid_params);
        case "teacher@resources-students-overview":
            // chart title
            $result->name = get_string("resources_students_overview_chart_title", "block_gismo");
            // links
            $result->links = null;
            // chart data
            $resource_accesses = $DB->get_records_select("block_gismo_resource", $ctu_filters, $ctu_params, "time ASC");
            // extra info (get max value)
            $ei_sql = implode(" AND ", array_filter(array($course_sql, $userid_sql))) . " AND restype NOT IN ('book') GROUP BY userid, resid";
            $ei_params = array_merge($course_params, $userid_params);
            $ei = $DB->get_records_select("block_gismo_resource", $ei_sql, $ei_params, "value DESC", "MAX(id), SUM(numval) AS value", 0, 1);
            // save extra info
            $extra_info = (object) array("max_value" => 0);
            if (is_array($ei) AND count($ei) > 0) {
                $extra_info->max_value = array_pop($ei)->value;
            }
            // result
            if ($resource_accesses !== false) {
                $result->extra_info = $extra_info;
                $result->data = $resource_accesses;
            }
            break;
        case "teacher@resources-access":
            switch ($subtype) {
                case "resources-details":
                    // check resource id
                    if (isset($_REQUEST["id"])) {
                        // chart title
                        $result->name = get_string("resources_access_detail_chart_title", "block_gismo");
                        // links
                        $result->links = "<a href='javascript:void(0);' onclick='javascript:g.analyse(\"resources-access\");'><img src=\"images/back.png\" alt=\"Close details\" title=\"Close details\" /></a>";
                        // filters
                        $filters = implode(" AND ", array_filter(array($course_sql, $time_sql, "resid = ?")));  // remove null values / empty strings / ... before imploding
                        $params = array_merge($course_params, $time_params, array(intval($_REQUEST["id"])));
                        // chart data
                        $resource_accesses = $DB->get_records_select("block_gismo_resource", $filters . " AND restype NOT IN ('book')", $params, "time ASC");
                        // result
                        if ($resource_accesses !== false) {
                            // evaluate start date and end date
                            // 1. get min date and max date
                            // 2. from min date to first of the month
                            //    from max date to last of the month
                            // 3. evaluate difference in days between the two dates
                            if (is_array($resource_accesses) AND count($resource_accesses) > 0) {
                                // 1. min and max date
                                $keys = array_keys($resource_accesses);
                                $min_date = $resource_accesses[$keys[0]]->timedate;
                                $max_date = $resource_accesses[$keys[count($resource_accesses)-1]]->timedate;
                                // adjust values
                                $mid = explode("-", $min_date);
                                $mad = explode("-", $max_date);
                                $min_date = date("Y-m-d", mktime(0, 0 ,0 , $mid[1], 1, $mid[0]));
                                $min_datetime = date("Y-m-d H:i:s", mktime(0, 0 ,0 , $mid[1], 1, $mid[0]));
                                $max_date = date("Y-m-d", mktime(0, 0 ,0 , $mad[1] + 1, 0, $mad[0]));
                                $max_datetime = date("Y-m-d H:i:s", mktime(0, 0 ,0 , $mad[1] + 1, 0, $mad[0]));
                                // diff
                                $days = intval(GISMOutil::days_between_dates($max_datetime, $min_datetime));
                                // save results
                                $extra_info = new stdClass();
                                $extra_info->min_date = $min_date;
                                $extra_info->max_date = $max_date;
                                $extra_info->num_days = $days;
                                $result->extra_info = $extra_info;
                            }
                            $result->data = $resource_accesses;
                        }
                    }
                    break;
                default:
                    // chart title
                    $result->name = get_string("resources_access_overview_chart_title", "block_gismo");
                    // links
                    $result->links = null;
                    // chart data
                    $resource_accesses = $DB->get_records_select("block_gismo_resource", $ctu_filters . " AND restype NOT IN ('book')", $ctu_params, "time ASC");
                    // result
                    if ($resource_accesses !== false) {
                        $result->data = $resource_accesses;
                    }
                    break;
            }
            break;
        case "student@resources-access":
            // chart title
            $result->name = get_string("resources_access_overview_chart_title", "block_gismo");
            // links
            $result->links = null;
            // add filters to extract only data related to the student
            $ctu_filters .= "AND userid = ?";
            array_push($ctu_params, $current_user_id);
            // chart data
            $resource_accesses = $DB->get_records_select("block_gismo_resource", $ctu_filters . " AND restype NOT IN ('book')", $ctu_params, "time ASC");
            // result
            if ($resource_accesses !== false) {
                $result->data = $resource_accesses;
            }
            break;
        case "teacher@student-books-access":
            switch ($subtype) {
                case "users-details":
                    // check student id
                    if (isset($_REQUEST["id"])) {
                        // chart title
                        $result->name = get_string("student_books_details_chart_title", "block_gismo");
                        //$result->name = get_string("student_resources_overview_chart_title", "block_gismo");
                        // links
                        $result->links = "<a href='javascript:void(0);' onclick='javascript:g.analyse(\"student-books-access\");'><img src=\"images/back.png\" alt=\"Close details\" title=\"Close details\" /></a>";
                        // filters
                        $filters = implode(" AND ", array_filter(array($course_sql, $time_sql, "userid = ?"))) . " AND restype = 'book'";  // remove null values / empty strings / ... before imploding
                        $params = array_merge($course_params, $time_params, array(intval($_REQUEST["id"])));
                        // get data
                        $student_book_access = $DB->get_records_select("block_gismo_resource", $filters, $params, "time ASC");
                        // build result
                        if ($student_book_access !== false) {
                            // evaluate start date and end date
                            // 1. get min date and max date
                            // 2. from min date to first of the month
                            //    from max date to last of the month
                            // 3. evaluate difference in days between the two dates
                            if (is_array($student_book_access) AND count($student_book_access) > 0) {
                                // 1. min and max date
                                $keys = array_keys($student_book_access);
                                $min_date = $student_book_access[$keys[0]]->timedate;
                                $max_date = $student_book_access[$keys[count($student_book_access)-1]]->timedate;
                                // adjust values
                                $mid = explode("-", $min_date);
                                $mad = explode("-", $max_date);
                                $min_date = date("Y-m-d", mktime(0, 0 ,0 , $mid[1], 1, $mid[0]));
                                $min_datetime = date("Y-m-d H:i:s", mktime(0, 0 ,0 , $mid[1], 1, $mid[0]));
                                $max_date = date("Y-m-d", mktime(0, 0 ,0 , $mad[1] + 1, 0, $mad[0]));
                                $max_datetime = date("Y-m-d H:i:s", mktime(0, 0 ,0 , $mad[1] + 1, 0, $mad[0]));
                                // diff
                                $days = intval(GISMOutil::days_between_dates($max_datetime, $min_datetime));
                                // save results
                                $extra_info = new stdClass();
                                $extra_info->min_date = $min_date;
                                $extra_info->max_date = $max_date;
                                $extra_info->num_days = $days;
                                $result->extra_info = $extra_info;
                            }
                            $result->data = $student_book_access;
                        }
                    }
                    break;
                default:
                    // chart title
                    $result->name = get_string("student_books_overview_chart_title", "block_gismo");
                    // get data
                    $student_book_access = $DB->get_records_select("block_gismo_resource", $ctu_filters . " AND restype = 'book'", $ctu_params, "time ASC");
                    // build result
                    if ($student_book_access !== false) {
                        $result->data = $student_book_access;
                    }
                    break;
            }
            break;
        case "student@books-students-overview":
            // overwrite userid filter (for max value)
            $userid_sql = "userid = ?";
            $userid_params = array($current_user_id);
            // overwrite ctu filters
            $ctu_filters = implode(" AND ", array_filter(array($course_sql, $time_sql, $userid_sql)));  // remove null values / empty strings / ... before imploding
            $ctu_params = array_merge($course_params, $time_params, $userid_params);
        case "teacher@books-students-overview":
            // chart title
            $result->name = get_string("books_students_overview_chart_title", "block_gismo");
            // links
            $result->links = null;
            // chart data
            $book_accesses = $DB->get_records_select("block_gismo_resource", $ctu_filters . " AND restype = 'book'", $ctu_params, "time ASC");
            // extra info (get max value)
            $ei_sql = implode(" AND ", array_filter(array($course_sql, $userid_sql))) . " AND restype = 'book' GROUP BY userid, resid";
            $ei_params = array_merge($course_params, $userid_params);
            $ei = $DB->get_records_select("block_gismo_resource", $ei_sql, $ei_params, "value DESC", "MAX(id), SUM(numval) AS value", 0, 1);
            // save extra info
            $extra_info = (object) array("max_value" => 0);
            if (is_array($ei) AND count($ei) > 0) {
                $extra_info->max_value = array_pop($ei)->value;
            }
            // result
            if ($book_accesses !== false) {
                $result->extra_info = $extra_info;
                $result->data = $book_accesses;
            }
            break;
        case "teacher@books-access":
            switch ($subtype) {
                case "books-details":
                    // check resource id
                    if (isset($_REQUEST["id"])) {
                        // chart title
                        $result->name = get_string("student_books_details_chart_title", "block_gismo");
                        // links
                        $result->links = "<a href='javascript:void(0);' onclick='javascript:g.analyse(\"books-access\");'><img src=\"images/back.png\" alt=\"Close details\" title=\"Close details\" /></a>";
                        // filters
                        $filters = implode(" AND ", array_filter(array($course_sql, $time_sql, "resid = ?"))) . " AND restype = 'book'";  // remove null values / empty strings / ... before imploding
                        $params = array_merge($course_params, $time_params, array(intval($_REQUEST["id"])));
                        // chart data
                        $book_accesses = $DB->get_records_select("block_gismo_resource", $filters, $params, "time ASC");
                        // result
                        if ($book_accesses !== false) {
                            // evaluate start date and end date
                            // 1. get min date and max date
                            // 2. from min date to first of the month
                            //    from max date to last of the month
                            // 3. evaluate difference in days between the two dates
                            if (is_array($book_accesses) AND count($book_accesses) > 0) {
                                // 1. min and max date
                                $keys = array_keys($book_accesses);
                                $min_date = $book_accesses[$keys[0]]->timedate;
                                $max_date = $book_accesses[$keys[count($book_accesses)-1]]->timedate;
                                // adjust values
                                $mid = explode("-", $min_date);
                                $mad = explode("-", $max_date);
                                $min_date = date("Y-m-d", mktime(0, 0 ,0 , $mid[1], 1, $mid[0]));
                                $min_datetime = date("Y-m-d H:i:s", mktime(0, 0 ,0 , $mid[1], 1, $mid[0]));
                                $max_date = date("Y-m-d", mktime(0, 0 ,0 , $mad[1] + 1, 0, $mad[0]));
                                $max_datetime = date("Y-m-d H:i:s", mktime(0, 0 ,0 , $mad[1] + 1, 0, $mad[0]));
                                // diff
                                $days = intval(GISMOutil::days_between_dates($max_datetime, $min_datetime));
                                // save results
                                $extra_info = new stdClass();
                                $extra_info->min_date = $min_date;
                                $extra_info->max_date = $max_date;
                                $extra_info->num_days = $days;
                                $result->extra_info = $extra_info;
                            }
                            $result->data = $book_accesses;
                        }
                    }
                    break;
                default:
                    // chart title
                    $result->name = get_string("books_access_overview_chart_title", "block_gismo");
                    // links
                    $result->links = null;
                    // chart data
                    $book_accesses = $DB->get_records_select("block_gismo_resource", $ctu_filters . " AND restype = 'book'", $ctu_params, "time ASC");
                    // result
                    if ($book_accesses !== false) {
                        $result->data = $book_accesses;
                    }
                    break;
            }
            break;
        case "student@books-access":
            // chart title
            $result->name = get_string("books_access_overview_chart_title", "block_gismo");
            // links
            $result->links = null;
            // add filters to extract only data related to the student
            $ctu_filters .= "AND userid = ?";
            array_push($ctu_params, $current_user_id);
            // chart data
            $book_accesses = $DB->get_records_select("block_gismo_resource", $ctu_filters . " AND restype = 'book'", $ctu_params, "time ASC");
            // result
            if ($book_accesses !== false) {
                $result->data = $book_accesses;
            }
            break;
        case "teacher@assignments":
        case "student@assignments":
            // chart title
            $result->name = get_string("assignments_chart_title", "block_gismo");
            // links
            $result->links = null;
            $course_id = intval($course_id);
            // chart data (select s.id because the stupid moodle get_records__sql function set array key with the first selected field (use a unique key to avoid data loss))
            $qry = "
                SELECT s.id, s.userid, gg.finalgrade as grade, gg.timemodified as timemarked, a.id AS test_id, a.grade AS test_max_grade
                FROM {assign} AS a
                    INNER JOIN {assign_submission} AS s ON a.id = s.assignment
                    INNER JOIN (SELECT id, iteminstance FROM {grade_items} WHERE itemtype = 'mod' AND itemmodule = 'assign' AND courseid = $course_id) gi ON a.id = gi.iteminstance
                    INNER JOIN {grade_grades} gg ON (gg.itemid=gi.id AND gg.userid=s.userid)
                WHERE a.course = $course_id AND s.timemodified BETWEEN " . $from . " AND " . $to . "
            ";
            // need to filter on user id ?
            if ($query === "student@assignments") {
                $qry .= " AND s.userid = " . $current_user_id;
            }
            $entries = $DB->get_records_sql($qry);
            // build result
            if (is_array($entries) AND count($entries) > 0 AND
                is_array($users) AND count($users) > 0) {
                foreach ($entries as $entry) {
                    if (array_key_exists($entry->userid, $users)) {
                        // standard item
                        $item = array(
                            "test_id" => $entry->test_id,
                            "test_max_grade" => $entry->test_max_grade,
                            "userid" => $entry->userid,
                            "user_grade" => ($entry->grade == null) ? -1 : $entry->grade,                  // -1 if it hasn't been corrected
                            "user_grade_label" => sprintf("%s / %s", $entry->grade, $entry->test_max_grade),
                            "test_timemarked" => $entry->timemarked         // 0 if it hasn't been corrected
                        );
                        // net to extract custom grade scale ?
                        if (intval($entry->test_max_grade) < 0 AND intval($entry->grade) !== -1) {
                            // scale id
                            $scale_id = abs($entry->test_max_grade);
                            // get scale
                            try {
                                $scale = $DB->get_field("scale", "scale", array("id" => $scale_id), MUST_EXIST);
                                $scale_values = explode(",", $scale);
                                $ug_idx = intval($entry->grade) - 1;
                                if (is_array($scale_values) AND count($scale_values) > 0 AND array_key_exists($ug_idx, $scale_values)) {
                                    $item["test_max_grade"] = count($scale_values);
                                    $item["user_grade_label"] = trim($scale_values[$ug_idx]);
                                }
                            } catch(Exception $e) {
                                echo "ERROR";
                            }
                        }
                        // store item
                        array_push($result->data, $item);
                    }
                }
            }
            break;
        case "teacher@quizzes":
        case "student@quizzes":
            // chart title
            $result->name = get_string("quizzes_chart_title", "block_gismo");
            // links
            $result->links = null;
            // chart data
            $qry = "
                SELECT g.id, g.userid, g.grade, g.timemodified, q.id AS test_id, q.grade AS test_max_grade, q.decimalpoints AS decimalpoints
                FROM {quiz} AS q INNER JOIN {quiz_grades} AS g ON q.id = g.quiz
                WHERE q.course = " . intval($course_id) . " AND g.timemodified BETWEEN " . $from . " AND " . $to . "
            ";
            // need to filter on user id ?
            if ($query === "student@quizzes") {
                $qry .= " AND g.userid = " . $current_user_id;
            }
            $entries = $DB->get_records_sql($qry);
            // build result
            if (is_array($entries) AND count($entries) > 0 AND
                is_array($users) AND count($users) > 0) {
                foreach ($entries as $entry) {
                    if (array_key_exists($entry->userid, $users)) {
                        $item = array(
                            "test_id" => $entry->test_id,
                            "test_max_grade" => $entry->test_max_grade,
                            "userid" => $entry->userid,
                            "user_grade" => $entry->grade,
                            "user_grade_label" => sprintf("%s / %s", format_float($entry->grade, $entry->decimalpoints), format_float($entry->test_max_grade, $entry->decimalpoints)),
                            "submission_time" => $entry->timemodified
                        );
                        array_push($result->data, $item);
                    }
                }
            }
            break;
        case "teacher@chats":
        case "teacher@forums":
        case "teacher@glossaries":
        case "teacher@wikis":
            // specific info
            $spec_info = array(
                "teacher@chats" => array("title" => "chats_chart_title", "subtitle" => "chats_ud_chart_title", "activity" => "chat", "back" => "chats"),
                "teacher@forums" => array("title" => "forums_chart_title", "subtitle" => "forums_ud_chart_title", "activity" => "forum", "back" => "forums"),
                "teacher@glossaries" => array("title" => "glossaries_chart_title", "subtitle" => "glossaries_ud_chart_title", "activity" => "glossary", "back" => "glossaries"),
                "teacher@wikis" => array("title" => "wikis_chart_title", "title" => "wikis_ud_chart_title", "activity" => "wiki", "back" => "wikis")
            );
            switch ($subtype) {
                case "users-details":
                    // user id filter
                    $ctu_filters .= "AND userid = ?";
                    array_push($ctu_params, (isset($_REQUEST["id"])) ? intval($_REQUEST["id"]) : -1);
                    // chart title
                    $result->name = get_string($spec_info[$query]["subtitle"], "block_gismo");
                    // links
                    $result->links = "<a href='javascript:void(0);' onclick='javascript:g.analyse(\"" . $spec_info[$query]["back"] ."\");'><img src=\"images/back.png\" alt=\"Close details\" title=\"Close details\" /></a>";
                    break;
                default:
                    // chart title
                    $result->name = get_string($spec_info[$query]["title"], "block_gismo");
                    // links
                    $result->links = null;
                    break;
            }
            // add filters to extract data related to the selected activity only
            $ctu_filters .= "AND activity = ?";
            array_push($ctu_params, $spec_info[$query]["activity"]);
            // chart data
            
            $activity_data = $DB->get_records_select("block_gismo_activity", $ctu_filters, $ctu_params, "time ASC");
            // result
            $result->error = $ctu_filters;
            $result->arr = $ctu_params;
            
            if (is_array($activity_data) AND count($activity_data) > 0) {
                $result->data = $activity_data;
            }
            break;
        case "student@chats-over-time":
        case "student@forums-over-time":
        case "student@glossaries-over-time":
        case "student@wikis-over-time":
            // add filters to extract data related to the current student only and then do
            // the same things as for teacher
            $ctu_filters .= "AND userid = ? ";
            array_push($ctu_params, $current_user_id);
        case "teacher@chats-over-time":
        case "teacher@forums-over-time":
        case "teacher@glossaries-over-time":
        case "teacher@wikis-over-time":
            // specific info
            $spec_info = array(
                "teacher@chats-over-time" => array("title" => "chats_over_time_chart_title", "activity" => "chat"),
                "teacher@forums-over-time" => array("title" => "forums_over_time_chart_title", "activity" => "forum"),
                "teacher@glossaries-over-time" => array("title" => "glossaries_over_time_chart_title", "activity" => "glossary"),
                "teacher@wikis-over-time" => array("title" => "wikis_over_time_chart_title", "activity" => "wiki"),
                "student@chats-over-time" => array("title" => "chats_over_time_chart_title", "activity" => "chat"),
                "student@forums-over-time" => array("title" => "forums_over_time_chart_title", "activity" => "forum"),
                "student@glossaries-over-time" => array("title" => "glossaries_over_time_chart_title", "activity" => "glossary"),
                "student@wikis-over-time" => array("title" => "wikis_over_time_chart_title", "activity" => "wiki")
            );
            // chart title
            $result->name = get_string($spec_info[$query]["title"], "block_gismo");
            // links
            $result->links = null;
            // add filters to extract data related to the selected activity only
            $ctu_filters .= "AND activity = ? AND context = ?";
            array_push($ctu_params, $spec_info[$query]["activity"]);
            array_push($ctu_params, "write");
            // chart data
            $activity_data = $DB->get_records_select("block_gismo_activity", $ctu_filters, $ctu_params, "time ASC");
            // result
            if (is_array($activity_data) AND count($activity_data) > 0) {
                // keys
                $keys = array_keys($activity_data);
                // extra info
                $extra_info = new stdClass();
                $extra_info->min_date = GISMOutil::this_month_first_day_time($activity_data[$keys[0]]->time);
                $extra_info->max_date = GISMOutil::this_month_last_day_time($activity_data[$keys[count($keys) - 1]]->time);
                $extra_info->num_days = intval(GISMOutil::days_between_times($extra_info->max_date, $extra_info->min_date));
                $extra_info->min_date = date("Y-m-d", $extra_info->min_date);
                $extra_info->max_date = date("Y-m-d", $extra_info->max_date);
                $result->extra_info = $extra_info;
                // save data
                $result->data = $activity_data;
            }
            break;
        case "student@chats":
        case "student@forums":
        case "student@glossaries":
        case "student@wikis":
            // specific info
            $spec_info = array(
                "student@chats" => array("title" => "chats_chart_title", "activity" => "chat"),
                "student@forums" => array("title" => "forums_chart_title", "activity" => "forum"),
                "student@glossaries" => array("title" => "glossaries_chart_title", "activity" => "glossary"),
                "student@wikis" => array("title" => "wikis_chart_title", "activity" => "wiki")
            );
            // chart title
            $result->name = get_string($spec_info[$query]["title"], "block_gismo");
            // links
            $result->links = null;
            // add filters to extract data related to the selected activity only
            $ctu_filters .= "AND activity = ? AND userid = ? ";
            array_push($ctu_params, $spec_info[$query]["activity"]);
            array_push($ctu_params, $current_user_id);
            // chart data
            $activity_data = $DB->get_records_select("block_gismo_activity", $ctu_filters, $ctu_params, "time ASC");
            // result
            if (is_array($activity_data) AND count($activity_data) > 0) {
                // save data
                $result->data = $activity_data;
            }
            break;
        default:
            break;
    }
    
    // echo json encoded result
    echo json_encode($result);
?>