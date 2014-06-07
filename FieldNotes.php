<?php

class FieldNotes {
    private $timeZoneFile;
    private $timeZoneLocal;
    private $data = array();
    private $days = array();
    private $count = 0;
    private $globalFirst = 0;
    private $globalLast = 0;
    private $currentCachesCount = 0;

    public function __construct() {
//	$this->timeZoneFile = new DateTimeZone('UTC');
//	$this->timeZoneLocal = new DateTimeZone('Europe/Prague');
    }

    public function setCurrentCachesCount($currentCachesCount) {
	$this->currentCachesCount = $currentCachesCount;
    }

    public function getData() {
	return $this->data;
    }

    public function setTimeZoneFile($timeZoneFile) {
	$this->timeZoneFile = $timeZoneFile;
    }

    public function getTimeZoneFile() {
	return $this->timeZoneFile;
    }

    public function setTimeZoneLocal($timeZoneLocal) {
	$this->timeZoneLocal = $timeZoneLocal;
    }

    public function getTimeZoneLocal() {
	return $this->timeZoneLocal;
    }

    public function format($text) {
	foreach ($this->data as $fn) {
	    $day = $this->days[$fn->getLocalDateTime()->format('Y-m-d')];
	    $data = array(
		'count' => $this->count,
		'globalFirst' => $this->globalFirst,
		'globalLast' => $this->globalLast,
		'countInDay' => $day['countInDay'],
		'globalFirstInDay' => $day['globalFirstInDay'],
		'globalLastInDay' => $day['globalLastInDay']
	    );
	    $fn->format($text, $data);
	}
    }
    
    public function codeOverride() {
	foreach ($this->data as $fn) {
	    if ($fn->getCode() == 'GCZZZZZ') {
		preg_match('/^([a-zA-Z0-9]+)(.*)$/', $fn->getText(), $matches);
		$code = $matches[1];
		$text = trim($matches[2]);
		if (strtolower(substr($code, 0, 2)) != 'gc') {
		    $code = 'GC' . $code;
		}
		$fn->setCode($code);
		$fn->setText($text);
	    }
	}
    }

    public function loadFileUtf16($file) {
	$output = tempnam(sys_get_temp_dir(), 'fieldNotes');

	system('iconv -f utf-16 -t utf-8 <' . escapeshellarg($file) . ' >' . escapeshellarg($output));
	$this->loadFile($output);
	unlink($output);
    }

    public function loadFile($file) {
	$f = fopen($file, 'r');

	$this->globalFirst = $this->currentCachesCount + 1;
	$this->globalLast = $this->currentCachesCount;
	$this->count = 0;
	$this->data = array();
	$this->days = array();
	$lastDate = null;
	$countInDay = 1;
	$globalCurr = 0;
	while (($line = fgets($f, 4096)) !== FALSE) {
	    if (trim($line) == '') {
		continue;
	    }
	    $fn = new FieldNote();
	    $fn->setTimeZoneFile($this->timeZoneFile);
	    $fn->setTimeZoneLocal($this->timeZoneLocal);

	    $this->data[] = $fn;

	    $fn->loadCsv($line);

	    $date = $fn->getLocalDateTime()->format('Y-m-d');

	    if ($fn->getWhat() == 'Found it') {
		$this->globalLast++;
		$this->count++;
		$countInDay++;

		$fn->setCurr($this->count);
		$fn->setGlobalCurr($this->globalLast);

		if ($lastDate != $date) {
		    $this->days[$date] = array();
		    $this->days[$date]['globalFirstInDay'] = $this->globalLast;
		    if ($lastDate != null) {
			$this->days[$lastDate]['globalLastInDay'] = $this->globalLast - 1;
			$this->days[$lastDate]['countInDay'] = $countInDay - 1;
		    }
		    $countInDay = 1;
		    $lastDate = $date;
		}

		$fn->setCurrInDay($countInDay);
	    }
	}
	$this->days[$lastDate]['globalLastInDay'] = $this->globalLast;
	$this->days[$lastDate]['countInDay'] = $countInDay;

	fclose($f);
    }
    
    public function loadString($data) {
	$tmp = tempnam(sys_get_temp_dir(), 'fieldNotes');
	file_put_contents($tmp, $data);
	$this->loadFile($tmp);
	unlink($tmp);
    }

    public function loadStringUtf16($data) {
	$tmp = tempnam(sys_get_temp_dir(), 'fieldNotes');
	file_put_contents($tmp, $data);
	$this->loadFileUtf16($tmp);
	unlink($tmp);
    }

    public function saveFile($file) {
	file_put_contents($file, $this->saveString());
    }

    public function saveFileUtf16($file) {
	$output = tempnam(sys_get_temp_dir(), 'fieldNotes');
	$this->saveFile($output);
	system('iconv -f utf-8 -t utf-16 <' . escapeshellarg($output) . ' >' . escapeshellarg($file));
	unlink($output);
    }

    public function saveString() {
	$output = '';
	foreach ($this->data as $fn) {
	    $output .= $fn->saveCsv() . "\r\n";
	}
	return $output;
    }

    public function saveStringUtf16() {
	$output = tempnam(sys_get_temp_dir(), 'fieldNotes');
	$this->saveFileUtf16($output);
	$data = file_get_contents($output);
	unlink($output);
	return $data;
    }

    public function deepClone() {
	$clone = clone $this;
	foreach ($clone->data as $key=>$value) {
	    $clone->data[$key] = clone $clone->data[$key];
	}
	return $clone;
    }
}

?>
