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
 * Question type class for the Music Interval question type.
 *
 * @package     qtype
 * @subpackage  musicinterval
 * @copyright   &copy; 2009 Eric Brisson for Moodle 1.x and Flash Component
 * @author      ebrisson at winona.edu
 * @copyright   &copy; 2012 Jay Huber for Moodle 2.x
 * @author      jhuber at colum.edu
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
*/


defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/question/type/musicinterval/question.php');

/**
 * The calculated question type.
 *
 * @copyright  1999 onwards Martin Dougiamas {@link http://moodle.com}
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_musicinterval extends question_type {
	
    /**
    * Overriden function. See comments from base class.
    */
    function name() {
        return 'musicinterval';    
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
	
    /// QUESTION OPTIONS /////////////////
	
    /**
    * Overriden function. See comments from base class.
    */
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
	
    /**
    * Overriden function. See comments from base class.
    * 
    * This implementation saves question answers before calling the parent function.
    * 
    */
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
	



	
	
    /// QUESTION VALIDATION /////////////////
	
    /**
    * Overriden function. See comments from base class.
    */
    function check_response(&$question, &$state){
echo "check_response<br />";	
        foreach($question->options->answers as $aid => $answer) {
            if ($this->test_response($question, $state, $answer)) {
                return $aid;
            }
        }
        return false;
    }
	
	
    /// PRINTING /////////////////
	
    /**
    * Overriden function. See comments from base class.
    */
    function print_question_formulation_and_controls(&$question, &$state, $cmoptions, $options) {
echo "print_question_formulation_and_controls<br />";		
        global $CFG;
		
        $readonly = empty($options->readonly) ? '' : 'readonly="readonly"';
        $formatoptions = new stdClass;
        $formatoptions->noclean = true;
        $formatoptions->para = false;
        $nameprefix = $question->name_prefix;
		
        /// Print question text and media
		
        $questiontext = format_text($question->questiontext,
        $question->questiontextformat,
        $formatoptions, $cmoptions->course);
        $image = get_question_image($question);
		
        /// Print input controls
		
        if (isset($state->responses['']) && $state->responses[''] != '') {
            $value = ' value="'.s($state->responses[''], true).'" ';
            } else {
            $value = ' value="" ';
        }
        $inputname = ' name="'.$nameprefix.'" ';
		
        $feedback = '';
        $class = '';
        $feedbackimg = '';
		
        if ($options->feedback) {
            $class = question_get_feedback_class(0);
            $feedbackimg = question_get_feedback_image(0);
            foreach($question->options->answers as $answer) {
				
                if ($this->test_response($question, $state, $answer)) {
                    // Answer was correct or partially correct.
                    $class = question_get_feedback_class($answer->fraction);
                    $feedbackimg = question_get_feedback_image($answer->fraction);
                    if ($answer->feedback) {
                        $feedback = format_text($answer->feedback, true, $formatoptions, $cmoptions->course);
                    }
                    break;
                }
            }
        }
		
        include("$CFG->dirroot/question/type/musicinterval/display.html");
    }
	
    /**
    * Overriden function. See comments from base class.
    * 
    * This implementation prints the correct answer.
    */
    function print_question_grading_details(&$question, &$state, $cmoptions, $options) {
echo "print_question_grading_details<br />";
        /* The default implementation prints the number of marks if no attempt
        has been made. Otherwise it displays the grade obtained out of the
        maximum grade available and a warning if a penalty was applied for the
        attempt and displays the overall grade obtained counting all previous
        responses (and penalties) */
        global $QTYPES ;
        // MDL-7496 show correct answer after "Incorrect"
        $correctanswer = '';
        if ($correctanswers =  $QTYPES[$question->qtype]->get_correct_responses($question, $state)) {
            if ($options->readonly && $options->correct_responses) {
                $delimiter = '';
                if ($correctanswers) {
                    foreach ($correctanswers as $ca) {
                        $correctanswer .= $delimiter.$ca;
                        $delimiter = ', ';
                    }
                }
            }
        }
		
        if (QUESTION_EVENTDUPLICATE == $state->event) {
            echo ' ';
            print_string('duplicateresponse', 'quiz');
        }
        if (!empty($question->maxgrade) && $options->scores) {
            if (question_state_is_graded($state->last_graded)) {
                // Display the grading details from the last graded state    
                $grade = new stdClass;
                $grade->cur = round($state->last_graded->grade, $cmoptions->decimalpoints);
                $grade->max = $question->maxgrade;
                $grade->raw = round($state->last_graded->raw_grade, $cmoptions->decimalpoints);
				
                // let student know wether the answer was correct
                echo '<div class="correctness ';
                if ($state->last_graded->raw_grade >= $question->maxgrade/1.01) { // We divide by 1.01 so that rounding errors dont matter.
                echo ' correct">';
                print_string('correct', 'quiz');
			} else if ($state->last_graded->raw_grade > 0) {
                echo ' partiallycorrect">';
                print_string('partiallycorrect', 'quiz');
                // MDL-7496
                if ($correctanswer) {    
                    echo ('<div class="correctness">');
                    print_string('correctansweris', 'quiz', s($correctanswer, true));
                    echo ('</div>');
                }
                } else {
                    echo ' incorrect">';
                    // MDL-7496
                    print_string('incorrect', 'quiz');
                    if ($correctanswer) {
                        echo ('<div class="correctness">');
                        print_string('correctansweris', 'quiz', s($correctanswer, true));
                        echo ('</div>');
                    }
                }
                echo '</div>';
				
                echo '<div class="gradingdetails">';
                // print grade for this submission
                print_string('gradingdetails', 'quiz', $grade);
                if ($cmoptions->penaltyscheme) {
                    // print details of grade adjustment due to penalties
                    if ($state->last_graded->raw_grade > $state->last_graded->grade){
                        echo ' ';
                        print_string('gradingdetailsadjustment', 'quiz', $grade);
                    }
                    // print info about new penalty
                    // penalty is relevant only if the answer is not correct and further attempts are possible
                    if (($state->last_graded->raw_grade < $question->maxgrade) and (QUESTION_EVENTCLOSEANDGRADE != $state->event)) {
                        if ('' !== $state->last_graded->penalty && ((float)$state->last_graded->penalty) > 0.0) {
                            // A penalty was applied so display it
                            echo ' ';
                            print_string('gradingdetailspenalty', 'quiz', $state->last_graded->penalty);
                            } else {
                            /* No penalty was applied even though the answer was
                            not correct (eg. a syntax error) so tell the student
                            that they were not penalised for the attempt */
                            echo ' ';
                            print_string('gradingdetailszeropenalty', 'quiz');
                        }
                    }
                }
                echo '</div>';
            }    
        }
    }
	
	

	
	
    /// BACKUP - RESTORE /////////////////
	
    /**
    * Backup the data in the question to a backup file.
    * 
    * Overriden function.
    *
    * This function is used by question/backuplib.php to create a copy of the data
    * in the question so that it can be restored at a later date. It also uses the 
    * question_backup_answers function from question/backuplib.php to backup all the 
    * question answers.
    * 
    * It originally comes from the algebra question type.
    *
    * @param $bf the backup file to write the information to
    * @param $preferences backup preferences in effect (not used)
    * @param $question the ID number of the question being backed up
    * @param $level the indentation level of the data being written
    * 
    * @return bool true if the backup was successful, false if it failed.
    */
    function backup($bf,$preferences,$question,$level=6) {
echo "backup<br />";
        // Set the devault return value, $status, to be true
        $status = true;
        $intervalqs = get_records('question_musicinterval','questionid',$question,'id ASC');
        // If there are musicinterval questions
        if ($intervalqs) {
            // Iterate over each interval question
            foreach ($intervalqs as $interval) {
                $status = $status && fwrite ($bf,start_tag('INTERVAL',$level,true));
                // Print musicinterval question contents
                fwrite ($bf,full_tag('DIRECTION',    $level+1, false, $interval->direction    ));
                fwrite ($bf,full_tag('QUALITY',    $level+1, false, $interval->quality    ));
                fwrite ($bf,full_tag('SIZE',      $level+1, false, $interval->size      ));
                fwrite ($bf,full_tag('ORIGNOTELETTER',    $level+1, false, $interval->orignoteletter    ));
                fwrite ($bf,full_tag('ORIGNOTEACCIDENTAL', $level+1, false, $interval->orignoteaccidental ));
                fwrite ($bf,full_tag('ORIGNOTEREGISTER',     $level+1, false, $interval->orignoteregister     ));
                fwrite ($bf,full_tag('CLEF',     $level+1, false, $interval->clef     ));
                // End interval data
                $status = $status && fwrite ($bf,end_tag('INTERVAL',$level,true));
            }
            // Backup the answers
            $status = $status && question_backup_answers($bf,$preferences,$question);
        }
        return $status;
    }
	
    /**
    * Restores the data in a backup file to produce the original question.
    *
    * Overriden function.
    * 
    * This method is used by question/restorelib.php to restore questions saved in
    * a backup file to the database. It reads the file directly and writes the information
    * straight into the database.
    *
    * @param $old_question_id the original ID number of the question being restored
    * @param $new_question_id the new ID number of the question being restored
    * @param $info the XML parse tree containing all the restore information
    * @param $restore information about the current restore in progress
    * 
    * @return bool true if the backup was successful, false if it failed.
    */
    function restore($old_question_id,$new_question_id,$info,$restore) {
echo "restore<br />";
        // Set the devault return value, $status, to be true
        $status = true;
        // Get the array of interval questions
        $intervalqs = $info['#']['INTERVAL'];
        // Iterate over the interval questions in the backup data
        for($i=0; $i<sizeof($intervalqs); $i++) {
            $int_info = $intervalqs[$i];
            // Create an empty class to store the question's database record
            $interval = new stdClass;
            // Fill the interval specific variables for this object
            $interval->questionid   = $new_question_id;
            $interval->direction    = backup_todb($int_info['#']['DIRECTION']['0']['#']);
            $interval->quality    = backup_todb($int_info['#']['QUALITY']['0']['#']);
            $interval->size      = backup_todb($int_info['#']['SIZE']['0']['#']);
            $interval->orignoteletter    = backup_todb($int_info['#']['ORIGNOTELETTER']['0']['#']);
            $interval->orignoteaccidental = backup_todb($int_info['#']['ORIGNOTEACCIDENTAL']['0']['#']);
            $interval->orignoteregister     = backup_todb($int_info['#']['ORIGNOTEREGISTER']['0']['#']);
            $interval->clef     = backup_todb($int_info['#']['CLEF']['0']['#']);
			
            // The structure is now equal to the db, so insert the question_musicinterval object
            // and check the the database insert call worked
            if (!insert_record('question_musicinterval',$interval)) {
                echo get_string('qtype_musicinterval','restoreqdbfailed'),"\n";
                $status = false;
            }
            // Generate output so that the user can see questions being restored
            if (($i+1) % 50 == 0) {
                if (!defined('RESTORE_SILENTLY')) {
                    echo ".";
                    if (($i+1) % 1000 == 0) {
                        echo "<br />";
                    }
                }
                backup_flush(300);
            }
        }
        return $status;
    }
	


    /// IMPORT - EXPORT /////////////////
	
    /**
    * Imports the question from Moodle XML format.
    *
    * This method is called by the format class when importing an algebra question from the 
    * Moodle XML format.
    * 
    * It originally comes from the algebra question type.
    *
    * @param $data structure containing the XML data
    * @param $question question object to fill: ignored by this function (assumed to be null)
    * @param $format format class exporting the question
    * @param $extra extra information (not required for importing this question in this format)
    * @return text string containing the question data in XML format
    */
//    function import_from_xml(&$data,&$question,&$format,&$extra) {
//echo "import_from_xml<br />";
		
        // return if data not from an interval question
//        $qtype = $data['@']['type'];
//        if ($qtype != "interval") {
//            return false;
//		}
		
	    // Import the common question headers
//        $qo = $format->import_headers($data);
        
        // Set the question type
//        $qo->qtype=$qtype;
//        $qo->direction = $format->getpath($data, array('#','direction',0,'#'),'', true);
//        $qo->quality  = $format->getpath($data, array('#','quality',0,'#'),'',true);
//        $qo->size   = $format->getpath($data, array('#','size',0,'#'),'');
//        $qo->orignoteletter  = $format->getpath($data, array('#','orignoteletter',0,'#'),'',true);
//        $qo->orignoteaccidental  = $format->getpath($data, array('#','orignoteaccidental',0,'#'),'',true);
//        $qo->orignoteregister  = $format->getpath($data, array('#','orignoteregister',0,'#'),'',true);
//        $qo->clef  = $format->getpath($data, array('#','clef',0,'#'),'',true);
        
        // Import all the answers
//        $answers = $data['#']['answer'];
//        $a_count = 0;
        // Loop over each answer block found in the XML
//        foreach($answers as $answer) {
        // Use the common answer import function in the format class to load the data
//            $ans = $format->import_answer($answer);
//            $qo->answer[$a_count] = $ans->answer;
//            $qo->fraction[$a_count] = $ans->fraction;
//            $qo->feedback[$a_count] = $ans->feedback;
//            ++$a_count;
//        }
		
//        return $qo;
//    }
	
    /**
    * Exports the question to Moodle XML format.
    *
    * This method is called by the format class when exporting an interval question into then
    * Moodle XML format.
    * 
    * It originally comes from the algebra question type.    	
    * @param $question question to be exported into XML format
    * @param $format format class exporting the question
    * @param $extra extra information (not required for exporting this question in this format)
    * @return text string containing the question data in XML format
    */
//    function export_to_xml(&$question,&$format,&$extra) {
//echo "export_to_xml<br />";
//        $expout='';
		
//        $expout .= "<direction>".$question->options->direction."</direction>\n";
//        $expout .= "<quality>".$question->options->quality."</quality>\n";
//        $expout .= "<size>".$question->options->size."</size>\n";
//        $expout .= "<orignoteletter>".$question->options->orignoteletter."</orignoteletter>\n";
//        $expout .= "<orignoteaccidental>".$question->options->orignoteaccidental."</orignoteaccidental>\n";
//        $expout .= "<orignoteregister>".$question->options->orignoteregister."</orignoteregister>\n";
//        $expout .= "<clef>".$question->options->clef."</clef>\n";
		
//        foreach ($question->options->answers as $answer) {
//            $percent = 100 * $answer->fraction;
//            $expout .= "<answer fraction=\"$percent\">\n";
//            $expout .= $format->writetext($answer->answer,2,true);
//            $expout .= "    <feedback>".$format->writetext($answer->feedback)."</feedback>\n";
//            $expout .= "</answer>\n";
//        }
//        return $expout;
//    }


	
	
	
	
}

// Register this question type with the system.
//question_register_questiontype(new musicinterval_qtype());

