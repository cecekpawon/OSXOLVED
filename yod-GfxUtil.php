#!/usr/bin/php
<?php

/*

*  gfxutil
*
*  Created by mcmatrix on 08.01.08.
*  Copyright 2008 mcmatrix All rights reserved.
*  You are free to use it and whatever you do please keep the result free for community
*
*  http://forum.netkas.org/index.php?topic=64.0

---

# PHP gfxutil
# @cecekpawon 04/12/2016 15:15 PM
# thrsh.net

---

9900 0000 0100 0000 0100 0000 8d00 0000
^^ filesize (+4)
          ^^ var1 (+4)
                    ^^ countofblocks (+4)
                              ^^ blocksize (+4), init size x8d = 141

0300 0000 0201 0c00 d041 030a 0000 0000
^^ records (+4)
          ^^ tmp / start devpath (= 22 bytes)

0101 0600 001b 7fff 0400 2800 0000 5000
               ^^^^ ^^^^ [0x0004ff7f || 0x0004ffff] (+4) / end devpath
                         ^^ "(" length ; x28 = 40, -4 = 36
                                   ^^ "P"
                                   size x8d = 141 / start key

6900 6e00 4300 6f00 6e00 6600 6900 6700

7500 7200 6100 7400 6900 6f00 6e00 7300

0000 0500 0000 0018 0000 006c 0061 0079

006f 0075 0074 002d 0069 0064 0000 0008

0000 0001 0000 0014 0000 0068 0064 0061

002d 0067 0066 0078 0000 000e 0000 006f

6e62 6f61 7264 2d31 00

---

## DEVPATH

02010c00d041030a0000000001010600011c0101060000007fff0400
02010c00d041030a 00000000 01010600011c 010106000000 7fff0400

* pciroot - 02010c00d041030a00000000

ACPI - 02010c00
PNP - d041
0A03 - 030a
uid - 00000000

* pci node - Pci(device,function) - 01010600011c

PCI - 010106000 : 01|01|0600 (01: hardware path type | 01: pci device type | 0600: length)
function - 0x01
device - 0x1c

* efi dev path must end with end node - 7fff0400

*/

$gVer = "1.4";
$gTITLE = "PHP gfxutil v{$gVer}";
$gUname = "cecekpawon";
$gME = "@{$gUname} | thrsh.net";
$gBase = "OSXOLVED";
$gRepo = "https://github.com/{$gUname}/{$gBase}";
$gRepoRAW = "https://raw.githubusercontent.com/{$gUname}/{$gBase}/master";

$BASE_VERSION = "0.75b";

$is_Terminal = (bool) isset($_SERVER["TERM_PROGRAM"]);

if ($is_Terminal) passthru("clear");

$gHEAD = <<<YODA
==========================================================================
GFX conversion utility version: $BASE_VERSION. Copyright (c) 2007 McMatrix
This program comes with ABSOLUTELY NO WARRANTY. This is free software!
--------------------------------------------------------------------------
$gTITLE : $gME
==========================================================================\n\n
YODA;

echo $gHEAD;

function dump($value) {
  die(var_dump($value));
}

function void($s, $a = "") {
  die(call_user_func($s, $a));
}

function error($s) {
  void("printf", "Error: {$s}!\n");
}

function passVar($str) {
  ob_start();
  passthru($str);
  return trim(ob_get_clean());
}

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

// CONST

// DATA_TYPES
const DATA_INT8 = 1;
const DATA_INT16  = 2;
const DATA_INT32  = 3;
const DATA_BINARY = 4;
const DATA_STRING = 5;

// FILE_TYPES
const FILE_BIN = 1;
const FILE_HEX = 2;
const FILE_XML = 3;
const FILE_REG = 4;

$settings = new stdClass;

