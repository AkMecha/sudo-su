<?php

#**************************************************************************
#  openSIS is a free student information system for public and non-public 
#  colleges from Open Solutions for Education, Inc. web: www.os4ed.com
#
#  openSIS is  web-based, open source, and comes packed with features that 
#  include student demographic info, scheduling, grade book, attendance, 
#  report cards, eligibility, transcripts, parent portal, 
#  student portal and more.   
#
#  Visit the openSIS web site at http://www.opensis.com to learn more.
#  If you have question regarding this system or the license, please send 
#  an email to info@os4ed.com.
#
#  This program is released under the terms of the GNU General Public License as  
#  published by the Free Software Foundation, version 2 of the License. 
#  See license.txt.
#
#  This program is distributed in the hope that it will be useful,
#  but WITHOUT ANY WARRANTY; without even the implied warranty of
#  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
#  GNU General Public License for more details.
#
#  You should have received a copy of the GNU General Public License
#  along with this program.  If not, see <http://www.gnu.org/licenses/>.
#
#***************************************************************************************
include('../../RedirectModulesInc.php');
unset($_SESSION['_REQUEST_vars']['subject_id']);
unset($_SESSION['_REQUEST_vars']['course_id']);
unset($_SESSION['_REQUEST_vars']['course_period_id']);


if ($_REQUEST['modfunc'] != 'delete' && !$_REQUEST['subject_id']) {
    $subjects_RET = DBGet(DBQuery("SELECT SUBJECT_ID,TITLE FROM course_subjects WHERE COLLEGE_ID='" . UserCollege() . "' AND SYEAR='" . UserSyear() . "'"));
    if (count($subjects_RET) == 1)
        $_REQUEST['subject_id'] = $subjects_RET[1]['SUBJECT_ID'];
}

if (clean_param($_REQUEST['course_modfunc'], PARAM_ALPHAMOD) == 'search') {
    PopTable('header', 'Search');
    echo "<FORM name=F1 id=F1 action=Modules.php?modname=" . strip_tags(trim($_REQUEST[modname])) . "&modfunc=" . strip_tags(trim($_REQUEST[modfunc])) . "&course_modfunc=search method=POST>";
    echo '<TABLE><TR><TD><INPUT type=text class=form-control name=search_term value="' . strip_tags(trim($_REQUEST['search_term'])) . '"></TD><TD><INPUT type=submit class="btn btn-primary" value=Search onclick=\'formload_ajax("F1")\';></TD></TR></TABLE>';
    echo '</FORM>';
    PopTable('footer');

    if ($_REQUEST['search_term']) {
        $subjects_RET = DBGet(DBQuery('SELECT SUBJECT_ID,TITLE FROM course_subjects WHERE (UPPER(TITLE) LIKE \'%' . strtoupper($_REQUEST['search_term']) . '%\' OR UPPER(SHORT_NAME) = \'' . strtoupper($_REQUEST['search_term']) . '\') AND SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\''));
        $courses_RET = DBGet(DBQuery('SELECT SUBJECT_ID,COURSE_ID,TITLE FROM courses WHERE (UPPER(TITLE) LIKE \'%' . strtoupper($_REQUEST['search_term']) . '%\' OR UPPER(SHORT_NAME) = \'' . strtoupper($_REQUEST['search_term']) . '\') AND SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\''));
        $periods_RET = DBGet(DBQuery('SELECT c.SUBJECT_ID,cp.COURSE_ID,cp.COURSE_PERIOD_ID,cp.TITLE FROM course_periods cp,courses c WHERE cp.COURSE_ID=c.COURSE_ID AND (UPPER(cp.TITLE) LIKE \'%' . strtoupper($_REQUEST['search_term']) . '%\' OR UPPER(cp.SHORT_NAME) = \'' . strtoupper($_REQUEST['search_term']) . '\') AND cp.SYEAR=\'' . UserSyear() . '\' AND cp.COLLEGE_ID=\'' . UserCollege() . '\''));

        echo '<div class="row">';
        echo '<div class="col-md-4">';
        $link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=$_REQUEST[modfunc]";
        $link['TITLE']['variables'] = array('subject_id' => 'SUBJECT_ID');
        ListOutput($subjects_RET, array('TITLE' => 'Subject'), 'Subject', 'Subjects', $link, array(), array('search' => false, 'save' => false));
        echo '</div><div class="col-md-4">';
        $link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=$_REQUEST[modfunc]";
        $link['TITLE']['variables'] = array('subject_id' => 'SUBJECT_ID', 'course_id' => 'COURSE_ID');
        ListOutput($courses_RET, array('TITLE' => 'Course'), 'Course', 'Courses', $link, array(), array('search' => false, 'save' => false));
        echo '</div><div class="col-md-4">';
        $link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=$_REQUEST[modfunc]";
        $link['TITLE']['variables'] = array('subject_id' => 'SUBJECT_ID', 'course_id' => 'COURSE_ID', 'course_period_id' => 'COURSE_PERIOD_ID');
        ListOutput($periods_RET, array('TITLE' => 'Course Period'), 'Course Period', 'Course Periods', $link, array(), array('search' => false, 'save' => false));
        echo '</div>';
        echo '</div>';
    }
}

