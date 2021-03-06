<?php
require_once('transporter.php');
require_once('assessmentdataconfig.php');

ini_set('display_errors', 'On');
error_reporting(E_ALL | E_STRICT);


$rows = $_POST[ROWS_NAME];

// collect student ids
$studentIDArray = collectInputs($rows, STUDENT_ID_NAME, null);
//$studentIDArray = validate("is_numeric", $studentIDArray);
if(!validate("is_string", $studentIDArray))
    return sendErrorMessage("ID error");


// TODO: check if this is the correct validataion
// collect assessment_phases
$assessmentPhaseArray = collectInputs($rows, ASSESSMENT_PHASE_NAME, null);
//$assessmentPhaseArray = validate("is_string", $assessmentPhaseArray);
if(!validate("is_string", $assessmentPhaseArray))
    return sendErrorMessage("Assessment phase error");


// collect ages
$ageArray = collectInputs($rows, AGE_NAME, null);
//$ageArray = validate("is_numeric", $ageArray);
if(!validate("is_numeric", $ageArray))
    return sendErrorMessage("Age error");


// collect genders
$genderArray = collectInputs($rows, GENDER_NAME, null);
//$genderArray = validate("is_string", $genderArray);
if(!validate("is_string", $genderArray))
    return sendErrorMessage("Gender error");


// receptive vocab score
$recepVocabScoreArray = collectInputs($rows, RECEPTIVE_VOCAB_NAME, 0);
//$recepVocabScoreArray = validate("is_numeric", $recepVocabScoreArray);
if(!validate("is_numeric", $recepVocabScoreArray))
    return sendErrorMessage("Receptive Vocab error");


// letter name id alphabetical score
$alphaLetterIDArray = collectInputs($rows, LETTER_ID_ALPHABETICAL_NAME, 0);
//$alphaLetterIDArray = validate("is_numeric", $alphaLetterIDArray);
if(!validate("is_numeric", $alphaLetterIDArray))
    return sendErrorMessage("Letter ID Alph. error");

// letter name id random score
$randomLetterIDArray = collectInputs($rows, LETTER_ID_RANDOM_NAME, 0);
//$randomLetterIDArray = validate("is_numeric", $randomLetterIDArray);
if(!validate("is_numeric", $randomLetterIDArray))
    return sendErrorMessage("Letter Id random error");

// sound letter id
$soundLetterIDArray = collectInputs($rows, SOUND_LETTER_ID_NAME, 0);
//$soundLetterIDArray = validate("is_numeric", $soundLetterIDArray);
if(!validate("is_numeric", $soundLetterIDArray))
    return sendErrorMessage("Sound letter error");

// dec sight word score
$decSightWordScoreArray = collectInputs($rows, DECODABLE_SIGHT_WORDS_NAME, 0);
//$decSightWordScoreArray = validate("is_numeric", $decSightWordScoreArray);
if(!validate("is_numeric", $decSightWordScoreArray))
    return sendErrorMessage("Decodeable sight errori");

// rhymiung score
$rhymingScoreArray = collectInputs($rows, RHYMING_NAME, 0);
//$rhymingScoreArray = validate("is_numeric", $rhymingScoreArray);
if(!validate("is_numeric", $rhymingScoreArray))
    return sendErrorMessage("Rhyming error");

// blending scores
$blendingScoreArray = collectInputs($rows, BLENDING_NAME, 0);
//$blendingScoreArray = validate("is_numeric", $blendingScoreArray);
if(!validate("is_numeric", $blendingScoreArray))
    return sendErrorMessage("Blending error");

// nonwordRep scores
$nonwordRepScoreArray = collectInputs($rows, NONWORD_REPETITION_NAME, 0);
//$nonwordRepScoreArray = validate("is_numeric", $nonwordRepScoreArray);
if(!validate("is_numeric", $nonwordRepScoreArray))
    return sendErrorMessage("Nonword Repetition error");

// testing dates
$testingDateArray = collectInputs($rows, TESTING_DATE_NAME, null);
//$testingDateArray = validate("isDate", $testingDateArray);
if(!validate("isDate", $testingDateArray))
    return sendErrorMessage("Testing date error");

$commentArray = collectInputs($rows, COMMENTS_NAME, null);

$tests = array($recepVocabScoreArray, $alphaLetterIDArray, $randomLetterIDArray, $soundLetterIDArray,
    $decSightWordScoreArray, $rhymingScoreArray, $blendingScoreArray, $nonwordRepScoreArray);

$totalScoreArray = getTotalScores($tests, $rows);

$percentageScoreArray = getPercentageScores($totalScoreArray, $rows);

// dict with field names in table and data rows for fields
$assessmentData = array(
    "student_id" => $studentIDArray,
    "assessment_phase" => $assessmentPhaseArray,
    "age" => $ageArray,
    "testing_date" => $testingDateArray,
    "gender" => $genderArray,
    "receptive_vocabulary" => $recepVocabScoreArray,
    "letter_name_identification_in_alphabetical_order" => $alphaLetterIDArray,
    "letter_name_identification_in_random_order" => $randomLetterIDArray,
    "sound_letter_identification" => $soundLetterIDArray,
    "decodeable_words_and_sight_words" => $decSightWordScoreArray,
    "phonological_awareness_rhyming" => $rhymingScoreArray,
    "phonological_awareness_blending" => $blendingScoreArray,
    "phonological_awareness_non_word_repetition" => $nonwordRepScoreArray,
    "comments" => $commentArray,
    "total_score" => $totalScoreArray,
    "percentage" => $percentageScoreArray,
);


