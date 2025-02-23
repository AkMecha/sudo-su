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


echo '<div class="row">';

echo '<div class="col-md-6 col-md-offset-3">';

$tables = array('college_periods' => 'College Periods', 'college_years' => 'Marking Periods', 'report_card_grades' => 'Report Card Grade Codes', 'report_card_comments' => 'Report Card Comment Codes', 'eligibility_activities' => 'Eligibility Activity Codes', 'attendance_codes' => 'Attendance Codes', 'college_gradelevels' => 'Grade Levels', 'rooms' => 'Rooms', 'college_gradelevel_sections' => 'Sections', 'course_subjects' => 'Subjects', 'college_calendars' => 'Calendar','courses' => 'Course',);

$table_list = '<br/><div class="form-group"><label class="control-label text-uppercase" for="collegeTitle"><b>New College\'s Title</b></label><INPUT type=text name=title placeholder="Title" value="New College" id="collegeTitle" onKeyUp="checkDuplicateName(1,this,0);" onBlur="checkDuplicateName(1,this,0);" class="form-control"></div>';

$table_list .= '<div class="row">';
foreach ($tables as $table => $name) {
    $table_list .= '<div class="col-md-6">';
    if($table=='courses')
    $table_list .= '<div class="checkbox checkbox-switch switch-success"><label><INPUT type="checkbox" id="course" value="Y" name="tables[' . $table . ']" checked="checked" onClick="checkChecked(\'course\',\'subject\');"><span></span> ' . $name . '</label></div>';
    elseif($table=='course_subjects')
    $table_list .= '<div class="checkbox checkbox-switch switch-success"><label><INPUT type="checkbox" id="subject"  value="Y" name="tables[' . $table . ']" checked="checked"  onClick="turnCheckOff(\'course\',\'subject\');"><span></span> ' . $name . '</label></div>';
    else
    $table_list .= '<div class="checkbox checkbox-switch switch-success"><label><INPUT type="checkbox" value="Y" name="tables[' . $table . ']" checked="checked"><span></span> ' . $name . '</label></div>';

    $table_list .= '</div>'; //.col-md-6
}
$table_list .= '</div>';

