#!/usr/bin/php
<?php

# GPUFramebuffers
# @cecekpawon 10/20/2015 00:26 AM
# thrsh.net

passthru("clear");

if (!isset($_SERVER["TERM_PROGRAM"])) die("Run in terminal!");

/*
  Inpiration: Apple Intel AMD/ATI Framebuffers by l0rd SJ_UnderWater
  http://www.tonymacx86.com/graphics/112299-apple-intel-amd-ati-framebuffers.html
*/

$ctype = array(
    "02000000" => "LVDS",
    "04000000" => "DDVI",
    "08000000" => "SVIDEO",
    "10000000" => "VGA",
    "00020000" => "SDVI",
    "00040000" => "DP",
    "00080000" => "HDMI",
    //"000c0000" => "4k?"
  );

$fb_a = array(
    "snb" => "AppleIntelSNBGraphicsFB",
    "capri" => "AppleIntelFramebufferCapri",
    "azul" => "AppleIntelFramebufferAzul",
    "skylake" => "AppleIntelSKLGraphicsFramebuffer"
  );

$sle = "/System/Library/Extensions/%s.kext/Contents/MacOS/%s";

function dump($value) {
  echo "<xmp>";
  die(var_dump($value));
}

function help() {
  $FNAME = basename(__FILE__);

  $help = <<<YODA
Valid args:
  {$FNAME} <fb>:
Ex:
  {$FNAME} --snb ( AppleIntelSNBGraphicsFB )
  {$FNAME} --capri ( AppleIntelFramebufferCapri )
  {$FNAME} --azul ( AppleIntelFramebufferAzul )
  {$FNAME} --skylake ( AppleIntelSKLGraphicsFramebuffer )

YODA;

  die($help);
}

function flip($b) {
  return implode("", array_reverse(str_split($b, 2)));
}

function toExt($b, $mb=0) {
  return " (" . number_format((hexdec(flip($b)) / ($mb ? 1048576 : 1)), 0, "", "") . ($mb ? "Mb" : "Hz") . ")";
}

function getbin($s, &$fb) {
  global $fb_a, $sle;

  if (!preg_match("#^\-\-[a-z]#i", $s)) return;

  $fb = preg_replace("#[^a-z]#i", "", $s);

  if (array_key_exists($fb, $fb_a) && is_file($bin = sprintf($sle, $fb_a[$fb], $fb_a[$fb]))) {
    $a = unpack("H*", file_get_contents($bin))[1];

    switch ($fb) {
      case 'snb':
        $r = "0[0-1]{1}0[2-3]{1}0[134]{1}00(1007|0000)0000(1007|ffff)[a-f0-9]{100}";
        break;
      case 'capri':
        $r = "0[a-b0-9]{1}006[26]{1}01[0-9]{16}[a-f0-9]{192}";
        break;
      case 'azul':
        $r = "0[0-8]{1}00[0-2]{1}[26e]{1}0[4acd]{1}0[0-9]{14}[24]{1}[a-f0-9]{192}";
        break;
      case 'skylake':
        $r = "[0-3]{4}[a-f0-9]{2}19[0-3]{8}00000004[a-f0-9]{176}";
        break;
      default:
        break;
    }

    return preg_match_all(sprintf("#(%s+)#i", $r), $a, $c) ? $c[1] : "";
  }
}

if (!isset($argv[1]) || (!($c = getbin($argv[1], $fb)))) help();

$r = array();

foreach ($c as $k => $v) {
  switch ($fb) {
    case 'azul':
    case 'skylake':
      $v = substr_replace($v, '', 96 , 8);
      break;
    default:
      break;
  }

  $d = str_split($v, 24);
  $t = array();

  $PlatformID="";

  foreach ($d as $i => $l) {
    $x = array();

    switch ($i) {
      case 0:
        $s = substr($v,  0, 8); $x[] = "Platform-ID: " . $s; $t[] = sprintf("ig-platform-id: %s\n",  flip($s));
        $s = substr($v, 10, 2); $x[] = "Port: " . $s;
        $s = substr($v, 12, 2); $x[] = "Pipes: " . $s;
        $s = substr($v, 14, 2); $x[] = "*FBMem: " . $s;
        $s = substr($v, 16, 8); $x[] = "StolenMemSize: " . $s . toExt($s,  1);
        break;
      case 1:
        if ($fb !== "snb") {
          $s = substr($l, 0, 8); $x[] = "FBMemSize: " . $s . toExt($s,  1);
          $s = substr($l, 8, 8); $x[] = "Vram: " . $s . toExt($s,  1);
          //$s = substr($l, 16, 8); $x[] = "BacklightFreq: " . $s . toExt($s);
          break;
        }
      case 2:
        //$s = substr($l, 0, 8); $x[] = "BacklightFreqMax: " . $s . toExt($s);
        //break;
      case 3:
      case 4:
      case 5:
      case 6:
      case 7:
        $ConnType=substr($l, 8, 8);

        if (array_key_exists($ConnType,  $ctype)) {
          $s = substr($l, 0, 2); $x[] = "Index: " . $s;
          $s = substr($l, 2, 2); $x[] = "Port: " . $s;
          $s = substr($l, 4, 2); $x[] = "*Pipe: " . $s;
          //$s=$s2=substr($l, 8, 8); $x[]="ConnType: " . $s;
          $x[] = "ConnType: " . $ConnType;
          $s = substr($l, 16, 8); $x[] = "ConnAtts: " . $s;
          $x[] = $ctype[$ConnType];
        }
        break;
      default:
        break;
    }

    if (count($x)) {
      $l .= " ==> " . implode(" | ", $x);
    }

    $t[] = $l;
  }

  $r[] = implode("\n",$t);
}


if (count($r)) die(implode("\n\n------------------------\n\n", $r) . "\n");