function usage($s = "") {
/*
-f name\t\tfinds object devicepath with the given name from IODeviceTree plane
*/
  $h = <<<YODA
gfxutil: [command_option] [other_options] infile outfile

Command options are:
-f\t\tgrab IODeviceTree/efi/device-properties as input
-h\t\tprint this summary
-a\t\tshow version
-i fmt\t\tinfile type, fmt is one of (default is hex): xml bin hex
-o fmt\t\toutfile type, fmt is one of (default is xml): xml bin hex

There are some additional optional arguments:
-v\t\tverbose mode
-s\t\tautomatically detect string format from binary data
-n\t\tautomatically detect numeric format from binary data

More:
-u\t\tupdate script
-d\t\tdump all datas

YODA;

  if ($s) {
    $h .= "\n\nError: {$s}!\n\n";
  }

  printf($h);
  reset_settings();
}

function reset_settings() {
  global $settings;

  $settings = new stdClass;
  /* set default value here */
  $settings->dump = FALSE;
  $settings->verbose = FALSE;
  $settings->detect_strings = FALSE;
  $settings->detect_numbers = FALSE;
}


function parse_args() {
  global $settings, $argv, $BASE_VERSION, $gVer;

  $args = getopt("uafdsnvhi:o:");

  $reg = FALSE;
  foreach ($args as $k => $v) {
    $k = strtolower($k);
    $v = strtolower($v);
    switch ($k) {
      case "a":
        void("printf", "Base: {$BASE_VERSION}, Port: {$gVer}\n");
        break;
      case "u":
        update();
        break;
      case "f":
        $reg = TRUE;
        break;
      case "h":
        void("usage");
        break;
      case "d":
        $settings->dump = TRUE;
        break;
      case "v":
        $settings->verbose = TRUE;
        break;
      case "s":
        $settings->detect_strings = TRUE;
        break;
      case "n":
        $settings->detect_numbers = TRUE;
        break;
      case "i":
      case "o":
        switch ($v) {
          case "xml":
          case "hex":
          case "bin":
            $ifile_type = constant("FILE_" . strtoupper($v));
            if ($k === "i") {
              $settings->ifile_type = $ifile_type;
            } else {
              $settings->ofile_type = $ifile_type;
            }
            break;
          default:
            break;
        }
        break;
      default:
        break;
    }
  }

  if (isset($argv) || (count($argv) >= 2)) {
    array_shift($argv);

    foreach ($argv as $arg) {
      if (!preg_match("/\./", $arg)) continue;

      if (!isset($settings->ifile) && is_writable($arg) && ((int) @filesize($arg))) {
        $settings->ifile = $arg;
        continue;
      }

      if (!isset($settings->ofile) && is_writable(dirname($arg))) {
        $settings->ofile = $arg;
      }
    }
  }

  if ($reg === TRUE) {
    $settings->ifile_type = FILE_REG;
  }

  if ((($reg === FALSE) && !isset($settings->ifile)) || !isset($settings->ofile)) {
    return void("usage", "Invalid in/out file");
  }

  if (!isset($settings->ifile_type) || !isset($settings->ofile_type) || ($settings->ifile_type === $settings->ofile_type)) {
    return void("usage", "Invalid in/out format");
  }
}

function WriteUint32($n, &$ret) {
  $ret .= flip(sprintf("%08s", dechex($n)));
}

function readbin($bp, &$size, &$index, $len, &$ret) {
  $ret = "";
  if ($size) {
    $s = $index;
    if ($len <= $size) {
        $nlen = $s + $len;
        for ($i=$s; $i < $nlen; $i++) {
          $ret .= $bp[$i];
          $index++;
        }
        $size -= $len;
    }
  }
}

function strbin($s) {
  $s = hex2bin($s);
  return (($s !== FALSE)) ? $s : FALSE;
}

function is_vchar($s) {
  $s = hex2bin($s);
  return (($s !== FALSE) && preg_match('/^[\w\s\p{P}]+$/', $s));
}

function is_ascii($s) {
  return (bool) !preg_match( '/[\x80-\xFF]/' , $s);
}

