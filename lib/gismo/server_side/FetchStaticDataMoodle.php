<?php
// this class is used to fetch static data
class FetchStaticDataMoodle {
    // course data
    protected $id;
    protected $timecreated;
    protected $fullname;

    protected $course;
    
    // actor
    protected $actor;

    // analysis start date / time
    protected $start_date;
    protected $start_time;

    // analysis end date / time
    protected $end_date;
    protected $end_time;

    // useful fields
    protected $users_ids;
    protected $teachers_ids;

    // Json fields
    protected $users;
    protected $groups;
    protected $teachers;
    protected $resources;
    protected $books;
    protected $assignments;
    protected $chats;
    protected $forums;
    protected $glossaries;
    protected $quizzes;
    protected $wikis;
    
    // constructor
    public function __construct($id, $actor) {
        $this->id = $id;
        $this->actor = $actor;
    }

    // getter
    public function __get($name) {
        return (property_exists($this, $name)) ? $this->$name : null;
    }

    // init
    public function init() {
        // check variable
        $check = true;
        // fetch data
        $check &= $this->FetchInfo();
        $check &= $this->FetchUsers();
        $check &= $this->FetchGroups();
        $check &= $this->FetchTeachers();
        $check &= $this->FetchResources();
        $check &= $this->FetchBooks();
        $check &= $this->FetchAssignments();
        $check &= $this->FetchChats();
        $check &= $this->FetchForums();
        $check &= $this->FetchGlossaries();
        $check &= $this->FetchQuizzes();
        $check &= $this->FetchWikis();
        // start date / time
        $check &= $this->FetchStartDateAndTime();
        // return result
        return $check;
    }

    // fetch course info
    protected function FetchInfo() {
        global $DB;
        // check variable
        $check = true;
        // fetch course
        $record = $DB->get_record("course", array("id" => $this->id));
        // save data
        if ($record !== FALSE) {
            $this->timecreated = $record->timecreated;
            $this->coursestart = $record->startdate;
            $this->fullname = $record->fullname;
            $this->course = $record;
        } else {
            $check = false;
        }
        // return result
        return $check;
    }
    
    // fetch users
    protected function FetchUsers() {
        global $USER;
        // default variables
        $check = false;
        $this->users = "[]";
        switch ($this->actor) {
            case "teacher":
                // fetch students
                $context = get_context_instance(CONTEXT_COURSE, $this->course->id);
                if ($context !== FALSE) {
                    $users = get_users_by_capability($context, "block/gismo:track-user", "", "lastname, firstname");
                    // save data
                    if ($users !== FALSE) {
                        $json_users = array();
                        $check = true;
                        if (is_array($users) AND count($users) > 0) {
                            foreach ($users as $user) {
                                $json_users[] = array("id" => $user->id,
                                                      "name" => ucfirst($user->lastname)." ".ucfirst($user->firstname),
                                                      "visible" => "1");
                            }
                            $this->users = json_encode($json_users);
                            $this->users_ids = array_keys($users);
                        }
                    }
                }
                break;
            default:
                $json_users = array();
                $json_users[] = array("id" => $USER->id,
                                      "name" => ucfirst($USER->lastname)." ".ucfirst($USER->firstname),
                                      "visible" => "1");
                $this->users = json_encode($json_users);
                $this->users_ids = array($USER->id);
                break;
        }
        // return result
        return $check;
    }

    // fetch groups
    protected function FetchGroups() {
        global $CFG, $DB;
        // default variables
        $check = false;
        $this->groups = "[]";
        $groupings = array();
        $groupcount = 0;
        if ($records = $DB->get_records('groupings', array('courseid'=>$this->course->id), 'name', 'id, name')) {
            $check = true;
            foreach ($records as $rec) {
                $groupings[$rec->id] = array('name'=>$rec->name, 'groups'=>array());
                if ($groups = groups_get_all_groups($this->id, 0, $rec->id)) {
                    uasort($groups, 'obj_name_sort_compare');
                    foreach ($groups as $group) {
                        $groupings[$rec->id]['groups'][$group->id] = array('name'=>format_string($group->name), 'members'=>array());
                        if ($members = groups_get_members($group->id, $fields='u.id')) {
                            foreach ($members as $member) {
                                $groupings[$rec->id]['groups'][$group->id]['members'][] = $member->id;
                                $groupcount++;
                            }
                        }
                    }
                }
            }
        }
        // Groups not in groupings
        $sql = "SELECT g.id, name 
                FROM {groups} g LEFT JOIN {groupings_groups} gg ON g.id=gg.groupid 
                WHERE g.courseid={$this->id} AND gg.groupingid IS NULL";
        if ($records = $DB->get_records_sql($sql)) {
            $check = true;
            $groupings[-1] = array('name'=>get_string('not_in_a_grouping', 'block_gismo'), 'groups'=>array());
            uasort($records, 'obj_name_sort_compare');
            foreach ($records as $rec) {
                $groupings[-1]['groups'][$rec->id] = array('name'=>format_string($rec->name), 'members'=>array());
                if ($members = groups_get_members($rec->id, $fields='u.id')) {
                    foreach ($members as $member) {
                        $groupings[-1]['groups'][$rec->id]['members'][] = $member->id;
                        $groupcount++;
                    }
                }
            }
        }

        // save data
        if ($check) {
            $groupings['length'] = $groupcount;
            $this->groups = json_encode($groupings);
        }
        // return true even if there are no groups in this course
        return true;
    }
    
