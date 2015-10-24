<?php

# VerbsInColor
# @cecekpawon 10/17/2015 01:17 AM
# thrsh.net

$codecdumptxt = "codec_dump.txt";

if ($Terminal = isset($_ENV["TERM_PROGRAM"]) || isset($_SERVER["TERM_PROGRAM"]) ) {
  if (isset($argv[1])) {
      $codecdump = $argv[1];
   }
} else {
  $codecdump = dirname(__FILE__) . "/{$codecdumptxt}";
}

if (!is_file($codecdump)) error();

$css = <<<HTML
<style>
table {
  border-collapse: collapse;
  border: solid thin;
}

td, th {
  border: 1px solid black;
  padding: 0.5rem;
  text-align: left;
  font-size: 14px;
}

.head1 {
  background-color: black;
}
.head2 {
  background-color: gray;
}

.hi, .head1, .head2 {
  color: white;
}
</style>
HTML;

function dump($value) {
  echo "<xmp>";
  die(var_dump($value));
}

function getIndex($k, $h) {
  foreach ($h as $key => $v) if($k === $v OR (is_array($v) && getIndex($k, $v) !== false)) return $key;
  return false;
}

function toColor($n) {
  $n = crc32($n);
  $n &= 0xffffffff;
  return "#" . substr("000000" . dechex($n), -6);
}

function error() {
  global $codecdumptxt;

  $FNAME = basename(__FILE__);

  $error = <<<YODA
Usage:
Place untouched "$codecdumptxt" in same dir
- OR -
php {$FNAME} <linux codecdump>

YODA;

  die($error);
}

$s = file_get_contents($codecdump);

$a = $a_p = $a_pfix  = array();
$b = array("Pin Complex", "Audio Mixer", "Audio Output", "Audio Input");

foreach ($b as $k) {
  $a[$k] = array();
}


$Codec = preg_match("#(Codec:\s?[^\r\n]+)#is", $s, $n) ? trim($n[1]) : "Unknown";

$m = preg_split("#(\sNode\s0x.*?)#is", $s);

if (count($m) < 2) error();

array_shift($m);

foreach ($m as $k) {
  if (!preg_match("#^([0-9a-f]{2})\s\[([^\]]+)#is", $k, $n)) continue;

  $n_nid = $n[1];
  $n_nid_int = hexdec($n_nid);
  $n_type = trim($n[2]);
  $n_name = $n_connection = $n_pindefault = "";

  if (!in_array($n_type, $b)) continue;
  if (preg_match("#Control: name\=\"([^\"]+)#i", $k, $n)) {
    $n_name = trim($n[1]);
  }

  if (preg_match("#Connection: \d+\s([ 0-9a-fx\*]+)#i", $k, $n)) {
    $n_connection = trim($n[1]);
  }

  $c = array(
      "node" => "0x{$n_nid}",
      "nint" => $n_nid_int,
      //"string" => $k
    );

  switch ($n_type) {
    //case "Audio Output":
    case "Audio Input":
    case "Audio Mixer":
      if (!$n_connection) continue 2;
      break;
    case "Pin Complex":
      if (!$n_name) continue 2;
      if (preg_match("#(mic+)#i", $n_name)) $c["mic"] = true;
      if (preg_match("#(eapd+)#i", $k)) $c["eapd"] = true;

$rgx = <<<HTML
Pin\sDefault([^\:]+)\:.*\[(.+)\]([^\\r\\n]+).*
\sConn\s=(.+),.*\sColor\s=([^\\r\\n]+).*
\sDefAssociation\s=(.+),.*\sSequence\s=([^\\r\\n]+)
HTML;

      if (preg_match(sprintf("#%s#is", preg_replace("#([\s])#is", "", $rgx)), $k, $n)) {
        $c += array(
          "pindefault" => trim($n[1]),
          "type" => trim($n[2]),
          "connector" => trim($n[3]),
          "port" => trim($n[4]),
          "color" => trim($n[5]),
          "defassoc" => trim($n[6]),
          "seq" => trim($n[7])
        );
      }
  }

  if ($n_name) {
    $c["name"] = $n_name;
    if (preg_match("#(playback|spdif+)#i", $n_name, $match)) {
      $c["playback"] = preg_replace(sprintf("#(\s%s.+)$#i", $match[1]), "", $n_name);
      if (!in_array($c["playback"], $a_p)) {
        $a_p[] = $c["playback"];
      }
    }
  }

  if ($n_connection) {
    switch ($n_type) {
      case "Pin Complex":
        $h = $f = "";
        if (!preg_match("#\s#", $n_connection)) $h = $n_connection;
        if (preg_match("#(0x[0-9a-f]+)\*#i", $n_connection, $mx)) $f = $h = $mx[1];

        if ($h) {
          if (!$f || isset($c["playback"])) {
            $c["mixer"] = hexdec(preg_replace("#0x#i", "", $h));
            if (!in_array($c["mixer"], $a_pfix)) $a_pfix[] = $c["mixer"];
          } elseif ($f) {
            $c["pinfix"] = $h;
          }
        }
      break;
      case "Audio Mixer":
        $c["mixer"] = $n_nid_int;
      break;
      case "Audio Input":
      break;
    }

    $c["connection"] = preg_replace("(\s)", ", ", $n_connection);
  }

  $a[$n_type][$n_nid_int] = $c;
}

