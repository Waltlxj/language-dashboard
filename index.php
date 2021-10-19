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
require_once($CFG->libdir.'/enrollib.php');
require_once('lib.php');

GLOBAL $DB;

// page parameters, we probably don't need these:
//$page    = optional_param('page', 0, PARAM_INT);
//$perpage = optional_param('perpage', 30, PARAM_INT);    // how many per page
//$sort    = optional_param('sort', 'timemodified', PARAM_ALPHA);
//$dir     = optional_param('dir', 'DESC', PARAM_ALPHA);
$courseid = required_param('id', PARAM_INT);

// For individual lookup
$student_page = optional_param('student', "none", PARAM_TEXT);
$level_page = optional_param('level', "none", PARAM_TEXT);

$course = $DB->get_record('course', array('id' => $courseid), '*', MUST_EXIST);
$context = context_course::instance($courseid);


// Check permissions
require_login($course);

require_capability('report/langdashboard:view', $context);

// Get group mode
$group = groups_get_course_group($course, true); // Supposed to verify group
if ($group === 0 && $course->groupmode == SEPARATEGROUPS) {
    require_capability('moodle/site:accessallgroups',$context);
}

//Page setup
$PAGE->set_url('/report/langdashboard/index.php', array('id'=>$courseid));
$PAGE->set_pagelayout('report');
$returnurl = new moodle_url('/course/view.php', array('id'=>$courseid));

$pagetitle = get_string('langdashboard', 'report_langdashboard');
$PAGE->set_title($course->shortname .': '. $pagetitle);
$PAGE->set_heading($course->fullname);

//admin_externalpage_setup('reportlangdashboard', '', null, '', array('pagelayout'=>'report'));
echo $OUTPUT->header();
echo $OUTPUT->heading(get_string('langdashboard', 'report_langdashboard'));

// get ids of all quizzes in this course
$quizzes = $DB->get_records('quiz', array('course'=>$courseid), null, 'id');

// get all students in the course
$students = get_enrolled_users($context, 'mod/quiz:attempt', 0, 'u.id, u.firstname, u.lastname', 'u.lastname');



//Collecting data from quiz_attempts, question_attempts, and tag_instances into our question_grades database
$num_question_grades = 0;
$table_tags = array(array(array()));

foreach ($quizzes as $quiz_instance) {
    $attempts = $DB->get_records('quiz_attempts', array('quiz'=>$quiz_instance->id, 'state' => 'finished'));
    foreach ($attempts as $attempt_instance){
        //load courseid, quizid, userid, quizattemptid
        $qg_instance = new stdClass();
        $qg_instance->courseid = $courseid;
        $qg_instance->userid = $attempt_instance->userid;
        $qg_instance->quizid = $attempt_instance->quiz;
        $qg_instance->quizattemptid = $attempt_instance->uniqueid;

        //find all question attempts of given attempt
        $answers = $DB->get_records('question_attempts', array('questionusageid'=>$attempt_instance->uniqueid));

        //load all qg_instances after adding questionid, categoryid, category, maxgrade, and grade
        foreach ($answers as $qg){
            $qg_instance->questionid = $qg->questionid;
            $qg_instance->maxgrade = $qg->maxmark;
            //grades here do NOT care about partial credit, my apologies
            if ($qg->responsesummary === $qg->rightanswer){
                $qg_instance->grade = $qg->maxmark;
            } else {
                $qg_instance->grade = $qg->minfraction;
            }
            //Dealing with tags of a question, here I decided to add all tags to one string
            //Simulataneously, I created a table for tags arrays
            $tags = "";
            $tags_list = array();
            $tags_of_q = $DB->get_records('tag_instance', array('itemid'=>$qg->questionid, 'itemtype'=>'question'));
            foreach ($tags_of_q as $onetag){
                $tag_name_instance = $DB->get_field('tag', 'rawname', array('id'=>$onetag->tagid));
                $tags .= $tag_name_instance.", ";
                array_push($tags_list, $tag_name_instance);
            }
            $qg_instance->categories = $tags;

            $num_question_grades++;
            $qg_instance->id = $num_question_grades;

            //tags table
            $table_tags[$qg_instance->userid][$qg_instance->quizid][$qg->questionid] = $tags_list;

            //write qg_intance into db
            if ($DB->record_exists('question_grades', array('quizid'=>$qg_instance->quizid, 'quizattemptid'=>$qg_instance->quizattemptid, 'questionid'=>$qg_instance->questionid))){
                $DB->update_record('question_grades', $qg_instance);
            } else {
                $DB->insert_record('question_grades', $qg_instance);
            }
        }
    }
}

//load levels from tags
$levels = array();
$tags_data = $DB->get_records('tag');
foreach ($tags_data as $thistag) {
    $tag_name = $thistag->rawname;
    if (stripos($tag_name, "level") !== false){
        if (!(in_array($tag_name, $levels))){
            array_push($levels, $tag_name);
        }
    }
}
array_push($levels, "Aggregated");

