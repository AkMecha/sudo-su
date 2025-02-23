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
DrawBC("GradeBook > " . ProgramTitle());

echo '<div class="panel panel-default">';
echo '<div class="panel-body">';
$mp_RET = DBGet(DBQuery('SELECT MP FROM course_periods WHERE course_period_id = \'' . UserCoursePeriod() . '\''));
if ($mp_RET[1]['MP'] == 'SEM') {
    $sem = GetParentMP('SEM', UserMP());

    $qtr = GetChildrenMP('QTR', UserMP());
}
if ($mp_RET[1]['MP'] == 'FY') {
    $sem = GetParentMP('SEM', UserMP());
    if ($sem)
        $fy = GetParentMP('FY', $sem);
    else
        $fy = GetParentMP('FY', UserMP());
    $qtr = GetChildrenMP('QTR', UserMP());
    $pros = GetChildrenMP('PRO', UserMP());
}
if ($mp_RET[1]['MP'] == 'QTR') {
    $qtr = GetChildrenMP('QTR', UserMP());
}
// if the UserMP has been changed, the REQUESTed MP may not work
if (CpvId() != '') {
    $cp_det = DBGet(DBQuery('SELECT cp.BEGIN_DATE,cp.MARKING_PERIOD_ID FROM course_periods cp,course_period_var cpv WHERE cpv.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND cpv.ID=' . CpvId()));
    if ($cp_det[1]['MARKING_PERIOD_ID'] == '') {
        $cp_type = 'custom';
    }
}
if ((!$_REQUEST['mp'] || strpos($str = "'" . UserMP() . "','" . $sem . "','" . $fy . "'," . $pros, "'" . ltrim($_REQUEST['mp'], 'E') . "'") === false) && $cp_type != 'custom') {
    $_REQUEST['mp'] = UserMP();
} elseif ($cp_type == 'custom' && !isset($_REQUEST['mp'])) {
    $full_year_mp = DBGet(DBQuery('SELECT MARKING_PERIOD_ID FROM college_years WHERE COLLEGE_ID=' . UserCollege() . ' AND SYEAR=' . UserSyear()));
    $_REQUEST['mp'] = $full_year_mp[1]['MARKING_PERIOD_ID'];
}
if ($_REQUEST['period'] == '')
    $_REQUEST['period'] = CpvId();
$custom_p = 'n';
if ($_REQUEST['period'] != '' && $_REQUEST['mp'] != '') {
    $check_custom = DBGet(DBQuery('SELECT cp.MARKING_PERIOD_ID  FROM course_periods cp,course_period_var cpv WHERE cp.COURSE_PERIOD_ID=cpv.COURSE_PERIOD_ID AND cpv.ID=' . $_REQUEST['period']));
    if ($check_custom[1]['MARKING_PERIOD_ID'] == '') {

        $get_syear_id = DBGet(DBQuery('SELECT MARKING_PERIOD_ID FROM college_years WHERE SYEAR=' . UserSyear() . ' AND COLLEGE_ID=' . UserCollege()));
        $custom_p = 'y';
    }
}
$course_period_id = UserCoursePeriod();
if ($course_period_id)
    $course_RET = DBGet(DBQuery('SELECT cp.COURSE_ID,c.TITLE as COURSE_NAME, cp.TITLE, cp.GRADE_SCALE_ID,CREDIT(cp.COURSE_PERIOD_ID,\'' . $_REQUEST['mp'] . '\') AS CREDITS,cp.COURSE_WEIGHT,cp.MARKING_PERIOD_ID FROM course_periods cp, courses c WHERE cp.COURSE_ID = c.COURSE_ID AND cp.COURSE_PERIOD_ID=\'' . $course_period_id . '\''));  //sg              
if (!$course_RET[1]['GRADE_SCALE_ID'] && !$_REQUEST['include_inactive']) {
    echo '<div class="alert bg-warning alert-styled-left">You cannot enter letter grades as grade scale is not set for this course .</div>';
    $not_graded = true;
    $_REQUEST['use_percents'] = true;
}
$course_title = $course_RET[1]['TITLE'];
$grade_scale_id = $course_RET[1]['GRADE_SCALE_ID'];
$course_id = $course_RET[1]['COURSE_ID'];

if ($_REQUEST['mp']) {
    $current_RET = DBGet(DBQuery('SELECT g.COLLEGE_ROLL_NO,g.REPORT_CARD_GRADE_ID,g.GRADE_PERCENT,g.REPORT_CARD_COMMENT_ID,g.COMMENT FROM student_report_card_grades g,course_periods cp WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID AND cp.COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND g.MARKING_PERIOD_ID=\'' . $_REQUEST['mp'] . '\''), array(), array('COLLEGE_ROLL_NO'));
    $current_commentsA_RET = DBGet(DBQuery('SELECT g.COLLEGE_ROLL_NO,g.REPORT_CARD_COMMENT_ID,g.COMMENT FROM student_report_card_comments g,course_periods cp WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID AND cp.COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND g.MARKING_PERIOD_ID=\'' . $_REQUEST['mp'] . '\' AND g.REPORT_CARD_COMMENT_ID IN (SELECT ID FROM report_card_comments WHERE COURSE_ID IS NOT NULL)'), array(), array('COLLEGE_ROLL_NO', 'REPORT_CARD_COMMENT_ID'));
    $current_commentsB_RET = DBGet(DBQuery('SELECT g.COLLEGE_ROLL_NO,g.REPORT_CARD_COMMENT_ID FROM student_report_card_comments g,course_periods cp WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID AND cp.COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND g.MARKING_PERIOD_ID=\'' . $_REQUEST['mp'] . '\' AND g.REPORT_CARD_COMMENT_ID IN (SELECT ID FROM report_card_comments WHERE COURSE_ID IS NULL)'), array(), array('COLLEGE_ROLL_NO'));
    $max_current_commentsB = 0;
    foreach ($current_commentsB_RET as $comments)
        if (count($comments) > $max_current_commentsB)
            $max_current_commentsB = count($comments);
    $current_completed = count(DBGet(DBQuery('SELECT \'\' FROM grades_completed WHERE STAFF_ID=\'' . User('STAFF_ID') . '\' AND MARKING_PERIOD_ID=\'' . $_REQUEST['mp'] . '\' AND PERIOD_ID=\'' . UserPeriod() . '\'')));
}
//bjj need more information on grades to load into student_report_card_grades
$grades_RET = DBGet(DBQuery('SELECT rcg.ID,rcg.TITLE,rcg.GPA_VALUE AS WEIGHTED_GP, rcg.UNWEIGHTED_GP ,gs.GP_SCALE  FROM report_card_grades rcg, report_card_grade_scales gs WHERE rcg.grade_scale_id = gs.id AND rcg.SYEAR=\'' . UserSyear() . '\' AND rcg.COLLEGE_ID=\'' . UserCollege() . '\' AND rcg.GRADE_SCALE_ID=\'' . $grade_scale_id . '\' ORDER BY rcg.BREAK_OFF IS NOT NULL DESC,rcg.BREAK_OFF DESC,rcg.SORT_ORDER'), array(), array('ID'));
$commentsA_RET = DBGet(DBQuery('SELECT ID,TITLE FROM report_card_comments WHERE COLLEGE_ID=\'' . UserCollege() . '\' AND SYEAR=\'' . UserSyear() . '\' AND (COURSE_ID=\'' . $course_id . '\' OR COURSE_ID=\'0\') ORDER BY SORT_ORDER'));
$commentsB_RET = DBGet(DBQuery('SELECT ID,TITLE,SORT_ORDER FROM report_card_comments WHERE COLLEGE_ID=\'' . UserCollege() . '\' AND SYEAR=\'' . UserSyear() . '\' AND COURSE_ID IS NULL ORDER BY SORT_ORDER'), array(), array('ID'));
$grades_select = array('' => '');



foreach ($grades_RET as $id => $code)
    $grades_select += array($id => array($code[1]['TITLE'], '<b>' . $code[1]['TITLE'] . '</b>'));
$commentsB_select = array();
if (0)
    foreach ($commentsB_RET as $id => $comment)
        $commentsB_select += array($id => array($comment[1]['SORT_ORDER'], $comment[1]['TITLE']));
else
    foreach ($commentsB_RET as $id => $comment)
        $commentsB_select += array($id => array($comment[1]['SORT_ORDER'] . ' - ' . substr($comment[1]['TITLE'], 0, 19) . (strlen($comment[1]['TITLE']) > 20 ? '...' : ''), $comment[1]['TITLE']));

