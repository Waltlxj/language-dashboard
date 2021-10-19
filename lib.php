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


function report_langdashboard_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('report/langdashboard:view', $context)) {
        $url = new moodle_url('/report/langdashboard/index.php', array('id'=>$course->id));
        $navigation->add(get_string('pluginname', 'report_langdashboard'), $url, navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
    }
}

/**
 * Callback to verify if the given instance of store is supported by this report or not.
 *
 * @param string $instance store instance.
 *
 * @return bool returns true if the store is supported by the report, false otherwise.
 */
function report_langdashboard_supports_logstore($instance) {
    // Use '\core\log\sql_select_reader' instead of '\core\log\sql_reader' in Moodle 2.7 and Moodle 2.8.
    if ($instance instanceof \core\log\sql_reader) {
        return true;
    }
    return false;
}




/**
 * Creates the action buttons (learning mode and groups) used on the report page.
 *
 * @param int $id The course id
 * @param int $student The student name (must not be "none")
 * @param int $level The quiz to look at (could be "none")
 * @param moodle_url $url The current page URL
 * @return string The generated HTML
 */
function display_action_buttons($student, $level, $url, $stdlist, $levellist) {
    global $OUTPUT;

    $studenturl = clone $url;
    $studenturl->params(array('level' => $level));
    $levelurl = clone $url;
    $levelurl->params(array('student' => $student));

    $select = new single_select($studenturl, 'student', $stdlist, $student);
    $select->label = "<b>Student Name</b>";
    $html = html_writer::start_tag('div');
    $html .= $OUTPUT->render($select);

    $html .= '&nbsp;&nbsp;&nbsp;&nbsp';

    $select = new single_select($levelurl, 'level', $levellist, $level);
    $select->label = "<b>Level</b>";
    $html .= $OUTPUT->render($select);
    $html .= html_writer::end_tag('div');
    return $html;
}


/**
 * Generate a list of subtopics grades (overview)
 * 
 * @return string html output to be printed
 */
function individual_overview($tagtable, $studentid){
    GLOBAL $DB;
    $question_set = $DB->get_records('question_grades', array('userid'=>$studentid));
    $tag_sets_by_quizzes = $tagtable[$studentid];
    $tag_sets_all = array();
    foreach ($tag_sets_by_quizzes as $tag_sets_by_quiz){
        foreach ($tag_sets_by_quiz as $qid=>$qtags){
            $tag_sets_all[$qid] = $qtags;
        }
    }

    //step 1, processing data into an array
    $scores = individual_data($question_set, $tag_sets_all);

    //step 2, display data
    $html = create_html($scores);
    return $html;
}


/**
 * Generate a list of subtopics grades (one quiz)
 * 
 * @return string html output to be printed
 */
function individual_onequiz($tagtable, $studentid, $quizid){
    GLOBAL $DB;
    $question_set = $DB->get_records('question_grades', array('userid'=>$studentid, 'quizid'=>$quizid));
    $tag_sets_all = $tagtable[$studentid][$quizid];

    //step 1, processing data into an array
    $scores = individual_data($question_set, $tag_sets_all);

    //step 2, display data
    $html = create_html($scores);
    return $html;
}

/**
 * Process data array into HTML file
 * 
 * @return string html output to be printed
 */
function create_html($scores){
    $html = html_writer::start_tag('div');
    //Grammar
    $html .= '<h5>Grammar</h5><ul>';
    foreach ($scores as $topic => $score){
        if ($topic[0] == 'g'){
            $html .= '<li>';
            $html .= '<b>'.substr($topic, 1).'</b>&nbsp;&nbsp;&nbsp;&nbsp'.number_format($score[0],1).'/'
                .number_format($score[1],1).' ('.number_format($score[0]/$score[1]*100,2).'%)';
            $html .= '</li>';
        }
    }
    $html .= '</ul>';

    //Vocabulary
    $html .= '<h5>Vocabulary</h5><ul>';
    foreach ($scores as $topic => $score){
        if ($topic[0] == 'v'){
            $html .= '<li>';
            $html .= '<b>'.substr($topic, 1).'</b>&nbsp;&nbsp;&nbsp;&nbsp'.number_format($score[0],1).'/'
                .number_format($score[1],1).' ('.number_format($score[0]/$score[1]*100,2).'%)';
            $html .= '</li>';
        }
    }
    $html .= '</ul>';

    //Discourse
    $html .= '<h5>Discourse</h5><ul>';
    foreach ($scores as $topic => $score){
        if ($topic[0] == 'd'){
            $html .= '<li>';
            $html .= '<b>'.substr($topic, 1).'</b>&nbsp;&nbsp;&nbsp;&nbsp'.number_format($score[0],1).'/'
                .number_format($score[1],1).' ('.number_format($score[0]/$score[1]*100,2).'%)';
            $html .= '</li>';
        }
    }
    $html .= '</ul>';

    //Comprehension
    $html .= '<h5>Comprehension</h5><ul>';
    foreach ($scores as $topic => $score){
        if ($topic[0] == 'c'){
            $html .= '<li>';
            $html .= '<b>'.substr($topic, 1).'</b>&nbsp;&nbsp;&nbsp;&nbsp'.number_format($score[0],1).'/'
                .number_format($score[1],1).' ('.number_format($score[0]/$score[1]*100,2).'%)';
            $html .= '</li>';
        }
    }
    $html .= '</ul>';

    $html .= html_writer::end_tag('div');
    return $html;
}