$table_list .= "<input type=hidden id=checkDuplicateNameTable1 value='colleges'/>";
$table_list .= "<input type=hidden id=checkDuplicateNameField1 value='title'/>";
$table_list .= "<input type=hidden id=checkDuplicateNameMsg1 value='college name'/>";
if (clean_param($_REQUEST['copy'], PARAM_ALPHAMOD) == 'done') {
    echo '<strong>College information has been copied successfully.</strong>';
} else {
    DrawBC("College Setup > " . ProgramTitle());
    if (Prompt_Copy_College('Confirm Copy College', 'Are you sure you want to copy the data for <span class="text-primary">' . GetCollege(UserCollege()) . '</span> to a new college?', $table_list)) {
        if (count($_REQUEST['tables'])) {

            $id = DBGet(DBQuery('SHOW TABLE STATUS LIKE \'colleges\''));
            $id[1]['ID'] = $id[1]['AUTO_INCREMENT'];
            $id = $id[1]['ID'];
            $copy_syear_RET = DBGet(DBQuery('SELECT MAX(syear) AS SYEAR FROM college_years WHERE college_id=' . UserCollege()));
            $new_sch_syear = $copy_syear_RET[1]['SYEAR'];
            DBQuery('INSERT INTO colleges (ID,SYEAR,TITLE) values(\'' . $id . '\',\'' . $new_sch_syear . '\',\'' . str_replace("'", "''", str_replace("\'", "''", paramlib_validation($col = TITLE, $_REQUEST['title']))) . '\')');
            DBQuery('INSERT INTO college_years (MARKING_PERIOD_ID,SYEAR,COLLEGE_ID,TITLE,SHORT_NAME,SORT_ORDER,START_DATE,END_DATE,POST_START_DATE,POST_END_DATE,DOES_GRADES,DOES_EXAM,DOES_COMMENTS,ROLLOVER_ID) SELECT fn_marking_period_seq(),SYEAR,\'' . $id . '\' AS COLLEGE_ID,TITLE,SHORT_NAME,SORT_ORDER,START_DATE,END_DATE,POST_START_DATE,POST_END_DATE,DOES_GRADES,DOES_EXAM,DOES_COMMENTS,MARKING_PERIOD_ID FROM college_years WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\' ORDER BY MARKING_PERIOD_ID');
            DBQuery('INSERT INTO program_config(COLLEGE_ID,SYEAR,PROGRAM,TITLE,VALUE) VALUES(\'' . $id . '\',\'' . $new_sch_syear . '\',\'MissingAttendance\',\'LAST_UPDATE\',\'' . date('Y-m-d') . '\')');
            DBQuery('INSERT INTO program_config(COLLEGE_ID,SYEAR,PROGRAM,TITLE,VALUE) VALUES(\'' . $id . '\',\'' . $new_sch_syear . '\',\'UPDATENOTIFY\',\'display_college\',"Y")');

            $current_start_date = DBGet(DBQuery('SELECT START_DATE FROM staff_college_relationship WHERE STAFF_ID=\'' . User('STAFF_ID') . '\' AND COLLEGE_ID='.UserCollege().' AND syear='.UserSyear().''));
            $temp_start_date='';
            if($current_start_date[1]['START_DATE']!='')
            $temp_start_date=$current_start_date[1]['START_DATE'];
            else
            $temp_start_date=date('Y-m-d');
            DBQuery('INSERT INTO staff_college_relationship(staff_id,college_id,syear,start_date)VALUES(\'' . User('STAFF_ID') . '\',\'' . $id . '\',\'' . UserSyear() . '\',"'.$temp_start_date.'")');
            $other_admin_details=DBGet(DBQuery('SELECT * FROM login_authentication WHERE PROFILE_ID=0 AND USER_ID!=' . User('STAFF_ID') . ''));
            if(!empty($other_admin_details))
            {
            foreach($other_admin_details as $college_data)
            {
            DBQuery('INSERT INTO  staff_college_relationship(staff_id,college_id,syear,start_date) VALUES (' . $college_data['USER_ID'] . ',' . $id . ',' . UserSyear(). ',"'.$temp_start_date.'")');    
            }
            }
            if (User('PROFILE_ID') != 0) {
                $super_id = DBGet(DBQuery('SELECT STAFF_ID FROM staff WHERE PROFILE_ID=0 AND PROFILE=\'admin\''));
                $current_start_date = DBGet(DBQuery('SELECT START_DATE FROM staff_college_relationship WHERE STAFF_ID=\'' . $super_id[1]['STAFF_ID'] . '\' AND COLLEGE_ID='.$id.' AND syear='.UserSyear().''));
                if($current_start_date[1]['START_DATE']!='')
                $temp_start_date=$current_start_date[1]['START_DATE'];
                else
                $temp_start_date=date('Y-m-d');
                 $staff_exists=DBGet(DBQuery('SELECT * FROM staff_college_relationship WHERE STAFF_ID='.$super_id[1]['STAFF_ID'] . ' AND COLLEGE_ID='. $id . ' AND SYEAR='.UserSyear()));
                    if(count($staff_exists)==0)
                        DBQuery('INSERT INTO  staff_college_relationship(staff_id,college_id,syear,start_date) VALUES (' . $super_id[1]['STAFF_ID'] . ',' . $id . ',' . UserSyear() . ',"'.$temp_start_date.'")');
            }
            foreach ($_REQUEST['tables'] as $table => $value)
                _rollover($table);
            DBQuery("UPDATE college_years SET ROLLOVER_ID = NULL WHERE COLLEGE_ID='$id'");
        }
        echo '<FORM action=Modules.php?modname=' . strip_tags(trim($_REQUEST['modname'])) . ' method=POST>';
        //echo '<script language=JavaScript>parent.side.location="' . $_SESSION['Side_PHP_SELF'] . '?modcat="+parent.side.document.forms[0].modcat.value;</script>';

        echo '<div class="panel panel-default">';
        echo '<div class="panel-body text-center">';
        echo '<div class="new-college-created  p-30">';
        echo '<div class="icon-college">';
        echo '<span></span>';
        echo '</div>';
        echo '<h5 class="p-20">The data have been copied to a new college called <b class="text-success">'.paramlib_validation($col = TITLE, $_REQUEST['title']).'</b>. To finish the operation, click the button below.</h5>';
        echo '<div class="text-center"><INPUT type="submit" value="Finish Setup" class="btn btn-primary btn-lg"></div>';
        echo '</div>'; //.new-college-created
        echo '</div>'; //.panel-body
        echo '</div>'; //.panel
        
        //DrawHeaderHome('<i class="icon-checkbox-checked"></i> &nbsp;The data have been copied to a new college called "' . paramlib_validation($col = TITLE, $_REQUEST['title']) . '".To finish the operation, click OK button.', '<INPUT  type=submit value=OK class="btn btn-primary">');
        echo '<input type="hidden" name="copy" value="done"/>';
        echo '</FORM>';
        unset($_SESSION['_REQUEST_vars']['tables']);
        unset($_SESSION['_REQUEST_vars']['delete_ok']);
    }
}