if (clean_param($_REQUEST['modfunc'], PARAM_ALPHAMOD) == 'gradebook') {
    if ($_REQUEST['mp']) {
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

        $_openSIS['_makeLetterGrade']['courses'][$course_period_id] = DBGet(DBQuery('SELECT DOES_BREAKOFF,GRADE_SCALE_ID FROM course_periods WHERE COURSE_PERIOD_ID=\'' . $course_period_id . '\''));
        $_SESSION['ROUNDING'] = $programconfig[User('STAFF_ID')]['ROUNDING'];
        include '_MakeLetterGradeFnc.php';
        if (false && GetMP($_REQUEST['mp'], 'TABLE') == 'college_semesters')
            $points_RET = DBGet(DBQuery('SELECT COLLEGE_ROLL_NO,MARKING_PERIOD_ID FROM student_report_card_grades WHERE COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND MARKING_PERIOD_ID IN (' . GetAllMP('SEM', $_REQUEST['mp']) . ",'E" . GetParentMP('SEM', UserMP()) . '\')'), array(), array('COLLEGE_ROLL_NO'));

        if (GetMP($_REQUEST['mp'], 'TABLE') == 'college_quarters' || GetMP($_REQUEST['mp'], 'TABLE') == 'college_progress_periods') {
            // the 'populate the form' approach does not require that we get precisely the right students because nothing is modified here
            // so we don't need to filter on enrollment dates - in fact, for include_inactive we want 'em all anyway
            if ($programconfig[User('STAFF_ID')]['WEIGHT'] == 'Y') {
                $course_periods = DBGet(DBQuery('select marking_period_id from course_periods where course_period_id=' . UserCoursePeriod()));
                if ($course_periods[1]['MARKING_PERIOD_ID'] == NULL) {
                    $college_years = DBGet(DBQuery('select marking_period_id from  college_years where  syear=' . UserSyear() . ' and college_id=' . UserCollege()));
                    $fy_mp_id = $college_years[1]['MARKING_PERIOD_ID'];

                    $points_RET = DBGet(DBQuery('SELECT DISTINCT s.COLLEGE_ROLL_NO,gt.ASSIGNMENT_TYPE_ID,  gt.ASSIGNMENT_TYPE_ID,sum(' . db_case(array('gg.POINTS', "'-1'", "'0'", 'gg.POINTS')) . ') AS PARTIAL_POINTS,
                            sum(' . db_case(array('gg.POINTS', '\'-1\' OR gg.POINTS IS NULL  OR (ga.due_date <  (select DISTINCT ssm.start_date  from student_enrollment ssm where ssm.COLLEGE_ROLL_NO=s.COLLEGE_ROLL_NO AND ssm.SYEAR=\'' . UserSyear() . '\' AND ssm.COLLEGE_ID=' . UserCollege() . ' AND (ssm.START_DATE IS NOT NULL AND (CURRENT_DATE<=ssm.END_DATE OR CURRENT_DATE>=ssm.END_DATE OR  ssm.END_DATE IS NULL)) order by ssm.start_date desc limit 1
)  ) ', "'0'", 'ga.POINTS')) . ') AS PARTIAL_TOTAL,
                                gt.FINAL_GRADE_PERCENT FROM students s JOIN schedule ss ON (ss.COLLEGE_ROLL_NO=s.COLLEGE_ROLL_NO AND ss.COURSE_PERIOD_ID=\'' . $course_period_id . '\') JOIN gradebook_assignments ga ON ((ga.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID OR ga.COURSE_ID=\'' . $course_id . '\' AND ga.STAFF_ID=\'' . User('STAFF_ID') . '\') AND (ga.MARKING_PERIOD_ID=\'' . UserMP() . '\' OR ga.MARKING_PERIOD_ID=\'' . $fy_mp_id . '\')) LEFT OUTER JOIN gradebook_grades gg ON (gg.COLLEGE_ROLL_NO=s.COLLEGE_ROLL_NO AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND gg.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID)
                                 
                                ,gradebook_assignment_types gt WHERE gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID AND gt.COURSE_ID=\'' . $course_id . '\' AND ((ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE) AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE) OR gg.POINTS IS NOT NULL) GROUP BY s.COLLEGE_ROLL_NO,ss.START_DATE,gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT'), array(), array('COLLEGE_ROLL_NO'));
                } else {

                    $points_RET = DBGet(DBQuery('SELECT DISTINCT s.COLLEGE_ROLL_NO,gt.ASSIGNMENT_TYPE_ID,  gt.ASSIGNMENT_TYPE_ID,sum(' . db_case(array('gg.POINTS', "'-1'", "'0'", 'gg.POINTS')) . ') AS PARTIAL_POINTS,
                            sum(' . db_case(array('gg.POINTS', '\'-1\' OR gg.POINTS IS NULL  OR (ga.due_date <  (select DISTINCT ssm.start_date  from student_enrollment ssm where ssm.COLLEGE_ROLL_NO=s.COLLEGE_ROLL_NO AND ssm.SYEAR=\'' . UserSyear() . '\' AND ssm.COLLEGE_ID=' . UserCollege() . ' AND (ssm.START_DATE IS NOT NULL AND (CURRENT_DATE<=ssm.END_DATE OR CURRENT_DATE>=ssm.END_DATE OR  ssm.END_DATE IS NULL)) order by ssm.start_date desc limit 1
)  ) ', "'0'", 'ga.POINTS')) . ') AS PARTIAL_TOTAL,
                                gt.FINAL_GRADE_PERCENT FROM students s JOIN schedule ss ON (ss.COLLEGE_ROLL_NO=s.COLLEGE_ROLL_NO AND ss.COURSE_PERIOD_ID=\'' . $course_period_id . '\') JOIN gradebook_assignments ga ON ((ga.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID OR ga.COURSE_ID=\'' . $course_id . '\' AND ga.STAFF_ID=\'' . User('STAFF_ID') . '\') AND ga.MARKING_PERIOD_ID=\'' . UserMP() . '\') LEFT OUTER JOIN gradebook_grades gg ON (gg.COLLEGE_ROLL_NO=s.COLLEGE_ROLL_NO AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND gg.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID)
                                 
                                ,gradebook_assignment_types gt WHERE gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID AND gt.COURSE_ID=\'' . $course_id . '\' AND ((ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE) AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE) OR gg.POINTS IS NOT NULL) GROUP BY s.COLLEGE_ROLL_NO,ss.START_DATE,gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT'), array(), array('COLLEGE_ROLL_NO'));
                }
            } else {

                $course_periods = DBGet(DBQuery('select marking_period_id from course_periods where course_period_id=' . UserCoursePeriod()));
                if ($course_periods[1]['MARKING_PERIOD_ID'] == NULL) {
                    $college_years = DBGet(DBQuery('select marking_period_id from  college_years where  syear=' . UserSyear() . ' and college_id=' . UserCollege()));
                    $fy_mp_id = $college_years[1]['MARKING_PERIOD_ID'];

                    $points_RET = DBGet(DBQuery('SELECT DISTINCT  s.COLLEGE_ROLL_NO,\'-1\' AS ASSIGNMENT_TYPE_ID,sum(' . db_case(array('gg.POINTS', "'-1'", "'0'", 'gg.POINTS')) . ') AS PARTIAL_POINTS,
                        sum(' . db_case(array('gg.POINTS', '\'-1\' OR gg.POINTS IS NULL  OR (ga.due_date <  (select DISTINCT ssm.start_date  from student_enrollment ssm where ssm.COLLEGE_ROLL_NO=s.COLLEGE_ROLL_NO AND ssm.SYEAR=\'' . UserSyear() . '\' AND ssm.COLLEGE_ID=' . UserCollege() . ' AND (ssm.START_DATE IS NOT NULL AND (CURRENT_DATE<=ssm.END_DATE OR CURRENT_DATE>=ssm.END_DATE OR  ssm.END_DATE IS NULL)) order by ssm.start_date desc limit 1
)  ) ', "'0'", 'ga.POINTS')) . ') AS PARTIAL_TOTAL,
                               \'1\' AS FINAL_GRADE_PERCENT FROM students s JOIN schedule ss ON (ss.COLLEGE_ROLL_NO=s.COLLEGE_ROLL_NO AND ss.COURSE_PERIOD_ID=\'' . $course_period_id . '\') JOIN gradebook_assignments ga ON ((ga.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID OR ga.COURSE_ID=\'' . $course_id . '\' AND ga.STAFF_ID=\'' . User('STAFF_ID') . '\') AND (ga.MARKING_PERIOD_ID=\'' . UserMP() . '\' OR ga.MARKING_PERIOD_ID=\'' . $fy_mp_id . '\')) LEFT OUTER JOIN gradebook_grades gg ON (gg.COLLEGE_ROLL_NO=s.COLLEGE_ROLL_NO AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND gg.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID) 
                         
                                WHERE  ((ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE) AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE) OR gg.POINTS IS NOT NULL) GROUP BY s.COLLEGE_ROLL_NO,ss.START_DATE,FINAL_GRADE_PERCENT'), array(), array('COLLEGE_ROLL_NO'));
                } else {

                    $points_RET = DBGet(DBQuery('SELECT DISTINCT  s.COLLEGE_ROLL_NO,\'-1\' AS ASSIGNMENT_TYPE_ID,sum(' . db_case(array('gg.POINTS', "'-1'", "'0'", 'gg.POINTS')) . ') AS PARTIAL_POINTS,
                        sum(' . db_case(array('gg.POINTS', '\'-1\' OR gg.POINTS IS NULL  OR (ga.due_date <  (select DISTINCT ssm.start_date  from student_enrollment ssm where ssm.COLLEGE_ROLL_NO=s.COLLEGE_ROLL_NO AND ssm.SYEAR=\'' . UserSyear() . '\' AND ssm.COLLEGE_ID=' . UserCollege() . ' AND (ssm.START_DATE IS NOT NULL AND (CURRENT_DATE<=ssm.END_DATE OR CURRENT_DATE>=ssm.END_DATE OR  ssm.END_DATE IS NULL)) order by ssm.start_date desc limit 1
)  ) ', "'0'", 'ga.POINTS')) . ') AS PARTIAL_TOTAL,
                               \'1\' AS FINAL_GRADE_PERCENT FROM students s JOIN schedule ss ON (ss.COLLEGE_ROLL_NO=s.COLLEGE_ROLL_NO AND ss.COURSE_PERIOD_ID=\'' . $course_period_id . '\') JOIN gradebook_assignments ga ON ((ga.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID OR ga.COURSE_ID=\'' . $course_id . '\' AND ga.STAFF_ID=\'' . User('STAFF_ID') . '\') AND ga.MARKING_PERIOD_ID=\'' . UserMP() . '\') LEFT OUTER JOIN gradebook_grades gg ON (gg.COLLEGE_ROLL_NO=s.COLLEGE_ROLL_NO AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND gg.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID) 
                         
                                WHERE  ((ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE) AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE) OR gg.POINTS IS NOT NULL) GROUP BY s.COLLEGE_ROLL_NO,ss.START_DATE,FINAL_GRADE_PERCENT'), array(), array('COLLEGE_ROLL_NO'));
                }
            }

            if (count($points_RET)) {
                foreach ($points_RET as $college_roll_no => $student) {
                    ##########   Previous Calculation Start ##########
                    $total = $total_percent = 0;
                    $student_points = $total_points = $percent_weights = array();
                    $tot_weighted_percent = array();
                    $assignment_type_count = array();
                    unset($student_points);
                    unset($total_points);
                    unset($percent_weights);
                    if ($programconfig[User('STAFF_ID')]['WEIGHT'] == 'Y') {
                        $assign_typ_wg = array();
                        $tot_weight_grade = '';
                        $total_weightage='';
                        if ($course_periods[1]['MARKING_PERIOD_ID'] == NULL) {
                            $sql = 'SELECT a.TITLE,t.TITLE AS ASSIGN_TYP,a.ASSIGNED_DATE,a.DUE_DATE, t.ASSIGNMENT_TYPE_ID, t.FINAL_GRADE_PERCENT AS WEIGHT_GRADE  ,  t.FINAL_GRADE_PERCENT,t.FINAL_GRADE_PERCENT as ASSIGN_TYP_WG,g.POINTS,a.POINTS AS TOTAL_POINTS,g.COMMENT,g.POINTS AS LETTER_GRADE,g.POINTS AS LETTERWTD_GRADE,CASE WHEN (a.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=a.ASSIGNED_DATE) AND (a.DUE_DATE IS NULL OR CURRENT_DATE>=a.DUE_DATE) THEN \'Y\' ELSE NULL END AS DUE FROM gradebook_assignment_types t,gradebook_assignments a LEFT OUTER JOIN gradebook_grades g ON (a.ASSIGNMENT_ID=g.ASSIGNMENT_ID AND g.COLLEGE_ROLL_NO=\'' . $college_roll_no . '\' AND g.COURSE_PERIOD_ID=\'' . $course_period_id . '\') WHERE   a.ASSIGNMENT_TYPE_ID=t.ASSIGNMENT_TYPE_ID AND (a.COURSE_PERIOD_ID=\'' . $course_period_id . '\' OR a.COURSE_ID=\'' . $course_id . '\' AND a.STAFF_ID=\'' . User('STAFF_ID') . '\') AND t.COURSE_ID=\'' . $course_id . '\' AND (a.MARKING_PERIOD_ID=\'' . UserMP() . '\' OR a.MARKING_PERIOD_ID=\'' . $fy_mp_id . '\')';
                        } else {
                            $sql = 'SELECT a.TITLE,t.TITLE AS ASSIGN_TYP,a.ASSIGNED_DATE,a.DUE_DATE,  t.ASSIGNMENT_TYPE_ID,   t.FINAL_GRADE_PERCENT AS WEIGHT_GRADE  , t.FINAL_GRADE_PERCENT,t.FINAL_GRADE_PERCENT as ASSIGN_TYP_WG,g.POINTS,a.POINTS AS TOTAL_POINTS,g.COMMENT,g.POINTS AS LETTER_GRADE,g.POINTS AS LETTERWTD_GRADE,CASE WHEN (a.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=a.ASSIGNED_DATE) AND (a.DUE_DATE IS NULL OR CURRENT_DATE>=a.DUE_DATE) THEN \'Y\' ELSE NULL END AS DUE FROM gradebook_assignment_types t,gradebook_assignments a LEFT OUTER JOIN gradebook_grades g ON (a.ASSIGNMENT_ID=g.ASSIGNMENT_ID AND g.COLLEGE_ROLL_NO=\'' . $college_roll_no . '\' AND g.COURSE_PERIOD_ID=\'' . $course_period_id . '\') WHERE   a.ASSIGNMENT_TYPE_ID=t.ASSIGNMENT_TYPE_ID AND (a.COURSE_PERIOD_ID=\'' . $course_period_id . '\' OR a.COURSE_ID=\'' . $course_id . '\' AND a.STAFF_ID=\'' . User('STAFF_ID') . '\') AND t.COURSE_ID=\'' . $course_id . '\' AND a.MARKING_PERIOD_ID=\'' . UserMP() . '\'';
                        }
                        $sql .= ' AND (a.POINTS!=\'0\' OR g.POINTS IS NOT NULL AND g.POINTS!=\'-1\') AND a.DUE_DATE>=(select DISTINCT start_date  from student_enrollment where COLLEGE_ROLL_NO =\'' . $college_roll_no . '\' AND SYEAR=' . UserSyear() . ' AND COLLEGE_ID= ' . UserCollege() . ' ORDER BY START_DATE desc limit 1) ORDER BY a.ASSIGNMENT_ID';
                        $grades_RET1 = DBGet(DBQuery($sql), array('ASSIGN_TYP_WG' => '_makeAssnWG', 'POINTS' => '_makeExtra', 'LETTER_GRADE' => '_makeExtra'));

                        if (count($grades_RET1)) {

                            foreach ($grades_RET1 as $key => $val) {
                                if ($val['LETTERWTD_GRADE'] != -1.00 && $val['LETTERWTD_GRADE'] != '') {
                                    $wper = explode('%', $val['LETTER_GRADE']);
                                    if ($tot_weighted_percent[$val['ASSIGNMENT_TYPE_ID']] != '')
                                        $tot_weighted_percent[$val['ASSIGNMENT_TYPE_ID']] = $tot_weighted_percent[$val['ASSIGNMENT_TYPE_ID']] + $wper[0];
                                    else
                                        $tot_weighted_percent[$val['ASSIGNMENT_TYPE_ID']] = $wper[0];
                                    if ($assignment_type_count[$val['ASSIGNMENT_TYPE_ID']] != '')
                                        $assignment_type_count[$val['ASSIGNMENT_TYPE_ID']] = $assignment_type_count[$val['ASSIGNMENT_TYPE_ID']] + 1;
                                    else
                                        $assignment_type_count[$val['ASSIGNMENT_TYPE_ID']] = 1;
                                    if ($val['ASSIGN_TYP_WG'] != '')
                                        $assign_typ_wg[$val['ASSIGNMENT_TYPE_ID']] = substr($val['ASSIGN_TYP_WG'], 0, -2);
                                }
                            }
                            foreach ($assignment_type_count as $assign_key => $value) {
                                $total_weightage = $total_weightage + $assign_typ_wg[$assign_key];
                                
                                if ($tot_weight_grade == '')
                                    $tot_weight_grade = round((round(($tot_weighted_percent[$assign_key] / $value), 2) * $assign_typ_wg[$assign_key]) / 100, 2);
                                else
                                    $tot_weight_grade = $tot_weight_grade + (round((round(($tot_weighted_percent[$assign_key] / $value), 2) * $assign_typ_wg[$assign_key]) / 100, 2));
                            }


                            //------------------------------------------------------//

                            if (check_exam(UserMP()) == 'Y') {
                                $sql = 'select * from student_report_card_grades where college_roll_no=\'' . $college_roll_no . '\' and marking_period_id=\'E' . UserMP() . '\' and course_period_id=\'' . $course_period_id . '\'';
                                $qr_exam = DBGet(DBQuery($sql));
                                $grade_percent = $qr_exam[1]['GRADE_PERCENT'];
                            } else {
                                $grade_percent = 0;
                            }
                            $mp1 = UserMP();

                            if (check_exam(UserMP()) == 'Y') {
                                $mpex = '';
                                $mpex .= "E" . UserMP();
                            }

                            $prefix = 'Q-';


//echo $total;
                            $total1 = 0;
                            if ($programconfig[User('STAFF_ID')][$prefix . $mp1] != '') {

                                $total1 += (round($tot_weight_grade, 2)) * ($programconfig[User('STAFF_ID')][$prefix . $mp1] / 100);

//                        break;
                            }
//                    
                            if ($programconfig[User('STAFF_ID')][$prefix . $mpex] != '') {

                                $total1 += ($grade_percent * $programconfig[User('STAFF_ID')][$prefix . $mpex]) / 100;
                                $temp_flag = 1;

//                        break;
                            }
                            if ($total1 != 0) {
                                $tot_weight_grade = $total1;
                            }



                            //-------------------------------------------------------//




                            $import_RET[$college_roll_no] = array(1 => array('REPORT_CARD_GRADE_ID' => _makeLetterGrade($tot_weight_grade/$total_weightage, $course_period_id, 0, 'ID'), 'GRADE_PERCENT' => _makeLetterGrade($tot_weight_grade / $total_weightage, $course_period_id, User('STAFF_ID'), '%') . '%'));
                        } else {
                            foreach ($student as $partial_points)
                                if ($partial_points['PARTIAL_TOTAL'] != 0) {
                                    $total += $partial_points['PARTIAL_POINTS'] * $partial_points['FINAL_GRADE_PERCENT'] / $partial_points['PARTIAL_TOTAL'];
                                    $total_percent += $partial_points['FINAL_GRADE_PERCENT'];
                                }
                            if (check_exam(UserMP()) == 'Y') {
                                $sql = 'select * from student_report_card_grades where college_roll_no=\'' . $college_roll_no . '\' and marking_period_id=\'E' . UserMP() . '\' and course_period_id=\'' . $course_period_id . '\'';
                                $qr_exam = DBGet(DBQuery($sql));
                                $grade_percent = $qr_exam[1]['GRADE_PERCENT'];
                            } else {
                                $grade_percent = 0;
                            }
                            $mp1 = UserMP();

                            if (check_exam(UserMP()) == 'Y') {
                                $mpex = '';
                                $mpex .= "E" . UserMP();
                            }

                            $prefix = 'Q-';


//echo $total;
                            $total1 = 0;
                            if ($programconfig[User('STAFF_ID')][$prefix . $mp1] != '') {

                                $total1 += (round(100 * $total, 2)) * ($programconfig[User('STAFF_ID')][$prefix . $mp1] / 100);
                                $temp_flag = 1;
//                        break;
                            }
//                    
                            if ($programconfig[User('STAFF_ID')][$prefix . $mpex] != '') {

                                $total1 += ($grade_percent * $programconfig[User('STAFF_ID')][$prefix . $mpex]) / 100;


//                        break;
                            }
                            if ($total1 != 0) {
                                $total = $total1 / 100;
                            }
                            $import_RET[$college_roll_no] = array(1 => array('REPORT_CARD_GRADE_ID' => _makeLetterGrade($total, $course_period_id, 0, 'ID'), 'GRADE_PERCENT' => round(100 * $total, 2)));
                        }
                    } else {

                        foreach ($student as $partial_points)
                            if ($partial_points['PARTIAL_TOTAL'] != 0) {
                                $partial_points['PARTIAL_POINTS'] . '*' . $partial_points['FINAL_GRADE_PERCENT'] . ' /' . $partial_points['PARTIAL_TOTAL'];

                                $total += $partial_points['PARTIAL_POINTS'] * $partial_points['FINAL_GRADE_PERCENT'] / $partial_points['PARTIAL_TOTAL'];

                                $total_percent += $partial_points['FINAL_GRADE_PERCENT'];
                            }
                        if (check_exam(UserMP()) == 'Y') {
                            $sql = 'select * from student_report_card_grades where college_roll_no=\'' . $college_roll_no . '\' and marking_period_id=\'E' . UserMP() . '\' and course_period_id=\'' . $course_period_id . '\'';
                            $qr_exam = DBGet(DBQuery($sql));
                            $grade_percent = $qr_exam[1]['GRADE_PERCENT'];
                        } else {
                            $grade_percent = 0;
                        }
                        $mp1 = UserMP();

                        if (check_exam(UserMP()) == 'Y') {
                            $mpex = '';
                            $mpex .= "E" . UserMP();
                        }

                        $prefix = 'Q-';


//echo $total;
                        $total1 = 0;
                        if ($programconfig[User('STAFF_ID')][$prefix . $mp1] != '') {

                            $total1 += (round(100 * $total, 2)) * ($programconfig[User('STAFF_ID')][$prefix . $mp1] / 100);

//                        break;
                        }
//                    
                        if ($programconfig[User('STAFF_ID')][$prefix . $mpex] != '') {

                            $total1 += ($grade_percent * $programconfig[User('STAFF_ID')][$prefix . $mpex]) / 100;
                            $temp_flag = 1;

//                        break;
                        }
                        if ($total1 != 0) {
                            $total = $total1 / 100;
                        }


                        $import_RET[$college_roll_no] = array(1 => array('REPORT_CARD_GRADE_ID' => _makeLetterGrade($total, $course_period_id, 0, 'ID'), 'GRADE_PERCENT' => round(100 * $total, 2)));
                    }
                }
            }
        } elseif (GetMP($_REQUEST['mp'], 'TABLE') == 'college_semesters' || GetMP($_REQUEST['mp'], 'TABLE') == 'college_years') {
            if ($sem || (GetMP($_REQUEST['mp'], 'TABLE') == 'college_years') && $fy) {
                if (GetMP($_REQUEST['mp'], 'TABLE') == 'college_semesters') {
                    $RET = DBGet(DBQuery('SELECT MARKING_PERIOD_ID,\'Y\' AS DOES_GRADES,NULL AS DOES_EXAM FROM college_quarters WHERE SEMESTER_ID=\'' . $_REQUEST['mp'] . '\' UNION SELECT MARKING_PERIOD_ID,NULL AS DOES_GRADES,DOES_EXAM FROM college_semesters WHERE MARKING_PERIOD_ID=\'' . $_REQUEST[mp] . '\''));
                    $prefix = 'SEM-';
                } else {
                    $RET = DBGet(DBQuery('SELECT q.marking_period_id,\'Y\' AS DOES_GRADES,NULL AS DOES_EXAM FROM college_quarters q,college_semesters s WHERE q.SEMESTER_ID=s.MARKING_PERIOD_ID AND s.YEAR_ID=\'' . $_REQUEST['mp'] . '\' UNION SELECT MARKING_PERIOD_ID,DOES_GRADES,DOES_EXAM FROM college_semesters WHERE YEAR_ID=\'' . $_REQUEST['mp'] . '\' UNION SELECT MARKING_PERIOD_ID,NULL AS DOES_GRADES,DOES_EXAM FROM college_years WHERE MARKING_PERIOD_ID=\'' . $_REQUEST['mp'] . '\''));
                    $prefix = 'FY-';
                }

                foreach ($RET as $mp) {
                    if ($mp['DOES_GRADES'] == 'Y')
                        $mps .= "'$mp[MARKING_PERIOD_ID]',";
                    if ($mp['DOES_EXAM'] == 'Y')
                        $mps .= "'E$mp[MARKING_PERIOD_ID]',";
                }
                foreach ($RET as $mp) {
                    if ($mp['DOES_GRADES'] == 'Y')
                        $mps1 .= "$mp[MARKING_PERIOD_ID],";
                    if ($mp['DOES_EXAM'] == 'Y')
                        $mps1 .= "E$mp[MARKING_PERIOD_ID],";
                }
                $mps = substr($mps, 0, -1);
                $temp_mps = explode(',', $mps);
                $mps1 = substr($mps1, 0, -1);
                $temp_mps1 = explode(',', $mps1);
                $percents_RET = DBGet(DBQuery('SELECT COLLEGE_ROLL_NO,GRADE_PERCENT,MARKING_PERIOD_ID FROM student_report_card_grades WHERE COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND MARKING_PERIOD_ID IN (' . $mps . ')'), array(), array('COLLEGE_ROLL_NO'));
                $temp_flag = 0;

                foreach ($temp_mps1 as $mp1) {
                    if ($programconfig[User('STAFF_ID')][$prefix . $mp1] != '') {
                        $temp_flag = 1;
                        break;
                    }
                }
                foreach ($temp_mps1 as $mp1) {
                    if ($programconfig[User('STAFF_ID')][$prefix . $mp1] == '' && $temp_flag == 1) {
                        $programconfig[User('STAFF_ID')][$prefix . $mp1] = 0;
                    }
                }
                $temp_flag = 0;
                foreach ($percents_RET as $college_roll_no => $percents) {
                    $total = $total_percent = 0;
                    foreach ($percents as $percent) {
                        if ($programconfig[User('STAFF_ID')][$prefix . $percent['MARKING_PERIOD_ID']] >= 0) {
                            $total += $percent['GRADE_PERCENT'] * $programconfig[User('STAFF_ID')][$prefix . $percent['MARKING_PERIOD_ID']];
                            $total_percent += $programconfig[User('STAFF_ID')][$prefix . $percent['MARKING_PERIOD_ID']];
                        } else {

                            $total += $percent['GRADE_PERCENT'];
                            $temp_flag++;
                        }
                    }
                    if ($programconfig[User('STAFF_ID')][$prefix . $percent['MARKING_PERIOD_ID']] == '' && $temp_flag > 0)
                        $total_percent = count(explode(",", $mps));

                    $total /= 100;


                    if (check_exam(UserMP()) == 'Y') {
                        $sql = 'select * from student_report_card_grades where college_roll_no=\'' . $college_roll_no . '\' and marking_period_id=\'E' . UserMP() . '\' and course_period_id=\'' . $course_period_id . '\'';
                        $qr_exam = DBGet(DBQuery($sql));
                        $grade_percent = $qr_exam[1]['GRADE_PERCENT'];
                    } else {
                        $grade_percent = 0;
                    }
                    $mp1 = UserMP();

                    if (check_exam(UserMP()) == 'Y') {
                        $mpex = '';
                        $mpex .= "E" . UserMP();
                    }

                    if (GetMP($_REQUEST['mp'], 'TABLE') == 'college_semesters') {

                        $prefix = 'SEM-';
                    } else {
                        $prefix = 'FY-';
                    }


//echo $total;
                    $total1 = 0;
//  if($programconfig[User('STAFF_ID')][$prefix.$mp1]!='')
//                    {
//      
//      $total1 +=(round(100*$total,2))*($programconfig[User('STAFF_ID')][$prefix.$mp1]/100);
//                        
////                        break;
//                    }
//                    
                    if ($programconfig[User('STAFF_ID')][$prefix . $mpex] != '') {

                        $total1 += ($grade_percent * $programconfig[User('STAFF_ID')][$prefix . $mpex]) / 100;
                        $temp_flag = 1;

//                        break;
                    }
                    if ($total1 != 0) {
                        $total = $total1 / 100;
                    }
//                      

                    $import_RET[$college_roll_no] = array(1 => array('REPORT_CARD_GRADE_ID' => _makeLetterGrade($total / 100, $course_period_id, 0, 'ID'), 'GRADE_PERCENT' => round($total, 2)));
                }
            } else {

                if ($_REQUEST['custom_cp'] == 'y')
                    $gg_mp = $_REQUEST['mp'];
                else
                    $gg_mp = UserMP();

                if ($programconfig[User('STAFF_ID')]['WEIGHT'] == 'Y')
                    $points_RET = DBGet(DBQuery('SELECT DISTINCT s.COLLEGE_ROLL_NO,gt.ASSIGNMENT_TYPE_ID,  gt.ASSIGNMENT_TYPE_ID,sum(' . db_case(array('gg.POINTS', "'-1'", "'0'", 'gg.POINTS')) . ') AS PARTIAL_POINTS,sum(' . db_case(array('gg.POINTS', '\'-1\' OR (ga.due_date <  (select DISTINCT ssm.start_date  from student_enrollment ssm where ssm.COLLEGE_ROLL_NO=s.COLLEGE_ROLL_NO AND ssm.SYEAR=\'' . UserSyear() . '\' AND ssm.COLLEGE_ID=' . UserCollege() . ' AND (ssm.START_DATE IS NOT NULL AND (CURRENT_DATE<=ssm.END_DATE OR CURRENT_DATE>=ssm.END_DATE OR  ssm.END_DATE IS NULL)) order by ssm.start_date desc limit 1
)  ) ', '\'0\'', 'ga.POINTS')) . ') AS PARTIAL_TOTAL,    gt.FINAL_GRADE_PERCENT FROM students s JOIN schedule ss ON (ss.COLLEGE_ROLL_NO=s.COLLEGE_ROLL_NO AND ss.COURSE_PERIOD_ID=\'' . $course_period_id . '\') JOIN gradebook_assignments ga ON ((ga.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID OR ga.COURSE_ID=\'' . $course_id . '\' AND ga.STAFF_ID=\'' . User('STAFF_ID') . '\') AND ga.MARKING_PERIOD_ID=\'' . $gg_mp . '\') LEFT OUTER JOIN gradebook_grades gg ON (gg.COLLEGE_ROLL_NO=s.COLLEGE_ROLL_NO AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND gg.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID),gradebook_assignment_types gt WHERE gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND gt.ASSIGNMENT_TYPE_ID=ga.ASSIGNMENT_TYPE_ID AND gt.COURSE_ID=\'' . $course_id . '\' AND ((ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE) AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE) OR gg.POINTS IS NOT NULL) GROUP BY s.COLLEGE_ROLL_NO,ss.START_DATE,gt.ASSIGNMENT_TYPE_ID,gt.FINAL_GRADE_PERCENT'), array(), array('COLLEGE_ROLL_NO'));
                else
                    $points_RET = DBGet(DBQuery('SELECT DISTINCT  s.COLLEGE_ROLL_NO,\'-1\' AS ASSIGNMENT_TYPE_ID,sum(' . db_case(array('gg.POINTS', "'-1'", "'0'", 'gg.POINTS')) . ') AS PARTIAL_POINTS,sum(' . db_case(array('gg.POINTS', '\'-1\' OR (ga.due_date <  (select DISTINCT ssm.start_date  from student_enrollment ssm where ssm.COLLEGE_ROLL_NO=s.COLLEGE_ROLL_NO AND ssm.SYEAR=\'' . UserSyear() . '\' AND ssm.COLLEGE_ID=' . UserCollege() . ' AND (ssm.START_DATE IS NOT NULL AND (CURRENT_DATE<=ssm.END_DATE OR CURRENT_DATE>=ssm.END_DATE OR  ssm.END_DATE IS NULL)) order by ssm.start_date desc limit 1
)  ) ', '\'0\'', 'ga.POINTS')) . ') AS PARTIAL_TOTAL,\'1\' AS FINAL_GRADE_PERCENT FROM students s JOIN schedule ss ON (ss.COLLEGE_ROLL_NO=s.COLLEGE_ROLL_NO AND ss.COURSE_PERIOD_ID=\'' . $course_period_id . '\') JOIN gradebook_assignments ga ON ((ga.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID OR ga.COURSE_ID=\'' . $course_id . '\' AND ga.STAFF_ID=\'' . User('STAFF_ID') . '\') AND ga.MARKING_PERIOD_ID=\'' . $gg_mp . '\') LEFT OUTER JOIN gradebook_grades gg ON (gg.COLLEGE_ROLL_NO=s.COLLEGE_ROLL_NO AND gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND gg.COURSE_PERIOD_ID=ss.COURSE_PERIOD_ID)   WHERE gg.ASSIGNMENT_ID=ga.ASSIGNMENT_ID AND ((ga.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=ga.ASSIGNED_DATE) AND (ga.DUE_DATE IS NULL OR CURRENT_DATE>=ga.DUE_DATE) OR gg.POINTS IS NOT NULL) GROUP BY s.COLLEGE_ROLL_NO,ss.START_DATE,FINAL_GRADE_PERCENT'), array(), array('COLLEGE_ROLL_NO'));

                if (count($points_RET)) {
                    foreach ($points_RET as $college_roll_no => $student) {
                        $total = $total_percent = 0;
                        $student_points = $total_points = $percent_weights = array();
                        $tot_weighted_percent = array();
                        $assignment_type_count = array();
                        unset($student_points);
                        unset($total_points);
                        unset($percent_weights);
                        if ($programconfig[User('STAFF_ID')]['WEIGHT'] == 'Y') {
                            $assign_typ_wg = array();
                            $tot_weight_grade = '';
                            $course_periods = DBGet(DBQuery('select marking_period_id from course_periods where course_period_id=' . UserCoursePeriod()));
                            if ($course_periods[1]['MARKING_PERIOD_ID'] == NULL) {
                                $college_years = DBGet(DBQuery('select marking_period_id from  college_years where  syear=' . UserSyear() . ' and college_id=' . UserCollege()));
                                $fy_mp_id = $college_years[1]['MARKING_PERIOD_ID'];
                                $sql = 'SELECT a.TITLE,t.TITLE AS ASSIGN_TYP,a.ASSIGNED_DATE,a.DUE_DATE, t.ASSIGNMENT_TYPE_ID, t.FINAL_GRADE_PERCENT AS WEIGHT_GRADE  ,  t.FINAL_GRADE_PERCENT,t.FINAL_GRADE_PERCENT as ASSIGN_TYP_WG,g.POINTS,a.POINTS AS TOTAL_POINTS,g.COMMENT,g.POINTS AS LETTER_GRADE,g.POINTS AS LETTERWTD_GRADE,CASE WHEN (a.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=a.ASSIGNED_DATE) AND (a.DUE_DATE IS NULL OR CURRENT_DATE>=a.DUE_DATE) THEN \'Y\' ELSE NULL END AS DUE FROM gradebook_assignment_types t,gradebook_assignments a LEFT OUTER JOIN gradebook_grades g ON (a.ASSIGNMENT_ID=g.ASSIGNMENT_ID AND g.COLLEGE_ROLL_NO=\'' . $college_roll_no . '\' AND g.COURSE_PERIOD_ID=\'' . $course_period_id . '\') WHERE   a.ASSIGNMENT_TYPE_ID=t.ASSIGNMENT_TYPE_ID AND (a.COURSE_PERIOD_ID=\'' . $course_period_id . '\' OR a.COURSE_ID=\'' . $course_id . '\' AND a.STAFF_ID=\'' . User('STAFF_ID') . '\') AND t.COURSE_ID=\'' . $course_id . '\' AND (a.MARKING_PERIOD_ID=\'' . UserMP() . '\' OR a.MARKING_PERIOD_ID=\'' . $fy_mp_id . '\')';
                            } else {
                                $sql = 'SELECT a.TITLE,t.TITLE AS ASSIGN_TYP,a.ASSIGNED_DATE,a.DUE_DATE,  t.ASSIGNMENT_TYPE_ID,   t.FINAL_GRADE_PERCENT AS WEIGHT_GRADE  , t.FINAL_GRADE_PERCENT,t.FINAL_GRADE_PERCENT as ASSIGN_TYP_WG,g.POINTS,a.POINTS AS TOTAL_POINTS,g.COMMENT,g.POINTS AS LETTER_GRADE,g.POINTS AS LETTERWTD_GRADE,CASE WHEN (a.ASSIGNED_DATE IS NULL OR CURRENT_DATE>=a.ASSIGNED_DATE) AND (a.DUE_DATE IS NULL OR CURRENT_DATE>=a.DUE_DATE) THEN \'Y\' ELSE NULL END AS DUE FROM gradebook_assignment_types t,gradebook_assignments a LEFT OUTER JOIN gradebook_grades g ON (a.ASSIGNMENT_ID=g.ASSIGNMENT_ID AND g.COLLEGE_ROLL_NO=\'' . $college_roll_no . '\' AND g.COURSE_PERIOD_ID=\'' . $course_period_id . '\') WHERE   a.ASSIGNMENT_TYPE_ID=t.ASSIGNMENT_TYPE_ID AND (a.COURSE_PERIOD_ID=\'' . $course_period_id . '\' OR a.COURSE_ID=\'' . $course_id . '\' AND a.STAFF_ID=\'' . User('STAFF_ID') . '\') AND t.COURSE_ID=\'' . $course_id . '\' AND a.MARKING_PERIOD_ID=\'' . UserMP() . '\'';
                            }
                            $sql .= ' AND (a.POINTS!=\'0\' OR g.POINTS IS NOT NULL AND g.POINTS!=\'-1\') AND a.DUE_DATE>=(select DISTINCT start_date  from student_enrollment where COLLEGE_ROLL_NO =\'' . $college_roll_no . '\' AND SYEAR=' . UserSyear() . ' AND COLLEGE_ID= ' . UserCollege() . ' ORDER BY START_DATE desc limit 1) ORDER BY a.ASSIGNMENT_ID';

                            $grades_RET1 = DBGet(DBQuery($sql), array('ASSIGN_TYP_WG' => '_makeAssnWG', 'POINTS' => '_makeExtra', 'LETTER_GRADE' => '_makeExtra'));
                            if (count($grades_RET1)) {
                                foreach ($grades_RET1 as $key => $val) {
                                    if ($val['LETTERWTD_GRADE'] != -1.00 && $val['LETTERWTD_GRADE'] != '') {
                                        $wper = explode('%', $val['LETTER_GRADE']);
                                        if ($tot_weighted_percent[$val['ASSIGNMENT_TYPE_ID']] != '')
                                            $tot_weighted_percent[$val['ASSIGNMENT_TYPE_ID']] = $tot_weighted_percent[$val['ASSIGNMENT_TYPE_ID']] + $wper[0];
                                        else
                                            $tot_weighted_percent[$val['ASSIGNMENT_TYPE_ID']] = $wper[0];
                                        if ($assignment_type_count[$val['ASSIGNMENT_TYPE_ID']] != '')
                                            $assignment_type_count[$val['ASSIGNMENT_TYPE_ID']] = $assignment_type_count[$val['ASSIGNMENT_TYPE_ID']] + 1;
                                        else
                                            $assignment_type_count[$val['ASSIGNMENT_TYPE_ID']] = 1;
                                        if ($val['ASSIGN_TYP_WG'] != '')
                                            $assign_typ_wg[$val['ASSIGNMENT_TYPE_ID']] = substr($val['ASSIGN_TYP_WG'], 0, -2);
                                    }
                                }
                                foreach ($assignment_type_count as $assign_key => $value) {
                                    if ($tot_weight_grade == '')
                                        $tot_weight_grade = round((round(($tot_weighted_percent[$assign_key] / $value), 2) * $assign_typ_wg[$assign_key]) / 100, 2);
                                    else
                                        $tot_weight_grade = $tot_weight_grade + (round((round(($tot_weighted_percent[$assign_key] / $value), 2) * $assign_typ_wg[$assign_key]) / 100, 2));
                                }

                                //--------------------------------------------------------------//  


                                if (check_exam(UserMP()) == 'Y') {
                                    $sql = 'select * from student_report_card_grades where college_roll_no=\'' . $college_roll_no . '\' and marking_period_id=\'E' . UserMP() . '\' and course_period_id=\'' . $course_period_id . '\'';
                                    $qr_exam = DBGet(DBQuery($sql));
                                    $grade_percent = $qr_exam[1]['GRADE_PERCENT'];
                                } else {
                                    $grade_percent = 0;
                                }
                                $mp1 = UserMP();

                                if (check_exam(UserMP()) == 'Y') {
                                    $mpex = '';
                                    $mpex .= "E" . UserMP();
                                }

                                if (GetMP($_REQUEST['mp'], 'TABLE') == 'college_semesters') {

                                    $prefix = 'SEM-';
                                } else {
                                    $prefix = 'FY-';
                                }


//echo $total;
                                $total1 = 0;
                                if ($programconfig[User('STAFF_ID')][$prefix . $mp1] != '') {

                                    $total1 += (round($tot_weight_grade, 2)) * ($programconfig[User('STAFF_ID')][$prefix . $mp1] / 100);

//                        break;
                                }
//                    
                                if ($programconfig[User('STAFF_ID')][$prefix . $mpex] != '') {

                                    $total1 += ($grade_percent * $programconfig[User('STAFF_ID')][$prefix . $mpex]) / 100;
                                    $temp_flag = 1;

//                        break;
                                }
                                if ($total1 != 0) {
                                    $tot_weight_grade = $total1;
                                }


//-----------------------------------------------------------------//



                                $import_RET[$college_roll_no] = array(1 => array('REPORT_CARD_GRADE_ID' => _makeLetterGrade($tot_weight_grade, $course_period_id, 0, 'ID'), 'GRADE_PERCENT' => _makeLetterGrade($tot_weight_grade / 100, $course_period_id, User('STAFF_ID'), '%') . '%'));
                            } else {
                                foreach ($student as $partial_points)
                                    if ($partial_points['PARTIAL_TOTAL'] != 0) {
                                        $total += $partial_points['PARTIAL_POINTS'] * $partial_points['FINAL_GRADE_PERCENT'] / $partial_points['PARTIAL_TOTAL'];
                                        $total_percent += $partial_points['FINAL_GRADE_PERCENT'];
                                    }


                                if (check_exam(UserMP()) == 'Y') {
                                    $sql = 'select * from student_report_card_grades where college_roll_no=\'' . $college_roll_no . '\' and marking_period_id=\'E' . UserMP() . '\' and course_period_id=\'' . $course_period_id . '\'';
                                    $qr_exam = DBGet(DBQuery($sql));
                                    $grade_percent = $qr_exam[1]['GRADE_PERCENT'];
                                } else {
                                    $grade_percent = 0;
                                }
                                $mp1 = UserMP();

                                if (check_exam(UserMP()) == 'Y') {
                                    $mpex = '';
                                    $mpex .= "E" . UserMP();
                                }

                                if (GetMP($_REQUEST['mp'], 'TABLE') == 'college_semesters') {

                                    $prefix = 'SEM-';
                                } else {
                                    $prefix = 'FY-';
                                }


//echo $total;
                                $total1 = 0;
                                if ($programconfig[User('STAFF_ID')][$prefix . $mp1] != '') {

                                    $total1 += (round(100 * $total, 2)) * ($programconfig[User('STAFF_ID')][$prefix . $mp1] / 100);

//                        break;
                                }
//                    
                                if ($programconfig[User('STAFF_ID')][$prefix . $mpex] != '') {

                                    $total1 += ($grade_percent * $programconfig[User('STAFF_ID')][$prefix . $mpex]) / 100;
                                    $temp_flag = 1;

//                        break;
                                }
                                if ($total1 != 0) {
                                    $total = $total1 / 100;
                                }


                                $import_RET[$college_roll_no] = array(1 => array('REPORT_CARD_GRADE_ID' => _makeLetterGrade($total, $course_period_id, 0, 'ID'), 'GRADE_PERCENT' => round(100 * $total, 2)));
                            }
                        } else {


                            foreach ($student as $partial_points)
                                if ($partial_points['PARTIAL_TOTAL'] != 0) {
                                    $total += $partial_points['PARTIAL_POINTS'] * $partial_points['FINAL_GRADE_PERCENT'] / $partial_points['PARTIAL_TOTAL'];
                                    $total_percent += $partial_points['FINAL_GRADE_PERCENT'];
                                }
                            ///-----------------------------------------------------///          

                            if (check_exam(UserMP()) == 'Y') {
                                $sql = 'select * from student_report_card_grades where college_roll_no=\'' . $college_roll_no . '\' and marking_period_id=\'E' . UserMP() . '\' and course_period_id=\'' . $course_period_id . '\'';
                                $qr_exam = DBGet(DBQuery($sql));
                                $grade_percent = $qr_exam[1]['GRADE_PERCENT'];
                            } else {
                                $grade_percent = 0;
                            }
                            $mp1 = UserMP();

                            if (check_exam(UserMP()) == 'Y') {
                                $mpex = '';
                                $mpex .= "E" . UserMP();
                            }

                            if (GetMP($_REQUEST['mp'], 'TABLE') == 'college_semesters') {

                                $prefix = 'SEM-';
                            } else {
                                $prefix = 'FY-';
                            }


//echo $total;
                            $total1 = 0;
                            if ($programconfig[User('STAFF_ID')][$prefix . $mp1] != '') {

                                $total1 += (round(100 * $total, 2)) * ($programconfig[User('STAFF_ID')][$prefix . $mp1] / 100);

//                        break;
                            }
//                    
                            if ($programconfig[User('STAFF_ID')][$prefix . $mpex] != '') {

                                $total1 += ($grade_percent * $programconfig[User('STAFF_ID')][$prefix . $mpex]) / 100;
                                $temp_flag = 1;

//                        break;
                            }
                            if ($total1 != 0) {
                                $total = $total1 / 100;
                            }
                            //---------------------------------------------------//           



                            $import_RET[$college_roll_no] = array(1 => array('REPORT_CARD_GRADE_ID' => _makeLetterGrade($total, $course_period_id, 0, 'ID'), 'GRADE_PERCENT' => round(100 * $total, 2)));
                        }
                    }
                }
            }
        }
    }
    unset($_SESSION['_REQUEST_vars']['modfunc']);
}

