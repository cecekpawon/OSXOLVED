<?php

# HDAVerbs Analyzer
# @cecekpawon 10/15/2015 11:15 AM
# thrsh.net

passthru("clear");

$Terminal = (bool) isset($_ENV["TERM_PROGRAM"]);

if ($Terminal) {
  $verbs = "";
} else {
  /* Change this to parsing via browser */
  $verbs = <<<HTML
    01171F99 01171C10 01171D01 01171E43
    01471C20 01471D40 01471E01 01471F01 01470C02
    01571C30 01571D10 01571E01 01571F01
    01671C40 01671D60 01671E01 01671F01
    01771C50 01771D20 01771E01 01771F01
    01871C60 01871D98 01871EA1 01871F01
    01971C70 01971D9C 01971EA1 01971F02
    01A71C90 01A71D30 01A71E81 01A71F01
    01B71CA0 01B71D4C 01B71E21 01B71F02 01B70C02
    01E71CB0 01E71D61 01E71E45 01E71F01
HTML;
}

$aColorCode = array(
    "Normal"  => "\e[0m",
    "Bold"    => "\e[1m",
    "Bg"      => "\e[100m",

    "Black"   => "\e[30m",
    "Gray"    => "\e[37m",
    "Blue"    => "\e[34m",
    "Green"   => "\e[92m",
    "Red"     => "\e[31m",
    "Orange"  => "\e[33m",
    "Yellow"  => "\e[93m",
    "Purple"  => "\e[95m",
    "Pink"    => "\e[33m",
    "White"   => "\e[97m"
  );

$aColorCodeBg = array(
    "Black"   => "\e[40m",
    "Gray"    => "\e[100m",
    "Blue"    => "\e[104m",
    "Green"   => "\e[42m",
    "Red"     => "\e[101m",
    "Orange"  => "\e[48;5;220m",
    "Yellow"  => "\e[48;5;226m",
    "Purple"  => "\e[45m",
    "Pink"    => "\e[48;5;199m",
    "White"   => "\e[47m"
  );

$aPort = array(
      0 => "Jack or ATAPI",
      1 => "No Connection",
      2 => "Internal",
      9 => "Internal + Jack"
  );

$aLocation = array(
      0 => "Unknown",
      1 => "Back",
      2 => "Front",
      9 => "Internal"
  );

$aDevice = array(
      0 => "Line Out",
      1 => "Speaker",
      2 => "Headphone",
      3 => "CD",
      4 => "SPDIF Out",
      5 => "Digital Other Out",
      6 => "Modem Line Side",
      7 => "Modem Handset Side",
      8 => "Line In",
      9 => "AUX",
    "a" => "Microphone",
    "b" => "Telephony",
    "c" => "SPDIF In",
    "d" => "Digital Other In",
    "e" => "Reserved",
    "f" => "Other"
  );

$aConnector = array(
      0 => "Unknown",
      1 => "1/8\" stereo/mono",
      2 => "1/4\" stereo/mono",
      3 => "ATAPI internal",
      4 => "RCA",
      5 => "Optical",
      6 => "Other Digital",
      7 => "Other Analog",
      8 => "Multichannel Analog (DIN)",
      9 => "XLR/Professional",
    "a" => "RJ-11 (Modem)",
    "b" => "Combination",
    "f" => "Other"
  );

$aColor = array(
      0 => "Unknown",
      1 => "Black",
      2 => "Gray",
      3 => "Blue",
      4 => "Green",
      5 => "Red",
      6 => "Orange",
      7 => "Yellow",
      8 => "Purple",
      9 => "Pink",
    "a" => "Reserved 1",
    "b" => "Reserved 2",
    "c" => "Reserved 3",
    "d" => "Reserved 4",
    "e" => "White",
    "f" => "Other"
  );

$aMisc = array(
      0 => "Jack Detect Override",
      1 => "Reserved 1",
      2 => "Reserved 2",
      3 => "Reserved 3"
  );

$aGroup = range(0,6) + array("f");

function dump($value) {
  echo "<xmp>";
  die(var_dump($value));
}

function help() {
  $FNAME = basename(__FILE__);

  $help = <<<YODA
Valid args:
  php {$FNAME} <string> (1 line)
  php {$FNAME} <file>

YODA;

  die($help);
}

if ($Terminal) {
  if (!isset($argv) || (count($argv) <= 1)) help();
  if (is_file($argv[1])) {
    $verbs = trim(@file_get_contents($argv[1]));
  } else {
    array_shift($argv);
    $verbs = trim(implode(" ", $argv));
  }

  if (!$verbs) help();
}

