<?php
// This report is a plugin created to display quiz results by tag for cummulative review.

/**
 * Config changes report
 *
 * @package    report
 * @subpackage langdashboard
 * @copyright  2018 Carly J Born
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

 //not sure if I need these libraries, but probably do
require(__DIR__.'/../../config.php');
require_once($CFG->libdir.'/adminlib.php');
require_once('lib.php');
GLOBAL $DB;

// page parameters
$page    = optional_param('page', 0, PARAM_INT);
$perpage = optional_param('perpage', 30, PARAM_INT);    // how many per page
$sort    = optional_param('sort', 'timemodified', PARAM_ALPHA);
$dir     = optional_param('dir', 'DESC', PARAM_ALPHA);

$courseid = required_param('id', PARAM_INT);
$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($courseid);

// Check permissions
require_login($course);

// Get group mode
$group = groups_get_course_group($course, true); // Supposed to verify group
if ($group === 0 && $course->groupmode == SEPARATEGROUPS) {
    require_capability('moodle/site:accessallgroups',$context);
}

$PAGE->set_pagelayout('report');

$url = new moodle_url('/report/langdashboard/levels.php', array('course'=>$courseid));
$PAGE->set_url($url);

$pagetitle = get_string('levelpagetitle', 'report_langdashboard');
$PAGE->set_title($course->shortname .': '. $pagetitle);
$PAGE->set_heading($course->fullname);
$PAGE->navbar->add(get_string('langdashboard', 'report_langdashboard'));
$PAGE->navbar->add($pagetitle);

//admin_externalpage_setup('reportlangdashboard', '', null, '', array('pagelayout'=>'report'));
echo $OUTPUT->header();

echo $OUTPUT->heading(get_string('langdashboard', 'report_langdashboard'));

echo '<a href="'.$CFG->wwwroot.'/report/langdashboard/index.php?id='.$courseid.'">Back to Language Dashboard Report</a>';

// use this page to assign skills to each of the category levels

if (array_key_exists('loadcategories',$_POST)){
    
}

report_langdashboard_collect_questions($courseid);

echo '<br><br>';
echo '<h2>Categories</h2>';
$categories = $DB->get_records('langdashboard_categories', array('courseid'=>$courseid));
var_dump($categories);

echo 'Use this page to assign skills to the higher level categories.<br>';


// start table
print '<table id="language-dashboard-levels" class="table table-bordered generaltable flexible boxaligncenter langdashboardlevels" style="text-align: left" cellpadding="5" border="1">';

//table header
print PHP_EOL.'<thead><tr style="vertical-align:top">';
echo '<th scope="row" class="rowheader">'.get_string('quizname', 'report_langdashboard');
echo '<th scope="row" class="rowheader">'.get_string('grammarcatheader','report_langdashboard').'</th>';
echo '<th scope="row" class="rowheader">'.get_string('vocabcatheader','report_langdashboard').'</th>';
echo '<th scope="row" class="rowheader">'.get_string('discoursecatheader','report_langdashboard').'</th>';
echo '<th scope="row" class="rowheader">'.get_string('comprehensioncatheader','report_langdashboard').'</th>';
print '</tr></thead>';
echo '<tbody>';


foreach ($categories as $cat) {
    print PHP_EOL.'<tr id="user-'.$cat->categoryname .'">';
    echo '<td>'.$cat->categoryname.'</td>';
    echo '<td><input type="checkbox" name="grammar" value="'.$cat->id.'"></td>';
    echo '<td><input type="checkbox" name="vocab" value="'.$cat->id.'"></td>';
    echo '<td><input type="checkbox" name="discourse" value="'.$cat->id.'"></td>';
    echo '<td><input type="checkbox" name="comprehension" value="'.$cat->id.'"></td></tr>';
}


print '</tbody></table>';



    echo $OUTPUT->footer();