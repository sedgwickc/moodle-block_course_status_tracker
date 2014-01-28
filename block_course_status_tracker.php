<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/* Course Status Tracker Block
 * The plugin shows the number and list of enrolled courses and completed courses.
 * It also shows the number of courses which are in progress and whose completion criteria is undefined but the manger.
 * @package blocks
 * @author: Azmat Ullah, Talha Noor
 */

require_once('lib.php');
/**
 * This class shows the content in block through calling lib.php function.
 */

class block_course_status_tracker extends block_base {
    public function init() {
        $this->title = get_string('course_status_tracker', 'block_course_status_tracker');
    }
    /**
     * Where to add the block
     *
     * @return boolean
     **/
    public function applicable_formats() {
        return array('all' => true);
    }
    /**
     * Gets the contents of the block (course view)
     *
     * @return object An object with the contents
     **/
     public function isguestuser($user = null)
     {return false;}
    public function get_content() {
        global $CFG, $OUTPUT, $USER, $DB;
        if ($this->content !== null) {
            return $this->content;
        }
        $this->content = new stdClass;
        if ($CFG->enablecompletion) {
            // $enrolled_courses=user_enrolled_courses($USER->id);
            // Enrolled courses.
			 $count_course=0;
    		 $courses = enrol_get_users_courses($USER->id, false, 'id, shortname, showgrades');
    		 if ($courses) {
                 foreach ($courses as $course) {
                     $count_course+=1;
                 }
        }
    		$enrolled_courses = $count_course;
			// End enrolled courses.
            // $count_complete_courses=count_complete_course($USER->id);
            // Completed courses.
            // $count_complete_courses=count_complete_course($USER->id);
			$total_courses=$DB->get_record_sql('SELECT count(course) as total_course FROM {course_completion_crit_compl} 						                                               WHERE userid = ?', array($USER->id));
                                        
            $total_courses=$total_courses->total_course;
            $count_complete_courses=$total_courses;
			// End completed courses.
            // $course_criteria_not_set=count_course_criteria($USER->id);
            // Course criteria not set.
            // $course_criteria_not_set=count_course_criteria($USER->id);
			 $count=0;
             $courses = enrol_get_users_courses($USER->id, false, 'id, shortname, showgrades');
             if ($courses) {
        	     $course_criteria_ns = array();
                 foreach ($courses as $course) {
                     $exist = $DB->record_exists('course_completion_criteria', array('course' => $course->id));
                     if(!$exist) {
                     $count++;
                     $course_criteria_ns[] = $course->id;
                }
             }
            }
            $course_criteria_not_set= $count;
			// End course criteria.
            $count_inprogress_courses=($enrolled_courses)-($count_complete_courses+$course_criteria_not_set);
            if ($enrolled_courses > 0) {
                $link_enrolled_courses = "<u><a href='".$CFG->wwwroot."/blocks/course_status_tracker/view.php?viewpage=2'>".
                                         $enrolled_courses."</a></u>";
            } else {
                $link_enrolled_courses = $enrolled_courses;
            }
            if ($count_complete_courses > 0) {
                $link_count_complete_courses = "<u><a href='".$CFG->wwwroot."/blocks/course_status_tracker/view.php?viewpage=1'>".
                                                $count_complete_courses."</a></u>";
            } else {
                $link_count_complete_courses = $count_complete_courses;
            }
            $link_course_criteria_not_set = "<a href='".$CFG->wwwroot."/blocks/course_status_tracker/view.php?viewpage=1'>".
                                            $course_criteria_not_set."</a>";
            $link_count_inprogress_courses = "<a href='".$CFG->wwwroot."/blocks/course_status_tracker/view.php?viewpage=1'>".
                                             $count_inprogress_courses."</a>";
             $this->content->text .= get_string('enrolled_courses', 'block_course_status_tracker')." :	<b>".$link_enrolled_courses."</b><br>";
            $this->content->text .= get_string('completed_courses', 'block_course_status_tracker')." : <b>".$link_count_complete_courses."</b><br>";
            $this->content->text .= get_string('inprogress_courses', 'block_course_status_tracker')." : <b>".$count_inprogress_courses."</b><br>";
            $this->content->text .= get_string('undefined_coursecriteria', 'block_course_status_tracker')." : <b>".$course_criteria_not_set."</b><br>";
        } else {
              $this->content->text .= get_string('coursecompletion_setting', 'block_course_status_tracker');
        }
        return $this->content;
    }
}   