// Aggregate question-answer instances into quiz instances, write into langdashboard_attempts db
$submissions = array();
$numofsubmissions = 0;
foreach ($quizzes as $thisquiz) {
    $submissions[$thisquiz->id] = $DB->get_records('quiz_attempts', array('quiz'=>$thisquiz->id, 'state' => 'finished'));
    $quizmaxscore = $DB-> get_field('quiz', 'sumgrades', array('id'=> $thisquiz->id));
    foreach ($submissions[$thisquiz->id] as $thisattempt) {

        $thisldattempt->courseid = $courseid;
        $thisldattempt->userid = $thisattempt->userid;
        $thisldattempt->quizid = $thisquiz->id;
        $thisldattempt->cummulativescore = number_format(($thisattempt->sumgrades / $quizmaxscore)*100, 2);

        // Getting scores for different tags
        $questionsInAttempt = $DB->get_records('question_grades', array('quizattemptid'=>$thisattempt->uniqueid));
        $grammar_score = $vocabulary_score = $discourse_score = $comprehension_score = array(0,0);
        foreach ($questionsInAttempt as $question) {
            if (strpos(strtolower($question->categories), "grammar") !==  false) {
              $grammar_score[0] += $question->grade;
              $grammar_score[1] += $question->maxgrade;
            }
            if (strpos(strtolower($question->categories), "vocabulary") !== false) {
              $vocabulary_score[0] += $question->grade;
              $vocabulary_score[1] += $question->maxgrade;
            }
            if (strpos(strtolower($question->categories), "discourse") !== false) {
              $discourse_score[0] += $question->grade;
              $discourse_score[1] += $question->maxgrade;
            }
            if (strpos(strtolower($question->categories), "comprehension") !== false) {
              $comprehension_score[0] += $question->grade;
              $comprehension_score[1] += $question->maxgrade;
            }
        }
        $thisldattempt->grammarscore = number_format(($grammar_score[0]/max($grammar_score[1], 0.00001))*100, 2);
        $thisldattempt->vocabscore = number_format(($vocabulary_score[0]/max($vocabulary_score[1], 0.00001))*100, 2);
        $thisldattempt->discoursescore = number_format(($discourse_score[0]/max($discourse_score[1], 0.00001))*100, 2);
        $thisldattempt->comprehensionscore = number_format(($comprehension_score[0]/max($comprehension_score[1], 0.00001))*100, 2);

        //Something's wrong here. It inserts new random copies even though records exist (I solved this; old codes commented out)
        $thisldattempt->id = $numofsubmissions + 1;
        if (!($DB->record_exists('langdashboard_attempts', array('courseid'=>$courseid, 'userid'=>$thisattempt->userid, 'quizid'=>$thisquiz->id)))){
        //if (empty($thisldattempt->id = $DB->record_exists('langdashboard_attempts', array('courseid'=>$courseid, 'userid'=>$thisattempt->userid, 'quizid'=>$thisquiz->id)))) {
            //$thisldattempt->id = 
            $DB->insert_record('langdashboard_attempts', $thisldattempt);
        } else {
            //$thisldattempt->id = 
            $DB->update_record('langdashboard_attempts', $thisldattempt);
        }
        ++$numofsubmissions;
    }
}

if ($student_page !== "none")  {
    $numofsubmissions = $DB->count_records('langdashboard_attempts', array('userid'=>$student_page));
}


// Make table to summarize student attempts
print '<br class="clearer"/>'; // not sure why we need this, copied from completion report

//get questions and tags and populate langdashboard_categories table
echo '<b>Total Number of Quiz Submissions: '. $numofsubmissions.'</b>';
echo '<br>';

// Create the overview table
print '<table id="language-dashboard-students" class="table table-bordered generaltable flexible boxaligncenter langdashboardstudents" style="text-align: left" cellpadding="5" border="1">';

//table header
print PHP_EOL.'<thead><tr style="vertical-align:top">';
echo '<th scope="row" class="rowheader">'.get_string('studentstableheading','report_langdashboard').'</th>';
echo '<th scope="row" class="rowheader">'.get_string('quizname', 'report_langdashboard');
echo '<th scope="row" class="rowheader">'.get_string('cummulativescore','report_langdashboard').'</th>';
echo '<th scope="row" class="rowheader">'.get_string('grammarscore','report_langdashboard').'</th>';
echo '<th scope="row" class="rowheader">'.get_string('vocabscore','report_langdashboard').'</th>';
echo '<th scope="row" class="rowheader">'.get_string('discoursescore','report_langdashboard').'</th>';
echo '<th scope="row" class="rowheader">'.get_string('comprehensionscore','report_langdashboard').'</th>';
print '</tr></thead>';
echo '<tbody>';