if (clean_param($_REQUEST['modfunc'], PARAM_ALPHAMOD) == 'grades') {
    if ($_REQUEST['prev_mp']) {
        include 'MakePercentGradeFnc.php';

        $import_RET = DBGet(DBQuery('SELECT g.COLLEGE_ROLL_NO,g.REPORT_CARD_GRADE_ID,g.GRADE_PERCENT FROM student_report_card_grades g,course_periods cp WHERE cp.COURSE_PERIOD_ID=g.COURSE_PERIOD_ID AND cp.COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND g.MARKING_PERIOD_ID=\'' . $_REQUEST['prev_mp'] . '\''), array(), array('COLLEGE_ROLL_NO'));

        foreach ($import_RET as $college_roll_no => $grade) {
            $import_RET[$college_roll_no][1]['GRADE_PERCENT'] = _makeLetterGrade($import_RET[$college_roll_no][1]['GRADE_PERCENT'] / 100, "", User('STAFF_ID'), "%");
        }
        unset($_SESSION['_REQUEST_vars']['prev_mp']);
    }
    unset($_SESSION['_REQUEST_vars']['modfunc']);
}

if (clean_param($_REQUEST['modfunc'], PARAM_ALPHAMOD) == 'comments') {
    if ($_REQUEST['prev_mp']) {
        $import_comments_RET = DBGet(DBQuery('SELECT g.COLLEGE_ROLL_NO,g.REPORT_CARD_COMMENT_ID,g.COMMENT FROM student_report_card_grades g WHERE g.COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND g.MARKING_PERIOD_ID=\'' . $_REQUEST['prev_mp'] . '\''), array(), array('COLLEGE_ROLL_NO'));
        $import_commentsA_RET = DBGet(DBQuery('SELECT g.COLLEGE_ROLL_NO,g.REPORT_CARD_COMMENT_ID,g.COMMENT FROM student_report_card_comments g WHERE g.COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND g.MARKING_PERIOD_ID=\'' . $_REQUEST['prev_mp'] . '\' AND g.REPORT_CARD_COMMENT_ID IN (SELECT ID FROM report_card_comments WHERE COURSE_ID IS NOT NULL)'), array(), array('COLLEGE_ROLL_NO', 'REPORT_CARD_COMMENT_ID'));
        $import_commentsB_RET = DBGet(DBQuery('SELECT g.COLLEGE_ROLL_NO,g.REPORT_CARD_COMMENT_ID FROM student_report_card_comments g WHERE g.COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND g.MARKING_PERIOD_ID=\'' . $_REQUEST['prev_mp'] . '\' AND g.REPORT_CARD_COMMENT_ID IN (SELECT ID FROM report_card_comments WHERE COURSE_ID IS NULL)'), array(), array('COLLEGE_ROLL_NO'));

        foreach ($import_commentsB_RET as $comments)
            if (count($comments) > $max_current_commentsB)
                $max_current_commentsB = count($comments);

        unset($_SESSION['_REQUEST_vars']['prev_mp']);
    }
    unset($_SESSION['_REQUEST_vars']['modfunc']);
}