// UPDATING
if (clean_param($_REQUEST['re_assignment_teacher'], PARAM_NOTAGS) && ($_POST['re_assignment_teacher'] || $_REQUEST['ajax']) && AllowEdit()) {
    $id = $_REQUEST['course_period_id'];
    $today = date('Y-m-d');
    $pre_staff_id = $_REQUEST['re_assignment_pre_teacher'];
    $_SESSION['undo_teacher'] = $pre_staff_id;
    $staff_id = $_REQUEST['re_assignment_teacher'];

    if ($_REQUEST['day_re_assignment'] && $_REQUEST['month_re_assignment'] && $_REQUEST['year_re_assignment'])
        $assign_date = date('Y-m-d', strtotime($_REQUEST['day_re_assignment'] . '-' . $_REQUEST['month_re_assignment'] . '-' . $_REQUEST['year_re_assignment']));
    if ($_REQUEST['day_re_assignment'] != '' && $_REQUEST['month_re_assignment'] != '' && $_REQUEST['year_re_assignment'] != '') {
        if (strtotime($assign_date) >= strtotime(date('Y-m-d'))) {
            if (scheduleAssociation($id)) {
                $reassigned = DBGet(DBQuery('SELECT COURSE_PERIOD_ID,TEACHER_ID,ASSIGN_DATE,PRE_TEACHER_ID,MODIFIED_DATE,MODIFIED_BY,UPDATED FROM teacher_reassignment WHERE course_period_id=\'' . $id . '\' AND assign_date=\'' . $assign_date . '\''));
                if ($reassigned) {
                    DBQuery('UPDATE teacher_reassignment SET teacher_id=\'' . $staff_id . '\',pre_teacher_id=\'' . $pre_staff_id . '\',modified_date=\'' . $today . '\',modified_by=\'' . User('STAFF_ID') . '\',updated=\'N\' WHERE course_period_id=\'' . $id . '\' AND assign_date=\'' . $assign_date . '\'');
                    $_SESSION['undo'] = 'UPDATE teacher_reassignment SET teacher_id=\'' . $pre_staff_id . '\',pre_teacher_id=\'' . $reassigned[1]['PRE_TEACHER_ID'] . '\',modified_date=\'' . $reassigned[1]['MODIFIED_DATE'] . '\',modified_by=\'' . $reassigned[1]['MODIFIED_BY'] . '\',updated=\'' . $reassigned[1]['UPDATED'] . '\' WHERE course_period_id=\'' . $id . '\' AND assign_date=\'' . $assign_date . '\'';
                } else {
                    DBQuery('INSERT INTO teacher_reassignment(course_period_id,teacher_id,assign_date,pre_teacher_id,modified_date,modified_by)VALUES(\'' . $id . '\',\'' . $staff_id . '\',\'' . $assign_date . '\',\'' . $pre_staff_id . '\',\'' . $today . '\',\'' . User('STAFF_ID') . '\')');
                    $_SESSION['undo'] = 'DELETE FROM teacher_reassignment WHERE course_period_id=\'' . $id . '\' AND teacher_id=\'' . $staff_id . '\' AND assign_date=\'' . $assign_date . '\'';
                }
                $undo_possible = true;
                $title_RET = DBGet(DBQuery('SELECT TITLE FROM course_periods WHERE COURSE_PERIOD_ID=\'' . $id . '\''));
                $_SESSION['undo_title'] = $title_RET[1]['TITLE'];
            } else {
                ShowErrPhp('There is no associations in this Course Period. You can delete it from College Set Up> Course Manager');
            }
        } else {
            ShowErrPhp('Assigned date cannot be lesser than today\'s date');
        }
    } else {
        ShowErrPhp('Please enter proper date');
    }
}