    // fetch teachers
    protected function FetchTeachers() {
        // default variables
        $check = false;
        $this->teachers = "[]";
        switch ($this->actor) {
            case "teacher":
                // fetch teachers
                $context = get_context_instance(CONTEXT_COURSE, $this->course->id);
                if ($context !== FALSE) {
                    $teachers = get_users_by_capability($context, "block/gismo:track-teacher", "", "lastname, firstname");
                    // save data
                    if ($teachers !== FALSE) {
                        $json_teachers = array();
                        $check = true;
                        if (is_array($teachers) AND count($teachers) > 0) {
                            foreach ($teachers as $teacher) {
                                $json_teachers[] = array("id" => $teacher->id,
                                                      "name" => ucfirst($teacher->lastname)." ".ucfirst($teacher->firstname),
                                                      "visible" => "1");
                            }
                            $this->teachers = json_encode($json_teachers);
                            $this->teachers_ids = array_keys($teachers);
                        }
                    }
                }
                break;
            default:
                break;
        }
        // return result
        return $check;
    }
    
    // fetch course modules ordered by position
    protected function FetchCourseModulesOrderedByPosition($modulenames, $course, $userid, $includeinvisible) {
        $ordered_modules = array();
        if (is_array($modulenames) AND count($modulenames) > 0) {
            $modules = array();
            // extract modules instances specified in $modulenames
            $tmp_modules = array(); 
            foreach ($modulenames as $m) {
                $tmp = get_all_instances_in_course($m, $course, $userid, $includeinvisible);
                if (is_array($tmp) AND count($tmp) > 0) {
                    foreach ($tmp as $t) {
                        $reduced = array_intersect_key((array) $t, array("coursemodule" => "", "id" => "", "course" => "", "name" => "", "visible" => ""));
                        $reduced["type"] = $m;
                        array_push($tmp_modules, (object) $reduced);
                    }
                }
                unset($tmp);
            }
            // sort modules instances by position
            if (is_array($tmp_modules) AND count($tmp_modules) > 0) {
                // MOODLE BUG (get_all_instances_in_course doesn't return an array indexed by cm.id) START
                foreach ($tmp_modules as $tm) {
                    $modules[$tm->coursemodule] = $tm;
                }
                unset($tmp_modules);
                // MOODLE BUG (get_all_instances_in_course doesn't return an array indexed by cm.id) END
                $sections = get_all_sections($this->id);
                if (is_array($sections) AND count($sections) > 0) {
                    foreach ($sections as $s) {
                        if (!is_null($s->sequence)) {
                            $sequences = explode(",", $s->sequence);
                            if (is_array($sequences) AND count($sequences) > 0) {
                                foreach ($sequences as $sq) {
                                    if (array_key_exists($sq, $modules)) {
                                        $ordered_modules[$sq] = $modules[$sq];
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }
        return $ordered_modules;
    }
    
    // fetch resources
    protected function FetchResources() {
        global $USER;
        // default variables
        $check = false;
        $this->resources = "[]";
        // fetch resources
        // $resources = get_all_instances_in_course("resource", $this->course, null, true);
        $resources = $this->FetchCourseModulesOrderedByPosition(array("folder", "imscp", "page", "resource", "url"), $this->course, $USER->id, true);
        // save data
        if ($resources !== FALSE) {
            $json_resources = array();
            $check = true;
            if (is_array($resources) AND count($resources) > 0) {
                foreach ($resources as $resource) {
                    $json_resources[] = array(
                        "id" => $resource->id,
                        "name" => $resource->name,
                        "visible" => $resource->visible,
                        "type" => $resource->type
            );
                }
                $this->resources = json_encode($json_resources);
            }
        }
        // return result
        return $check;
    }
    
    // fetch books
    protected function FetchBooks() {
        global $USER;
        // default variables
        $check = false;
        $this->books = "[]";
        // fetch books
        $books = $this->FetchCourseModulesOrderedByPosition(array("book"), $this->course, $USER->id, true);
        // save data
        if ($books !== FALSE) {
            $json_books = array();
            $check = true;
            if (is_array($books) AND count($books) > 0) {
                foreach ($books as $book) {
                    $json_books[] = array(
                        "id" => $book->id,
                        "name" => $book->name,
                        "visible" => $book->visible,
                        "type" => $book->type
            );
                }
                $this->books = json_encode($json_books);
            }
        }
        // return result
        return $check;
    }

    // fetch assignments
    protected function FetchAssignments() {
        global $USER;
        // default variables
        $check = false;
        $this->assignments = "[]";
        // fetch assignments
        $assignments = get_all_instances_in_course("assign", $this->course, null, true);
        // save data
        if ($assignments !== FALSE) {
            $json_assignments = array();
            $check = true;
            if (is_array($assignments) AND count($assignments) > 0) {
                foreach ($assignments as $assignment) {
                    $json_assignments[] = array(
                        "id" => $assignment->id,
                        "name" => $assignment->name,
                        "timeavailable" => $assignment->timeavailable,
                        "gradeOver" => $assignment->grade,
                        "timedue" => $assignment->timedue,
                        "visible" => $assignment->visible
                    );
                }
                $this->assignments = json_encode($json_assignments);
            }
        }
        // return result
        return $check;
    }

    // fetch chats
    protected function FetchChats() {
        global $USER;
        // default variables
        $check = false;
        $this->chats = "[]";
        // fetch chats
        $chats = get_all_instances_in_course("chat", $this->course, null, true);
        // $chats = $this->FetchCourseModulesOrderedByPosition("chat", $this->course, $USER->id, true);
        // save data
        if (is_array($chats) AND count($chats) > 0) {
            $json_chats = array();
            $check = true;
            foreach ($chats as $chat) {
                $json_chats[] = array(
                    "id" => $chat->id,
                    "name" => $chat->name,
                    "visible" => $chat->visible
                );
            }
            $this->chats = json_encode($json_chats);
        }
        // return result
        return $check;
    }
    
    // fetch forums
    protected function FetchForums() {
        global $USER;
        // default variables
        $check = false;
        $this->forums = "[]";
        // fetch forums
        $forums = get_all_instances_in_course("forum", $this->course, null, true);
        // $forums = $this->FetchCourseModulesOrderedByPosition("forum", $this->course, $USER->id, true);
        // save data
        if (is_array($forums) AND count($forums) > 0) {
            $json_forums = array();
            $check = true;
            foreach ($forums as $forum) {
                $json_forums[] = array(
                    "id" => $forum->id,
                    "name" => $forum->name,
                    "visible" => $forum->visible
                );
            }
            $this->forums = json_encode($json_forums);
        }
        // return result
        return $check;
    }
    
    // fetch glossaries
    protected function FetchGlossaries() {
        global $USER;
        // default variables
        $check = false;
        $this->glossaries = "[]";
        // fetch glossaries
        $glossaries = get_all_instances_in_course("glossary", $this->course, null, true);
        // save data
        if (is_array($glossaries) AND count($glossaries) > 0) {
            $json_glossaries = array();
            $check = true;
            foreach ($glossaries as $glossary) {
                $json_glossaries[] = array(
                    "id" => $glossary->id,
                    "name" => $glossary->name,
                    "visible" => $glossary->visible
                );
            }
            $this->glossaries = json_encode($json_glossaries);
        }
        // return result
        return $check;
    }

    // fetch quizzes
    protected function FetchQuizzes() {
        global $USER;
        // default variables
        $check = false;
        $this->quizzes = "[]";
        // fetch quizzes
        $quizzes = get_all_instances_in_course("quiz", $this->course, null, true);
        // $quizzes = $this->FetchCourseModulesOrderedByPosition("quiz", $this->course, $USER->id, true);
        // save data
        if ($quizzes !== FALSE) {
            $json_quizzes = array();
            $check = true;
            if (is_array($quizzes) AND count($quizzes) > 0) {
                foreach ($quizzes as $quiz) {
                    $json_quizzes[] = array(
                        "id" => $quiz->id,
                        "name" => $quiz->name,
                        "timeopen_qui" => $quiz->timeopen,
                        "timeclose_qui" => $quiz->timeclose,
                        "visible" => $quiz->visible
                    );
                }
                $this->quizzes = json_encode($json_quizzes);
            }
        }
        // return result
        return $check;
    }
    
    // fetch wikis
    protected function FetchWikis() {
        global $USER;
        // default variables
        $check = false;
        $this->wikis = "[]";
        // fetch wikis
        $wikis = get_all_instances_in_course("wiki", $this->course, null, true);
        // $wikis = $this->FetchCourseModulesOrderedByPosition("wiki", $this->course, $USER->id, true);
        // save data
        if (is_array($wikis) AND count($wikis) > 0) {
            $json_wikis = array();
            $check = true;
            foreach ($wikis as $wiki) {
                $json_wikis[] = array(
                    "id" => $wiki->id,
                    "name" => $wiki->name,
                    "visible" => $wiki->visible
                );
            }
            $this->wikis = json_encode($json_wikis);
        }
        // return result
        return $check;
    }

    // fetch start date and time
    protected function FetchStartDateAndTime() {
        global $DB;
        
        // check variable
        $check = true;
        
        // select min date / time & max date / time for each log table
        
        // default
        $this->end_time = time();
        $this->end_date = date("Y-m-d", $this->end_time);
        $this->start_time = (empty($CFG->loglifetime)) ? $this->coursestart : ($this->end_time - ($CFG->loglifetime * 86400));
        $this->start_date = date("Y-m-d", $this->start_time);
        
        // adjust values according to logs
        if (is_array($this->users_ids) AND count($this->users_ids) > 0) {
            // useful data for queries
            $tables = array("block_gismo_activity", "block_gismo_resource", "block_gismo_sl");
            list($userid_sql, $params) = $DB->get_in_or_equal($this->users_ids);
            
            // push to the params array the course id
            array_push($params, $this->id);
            
            // get the lowest date & time from the gismo tables and adjust START date and time
            $time = null;
            $date = null;
            foreach ($tables as $table) {
                $tmp = $DB->get_records_select($table, "userid $userid_sql AND course = ?", $params, "time ASC", "id, time, timedate", 0, 1);
                if (is_array($tmp) AND count($tmp) > 0) {
                    $tmp = array_pop($tmp);
                    $time = (is_null($time) OR $tmp->time < $time) ? $tmp->time : $time;
                    $date = (is_null($date) OR $tmp->timedate < $date) ? $tmp->timedate : $date;
                }
            }
            if (!(is_null($time) AND is_null($date))) {
                $this->start_time = $time;
                $this->start_date = $date;
            }
            
            // get the highest date & time from the gismo tables and adjust END date and time
            $time = null;
            $date = null;
            foreach ($tables as $table) {
                $tmp = $DB->get_records_select($table, "userid $userid_sql AND course = ?", $params, "time DESC", "id, time, timedate", 0, 1);
                if (is_array($tmp) AND count($tmp) > 0) {
                    $tmp = array_pop($tmp);
                    $time = (is_null($time) OR $tmp->time > $time) ? $tmp->time : $time;
                    $date = (is_null($date) OR $tmp->timedate > $date) ? $tmp->timedate : $date;
                }
            }
            if (!(is_null($time) AND is_null($date))) {
                $this->end_time = $time;
                $this->end_date = $date;
            }
            
            // start date & time => to the first day of the month
            $this->start_time = GISMOutil::this_month_first_day_time($this->start_time);
            $this->start_date = date("Y-m-d", $this->start_time);
            
            // end date & time => to the first day of the next month
            $this->end_time = GISMOutil::next_month_first_day_time($this->end_time);
            $this->end_date = date("Y-m-d", $this->end_time);
        }
        // return result
        return $check;
    }
    
    public function checkData() {
        return ($this->checkUsers() AND ($this->checkResources() OR $this->checkActivities())) ? true : false;
    }
    
    public function checkUsers() {
        return ($this->users !== "[]") ? true : false;
    }
    
    public function checkTeachers() {
        return ($this->users !== "[]") ? true : false;
    }
    
    public function checkResources() {
        return ($this->resources !== "[]" OR $this->books !== "[]") ? true : false;
    }
    
    public function checkActivities() {
        return ($this->assignments !== "[]" OR $this->chats !== "[]" OR $this->forums !== "[]" OR $this->glossaries !== "[]" OR $this->quizzes !== "[]" OR $this->wikis !== "[]") ? true : false;
    }
}
?>