/**
 * Processing individual's data into an array
 * 
 * @return string an array that is ready to be output
 */
function individual_data($question_set, $tag_sets_all){

    $scores = array();
    foreach ($question_set as $question){//question level
        //Grammar
        if (stripos($question->categories, "grammar") !== false){
            foreach ($tag_sets_all[$question->questionid] as $tag){//tag level
                if (stripos($tag, "grammar") === false and stripos($tag, "level") === false ){
                    if (array_key_exists("g".$tag, $scores)){
                        $scores["g".$tag][0] += $question->grade;
                        $scores["g".$tag][1] += $question->maxgrade;
                    } else {
                        $scores["g".$tag] = array($question->grade, $question->maxgrade);
                    }
                }
            }
        }
        //Vocab
        if (stripos($question->categories, "vocab") !== false){
            foreach ($tag_sets_all[$question->questionid] as $tag){//tag level
                if (stripos($tag, "vocab") === false and stripos($tag, "level") === false){
                    if (array_key_exists("v".$tag, $scores)){
                        $scores["v".$tag][0] += $question->grade;
                        $scores["v".$tag][1] += $question->maxgrade;
                    } else {
                        $scores["v".$tag] = array($question->grade, $question->maxgrade);
                    }
                }
            }
        }
        //Discourse
        if (stripos($question->categories, "discourse") !== false){
            foreach ($tag_sets_all[$question->questionid] as $tag){//tag level
                if (stripos($tag, "discourse") === false and stripos($tag, "level") === false){
                    if (array_key_exists("d".$tag, $scores)){
                        $scores["d".$tag][0] += $question->grade;
                        $scores["d".$tag][1] += $question->maxgrade;
                    } else {
                        $scores["d".$tag] = array($question->grade, $question->maxgrade);
                    }
                }
            }
        }
        //Comprehension
        if (stripos($question->categories, "comprehension") !== false){
            foreach ($tag_sets_all[$question->questionid] as $tag){//tag level
                if (stripos($tag, "comprehension") === false and stripos($tag, "level") === false){
                    if (array_key_exists("c".$tag, $scores)){
                        $scores["c".$tag][0] += $question->grade;
                        $scores["c".$tag][1] += $question->maxgrade;
                    } else {
                        $scores["c".$tag] = array($question->grade, $question->maxgrade);
                    }
                }
            }
        }
    }
    return $scores;

}





// We don't need these funtions:

/* Function that takes subskills and loads them into Subskills table
 * returns subkill id
 */
function create_subskill($subskills) {
    global $DB;
    $subs = explode(', ', $subskills);
    foreach ($subs as $subskill) {
        if (!$thissubskill = $DB->get_record('langdashboard_subskills', array('subskillname'=>$subskill))) {
            $newsubskill->subskillname = $subskill;
            $thissubskill = $DB->insert_record('langdashboard_subskills', $newsubskill);
        }
    }
    // need to return array of subskills or only call this per subskill - figure out how this will work
    return;
}

 /* Function that gets the tags on a particular question,
  * detects Skills, Levels and Subskills
  * records Subskills into langdashboard table
  * returns object with Level, Skills and Subskills identified by id
  */
 function sort_tags($courseid) {
    global $DB;
    $skills = array();
    $skills = $DB->get_records('langdashboard_skills', array('courseid'=>$courseid));
    foreach ($skills as $thisskill) {
        switch ($thisskill->name) {
            case 'Level 1':
                $level = '1';
                break;
            case 'Level 2':
                $level = '2';
                break;
            case 'Level 3':
                $level = '3';
                break;
            case 'Level 4':
                $level = '4';
                break;
            case 'Level 5':
                $level = '5';
                break;
            case 'Level 6':
                $level = '6';
                break;
            case 'Level 7':
                $level = '7';
                break;
            case 'Level 8':
                $level = '8';
                break;
            case 'Grammar':
                $skill = '1';
                break;
            case 'Vocabulary':
                $skill = '2';
                break;
            case 'Discourse':
                $skill = '3';
                break;
            case 'Comprehension':
                $skill = '4';
                break;
            default:
                $subskill .= $thistag->tagname.', ';
        }
    }
    $questiontags->level = $level;
    $questiontags->skill = $skill;
    $questiontags->subskill = trim($subskill);
    
    return $questiontags;
 }

