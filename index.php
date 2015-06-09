<?php

require_once('FieldNote.php');
require_once('FieldNotes.php');

$fns = new FieldNotes();
if (isset($_POST['count'])) {
    $fns->setCurrentCachesCount((int) str_replace(array('.', ','), '', $_POST['count']));
}
if (isset($_POST['timeZoneFile'])) {
    try {
	$fns->setTimeZoneFile(new DateTimeZone($_POST['timeZoneFile']));
    } catch (Exception $e) {
	echo 'TZ souboru neni platne';
	exit;
    }
} else {
    $fns->setTimeZoneFile(new DateTimeZone('UTC'));
}
if (isset($_POST['timeZoneLocal'])) {
    try {
	$fns->setTimeZoneLocal(new DateTimeZone($_POST['timeZoneLocal']));
    } catch (Exception $e) {
	echo 'Mistni TZ neni platne';
	exit;
    }
} else {
    $fns->setTimeZoneLocal(new DateTimeZone('Europe/Prague'));
}
if (isset($_FILES['file']) && isset($_FILES['file']['tmp_name']) && $_FILES['file']['tmp_name']) {
    $fns->loadFileUtf16($_FILES['file']['tmp_name']);
} else if (isset($_POST['data'])) {
    $fns->loadString($_POST['data']);
}
$fns->codeOverride();

$originalFns = $fns->deepClone();

$format = null;
if (isset($_POST['format'])) {
    $format = $_POST['format'];
} else if (isset($_COOKIE['format'])) {
    $format = $_COOKIE['format'];
} else {
    $format = '<p>#{globalCurr}, {time}</p>' . "\n\n" . '<p>{text}</p>';
}
$fns->format($format);
setcookie('format', $format, time() + 60 * 60 * 24 * 365); // 365 days

if (isset($_POST['download'])) {
    $formatFile = tempnam('/var/www/fn/format', '');
    chmod($formatFile, 0644);
    file_put_contents($formatFile, $format);

    header('Content-type: text/csv');
    header('Content-Disposition: attachment; filename="geocache_visits_updated.txt"');

    print $fns->saveStringUtf16(true);
    exit;
}

?>
<?php echo '<?xml version="1.0" encoding="utf-8" ?>'; ?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN"
        "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="cs">
<head>
<title>fn.ondrej.org</title>
</head>
<body>
<div>
<p>Navod:</p>
<ul>
    <li>Do prvniho pole zadejte kolik mate nyni odlovenych kesi</li>
    <li>Do pole Format zadejte formatovaci retezec. Pro nahrazovani pouzijte tabulku po prave strane</li>
    <li>Pote nahrajte svuj geocache_visits.txt soubor</li>
    <li>Pro nahled vysledku kliknete na tlacitko Nahled</li>
    <li>Pro stazeni upraveneho geocache_visits.txt kliknete na tlacitko Stahnout</li>
</ul>
<p>Logovani kesi ktere nemate v GPS:</p>
<ul>
    <li>Stahnete si <a href='GCZZZZZ.gpx'>GCZZZZZ.gpx</a> a nahrejte do GPS jako normalni kes</li>
    <li>V GPS zalogujte kes 'Neexistujici kes' a pridejte komentar s GC kodem</li>
    <li>Po nahrati geocache_visits.txt na tento web dojde k automaticke konverzi kodu</li>
</ul>

</div>
<div style='float: right'>
<table border='1'>
<tr>
<td>{curr}</td><td>Poradi logovane cache</td>
</tr><tr>
<td>{count}</td><td>Pocet logovanych cache</td>
</tr><tr>
<td>{currInDay}</td><td>Poradi logovane cache v ramci dne</td>
</tr><tr>
<td>{countInDay}</td><td>Pocet logovanych cache v ramci dne</td>
</tr><tr>
<td>{globalFirst}</td><td>Celkove poradi prvni cache</td>
</tr><tr>
<td>{globalLast}</td><td>Celkove poradi posledni cache</td>
</tr><tr>
<td>{globalCurr}</td><td>Celkove poradi logovane cache</td>
</tr><tr>
<td>{globalFirstInDay}</td><td>Celkove poradi prvni cache v ramci dne</td>
</tr><tr>
<td>{globalLastInDay}</td><td>Celkove poradi posledni cache v ramci dne</td>
</tr><tr>
<td>{time}</td><td>Cas</td>
</tr><tr>
<td>{text}</td><td>Puvodni text</td>
</tr>
</table>
</div>
<form method='post' action='' enctype="multipart/form-data">
<div>
    <label for='count'>Nalezenych kesi:</label>
    <input type='text' name='count' id='count' value='<?php echo isset($_POST['count']) ? htmlspecialchars($_POST['count']) : ""; ?>' />
</div>
<div>
    <label for='format'>Format:</label>
    <textarea name='format' rows='5' cols='69' id='format'><?php echo htmlspecialchars($format); ?></textarea>
</div>
<div>
    <label for='timeZoneFile'>TZ souboru:</label>
    <input type='text' name='timeZoneFile' id='timeZoneFile' value='<?php echo htmlspecialchars($fns->getTimeZoneFile()->getName()); ?>' />
</div>
<div>
    <label for='timeZoneLocal'>Mistni TZ:</label>
    <input type='text' name='timeZoneLocal' id='timeZoneLocal' value='<?php echo htmlspecialchars($fns->getTimeZoneLocal()->getName()); ?>' />
</div>
<div>
    <label for='file'>geocache_visits.txt:</label>
    <input type='file' name='file' id='file' />
</div>
<div>
    <input type='submit' name='submit' value='Nahled' />
    <input type='submit' name='download' value='Stahnout' />
    <a href='http://www.geocaching.com/my/uploadfieldnotes.aspx' onclick='this.target="_blank";'>Nahrat na geocaching.com</a>
</div>
<div>
    <textarea name='data' style='display: none;' rows="1" cols="1"><?php print htmlspecialchars($originalFns->saveString()); ?></textarea>
</div>
</form>

<?php
if (count($fns->getData())) {
?>
<table border='1'>
<tr>
<th>Kod</th>
<th>Datum a cas</th>
<th>Log</th>
</tr>
<?php
foreach ($fns->getData() as $fn) {
    print '<tr>';
    print '<td><a href="http:///www.coord.info/'.htmlspecialchars($fn->getCode()).'" target="_blank">' . htmlspecialchars($fn->getCode()) . '</a></td>';
    print '<td>' . htmlspecialchars($fn->getLocalDateTime()->format('Y-m-d G:i')) . '</td>';
    print '<td>' . $fn->getFormatedText() . '</td>';
    print '</tr>';
}
?>
</table>
<?php
}
?>
</body>
</html>