function nopadd($s) {
  return (!($s = ltrim($s, "0"))) ? 0 : $s;
}

function flip($s) {
  return implode("", array_reverse(str_split($s, 2)));
}

function ishex($s, $x = 0) {
  $patt = "/^(0x+)/i";
  $xb = preg_match($patt, $s);
  $s = preg_replace($patt, "", $s);
  $ret = (ctype_xdigit($s) && in_array(strlen($s), array(2, 4, 8)));
  return $x ? ($x && $ret) : $ret;
}

function readbytes($a, $s, $l) {
  $r = ""; $l += $s;
  for ($i=$s; $i < $l; $i++) {
    $r .= $a[$i];
  }
  return $r;
}

function readint($bp, $index, $len) {
  return hexdec(flip(readbytes($bp, $index, $len)));
}

function uni2str($s, &$str, &$len) {
  $str = mb_convert_encoding($s, "UTF-8", "UTF-16LE");
  $len = strlen($str);
  return $len;
}

function str2uni($s, &$str, &$len) {
  $str = mb_convert_encoding($s, "UTF-16LE", "UTF-8");
  $len = mb_strlen($str, "UTF-8");
}

function parse_v($v) {
  switch ($v->nodeName) {
    case 'dict':
      return parse_d($v);
      break;
    default:
      return $v->textContent;
      break;
  }
}

function parse_d($n, $d = array()) {
  $n = $n->firstChild;
  for (; $n != NULL; $n = $n->nextSibling) {
    if ($n->nodeName === "key") {
      $k = $n->textContent;
      $v = $n->nextSibling;

      while ($v->nodeType === XML_TEXT_NODE) {
        $v = $v->nextSibling;
      }

      $d[] = array(
        "key" => $k,
        "val" => parse_v($v),
        "type" => $v->nodeName
        );
    }
  }

  return $d;
}

function to_blockheader(&$gfx_blockheader) {
  preg_match("/[a-f0-9]{16}([0-9]{8})(.+)[7f]fff0400/i", $gfx_blockheader->devpath, $matches);

  $c = count($matches);
  if ($c >=3) {
    for ($y=0; $y < $c; $y++) {
      $str = $matches[$y];
      switch ($y) {
        case 0:
          $gfx_blockheader->ACPI = substr($str, 0, 8);
          $gfx_blockheader->PNP = substr($str, 8, 4);
          $gfx_blockheader->{"0A03"} = flip(substr($str, 12, 4));
          break;
        case 1:
          $gfx_blockheader->uid = flip($str);
          $str = nopadd(substr($str, 0, 2));
          $gfx_blockheader->devpathstr_pci = sprintf("PciRoot(0x%s)", $str);
          $gfx_blockheader->devpathstr_acpi = sprintf("Acpi(PNP%s,%s)", $gfx_blockheader->{"0A03"}, $str);
          break;
        default:
          $a = str_split($str, 12);
          foreach ($a as $k => $str) {
            $len = strlen($str);
            if ($len === 12) {
              $str = sprintf("/Pci(0x%s,0x%s)", nopadd(substr($str, ($len - 2), 2)), nopadd(substr($str, ($len - 4), 2)));
              $gfx_blockheader->devpathstr_pci .= $str;
              $gfx_blockheader->devpathstr_acpi .= str_ireplace(array("0x", ","), array("", "|"), $str);
            }
          }
          break;
      }
    }
  }
}

