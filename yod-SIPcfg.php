#!/usr/bin/php
<?php

# SIPcfg
# @cecekpawon 09/30/2015 14:36 PM
# thrsh.net

$gVer="1.1";
$gTITLE="SIP Cfg v{$gVer}";
$gUname="cecekpawon";
$gME="@{$gUname} | thrsh.net";
$gBase="OSXOLVED";
$gRepo="https://github.com/{$gUname}/{$gBase}";
$gRepoRAW="https://raw.githubusercontent.com/{$gUname}/{$gBase}/master";

passthru("clear");

if (!isset($_SERVER["TERM_PROGRAM"])) die("Run in terminal!");

$CSR_VALID_FLAGS = $BOOTFLAGSI = 0;
$BIT = $DEBUG = $BRUTE = FALSE;
$DBGCSRSTR = $DBGBBOOTERSTR = "";
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

/*
$BOOTFLAGS = array(
 Bitfields for boot_args->flags
    "kBootArgsFlagRebootOnPanic"      => (1 << 0),
    "kBootArgsFlagHiDPI"              => (1 << 1),
    "kBootArgsFlagBlack"              => (1 << 2),
    "kBootArgsFlagCSRActiveConfig"    => (1 << 3),
    "kBootArgsFlagCSRPendingConfig"   => (1 << 4),
    "kBootArgsFlagCSRBoot"            => (1 << 5),
    "kBootArgsFlagBlackBg"            => (1 << 6),
    "kBootArgsFlagLoginUI"            => (1 << 7)
  );
*/

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
  \e[34m{$FNAME} \e[31m--help\e[0m
  \e[34m{$FNAME} \e[31m--update\e[0m
  \e[34m{$FNAME} \e[31m--brute\e[0m
  \e[34m{$FNAME} \e[31m--csrstatus\e[0m
* BIT (hex):
  \e[34m{$FNAME} \e[31m11\e[0m
  \e[34m{$FNAME} \e[31m0x11\e[0m
* FLAGS:
  \e[34m{$FNAME} \e[31m--{$FLAGS[0]} (try --brute: more flags)\e[0m

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
  global $CSR_VALID_FLAGS;

  $i = -1; $b = $a = array();

  while (++$i <= $CSR_VALID_FLAGS) {
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

function csrstatus() {
  die(passthru("clear && csrutil status"));
}

function print_header($s) {
  return printf("\e[31m[x] %s\e[0m\n\n", $s);
}

if (!isset($argv) || (count($argv) <= 1)) help();
else {
  array_shift($argv);
  foreach ($argv as $arg) {
    $arg = trim(strtolower($arg));
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
        } elseif (preg_match("#(csr_allow+)#i", implode(" ", $argv))) {
          foreach ($argv as $val) {
            $val = trim(strtoupper(preg_replace("#[^a-z_]#i", "", $val)));
            if (array_key_exists($val, $FLAGS)) {
              $BITS_ARR[] = $val;
            }
          }
          if (count($BITS_ARR)) {
            $DEBUG = TRUE;
            break 2;
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

  $CSR_VALID_FLAGS = $k ? $v : ($CSR_VALID_FLAGS | $v);

  if ($DEBUG || $BRUTE) {
    $DBGCSRSTR .= sprintf("{$k}: \e[34m%d\e[0m | \e[34m0x%02x\e[0m\n", $v, $v);
  }
}

#print_header("BOOTER CFG");
#print("{$DBGBBOOTERSTR}\n");

if ($DEBUG || $BRUTE) {
  print_header("CSR_VALID_FLAGS");
  print("{$DBGCSRSTR}\n");

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
