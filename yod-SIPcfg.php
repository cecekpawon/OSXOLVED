#!/usr/bin/php
<?php

# SIPcfg
# @cecekpawon 09/30/2015 14:36 PM
# thrsh.net

$gVer="1.2";
$gTITLE="SIP Cfg v{$gVer}";
$gUname="cecekpawon";
$gME="@{$gUname} | thrsh.net";
$gBase="OSXOLVED";
$gRepo="https://github.com/{$gUname}/{$gBase}";
$gRepoRAW="https://raw.githubusercontent.com/{$gUname}/{$gBase}/master";

passthru("clear");

if (!isset($_SERVER["TERM_PROGRAM"])) die("Run in terminal!");

$VALID_FLAGS = 0;
$BIT = $DEBUG = $BRUTE = $BOOTER = FALSE;
$DBGSTR = "";
$BITS_ARR = array();
$FLAGS = array(
    "CSR_ALLOW_UNTRUSTED_KEXTS"         => (1 << 0), // 1
    "CSR_ALLOW_UNRESTRICTED_FS"         => (1 << 1), // 2
    "CSR_ALLOW_TASK_FOR_PID"            => (1 << 2), // 4
    "CSR_ALLOW_KERNEL_DEBUGGER"         => (1 << 3), // 8
    "CSR_ALLOW_APPLE_INTERNAL"          => (1 << 4), // 16
    "CSR_ALLOW_UNRESTRICTED_DTRACE"     => (1 << 5), // 32
    "CSR_ALLOW_UNRESTRICTED_NVRAM"      => (1 << 6), // 64
    "CSR_ALLOW_DEVICE_CONFIGURATION"    => (1 << 7)  // 128
  );

$BOOTERFLAGS = array(
    "kBootArgsFlagRebootOnPanic"        => (1 << 0), // 1
    "kBootArgsFlagHiDPI"                => (1 << 1), // 2
    "kBootArgsFlagBlack"                => (1 << 2), // 4
    "kBootArgsFlagCSRActiveConfig"      => (1 << 3), // 8
    "kBootArgsFlagCSRPendingConfig"     => (1 << 4), // 16
    "kBootArgsFlagCSRBoot"              => (1 << 5), // 32
    "kBootArgsFlagBlackBg"              => (1 << 6), // 64
    "kBootArgsFlagLoginUI"              => (1 << 7)  // 128
  );