function devpath2hex($s, &$gfx_blockheader) {
  if (!$s) return;
  preg_match("/PciRoot\(0x([a-f0-9]+)\)\/(.+)/i", $s, $m);
  if (count($m > 1)) {
    $a = array();
    $a[] = "02010c00d041030a";
    $bus = $m[1];
    $a[] = flip(sprintf("%08x", $bus));
    preg_match_all("/Pci\(0x[a-f0-9]+,0x[a-f0-9]+\)/i", $m[2], $m);
    if (count($m)) {
      foreach ($m[0] as $k) {
        preg_match("/0x([a-f0-9]+),0x([a-f0-9]+)/i", $k, $p);
        if (count($p) < 3) return;
        $a[] = sprintf("01010600%02x%02x", $p[2], $p[1]);
      }

      $a[] = "7fff0400";
      $a = implode("", $a);
      $gfx_blockheader->devpath = $a;
      $gfx_blockheader->devpath_len = (strlen($a) / 2);
      return 1;
    }
  }
}

function indent($isNode, $serviceDepth, $stackOfBits) {
  // stackOfBits representation, given current zero-based depth is n:
  //   bit n+1             = does depth n have children?       1=yes, 0=no
  //   bit [n, .. i .., 0] = does depth i have more siblings?  1=yes, 0=no

  if (!$isNode) $serviceDepth++;
  $op = $isNode ? "<" : "<=";
  $n = $isNode ? "printf(\"+-o \");" : "";

  $e = <<<YODA
    for (\$index = 0; \$index $op \$serviceDepth; \$index++) {
      printf( (\$stackOfBits & (1 << \$index)) ? "| " : "  " );
    }
    $n
YODA;

  eval($e);
}

function ReadPlist() {
  global $settings, $gfx;

  $doc = new DOMDocument();
  if (!($doc->loadXML(@file_get_contents($settings->ifile)))) {
    error("ReadPlist: invalid or empty input");
  }
  $tmp = $doc->documentElement;
  $tmp = $tmp->firstChild;

  while ($tmp->nodeName === "#text") {
    $tmp = $tmp->nextSibling;
  }

  $a = parse_v($tmp);

  if (!($num_blocks = count($a))) {
    error("ReadPlist: empty devices");
  }

  //create header data
  $gfx_header = new stdClass;
  $gfx_header->filesize = 0;
  $gfx_header->var1 = 0x1;
  $gfx_header->countofblocks = $num_blocks;

  $gfx = $gfx_header;

  $gfx_size = 12; // set first 12 bytes

  for ($i=0; $i < $num_blocks; $i++) {
    $key = $a[$i]["key"];
    $rec = $a[$i]["val"];
    $num_rec = count($rec);

    if (!$num_rec) {
      error("ReadPlist: empty properties");
    }

    $block_size = 0;
    $block_size += 4;
    $block_size += 4;

    $gfx_blockheader = new stdClass;
    $gfx_blockheader->blocksize = 0;
    $gfx_blockheader->records = $num_rec;

    $count = strlen($key);
    if (($count % 2) != 0) {
      $count++;
    }

    if (!devpath2hex($key, $gfx_blockheader)) {
      error("ReadPlist: device path conversion");
    }

    $block_size += $gfx_blockheader->devpath_len;

    // -[ MAGIC ]- devpath
    to_blockheader($gfx_blockheader);

    $gfx->blocks[$i] = $gfx_blockheader;

    for ($y=0; $y < $num_rec; $y++) {
      $gfx_entry = new stdClass;

      $entry = $rec[$y];

      foreach ($entry as $ek => $ev) {
        ${"e_{$ek}"} = $ev;
      }

      if (!($key_len = strlen($e_key))) {
        error("ReadPlist: empty key (length)");
      }

      $foolterminator = 0;
      if (($key_len % 2) != 0) {
        $e_key .= "\0";
        $key_len++;
        $foolterminator++;
      }

      $gfx_entry->key = $e_key;
      $gfx_entry->key_len = $key_len;

      str2uni($gfx_entry->key, $bkey, $bkey_len);

      if (!$bkey_len) {
        error("ReadPlist: string conversion");
      }

      if (!$foolterminator) {
        $bkey .= "\0\0";
        $bkey_len += 2;
      }

      $gfx_entry->bkey = bin2hex($bkey);
      $gfx_entry->bkey_len = $bkey_len;

      $block_size += 4; // key len
      $block_size += $gfx_entry->bkey_len;

      $n_val = $e_val;
      $length = strlen($n_val);
      $gfx_entry->val_type = DATA_BINARY;
      switch ($e_type) {
        case "data":
          $n_val = base64_decode($e_val);
          $length = strlen($n_val);
          $n_val = bin2hex($n_val);
          break;
        default:
          if (ishex($e_val, 1)) {
            $pad = "";
            $n_val = preg_replace("/^(0x+)/i", "", $e_val);
            $length = (strlen($n_val) / 2);
            switch ($length) {
              case 1: // int8
                $pad = "2";
                $gfx_entry->val_type = DATA_INT8;
                break;
              case 2: //int16
                $pad = "4";
                $gfx_entry->val_type = DATA_INT16;
                break;
              case 4: //int32
                $pad = "8";
                $gfx_entry->val_type = DATA_INT32;
              break;
              default:
                error("ReadPlist: incompatible hex string");
                break;
            }

            if ($pad) {
              $n_val = sprintf("%0" . $pad . "s", $n_val);
              $n_val = flip($n_val);
            }
          }

          if ($gfx_entry->val_type === DATA_BINARY) {
            $gfx_entry->val_type = DATA_STRING;
            $n_val = bin2hex($e_val);
          }
          break;
      }

      $gfx_entry->val = $n_val;
      $gfx_entry->val_len = $length;

      $block_size += 4;
      $block_size += $gfx_entry->val_len;
      //printf("%s : %d : %d\n", $gfx_entry->key, $gfx_entry->bkey_len, $gfx_entry->val_len);

      $gfx->blocks[$i]->entries[] = $gfx_entry;
    }

    $gfx_size += $block_size;
    $gfx->blocks[$i]->blocksize = $block_size;
  }

  $gfx->filesize = $gfx_size;
}

