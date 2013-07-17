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
 * Music Interval question definition class.
 *
 * @package     qtype
 * @subpackage  musicinterval
 * @copyright   &copy; 2009 Eric Brisson for Moodle 1.x and Flash Component
 * @author      ebrisson at winona.edu
 * @copyright   &copy; 2013 Jay Huber for Moodle 2.x
 * @author      jhuber at colum.edu
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/


defined('MOODLE_INTERNAL') || die();


class qtype_musicinterval_question extends question_graded_automatically {
	public $answers;

    public function get_expected_data() {
        return array('answer' => PARAM_CLEANHTML);
    }

    public function get_correct_response() {
		foreach ($this->answers as $v) {
			$q_answer = $v->answer;
			break;
		}
        return array('answer' => (string) $this->rightanswer);
    }

    public function summarise_response(array $response) {
        if (!array_key_exists('answer', $response)) {
            return null;
        } else {
			return $response['answer'];
		}
    }

    public function is_complete_response(array $response) {
        return !empty($response['answer']);
    }

    public function get_validation_error(array $response) {
        return;
    }

    public function is_same_response(array $prevresponse, array $newresponse) {
        return question_utils::arrays_same_at_key_missing_is_blank(
                $prevresponse, $newresponse, 'answer');
    }

    public function grade_response(array $response) {
        if ($this->rightanswer == $response['answer']) {
            $fraction = 1;
        } else {
            $fraction = 0;
        }
        return array($fraction, question_state::graded_state_for_fraction($fraction));
    }


}