//change $students into one student during individual lookup
if ($student_page !== "none") {
    $thisstudent = $DB->get_record('user', array('id'=>$student_page));
    if ($thisstudentattempts = $DB->get_records('langdashboard_attempts', array('courseid'=>$courseid, 'userid'=>$thisstudent->id))) {
        $attemptcount = count($thisstudentattempts);
        if ($attemptcount > 1) {
            print PHP_EOL.'<tr id="user-'.$thisattempt->userid .'">';
            echo '<td rowspan='.$attemptcount.'>'. $thisstudent->lastname.', '.$thisstudent->firstname. '</td>';
            foreach ($thisstudentattempts as $thisattempt) {
                // print row for this attempt
                echo '<td>'. $DB->get_field('quiz', 'name', array('id'=>$thisattempt->quizid)).'</td>';
                echo '<td>'. $thisattempt->cummulativescore.' % </td>';
                echo '<td>'. $thisattempt->grammarscore.' % </td>';
                echo '<td>'. $thisattempt->vocabscore.' % </td>';
                echo '<td>'. $thisattempt->discoursescore.' % </td>';
                echo '<td>'. $thisattempt->comprehensionscore.' % </td></tr>';
            }
        } else {
            $thisattempt = array_shift($thisstudentattempts);
            print PHP_EOL.'<tr id="user-'.$thisattempt->userid . ':>';
            echo '<td>'.$thisstudent->lastname.', '.$thisstudent->firstname. '</td>';
            echo '<td>'. $DB->get_field('quiz', 'name', array('id'=>$thisattempt->quizid)).'</td>';
            echo '<td>'. $thisattempt->cummulativescore.' % </td>';
            echo '<td>'. $thisattempt->grammarscore.' % </td>';
            echo '<td>'. $thisattempt->vocabscore.' % </td>';
            echo '<td>'. $thisattempt->discoursescore.' % </td>';
            echo '<td>'. $thisattempt->comprehensionscore.' % </td></tr>';
        }
    }
} else {
    // get all attempts for this student in LD table, display below
    $attemptcount = 0;
    foreach ($students as $thisstudent) {
        if ($thisstudentattempts = $DB->get_records('langdashboard_attempts', array('courseid'=>$courseid, 'userid'=>$thisstudent->id))) {
            $attemptcount = count($thisstudentattempts);
            if ($attemptcount > 1) {
                print PHP_EOL.'<tr id="user-'.$thisattempt->userid .'">';
                echo '<td rowspan='.$attemptcount.'>'. $thisstudent->lastname.', '.$thisstudent->firstname. '</td>';
                foreach ($thisstudentattempts as $thisattempt) {
                    // print row for this attempt
                    echo '<td>'. $DB->get_field('quiz', 'name', array('id'=>$thisattempt->quizid)).'</td>';
                    echo '<td>'. $thisattempt->cummulativescore.' % </td>';
                    echo '<td>'. $thisattempt->grammarscore.' % </td>';
                    echo '<td>'. $thisattempt->vocabscore.' % </td>';
                    echo '<td>'. $thisattempt->discoursescore.' % </td>';
                    echo '<td>'. $thisattempt->comprehensionscore.' % </td></tr>';
                }
            } else {
                $thisattempt = array_shift($thisstudentattempts);
                print PHP_EOL.'<tr id="user-'.$thisattempt->userid . ':>';
                echo '<td>'.$thisstudent->lastname.', '.$thisstudent->firstname. '</td>';
                echo '<td>'. $DB->get_field('quiz', 'name', array('id'=>$thisattempt->quizid)).'</td>';
                echo '<td>'. $thisattempt->cummulativescore.' % </td>';
                echo '<td>'. $thisattempt->grammarscore.' % </td>';
                echo '<td>'. $thisattempt->vocabscore.' % </td>';
                echo '<td>'. $thisattempt->discoursescore.' % </td>';
                echo '<td>'. $thisattempt->comprehensionscore.' % </td></tr>';
            }
        }
    }
}

print '</tbody></table>';

echo '<br><br>';
//display individual student

//Individual look up


echo '<h3>Score Breakdown</h3>';
echo '<br>';

$studentnames_list_for_param = array();
foreach($students as $onestudent) {
    $studentname = $onestudent->lastname .', '.$onestudent->firstname;
    $studentnames_list_for_param[$onestudent->id] = $studentname;
}

//display buttons
echo display_action_buttons($student_page, $level_page, $PAGE->url, $studentnames_list_for_param, $levels);

if ($student_page !== "none") {
    echo '<br>';
    echo '<b>Showing: '.$studentnames_list_for_param[$student_page].' - ';
    if ($level_page === "none" or $levels[$level_page] === "Aggregated"){
        echo 'Aggregated</b>';
        echo '<br><br><br>';
        echo individual_overview($table_tags, $student_page);
    } else {
        echo 'Level '.($level_page+1).'</b>';
        echo '<br><br><br>';
        echo individual_onequiz($table_tags, $student_page, $level_page+1);
    }
}

echo '<br><br>';
//echo '<pre>Debugging: ' . $student_page . ', ' . $level_page.'</pre>';


echo $OUTPUT->footer();


?>