function _rollover($table) {
    global $id;

    switch ($table) {
        case 'college_periods':
            DBQuery('INSERT INTO college_periods (SYEAR,COLLEGE_ID,SORT_ORDER,TITLE,SHORT_NAME,LENGTH,START_TIME,END_TIME,IGNORE_SCHEDULING,ATTENDANCE) SELECT SYEAR,\'' . $id . '\' AS COLLEGE_ID,SORT_ORDER,TITLE,SHORT_NAME,LENGTH,START_TIME,END_TIME,IGNORE_SCHEDULING,ATTENDANCE FROM college_periods WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\'');
            break;

        case 'college_gradelevels':
            $table_properties = db_properties($table);
            $columns = '';
            foreach ($table_properties as $column => $values) {
                if ($column != 'ID' && $column != 'COLLEGE_ID' && $column != 'NEXT_GRADE_ID')
                    $columns .= ',' . $column;
            }
            DBQuery('INSERT INTO ' . $table . ' (COLLEGE_ID' . $columns . ') SELECT \'' . $id . '\' AS COLLEGE_ID' . $columns . ' FROM ' . $table . ' WHERE COLLEGE_ID=\'' . UserCollege() . '\'');
            DBQuery('UPDATE ' . $table . ' t1,' . $table . ' t2 SET t1.NEXT_GRADE_ID= t1.ID+1 WHERE t1.COLLEGE_ID=\'' . $id . '\' AND t1.ID+1=t2.ID');
            break;

        case 'college_years':
            DBQuery('INSERT INTO college_semesters (MARKING_PERIOD_ID,YEAR_ID,SYEAR,COLLEGE_ID,TITLE,SHORT_NAME,SORT_ORDER,START_DATE,END_DATE,POST_START_DATE,POST_END_DATE,DOES_GRADES,DOES_EXAM,DOES_COMMENTS,ROLLOVER_ID) SELECT fn_marking_period_seq(),(SELECT MARKING_PERIOD_ID FROM college_years y WHERE y.SYEAR=s.SYEAR AND y.ROLLOVER_ID=s.YEAR_ID AND y.COLLEGE_ID=\'' . $id . '\') AS YEAR_ID,SYEAR,\'' . $id . '\' AS COLLEGE_ID,TITLE,SHORT_NAME,SORT_ORDER,START_DATE,END_DATE,POST_START_DATE,POST_END_DATE,DOES_GRADES,DOES_EXAM,DOES_COMMENTS,MARKING_PERIOD_ID FROM college_semesters s WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\' ORDER BY MARKING_PERIOD_ID');
            DBQuery('INSERT INTO college_quarters (MARKING_PERIOD_ID,SEMESTER_ID,SYEAR,COLLEGE_ID,TITLE,SHORT_NAME,SORT_ORDER,START_DATE,END_DATE,POST_START_DATE,POST_END_DATE,DOES_GRADES,DOES_EXAM,DOES_COMMENTS,ROLLOVER_ID) SELECT fn_marking_period_seq(),(SELECT MARKING_PERIOD_ID FROM college_semesters s WHERE s.SYEAR=q.SYEAR AND s.ROLLOVER_ID=q.SEMESTER_ID AND s.COLLEGE_ID=\'' . $id . '\') AS SEMESTER_ID,SYEAR,\'' . $id . '\' AS COLLEGE_ID,TITLE,SHORT_NAME,SORT_ORDER,START_DATE,END_DATE,POST_START_DATE,POST_END_DATE,DOES_GRADES,DOES_EXAM,DOES_COMMENTS,MARKING_PERIOD_ID FROM college_quarters q WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\' ORDER BY MARKING_PERIOD_ID');
            DBQuery('INSERT INTO college_progress_periods (MARKING_PERIOD_ID,QUARTER_ID,SYEAR,COLLEGE_ID,TITLE,SHORT_NAME,SORT_ORDER,START_DATE,END_DATE,POST_START_DATE,POST_END_DATE,DOES_GRADES,DOES_EXAM,DOES_COMMENTS,ROLLOVER_ID) SELECT fn_marking_period_seq(),(SELECT MARKING_PERIOD_ID FROM college_quarters q WHERE q.SYEAR=p.SYEAR AND q.ROLLOVER_ID=p.QUARTER_ID AND q.COLLEGE_ID=\'' . $id . '\'),SYEAR,\'' . $id . '\' AS COLLEGE_ID,TITLE,SHORT_NAME,SORT_ORDER,START_DATE,END_DATE,POST_START_DATE,POST_END_DATE,DOES_GRADES,DOES_EXAM,DOES_COMMENTS,MARKING_PERIOD_ID FROM college_progress_periods p WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\' ORDER BY MARKING_PERIOD_ID');

            DBQuery('UPDATE college_semesters SET ROLLOVER_ID = NULL WHERE COLLEGE_ID=\'' . $id . '\'');
            DBQuery('UPDATE college_quarters SET ROLLOVER_ID = NULL WHERE COLLEGE_ID=\'' . $id . '\'');
            DBQuery('UPDATE college_progress_periods SET ROLLOVER_ID = NULL WHERE COLLEGE_ID=\'' . $id . '\'');

            break;

        case 'report_card_grades':
            DBQuery('INSERT INTO report_card_grade_scales (SYEAR,COLLEGE_ID,TITLE,COMMENT,SORT_ORDER,ROLLOVER_ID,GP_SCALE) SELECT SYEAR,\'' . $id . '\',TITLE,COMMENT,SORT_ORDER,ID,GP_SCALE FROM report_card_grade_scales WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\'');

            $qr = DBGet(DBQuery('select * from report_card_grades where college_id=' . UserCollege() . ' and SYEAR= ' . UserSyear() . ''));
            $c = 1;
            foreach ($qr as $qk => $qv) {

                $qr1 = DBGet(DBQuery('select id from report_card_grade_scales where title=(select title from report_card_grade_scales where id=' . $qv['GRADE_SCALE_ID'] . ') and college_id=' . $id . ''));
                $gr_scale_id = $qr1[1]['ID'];

                DBQuery('INSERT INTO report_card_grades (SYEAR,COLLEGE_ID,TITLE,COMMENT,BREAK_OFF,GPA_VALUE,UNWEIGHTED_GP,GRADE_SCALE_ID,SORT_ORDER) SELECT SYEAR,\'' . $id . '\',TITLE,COMMENT,BREAK_OFF,GPA_VALUE,UNWEIGHTED_GP,\'' . $gr_scale_id . '\',SORT_ORDER FROM report_card_grades WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\' AND ID=' . $qv['ID']);
            }

            DBQuery('UPDATE report_card_grade_scales SET ROLLOVER_ID=NULL WHERE COLLEGE_ID=\'' . $id . '\'');



            break;

        case 'report_card_comments':
            $qr = DBGet(DBQuery('SELECT COURSE_ID,ID FROM report_card_comments WHERE   SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\''));

            foreach ($qr as $qk => $qv) {

                $qr1 = DBGet(DBQuery('select COURSE_ID,ID FROM report_card_comments WHERE   SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\''));
                $course_id = $qr1[$qk]['COURSE_ID'];
                $id1 = $qr1[$qk]['ID'];
                DBQuery('INSERT INTO report_card_comments (SYEAR,COLLEGE_ID,TITLE,SORT_ORDER,COURSE_ID) SELECT SYEAR,\'' . $id . '\',TITLE,SORT_ORDER,\'' . $course_id . '\' FROM report_card_comments WHERE ID =\'' . $id1 . '\' AND SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\'');
            }
            break;

        case 'eligibility_activities':
        case 'attendance_codes':
            $table_properties = db_properties($table);
            $columns = '';
            foreach ($table_properties as $column => $values) {
                if ($column != 'ID' && $column != 'SYEAR' && $column != 'COLLEGE_ID')
                    $columns .= ',' . $column;
            }
            DBQuery('INSERT INTO ' . $table . ' (SYEAR,COLLEGE_ID' . $columns . ') SELECT SYEAR,\'' . $id . '\' AS COLLEGE_ID' . $columns . ' FROM ' . $table . ' WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\'');
            break;
            
        case 'rooms':
           $table_properties = db_properties($table);
           $columns = '';
           foreach ($table_properties as $column => $values) {
               if ($column != 'ROOM_ID' && $column != 'COLLEGE_ID')
                   $columns .= ',' . $column;
           }
           DBQuery('INSERT INTO ' . $table . ' (COLLEGE_ID' . $columns . ') SELECT \'' . $id . '\' AS COLLEGE_ID' . $columns . ' FROM ' . $table . ' WHERE COLLEGE_ID=\'' . UserCollege() . '\'');
           break;   
            
        case 'college_gradelevel_sections':
            $table_properties = db_properties($table);
            $columns = '';
            foreach ($table_properties as $column => $values) {
                if ($column != 'ID' && $column != 'COLLEGE_ID')
                    $columns .= ',' . $column;
            }
            DBQuery('INSERT INTO ' . $table . ' (COLLEGE_ID' . $columns . ') SELECT \'' . $id . '\' AS COLLEGE_ID' . $columns . ' FROM ' . $table . ' WHERE  COLLEGE_ID=\'' . UserCollege() . '\'');
            break;    
        
        case 'course_subjects':
            $table_properties = db_properties($table);
            $columns = '';
            foreach ($table_properties as $column => $values) {
                if ($column != 'SUBJECT_ID' && $column != 'SYEAR' && $column != 'COLLEGE_ID')
                    $columns .= ',' . $column;
            }
            DBQuery('INSERT INTO ' . $table . ' (SYEAR,COLLEGE_ID' . $columns . ') SELECT SYEAR,\'' . $id . '\' AS COLLEGE_ID' . $columns . ' FROM ' . $table . ' WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\'');
            break;  
            
            
         
        case 'college_calendars':
           $get_all=DBGet(DBQuery('SELECT * FROM college_calendars WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\'')); 
           foreach($get_all as $ga)
           {
           $query_values=$id.','."'".$ga['TITLE']."'".','.$ga['SYEAR'];
           $query_build='INSERT INTO college_calendars (COLLEGE_ID,TITLE,SYEAR';
           if($ga['DEFAULT_CALENDAR']!='')
           {
               $query_build.=',DEFAULT_CALENDAR';
               $query_values.=','."'".$ga['DEFAULT_CALENDAR']."'";
           }
           if($ga['DAYS']!='')
           {
               $query_build.=',DAYS';
               $query_values.=','."'".$ga['DAYS']."'";
           }
           $query_build.=') VALUES ('.$query_values.')';
           DBQuery($query_build);
           unset($query_values);
           unset($query_build);
           $calendar_id=DBGet(DBQuery('SELECT MAX(CALENDAR_ID) as CALENDAR_ID FROM college_calendars WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' .$id.'\''));
           
           $table_properties = db_properties('attendance_calendar');
            $columns = '';
            foreach ($table_properties as $column => $values) {
                if ($column != 'COLLEGE_ID' && $column != 'CALENDAR_ID')
                    $columns .= ',' . $column;
            }
            DBQuery('INSERT INTO attendance_calendar (CALENDAR_ID,COLLEGE_ID' . $columns . ') SELECT \''.$calendar_id[1]['CALENDAR_ID'].'\' as CALENDAR_ID,\'' . $id . '\' AS COLLEGE_ID' . $columns . ' FROM attendance_calendar WHERE CALENDAR_ID=\''.$ga['CALENDAR_ID'].'\' ');
           }
           break;
           
           
           case 'courses':
           $get_ts_grade=DBGet(DBQuery('SELECT * FROM college_gradelevels WHERE COLLEGE_ID=\''.$id.'\' '));
           $get_cs_grade=DBGet(DBQuery('SELECT * FROM college_gradelevels WHERE  COLLEGE_ID=\''.UserCollege().'\' '));
           $get_ts_subjects=DBGet(DBQuery('SELECT * FROM course_subjects WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' .$id. '\''));     
           $get_cs_subjects=DBGet(DBQuery('SELECT * FROM course_subjects WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\'')); 
           foreach($get_cs_subjects as $gcsi=>$gcsd)
           {
               $get_course=DBGet(DBQuery('SELECT SYEAR,TITLE,SHORT_NAME,GRADE_LEVEL FROM courses WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\' AND SUBJECT_ID=\''.$gcsd['SUBJECT_ID'].'\''));
               foreach($get_course as $gc)
               {
                   $sql_columns=array('SUBJECT_ID','COLLEGE_ID');
                   $sql_values=array($get_ts_subjects[$gcsi]['SUBJECT_ID'],$id);
                   foreach($gc as $gcc=>$gcd)
                   {
                       if($gcd!='' && $gcc!='GRADE_LEVEL')
                       {
                       $sql_columns[]=$gcc;
                       $sql_values[]="'".$gcd."'";
                       }
                       if($gcd!='' && $gcc=='GRADE_LEVEL')
                       {
                           foreach($get_cs_grade as $gcsgi=>$gcsgd)
                           {
                            if($gcd==$gcsd['ID']) 
                            {
                            
                            $sql_columns[]='GRADE_LEVEL';
                            $sql_values[]="'".$get_ts_grade[$gcsgi]['ID']."'";
                            
                            }
                           }
                       }
                   }
                   DBQuery('INSERT INTO courses ('.implode(',',$sql_columns).') VALUES ('.(implode(',',$sql_values)).')');
               }
           }
           break;
    }
}

echo '</div>';
echo '</div>'; //.row
?>