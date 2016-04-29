#!/usr/bin/php
<?php
# SerialKiller
# @cecekpawon 04/29/2016 20:17 PM
# thrsh.net

$gVer = "1.0";
$gTITLE = "IoreXtripper v{$gVer}";
$gUname = "cecekpawon";
$gME = "@{$gUname} | thrsh.net";
$gBase = "OSXOLVED";
$gRepo = "https://github.com/{$gUname}/{$gBase}";
$gRepoRAW = "https://raw.githubusercontent.com/{$gUname}/{$gBase}/master";

function isJson($str) {
 $rtn = @json_decode($str, TRUE);
 return json_last_error() == JSON_ERROR_NONE ? $rtn : "";
}

function update() {
  global $gVer, $gRepoRAW;

  printf("Looking for updates ..\n");

  $FNAME = basename(__FILE__);

  $json = file_get_contents("{$gRepoRAW}/versions.json");
  if (!($json = isJson($json))) printf("Update failed :((\n");
  if (!isset($json[$FNAME])) printf("Update failed :((\n");
  $gTmp = $json[$FNAME];
  if (version_compare($gVer, $gTmp, "<")) {
    echo "Update currently available (v{$gTmp}) ..\n";
    $gStr = @file_get_contents("{$gRepoRAW}/{$FNAME}");
    if ($gStr) {
      $fp = fopen(__FILE__, "w");
      fputs($fp, $gStr);
      fclose($fp);
      printf("Update successfully :))\n");
    } else {
      printf("Update failed :((\n");
    }
  } else {
    printf("Scripts up-to-date! :))\n");
  }

  printf("\n");
}

$is_Terminal = (bool) isset($_SERVER["TERM_PROGRAM"]);

if ($is_Terminal) passthru("clear");

$gHEAD = <<<YODA
====================================================================
$gTITLE : $gME
--------------------------------------------------------------------
Strip your .ioreg ultra sensitive datas
====================================================================\n\n
YODA;

echo $gHEAD;

function usage($s = "") {
  $FNAME = basename(__FILE__);

  $h = <<<YODA
Usage: ./{$FNAME} -i <input.ioreg> -o <output.ioreg>
More: -u <update>

YODA;

  printf($h);

  if ($s) {
    xprint("Error", "$s");
  }
}

function dump($value) {
  die(var_dump($value));
}

function void($s, $a = "") {
  die(call_user_func($s, $a));
}

function xprint($l, $s) {
  void("printf", "\n{$l}: {$s}!\n");
}

function passVar($str) {
  ob_start();
  passthru($str);
  return trim(ob_get_clean());
}

function swap($s) {
  return str_repeat("X", intval($s) ? $s : strlen($s));
}

function parse_args() {
  global $input, $output;

  $input = $output = "";

  $args = getopt("u::i:o:");

  foreach ($args as $k => $v) {
    $k = strtolower($k);
    $v = strtolower($v);
    switch ($k) {
      case "u":
        update();
        break;
      case "i":
        $input = $v;
        break;
      case "o":
        $output = $v;
        break;
    }
  }

  if (!file_exists($input)) usage("Input: not exists");
  if (empty($output)) usage("Output: cannot be empty");
}


$a = array(
  "IOPlatformSerialNumber" => "(>IOPlatformSerialNumber<.*?string>+)([^<]+)",
  "SerialNumber" => "(>serial\-number<.*?data>.*?)([a-z0-9\+\/\=]+)(.*?<\/data>+)",
);

function call_IOPlatformSerialNumber($rgx,$m) {
  global $str;

  $tmp = swap($m[2]);
  $str = preg_replace($rgx, "$1{$tmp}", $str);
}

function call_SerialNumber($rgx,$m) {
  global $str;

  $tmp = bin2hex(base64_decode(trim($m[2])));
  $len = strlen($tmp) / 2;
  $tmp = swap($len);
  if (($len % 2) != 0) {
    $tmp = preg_replace("#(.{1}+)$#i", "\0", $tmp);
  }
  $tmp = base64_encode($tmp);
  $str = preg_replace($rgx, "$1{$tmp}$3", $str);
}

function boot() {
  global $input, $output, $a, $str;

  $str = @file_get_contents($input);

  foreach ($a as $k => $v) {
    $v = "#{$v}#is";
    if (preg_match($v, $str, $m)) {
      call_user_func("call_{$k}", $v, $m);
    }
  }

  @file_put_contents($output, $str);

  if (file_exists($output)) xprint("Done", "Be safe next time");
}

parse_args();
boot();
