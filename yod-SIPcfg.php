<?php

# SIPcfg
# @cecekpawon 09/30/2015 14:36 PM
# thrsh.net

passthru("clear");

if (!isset($_ENV["TERM_PROGRAM"])) die("Run in terminal!");

$DEBUG = $BRUTE = $CSR_VALID_FLAGS = $BOOTFLAGSI = 0;
$DBGCSRSTR = $DBGBBOOTERSTR = "";
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

function help() {
  $FNAME = basename(__FILE__);

  $help = <<<HCSR
Valid args:
  php \e[34m{$FNAME} \e[31m--help\e[0m
  php \e[34m{$FNAME} \e[31m--brute\e[0m
  php \e[34m{$FNAME} \e[31m--csrstatus\e[0m
* BIT (bool) debug:
  php \e[34m{$FNAME} \e[31m11\e[0m
  php \e[34m{$FNAME} \e[31m11 \e[32mtrue\e[0m

HCSR;

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
  die(passthru("csrutil status"));
}

function print_header($s) {
  return printf("\e[31m[x] %s\e[0m\n\n", $s);
}

if (!isset($argv) || (count($argv) <= 1)) help();
else {
  $arg = trim(strtolower($argv[1]));
  switch ($arg) {
    case '--help':
      help(); break;
    case '--csrstatus':
      csrstatus(); break;
    case '--brute':
      $DEBUG = $BRUTE = 1; break;
    default:
      if (preg_match("#^[a-f0-9]{1,2}$#", $arg)) {
        $DEBUG = (isset($argv[2]) && ((bool) $argv[2]));
        $BIT = $arg;
      } else help();
  }
}

foreach ($FLAGS as $k => $v) {
  define($k, $v);

  $CSR_VALID_FLAGS = $k ? $v : ($CSR_VALID_FLAGS | $v);

  if ($DEBUG) {
    $DBGCSRSTR .= sprintf("{$k}: \e[34m%d\e[0m | \e[34m0x%02x\e[0m\n", $v, $v);
  }
}

if ($DEBUG) {
  print_header("CSR_VALID_FLAGS");
  print("{$DBGCSRSTR}\n");
  print_header("BOOTER CFG");
  print("{$DBGBBOOTERSTR}\n");
  if ($BRUTE) brute();

  # experiment with flags below
  $a = array(
      "CSR_ALLOW_UNTRUSTED_KEXTS",
      "CSR_ALLOW_UNRESTRICTED_FS",
      //"CSR_ALLOW_TASK_FOR_PID",
      //"CSR_ALLOW_KERNEL_DEBUGGER",
      "CSR_ALLOW_APPLE_INTERNAL",
      //"CSR_ALLOW_UNRESTRICTED_DTRACE",
      //"CSR_ALLOW_UNRESTRICTED_NVRAM",
      //"CSR_ALLOW_DEVICE_CONFIGURATION"
    );

  $b = array(); foreach ($a as $k) { $b[] = constant($k); }
  $c = array_sum(array_unique($b));
  print_header("DEBUG TEST (read source)");
  printf("(%s) = %s\n\n", ("\e[34m" . implode("\e[0m | \e[34m", $a) . "\e[0m"), sprintf("\e[34m%d\e[0m | \e[34m0x%02x\e[0m", $c, $c));

  print_flags($c);
}

print_header("INPUT");
print_flags(hexdec($BIT));