function ReadBinary() {
  global $settings, $gfx;

  switch ($settings->ifile_type) {
    case FILE_REG:
      $bp = passVar("ioreg -lw0 -p IODeviceTree -n efi -r -x | grep device-properties | sed 's/.*<//;s/>.*//;'");
      break;
    case FILE_HEX:
    case FILE_BIN:
      $bp = @file_get_contents($settings->ifile);
      if ($settings->ifile_type === FILE_BIN) {
        $bp = bin2hex($bp);
      }
      break;
  }

  if (!(string) $bp) {
    error("ReadBinary: invalid or empty input");
  }

  $bp = explode("|", chunk_split($bp, 2, "|"));

  $index = 0;

  $gfx_header = new stdClass;

  if (!($gfx_header->filesize = readint($bp, $index, 4))) {
    error("ReadBinary: cannot read filesize");
  }
  $index += 4;
  if (!($gfx_header->var1 = readint($bp, $index, 4))) {
    error("ReadBinary: cannot read var1");
  }
  $index += 4;
  if (!($gfx_header->countofblocks = readint($bp, $index, 4))) {
    error("ReadBinary: cannot read countofblocks");
  }
  $index += 4;

  $gfx = $gfx_header;
  //$gfx = (object) array_merge((array) $gfx, (array) $gfx_header);

  for ($i=0; $i < $gfx_header->countofblocks; $i++) {
    $gfx_blockheader = new stdClass;

    if (!($gfx_blockheader->blocksize = readint($bp, $index, 4))) {
      error("ReadBinary: cannot read blocksize");
    }
    $index += 4;
    if (!($gfx_blockheader->records = readint($bp, $index, 4))) {
      error("ReadBinary: cannot read blockrecords");
    }
    $index += 4;

    $size = $gfx_blockheader->blocksize;
    $tmp = $index;
    $length = 0;

    while ($tmp <= $gfx_header->filesize) { //MAX_DEVICE_PATH_LEN
      $str = readbytes($bp, $tmp, 4);
      if (preg_match("/(7|f)fff0400/i", $str)) {
        $tmp += 4;
        break;
      }
      $tmp++;
    }

    if ($tmp === $index) {
      error("ReadBinary: cannot find device path end");
    }

    $gfx_blockheader->devpath_len = abs($tmp-$index);

    readbin($bp, $size, $index, $gfx_blockheader->devpath_len, $gfx_blockheader->devpath);

    if (!$gfx_blockheader->devpath_len || !$gfx_blockheader->devpath) {
      error("ReadBinary: cannot read device path");
    }

    // -[ MAGIC ]- devpath
    to_blockheader($gfx_blockheader);

    $gfx->blocks[$i] = $gfx_blockheader;

    for($y=1; $y <= $gfx_blockheader->records; $y++) {
      $length = readint($bp, $index, 4);
      $length -= 4; $index += 4; $size -= 4;
      readbin($bp, $size, $index, $length, $bin);

      if (!($key = strbin($bin))) {
        error("ReadBinary: empty key (length)");
      }

      if (uni2str($key, $str, $str_len)) {
        $key = $str;
      } else {
        error("ReadBinary: string conversion");
      }

      $length = readint($bp, $index, 4);
      $length -= 4; $index += 4; $size -=4;
      readbin($bp, $size, $index, $length, $val);

      if (!$val) {
        error("ReadBinary: empty val (length)");
      }

      //read entries
      $gfx_entry = new stdClass;
      $gfx_entry->bkey = $bin;
      $gfx_entry->bkey_len = $length;
      $gfx_entry->key = $key;
      $gfx_entry->key_len = $str_len;
      $gfx_entry->val_type = DATA_BINARY;
      $gfx_entry->val = $val;
      $gfx_entry->val_len = $length;

      if ($settings->detect_numbers) { // detect numbers
        if (ishex($val)) {
          switch ($length) {
            case 1: // int8
              $gfx_entry->val_type = DATA_INT8;
              break;
            case 2: //int16
              $gfx_entry->val_type = DATA_INT16;
              break;
            case 4: //int32
              $gfx_entry->val_type = DATA_INT32;
              break;
            default:
              //
              break;
          }
        } else {
          //
        }
      }

      // detect strings
      if (
        $settings->detect_strings
        && ($gfx_entry->val_type === DATA_BINARY)
        && is_vchar($val)
        /*
        && is_ascii($val)
        && !is_numeric($val)
        && !ishex($val)
        && is_string($val)
        */
        ) {
        $gfx_entry->val_type = DATA_STRING;
      }

      $gfx->blocks[$i]->entries[] = $gfx_entry;
    }
  }
}

