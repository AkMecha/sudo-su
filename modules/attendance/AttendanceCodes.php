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
if (!isset($_REQUEST['table']))
    $_REQUEST['table'] = 0;
if ($_REQUEST['values'] && ($_POST['values'] || $_REQUEST['ajax'])) {
    foreach ($_REQUEST['values'] as $id => $columns) {
        if (!(isset($columns['TITLE']) && trim($columns['TITLE']) == '')) {
            if ($columns['DEFAULT_CODE'] == 'Y')
                DBQuery('UPDATE attendance_codes SET DEFAULT_CODE=NULL WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\' AND TABLE_NAME=\'' . $_REQUEST['table'] . '\'');

            if ($id != 'new') {
                $flag = 1;
                $sql = 'UPDATE attendance_codes SET ';
                foreach ($columns as $column => $value) {
                    if ($column == 'TITLE' || $column == 'SHORT_NAME') {
                        $value = clean_param($value, PARAM_SPCL);
                        $samedata = DBGet(DBQuery('Select TITLE from attendance_codes WHERE ID=\'' . $id . '\''));
                        if ($samedata[1]['TITLE'] != $value) {

                            $validate_title = DBGet(DBQuery('SELECT COUNT(1) as TITLE_EX FROM attendance_codes WHERE SYEAR=' . UserSyear() . ' AND COLLEGE_ID=' . UserCollege() . ' AND TITLE=\'' . str_replace("'", "''", $value) . '\''));
                            if ($validate_title[1]['TITLE_EX'] != 0) {
                                echo "<div class=\"alert bg-warning alert-styled-left\">Unable to save data, because category title already exists.</div>";
                                $flag = 0;
                                break;
                            }
                        }
                    }
                    $sql .= $column . '=\'' . str_replace("'", "''", str_replace("\'", "''", trim($value))) . '\',';
                }
                $sql = substr($sql, 0, -1) . ' WHERE ID=\'' . $id . '\'';
                if ($flag != 0)
                    DBQuery($sql);
            }
            else {
                $sql = 'INSERT INTO attendance_codes ';
                $fields = 'COLLEGE_ID,SYEAR,TABLE_NAME,';
                $values = '\'' . UserCollege() . '\',\'' . UserSyear() . '\',\'' . $_REQUEST['table'] . '\',';


                $go = 0;
                $title = "";
                foreach ($columns as $column => $value) {
                    if (trim($value)) {
                        $fields .= $column . ',';
                        if ($column == 'TITLE' || $column == 'SHORT_NAME') {
                            $value = clean_param($value, PARAM_SPCL);
                            if ($column == 'TITLE')
                                $title = $value;
                        }
                        $values .= '\'' . str_replace("'", "''", str_replace("\'", "''", trim($value))) . '\',';
                        $go = true;
                    }
                }
                $sql .= '(' . substr($fields, 0, -1) . ') values(' . substr($values, 0, -1) . ')';
                $validate_title = DBGet(DBQuery('SELECT COUNT(1) as TITLE_EX FROM attendance_codes WHERE SYEAR=' . UserSyear() . ' AND COLLEGE_ID=' . UserCollege() . ' AND TITLE=\'' . singleQuoteReplace("", "", $title) . '\''));
                if ($validate_title[1]['TITLE_EX'] != 0 || $new_cat == 'Attendance') {
                    echo "<div class=\"alert bg-warning alert-styled-left\">Unable to save data, because title already exists.</div>";
                } else {

                    if ($go) {
                        DBQuery($sql);
                    }
                }
            }
        }   // Title validation ends to show error message add else after this line
    }
}

DrawBC("Attendance > " . ProgramTitle());

