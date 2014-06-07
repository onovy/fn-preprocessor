<?php

class FieldNote {
    private $code;
    private $dateTime;
    private $what;
    private $text;
    private $formatedText;
    private $timeZoneFile;
    private $timeZoneLocal;
    private $curr;
    private $currInDay;
    private $globalCurr;

    public function setTimeZoneFile($timeZoneFile) {
	$this->timeZoneFile = $timeZoneFile;
    }

    public function setTimeZoneLocal($timeZoneLocal) {
	$this->timeZoneLocal = $timeZoneLocal;
    }

    public function setCurr($curr) {
	$this->curr = $curr;
    }

    public function setCurrInDay($currInDay) {
	$this->currInDay = $currInDay;
    }
    
    public function setGlobalCurr($globalCurr) {
	$this->globalCurr = $globalCurr;
    }

    public function setCode($code) {
	$this->code = $code;
    }

    public function getCode() {
	return $this->code;
    }

    public function getDateTime() {
	if ($this->dateTime === NULL) {
	    return NULL;
	} else {
	    return clone $this->dateTime;
	}
    }
    
    public function getLocalDateTime() {
	if ($this->dateTime === NULL) {
	    return NULL;
	} else {
	    return $this->getDateTime()->setTimeZone($this->timeZoneLocal);
	}
    }

    public function getWhat() {
	return $this->what;
    }

    public function setText($text) {
	$this->text = $text;
    }

    public function getText() {
	return $this->text;
    }

    public function getFormatedText() {
	return $this->formatedText;
    }

    public function format($text, $data = array()) {
	$data['curr'] = $this->curr;
	$data['currInDay'] = $this->currInDay;
	$data['globalCurr'] = $this->globalCurr;
	$data['time'] = $this->getLocalDateTime()->format('G:i');
	$data['text'] = $this->text;

	$formatedText = $text;
	foreach ($data as $name=>$value) {
	    $formatedText = str_replace('{' . $name . '}', $value, $formatedText);
	}
	$this->formatedText = $formatedText;
    }

    public function loadCsv($csvString) {
	$csv = str_getcsv($csvString, ',', '"');
	if (count($csv) != 4) {
		throw new Exception('CSV malformed!');
	}
	$this->code = $csv[0];
	$this->dateTime = DateTime::createFromFormat('Y-m-d\TH:i\Z', $csv[1], $this->timeZoneFile);
	assert($this->dateTime !== false);
	$this->what = $csv[2];
	$this->text = $csv[3];
    }
    
    public function saveCsv() {
	$csv = array();
	$csv[0] = $this->code;
	$csv[1] = $this->dateTime->format('Y-m-d\TH:i\Z');
	$csv[2] = $this->what;
	if ($this->formatedText !== NULL) {
	    $csv[3] = '"' . $this->formatedText . '"';
	} else {
	    $csv[3] = '"' . $this->text . '"';
	}
	
	return implode(',', $csv);
    }
}

?>