$n_type = $tmp = "";
$ret = array();
$b = array(
    "nint",
    "node",
    "pindefault",
    "type",
    "connector",
    "port",
    "color",
    //"defassoc",
    //"seq",
    "name",
    "connection",
    "pinfix",
    "mixer",
    "hack",
    "mic",
    "eapd"
  );

$colspan = count($b) + 1;

foreach ($a as $k => $v) {
  if ($k !== $n_type) {
    $n_type = $k;
    $ret[] = "<tr class=head1><th colspan=$colspan>$n_type";
    $ret[] = "<tr class=head2><th>no<th>" . implode("<th>", $b);
  }

  $ii = 0;

  foreach ($v as $i) {
    switch ($k) {
      case "Pin Complex":
        if (!isset($i["pinfix"]) && (isset($i["connection"]) && (!getIndex($i["connection"], $a["Audio Mixer"]) && getIndex($i["connection"], $a["Audio Output"])))) {
          $i["hack"] = $i["nint"];
          unset($i["mixer"]);
        }
      break;
      case "Audio Output":
        if (array_key_exists("playback", $i)) {
          if ($tmp = getIndex($i["playback"], $a["Pin Complex"])) {
            if (isset($a["Pin Complex"][$tmp]["mixer"])) {
              $i["mixer"] = $a["Pin Complex"][$tmp]["mixer"];
            }
          }
        }
        if (!getIndex($i["nint"], $a["Audio Mixer"])) {
          if ($t = getIndex($i["nint"], $a["Pin Complex"])) {
            $i["hack"] = $a["Pin Complex"][$t]["nint"];
          }
        }
      break;
      case "Audio Mixer":
        if ($tmp = getIndex($i["node"], $a["Audio Input"])) {
          $i["mic"] = $a["Audio Input"][$tmp]["nint"];
        }
      break;
      case "Audio Input":
        if ($tmp = getIndex($i["connection"], $a["Audio Mixer"])) {
          $i["mic"] = $i["nint"];
        } else {
          //continue 2;
          if (isset($i["name"])) {
            $i["mic"] = 1;
          }
        }
      break;
    }

    $retx = "<tr><td>" . ++$ii;

    foreach ($b as $y) {
      $tmp = "";

      switch ($k) {
        case "Audio Output":
        //break;
        case "Pin Complex":
        case "Audio Mixer":
          switch ($y) {
            case "mixer":
              if (isset($i[$y]) && in_array($i[$y], $a_pfix)) {
                $tmp = "style=background-color:" . toColor($i[$y]);
              }
            break;
            case "hack":
            case "mic":
            case "eapd":
              if (isset($i[$y])) {
                $tmp = "style=background-color:" . toColor($i[$y]);
              }
            break;
          }
        break;
        case "Audio Input":
          switch ($y) {
            case "mic":
              if (isset($i[$y])) {
                $tmp = "style=background-color:" . toColor($i["mic"]);
              }
            break;
          }
        break;
      }

      if ($tmp) {
        $tmp .= " class=hi ";
      }

      $x = isset($i[$y]) ? $i[$y] : "";
      $retx .= "<td $tmp>$x";
    }

    $ret[] = $retx;
  }
}

if (!count($ret)) error();

$ret = $css . "<h1>$Codec<table>" . implode("\r\n", $ret);

if ($Terminal) {
  $Codec = sprintf("/tmp/%s.html", preg_replace("#[^a-z0-9]#i", "_", $Codec));
  file_put_contents($Codec, $ret);
  passthru("open '$Codec'");
} else {
  die($ret);
}
