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

/**
 * This function count the total completed courses of any user
 *
 * @param int   $userid Variable
 * @return String $total_courses return total completed courses of any use.
 */
function count_complete_course($userid) {
    global $DB;
    $total_courses = $DB->get_record_sql('SELECT count(course) as total_course FROM {course_completion_crit_compl} WHERE userid = ?', array($userid));
    $total_courses = $total_courses->total_course;
    return $total_courses;
}

/**
 * This function retrun the total number of enrolled courses
 *
 * @see enrol_get_users_courses()
 * @param int   $userid Moodle user id 
 * @return String $count_course return total enrolled courses.
 */
function user_enrolled_courses($userid) {
    global $CFG;
    $count_course = 0;
    $courses = enrol_get_users_courses($userid, false, 'id, shortname, showgrades');
    if ($courses) {
        foreach ($courses as $course) {
            $count_course+=1;
        }
    }
    return $count_course;
}

/**
 * This function tells how many enrolled courses criteria has not set yet of the user.
 *
 * @see enrol_get_users_courses()
 * @param int   $userid Moodle user id
 * @return String $count return number that tells total undefined course criteria of course.
 */
function count_course_criteria($userid) {
    global $DB;
    $count = 0;
    $courses = enrol_get_users_courses($userid, false, 'id, shortname, showgrades');
    if ($courses) {
        $course_criteria_ns = array();
        foreach ($courses as $course) {
            $exist = $DB->record_exists('course_completion_criteria', array('course' => $course->id));
            if (!$exist) {
                $count++;
                $course_criteria_ns[] = $course->id;
            }
        }
    }
    return $count;
}

/**
 * This function return the course category.
 *
 * @param int   $id Moodle course id
 * @return String $module return category name of course.
 */
function module_name($id) {
    global $DB;
    $module = $DB->get_record_sql('SELECT name FROM {course_categories}  WHERE id = ?', array($id));
    $module = format_string($module->name);
    return $module;
}

/**
 * This function return course name on the base of course id.
 *
 * @param int   $course Moodle course id
 * @return String $course Moodle course name.
 */
function course_name($id) {
    global $DB;
    $course = $DB->get_record_sql('SELECT fullname  FROM {course} WHERE id = ?', array($id));
    $course = format_string($course->fullname);
    $course = $course . ' ' . get_string('course', 'block_course_status_tracker');
    ;
    return $course;
}

/**
 * This function return user detail in the form of table.
 *
 * @see report_get_custome_field($id, "Designation") This function return custom field Designation value on the bass userid
 * @param int   $id Moodle userid
 * @return String $table Moodle course name.
 */
function user_details($id) {
    global $OUTPUT, $DB;
    // $user = new stdClass();
    $user = $DB->get_record('user', array('id' => $id));
    //$user->id = $id; // User Id.

    $user->picture = $OUTPUT->user_picture($user, array('size' => 100));
    // Fetch Data.
    $result = $DB->get_record_sql('SELECT concat(firstname," ",lastname) as name,department, timecreated as date  FROM {user} WHERE id = ?', array($id));
    $table = '<table width="80%"><tr><td width="20%" style="vertical-align:middle;" rowspan="5">' . $user->picture . '</td></tr>
           <tr><td width="20%">' . get_string('name', 'block_course_status_tracker') . '</td><td>' . $result->name . '</td></tr>';

    $check_designation_field = report_get_custom_field($id, "Designation"); // Custom Field name for designation is "Designation".
    if ($check_designation_field != 0) {
        $table .='<tr><td>' . get_string('job_title', 'block_course_status_tracker') . '</td><td>' . format_string($check_designation_field) . '</td></tr>';
    }
    $table .='<tr><td>' . get_string('department', 'block_course_status_tracker') . '</td><td>' . format_string($result->department) . '</td></tr>
             <tr><td>' . get_string('joining_date', 'block_course_status_tracker') . '</td><td>' . userdate($result->date, get_string('strftimedate', 'core_langconfig')) . '</td></tr>
             </table>';
    return $table;
}

/**
 * This function return the value of custom field on the base of parameter field name.
 *
 * @param int    $userid Moodle userid
 * @param string $text custom field name
 * @return string Return field value.
 */
function report_get_custom_field($userid, $text) {
    global $DB;
    $result = $DB->get_record_sql('SELECT table2.data as fieldvalue  FROM {user_info_field} as table1  join  {user_info_data} as table2
                                   on table1.id=table2.fieldid where table2.userid=? AND table1.name=?', array($userid, $text));

    $fieldvalue = $result['fieldvalue'];
    if (empty($fieldvalue)) {
        return "0";
    } else {
        return format_string($result->fieldvalue);
    }
}

/**
 * This function return list of courses in which user enrolled.
 *
 * @see module_name()
 * @enrol_get_users_courses
 * @param int    $userid Moodle userid
 * @return  Return table in which user can see the enrolled courses list.
 */
function user_enrolled_courses_report($userid) {
    global $CFG;
    $count_course = 0;
    $courses = enrol_get_users_courses($userid, false, 'id, shortname,showgrades');
    if ($courses) {
        $table = new html_table();
        $table->head = array(get_string('s_no', 'block_course_status_tracker'), get_string('module', 'block_course_status_tracker'), get_string('course_name', 'block_course_status_tracker'));
        $table->size = array('20%', '35%', '50%');
        $table->width = "80%";

        $table->align = array('center', 'left', 'left');
        $table->data = array();
        $i = 0;
        foreach ($courses as $course) {
            $row = array();
            $row[] = ++$i;
            $row[] = module_name($course->category);
            $row[] = "<a href=" . $CFG->wwwroot . "/course/view.php?id=" . $course->id . ">" . course_name($course->id) . "</a>";
            $table->data[] = $row;
        }
    }
    return $table;
}

function user_inprogress_courses_report($userid) {
    global $CFG,$DB;
    $count_course = 0;
    $courses = $DB->get_records_sql('select
    c.id,c.category,c.fullname,cc.timeenrolled from
    	{course} c inner join {course_completions} cc on c.id = cc.course where
    	cc.timecompleted is NULL and cc.timeenrolled > 0 and cc.userid = '.$userid);
    $table = new html_table();
    $table->head = array(get_string('s_no', 'block_course_status_tracker'), 
        get_string('module', 'block_course_status_tracker'), 
        get_string('course_name', 'block_course_status_tracker'),
        get_string('timeenrolled', 'block_course_status_tracker'));
    $table->size = array('20%', '35%', '50%');
    $table->width = "80%";

    $table->align = array('center', 'left', 'left');
    $table->data = array();
    $i = 0;
    if ($courses)
    {
        foreach ($courses as $course) {
            $row = array();
            $row[] = ++$i;
            $row[] = module_name($course->category);
            $row[] = "<a href=" . $CFG->wwwroot . "/course/view.php?id=" .
            	$course->id . ">" . $course->fullname . "</a>";
            $row[] = date(DATE_RFC1123,$course->timeenrolled);
            $table->data[] = $row;
        }
    }

    return $table;
}