if ($_REQUEST['new_category_title'] && $_REQUEST['cat_edit_id'] == '') {

    $new_cat = optional_param('new_category_title', '', PARAM_SPCL);
    if ($new_cat) {
        $validate_title = DBGet(DBQuery('SELECT COUNT(1) as TITLE_EX FROM attendance_code_categories WHERE SYEAR=' . UserSyear() . ' AND COLLEGE_ID=' . UserCollege() . ' AND TITLE=\'' . singleQuoteReplace("", "", $new_cat) . '\''));
        if ($validate_title[1]['TITLE_EX'] != 0 || $new_cat == 'Attendance') {
            echo "<font color='red'><b>Unable to save data, because category title already exists.</b></font>";
        } else
            DBQuery('INSERT INTO attendance_code_categories (SYEAR,COLLEGE_ID,TITLE) values(\'' . UserSyear() . '\',\'' . UserCollege() . '\',\'' . singleQuoteReplace("", "", $new_cat) . '\')');

        // possible modification start
        $id = DBGet(DBQuery('SELECT max(ID) as ID from attendance_code_categories'));
        $id = $id[1]['ID'];
        $_REQUEST['table'] = $id;
    }
    else {
        echo "<div class=\"alert bg-warning alert-styled-left\">Unable to save data, because Special Charecters do not allow in Category Title</div>";
    }
    // possible modification end
}
if ($_REQUEST['new_category_title'] != '' && $_REQUEST['cat_edit_id'] != '') {
    $title = str_replace('"', '""', $_REQUEST['new_category_title']);
    $title = str_replace("'", "''", $title);
    $validate_title = DBGet(DBQuery('SELECT COUNT(1) as TITLE_EX FROM attendance_code_categories WHERE SYEAR=' . UserSyear() . ' AND COLLEGE_ID=' . UserCollege() . ' AND TITLE=\'' . $title . '\' AND ID!=' . $_REQUEST['cat_edit_id']));
    if ($validate_title[1]['TITLE_EX'] != 0 || $_REQUEST['new_category_title'] == 'Attendance') {
        echo "<div class=\"alert bg-warning alert-styled-left\">Unable to save data, because category title already exists.</div>";
    } else {
        DBQuery('UPDATE attendance_code_categories SET TITLE=\'' . $title . '\' WHERE ID=' . $_REQUEST['cat_edit_id']);
    }
}


if (optional_param('modfunc', '', PARAM_ALPHA) == 'remove') {
    if ($_REQUEST['id']) {



        $has_assigned_RET = DBGet(DBQuery('SELECT COUNT(*) AS TOTAL_ASSIGNED FROM attendance_period WHERE ATTENDANCE_CODE=\'' . optional_param('id', '', PARAM_INT) . '\''));
        $has_assigned = $has_assigned_RET[1]['TOTAL_ASSIGNED'];
    } else {
        $has_assigned = 0;
    }
    if ($has_assigned > 0) {
        UnableDeletePrompt('Cannot delete because attendance codes are associated.');
    } else {
        if ($_REQUEST['id']) {
            if (DeletePromptCommon('attendance code')) {


                DBQuery('DELETE FROM attendance_codes WHERE ID=\'' . optional_param('id', '', PARAM_INT) . '\'');
                unset($_REQUEST['modfunc']);
            }
        } elseif ($_REQUEST['table']) {
            if (DeletePromptCommon('category')) {
                DBQuery('DELETE FROM attendance_code_categories WHERE ID=\'' . $_REQUEST[table] . '\'');

                unset($_REQUEST['modfunc']);
                $_REQUEST['table'] = '0';
            }
        }
    }
}

