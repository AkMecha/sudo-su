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
#######################################################################################################################
include('../../RedirectModulesInc.php');
if (clean_param($_REQUEST['modfunc'], PARAM_ALPHAMOD) == 'print' && $_REQUEST['report']) {
    echo '<style type="text/css">*{font-family:arial; font-size:12px;}</style>';

    if (clean_param($_REQUEST['marking_period_id'], PARAM_ALPHANUM))
        $where = ' AND MARKING_PERIOD_ID=' . $_REQUEST['marking_period_id'];
    $sql = 'select distinct
				(select title from course_subjects where subject_id=(select subject_id from courses where course_id=course_periods.course_id)) as subject,
				(select title from courses where course_id=course_periods.course_id) as COURSE_TITLE,course_id
				from course_periods where college_id=\'' . UserCollege() . '\' and syear=\'' . UserSyear() . '\' ' . $where . ' order by subject,COURSE_TITLE';


    $ret = DBGet(DBQuery($sql));

    if (count($ret)) {

        foreach ($ret as $s_id) {
            echo "<table width=100%  style=\" font-family:Arial; font-size:12px;\" >";
            $mark_name_rp = DBGet(DBQuery('SELECT TITLE,SHORT_NAME,\'2\'  FROM college_quarters WHERE MARKING_PERIOD_ID=\'' . $_REQUEST['marking_period_id'] . '\' AND COLLEGE_ID=\'' . UserCollege() . '\' AND SYEAR=\'' . UserSyear() . '\' UNION SELECT TITLE,SHORT_NAME,\'1\'  FROM college_semesters WHERE MARKING_PERIOD_ID=\'' . $_REQUEST['marking_period_id'] . '\' AND COLLEGE_ID=\'' . UserCollege() . '\' AND SYEAR=\'' . UserSyear() . '\' UNION SELECT TITLE,SHORT_NAME,\'0\'  FROM college_years WHERE MARKING_PERIOD_ID=\'' . $_REQUEST['marking_period_id'] . '\' AND COLLEGE_ID=\'' . UserCollege() . '\' AND SYEAR=\'' . UserSyear() . '\' ORDER BY 3'));
            $mark_name_rpt = $mark_name_rp[1]['TITLE'];
            if ($mark_name_rpt != '') {
                echo "<tr><td width=105>" . DrawLogo() . "</td><td  style=\"font-size:15px; font-weight:bold; padding-top:20px;\">" . GetCollege(UserCollege()) . "<div style=\"font-size:12px;\">Course catalog by Term: " . $mark_name_rpt . "</div></td><td align=right style=\"padding-top:20px;\">" . ProperDate(DBDate()) . "<br />Powered by openSIS</td></tr><tr><td colspan=3 style=\"border-top:1px solid #333;\">&nbsp;</td></tr></table>";
            } else {
                echo "<tr><td width=105>" . DrawLogo() . "</td><td  style=\"font-size:15px; font-weight:bold; padding-top:20px;\">" . GetCollege(UserCollege()) . "<div style=\"font-size:12px;\">Course catalog by Term: All</div></td><td align=right style=\"padding-top:20px;\">" . ProperDate(DBDate()) . "<br />Powered by openSIS</td></tr><tr><td colspan=3 style=\"border-top:1px solid #333;\">&nbsp;</td></tr></table>";
            }

            echo '<div align="center">';
            echo '<table border="0" width="97%" align="center"><tr><td><font face=verdana size=-1><b>' . $s_id['SUBJECT'] . '</b></font></td></tr>';
            echo '<tr><td align="right"><table border="0" width="97%"><tr><td><font face=verdana size=-1><b>' . $s_id['COURSE_TITLE'] . '</b></font></td></tr>';


            if (!$_REQUEST['marking_period_id']) {

                $sql_periods = 'SELECT cp.SHORT_NAME,(SELECT TITLE FROM college_periods WHERE period_id=cpv.period_id) AS PERIOD,r.TITLE as ROOM,SCHEDULE_TYPE, DAYOFWEEK(COURSE_PERIOD_DATE) AS CP_DAYS,cpv.DAYS,(SELECT CONCAT(LAST_NAME,\' \',FIRST_NAME,\' \') from staff where staff_id=cp.TEACHER_ID) as TEACHER from course_periods cp,course_period_var cpv,rooms r where cp.course_id=' . $s_id['COURSE_ID'] . ' and cp.syear=\'' . UserSyear() . '\' and cp.course_period_id=cpv.course_period_id and cpv.room_id=r.room_id and cp.college_id=\'' . UserCollege() . '\'';
            } else {

                $sql_periods = 'SELECT distinct cp.SHORT_NAME,(select CONCAT(START_TIME,\' - \',END_TIME,\' \') from college_periods where period_id=cpv.period_id) as PERIOD,r.TITLE as ROOM,SCHEDULE_TYPE, DAYOFWEEK(COURSE_PERIOD_DATE) AS CP_DAYS,cpv.DAYS,(select CONCAT(LAST_NAME,\' \',FIRST_NAME,\' \') from staff where staff_id=cp.TEACHER_ID) as TEACHER from course_periods cp,course_period_var cpv,rooms r where cp.course_id=' . $s_id['COURSE_ID'] . ' and cp.syear=\'' . UserSyear() . '\' and cp.course_period_id=cpv.course_period_id and cpv.room_id=r.room_id and cp.college_id=\'' . UserCollege() . '\' and cp.marking_period_id=\'' . $_REQUEST['marking_period_id'] . '\'';
            }



            $period_list = DBGet(DBQuery($sql_periods));
            //print_r($period_list);
            foreach ($period_list as $key => $val) {
                $cal_days = '';
                if ($val['CP_DAYS'] != '' && $val['SCHEDULE_TYPE'] == 'BLOCKED') {
                    switch ($val['CP_DAYS']) {
                        case 1:
                            $cal_days = 'U';
                            break;
                        case 2:
                            $cal_days = 'M';
                            break;
                        case 3:
                            $cal_days = 'T';
                            break;
                        case 4:
                            $cal_days = 'W';
                            break;
                        case 5:
                            $cal_days = 'H';
                            break;
                        case 6:
                            $cal_days = 'F';
                            break;
                        case 7:
                            $cal_days = 'S';
                            break;
                    }
                    $period_list[$key]['DAYS'] = $cal_days;
                }
            }
##############################################List Output Generation##################################################

            $columns = array('SHORT_NAME' => 'Course Period', 'PERIOD' => 'Time', 'DAYS' => 'Days', 'ROOM' => 'Location', 'TEACHER' => 'Teacher');

            echo '<tr><td colspan="2" valign="top" align="right">';
            PrintCatalog($period_list, $columns, 'Course', 'Courses', '', '', array('search' => false));
            echo '</td></tr></table></td></tr></table></td></tr>';

            ######################################################################################################################
            echo '</table></div>';

            echo "<div style=\"page-break-before: always;\"></div>";
        }
    } else
        echo '<table width=100%><tr><td align=center><font color=red face=verdana size=2><strong>No Courses are found in this term</strong></font></td></tr></table>';
}
else {
    echo '<div class="row">';
    echo '<div class="col-md-6 col-md-offset-3">';
    PopTable('header', 'Print Catalog by Term', 'class="panel panel-default"');
    echo "<FORM id='search' name='search' class='form-horizontal' method=POST action=Modules.php?modname=" . strip_tags(trim($_REQUEST[modname])) . ">";
    $mp_RET = DBGet(DBQuery('SELECT MARKING_PERIOD_ID,TITLE,SHORT_NAME,\'2\'  FROM college_quarters WHERE COLLEGE_ID=\'' . UserCollege() . '\' AND SYEAR=\'' . UserSyear() . '\' UNION SELECT MARKING_PERIOD_ID,TITLE,SHORT_NAME,\'1\'  FROM college_semesters WHERE COLLEGE_ID=\'' . UserCollege() . '\' AND SYEAR=\'' . UserSyear() . '\' UNION SELECT MARKING_PERIOD_ID,TITLE,SHORT_NAME,\'0\'  FROM college_years WHERE COLLEGE_ID=\'' . UserCollege() . '\' AND SYEAR=\'' . UserSyear() . '\' ORDER BY 3'));
    unset($options);
    if (count($mp_RET)) {
        foreach ($mp_RET as $key => $value) {
            if ($value['MARKING_PERIOD_ID'] == $_REQUEST['marking_period_id'])
                $mp_RET[$key]['row_color'] = Preferences('HIGHLIGHT');
        }

        $columns = array('TITLE' => 'Marking Periods');
        $link = array();
        $link['TITLE']['link'] = "Modules.php?modname=$_REQUEST[modname]";
        $link['TITLE']['variables'] = array('marking_period_id' => 'MARKING_PERIOD_ID', 'mp_name' => 'SHORT_NAME');
        $link['TITLE']['link'] .= "&modfunc=$_REQUEST[modfunc]";

        echo '<div class="form-group"><div class="col-md-12">'.CreateSelect($mp_RET, 'marking_period_id', 'All', 'Select Term', 'Modules.php?modname=' . strip_tags(trim($_REQUEST['modname'])) . '&marking_period_id=').'</div></div>';
    }
    if (clean_param($_REQUEST['marking_period_id'], PARAM_ALPHANUM)) {
        $mark_name = DBGet(DBQuery('SELECT TITLE,SHORT_NAME,\'2\'  FROM college_quarters WHERE MARKING_PERIOD_ID=\'' . $_REQUEST['marking_period_id'] . '\' AND COLLEGE_ID=\'' . UserCollege() . '\' AND SYEAR=\'' . UserSyear() . '\' UNION SELECT TITLE,SHORT_NAME,\'1\'  FROM college_semesters WHERE MARKING_PERIOD_ID=\'' . $_REQUEST['marking_period_id'] . '\' AND COLLEGE_ID=\'' . UserCollege() . '\' AND SYEAR=\'' . UserSyear() . '\' UNION SELECT TITLE,SHORT_NAME,\'0\'  FROM college_years WHERE MARKING_PERIOD_ID=\'' . $_REQUEST['marking_period_id'] . '\' AND COLLEGE_ID=\'' . UserCollege() . '\' AND SYEAR=\'' . UserSyear() . '\' ORDER BY 3'));
        $mark_name = $mark_name[1]['SHORT_NAME'];
        echo '<div class="alert bg-success alert-styled-left">Report generated for ' . $mark_name . ' Term</div>';
    } else {
        echo '<div class="alert bg-success alert-styled-left">Report generated for all Terms</div>';
    }
    echo '</form>';
    echo "<FORM name=exp class=no-margin-bottom id=exp action=ForExport.php?modname=" . strip_tags(trim($_REQUEST['modname'])) . "&modfunc=print&marking_period_id=" . $_REQUEST['marking_period_id'] . "&_openSIS_PDF=true&report=true method=POST target=_blank>";
    echo '<div class="text-right"><INPUT type=submit class="btn btn-primary" value=\'Print\'></div>';
    echo '</form>';
    PopTable('footer');
    echo '</div>'; //.col-md-6.col-md-offset-3
    echo '</div>'; //.row
}






##########functions###################

function CreateSelect($val, $name, $opt, $cap, $link) {
    $html .= '<label class="control-label text-uppercase"><b>' . $cap . '</b></label>';
    $html .= "<select name=" . $name . " id=" . $name . " class=\"form-control\" onChange=\"window.location='" . $link . "' + this.options[this.selectedIndex].value;\">";
    $html .= "<option value=''>" . $opt . "</option>";

    foreach ($val as $key => $value) {
        if ($value[strtoupper($name)] == $_REQUEST[$name])
            $html .= "<option selected value=" . $value[strtoupper($name)] . ">" . $value['TITLE'] . "</option>";
        else
            $html .= "<option value=" . $value[strtoupper($name)] . ">" . $value['TITLE'] . "</option>";
    }


    $html .= "</select>";
    return $html;
}

?>