$gHEAD = <<<YODA
\e[34m======================================================================
$gTITLE : $gME
======================================================================\e[0m\n\n
YODA;

echo $gHEAD;

function help() {
  global $FLAGS;

  $FNAME = basename(__FILE__);

  $FLAGS = array_keys($FLAGS);

  $help = <<<YODA
Valid args:
* DEBUG:
  \e[34m{$FNAME} \e[31m--help\e[0m (this texts)
  \e[34m{$FNAME} \e[31m--update\e[0m (update scripts)
  \e[34m{$FNAME} \e[31m--brute\e[0m (invoke all vars)
  \e[34m{$FNAME} \e[31m--csrstatus\e[0m (sip status)
  \e[34m{$FNAME} \e[31m--debug\e[0m (all flags)
  \e[34m{$FNAME} \e[31m--booter\e[0m (use booter instead of csr flags)
* BIT (hex):
  \e[34m{$FNAME} \e[31m11\e[0m
  \e[34m{$FNAME} \e[31m0x11\e[0m
* FLAGS:
  \e[34m{$FNAME} \e[31m--{$FLAGS[0]} (try --brute: more flags)\e[0m
* BOOTER FLAGS:
  \e[34m{$FNAME} \e[31m2A --booter\e[0m
  \e[34m{$FNAME} \e[31m--brute --booter\e[0m
  \e[34m{$FNAME} \e[31m--booter --debug\e[0m

YODA;

  die($help);
}

function print_flags($i, $ret=false) {
  global $FLAGS;

  $a = array();
  $h = $t = "";

  foreach ($FLAGS as $k => $v) {
    $a[] = "{$k}: " . (($i & constant($k)) ? "\e[32menable \e[34m(1)\e[0m" : "\e[31mdisable \e[34m(0)\e[0m");
  }

  $s = sprintf(
      "!Flag: \e[34m%d\e[0m | \e[34m0x%02x\e[0m | \e[34m(%s)\e[0m\n\n!%s",
      $i,
      $i,
      sprintf("%08d", decbin($i)),
      (implode("\n", $a) . "\n\n")
    );

  if ($ret) return $s;

  echo preg_replace("#!#", "", $s);
}

function csrstatus() {
  die(passthru("clear && csrutil status"));
}

function print_header($s) {
  return printf("\e[31m[x] %s\e[0m\n\n", $s);
}

function isJson($str) {
 $rtn = @json_decode($str, TRUE);
 return json_last_error() == JSON_ERROR_NONE ? $rtn : "";
}

function update() {
  global $gVer, $gRepoRAW;

  echo "Looking for updates ..\n";

  $FNAME = basename(__FILE__);

  $json = file_get_contents("{$gRepoRAW}/versions.json");
  if (!($json = isJson($json))) die("Update failed :((\n");
  if (!isset($json[$FNAME])) die("Update failed :((\n");
  $gTmp = $json[$FNAME];
  if (version_compare($gVer, $gTmp, '<')) {
    echo "Update currently available (v{$gTmp}) ..\n";
    $gStr = @file_get_contents("{$gRepoRAW}/{$FNAME}");
    if ($gStr) {
      $fp = fopen(__FILE__, "w");
      fputs($fp, $gStr);
      fclose($fp);
      die("Update successfully :))\n");
    } else {
      die("Update failed :((\n");
    }
  } else {
    die("Scripts up-to-date! :))\n");
  }
}

function brute() {
  global $VALID_FLAGS;

  $i = -1; $b = $a = array();

  while (++$i <= $VALID_FLAGS) {
    $s = print_flags($i, true);
    # Eleminate same values
    $md5 = md5(trim($s));
    if (!in_array($md5, $a)) {
      $a[] = $md5;
      $b[] = preg_replace("#!#", "", $s);
    }
  }

  print_header("BRUTE");
  die(implode("", $b));
}

if (!isset($argv) || (count($argv) <= 1)) help();
else {
  array_shift($argv);
  $args = implode(" ", $argv);

  foreach ($argv as $arg) {
    $arg = trim(strtolower($arg));
    if (preg_match("#(\-\-booter+)#i", $args)) {
      $BOOTER = TRUE;
      $FLAGS = $BOOTERFLAGS;
    }
    if (preg_match("#(\-\-debug+)#i", $args)) {
      $DEBUG = TRUE;
    }

    switch ($arg) {
      case '--help':
        help(); break 2;
      case '--update':
        update(); break 2;
      case '--csrstatus':
        csrstatus(); break 2;
      case '--brute':
        $BRUTE = TRUE; break 2;
      default:
        if (preg_match("#^(0x)?([a-f0-9]{1,2})$#i", $arg)) {
          $BIT = $arg;
        } else {
          if (preg_match("#(kBootArgsFlag+)#i", $args)) {
            $BOOTER = TRUE;
            $FLAGS = $BOOTERFLAGS;
          } elseif (!preg_match("#(csr_allow+)#i", $args)) {
            break;
          }

          foreach ($argv as $val) {
            $val = trim(preg_replace("#[^a-z_]#i", "", $val));
            if (array_key_exists($val, $FLAGS)) {
              $BITS_ARR[] = $val;
            }
          }
          if (count($BITS_ARR)) {
            $DEBUG = TRUE; break 2;
          }
        }
    }
  }

  if (
    ($DEBUG === FALSE) &&
    ($BIT === FALSE) &&
    ($BRUTE === FALSE)
  ) help();
}

foreach ($FLAGS as $k => $v) {
  define($k, $v);

  $VALID_FLAGS = $k ? $v : ($VALID_FLAGS | $v);

  if ($DEBUG || $BRUTE) {
    $DBGSTR .= sprintf("{$k}: \e[34m%d\e[0m | \e[34m0x%02x\e[0m\n", $v, $v);
  }
}

if ($DEBUG || $BRUTE) {
  $dbg_header = ($BOOTER ? "BOOTER" : "CSR") . "_VALID_FLAGS";
  print_header($dbg_header);
  print("{$DBGSTR}\n");

  if ($BRUTE) brute();
  elseif(count($BITS_ARR)) {
    # With given user flags
    $a = $BITS_ARR;
    $b = array(); foreach ($a as $k) { $b[] = constant($k); }
    $c = array_sum(array_unique($b));

    print_header("USER_FLAGS");
    printf(
        "(%s) = %s\n\n",
        ("\e[34m" . implode("\e[0m | \e[34m", $a) . "\e[0m"),
        sprintf("\e[34m%d\e[0m | \e[34m0x%02x\e[0m", $c, $c)
      );

    die(print_flags($c));
  } else {
    help();
  }
}

print_header("INPUT_FLAGS");
print_flags(hexdec($BIT));
