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
include 'modules/grades/ConfigInc.php';
require_once('functions/MakeLetterGradeFnc.php');
$_openSIS['allow_edit'] = false;
if ($_REQUEST['_openSIS_PDF'])
    $do_stats = false;

Search('college_roll_no');
$MP_TYPE_RET = DBGet(DBQuery('SELECT MP_TYPE FROM marking_periods WHERE MARKING_PERIOD_ID=' . UserMP() . ' LIMIT 1'));
$MP_TYPE = $MP_TYPE_RET[1]['MP_TYPE'];
if ($MP_TYPE == 'year') {
    $MP_TYPE = 'FY';
} else if ($MP_TYPE == 'semester') {
    $MP_TYPE = 'SEM';
} else if ($MP_TYPE == 'quarter') {
    $MP_TYPE = 'QTR';
} else {
    $MP_TYPE = '';
}
$rank_RET = DBGet(DBQuery('SELECT VALUE FROM program_config WHERE college_id=\'' . UserCollege() . '\' AND program=\'class_rank\' AND title=\'display\' LIMIT 0, 1'));
$rank = $rank_RET[1];
$display_rank = $rank['VALUE'];
####################
if (isset($_REQUEST['college_roll_no'])) {
    $RET = DBGet(DBQuery('SELECT FIRST_NAME,LAST_NAME,MIDDLE_NAME,NAME_SUFFIX,COLLEGE_ID FROM students,student_enrollment WHERE students.COLLEGE_ROLL_NO=\'' . $_REQUEST['college_roll_no'] . '\' AND student_enrollment.COLLEGE_ROLL_NO = students.COLLEGE_ROLL_NO '));

    $count_student_RET = DBGet(DBQuery('SELECT COUNT(*) AS NUM FROM students'));
    if ($count_student_RET[1]['NUM'] > 1) {
       DrawHeaderHome( 'Selected Student: '.$RET[1]['FIRST_NAME'].'&nbsp;'.($RET[1]['MIDDLE_NAME']?$RET[1]['MIDDLE_NAME'].' ':'').$RET[1]['LAST_NAME'].'&nbsp;'.$RET[1]['NAME_SUFFIX'].' (<A HREF=Side.php?college_roll_no=new&modcat='.$_REQUEST['modcat'].'><font color=red>Deselect</font></A>) | <A HREF=Modules.php?modname='.$_REQUEST['modname'].'&search_modfunc=list&next_modname=students/Student.php&ajax=true&bottom_back=true&return_session=true target=body>Back to Student List</A>');
    } else if ($count_student_RET[1]['NUM'] == 1) {
      DrawHeaderHome( 'Selected Student: '.$RET[1]['FIRST_NAME'].'&nbsp;'.($RET[1]['MIDDLE_NAME']?$RET[1]['MIDDLE_NAME'].' ':'').$RET[1]['LAST_NAME'].'&nbsp;'.$RET[1]['NAME_SUFFIX'].' (<A HREF=Side.php?college_roll_no=new&modcat='.$_REQUEST['modcat'].'><font color=red>Deselect</font></A>) ');
    }
}
####################
if (UserStudentID() && !$_REQUEST['modfunc']) {

    if (!$_REQUEST['id']) {
        echo '<div class="panel">';
        DrawHeader('Totals', "<span class=\"heading-text\"><A HREF=Modules.php?modname=$_REQUEST[modname]&id=all><i class=\"icon-menu-open\"></i> &nbsp; Expand All</A></span>");
        echo '</div>';
        $courses_RET = DBGet(DBQuery('SELECT c.TITLE AS COURSE_TITLE,cp.TITLE,cp.COURSE_PERIOD_ID,cp.COURSE_ID,cp.TEACHER_ID AS STAFF_ID FROM schedule s,course_periods cp,courses c WHERE s.SYEAR=\'' . UserSyear() . '\' AND cp.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID AND ((cp.MARKING_PERIOD_ID IN (' . GetAllMP($MP_TYPE, UserMP()) . ') OR cp.MARKING_PERIOD_ID IS NULL) AND ((\'' . date('Y-m-d', strtotime(DBDate())) . '\' BETWEEN s.START_DATE AND s.END_DATE OR \'' . date('Y-m-d', strtotime(DBDate())) . '\'>=s.START_DATE AND s.END_DATE IS NULL))) AND s.COLLEGE_ROLL_NO=\'' . UserStudentID() . '\' ' . (User('PROFILE') == 'teacher' ? ' AND (cp.TEACHER_ID=\'' . User('STAFF_ID') . '\' OR cp.SECONDARY_TEACHER_ID=\'' . User('STAFF_ID') . '\')' : '') . ' AND c.COURSE_ID=cp.COURSE_ID ORDER BY cp.COURSE_ID'), array(), array('COURSE_PERIOD_ID'));
        if ($display_rank == 'Y')
            $LO_columns = array('TITLE' => 'Course Title', 'TEACHER' => 'Teacher', 'PERCENT' => 'Percent', 'GRADE' => 'Letter', 'UNGRADED' => 'Ungraded') + ($do_stats ? array('BAR1' => 'Grade Range(%)', 'BAR2' => 'Class Rank') : array());
        else
            $LO_columns = array('TITLE' => 'Course Title', 'TEACHER' => 'Teacher', 'PERCENT' => 'Percent', 'GRADE' => 'Letter', 'UNGRADED' => 'Ungraded') + ($do_stats ? array('BAR1' => 'Grade Range(%)') : array());

        if (count($courses_RET)) {
            $LO_ret = array(0 => array());

            foreach ($courses_RET as $course) {

                $mp = GetAllMP('QTR', UserMP());

                if (!isset($mp))
                    $mp = GetAllMP('SEM', UserMP());

                if (!isset($mp))
                    $mp = GetAllMP('FY', UserMP());


                $course = $course[1];
                $staff_id = $course['STAFF_ID'];
                $course_id = $course['COURSE_ID'];
                $course_period_id = $course['COURSE_PERIOD_ID'];
                $course_title = $course['TITLE'];

                $assignments_RET = DBGet(DBQuery('SELECT ASSIGNMENT_ID,TITLE,POINTS FROM gradebook_assignments WHERE STAFF_ID=\'' . $staff_id . '\' AND (COURSE_ID=\'' . $course_id . '\' OR COURSE_PERIOD_ID=\'' . $course_period_id . '\') AND MARKING_PERIOD_ID IN (' . $mp . ') ORDER BY DUE_DATE DESC,ASSIGNMENT_ID'));


                if (!$programconfig[$staff_id]) {
                        $config_RET = DBGet(DBQuery('SELECT TITLE,VALUE FROM program_user_config WHERE USER_ID=\'' . User('STAFF_ID') . '\' AND COLLEGE_ID=\'' . UserCollege() . '\' AND PROGRAM=\'Gradebook\' AND VALUE LIKE \'%_' . $course_period_id . '\''), array(), array('TITLE'));


                        if (count($config_RET))
                        foreach ($config_RET as $title => $value) {

                        if ($title == 'ANOMALOUS_MAX') {
                        $arr = explode('_', $value[1]['VALUE']);

                        $programconfig[User('STAFF_ID')][$title] = $arr[0];
                        } elseif (substr($title, 0, 3) == 'SEM' || substr($title, 0, 1) == 'Q' || substr($title, 0, 1) == 'FY') {
                        $arr = explode('_', $value[1]['VALUE']);

                        $programconfig[User('STAFF_ID')][$title] = $arr[0];
                        } else {
                        $unused_var = explode('_', $value[1]['VALUE']);
                        $programconfig[User('STAFF_ID')][$title] = $unused_var[0];
                        //			$programconfig[User('STAFF_ID')][$title] = rtrim($value[1]['VALUE'],'_'.UserCoursePeriod());
                        }
                        } else
                        $programconfig[User('STAFF_ID')] = true;
                }

                if ($programconfig[$staff_id]['WEIGHT'] == 'Y') {
                    $mp = GetAllMP('QTR', UserMP());

                    if (!isset($mp))
                        $mp = GetAllMP('SEM', UserMP());

                    if (!isset($mp))
                        $mp = GetAllMP('FY', UserMP());


                    $points_RET1 = DBGet(DBQuery('SELECT DISTINCT s.COLLEGE_ROLL_NO, gt.ASSIGNMENT_TYPE_ID, sum(' . db_case(array('gg.POINTS', "'-1'", "'0'", 'gg.POINTS')) . ') AS PARTIAL_POINTS,sum(' . db_case(array('gg.POINTS', "'-1'", "'0'", 'ga.POINTS')) . ') AS PARTIAL_TOTAL, gt.FINAL_GRADE_PERCENT FROM students s JOIN schedule ss ON (ss.COLLEGE_ROLL_NO=s.COLLEGE_ROLL_NO AND ss.COURSE_PERIOD_ID=\'' . $course_period_id . '\') JOIN gradebook_assignments ga ON ((ga.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID OR ga.COURSE_ID=\'' . $course_id . '\' AND ga.STAFF_ID=\'' . User('STAFF_ID') . '\') AND ga.MARKING_PERIOD_ID IN (' . $mp . ')) LEFT OUTER JOIN gradebook_grades gg ON (gg.COLLEGE_ROLL_NO=s.COLLEGE_ROLL_NO AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND gg.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID),gradebook_assignment_types gt WHERE gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID AND gt.COURSE_ID=\'' . $course_id . '\' AND ((ga.ASSIGNED_DATE IS NOT NULL )  OR gg.POINTS IS NOT NULL) GROUP BY s.COLLEGE_ROLL_NO,ss.START_DATE,gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT'), array(), array('COLLEGE_ROLL_NO'));
                    $points_RET = DBGet(DBQuery('SELECT      gt.ASSIGNMENT_TYPE_ID,sum(' . db_case(array('gg.POINTS', "'-1'", "'0'", 'gg.POINTS')) . ') AS PARTIAL_POINTS,sum(' . db_case(array('gg.POINTS', "'-1'", "'0'", 'ga.POINTS')) . ') AS PARTIAL_TOTAL,    gt.FINAL_GRADE_PERCENT,sum(' . db_case(array('gg.POINTS', "''", "1", "0")) . ') AS UNGRADED FROM gradebook_assignments ga LEFT OUTER JOIN gradebook_grades gg ON (gg.COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND gg.COLLEGE_ROLL_NO=\'' . UserStudentID() . '\' AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID),gradebook_assignment_types gt WHERE (ga.COURSE_PERIOD_ID=\'' . $course_period_id . '\' OR ga.COURSE_ID=\'' . $course_id . '\' AND ga.STAFF_ID=\'' . $staff_id . '\') AND ga.MARKING_PERIOD_ID IN (' . $mp . ') AND gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID AND gt.COURSE_ID=\'' . $course_id . '\' AND ((ga.ASSIGNED_DATE IS NOT NULL )  and gg.POINTS IS NOT NULL) GROUP BY gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT'));

                    $points_RET_all1 = DBGet(DBQuery('SELECT      gt.ASSIGNMENT_TYPE_ID,sum(' . db_case(array('gg.POINTS', "'-1'", "'0'", 'gg.POINTS')) . ') AS PARTIAL_POINTS,sum(' . db_case(array('gg.POINTS', "'-1'", "'0'", 'ga.POINTS')) . ') AS PARTIAL_TOTAL,    gt.FINAL_GRADE_PERCENT,sum(' . db_case(array('gg.POINTS', "''", "1", "0")) . ') AS UNGRADED FROM gradebook_assignments ga LEFT OUTER JOIN gradebook_grades gg ON (gg.COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND gg.COLLEGE_ROLL_NO=\'' . UserStudentID() . '\' AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID),gradebook_assignment_types gt WHERE (ga.COURSE_PERIOD_ID=\'' . $course_period_id . '\' OR ga.COURSE_ID=\'' . $course_id . '\' AND ga.STAFF_ID=\'' . $staff_id . '\') AND ga.MARKING_PERIOD_ID IN (' . $mp . ') AND gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID AND gt.COURSE_ID=\'' . $course_id . '\' AND ((ga.ASSIGNED_DATE IS NOT NULL )  or gg.POINTS IS NOT NULL) GROUP BY gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT'));
                    if ($do_stats)
                        $all_RET = DBGet(DBQuery('SELECT gg.COLLEGE_ROLL_NO, gt.ASSIGNMENT_TYPE_ID,sum(' . db_case(array('gg.POINTS', "'-1'", "'0'", 'gg.POINTS')) . ') AS PARTIAL_POINTS,sum(' . db_case(array('gg.POINTS', "'-1'", "'0'", 'ga.POINTS')) . ') AS PARTIAL_TOTAL,    gt.FINAL_GRADE_PERCENT FROM gradebook_grades gg,gradebook_assignments ga LEFT OUTER JOIN gradebook_grades g ON (g.COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND g.COLLEGE_ROLL_NO=\'' . UserStudentID() . '\' AND g.ASSIGNMENT_ID=ga.ASSIGNMENT_ID),gradebook_assignment_types gt WHERE gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID AND ga.ASSIGNMENT_ID=gg.ASSIGNMENT_ID AND ga.MARKING_PERIOD_ID IN (' . $mp . ') AND gg.COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND (ga.COURSE_PERIOD_ID=\'' . $course_period_id . '\' OR ga.COURSE_ID=\'' . $course_id . '\' AND ga.STAFF_ID=\'' . $staff_id . '\') AND gt.COURSE_ID=\'' . $course_id . '\' AND (ga.ASSIGNED_DATE IS NOT NULL   OR gg.POINTS IS NOT NULL) GROUP BY gg.COLLEGE_ROLL_NO,gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT'), array(), array('COLLEGE_ROLL_NO'));
                }
                else {
                    $mp = GetAllMP('QTR', UserMP());

                    if (!isset($mp))
                        $mp = GetAllMP('SEM', UserMP());
//'gg.POINTS','\'-1\' OR gg.POINTS IS NULL OR (ga.due_date < (select DISTINCT ssm.start_date  from student_enrollment ssm where ssm.COLLEGE_ROLL_NO=s.COLLEGE_ROLL_NO AND ssm.SYEAR=\''.UserSyear().'\' AND ssm.COLLEGE_ID='.UserCollege().' AND (ssm.START_DATE IS NOT NULL AND (CURRENT_DATE<=ssm.END_DATE OR CURRENT_DATE>=ssm.END_DATE OR  ssm.END_DATE IS NULL)) order by ssm.start_date desc limit 1) ) ',"'0'",'ga.POINTS'
                    if (!isset($mp))
                        $mp = GetAllMP('FY', UserMP());
                     $points_RET = DBGet(DBQuery('SELECT \'-1\' AS ASSIGNMENT_TYPE_ID,sum(' . db_case(array('gg.POINTS', "'-1'", "'0'", 'gg.POINTS')) . ') AS PARTIAL_POINTS,sum(' . db_case(array('gg.POINTS', '\'-1\' OR gg.POINTS IS NULL OR (ga.due_date < (select DISTINCT ssm.start_date  from student_enrollment ssm,students s where ssm.COLLEGE_ROLL_NO=s.COLLEGE_ROLL_NO AND s.COLLEGE_ROLL_NO=\'' . UserStudentID() . '\' AND ssm.SYEAR=\'' . UserSyear() . '\' AND ssm.COLLEGE_ID=' . UserCollege() . ' AND (ssm.START_DATE IS NOT NULL AND (CURRENT_DATE<=ssm.END_DATE OR CURRENT_DATE>=ssm.END_DATE OR  ssm.END_DATE IS NULL)) order by ssm.start_date desc limit 1) ) ', "'0'", 'ga.POINTS')) . ') AS PARTIAL_TOTAL,\'1\' AS FINAL_GRADE_PERCENT,sum(' . db_case(array('gg.POINTS', "''", "1", "0")) . ') AS UNGRADED FROM gradebook_assignments ga LEFT OUTER JOIN gradebook_grades gg ON (gg.COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND gg.COLLEGE_ROLL_NO=\'' . UserStudentID() . '\' AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID) WHERE (ga.COURSE_PERIOD_ID=\'' . $course_period_id . '\' OR ga.COURSE_ID=\'' . $course_id . '\' AND ga.STAFF_ID=\'' . $staff_id . '\') AND ga.MARKING_PERIOD_ID IN (' . $mp . ') AND (ga.ASSIGNED_DATE IS NOT NULL ) GROUP BY  FINAL_GRADE_PERCENT'));

                    $points_RET_all1 = DBGet(DBQuery('SELECT gt.ASSIGNMENT_TYPE_ID,sum(' . db_case(array('gg.POINTS', "'-1'", "'0'", 'gg.POINTS')) . ') AS PARTIAL_POINTS,sum(' . db_case(array('gg.POINTS', '\'-1\' OR gg.POINTS IS NULL OR (ga.due_date < (select DISTINCT ssm.start_date  from student_enrollment ssm,students s where ssm.COLLEGE_ROLL_NO=s.COLLEGE_ROLL_NO AND s.COLLEGE_ROLL_NO=\'' . UserStudentID() . '\' AND ssm.SYEAR=\'' . UserSyear() . '\' AND ssm.COLLEGE_ID=' . UserCollege() . ' AND (ssm.START_DATE IS NOT NULL AND (CURRENT_DATE<=ssm.END_DATE OR CURRENT_DATE>=ssm.END_DATE OR  ssm.END_DATE IS NULL)) order by ssm.start_date desc limit 1) ) ', "'0'", 'ga.POINTS')) . ') AS PARTIAL_TOTAL,    gt.FINAL_GRADE_PERCENT,sum(' . db_case(array('gg.POINTS', "''", "1", "0")) . ') AS UNGRADED FROM gradebook_assignments ga LEFT OUTER JOIN gradebook_grades gg ON (gg.COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND gg.COLLEGE_ROLL_NO=\'' . UserStudentID() . '\' AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID),gradebook_assignment_types gt WHERE (ga.COURSE_PERIOD_ID=\'' . $course_period_id . '\' OR ga.COURSE_ID=\'' . $course_id . '\' AND ga.STAFF_ID=\'' . $staff_id . '\') AND ga.MARKING_PERIOD_ID IN (' . $mp . ') AND gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID AND gt.COURSE_ID=\'' . $course_id . '\' AND ((ga.ASSIGNED_DATE IS NOT NULL )  or gg.POINTS IS NOT NULL) GROUP BY gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT'));
                    if ($do_stats)
                        $all_RET = DBGet(DBQuery('SELECT gg.COLLEGE_ROLL_NO,\'-1\' AS ASSIGNMENT_TYPE_ID,sum(' . db_case(array('gg.POINTS', "'-1'", "'0'", 'gg.POINTS')) . ') AS PARTIAL_POINTS,sum(' . db_case(array('gg.POINTS', '\'-1\' OR gg.POINTS IS NULL OR (ga.due_date < (select DISTINCT ssm.start_date  from student_enrollment ssm,students s where ssm.COLLEGE_ROLL_NO=s.COLLEGE_ROLL_NO AND s.COLLEGE_ROLL_NO=\'' . UserStudentID() . '\' AND ssm.SYEAR=\'' . UserSyear() . '\' AND ssm.COLLEGE_ID=' . UserCollege() . ' AND (ssm.START_DATE IS NOT NULL AND (CURRENT_DATE<=ssm.END_DATE OR CURRENT_DATE>=ssm.END_DATE OR  ssm.END_DATE IS NULL)) order by ssm.start_date desc limit 1) ) ', "'0'", 'ga.POINTS')) . ') AS PARTIAL_TOTAL,\'1\' AS FINAL_GRADE_PERCENT FROM gradebook_grades gg,gradebook_assignments ga LEFT OUTER JOIN gradebook_grades g ON (g.COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND g.COLLEGE_ROLL_NO=\'' . UserStudentID() . '\' AND g.ASSIGNMENT_ID=ga.ASSIGNMENT_ID)
                                                        WHERE  ga.ASSIGNMENT_ID=gg.ASSIGNMENT_ID AND ga.MARKING_PERIOD_ID IN (' . $mp . ') AND gg.COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND (ga.COURSE_PERIOD_ID=\'' . $course_period_id . '\' OR ga.COURSE_ID=\'' . $course_id . '\' AND ga.STAFF_ID=\'' . $staff_id . '\') AND (ga.ASSIGNED_DATE IS NOT NULL OR gg.POINTS IS NOT NULL) GROUP BY gg.COLLEGE_ROLL_NO, FINAL_GRADE_PERCENT'), array(), array('COLLEGE_ROLL_NO'));
                }
                $cls_tot = 0;
                $Class_Rank = array();
                $Class_Rank = DBGet(DBQuery('SELECT  COUNT(ga.COLLEGE_ROLL_NO) AS TOTAL_STUDENT FROM gradebook_grades ga WHERE ga.COURSE_PERIOD_ID=\'' . $course_period_id . '\'   GROUP BY ga.COLLEGE_ROLL_NO'));
//                                $Class_Rank = DBGet(DBQuery(' SELECT FOUND_ROWS() as TOTAL_STUDENT')) ;
                if (count($Class_Rank))
                    $cls_tot = count($Class_Rank);
                $total = $total_percent = 0;
                $ungraded = 0;
                if (empty($points_RET)) {
                    $total = 'Not graded';
                    $total_percent = 0;
                } else {
                    foreach ($points_RET as $partial_points) {
                        if ($partial_points['PARTIAL_TOTAL'] != 0) {
                            $total += $partial_points['PARTIAL_POINTS'];
                            $total_percent += $partial_points['PARTIAL_TOTAL'];
                        } else {
                            $total = 'Not graded';
                            $total_percent = 0;
                        }
                    }
                }

                foreach ($points_RET_all1 as $partial_points1) {
                    $ungraded += $partial_points1['UNGRADED'];
                }

                if ($total_percent != 0)
                    $total /= $total_percent;
                $percent = $total;


                if ($do_stats) {
                    unset($bargraph1);
                    unset($bargraph2);
                    $min_percent = $max_percent = $percent;
                    $avg_percent = 0;
                    $lower = $higher = 0;
                    foreach ($all_RET as $xcollege_roll_no => $student) {
                        if ($student['COLLEGE_ROLL_NO'])
                            $count++;
                        $total = $total_percent = 0;
                        foreach ($student as $partial_points)
                            if ($partial_points['PARTIAL_TOTAL'] != 0) {
                                $total += $partial_points['PARTIAL_POINTS'];
                                $total_percent += $partial_points['PARTIAL_TOTAL'];
                            }

                        if ($total_percent != 0)
                            $total /= $total_percent;
                        $Rank_Pos[] = number_format(100 * $total, 1);
                    }
                    if ($total < $min_percent)
                        $min_percent = $total;
                    if ($total > $max_percent)
                        $max_percent = $total;
                    $avg_percent += $total;
                    if ($xcollege_roll_no !== UserStudentID())
                        if ($total > $percent)
                            $higher++;
                        else
                            $lower++;
                }


                $avg_percent /= count($all_RET);

                $scale = $max_percent > 1 ? $max_percent : 1;
                $w1 = round(100 * $min_percent / $scale);
                if ($percent < $avg_percent) {
                    $w2 = round(100 * ($percent - $min_percent) / $scale);
                    $c2 = '#ff0000';
                    $w4 = round(100 * ($max_percent - $avg_percent) / $scale);
                    $c4 = '#00ff00';
                } else {
                    $w2 = round(100 * ($avg_percent - $min_percent) / $scale);
                    $c2 = '#00ff00';
                    $w4 = round(100 * ($max_percent - $percent) / $scale);
                    $c4 = '#ff0000';
                }
                $w5 = round(100 * (1.0 - $max_percent / $scale));

                $w3 = 100 - $w1 - $w2 - $w4 - $w5;


                rsort($Rank_Pos);
                foreach ($Rank_Pos as $key => $val) { {
                        if (number_format(100 * $percent, 1) == $val)
                            $rank = $key + 1;
                    }

                    $highrange = max($Rank_Pos);
                    $lowrange = min($Rank_Pos);
                    $bargraph1 = $lowrange . " - " . $highrange;

                    $scale = $lower + $higher + 1;
                    $w1 = round(100 * $lower / $scale);
                    $w3 = round(100 * $higher / $scale);
                    $w2 = 100 - $w1 - $w3;

                    if ($rank)
                        $bargraph2 = $rank . " out of " . $Class_Rank[1]['TOTAL_STUDENT'];
                }

                if ($percent == 'Not graded')
                    $LO_ret[] = array('ID' => $course_period_id, 'TITLE' => $course['COURSE_TITLE'], 'TEACHER' => substr($course_title, strrpos(str_replace(' - ', ' ^ ', $course_title), '^') + 2), 'PERCENT' => _makeLetterGrade($percent, $course_period_id, $staff_id, "%") . '%', 'GRADE' => '<b>' . 'Not graded' . '</b>', 'UNGRADED' => $ungraded) + ($do_stats ? array('BAR1' => $bargraph1, 'BAR2' => $bargraph2) : array());
                else
                    $LO_ret[] = array('ID' => $course_period_id, 'TITLE' => $course['COURSE_TITLE'], 'TEACHER' => substr($course_title, strrpos(str_replace(' - ', ' ^ ', $course_title), '^') + 2), 'PERCENT' => _makeLetterGrade($percent, $course_period_id, $staff_id, "%") . '%', 'GRADE' => '<b>' . _makeLetterGrade($percent, $course_period_id, $staff_id) . '</b>', 'UNGRADED' => $ungraded) + ($do_stats ? array('BAR1' => $bargraph1, 'BAR2' => $bargraph2) : array());

                unset($Rank_Pos);
            }
            unset($LO_ret[0]);
            $link = array('TITLE' => array('link' => "Modules.php?modname=$_REQUEST[modname]", 'variables' => array('id' => 'ID')));
            echo '<div class="panel">';
            ListOutput($LO_ret, $LO_columns, 'Course', 'Courses', $link, array(), array('center' => false, 'save' => false, 'search' => false));
            echo '</div>';
        } else
            DrawHeader('There are no grades available for this student.');
    }

    else {

        if ($_REQUEST['modfun'] == 'assgn_detail') {

            $assignments_RET = DBGet(DBQuery('SELECT ga.TITLE,ga.DESCRIPTION,ga.ASSIGNED_DATE,ga.DUE_DATE,ga.POINTS ,gt.title as assignment_type
                                                   FROM gradebook_assignments ga, gradebook_assignment_types gt
                                      where assignment_id =\'' . $_REQUEST['assignment_id'] . '\' and gt.assignment_type_id=ga.assignment_type_id'));

            $val1 = '<div class="panel-heading"><h6 class="panel-title">Assignment Details</h6></div>';
            $val1 .= '<hr class="no-margin"/>';
            $val1 .= '<div class="panel-body">';
            $val1 .= '<div class="row form-horizontal">';
            $val1 .= '<div class="col-md-4">';
            $val1 .= '<div class="form-group">';
            $val1 .= '<label class="control-label col-lg-4">Title</label>';
            $val1 .= '<div class="col-lg-8"><input type="readonly" class="form-control" value="' . $assignments_RET[1]['TITLE'] . '" /></div>';
            $val1 .= '</div>'; //.form-group
            $val1 .= '</div>'; //.col-md-4
            $val1 .= '<div class="col-md-4">';
            $val1 .= '<div class="form-group">';
            $val1 .= '<label class="control-label col-lg-4">Assigned Date</label>';
            $val1 .= '<div class="col-lg-8"><input type="readonly" class="form-control" value="' . $assignments_RET[1]['ASSIGNED_DATE'] . '" /></div>';
            $val1 .= '</div>'; //.form-group
            $val1 .= '</div>';
            $val1 .= '<div class="col-md-4">';
            $val1 .= '<div class="form-group">';
            $val1 .= '<label class="control-label col-lg-4">Due Date</label>';
            $val1 .= '<div class="col-lg-8"><input type="readonly" class="form-control" value="' . $assignments_RET[1]['DUE_DATE'] . '" /></div>';
            $val1 .= '</div>'; //.form-group
            $val1 .= '</div>'; //.col-md-4
            $val1 .= '</div>'; //.row
            
            $val1 .= '<div class="row form-horizontal">';
            $val1 .= '<div class="col-md-4">';
            $val1 .= '<div class="form-group">';
            $val1 .= '<label class="control-label col-lg-4">Assignement Type</label>';
            $val1 .= '<div class="col-lg-8"><input type="readonly" class="form-control" value="' . $assignments_RET[1]['ASSIGNMENT_TYPE'] . '" /></div>';
            $val1 .= '</div>'; //.form-group
            $val1 .= '</div>'; //.col-md-4
            $val1 .= '<div class="col-md-4">';
            $val1 .= '<div class="form-group">';
            $val1 .= '<label class="control-label col-lg-4">Points</label>';
            $val1 .= '<div class="col-lg-8"><input type="readonly" class="form-control" value="' . $assignments_RET[1]['POINTS'] . '" /></div>';
            $val1 .= '</div>'; //.form-group
            $val1 .= '</div>';
            $val1 .= '<div class="col-md-4">';
            $val1 .= '<div class="form-group">';
            $val1 .= '<label class="control-label col-lg-4">Description</label>';
            $val1 .= '<div class="col-lg-8"><input type="readonly" class="form-control" value="' . strip_tags(html_entity_decode(html_entity_decode($assignments_RET[1]['DESCRIPTION']))) . '" /></div>';
            $val1 .= '</div>'; //.form-group
            $val1 .= '</div>'; //.col-md-4
            $val1 .= '</div>'; //.row
            $val1 .= '</div>'; //.panel-body
            
        }

        if ($_REQUEST['id'] == 'all') {

            $mp = GetAllMP('QTR', UserMP());

            if (!isset($mp))
                $mp = GetAllMP('SEM', UserMP());

            if (!isset($mp))
                $mp = GetAllMP('FY', UserMP());

            $courses_RET = DBGet(DBQuery('SELECT c.TITLE AS COURSE_TITLE,cp.TITLE,cp.COURSE_PERIOD_ID,cp.COURSE_ID,cp.TEACHER_ID AS STAFF_ID FROM schedule s,course_periods cp,courses c WHERE s.SYEAR=\'' . UserSyear() . '\' AND cp.COURSE_PERIOD_ID=s.COURSE_PERIOD_ID AND s.MARKING_PERIOD_ID IN (' . $mp . ') AND (\'' . DBDate() . '\' BETWEEN s.START_DATE AND s.END_DATE OR \'' . DBDate() . '\'>=s.START_DATE AND s.END_DATE IS NULL) AND s.COLLEGE_ROLL_NO=\'' . UserStudentID() . '\' AND cp.GRADE_SCALE_ID IS NOT NULL' . (User('PROFILE') == 'teacher' ? ' AND cp.TEACHER_ID=\'' . User('STAFF_ID') . '\'' : '') . ' AND c.COURSE_ID=cp.COURSE_ID ORDER BY cp.COURSE_ID'));
            echo '<div class="panel">';
            DrawHeader('All Courses', '');
            DrawHeader($val1);
            echo '</div>';
        }
        else {
            $courses_RET = DBGet(DBQuery('SELECT c.TITLE AS COURSE_TITLE,cp.TITLE,cp.COURSE_PERIOD_ID,cp.COURSE_ID,cp.TEACHER_ID AS STAFF_ID FROM course_periods cp,courses c WHERE cp.COURSE_PERIOD_ID=\'' . clean_param($_REQUEST[id], PARAM_INT) . '\' AND c.COURSE_ID=cp.COURSE_ID'));
            echo '<div class="panel">';
            DrawHeader('<span class="text-pink"><b>' . $courses_RET[1]['COURSE_TITLE'] . '</b> - ' . substr($courses_RET[1]['TITLE'].'</span>', strrpos(str_replace(' - ', ' ^ ', $courses_RET[1]['TITLE']), '^') + 2), "<span class=\"heading-text\"><A HREF=Modules.php?modname=$_REQUEST[modname]><i class=\"icon-square-left\"></i> &nbsp; Back to Totals</A></span>");
            echo '</div>';
            echo '<div class="panel">';
            DrawHeaderhome($val1);
            echo '</div>';
        }


        foreach ($courses_RET as $course) {

            $staff_id = $course['STAFF_ID'];
            if (!$programconfig[$staff_id]) {
                $config_RET = DBGet(DBQuery('SELECT TITLE,VALUE FROM program_user_config WHERE USER_ID=\'' . $staff_id . '\' AND PROGRAM=\'Gradebook\' AND VALUE LIKE \'%_' . UserCoursePeriod() . '\''), array(), array('TITLE'));
                if (count($config_RET))
                    foreach ($config_RET as $title => $value)
                        $programconfig[$staff_id][$title] = rtrim($value[1]['VALUE'], '_' . UserCoursePeriod());
                else
                    $programconfig[$staff_id] = true;
            }
            $assignments_RET = DBGet(DBQuery('SELECT ga.ASSIGNMENT_ID,gg.POINTS,ga.ASSIGNED_DATE,ga.DUE_DATE,ga.DUE_DATE AS DUE ,gg.COMMENT,ga.TITLE,ga.DESCRIPTION,ga.ASSIGNED_DATE,ga.DUE_DATE,ga.POINTS AS POINTS_POSSIBLE,at.TITLE AS CATEGORY
                                                   FROM gradebook_assignments ga LEFT OUTER JOIN gradebook_grades gg
                                                  ON (gg.COURSE_PERIOD_ID=\'' . $course[COURSE_PERIOD_ID] . '\' AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND gg.COLLEGE_ROLL_NO=\'' . UserStudentID() . '\'),gradebook_assignment_types at
                                                  WHERE (ga.COURSE_PERIOD_ID=\'' . $course[COURSE_PERIOD_ID] . '\' OR ga.COURSE_ID=\'' . $course[COURSE_ID] . '\' AND ga.STAFF_ID=\'' . $staff_id . '\') AND ga.MARKING_PERIOD_ID IN (' . GetAllMP($MP_TYPE, UserMP()) . ') 
                                                   AND at.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID AND ((ga.ASSIGNED_DATE IS NOT NULL )
                                                  or gg.POINTS IS NOT NULL) AND (ga.POINTS!=\'0\' OR gg.POINTS IS NOT NULL AND gg.POINTS!=\'-1\') ORDER BY ga.ASSIGNMENT_ID DESC'), array('TITLE' => '_makeTipTitle', 'ASSIGNED_DATE' => 'ProperDate', 'DUE_DATE' => 'ProperDate'));


            $stu_enroll_date = DBGet(DBQuery('SELECT * FROM student_enrollment ssm WHERE COLLEGE_ROLL_NO=\'' . UserStudentID() . '\'  AND ssm.SYEAR=\'' . UserSyear() . '\' AND ((ssm.START_DATE IS NOT NULL AND \'' . date('Y-m-d') . '\'>=ssm.START_DATE) AND (\'' . date('Y-m-d') . '\'<=ssm.END_DATE OR ssm.END_DATE IS NULL)) '));
            $stu_enroll_date = $stu_enroll_date[1][START_DATE];
            if (count($assignments_RET)) {
                
                echo '<div class="panel">';
                if ($_REQUEST['id'] == 'all') {

                    DrawHeader('<span class="text-pink"><b>' . $course['COURSE_TITLE'] . '</b> - ' . substr($course['TITLE'], strrpos(str_replace(' - ', ' ^ ', $course['TITLE']), '^') + 2).'</span>', "<span class=\"heading-text\"><A HREF=Modules.php?modname=$_REQUEST[modname]><i class=\"icon-square-left\"></i> &nbsp; Back to Totals</A></span>");
                    echo '<hr class="no-margin"/>';
                }
                if ($do_stats)
                    $all_RET = DBGet(DBQuery('SELECT ga.ASSIGNMENT_ID,gg.POINTS,min(' . db_case(array('gg.POINTS', "'-1'", 'ga.POINTS', 'gg.POINTS')) . ') AS MIN,max(' . db_case(array('gg.POINTS', "'-1'", '0', 'gg.POINTS')) . ') AS MAX,' . db_case(array("sum(" . db_case(array('gg.POINTS', "'-1'", '0', '1')) . ")", "'0'", "'0'", "sum(" . db_case(array('gg.POINTS', "'-1'", '0', 'gg.POINTS')) . ') / sum(' . db_case(array('gg.POINTS', "'-1'", '0', '1')) . ")")) . ' AS AVG,sum(CASE WHEN gg.POINTS<=g.POINTS AND gg.COLLEGE_ROLL_NO!=g.COLLEGE_ROLL_NO THEN 1 ELSE 0 END) AS LOWER,sum(CASE WHEN gg.POINTS>g.POINTS THEN 1 ELSE 0 END) AS HIGHER FROM gradebook_grades gg,gradebook_assignments ga LEFT OUTER JOIN gradebook_grades g ON (g.COURSE_PERIOD_ID=\'' . $course['COURSE_PERIOD_ID'] . '\' AND g.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND g.COLLEGE_ROLL_NO=\'' . UserStudentID() . '\'),gradebook_assignment_types at WHERE (ga.COURSE_PERIOD_ID=\'' . $course['COURSE_PERIOD_ID'] . '\' OR ga.COURSE_ID=\'' . $course['COURSE_ID'] . '\' AND ga.STAFF_ID=\'' . $staff_id . '\') AND ga.MARKING_PERIOD_ID=\'' . UserMP() . '\' AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND at.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID AND ((ga.ASSIGNED_DATE IS NOT NULL )  OR g.POINTS IS NOT NULL) AND ga.POINTS!=\'0\' GROUP BY ga.ASSIGNMENT_ID'), array(), array('ASSIGNMENT_ID'));

                if ($display_rank == 'Y')
                    $LO_columns = array('TITLE' => 'Title', 'CATEGORY' => 'Category', 'POINTS' => 'Points / Possible', 'PERCENT' => 'Percent', 'LETTER' => 'Letter', 'ASSIGNED_DATE' => 'Assigned Date', 'DUE_DATE' => 'Due Date') + ($do_stats ? array('BAR1' => 'Grade Range', 'BAR2' => 'Class Rank') : array());
                else
                    $LO_columns = array('TITLE' => 'Title', 'CATEGORY' => 'Category', 'POINTS' => 'Points / Possible', 'PERCENT' => 'Percent', 'LETTER' => 'Letter', 'ASSIGNED_DATE' => 'Assigned Date', 'DUE_DATE' => 'Due Date') + ($do_stats ? array('BAR1' => 'Grade Range') : array());

                $LO_ret = array(0 => array());

                foreach ($assignments_RET as $assignment) {

                    $days_left = floor((strtotime($assignment[DUE], 0) - strtotime($stu_enroll_date, 0)) / 86400);
                    if ($days_left >= 1) {
                        if ($do_stats) {
                            unset($bargraph1);
                            unset($bargraph2);
                            if ($all_RET[$assignment['ASSIGNMENT_ID']]) {
                                $all = $all_RET[$assignment['ASSIGNMENT_ID']][1];
                                $all_RET1 = DBGet(DBQuery('SELECT g.ASSIGNMENT_ID,g.POINTS  FROM gradebook_grades g where g.COURSE_PERIOD_ID=\'' . $course[COURSE_PERIOD_ID] . '\' '));
                                $count_tot = 0;
                                foreach ($all_RET1 as $all1) {
                                    if ($assignment['ASSIGNMENT_ID'] == $all1['ASSIGNMENT_ID']) {
                                        $assg_tot[] = $all1['POINTS'];
                                        $count_tot++;
                                    }
                                }
                                rsort($assg_tot);
                                unset($ranknew);
                                unset($prev_val);
                                $k = 0;
                                foreach ($assg_tot as $key => $val) {
                                    if ($prev_val != $val)
                                        $k++;
                                    "RankNew[" . $key . "] = " . $val . "\n";
                                    if ($assignment['POINTS'] == $val)
                                        if ($prev_val != $val)
                                            $ranknew = $k;
                                    ;
                                    $prev_val = $val;
                                }
                                unset($assg_tot);

                                $scale = $all['MAX'] > $assignment['POINTS_POSSIBLE'] ? $all['MAX'] : $assignment['POINTS_POSSIBLE'];
                                if ($ranknew && $assignment['POINTS'] > 0)
                                    $bargraph2 = $ranknew . " out of " . $count_tot;
                                if ($assignment['POINTS'] != '-1' && $assignment['POINTS'] != '') {

                                    $w1 = round(100 * $all['MIN'] / $scale);
                                    if ($assignment['POINTS'] < $all['AVG']) {
                                        $w2 = round(100 * ($assignment['POINTS'] - $all['MIN']) / $scale);
                                        $c2 = '#ff0000';
                                        $w4 = round(100 * ($all['MAX'] - $all['AVG']) / $scale);
                                        $c4 = '#00ff00';
                                    } else {
                                        $w2 = round(100 * ($all['AVG'] - $all['MIN']) / $scale);
                                        $c2 = '#00ff00';
                                        $w4 = round(100 * ($all['MAX'] - $assignment['POINTS']) / $scale);
                                        $c4 = '#ff0000';
                                    }
                                    $w5 = round(100 * (1.0 - $all['MAX'] / $scale));
                                    $w3 = 100 - $w1 - $w2 - $w4 - $w5;

                                    $bargraph1 = $all['MIN'] . " - " . $all['MAX'];
                                    $scale = $all['LOWER'] + $all['HIGHER'] + 1;
                                    $w1 = round(100 * $all['LOWER'] / $scale);
                                    $w3 = round(100 * $all['HIGHER'] / $scale);
                                    $w2 = 100 - $w1 - $w3;
                                }
                            }
                        }

                        $LO_ret[] = array('TITLE' => $assignment['TITLE'], 'CATEGORY' => $assignment['CATEGORY'], 'POINTS' => ($assignment['POINTS'] == '-1' ? '*' : ($assignment['POINTS'] == '' ? '*' : rtrim(rtrim(number_format($assignment['POINTS'], 2), '0'), '.'))) . ' / ' . $assignment['POINTS_POSSIBLE'], 'PERCENT' => ($assignment['POINTS_POSSIBLE'] == '0' ? '' : ($assignment['POINTS'] == '-1' || $assignment['POINTS'] == '' ? '*' : _makeLetterGrade($assignment['POINTS'] / $assignment['POINTS_POSSIBLE'], $course['COURSE_PERIOD_ID'], $staff_id, "%") . '%')), 'LETTER' => ($assignment['POINTS_POSSIBLE'] == '0' ? 'e/c' : ($assignment['POINTS'] == '-1' || $assignment['POINTS'] == '' ? 'Not Graded' : '<b>' . _makeLetterGrade($assignment['POINTS'] / $assignment['POINTS_POSSIBLE'], $course['COURSE_PERIOD_ID'], $staff_id))) . '</b>', 'ASSIGNED_DATE' => $assignment['ASSIGNED_DATE'], 'DUE_DATE' => $assignment['DUE_DATE']) + ($do_stats ? array('BAR1' => $bargraph1, 'BAR2' => $bargraph2) : array());
                    }
                }

                unset($LO_ret[0]);
                ListOutput($LO_ret, $LO_columns, 'Assignment', 'Assignments', array(), array(), array('center' => false, 'save' => $_REQUEST['id'] != 'all', 'search' => false));
                echo '</div>';
            } else
            if ($_REQUEST['id'] != 'all')
                DrawHeader('There are no grades available for this student.');
        }
    }
}

function _makeTipTitle($value, $column) {
    global $THIS_RET;

    if (($THIS_RET['DESCRIPTION'] || $THIS_RET['ASSIGNED_DATE'] || $THIS_RET['DUE_DATE']) && !$_REQUEST['_openSIS_PDF']) {
        $tip_title = '<A HREF=Modules.php?modname=grades/StudentGrades.php&id=' . $_REQUEST['id'] . '&modfun=assgn_detail&assignment_id=' . $THIS_RET['ASSIGNMENT_ID'].'>' . $value . '</A>';
    } else
        $tip_title = $value;

    return $tip_title;
}

?>
