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
 * @package    qtype
 * @subpackage musicinterval
 * @copyright  2013 Jay Huber (jhuber@colum.edu)
 * @copyright  2009 Eric Bisson (ebrisson@winona.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/musicinterval/question.php');

/**
 * The musicinterval question type.
 *
 * @copyright  2013 Jay Huber (jhuber@colum.edu)
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
 
class qtype_musicinterval extends question_type {
	
    function name() {
        return 'musicinterval';    
    }

    function extra_question_fields() {
        return array('question_musicinterval',
        'direction',        
        'quality',        
        'size',          
        'orignoteletter',  
        'orignoteaccidental',      
        'orignoteregister' ,
        'clef'    
        );
    }

    public function move_files($questionid, $oldcontextid, $newcontextid) {
        parent::move_files($questionid, $oldcontextid, $newcontextid);
        $this->move_files_in_hints($questionid, $oldcontextid, $newcontextid);
    }

    protected function delete_files($questionid, $contextid) {
        parent::delete_files($questionid, $contextid);
        $this->delete_files_in_hints($questionid, $contextid);
    }

	protected function initialise_question_instance(question_definition $question, $questiondata) {
        parent::initialise_question_instance($question, $questiondata);
		$answers = $questiondata->options->answers;
		foreach ($answers as $a) {
			$question->rightanswer = $a->answer;
			$question->feedback = $a->feedback;
			$question->feedbackformat = $a->feedbackformat;
			break;
		}
		$this->initialise_question_answers($question, $questiondata, false);
	}

    public function get_random_guess_score($questiondata) {
        // TODO.
        return 0;
    }

    public function get_possible_responses($questiondata) {
        // TODO.
        return array();
    }
	
    function save_question_options($question) {
        $this->save_question_answers($question);
		
        if($res=parent::save_question_options($question)) {
            return $res;
        } else {
            return true;
        }
    }

    function save_question_answers($question) {
		global $DB;
        $result = new stdClass;
        $context = $question->context;

        // Get all the old answers from the database as an array
		$conditions = array("question" => $question->id);
		$answers = $DB->get_records("question_answers", $conditions);
        if (!$oldanswers = $answers) {
            $oldanswers = array();
        }

        // Create an array of the answer IDs for the question
        $answers = array();
        // Set the maximum answer fraction to be -1. We will check this at the end of our
        // loop over the questions and if it is not 100% (=1.0) then we will flag an error
        $maxfraction = -1;
		
        // Loop over all the answers in the question form and write them to the database
        foreach ($question->answer as $key => $dataanswer) {
            // Check to see that there is an answer and skip any which are empty
            if ($dataanswer == '') {
                continue;
            }
            // Get the old answer from the array and overwrite what is required, if there 
            if ($oldanswer = array_shift($oldanswers)) {  // Existing answer, so reuse it
                $answer = $oldanswer;
                $answer->answer   = trim($dataanswer);
                $answer->fraction = $question->fraction[$key];
	            $answer->feedback = $this->import_or_save_files($question->feedback[$key],
	                    $context, 'question', 'answerfeedback', $answer->id);
	            $answer->feedbackformat = $question->feedback[$key]['format'];

                // Update the record in the database table
                if (!$DB->update_record('question_answers', $answer)) {
                    throw new Exception("Could not update quiz answer! (id=$answer->id)");
                }
            }  else {
	            // This is a completely new answer so we have to create a new record
                $answer = new stdClass;
                $answer->answer   = trim($dataanswer);
                $answer->question = $question->id;
	            $answer->fraction = '';
	            $answer->feedback = '';

                // Insert a new record into the database table
                if (!$answer->id = $DB->insert_record('question_answers', $answer)) {
                    throw new Exception('Could not insert quiz answer!');
                }
            }

			//Add this to the answer
			$answer->fraction = $question->fraction[$key];
            $answer->feedback = $this->import_or_save_files($question->feedback[$key],
                    $context, 'question', 'answerfeedback', $answer->id);
            $answer->feedbackformat = $question->feedback[$key]['format'];
			$DB->update_record('question_answers', $answer);

            // Add the answer ID to the array of IDs
            $answers[] = $answer->id;

            // Increase the value of the maximum grade fraction if needed
            if ($question->fraction[$key] > $maxfraction) {
                $maxfraction = $question->fraction[$key];
            }
        }     // end loop over answers
		
        // Perform sanity check on the maximum fractional grade which should be 100%
        if ($maxfraction != 1) {
            $maxfraction = $maxfraction * 100;
            throw new Exception(get_string('fractionsnomax', 'quiz', $maxfraction));
        }
		
        // Finally we are all done so return the result!
        return true;
    }
	
}