if ($_REQUEST['modfunc'] != 'remove') {
    if ($_REQUEST['table'] !== 'new') {

        $sql = 'SELECT ID,TITLE,SHORT_NAME,TYPE,DEFAULT_CODE,STATE_CODE,SORT_ORDER,TABLE_NAME FROM attendance_codes WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\' AND TABLE_NAME=\'' . $_REQUEST['table'] . '\' ORDER BY SORT_ORDER,TITLE';


        $QI = DBQuery($sql);
        $LO = DBGet(DBQuery($sql));
        $attandance_id_arr = array();
        foreach ($LO as $ti => $td) {
            array_push($attandance_id_arr, $td[ID]);
        }
        $attandance_id = implode(',', $attandance_id_arr);
        $attendance_codes_RET = DBGet($QI, array('TITLE' => '_makeTextInput', 'SHORT_NAME' => '_makeTextInput', 'SORT_ORDER' => '_makeTextInput', 'TYPE' => '_makeSelectInput', 'STATE_CODE' => '_makeSelectInput', 'DEFAULT_CODE' => '_makeCheckBoxInput'));
    }

    $columns = array('TITLE' => 'Title', 'SHORT_NAME' => 'Short Name', 'SORT_ORDER' => 'Sort Order', 'TYPE' => 'Type', 'DEFAULT_CODE' => 'Default for Teacher and Office', 'STATE_CODE' => 'State Code');

    $link['add']['html'] = array('TITLE' => _makeTextInput('', 'TITLE'), 'SHORT_NAME' => _makeTextInput('', 'SHORT_NAME'), 'SORT_ORDER' => _makeTextInput('', 'SORT_ORDER', 'onkeydown=return numberOnlyMod(event,this);'), 'TYPE' => _makeSelectInput('', 'TYPE'), 'DEFAULT_CODE' => _makeCheckBoxInput('', 'DEFAULT_CODE'), 'STATE_CODE' => _makeSelectInput('', 'STATE_CODE'));
    $link['remove']['link'] = "Modules.php?modname=$_REQUEST[modname]&modfunc=remove";
    $link['remove']['variables'] = array('id' => 'ID', 'table' => 'TABLE_NAME');

    echo "<FORM class=\"form-horizontal\" name=F1 id=F1 action=Modules.php?modname=" . strip_tags(trim($_REQUEST[modname])) . "&modfunc=update&table=" . strip_tags(trim($_REQUEST[table])) . " method=POST>";

    echo '<input type="hidden" name="h1" id="h1" value="' . $attandance_id . '">';

    $tabs = array(array('title' => 'Attendance', 'link' => "Modules.php?modname=$_REQUEST[modname]&table=0"));
    $categories_RET = DBGet(DBQuery('SELECT ID,TITLE FROM attendance_code_categories WHERE SYEAR=\'' . UserSyear() . '\' AND COLLEGE_ID=\'' . UserCollege() . '\' ORDER BY TITLE'));
    foreach ($categories_RET as $category)
        $tabs[] = array('title' => $category['TITLE'], 'link' => "Modules.php?modname=$_REQUEST[modname]&table=" . $category['ID']);


    if ($_REQUEST['table'] === 'new')
        $tabs[] = array('title' => button('white_add'), 'link' => "Modules.php?modname=$_REQUEST[modname]&table=new");
    else
        $tabs[] = array('title' => button('add'), 'link' => "Modules.php?modname=$_REQUEST[modname]&table=new");

    $max_id = DBGet(DBQuery("select max(ID) maxid from attendance_codes"));
    $max_id = $max_id[1][MAXID];
    echo "<input type=hidden value=" . $max_id . " id=count >";
    if ($_REQUEST['table'] !== 'new' && $_REQUEST['modfunc'] != 'edit') {
        if (count($attendance_codes_RET) == 0) {
            $_openSIS['selected_tab'] = "Modules.php?modname=$_REQUEST[modname]&table=$_REQUEST[table]";
            echo "<div id='students' >";
            echo PopTable('header', $tabs);

            ListOutput($attendance_codes_RET, $columns, '', '', $link, array(), array('download' => false, 'search' => false));
            echo "</div>";
            if ($_REQUEST['table'] != 0) {
                echo '<div class="pt-15">' . button('edit', 'Edit category title', "Modules.php?modname=$_REQUEST[modname]&modfunc=edit&table=$_REQUEST[table]") . ' &nbsp;';
                echo button('remove', 'Delete this category', "Modules.php?modname=$_REQUEST[modname]&modfunc=remove&table=$_REQUEST[table]") . '</div>';
            }
            echo '<hr/>' . SubmitButton('Save', '', 'class="btn btn-primary" onclick="formcheck_attendance_codes();"');
            echo PopTable('footer');
        } else {

            PopTable_wo_header_attn_code('header', $tabs);
            ListOutput($attendance_codes_RET, $columns, '', '', $link, array(), array('download' => false, 'search' => false), '', false, false);
            $btn =  SubmitButton('Save', '', 'class="btn btn-primary" onclick="formcheck_attendance_codes();"');
            PopTable('footer', $btn);
        }
    } elseif ($_REQUEST['table'] == 'new' && $_REQUEST['modfunc'] != 'edit') {
        $_openSIS['selected_tab'] = "Modules.php?modname=$_REQUEST[modname]&table=$_REQUEST[table]";
        echo PopTable('header', $tabs);
        echo '<div class="form-group"><label class="control-label col-md-2">New Category Title</label><div class="col-md-3"><INPUT type=text placeholder="New Category Title" id=new_category_title name=new_category_title class="form-control"></div><div class="col-md-6">' . SubmitButton('Save', '', 'class="btn btn-primary" onclick="formcheck_attendance_category();"') . '</div></div>';
        echo PopTable('footer');
    } elseif ($_REQUEST['table'] !== 'new' && $_REQUEST['modfunc'] == 'edit') {
        $code_cat = DBGet(DBQuery('SELECT TITLE FROM attendance_code_categories WHERE id=' . $_REQUEST['table']));
        $code_cat = $code_cat[1]['TITLE'];
        $_openSIS['selected_tab'] = "Modules.php?modname=$_REQUEST[modname]&table=$_REQUEST[table]";
        echo PopTable('header', $tabs);
        echo '<CENTER>Category Title <INPUT type=text id=new_category_title name=new_category_title value="' . $code_cat . '"></CENTER>';
        echo '<input type=hidden name=cat_edit_id value=' . $_REQUEST['table'] . ' />';
        echo '<BR><CENTER>' . SubmitButton('Save', '', 'class="btn btn-primary" onclick="formcheck_attendance_category();"') . '</CENTER>';
        echo PopTable('footer');
    }
    echo '</FORM>';
}