$verbs = strtolower($verbs);

if (preg_match_all("#([^\s]+)#s", $verbs, $m) && isset($m[1])) {
  $m = $m[1];
  $a1 = array();

  foreach ($m as $key) {
    $key = preg_replace("#(0x|[^a-f0-9])#", "", $key);
    if (strlen($key) === 8) {
      preg_match("#^([\d]{1})([a-f0-9]{2})([a-f0-9]{5})$#", $key, $n);
      if (isset($n[2])) {
        $a1[$n[2]][] = $key;
      }
    }
  }

  $res = array();

  if (count($a1)) {
    foreach ($a1 as $key => $val) {
      if (count($val) < 4) {
        continue;
      }

      $EAPD = $PinDefault = array();
      sort($val);

      foreach ($val as $k) {
        preg_match("#^([\d]{1})([a-f0-9]{2})(7[01][c-f])([a-f0-9]{2})$#", $k, $a);
        if (isset($a[4])) {
          $CodecAddress = $a[1];
          $Node = $a[2];
          $VerbCommands = $a[3];
          $VerbData = $a[4];

          if ($VerbCommands === "70c") {
            $EAPD["data"] = $VerbData;
            $EAPD["val"] = $k;
            continue;
          }

          $PinDefault[] = $VerbData;
        }
      }

      $Node = hexdec($Node) . " (0x{$Node})";
      $Verbs = implode(" ", $val);

      $PinDefault = implode("", array_reverse($PinDefault));

      if ($PinDefault === "400000f0") {
        $res[] = <<<HTML
Node: $Node (disabled)
CodecAddress\t: $CodecAddress

Verbs\t\t: $Verbs
HTML;

        continue;
      }

      $PinDefaultPattern = <<<HTML
([0129]{1})     // Port
([0129]{1})     // Location
([a-f0-9]{1})   // Device
([abf0-9]{1})   // Connector
([a-f0-9]{1})   // Color
([a-f0-9]{1})   // Misc
([a-f0-9]{1})   // Group
([a-f0-9]{1})   // Port
HTML;

      $PinDefaultPattern = preg_replace("#(\}\))(.*[\r\n]?+)#", "\\1", $PinDefaultPattern);

      preg_match(sprintf("#^%s$#i", $PinDefaultPattern), $PinDefault, $a);

      if (count($a) < 8) {
        continue;
      }

      if (count($EAPD)) {
        $val[] = $EAPD["val"];
        $EAPD = $EAPD["data"];
      } else {
        $EAPD = "No";
      }

      $Port = $aPort[$a[1]];
      $Location = $aLocation[$a[2]];
      $Device = $aDevice[$a[3]];
      $Connector = $aConnector[$a[4]];
      $Color = $aColor[$a[5]];
      $Misc = $a[6];  // 0-3
      if (!isset($aMisc[$Misc])) $Misc = "$Misc (custom)";
      $Group = hexdec($a[7]); // 0-6f
      if (!isset($aGroup[$Group])) $Group = "$Group (custom)";
      $Position = hexdec($a[8]);

      // Device
      $Type = ord($a[3]);

      if ($Type < 56)       // < 0 .. 7
        $Type = "Out";
      elseif ($Type > 100)  // e .. f
        $Type = "-";
      else                  // 8 .. 9 | a .. c
        $Type = "In";


      if ($Terminal && array_key_exists($Color, $aColorCodeBg)) {
        $fg = in_array($Color, array("Orange", "Yellow", "White")) ? $aColorCode["Black"] : $aColorCode["White"];
        $Color = "{$aColorCodeBg[$Color]} {$fg}{$Color} {$aColorCode['Normal']}";
      }

      $res[] = <<<HTML
Node\t\t: $Node
CodecAddress\t: $CodecAddress
Type\t\t: $Type
EAPD\t\t: $EAPD

PinDefault\t: $PinDefault
Verbs\t\t: $Verbs

Port\t\t: $Port
Location\t: $Location
Device\t\t: $Device
Connector\t: $Connector
Color\t\t: $Color
Misc\t\t: $Misc
Group\t\t: $Group
Position\t: $Position
HTML;
    }
  }

  if (count($res)) {
    $res = implode("\n\n-----------------------------------------------------------------------\n\n", $res);

    if ($Terminal) {
      passthru("tabs");
    } else {
      echo "<xmp>";
    }

    die($res . "\n\n:)\n");
  }
}

die("\n\n:(\n");