function print_gfx($gfx) {
  global $settings, $devprop_plist;

  if ($settings->verbose) {
    $bit = 0;
    $count = $gfx->countofblocks;
    $bit = $count ? 1 : 0;
    printf("o device-properties <size=%d, children=%d>\n", $gfx->filesize, $count);
  }

  if ($settings->ofile_type === FILE_XML) {
    $imp = new DOMImplementation();
    $doctype = $imp->createDocumentType("plist", "-//Apple//DTD PLIST 1.0//EN", "http://www.apple.com/DTDs/PropertyList-1.0.dtd");
    $devprop_plist = $imp->createDocument("", "", $doctype);
    $devprop_plist->formatOutput = true;
    $devprop_plist->version = "1.0";
    $devprop_plist->encoding = "UTF-8";
    $dict = $devprop_plist->createElement("dict");
  }

  foreach ($gfx->blocks as $gfx_blockheader) {
    $tmp = $gfx_blockheader->devpathstr_pci ? $gfx_blockheader->devpathstr_pci : "???";

    if ($settings->verbose) {
      indent(false, 0, $bit);
      printf("\n");

      $bit = ($count-1) ? 1 : 0;

      indent(true, 0, $bit);
      printf("%s <size=%d, records=%d>\n", $tmp, $gfx_blockheader->blocksize, $gfx_blockheader->records);

      indent(false, 0, $bit);
      printf("{\n");
    }

    if ($settings->ofile_type === FILE_XML) {
      $tmp = $devprop_plist->createElement("key", $tmp);
      $dict->appendChild($tmp);
      $dict2 = $devprop_plist->createElement("dict");
    }

    foreach ($gfx_blockheader->entries as $gfx_entry) {
      if ($settings->verbose) {
        indent(false, 1, $bit);
      }

      $key = $gfx_entry->key;
      $val = $gfx_entry->val;
      $b1 = $b2 = "";
      $pad = "";

      switch ($gfx_entry->val_type) {
        case DATA_INT8:
          $pad = "2";
          break;
        case DATA_INT16:
          $pad = "4";
          break;
        case DATA_INT32:
          $pad = "8";
          break;
        case DATA_STRING:
          $b1 = $b2 = "'";
          break;
        default:
        case DATA_BINARY:
          $b1 = "<"; $b2 = ">";
          break;
      }

      if ($pad) {
        $val = preg_replace("/([^0-9a-fx]+)/i", "", $val);
        $val = sprintf("%0" . $pad . "s", $val);
        $val = "0x" . flip($val);
      }

      if ($settings->verbose) {
        printf("\"%s\"=$b1%s$b2\n", $key, ($gfx_entry->val_type === DATA_BINARY) ? $val : strbin($val));
      }

      if ($settings->ofile_type === FILE_XML) {
        $tmp = "string";
        if (!$pad) {
          $val = strbin($val);
        }
        if ($gfx_entry->val_type === DATA_BINARY) {
          $tmp = "data";
          $val = base64_encode($val);
        }
        $k = $devprop_plist->createElement("key", $key);
        $v = $devprop_plist->createElement($tmp, $val);
        $dict2->appendChild($k);
        $dict2->appendChild($v);
      }
    }

    if ($settings->verbose) {
      indent(false, 0, $bit);
      printf("}\n");
    }

    if ($settings->ofile_type === FILE_XML) {
      $dict->appendChild($dict2);
    }
  }

  if ($settings->ofile_type === FILE_XML) {
    $plist = $devprop_plist->createElement("plist");
    $plist->setAttribute("version", "1.0");
    $plist->appendChild($dict);
    $devprop_plist->appendChild($plist);
    $ret = $devprop_plist->saveXML();
    if ($ret !== FALSE) {
      if ($settings->verbose) {
        printf("\n\n%s\n\n", $ret);
      }
      $devprop_plist->save($settings->ofile);
    }
  } else {
    $ret = "";

    if ($gfx->filesize > 0) {
      WriteUint32($gfx->filesize, $ret);
      WriteUint32($gfx->var1, $ret);
      WriteUint32($gfx->countofblocks, $ret);

      foreach ($gfx->blocks as $block) {
        WriteUint32($block->blocksize, $ret);
        WriteUint32($block->records, $ret);
        $ret .= $block->devpath;
        foreach ($block->entries as $entries) {
          WriteUint32($entries->bkey_len + 4, $ret);
          $ret .= $entries->bkey;
          WriteUint32($entries->val_len + 4, $ret);
          $ret .= $entries->val;
        }
      }
    }

    if ($ret) {
      if ($settings->ofile_type === FILE_BIN) {
        $ret = strbin($ret);
      }
      if ($ret) @file_put_contents($settings->ofile, $ret);
    }
  }

  if (!$ret || !((int) @filesize($settings->ofile))) {
    error("Create GFX");
  }
}

parse_args();

switch ($settings->ifile_type) {
  case FILE_REG:
  case FILE_HEX:
  case FILE_BIN:
    ReadBinary();
    break;
  case FILE_XML:
    ReadPlist();
    break;
  default:
    break;
}

print_gfx($gfx);

if ($settings->dump) dump($gfx);
