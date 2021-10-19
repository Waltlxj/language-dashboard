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

/**
 * Language Dashboard report renderer.
 *
 * @package    report_langdashboard
 * @copyright  2018 Carly J. Born
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
defined('MOODLE_INTERNAL') || die;

/**
 * Report Language Dashboard renderer's for printing reports.
 *
 * @package    report_langdashboard
 * @copyright  2018 Carly J. Born
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class report_langdashboard_renderer extends plugin_renderer_base {

    /**
     * Render Language Dashboard report page.
     *
     * @param report_langdashboard_renderable $reportlangdashboard object of report_langdashboard.
     */
    protected function render_report_langdashboard(report_langdashboard_renderable $reportlangdashboard) {
        if (empty($reportlangdashboard->selectedlogreader)) {
            echo $this->output->notification(get_string('nologreaderenabled', 'report_langdashboard'), 'notifyproblem');
            return;
        }
        if ($reportlangdashboard->showselectorform) {
            $this->report_selector_form($reportlangdashboard);
        }

        if ($reportlangdashboard->showreport) {
            $reportlangdashboard->tablelog->out($reportlangdashboard->perpage, true);
        }
    }

    /**
     * Prints/return reader selector
     *
     * @param report_langdashboard_renderable $reportlangdashboard report.
     */
    public function reader_selector(report_langdashboard_renderable $reportlangdashboard) {
        $readers = $reportlangdashboard->get_readers(true);
        if (empty($readers)) {
            $readers = array(get_string('nologreaderenabled', 'report_langdashboard'));
        }
        $url = fullclone ($reportlangdashboard->url);
        $url->remove_params(array('logreader'));
        $select = new single_select($url, 'logreader', $readers, $reportlangdashboard->selectedlogreader, null);
        $select->set_label(get_string('selectlogreader', 'report_langdashboard'));
        echo $this->output->render($select);
    }

    /**
     * This function is used to generate and display selector form
     *
     * @param report_langdashboard_renderable $reportlangdashboard log report.
     */
    public function report_selector_form(report_langdashboard_renderable $reportlangdashboard) {
        echo html_writer::start_tag('form', array('class' => 'logselecform', 'action' => $reportlangdashboard->url, 'method' => 'get'));
        echo html_writer::start_div();
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'chooselog', 'value' => '1'));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'showusers', 'value' => $reportlangdashboard->showusers));
        echo html_writer::empty_tag('input', array('type' => 'hidden', 'name' => 'showcourses',
            'value' => $reportlangdashboard->showcourses));

        $selectedcourseid = empty($reportlangdashboard->course) ? 0 : $reportlangdashboard->course->id;

        // Add course selector.
        $sitecontext = context_system::instance();
        $courses = $reportlangdashboard->get_course_list();
        if (!empty($courses) && $reportlangdashboard->showcourses) {
            echo html_writer::label(get_string('selectacourse'), 'menuid', false, array('class' => 'accesshide'));
            echo html_writer::select($courses, "id", $selectedcourseid, null);
        } else {
            $courses = array();
            $courses[$selectedcourseid] = get_course_display_name_for_list($reportlangdashboard->course) . (($selectedcourseid == SITEID) ?
                ' (' . get_string('site') . ') ' : '');
            echo html_writer::label(get_string('selectacourse'), 'menuid', false, array('class' => 'accesshide'));
            echo html_writer::select($courses, "id", $selectedcourseid, false);
            // Check if user is admin and this came because of limitation on number of courses to show in dropdown.
            if (has_capability('report/log:view', $sitecontext)) {
                $a = new stdClass();
                $a->url = new moodle_url('/report/log/index.php', array('chooselog' => 0,
                    'group' => $reportlangdashboard->get_selected_group(), 'user' => $reportlangdashboard->userid,
                    'id' => $selectedcourseid, 'date' => $reportlangdashboard->date, 'modid' => $reportlangdashboard->modid,
                    'showcourses' => 1, 'showusers' => $reportlangdashboard->showusers));
                $a->url = $a->url->out(false);
                print_string('logtoomanycourses', 'moodle', $a);
            }
        }

        // Add group selector.
        $groups = $reportlangdashboard->get_group_list();
        if (!empty($groups)) {
            echo html_writer::label(get_string('selectagroup'), 'menugroup', false, array('class' => 'accesshide'));
            echo html_writer::select($groups, "group", $reportlangdashboard->groupid, get_string("allgroups"));
        }

        // Add user selector.
        $users = $reportlangdashboard->get_user_list();

        if ($reportlangdashboard->showusers) {
            echo html_writer::label(get_string('selctauser'), 'menuuser', false, array('class' => 'accesshide'));
            echo html_writer::select($users, "user", $reportlangdashboard->userid, get_string("allparticipants"));
        } else {
            $users = array();
            if (!empty($reportlangdashboard->userid)) {
                $users[$reportlangdashboard->userid] = $reportlangdashboard->get_selected_user_fullname();
            } else {
                $users[0] = get_string('allparticipants');
            }
            echo html_writer::label(get_string('selctauser'), 'menuuser', false, array('class' => 'accesshide'));
            echo html_writer::select($users, "user", $reportlangdashboard->userid, false);
            $a = new stdClass();
            $a->url = new moodle_url('/report/log/index.php', array('chooselog' => 0,
                'group' => $reportlangdashboard->get_selected_group(), 'user' => $reportlangdashboard->userid,
                'id' => $selectedcourseid, 'date' => $reportlangdashboard->date, 'modid' => $reportlangdashboard->modid,
                'showusers' => 1, 'showcourses' => $reportlangdashboard->showcourses));
            $a->url = $a->url->out(false);
            echo html_writer::start_span('m-x-1');
            print_string('logtoomanyusers', 'moodle', $a);
            echo html_writer::end_span();
        }

        // Add date selector.
        $dates = $reportlangdashboard->get_date_options();
        echo html_writer::label(get_string('date'), 'menudate', false, array('class' => 'accesshide'));
        echo html_writer::select($dates, "date", $reportlangdashboard->date, get_string("alldays"));

        // Add activity selector.
        $activities = $reportlangdashboard->get_activities_list();
        echo html_writer::label(get_string('activities'), 'menumodid', false, array('class' => 'accesshide'));
        echo html_writer::select($activities, "modid", $reportlangdashboard->modid, get_string("allactivities"));

        // Add actions selector.
        echo html_writer::label(get_string('actions'), 'menumodaction', false, array('class' => 'accesshide'));
        echo html_writer::select($reportlangdashboard->get_actions(), 'modaction', $reportlangdashboard->action, get_string("allactions"));

        // Add origin.
        $origin = $reportlangdashboard->get_origin_options();
        echo html_writer::label(get_string('origin', 'report_langdashboard'), 'menuorigin', false, array('class' => 'accesshide'));
        echo html_writer::select($origin, 'origin', $reportlangdashboard->origin, false);

        // Add edulevel.
        $edulevel = $reportlangdashboard->get_edulevel_options();
        echo html_writer::label(get_string('edulevel'), 'menuedulevel', false, array('class' => 'accesshide'));
        echo html_writer::select($edulevel, 'edulevel', $reportlangdashboard->edulevel, false).$this->help_icon('edulevel');

        // Add reader option.
        // If there is some reader available then only show submit button.
        $readers = $reportlangdashboard->get_readers(true);
        if (!empty($readers)) {
            if (count($readers) == 1) {
                $attributes = array('type' => 'hidden', 'name' => 'logreader', 'value' => key($readers));
                echo html_writer::empty_tag('input', $attributes);
            } else {
                echo html_writer::label(get_string('selectlogreader', 'report_langdashboard'), 'menureader', false,
                        array('class' => 'accesshide'));
                echo html_writer::select($readers, 'logreader', $reportlangdashboard->selectedlogreader, false);
            }
            echo html_writer::empty_tag('input', array('type' => 'submit', 'value' => get_string('gettheselogs'),
                'class' => 'btn btn-secondary'));
        }
        echo html_writer::end_div();
        echo html_writer::end_tag('form');
    }
}