if ($_REQUEST['action'] == 'undo') {
    DBQuery($_SESSION['undo']);
    DBQuery('UPDATE course_periods set title=\'' . $_SESSION['undo_title'] . '\',teacher_id=\'' . $_SESSION['undo_teacher'] . '\' WHERE course_period_id=\'' . $_REQUEST['course_period_id'] . '\'');

    unset($_SESSION['undo']);
    unset($_SESSION['undo_teacher']);
    unset($_SESSION['undo_title']);
}

if ((!$_REQUEST['modfunc'] || clean_param($_REQUEST['modfunc'], PARAM_ALPHAMOD) == 'choose_course') && !$_REQUEST['course_modfunc']) {
    if ($_REQUEST['modfunc'] != 'choose_course')
        DrawBC("scheduling > " . ProgramTitle());
    $sql = 'SELECT SUBJECT_ID,TITLE FROM course_subjects WHERE COLLEGE_ID=\'' . UserCollege() . '\' AND SYEAR=\'' . UserSyear() . '\' ORDER BY TITLE';
    $QI = DBQuery($sql);
    $subjects_RET = DBGet($QI);

    if ($_REQUEST['modfunc'] != 'choose_course') {
        if (clean_param($_REQUEST['course_period_id'], PARAM_ALPHANUM)) {
            $sql = 'SELECT TITLE,TEACHER_ID,SECONDARY_TEACHER_ID
						FROM course_periods
						WHERE COURSE_PERIOD_ID=\'' . $_REQUEST[course_period_id] . '\'';
            $QI = DBQuery($sql);
            $RET = DBGet($QI);
            $RET = $RET[1];
            $title = $RET['TITLE'];
            if ($undo_possible == true)
                $title .='<div class="alert bg-success alert-styled-left">
                            <button type="button" class="close" data-dismiss="alert"><span>×</span><span class="sr-only">Close</span></button>
                            Teacher Re-Assignment Done <a href="#" onclick="load_link(\'Modules.php?modname=' . $_REQUEST['modname'] . '&subject_id=' . $_REQUEST['subject_id'] . '&course_id=' . $_REQUEST['course_id'] . '&course_period_id=' . $_REQUEST['course_period_id'] . '&action=undo\')" class="alert-link">Undo</a>
                        </div>';
            echo "<FORM name=F2 id=F2 action=Modules.php?modname=" . strip_tags(trim($_REQUEST[modname])) . "&subject_id=" . strip_tags(trim($_REQUEST[subject_id])) . "&course_id=" . strip_tags(trim($_REQUEST[course_id])) . "&course_period_id=" . strip_tags(trim($_REQUEST[course_period_id])) . " method=POST>";

            echo '<div class="panel panel-default">';
            echo '<div class="panel-heading">
                        <h6 class="panel-title">' . $title . '</h6>
                        <div class="heading-elements">' . SubmitButton('Save', '', 'class="btn btn-primary" onclick="formcheck_teacher_reassignment();"') . '</div>
                </div>';
            $header .= '<div class="panel-body">';
            $header .= '<div class="row">';
            $header .= '<div class="col-md-3"><label class="control-label">Select New Teacher</label>';
            $teachers_RET = DBGet(DBQuery('SELECT STAFF_ID,LAST_NAME,FIRST_NAME,MIDDLE_NAME FROM staff st INNER JOIN staff_college_relationship ssr USING (staff_id) WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\' AND PROFILE=\'teacher\' AND staff_id <>\'' . $RET['TEACHER_ID'] . '\' AND (IS_DISABLE IS NULL OR IS_DISABLE<>\'Y\')  ORDER BY LAST_NAME,FIRST_NAME '));
            if (count($teachers_RET)) {
                foreach ($teachers_RET as $teacher)
                    $teachers[$teacher['STAFF_ID']] = $teacher['LAST_NAME'] . ', ' . $teacher['FIRST_NAME'] . ' ' . $teacher['MIDDLE_NAME'];
            }
            $header .= SelectInput('', 're_assignment_teacher', '', $teachers) . '</div>';
            $header .= '<div class="col-md-3"><label class="control-label">Assign Date</label>';

            $header .= DateInputAY('', 're_assignment', 1) . '</div>';
            $header .= '<input type=hidden name=course_period_id value=' . $_REQUEST['course_period_id'] . '><input type=hidden name=re_assignment_pre_teacher value=' . $RET['TEACHER_ID'] . '>';
            $header .= '</div>'; //.row
            DrawHeaderHome($header);
            //--------------------------------------------Re Assignment Record-------------------------------------------------------------

            $sql = 'SELECT COURSE_PERIOD_ID,(SELECT CONCAT_WS(\' \',last_name,middle_name,first_name) FROM staff WHERE staff_id=teacher_id) AS TEACHER,ASSIGN_DATE,(SELECT CONCAT_WS(\' \',last_name,middle_name,first_name) FROM staff WHERE staff_id=pre_teacher_id) AS PRE_TEACHER_ID,MODIFIED_DATE,(SELECT CONCAT_WS(\' \',last_name,first_name) FROM staff WHERE staff_id=modified_by) AS MODIFIED_BY FROM teacher_reassignment WHERE course_period_id=\'' . $_REQUEST['course_period_id'] . '\' ORDER BY assign_date DESC';
            $QI = DBQuery($sql);
            $courses_RET = DBGet($QI, array('ASSIGN_DATE' => 'ProperDAte', 'MODIFIED_DATE' => 'ProperDate'));

            echo '<div class="table-responsive">';
            $LO_options = array('save' => false, 'search' => false);
            $columns = array('TEACHER' => 'Teacher', 'ASSIGN_DATE' => 'Assign Date', 'PRE_TEACHER_ID' => 'Previous Teacher', 'MODIFIED_DATE' => 'Modified Date', 'MODIFIED_BY' => 'Modified By');
            $link = array();
            $link['TITLE']['variables'] = array('course_id' => 'COURSE_ID');

            ListOutput($courses_RET, $columns, 'Re-Assignment Record', 'Re-Assignment Records', $link, array(), $LO_options);
            echo '</div>';
            echo '</div>';

            echo '</div>'; //.panel
            echo '</FORM>';
            //--------------------------------------------------------------------------------------------------------------------------------------------
        }
    }

    // DISPLAY THE MENU
    $LO_options = array('save' => false, 'search' => false);

    if (!$_REQUEST['subject_id'] || clean_param($_REQUEST['modfunc'], PARAM_ALPHAMOD) == 'choose_course'){
        echo '<div class="panel panel-default">';
        echo '<div class="panel-body">';
        echo "<A HREF=ForWindow.php?modname=$_REQUEST[modname]&modfunc=$_REQUEST[modfunc]&course_modfunc=search><i class=\"icon-search4 position-left\"></i> Search Course</A>";
        echo '</div>';
        echo '</div>'; //.panel
    }

    echo '<div class="row">';

    if (count($subjects_RET)) {
        if (clean_param($_REQUEST['subject_id'], PARAM_ALPHANUM)) {
            foreach ($subjects_RET as $key => $value) {
                if ($value['SUBJECT_ID'] == $_REQUEST['subject_id'])
                    $subjects_RET[$key]['row_color'] = Preferences('HIGHLIGHT');
            }
        }
    }

    echo '<div class="col-md-4">';
    echo '<div class="panel panel-default">';
    $columns = array('TITLE' => 'Subject');
    $link = array();
    $link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]";
    $link['TITLE']['variables'] = array('subject_id' => 'SUBJECT_ID');

    $link['TITLE']['link'] .= "&modfunc=$_REQUEST[modfunc]";

    ListOutput($subjects_RET, $columns, 'Subject', 'Subjects', $link, array(), $LO_options);
    echo '</div>'; //.panel
    echo '</div>'; //.col-md-4

    if (clean_param($_REQUEST['subject_id'], PARAM_ALPHANUM) && $_REQUEST['subject_id'] != 'new') {
        $sql = 'SELECT COURSE_ID,TITLE FROM courses WHERE SUBJECT_ID=\'' . $_REQUEST['subject_id'] . '\' ORDER BY TITLE';
        $QI = DBQuery($sql);
        $courses_RET = DBGet($QI);

        if (count($courses_RET)) {
            if (clean_param($_REQUEST['course_id'], PARAM_ALPHANUM)) {
                foreach ($courses_RET as $key => $value) {
                    if ($value['COURSE_ID'] == $_REQUEST['course_id'])
                        $courses_RET[$key]['row_color'] = Preferences('HIGHLIGHT');
                }
            }
        }

        echo '<div class="col-md-4">';
        echo '<div class="panel panel-default">';
        $columns = array('TITLE' => 'Course');
        $link = array();
        $link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&subject_id=$_REQUEST[subject_id]";
        $link['TITLE']['variables'] = array('course_id' => 'COURSE_ID');

        ListOutput($courses_RET, $columns, 'Course', 'Courses', $link, array(), $LO_options);
        echo '</div>'; //.panel
        echo '</div>'; //.col-md-4

        if (clean_param($_REQUEST['course_id'], PARAM_ALPHANUM) && $_REQUEST['course_id'] != 'new') {
            $sql = "SELECT COURSE_PERIOD_ID,TITLE,COALESCE(TOTAL_SEATS-FILLED_SEATS,0) AS AVAILABLE_SEATS FROM course_periods WHERE COURSE_ID='$_REQUEST[course_id]' AND (marking_period_id IN(" . GetAllMP(GetMPTable(GetMP(UserMP(), 'TABLE')), UserMP()) . ") OR (CURDATE() <= end_date AND marking_period_id IS NULL)) ORDER BY TITLE";


            $QI = DBQuery($sql);
            $periods_RET = DBGet($QI);

            if (count($periods_RET)) {
                if (clean_param($_REQUEST['course_period_id'], PARAM_ALPHANUM)) {
                    foreach ($periods_RET as $key => $value) {
                        if ($value['COURSE_PERIOD_ID'] == $_REQUEST['course_period_id'])
                            $periods_RET[$key]['row_color'] = Preferences('HIGHLIGHT');
                    }
                }
            }

            echo '<div class="col-md-4">';
            echo '<div class="panel panel-default">';
            $columns = array('TITLE' => 'Course Period');
            if ($_REQUEST['modname'] == 'scheduling/Schedule.php')
                $columns += array('AVAILABLE_SEATS' => 'Available Seats');
            $link = array();
            $link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]&subject_id=$_REQUEST[subject_id]&course_id=$_REQUEST[course_id]";
            $link['TITLE']['variables'] = array('course_period_id' => 'COURSE_PERIOD_ID');

            ListOutput($periods_RET, $columns, 'Period', 'Periods', $link, array(), $LO_options);
            echo '</div>'; //.panel
            echo '</div>'; //.col-md-4
        }
    }

    echo '</div>';
}
?>