/*Function that loads all relevant question and skill (tags) data into the report tables
 *
 */

 function report_langdashboard_collect_questions($courseid) {
    global $DB;

   //get all quiz ids on this course page
    $quizids = $DB->get_records('quiz', array('course'=>$courseid), null, 'id');

    // Get all questions from the course categories in the course rather than from the quizzes themselves
    // can pull from questions_categories.contextid which gets context.instanceid that happens to be the courseid
    // when the contextlevel is 50.
    
    $contextid = $DB->get_field('context', 'id', array('contextlevel'=>50, 'instanceid'=>$courseid));
    $questioncategories = $DB->get_records('question_categories', array('contextid'=>$contextid), null, 'id, name');
    
    $questions = array();
    $categories = array();
    foreach($questioncategories as $category) {
        if ($category->name == 'top') {
            // this is a fake category in Moodle and it won't have any questions
            break;
        } else {
            $categories[] = $category->id;
        }
    }
    
    $catquestions = array();
    foreach($categories as $thiscategory) {
        $catquestions = $DB->get_records('question', array('category'=>$thiscategory), null, 'id');
        foreach($catquestions as $thisquestionid) {
            $questions[] = $thisquestionid->id;
        }
    }
    
    foreach($questions as $thisquestion=>$qid) {
        $untaggedquestions = array();
        $dashboardquestion = new stdClass();
        $dashboardquestion->questionid = $qid;
        $dashboardquestion->courseid = $courseid;
        $dashboardquestion->maxmark = $DB->get_field('quiz_slots', 'maxmark', array('questionid'=>$dashboardquestion->questionid));
        $tagids = $DB->get_records('tag_instance', array('itemtype'=>'question', 'itemid'=>$dashboardquestion->questionid));
        if (!empty($tagids)) {
            foreach($tagids as $thistagid) {
                // first find all the Levels and Big Four tags
                // and put them into a specific array, get them out of the list
                $thistag = $DB->get_field('tag', 'rawname', array('id'=>$thistagid->tagid));
                if (preg_match('/(L|l)evel.*/', $thistag)) {
                    $levels[] = $thistag;
                } elseif (preg_match('/(G|g)rammar.*/', $thistag)) {
                    $grammar = 'Grammar';
                } elseif (preg_match('/(V|v)ocab.*/', $thistag, $vocab)) {
                    $vocab = 'Vocabulary';
                } elseif (preg_match('/(D|d)iscourse.*/', $thistag, $discourse)) {
                    $discourse = 'Discourse';
                } elseif (preg_match('/(C|c)omprehension.*/', $thistag, $comprehension)) {
                    $comprehension = 'Comprehension';
                }
                // find a way to pull these tags out of the array now that they have been idenified
            }
            
            $newskill->courseid = $courseid;
            foreach($levels as $thislevel) {
                $number = $thislevel->rawname;
                preg_match('/\d/', $thislevel, $number);
                if ($number[0] == 1) {
                    //insert Level 1 record in db onl if it does not already exist
                    if (empty($DB->get_record('langdashboard_skills', array('skillname'=>'Level 1')))) {
                        $newskill->skillname = 'Level 1';
                        $DB->insert_record('langdashboard_skills', $newskill);   
                    }
                } elseif ($number[0] == 2) {
                    //insert Level 2 record in db
                    if (empty($DB->get_record('langdashboard_skills', array('skillname'=>'Level 2')))) {
                        $newskill->skillname = 'Level 2';
                        $DB->insert_record('langdashboard_skills', $newskill);
                    }
                } elseif ($number[0] == 3) {
                    if (empty($DB->get_record('langdashboard_skills', array('skillname'=>'Level 3')))) {
                        $newskill->skillname = 'Level 3';
                        $DB->insert_record('langdashboard_skills', $newskill);
                    }
                } elseif ($number[0] == 4) {
                    if (empty($DB->get_record('langdashboard_skills', array('skillname'=>'Level 4')))) {
                        $newskill->skillname = 'Level 4';
                        $DB->insert_record('langdashboard_skills', $newskill);
                    }
                } elseif ($number[0] == 5) {
                    if (empty($DB->get_record('langdashboard_skills', array('skillname'=>'Level 5')))) {
                        $newskill->skillname = 'Level 5';
                        $DB->insert_record('langdashboard_skills', $newskill);
                    }
                } elseif ($number[0] == 6) {
                    if (empty($DB->get_record('langdashboard_skills', array('skillname'=>'Level 6')))) {
                        $newskill->skillname = 'Level 6';
                        $DB->insert_record('langdashboard_skills', $newskill);
                    }
                } elseif ($number[0] == 7) {
                    if (empty($DB->get_record('langdashboard_skills', array('skillname'=>'Level 7')))) {
                        $newskill->skillname = 'Level 7';
                        $DB->insert_record('langdashboard_skills', $newskill);
                    }
                } elseif ($number[0] == 8) {
                    if (empty($DB->get_record('langdashboard_skills', array('skillname'=>'Level 8')))) {
                        $newskill->skillname = 'Level 8';
                        $DB->insert_record('langdashboard_skills', $newskill);
                    }
                } elseif ($number[0] == 9) {
                    if (empty($DB->get_record('langdashboard_skills', array('skillname'=>'Level 9')))) {
                        $newskill->skillname = 'Level 9';
                        $DB->insert_record('langdashboard_skills', $newskill);
                    }
                } elseif ($number[0] == 10) {
                    if (empty($DB->get_record('langdashboard_skills', array('skillname'=>'Level 10')))) {
                        $newskill->skillname = 'Level 10';
                        $DB->insert_record('langdashboard_skills', $newskill);
                    }
                }
            }
            // now create skill records for the Big Four
            // TODO: this is not detecting the grammar (or any other tags), need to figure out why
            if (IS_NULL($grammartag = $DB->get_record('langdashboard_skills', array('skillname' => 'Grammar', 'courseid' => $courseid)))) {
                $newskill->skillname = $grammar;
                $DB->insert_record('langdashboard_skills', $newskill);    
            } elseif (IS_NULL($vocabtag = $DB->get_record('langdashboard_skills', array('skillname'=>'Vocabulary', 'courseid' => $courseid)))) {
                $newskill->skillname = $vocab;
                $DB->insert_record('langdashboard_skills', $newskill);    
            } elseif (IS_NULL($vocabtag = $DB->get_record('langdashboard_skills', array('skillname'=>'Discourse', 'courseid' => $courseid)))) {
                $newskill->skillname = $discourse;
                $DB->insert_record('langdashboard_skills', $newskill);
            } elseif (IS_NULL($vocabtag = $DB->get_record('langdashboard_skills', array('skillname'=>'Comprehension', 'courseid' => $courseid)))) {
                $newskill->skillname = $comprehension;
                $DB->insert_record('langdashboard_skills', $newskill);
            }

            // NOW we can create skill records for each of the remaining tags
            if (empty($skillid = $DB->get_record('langdashboard_skills', array('courseid'=>$courseid, 'skillname'=>$newskill->skillname)))){
                $newskillid = $DB->insert_record('langdashboard_skills', $newskill);
                $dashboardquestion->skillid = $newskillid->id;
            } else {
                $dashboardquestion->skillid = $skillid->id;
            }
            // attach skill id to a question record and insert
            $newquestionid = $DB->insert_record('langdashboard_questions', $dashboardquestion);

        } else {
            $untaggedquestions[$dashboardquestion->questionid] = $dashboardquestion->questionid;              
        }
        unset($dashboardquestion);
    }
    
    if (!empty($untaggedquestions)) {
        echo '<h2>These questions did not have tags and will not be included in report results: ';
        echo $untaggedquestions;
        echo '</br>';
    }
    
    echo "<h2>All Skills Loaded!</h2>";

 }

/* Function that identifies all of the questions with the Big Four tags
 * so that we can calculate results for that set of questions for each student
*/
function find_grammar_questions($courseid) {
    global $DB;
    $grammarquestions = $DB->get_records('langdashboard_skills', array('skillid'=> 1, 'courseid'=>$courseid));
    
  
}

?>