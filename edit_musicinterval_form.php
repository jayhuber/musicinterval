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
 * Defines the editing form for the music interval question type.
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

require_once($CFG->dirroot . '/question/type/edit_question_form.php');

/**
 * Select from drop down list question editing form definition.
 *
 * @copyright  2011 The Open University
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class qtype_musicinterval_edit_form extends question_edit_form {
    const MAX_GROUPS = 8;

    /** @var array of HTML tags allowed in choices / drag boxes. */
    protected $allowedhtmltags = array(
        'sub',
        'sup',
        'b',
        'i',
        'em',
        'strong'
    );

    /** @var string regex to match HTML open tags. */
    private $htmltstarttagsandattributes = '/<\s*\w.*?>/';

    /** @var string regex to match HTML close tags or br. */
    private $htmltclosetags = '~<\s*/\s*\w\s*.*?>|<\s*br\s*>~';

    /** @var string regex to select text like [[cat]] (including the square brackets). */
    private $squarebracketsregex = '/\[\[[^]]*?\]\]/';

    /**
     * definition_inner adds all specific fields to the form.
     * @param object $mform (the form being built).
     */
    protected function definition_inner($mform) {
        global $CFG;

        $mform->addElement('select', 'direction', get_string('direction','qtype_musicinterval'),
            array( "a"  => get_string('dirasc', 'qtype_musicinterval'),
            "d"  => get_string('dirdesc', 'qtype_musicinterval')
            ));

        $mform->addElement('select', 'quality', get_string('quality','qtype_musicinterval'),
            array( "M"  => get_string('major','qtype_musicinterval'),
            "m"  => get_string('minor','qtype_musicinterval'),
            "P"  => get_string('perfect','qtype_musicinterval'),
            "A"  => get_string('augmented','qtype_musicinterval'),
            "D"  => get_string('diminished','qtype_musicinterval'),
            ));
		$mform->addHelpButton('quality', 'quality', 'qtype_musicinterval');

		$mform->addElement('select', 'size', get_string('size','qtype_musicinterval'), 
            array( "2"  => "2",
            "3"  => "3",
            "4"  => "4",
            "5"  => "5",
            "6"  => "6",
            "7"  => "7",
            "8"  => "8",
            "9"  => "9",
            "10"  => "10",
            "11"  => "11",
            "12"  => "12",
            "13"  => "13",
            ));
		$mform->addHelpButton('size', 'size', 'qtype_musicinterval');

		$mform->addElement('select', 'orignoteletter', 
		    get_string('orignoteletter','qtype_musicinterval'),
		    array( "C"  => get_string('C','qtype_musicinterval'),
		    "D"  => get_string('D','qtype_musicinterval'),
		    "E"  => get_string('E','qtype_musicinterval'),
		    "F"  => get_string('F','qtype_musicinterval'),
		    "G"  => get_string('G','qtype_musicinterval'),
		    "A"  => get_string('A','qtype_musicinterval'),
		    "B"  => get_string('B','qtype_musicinterval'),
		    ));
		$mform->addHelpButton('orignoteletter', 'orignoteletter', 'qtype_musicinterval');

		$mform->addElement('select', 'orignoteaccidental', 
		    get_string('orignoteaccidental','qtype_musicinterval'),
		    array( ""  => "&#9838",
		    "#"  => "&#9839",
		    "b"  => "&#9837",
		    "x"  => "x",
		    "bb"  => "bb",
		    ));
		$mform->addHelpButton('orignoteaccidental', 'orignoteaccidental', 'qtype_musicinterval');

		$mform->addElement('select', 'orignoteregister', 
		    get_string('orignoteregister','qtype_musicinterval'),
		    array( "3"  => "3",
		    "4"  => "4",
		    "5"  => "5",
		    ));
		$mform->addHelpButton('orignoteregister', 'orignoteregister', 'qtype_musicinterval');

		$mform->addElement('select', 'clef', get_string('clef','qtype_musicinterval'),
		    array( "t"  => get_string('treble','qtype_musicinterval'),
		    "b"  => get_string('bass','qtype_musicinterval'),
		    ));
		$mform->addHelpButton('clef','clef','qtype_musicinterval');

		$this->add_per_answer_fields($mform, get_string('answerno', 'qtype_musicinterval', '{no}'),
		        	question_bank::fraction_options(), 1, 1);

		//this adds the hint options
		$this->add_interactive_settings();	
    }

    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        if(($data['quality']=="M" || $data['quality']=="m") &&
	        ($data['size']==1 || 
	        $data['size']==4 || 
	        $data['size']==5 ||
	        $data['size']==8 ||
	        $data['size']==11 ||
	        $data['size']==12)
        ) {
            $errors['quality']=get_string('qualitymismatch','qtype_musicinterval');
        }

        if(($data['quality']=="P") &&
	        ($data['size']==2 || 
	        $data['size']==3 || 
	        $data['size']==6 ||
	        $data['size']==7 ||
	        $data['size']==9 ||
	        $data['size']==10 ||
	        $data['size']==13)
        ) {
            $errors['quality']=get_string('qualitymismatch','qtype_musicinterval');
        }

        $answers = $data['answer'];
        $answercount = 0;
        $maxgrade = false;
        foreach ($answers as $key => $answer) {
            $trimmedanswer = trim($answer);
            if ($trimmedanswer !== ''){
                $answercount++;
                if ($data['fraction'][$key] == 1) {
                    $maxgrade = true;
                }
            } else if ($data['fraction'][$key] != 0 || !html_is_blank($data['feedback'][$key]['text'])) {
                $errors["answer[$key]"] = get_string('answermustbegiven', 'qtype_shortanswer');
                $answercount++;
            }
        }

        if ($answercount==0){
            $errors['answer[0]'] = get_string('notenoughanswers', 'question', 1);
        }
        if ($maxgrade == false) {
            $errors['fraction[0]'] = get_string('fractionsnomax', 'question');
        }

        return $errors;
    }

    protected function data_preprocessing($question) {
        $question = parent::data_preprocessing($question);
        $question = $this->data_preprocessing_answers($question);
        $question = $this->data_preprocessing_hints($question);

        return $question;
    }

    public function qtype() {
        return 'musicinterval';
    }
}
