<?php
class block_gismo extends block_base {
    protected $course;
    
    public function init() {
        $this->title = get_string('gismo', 'block_gismo');
    }

    public function has_config() {
        return false;
    }

    public function specialization() {
        global $DB, $COURSE;
        $this->course = $DB->get_record('course', array('id' => $COURSE->id));
    }

    public function get_content() {
        global $CFG, $OUTPUT;
        
        if ($this->content !== NULL) {
            return $this->content;
        }
        
        // init content
        $this->content = new stdClass;
        
        // check gismo:view capability
        // if (has_capability('block/gismo:view', get_context_instance(CONTEXT_BLOCK, $this->instance->id))) {
            // server data
            $data = new stdClass();
            $data->block_instance_id = $this->instance->id;
            $data->course_id = $this->course->id;
            $srv_data_encoded = urlencode(base64_encode(serialize($data)));

            // moodle bug fix
            $fix_style = "line-height: 20px; vertical-align: top;";

            // block content
            $this->content->text = html_writer::empty_tag('img', array('src' => $CFG->wwwroot.'/blocks/gismo/images/gismo.gif', 'alt' => '', 'style' => $fix_style));
            $this->content->text .= html_writer::tag('span', '&nbsp;');
            if ($this->check_data() === true) {
                $this->content->text .= html_writer::tag('a', get_string("gismo_report_launch", "block_gismo"), array('href' => $CFG->wwwroot.'/blocks/gismo/main.php?srv_data=' . $srv_data_encoded, 'target' => '_blank'));
            } else {
                $this->content->text .= html_writer::tag('span', strtoupper(get_string("gismo", "block_gismo")) . ' (disabled)');
                $this->content->text .= $OUTPUT->help_icon('gismo', 'block_gismo');
            }
            $this->content->footer = '';
        // }
        
        // return content
        return $this->content;
    }

    public function instance_allow_multiple() {
        return false;
    }

    public function instance_allow_config() {
        return false;
    }

    public function applicable_formats() {
        return array('site' => true, 'course-view' => true);
    }

    public function check_data() {
        global $CFG;
        // libraries
        $lib_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'gismo' . DIRECTORY_SEPARATOR . 'server_side' . DIRECTORY_SEPARATOR;
        require_once $lib_dir . "GISMOutil.php";
        require_once $lib_dir . "FetchStaticDataMoodle.php";
        // FetchStaticDataMoodle instance
        $gismo_static_data = new FetchStaticDataMoodle($this->course->id, "teacher");
        $gismo_static_data->init();
        // check
        return $gismo_static_data->checkData();
    }

    public function cron() {
        global $CFG;
        // libraries
        $lib_dir = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'lib' . DIRECTORY_SEPARATOR . 'gismo' . DIRECTORY_SEPARATOR . 'server_side' . DIRECTORY_SEPARATOR;
        require_once $lib_dir . "GISMOutil.php";
        require_once $lib_dir . "GISMOdata_manager.php";

        // trace start
        mtrace("GISMO - cron (start)!");

        $gdm = new GISMOdata_manager(false);

        // purge
        $purge_check = $gdm->purge_data();
        if ($purge_check === true) {
            mtrace("Gismo data has been purged successfully!");
        } else {
            mtrace($purge_check);
        }

        // sync
        $sync_check = $gdm->sync_data();
        if ($sync_check === true) {
            mtrace("Gismo data has been syncronized successfully!");
        } else {
            mtrace($sync_check);
        }

        // trace end
        mtrace("GISMO - cron (end)!");

        // ok     
        return true;
    }
    
}
?>