insertAssessmentData($assessmentData, $rows);
if(checkInsertionSuccess($assessmentData, $rows))
{
    echo <<<EOT
    <div class="alert alert-success fade in" id="success_alert">
        <a href="#" class="close" data-dismiss="alert">&times;</a>
        <strong>Success!</strong> Your form has been sent successfully.
    </div>
EOT;
}
else
{
    sendErrorMessage();
}


function sendErrorMessage($msg = null)
{
    echo <<<EOT
    <div class="alert alert-danger fade in" id="success_alert">
        <a href="#" class="close" data-dismiss="alert">&times;</a>
        <strong>Error</strong> Unable to submit form data.  Please report the problem.<br />
        $msg
    </div>
EOT;
    return false;
}



function getPercentageScores($totalScores, $rows) {
    $percentScores = array();
    for ($i = 0; $i < $rows; $i++) {
        $percentScores[$i] = ($totalScores[$i] / MAX_TOTAL_SCORE) * 100;
    }
    return $percentScores;
}


function getTotalScores($tests, $rows) {
    $totalScores = array();
    for ($i = 0; $i < $rows; $i++) {
        $total = 0;
        foreach ($tests as $scores) {
            $total = $total + $scores[$i];
        }
        $totalScores[$i] = $total;
    }
    return $totalScores;
}


// checks that all of our assessmentData was inserted into mysql
function checkInsertionSuccess($data, $rows) {
    $success = false;
    $count = count($data);
    $transporter = new Transporter();
    $db = $transporter->dbConnectPdo();

    for ($i = 0; $i < $rows; $i++) {
        $sql = 'SELECT * from foreign_site_child_analysis WHERE ';
        $j = 0;
        foreach ($data as $fieldName => $dataArray) {
            $value = $dataArray[$i];
            $sql = $sql . $fieldName ;
            if ($value != null) {
                $sql = $sql . '=' . format_value($value);
            }
            else {
                $sql = $sql . " IS NULL";
            }
            if ($j < $count - 1 ) {
                $sql = $sql . " AND ";
            }
            $j = $j + 1;
        }
        $sql = $sql . ';';
        $statement = $db->prepare($sql);
        $statement->execute();
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if ($row == true) {
            $success = true;
        }
    }
    $db = null;
    return $success;
}


// inserts the given data into the assessment_data table under the given field names
function insertAssessmentData($field_data_dict, $numRows) {
    $transporter = new Transporter();
    $db = $transporter->dbConnectPdo();

    $fieldStr = queryStr(array_keys($field_data_dict));

    // go through each row in the table
    for ($i = 0; $i < $numRows; $i++) {
        $values = array();
        // collect all values from that row
        foreach ($field_data_dict as $field => $dataArray){
            array_push($values, $dataArray[$i]);
        }
        $valsStr = queryStr($values, "format_value");
        // form the first part of the query
        $sql = "INSERT INTO assessment_information " . $fieldStr . " VALUES " . $valsStr . ";";
        var_dump($sql);
        print_r($sql);
        die();
        $statement = $db->prepare($sql);
        $result = $statement->execute();

        if ($result == false) {
            sendErrorMessage();
//            print_r($db->errorInfo());
        }
    }
    // make space
    unset($transporter);
//    $transporter = null;
}


function format_value($value) {
    $formattedStr = null;
    if ($value == null) {
        $formattedStr = "null";
    }
    elseif (isDate($value) == true) {
        // put it into the correct date format
        $formattedStr = date("Y-m-d", strtotime($value));
        $formattedStr = '\'' . $formattedStr . '\'';
    }
    else if (is_numeric($value) or is_string($value)) {
        $formattedStr = '\'' . $value . '\'';
    }
    else {
        die("unrecognized value");
    }
    return $formattedStr;
}


function queryStr($elements, callable $apply = null) {
    $str = "(";
    $i = 0;
    $count = count($elements);

    foreach ($elements as $elem) {
        if ($apply != null) {
            $str = $str . $apply($elem);
        }
        else {
            $str = $str . $elem;
        }
        // put a comma after every value but the last
        if ( $i < $count - 1) {
            $str = $str . ", ";
        }
        $i = $i + 1;
    }
    $str = $str . ")";
    return $str;
}


function collectInputs($numRows, $fieldName, $default) {
    $inputArray = array();
    for ($i = 0; $i < $numRows; $i++) {
        if(isset($_POST["$fieldName$i"]) and $_POST["$fieldName$i"] != "") {
            // input is set so add it to the array
            array_push($inputArray, $_POST["$fieldName$i"]);
        }
        else {
            array_push($inputArray, $default);
        }
    }
    return $inputArray;
}


// returns an array where all invalid elements in the given array are null
//function validate(callable $isValid, $array) {
//    $validArray = array();
//    foreach ($array as $elem) {
//        if ($isValid($elem) == true or $elem == null) {
//            array_push($validArray, $elem);
//        }
//        else {
//            array_push($validArray, null);
//        }
//    }
//    return $validArray;
//}

//Check if the array is valid
function validate(callable $isValid, $array)
{
    foreach ($array as $elem) {
        if (!$isValid($elem) && strlen($elem) <= 0) {
            return false;
        }
    }
    return true;
}

// returns true if the the given string describes a valid date in one
// of the following two formats: mm/dd/yyyy  or yyyy-dd-mm
function isDate($str) {
    if (validateDate($str, 'm/d/Y') or validateDate($str, 'Y-d-m')) {
        return true;
    }
    else {
        return false;
    }
}


function validateDate($date, $format = 'm-d-Y') {
    $d = DateTime::createFromFormat($format, $date);
    return $d;
}