function _makeTextInput($value, $name) {
    global $THIS_RET;
    if ($THIS_RET['ID'])
        $id = $THIS_RET['ID'];
    else
        $id = 'new';
    if ($name == 'TITLE' && $id == 'new')
        $extra = 'id=title';
    if ($name == 'SHORT_NAME')
        $extra = 'size=5 maxlength=5 class=form-control';
    if ($name == 'SORT_ORDER') {
        if ($id == 'new' || $THIS_RET['SORT_ORDER'] == '')
            $extra = 'size=5 maxlength=5 class=form-control onkeydown="return numberOnly(event);"';
        else
            $extra = 'size=5 maxlength=5 class=form-control onkeydown=\"return numberOnly(event);\"';
    }

    return TextInput($value, 'values[' . $id . '][' . $name . ']', '', $extra);
}

function _makeSelectInput($value, $name) {
    global $THIS_RET;

    if ($THIS_RET['ID'])
        $id = $THIS_RET['ID'];
    else
        $id = 'new';

    if ($name == 'TYPE') {
        if ($id == 'new')
            $allow_na = 'N/A';
        else
            $allow_na = false;
        $options = array('teacher' => 'Teacher & Office', 'official' => 'Office Only');
    }
    elseif ($name == 'STATE_CODE') {
        if ($id == 'new')
            $allow_na = 'N/A';
        else
            $allow_na = false;
        $options = array('P' => 'Present', 'A' => 'Absent', 'H' => 'Half');
    }

    return SelectInput($value, 'values[' . $id . '][' . $name . ']', '', $options, $allow_na);
}

function _makeCheckBoxInput($value, $name) {
    global $THIS_RET;

    if ($THIS_RET['ID'])
        $id = $THIS_RET['ID'];
    else {
        $id = 'new';
        $new = true;
    }

    return CheckBoxInput($value, 'values[' . $id . '][' . $name . ']', '', '', $new);
}

?>
