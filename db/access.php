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
 
defined('MOODLE_INTERNAL') || die;
$capabilities = array(
    'report/langdashboard:view' => array(
        'riskbitmask' => RISK_PERSONAL,
        'captype' => 'read',
        'contextlevel' => CONTEXT_COURSE,
        'archetypes' => array(
            'editingteacher' => CAP_ALLOW,
            'manager' => CAP_ALLOW
        ),
        'clonepermissionsfrom' => 'moodle/site:viewreports',
    )
);