if (clean_param($_REQUEST['modfunc'], PARAM_ALPHAMOD) == 'clearall') {
    foreach ($current_RET as $college_roll_no => $prev) {
        $current_RET[$college_roll_no][1]['REPORT_CARD_GRADE_ID'] = '';
        $current_RET[$college_roll_no][1]['GRADE_PERCENT'] = '';
        $current_RET[$college_roll_no][1]['COMMENT'] = '';
    }
    foreach ($current_commentsA_RET as $college_roll_no => $comments)
        foreach ($comments as $id => $comment)
            $current_commentsA_RET[$college_roll_no][$id][1]['COMMENT'] = '';
    foreach ($current_commentsB_RET as $college_roll_no => $comment)
        foreach ($comment as $i => $comment)
            $current_commentsB_RET[$college_roll_no][$i] = '';
    unset($_SESSION['_REQUEST_vars']['modfunc']);
}

if (clean_param($_REQUEST['values'], PARAM_NOTAGS) && ($_POST['values'] || $_REQUEST['ajax']) && $_REQUEST['submit']['save']) {
    include 'MakeLetterGradeFnc.php';
    include 'MakePercentGradeFnc';
    $completed = true;

    foreach ($_REQUEST['values'] as $college_roll_no => $columns) {
        $sql = $sep = '';
        if ($current_RET[$college_roll_no]) {
            if (isset($columns['grade']) && $columns['grade'] != $current_RET[$college_roll_no][1]['REPORT_CARD_GRADE_ID']) {
                if (substr($columns['grade'], -1) == '%') {
                    $percent = substr($columns['grade'], 0, -1);
                    $sql .= 'REPORT_CARD_GRADE_ID=\'' . _makeLetterGrade($percent / 100, $course_period_id, 0, 'ID') . '\'';
                    $sql .= ',GRADE_PERCENT=\'' . $percent . '\'';
                    $sep = ',';
                } elseif ($columns['grade'] != $current_RET[$college_roll_no][1]['REPORT_CARD_GRADE_ID']) {
                    $sql .= 'REPORT_CARD_GRADE_ID=\'' . $columns['grade'] . '\'';

                    $sql .= ',GRADE_PERCENT=\'' . ($columns['grade'] == '' ? '' : _makePercentGrade($columns['grade'], $course_period_id)) . '\'';

                    $sep = ',';
                }
                //bjj can we use $percent all the time?  TODO: rework this so updates to credits occur when grade is changed
                $grade_title_RET = DBGet(DBQuery('SELECT TITLE FROM report_card_grades WHERE ID=\'' . str_replace("\'", "''", $columns['grade']) . '\''));
                $sql .= ',GRADE_LETTER=\'' . $grade_title_RET[1]['TITLE'] . '\'';
                if ($course_RET[1]['COURSE_WEIGHT']) {
                    $sql .= ',WEIGHTED_GP=\'' . $grades_RET[$columns['grade']][1]['WEIGHTED_GP'] . '\'';
                    $sql .= ',CREDIT_EARNED=\'' . ($grades_RET[$columns['grade']][1]['WEIGHTED_GP'] > 0 ? $course_RET[1]['CREDITS'] : 0) . '\'';
                } else {
                    $sql .= ',UNWEIGHTED_GP=\'' . $grades_RET[$columns['grade']][1]['UNWEIGHTED_GP'] . '\'';
                    $sql .= ',CREDIT_EARNED=\'' . ($grades_RET[$columns['grade']][1]['UNWEIGHTED_GP'] > 0 ? $course_RET[1]['CREDITS'] : 0) . '\'';
                }
                $sql .= ',COURSE_TITLE=\'' . $course_RET[1]['COURSE_NAME'] . '\'';
                $sql .= ',GP_SCALE=\'' . $grades_RET[$columns['grade']][1]['GP_SCALE'] . '\'';
                $sql .= ',CREDIT_ATTEMPTED=\'' . $course_RET[1]['CREDITS'] . '\'';
            } elseif (isset($columns['percent']) && $columns['percent'] != $current_RET[$college_roll_no][1]['GRADE_PERCENT']) {
                if ($columns['percent'] == '') {
                    $sql .= 'REPORT_CARD_GRADE_ID=\'\'';
                    $gp_id = "";
                } else {
                    $percent = rtrim($columns['percent'], '%') + 0;
                    if ($percent > 999.9)
                        $percent = 999.9;
                    elseif ($percent < 0)
                        $percent = 0;
                    $gp_id = _makeLetterGrade($percent / 100, $course_period_id, 0, 'ID');
                    $sql .= 'REPORT_CARD_GRADE_ID=\'' . $gp_id . '\'';
                    if ($gp_id)
                        $grade_title_RET = DBGet(DBQuery("SELECT TITLE FROM report_card_grades WHERE ID=$gp_id"));
                    $sql .= ',GRADE_LETTER=\'' . $grade_title_RET[1]['TITLE'] . '\'';


                    if ($course_RET[1]['GRADE_SCALE_ID'] != '') {
                        if ($course_RET[1]['COURSE_WEIGHT']) {
                            $sql .= ',WEIGHTED_GP=\'' . $grades_RET[$gp_id][1]['WEIGHTED_GP'] . '\'';
                            $sql .= ',CREDIT_EARNED=\'' . ($grades_RET[$gp_id][1]['WEIGHTED_GP'] > 0 ? $course_RET[1]['CREDITS'] : 0) . '\'';
                        } else {
                            $sql .= ',UNWEIGHTED_GP=\'' . $grades_RET[$gp_id][1]['UNWEIGHTED_GP'] . '\'';
                            $sql .= ',CREDIT_EARNED=\'' . ($grades_RET[$gp_id][1]['UNWEIGHTED_GP'] > 0 ? $course_RET[1]['CREDITS'] : 0) . '\'';
                        }
                    } else {
                        if ($percent > 0)
                            $sql .= ',CREDIT_EARNED=\'' . $course_RET[1]['CREDITS'] . '\'';
                        else
                            $sql .= ',CREDIT_EARNED=\'0\'';
                    }
                    $sql .= ',COURSE_TITLE=\'' . $course_RET[1]['COURSE_NAME'] . '\'';
                    $sql .= ',GP_SCALE=\'' . $grades_RET[$gp_id][1]['GP_SCALE'] . '\'';
                    $sql .= ',CREDIT_ATTEMPTED=\'' . $course_RET[1]['CREDITS'] . '\'';
                }

                $sql .= ',GRADE_PERCENT=\'' . $percent . '\'';
                $sep = ',';
            }

            if (isset($columns['comment'])) {
                $columns['comment'] = clean_param($columns['comment'], PARAM_NOTAGS);
                $sql .= $sep;
                if (stripos($_SERVER['SERVER_SOFTWARE'], 'linux')) {
                    $columns['comment'] = mysql_real_escape_string($columns['comment']);
                }
                $sql .= 'COMMENT=\'' . singleQuoteReplace('', '', $columns['comment']) . '\' ';
            }


            if ($sql)
                $sql = 'UPDATE student_report_card_grades SET ' . $sql . ' WHERE COLLEGE_ROLL_NO=\'' . $college_roll_no . '\' AND COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND MARKING_PERIOD_ID=\'' . $_REQUEST['mp'] . '\'';
        }
        elseif ($columns['grade'] || $columns['percent'] != '' || $columns['comment']) {
            $grade = $percent = '';
            if ($columns['grade'])
                if (substr($columns['grade'], -1) == '%') {
                    $percent = substr($columns['grade'], 0, -1);
                    $grade = _makeLetterGrade($percent / 100, $course_period_id, 0, 'ID');
                } else {
                    $grade = $columns['grade'];
                    $percent = _makePercentGrade($grade, $course_period_id);
                } elseif ($columns['percent'] != '') {
                $percent = rtrim($columns['percent'], '%') + 0;
                if ($percent > 999.9)
                    $percent = 999.9;
                elseif ($percent < 0)
                    $percent = 0;
                $grade = _makeLetterGrade($percent / 100, $course_period_id, 0, 'ID');
            }

            $id = DBGet(DBQuery("SHOW TABLE STATUS LIKE 'student_report_card_grades'"));
            $grade_id[1]['ID'] = $id[1]['AUTO_INCREMENT'];
            $ngrade_id = $grade_id[1]['ID'];

            if (stripos($_SERVER['SERVER_SOFTWARE'], 'linux')) {
                $columns['comment'] = mysqli_real_escape_string($columns['comment']);
            }
            if ($course_RET[1]['GRADE_SCALE_ID'] != '') {
                if ($course_RET[1]['COURSE_WEIGHT']) {
                    $WEIGHTED_GP = $grades_RET[$grade][1]['WEIGHTED_GP'];
                    $CREDIT_EARNED = $grades_RET[$grade][1]['WEIGHTED_GP'] > 0 ? $course_RET[1]['CREDITS'] : 0;
                } else {
                    $UNWEIGHTED_GP = $grades_RET[$grade][1]['UNWEIGHTED_GP'];
                    $CREDIT_EARNED = $grades_RET[$grade][1]['UNWEIGHTED_GP'] > 0 ? $course_RET[1]['CREDITS'] : 0;
                }
            } else {
                if ($percent > 0)
                    $CREDIT_EARNED = $course_RET[1]['CREDITS'];
                else
                    $CREDIT_EARNED = 0;
            }
            $sql = 'INSERT INTO student_report_card_grades (SYEAR,COLLEGE_ID,COLLEGE_ROLL_NO,COURSE_PERIOD_ID,MARKING_PERIOD_ID,REPORT_CARD_GRADE_ID,GRADE_PERCENT,
                    COMMENT,GRADE_LETTER,WEIGHTED_GP,UNWEIGHTED_GP,COURSE_TITLE,GP_SCALE,CREDIT_ATTEMPTED,CREDIT_EARNED,CREDIT_CATEGORY)
					values(\'' . UserSyear() . '\',\'' . UserCollege() . '\',\'' . $college_roll_no . '\',\'' . $course_period_id . '\',\'' . $_REQUEST['mp'] . '\',\'' . $grade . '\',\'' . $percent . '\',
                    \'' . singleQuoteReplace('', '', clean_param($columns['comment'], PARAM_NOTAGS)) . '\',\'' . $grades_RET[$grade][1]['TITLE'] . '\',\'' . $WEIGHTED_GP . '\',\'' . $UNWEIGHTED_GP . '\',\'' . $course_RET[1]['COURSE_NAME'] . '\',\'' . $grades_RET[$grade][1]['GP_SCALE'] . '\',\'' . $course_RET[1]['CREDITS'] . '\',\'' . $CREDIT_EARNED . '\',\'' . $gr_crct . '\')';
        }

        if ($sql) {
            DBQuery($sql);
        }

        if ($_REQUEST['mp'] != '') {

            $arr_gr = array();
            $grade_student_arr = array();
            $sql = 'select ssm.college_roll_no,sgc.gpa,ssm.grade_id,sgc.gpa from student_enrollment ssm,student_gpa_calculated sgc where ssm.college_roll_no=sgc.college_roll_no and ssm.syear=' . UserSyear() . ' and ssm.college_id=' . UserCollege() . ' and marking_period_id=\'' . $_REQUEST['mp'] . '\' and gpa IS NOT NULL';
            $qr = DBGet(DBQuery($sql));

            foreach ($qr as $qr1) {
                $arr_gr[$qr1['GRADE_ID']][$qr1['COLLEGE_ROLL_NO']] = $qr1['GPA'];
                $grade_student_arr[$qr1['GRADE_ID']][$qr1['GPA']][] = $qr1['COLLEGE_ROLL_NO'];
            }
            //print_r($grade_student_arr);
            //echo "aita grade wise student---"."<br>";
            foreach ($arr_gr as $key1 => $arr_gr11) {
                //echo $key1;//grade id;
                $arr_gr11 = array_unique($arr_gr11);
////                                echo 
                rsort($arr_gr11);
//                              print_r($arr_gr11);
                $new_rank = 1;
                foreach ($arr_gr11 as $key => $class_rank) {
                    $college_roll_no1 = implode(',', $grade_student_arr[$key1][$class_rank]);

                    $sql_up = 'UPDATE  student_gpa_calculated SET class_rank=' . $new_rank . ' where marking_period_id=\'' . $_REQUEST['mp'] . '\' and college_roll_no in(' . $college_roll_no1 . ')';

                    DBQuery($sql_up);
                    //$new_val=$class_rank;
                    $new_rank = $new_rank + 1;
                }
            }
        }

        if (isset($columns['grade'])) {
            if (!$columns['grade'])
                $completed = false;
        }


        elseif (isset($columns['percent'])) {
            if (!$columns['percent'])
                $completed = false;
        }

        foreach ($columns['commentsA'] as $id => $comment)
            if ($current_commentsA_RET[$college_roll_no][$id])
                if ($comment)
                    DBQuery('UPDATE student_report_card_comments SET COMMENT=\'' . str_replace("\'", "''", clean_param($comment, PARAM_NOTAGS)) . '\' WHERE COLLEGE_ROLL_NO=\'' . $college_roll_no . '\' AND COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND MARKING_PERIOD_ID=\'' . $_REQUEST['mp'] . '\' AND REPORT_CARD_COMMENT_ID=\'' . $id . '\'');
                else
                    DBQuery('DELETE FROM student_report_card_comments WHERE COLLEGE_ROLL_NO=\'' . $college_roll_no . '\' AND COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND MARKING_PERIOD_ID=\'' . $_REQUEST['mp'] . '\' AND REPORT_CARD_COMMENT_ID=\'' . $id . '\'');
            elseif ($comment)
                DBQuery('INSERT INTO student_report_card_comments (SYEAR,COLLEGE_ID,COLLEGE_ROLL_NO,COURSE_PERIOD_ID,MARKING_PERIOD_ID,REPORT_CARD_COMMENT_ID,COMMENT)
						values(\'' . UserSyear() . '\',\'' . UserCollege() . '\',\'' . $college_roll_no . '\',\'' . $course_period_id . '\',\'' . $_REQUEST['mp'] . '\',\'' . $id . '\',\'' . clean_param($comment, PARAM_NOTAGS) . '\')');

        // create mapping for current
        $old = array();
        foreach ($current_commentsB_RET[$college_roll_no] as $i => $comment)
            $old[$comment['REPORT_CARD_COMMENT_ID']] = $i;
        // create change list
        $change = array();
        foreach ($columns['commentsB'] as $i => $comment)
            $change[$i] = array('REPORT_CARD_COMMENT_ID' => 0);
        // prune changes already in current set and reserve if in change list
        foreach ($columns['commentsB'] as $i => $comment)
            if ($comment)
                if ($old[$comment]) {
                    if ($change[$old[$comment]])
                        $change[$old[$comment]]['REPORT_CARD_COMMENT_ID'] = $comment;
                    $columns['commentsB'][$i] = false;
                }
        // assign changes at their index if possible
        $new = array();
        foreach ($columns['commentsB'] as $i => $comment)
            if ($comment)
                if (!$new[$comment]) {
                    if (!$change[$i]['REPORT_CARD_COMMENT_ID']) {
                        $change[$i]['REPORT_CARD_COMMENT_ID'] = $comment;
                        $new[$comment] = $i;
                        $columns['commentsB'][$i] = false;
                    }
                } else
                    $columns['commentsB'][$i] = false;
        // assign remaining changes to first available
        reset($change);
        foreach ($columns['commentsB'] as $i => $comment)
            if ($comment) {
                if (!$new[$comment]) {
                    while ($change[key($change)]['REPORT_CARD_COMMENT_ID'])
                        next($change);
                    $change[key($change)]['REPORT_CARD_COMMENT_ID'] = $comment;
                    $new[$comment] = key($change);
                }
                $columns['commentsB'][$i] = false;
            }

        // update the db
        foreach ($change as $i => $comment)
            if ($current_commentsB_RET[$college_roll_no][$i])
                if ($comment['REPORT_CARD_COMMENT_ID']) {
                    if ($comment['REPORT_CARD_COMMENT_ID'] != $current_commentsB_RET[$college_roll_no][$i]['REPORT_CARD_COMMENT_ID'])
                        DBQuery('UPDATE student_report_card_comments SET REPORT_CARD_COMMENT_ID=\'' . $comment['REPORT_CARD_COMMENT_ID'] . '\' WHERE COLLEGE_ROLL_NO=\'' . $college_roll_no . '\' AND COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND MARKING_PERIOD_ID=\'' . $_REQUEST['mp'] . '\' AND REPORT_CARD_COMMENT_ID=\'' . $current_commentsB_RET[$college_roll_no][$i]['REPORT_CARD_COMMENT_ID'] . '\'');
                } else
                    DBQuery('DELETE FROM student_report_card_comments WHERE COLLEGE_ROLL_NO=\'' . $college_roll_no . '\' AND COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND MARKING_PERIOD_ID=\'' . $_REQUEST['mp'] . '\' AND REPORT_CARD_COMMENT_ID=\'' . $current_commentsB_RET[$college_roll_no][$i]['REPORT_CARD_COMMENT_ID'] . '\'');
            else
            if ($comment['REPORT_CARD_COMMENT_ID'])
                DBQuery('INSERT INTO student_report_card_comments (SYEAR,COLLEGE_ID,COLLEGE_ROLL_NO,COURSE_PERIOD_ID,MARKING_PERIOD_ID,REPORT_CARD_COMMENT_ID)
						values(\'' . UserSyear() . '\',\'' . UserCollege() . '\',\'' . $college_roll_no . '\',\'' . $course_period_id . '\',\'' . $_REQUEST['mp'] . '\',\'' . $comment['REPORT_CARD_COMMENT_ID'] . '\')');
    }

    if ($completed) {
        if (!$current_completed) {

            $per_all = DBGet(DBQuery('select distinct(period_id) as period_id from course_period_var where course_period_id=\'' . $course_period_id . '\''));
            foreach ($per_all as $val) {
                DBQuery('delete from grades_completed where STAFF_ID=\'' . User('STAFF_ID') . '\' and  MARKING_PERIOD_ID=\'' . $_REQUEST['mp'] . '\' and PERIOD_ID=\'' . $val['PERIOD_ID'] . '\'');
                DBQuery('INSERT INTO grades_completed (STAFF_ID,MARKING_PERIOD_ID,PERIOD_ID) values(\'' . User('STAFF_ID') . '\',\'' . $_REQUEST['mp'] . '\',\'' . $val['PERIOD_ID'] . '\')');
            }
        }
    } else
    if ($current_completed) {
        $per_all = DBGet(DBQuery('select distinct(period_id) as period_id from course_period_var where course_period_id=\'' . $course_period_id . '\''));
        foreach ($per_all as $val) {

            DBQuery('DELETE FROM grades_completed WHERE STAFF_ID=\'' . User('STAFF_ID') . '\' AND MARKING_PERIOD_ID=\'' . $_REQUEST['mp'] . '\' AND PERIOD_ID=\'' . $val['PERIOD_ID'] . '\'');
        }
    }

    $current_RET = DBGet(DBQuery('SELECT g.COLLEGE_ROLL_NO,g.REPORT_CARD_GRADE_ID,g.GRADE_PERCENT,g.REPORT_CARD_COMMENT_ID,g.COMMENT FROM student_report_card_grades g WHERE g.COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND g.MARKING_PERIOD_ID=\'' . $_REQUEST['mp'] . '\''), array(), array('COLLEGE_ROLL_NO'));

    $current_commentsA_RET = DBGet(DBQuery('SELECT g.COLLEGE_ROLL_NO,g.REPORT_CARD_COMMENT_ID,g.COMMENT FROM student_report_card_comments g WHERE g.COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND g.MARKING_PERIOD_ID=\'' . $_REQUEST['mp'] . '\' AND g.REPORT_CARD_COMMENT_ID IN (SELECT ID FROM report_card_comments WHERE COURSE_ID IS NOT NULL)'), array(), array('COLLEGE_ROLL_NO', 'REPORT_CARD_COMMENT_ID'));
    $current_commentsB_RET = DBGet(DBQuery('SELECT g.COLLEGE_ROLL_NO,g.REPORT_CARD_COMMENT_ID FROM student_report_card_comments g WHERE g.COURSE_PERIOD_ID=\'' . $course_period_id . '\' AND g.MARKING_PERIOD_ID=\'' . $_REQUEST['mp'] . '\' AND g.REPORT_CARD_COMMENT_ID IN (SELECT ID FROM report_card_comments WHERE COURSE_ID IS NULL)'), array(), array('COLLEGE_ROLL_NO'));
    $max_current_commentsB = 0;
    foreach ($current_commentsB_RET as $comments)
        if (count($comments) > $max_current_commentsB)
            $max_current_commentsB = count($comments);
    $current_completed = count(DBGet(DBQuery('SELECT \'\' FROM grades_completed WHERE STAFF_ID=\'' . User('STAFF_ID') . '\' AND MARKING_PERIOD_ID=\'' . $_REQUEST['mp'] . '\' AND PERIOD_ID=\'' . UserPeriod() . '\'')));
    unset($_SESSION['_REQUEST_vars']['values']);
}

if (clean_param($_REQUEST['values'], PARAM_NOTAGS) && ($_POST['values'] || $_REQUEST['ajax']) && $_REQUEST['submit']['cancel']) {
    unset($_SESSION['_REQUEST_vars']['values']);
}
if (CpvId() != '') {
    $cp_det = DBGet(DBQuery('SELECT cp.BEGIN_DATE,cp.END_DATE,cp.MARKING_PERIOD_ID FROM course_periods cp,course_period_var cpv WHERE cpv.COURSE_PERIOD_ID=cp.COURSE_PERIOD_ID AND cpv.ID=' . CpvId()));
    if ($cp_det[1]['MARKING_PERIOD_ID'] == '') {
        $cp_type = 'custom';

        $grade_start_date[1]['POST_START_DATE'] = date('Y-m-d', strtotime($cp_det[1]['BEGIN_DATE']));
        //$grade_end_date=DBGet(DBQuery('SELECT `POST_END_DATE` FROM `college_years` WHERE `college_id`='.  UserCollege()));
        $grade_end_date[1]['POST_END_DATE'] = date('Y-m-d', strtotime('+5 days', strtotime($cp_det[1]['END_DATE'])));
        $grade_start_time = strtotime($grade_start_date[1]['POST_START_DATE']);

        $grade_start_date[1]['POST_END_DATE'] = $grade_end_date[1]['POST_END_DATE'];
        $grade_end_time = strtotime($grade_end_date[1]['POST_END_DATE']);
        
        $current_time = strtotime(date("Y-m-d"));

        if ($current_time >= $grade_start_time && $current_time <= $grade_end_time && $grade_start_time != '' && $grade_end_time != '') {
            $grade_status = 'open';
        } else if ($current_time >= $grade_end_time && $grade_end_time != '') {
            $grade_status = 'closed';
        } else if ($current_time <= $grade_start_time) {
            $grade_status = 'not open yet';
        } else {
            $grade_status = 'not set yet';
        }
        if ($grade_status == 'open')
            $allow_edit = true;
    }
}
if ($cp_type == 'custom') {
    $full_year_mp = DBGet(DBQuery('SELECT MARKING_PERIOD_ID FROM college_years WHERE COLLEGE_ID=' . UserCollege() . ' AND SYEAR=' . UserSyear()));
}
$time = strtotime(DBDate('postgres'));

$mps_select = "<SELECT class=\"form-control\" name=mp onChange='this.form.submit();'>";
if ($cp_type != 'custom') {
    if ($pros != '')
        foreach (explode(',', str_replace("'", '', $pros)) as $pro) {
            if ($_REQUEST['mp'] == $pro && GetMP($pro, 'POST_START_DATE') && ($time >= strtotime(GetMP($pro, 'POST_START_DATE')) && $time <= strtotime(GetMP($pro, 'POST_END_DATE'))))
                $allow_edit = true;
            if (GetMP($pro, 'DOES_GRADES') == 'Y')
                $mps_select .= "<OPTION value=" . $pro . (($pro == $_REQUEST['mp']) ? ' SELECTED' : '') . ">" . GetMP($pro) . "</OPTION>";
        }

//if($_REQUEST['mp']==UserMP() && GetMP(UserMP(),'POST_START_DATE') && ($time>=strtotime(GetMP(UserMP(),'POST_START_DATE')) && $time<=strtotime(GetMP(UserMP(),'POST_END_DATE'))))
//	$allow_edit = true;
//$mps_select .= "<OPTION value=".UserMP().((UserMP()==$_REQUEST['mp'])?' SELECTED':'').">".GetMP(UserMP())."</OPTION>";
//$mps_select .= "<OPTION value='E'".UserMP().((UserMP()=='E'.$_REQUEST['mp'])?' SELECTED':'').">".GetMP("'E'".UserMP())."</OPTION>";


    if (($_REQUEST['mp'] == $qtr || $_REQUEST['mp'] == 'E' . $qtr) && GetMP($qtr, 'POST_START_DATE') && ($time >= strtotime(GetMP($qtr, 'POST_START_DATE')) && $time <= strtotime(GetMP($qtr, 'POST_END_DATE'))))
        $allow_edit = true;
    if (GetMP($qtr, 'DOES_GRADES') == 'Y')
        $mps_select .= "<OPTION value=$qtr" . (($qtr == $_REQUEST['mp']) ? ' SELECTED' : '') . ">" . GetMP($qtr) . "</OPTION>";
    if (GetMP($qtr, 'DOES_EXAM') == 'Y')
        $mps_select .= "<OPTION value=E$qtr" . (('E' . $qtr == $_REQUEST['mp']) ? ' SELECTED' : '') . ">" . GetMP($qtr) . " Exam</OPTION>";



    if (($_REQUEST['mp'] == $sem || $_REQUEST['mp'] == 'E' . $sem) && GetMP($sem, 'POST_START_DATE') && ($time >= strtotime(GetMP($sem, 'POST_START_DATE')) && $time <= strtotime(GetMP($sem, 'POST_END_DATE'))))
        $allow_edit = true;
    if (GetMP($sem, 'DOES_GRADES') == 'Y')
        $mps_select .= "<OPTION value=$sem" . (($sem == $_REQUEST['mp']) ? ' SELECTED' : '') . ">" . GetMP($sem) . "</OPTION>";
    if (GetMP($sem, 'DOES_EXAM') == 'Y')
        $mps_select .= "<OPTION value=E$sem" . (('E' . $sem == $_REQUEST['mp']) ? ' SELECTED' : '') . ">" . GetMP($sem) . " Exam</OPTION>";

    if (($_REQUEST['mp'] == $fy || $_REQUEST['mp'] == 'E' . $fy) && GetMP($fy, 'POST_START_DATE') && ($time >= strtotime(GetMP($fy, 'POST_START_DATE')) && $time <= strtotime(GetMP($fy, 'POST_END_DATE'))))
        $allow_edit = true;
    if (GetMP($fy, 'DOES_GRADES') == 'Y')
        $mps_select .= "<OPTION value=" . $fy . (($fy == $_REQUEST['mp']) ? ' SELECTED' : '') . ">" . GetMP($fy) . "</OPTION>";
    if (GetMP($fy, 'DOES_EXAM') == 'Y')
        $mps_select .= "<OPTION value=E" . $fy . (('E' . $fy == $_REQUEST['mp']) ? ' SELECTED' : '') . ">" . GetMP($fy) . " Exam</OPTION>";
}
else {

//   if(GetMP($full_year_mp[1]['MARKING_PERIOD_ID'],'DOES_GRADES')=='Y')
    $mps_select .= "<OPTION value=" . $full_year_mp[1]['MARKING_PERIOD_ID'] . (($full_year_mp[1]['MARKING_PERIOD_ID'] == $_REQUEST['mp']) ? ' SELECTED' : '') . ">" . GetMP($full_year_mp[1]['MARKING_PERIOD_ID']) . "</OPTION>";
    if (GetMP($full_year_mp[1]['MARKING_PERIOD_ID'], 'DOES_EXAM') == 'Y')
        $mps_select .= "<OPTION value=E" . $full_year_mp[1]['MARKING_PERIOD_ID'] . (('E' . $full_year_mp[1]['MARKING_PERIOD_ID'] == $_REQUEST['mp']) ? ' SELECTED' : '') . ">" . GetMP($full_year_mp[1]['MARKING_PERIOD_ID']) . " Exam</OPTION>";
}
$mps_select .= '</SELECT>';

// if running as a teacher program then openSIS[allow_edit] will already be set according to admin permissions

if (!isset($_openSIS['allow_edit']))
    $_openSIS['allow_edit'] = $allow_edit;

if ($_REQUEST['use_percents'] != 'true') {
    $extra['SELECT'] = ",'' AS GRADE_PERCENT,'' AS REPORT_CARD_GRADE";
    $extra['functions'] = array('GRADE_PERCENT' => '_makeGrade', 'REPORT_CARD_GRADE' => '_makeGrade');
} elseif ($not_graded) {

    $extra['SELECT'] = ",'' AS GRADE_PERCENT";
    $extra['functions'] = array('GRADE_PERCENT' => '_makePercent');
} else {

    $extra['SELECT'] = ",'' AS REPORT_CARD_GRADE,'' AS GRADE_PERCENT";
    $extra['functions'] = array('REPORT_CARD_GRADE' => '_makePercent', 'GRADE_PERCENT' => '_makePercent');
}

if (substr($_REQUEST['mp'], 0, 1) != 'E' && GetMP($_REQUEST['mp'], 'DOES_COMMENTS') == 'Y') {
    foreach ($commentsA_RET as $value) {
        $extra['SELECT'] .= ',\'' . $value['ID'] . '\' AS CA' . $value['ID'];
        $extra['functions'] += array('CA' . $value['ID'] => '_makeCommentsA');
    }
    for ($i = 1; $i <= $max_current_commentsB; $i++) {
        $extra['SELECT'] .= ',\'' . $i . '\' AS CB' . $i;
        $extra['functions'] += array('CB' . $i => '_makeCommentsB');
    }
    if (count($commentsB_select) && AllowEdit()) {
        $extra['SELECT'] .= ',\'' . $i . '\' AS CB' . $i;
        $extra['functions'] += array('CB' . $i => '_makeCommentsB');
    }
}


$extra['SELECT'] .= ",'' AS COMMENTS,'' AS COMMENT";



$extra['functions'] += array('COMMENT' => '_makeComment');

$stu_RET = GetStuList($extra);

echo "<FORM class=\"no-margin\" action=Modules.php?modname=" . strip_tags(trim($_REQUEST[modname])) . " method=POST>";

if (!$_REQUEST['_openSIS_PDF']) {

    $tmp_REQUEST = $_REQUEST;
    if (strstr($tmp_REQUEST['mp'], 'E') != "")
        $tmp_REQUEST['mp'] = str_replace('E', '', $tmp_REQUEST['mp']);
    unset($tmp_REQUEST['include_inactive']);

    if ($cp_type != 'custom') {
        $grade_start_date = DBGet(DBQuery('SELECT `POST_START_DATE` FROM `marking_periods` WHERE `marking_period_id`=' . $tmp_REQUEST['mp'] . ' AND does_grades=\'Y\''));
        $grade_end_date = DBGet(DBQuery('SELECT `POST_END_DATE` FROM `marking_periods` WHERE `marking_period_id`=' . $tmp_REQUEST['mp'] . ' AND does_grades=\'Y\''));
        $grade_start_time = strtotime($grade_start_date[1]['POST_START_DATE']);
        $grade_end_time = strtotime($grade_end_date[1]['POST_END_DATE']);
    }
    $current_time = strtotime(date("Y-m-d"));

    if ($current_time >= $grade_start_time && $current_time <= $grade_end_time && $grade_start_time != '' && $grade_end_time != '') {
        $grade_status = 'open';
    } else if ($current_time >= $grade_end_time && $grade_end_time != '') {
        $grade_status = 'closed';
    } else if ($current_time <= $grade_start_time) {
        $grade_status = 'not open yet';
    } else {
        $grade_status = 'not set yet';
    }

    if ($grade_status == 'not open yet' && $grade_end_time == '')
        $grade_status = 'not set yet';

    echo '<div class="form-group"><div class="form-inline">';
    if (count($stu_RET) != 0) {
        echo $mps_select . ' &nbsp; ' . SubmitButton('Save', 'submit[save]', 'class="btn btn-primary"').' &nbsp; &nbsp; ';
        echo '<input type=hidden name=period value="' . strip_tags(trim($_REQUEST['period'])) . '" />';
    }
    echo '<label class="checkbox checkbox-inline checkbox-switch switch-success switch-xs"><INPUT type=checkbox name=include_inactive value=Y' . ($_REQUEST['include_inactive'] == 'Y' ? " CHECKED onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&include_inactive=\";'" : " onclick='document.location.href=\"" . PreparePHP_SELF($tmp_REQUEST) . "&include_inactive=Y\";'") . '><span></span>Include Inactive Students</label>';
    echo '</div></div>';
    $dbf = DBGet(DBQuery('SELECT DOES_BREAKOFF,GRADE_SCALE_ID FROM course_periods WHERE COURSE_PERIOD_ID=\'' . UserCoursePeriod() . '\''));
    if ($dbf[1]['DOES_BREAKOFF'] == 'Y') {

        $get_details1 = DBGet(DBQuery('SELECT TITLE FROM program_user_config WHERE TITLE LIKE \'' . UserCoursePeriod() . '-%' . '\' AND USER_ID=\'' . User('STAFF_ID') . '\' AND PROGRAM=\'Gradebook\' AND VALUE LIKE \'%_' . UserCoursePeriod() . '\' AND VALUE<>\'\' '));
        $default_details1 = DBGet(DBQuery('SELECT ID FROM report_card_grades where college_id=\'' . UserCollege() . '\' AND syear=\'' . UserSyear() . '\' AND GRADE_SCALE_ID=\'' . $dbf[1]['GRADE_SCALE_ID'] . '\''));

        if (count($get_details1) != count($default_details1)) {

            echo '<div class="alert bg-danger alert-styled-left">Score Breakoff Points setup is incomplete,Please set Score Breakoff Points from configuration.</div>';
        }
    }

    if (AllowEdit()) {
        echo '<p class="alert alert-info alert-bordered">' . ($current_completed ? '<span>These grades are complete.</span>' : '<span>Grade reporting is open for this marking period.</span>') . (AllowEdit() ? ' <span>You can edit these grades.</span>' : ' <span class="text-danger">Grade reporting begins on : ' . date("M d, Y ", strtotime($grade_start_date[1]['POST_START_DATE'])) . '.</span>') . '</p>';
    } else if ($grade_status == 'not open yet') {
        echo '<p class="alert alert-info alert-bordered">' . ($current_completed ? '<span>These grades are complete.</span>' : '<span class="text-danger">Grade reporting is not open for this marking period.</span>') . (AllowEdit() ? ' <span>You can edit these grades.</span>' : ' <span class="text-danger">Grade reporting starts on: ' . date("M d, Y ", strtotime($grade_start_date[1]['POST_START_DATE'])) . ' and ends on : ' . date("M d, Y ", strtotime($grade_end_date[1]['POST_END_DATE'])) . '.</span>') . '</p>';
    } else if ($grade_status == 'closed') {
        echo '<p class="alert alert-info alert-bordered">' . ($current_completed ? '<span>These grades are complete.</span>' : '<span class="text-danger">These grades are complete.</span>') . (AllowEdit() ? ' <span>You can edit these grades.</span>' : ' <span class="text-danger">Grade reporting ended for this marking period on : ' . date("M d, Y ", strtotime($grade_end_date[1]['POST_END_DATE'])) . '.</span>') . '</p>';
    } else if ($grade_status == 'not set yet') {
        echo '<div class="alert bg-danger alert-styled-left">Grade reporting date has not set for this marking period.</div>';
    }

    if (AllowEdit() && count($stu_RET) != 0) {
        echo '<ul class="nav nav-pills nav-xs nav-pills-bordered">';
        if ($_REQUEST['use_percents'] != 'true')
            $gb_header = "<li><A HREF=Modules.php?modname=$_REQUEST[modname]&include_inactive=$_REQUEST[include_inactive]&mp=$_REQUEST[mp]&use_percents=true&period=" . CpvId() . ">Assign Percents</A></li>";
        elseif ($not_graded != true)
            $gb_header = "<li><A HREF=Modules.php?modname=$_REQUEST[modname]&include_inactive=$_REQUEST[include_inactive]&mp=$_REQUEST[mp]&use_percents=false&period=" . CpvId() . ">Assign Letters</A></li>";

        if (substr($_REQUEST['mp'], 0, 1) != 'E') {
            if ($cp_type != 'custom')
                $gb_header .= "<li><A HREF=Modules.php?modname=$_REQUEST[modname]&include_inactive=$_REQUEST[include_inactive]&modfunc=gradebook&mp=$_REQUEST[mp]&use_percents=true&period=$_REQUEST[period]>Get Gradebook grades</A></li>";
            else
                $gb_header .= "<li><A HREF=Modules.php?modname=$_REQUEST[modname]&include_inactive=$_REQUEST[include_inactive]&modfunc=gradebook&mp=" . ($_REQUEST[mp] == '' ? $full_year_mp[1]['MARKING_PERIOD_ID'] : $_REQUEST[mp]) . "&custom_cp=y&use_percents=true&period=$_REQUEST[period]>Get Gradebook grades</A></li>";

            if ($cp_type != 'custom') {
                if (GetMP($_REQUEST['mp'], 'PA_ID') != -1) {
                    $extra_sql = GetMP($_REQUEST['mp'], 'PA_ID') ."='" . ParentMP($_REQUEST['mp']) . "'";
                } else {
                    $extra_sql = "";
                }
                $prev_mp = DBGet(DBQuery("SELECT MARKING_PERIOD_ID,TITLE,START_DATE FROM ". GetMP($_REQUEST['mp'], 'TABLE') . " WHERE COLLEGE_ID='" . UserCollege() . "' AND SYEAR='" . UserSyear() . "' AND START_DATE<'" . GetMP($_REQUEST['mp'], 'START_DATE') . "' AND ". $extra_sql ." ORDER BY START_DATE DESC LIMIT 1"));
                
                $cp_mp = DBGet(DBQuery("SELECT MP FROM course_periods WHERE COURSE_PERIOD_ID='" . UserCoursePeriod() . "' AND SYEAR='" . UserSyear() . "' AND COLLEGE_ID='" . UserCollege() . "'"));
            }
            $cp_mp = $cp_mp[1]['MP'];
            $prev_mp = $prev_mp[1];
            
            if ($cp_mp !='FY' && $cp_type != 'custom' && $prev_mp) {
                $gb_header .= "<li><A HREF=Modules.php?modname=$_REQUEST[modname]&include_inactive=$_REQUEST[include_inactive]&modfunc=grades&mp=$_REQUEST[mp]&prev_mp=$prev_mp[MARKING_PERIOD_ID]&use_percents=true&period=$_REQUEST[period]>Get $prev_mp[TITLE] grades</A></li>";
                $gb_header .= "<li><A HREF=Modules.php?modname=$_REQUEST[modname]&include_inactive=$_REQUEST[include_inactive]&modfunc=comments&mp=$_REQUEST[mp]&prev_mp=$prev_mp[MARKING_PERIOD_ID]&use_percents=false&period=$_REQUEST[period]>Get $prev_mp[TITLE] Comments</A></li>";
            }
        }
        if ($cp_type != 'custom')
            $bar = ' | ';

        if (substr($_REQUEST['mp'], 0, 1) == 'E')
            $bar = ' | ';
        $gb_header .= "<li><A class=\"text-danger\" HREF=Modules.php?modname=$_REQUEST[modname]&include_inactive=$_REQUEST[include_inactive]&modfunc=clearall&mp=$_REQUEST[mp]&use_percents=$_REQUEST[use_percents]&period=$_REQUEST[period]><i class=\"fa fa-times\"></i> Clear All</A></li>";
        $gb_header .= '</ul>';
    }
    echo $gb_header;
}
else {

    DrawHeader($course_title);
    DrawHeader(GetMP(UserMP()));
}

$columns = array('FULL_NAME' => 'Student', 'COLLEGE_ROLL_NO' => 'College Roll No');
if ($_REQUEST['include_inactive'] == 'Y')
    $columns += array('ACTIVE' => 'College Status', 'ACTIVE_SCHEDULE' => 'Course Status');
if ($_REQUEST['use_percents'] != 'true')
    $columns += array('GRADE_PERCENT' => 'Percent', 'REPORT_CARD_GRADE' => 'Assign Grade');
elseif ($not_graded)
    $columns += array('GRADE_PERCENT' => 'Assign Percent');
else
    $columns += array('REPORT_CARD_GRADE' => 'Grade', 'GRADE_PERCENT' => 'Assign Percent');



if (substr($_REQUEST['mp'], 0, 1) != 'E' && GetMP($_REQUEST['mp'], 'DOES_COMMENTS') == 'Y') {
    foreach ($commentsA_RET as $value)
        $columns += array('CA' . $value['ID'] => $value['TITLE']);
    for ($i = 1; $i <= $max_current_commentsB; $i++)
        $columns += array('CB' . $i => 'Comment ' . $i);
    if (count($commentsB_select) && AllowEdit() && !isset($_REQUEST['_openSIS_PDF']))
        $columns += array('CB' . $i => 'Add Comment');
    $columns += array('COMMENT' => 'Comment');
}

ListOutput($stu_RET, $columns, 'Student', 'Students', false, false, array('yscroll' => true));



if (count($stu_RET) != 0) {
    echo '<div class="panel-footer">' . SubmitButton('Save', 'submit[save]', 'class="btn btn-primary"') . '</div>';
}
echo "</FORM>";
echo '</div>'; //.panel-body
echo '</div>'; //.panel

function _makeGrade($value, $column) {
    global $THIS_RET, $current_RET, $import_RET, $grades_RET, $grades_select, $student_count, $tabindex;
    $tc_grade = 'n';
    if ($column == 'REPORT_CARD_GRADE') {

        if (!isset($_REQUEST['_openSIS_PDF'])) {
            $student_count++;
            $tabindex = $student_count;

            if ($import_RET[$THIS_RET['COLLEGE_ROLL_NO']]) {
                $select = $import_RET[$THIS_RET['COLLEGE_ROLL_NO']][1]['REPORT_CARD_GRADE_ID'];
                $extra_select = array($select => $grades_select[$import_RET[$THIS_RET['COLLEGE_ROLL_NO']][1]['REPORT_CARD_GRADE_ID']]);
                $div = false;
            } else {

                if ($current_RET[$THIS_RET['COLLEGE_ROLL_NO']][1]['GRADE_PERCENT'] != '') {

                    $select = _makeLetterGrade($current_RET[$THIS_RET['COLLEGE_ROLL_NO']][1]['GRADE_PERCENT'] / 100, "", User('STAFF_ID'), "%");
                }
                $rounding = DBGet(DBQuery('SELECT VALUE FROM program_user_config WHERE USER_ID=\'' . User('STAFF_ID') . '\' AND TITLE=\'ROUNDING\' AND PROGRAM=\'Gradebook\' AND VALUE LIKE \'%_' . UserCoursePeriod() . '\''));
                if (count($rounding))
                    $_SESSION['ROUNDING'] = rtrim($rounding[1]['VALUE'], '_' . UserCoursePeriod());
                else
                    $_SESSION['ROUNDING'] = '';

                if ($_SESSION['ROUNDING'] == 'UP')
                    $select = ceil($select);
                elseif ($_SESSION['ROUNDING'] == 'DOWN')
                    $select = floor($select);
                elseif ($_SESSION['ROUNDING'] == 'NORMAL')
                    $select = round($select);


                $dbf = DBGet(DBQuery('SELECT DOES_BREAKOFF,GRADE_SCALE_ID FROM course_periods WHERE COURSE_PERIOD_ID=\'' . UserCoursePeriod() . '\''));
                if ($dbf[1]['DOES_BREAKOFF'] == 'Y' && $select !== '') {

                    $get_details = DBGet(DBQuery('SELECT TITLE,VALUE FROM program_user_config WHERE USER_ID=\'' . User('STAFF_ID') . '\' AND PROGRAM=\'Gradebook\' AND VALUE LIKE \'%_' . UserCoursePeriod() . '\'  ORDER BY VALUE DESC '));
                    if (count($get_details)) {
                        $tgs_total = DBGet(DBQuery('SELECT COUNT(*) as RET_EX FROM program_user_config WHERE USER_ID=\'' . User('STAFF_ID') . '\' AND PROGRAM=\'Gradebook\' AND VALUE LIKE \'%_' . UserCoursePeriod() . '\''));
                        $tgs_total = $tgs_total[1]['RET_EX'];
                        $tgs_ac_total = DBGet(DBQuery('SELECT COUNT(*) as RET_EX FROM program_user_config WHERE USER_ID=\'' . User('STAFF_ID') . '\' AND PROGRAM=\'Gradebook\' AND VALUE LIKE \'%_' . UserCoursePeriod() . '\' AND VALUE IS NOT NULL'));
                        $tgs_ac_total = $tgs_ac_total[1]['RET_EX'];
                        if ($tgs_ac_total == $tgs_total) {
                            foreach ($get_details as $i => $d) {
                                $unused_var = explode('_', $d['VALUE']);
                                $unused_var2 = explode('-', $d['TITLE']);
                                if ($select >= $unused_var[0] && is_numeric($unused_var[0]) && $unused_var2[0] == UserCoursePeriod()) {
                                    $id = $i;
                                    break;
                                }
                            }

                            $grade_id = explode('-', $get_details[$id]['TITLE']);
                            $select = $grade_id[1];
                        } else
                            $select = '';
                        $tc_grade = 'y';
                    }
                }

                if ($tc_grade == 'n')
                    $select = $current_RET[$THIS_RET['COLLEGE_ROLL_NO']][1]['REPORT_CARD_GRADE_ID'];
                $extra_select = array();
                $div = true;
            }
            if ($_REQUEST['modfunc'] == 'clearall')
                $select = '';

            $return = SelectInput($select, 'values[' . $THIS_RET['COLLEGE_ROLL_NO'] . '][grade]', '', $extra_select + $grades_select, false, 'tabindex=' . $tabindex, $div);
        }
        else {
            if ($import_RET[$THIS_RET['COLLEGE_ROLL_NO']])
                $select = $import_RET[$THIS_RET['COLLEGE_ROLL_NO']][1]['REPORT_CARD_GRADE_ID'];
            else
                $select = $current_RET[$THIS_RET['COLLEGE_ROLL_NO']][1]['REPORT_CARD_GRADE_ID'];
            $return = '<b>' . $grades_RET[$select][1]['TITLE'] . '</b>';
        }
    }
    elseif ($column == 'GRADE_PERCENT') {
        if ($import_RET[$THIS_RET['COLLEGE_ROLL_NO']])
            $select = $import_RET[$THIS_RET['COLLEGE_ROLL_NO']][1]['GRADE_PERCENT'];
        else {
            $select = $current_RET[$THIS_RET['COLLEGE_ROLL_NO']][1]['GRADE_PERCENT'];
            $rounding = DBGet(DBQuery('SELECT VALUE FROM program_user_config WHERE USER_ID=\'' . User('STAFF_ID') . '\' AND TITLE=\'ROUNDING\' AND PROGRAM=\'Gradebook\' AND VALUE LIKE \'%_' . UserCoursePeriod() . '\''));
            if (count($rounding))
                $_SESSION['ROUNDING'] = rtrim($rounding[1]['VALUE'], '_' . UserCoursePeriod());
            else
                $_SESSION['ROUNDING'] = '';

            if ($_SESSION['ROUNDING'] == 'UP')
                $select = ceil($select);
            elseif ($_SESSION['ROUNDING'] == 'DOWN')
                $select = floor($select);
            elseif ($_SESSION['ROUNDING'] == 'NORMAL')
                $select = round($select);
            $return = $select == '' ? '' : ($select + 0) . '%';
        }
        if ($_REQUEST['modfunc'] == 'clearall')
            $return = '<input type="hidden" name="values[' . $THIS_RET[COLLEGE_ROLL_NO] . '][percent]" value="" />';
    }

    return $return;
}

function _makePercent($value, $column) {
    global $THIS_RET, $current_RET, $grades_RET, $student_count, $tabindex, $import_RET;

    if ($column == 'GRADE_PERCENT') {
        if (!isset($_REQUEST['_openSIS_PDF'])) {
            $student_count++;
            $tabindex = $student_count;
            if ($import_RET[$THIS_RET['COLLEGE_ROLL_NO']])
                $return = TextInput($import_RET[$THIS_RET['COLLEGE_ROLL_NO']][1]['GRADE_PERCENT'] == '' ? '' : (_makeLetterGrade($import_RET[$THIS_RET['COLLEGE_ROLL_NO']][1]['GRADE_PERCENT'] / 100, "", User('STAFF_ID'), "%") + 0) . '%', "values[$THIS_RET[COLLEGE_ROLL_NO]][percent]", '', (0 ? 'readonly ' : '') . 'size=6 maxlength=6 tabindex=' . $tabindex, false);
            else
                $return = TextInput($current_RET[$THIS_RET['COLLEGE_ROLL_NO']][1]['GRADE_PERCENT'] == '' ? '' : (_makeLetterGrade($current_RET[$THIS_RET['COLLEGE_ROLL_NO']][1]['GRADE_PERCENT'] / 100, "", User('STAFF_ID'), "%") + 0) . '%', "values[$THIS_RET[COLLEGE_ROLL_NO]][percent]", '', (0 ? 'readonly ' : '') . 'size=6 maxlength=6 tabindex=' . $tabindex, !$current_RET[$THIS_RET['COLLEGE_ROLL_NO']][1]['DIV']);
        } else
            $return = $current_RET[$THIS_RET['COLLEGE_ROLL_NO']][1]['GRADE_PERCENT'] == '' ? '' : (_makeLetterGrade($current_RET[$THIS_RET['COLLEGE_ROLL_NO']][1]['GRADE_PERCENT'] / 100, "", User('STAFF_ID'), "%") + 0) . '%';
    }
    elseif ($column == 'REPORT_CARD_GRADE') {
        if ($current_RET[$THIS_RET['COLLEGE_ROLL_NO']][1]['REPORT_CARD_GRADE_ID'] == '' && $import_RET[$THIS_RET['COLLEGE_ROLL_NO']]) {
            $return = '<b>' . $grades_RET[$import_RET[$THIS_RET['COLLEGE_ROLL_NO']][1]['REPORT_CARD_GRADE_ID']][1]['TITLE'] . '</b>';
        } else
            $return = '<b>' . $grades_RET[$current_RET[$THIS_RET['COLLEGE_ROLL_NO']][1]['REPORT_CARD_GRADE_ID']][1]['TITLE'] . '</b>';
    }
    return $return;
}

$table = 'student_report_card_comments';

function _makeComment($value, $column) {
    global $THIS_RET, $current_RET, $import_comments_RET, $tabindex;
    $table = 'student_report_card_comments';

    if ($import_comments_RET[$THIS_RET['COLLEGE_ROLL_NO']]) {
        $select = $import_comments_RET[$THIS_RET['COLLEGE_ROLL_NO']][1]['COMMENT'];
        $div = false;
    } else {
        $select = $current_RET[$THIS_RET['COLLEGE_ROLL_NO']][1]['COMMENT'];
        $div = true;
    }

    if (!isset($_REQUEST['_openSIS_PDF'])) {

        $return = TextAreaInputInputFinalGrade(str_replace('"', '\"', $select), "values[$THIS_RET[COLLEGE_ROLL_NO]][comment]", '', 'tabindex=' . ($tabindex += 100), $div);
    } else {
        $return = '<small>' . $select . '</small>';
    }

    return $return;
}

function _makeCommentsA($value, $column) {
    global $THIS_RET, $current_commentsA_RET, $import_commentsA_RET, $commentsA_select, $tabindex;

    if ($import_commentsA_RET[$THIS_RET['COLLEGE_ROLL_NO']][$value]) {
        $select = $import_commentsA_RET[$THIS_RET['COLLEGE_ROLL_NO']][$value][1]['COMMENT'];
        $div = false;
    } else {
        if (!$current_commentsA_RET[$THIS_RET['COLLEGE_ROLL_NO']] && !$import_commentsA_RET[$THIS_RET['COLLEGE_ROLL_NO']] && AllowEdit()) {
            $select = Preferences('COMMENT_A', 'Gradebook');
            $div = false;
        } else {
            $select = $current_commentsA_RET[$THIS_RET['COLLEGE_ROLL_NO']][$value][1]['COMMENT'];
            $div = true;
        }
    }

    if (!isset($_REQUEST['_openSIS_PDF']))
        $return = SelectInput($select, 'values[' . $THIS_RET['COLLEGE_ROLL_NO'] . '][commentsA][' . $value . ']', '', $commentsA_select, 'N/A', 'tabindex=' . ($tabindex += 100), $div);
    else
        $return = $select != ' ' ? $select : 'o';

    return $return;
}

function _makeCommentsB($value, $column) {
    global $THIS_RET, $current_commentsB_RET, $import_commentsB_RET, $commentsB_RET, $max_current_commentsB, $commentsB_select, $tabindex;

    if ($import_commentsB_RET[$THIS_RET['COLLEGE_ROLL_NO']][$value]) {
        $select = $import_commentsB_RET[$THIS_RET['COLLEGE_ROLL_NO']][$value]['REPORT_CARD_COMMENT_ID'];
        $div = false;
    } else {
        $select = $current_commentsB_RET[$THIS_RET['COLLEGE_ROLL_NO']][$value]['REPORT_CARD_COMMENT_ID'];
        $div = true;
    }

    if (!isset($_REQUEST['_openSIS_PDF']))
        if ($value > $max_current_commentsB)
            $return = SelectInput('', 'values[' . $THIS_RET['COLLEGE_ROLL_NO'] . '][commentsB][' . $value . ']', '', $commentsB_select, 'N/A', 'tabindex=' . ($tabindex += 100));
        elseif ($import_commentsB_RET[$THIS_RET['COLLEGE_ROLL_NO']][$value] || $current_commentsB_RET[$THIS_RET['COLLEGE_ROLL_NO']][$value])
            $return = SelectInput($select, 'values[' . $THIS_RET['COLLEGE_ROLL_NO'] . '][commentsB][' . $value . ']', '', $commentsB_select, 'N/A', 'tabindex=' . ($tabindex += 100), $div);
        else
            $return = '';
    else
        $return = '<small>' . $commentsB_RET[$select][1]['TITLE'] . '</small>';

    return $return;
}

function _makeAssnWG($value, $column) {
    global $THIS_RET, $student_points, $total_points, $percent_weights;
    return ($THIS_RET['ASSIGN_TYP_WG'] != 'N/A' ? ($value * 100) . ' %' : $THIS_RET['ASSIGN_TYP_WG']);
}

function _makeExtra($value, $column) {
    global $THIS_RET, $student_points, $total_points, $percent_weights;

    if ($column == 'POINTS') {
        if ($THIS_RET['TOTAL_POINTS'] != '0')
            if ($value != '-1') {
                if (($THIS_RET['DUE'] || $value != '') && $value != '') {
                    $student_points[$THIS_RET['ASSIGNMENT_TYPE_ID']] += $value;
                    $total_points[$THIS_RET['ASSIGNMENT_TYPE_ID']] += $THIS_RET['TOTAL_POINTS'];
                    $percent_weights[$THIS_RET['ASSIGNMENT_TYPE_ID']] = $THIS_RET['FINAL_GRADE_PERCENT'];
                }
                return '<TABLE border=0 cellspacing=0 cellpadding=0 class=LO_field><TR><TD><font size=-1>' . (rtrim(rtrim($value, '0'), '.') + 0) . '</font></TD><TD><font size=-1>&nbsp;/&nbsp;</font></TD><TD><font size=-1>' . $THIS_RET['TOTAL_POINTS'] . '</font></TD></TR></TABLE>';
            } else
                return '<TABLE border=0 cellspacing=0 cellpadding=0 class=LO_field><TR><TD><font size=-1>Excluded</font></TD><TD></TD><TD></TD></TR></TABLE>';
        else {
            $student_points[$THIS_RET['ASSIGNMENT_TYPE_ID']] += $value;
            return '<TABLE border=0 cellspacing=0 cellpadding=0 class=LO_field><TR><TD><font size=-1>' . (rtrim(rtrim($value, '0'), '.') + 0) . '</font></TD><TD><font size=-1>&nbsp;/&nbsp;</font></TD><TD><font size=-1>' . $THIS_RET['TOTAL_POINTS'] . '</font></TD></TR></TABLE>';
        }
    } elseif ($column == 'LETTER_GRADE') {
        if ($THIS_RET['TOTAL_POINTS'] != '0')
            if ($value != '-1')
                if ($THIS_RET['DUE'] && $value == '')
                    return 'Not Graded';
                else if ($THIS_RET['DUE'] || $value != '') {

                    $per = $value / $THIS_RET['TOTAL_POINTS'];

                    return _makeLetterGrade($per, "", User('STAFF_ID'), "%") . '%&nbsp;' . _makeLetterGrade($value / $THIS_RET['TOTAL_POINTS'], "", User('STAFF_ID'));
                } else
                    return 'Due';
            else
                return 'N/A';
        else
            return 'E/C';
    }
}

?>
