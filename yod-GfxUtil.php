#!/usr/bin/php
<?php

/*

*  gfxutil
*
*  Created by mcmatrix on 08.01.08.
*  Copyright 2008 mcmatrix All rights reserved.
*
*  http://forum.netkas.org/index.php?topic=64.0

---

# PHP gfxutil
# @cecekpawon 04/12/2016 15:15 PM
# thrsh.net

---

gfxutil: [command_option] [other_options] infile outfile
Command options are:
-f name   finds objects devicepath with the given name from IODeviceTree plane
-h      print this summary
-a      show version
-i fmt    infile type, fmt is one of (default is hex): xml bin hex
-o fmt    outfile type, fmt is one of (default is xml): xml bin hex
There are some additional optional arguments:
-v      verbose mode
-s      automatically detect string format from binary data
-n      automatically detect numeric format from binary data


12.01.2007 - New version of gfxutil is out. Please test and give feedback!
06.08.2009 - gfxutil sourcecode is now available!

You are free to use it and whatever you do please keep the result free for community

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

$gVer = "1.1";
$gTITLE = "PHP gfxutil v{$gVer}";
$gUname = "cecekpawon";
$gME = "@{$gUname} | thrsh.net";
$gBase = "OSXOLVED";
$gRepo = "https://github.com/{$gUname}/{$gBase}";
$gRepoRAW = "https://raw.githubusercontent.com/{$gUname}/{$gBase}/master";

header("Content-Type", "text/html; charset=utf-8");

passthru("clear");

$gHEAD = <<<YODA
=========================================================================
$gTITLE : $gME
-------------------------------------------------------------------------
PHP port of legendary mcmatrix gfxutil
-------------------------------------------------------------------------
BETA (readonly):
 - Find "dump.hex" in script directory / get from registry
 - Print as heirarchical structure
=========================================================================\n\n
YODA;

echo $gHEAD;

function dump($value) {
  die(var_dump($value));
}

function help() {
  $FNAME = basename(__FILE__);

  $help = <<<YODA
YODA;

  die($help);
}

function passVar($str) {
  ob_start();
  passthru($str);
  return trim(ob_get_clean());
}

function update() {
  global $gVer, $gRepoRAW;

  echo "Looking for updates ..\n";

  $FNAME = basename(__FILE__);

  $json = file_get_contents("{$gRepoRAW}/versions.json");
  if (!($json = isJson($json))) die("Update failed :((\n");
  if (!isset($json[$FNAME])) die("Update failed :((\n");
  $gTmp = $json[$FNAME];
  if (version_compare($gVer, $gTmp, "<")) {
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

// CONST
$hexfile = "dump.hex";
$xmlfile = "dump.xml";

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

// Dummy settings, later to args
$settings = new stdClass;
$settings->detect_numbers = TRUE;
$settings->detect_strings = TRUE;
$settings->verbose = TRUE;
$settings->ifile_type = FILE_XML;


if ($settings->ifile_type === FILE_XML) {
  $imp = new DOMImplementation();
  $doctype = $imp->createDocumentType("plist", "-//Apple//DTD PLIST 1.0//EN", "http://www.apple.com/DTDs/PropertyList-1.0.dtd");
  $devprop_plist = $imp->createDocument("", "", $doctype);
  $devprop_plist->formatOutput = true;
  $devprop_plist->version = "1.0";
  $devprop_plist->encoding = "UTF-8";
}

function readbin($bp, &$size, &$index, $len, &$ret) {
  $ret="";
  if ($size) {
    $s = $index;
    if ($len <= $size) {
        $nlen = $s + $len;
        for ($i=$s; $i < $nlen; $i++) {
          $ret .= $bp[$i];
          $index++;
        }
        $size -= $len;
        return 1;
    }
  }
  // error: read_binary: invalid binary data
}

function is_vchar($s) {
  return preg_match('/^[\w\s\p{P}]+$/', hex2bin($s));
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

function ishex($s) {
  return (ctype_xdigit($s) && in_array(strlen($s), array(2, 4, 8)));
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

function uni2str($d, $length, &$str, &$len) {
  $x = str_split($d, 2);
  $length = (count($x) + 1);
  if($length != 0) {
    $len = 0;
    $str = array();
    while ($length--) {
      $unich = $x[$len++];
      if($unich < 0x80) {
        $str[$len++] = $unich;
      } else if ($unich < (1 << 11)) {
        $str[$len++] = ord(0xC0 | ($unich >> 6));
        $str[$len++] = ord(0x80 | ($unich & 0x3F));
      } else {
        $str[$len++] = ord(0xE0 | ($unich >> 12));
        $str[$len++] = ord(0x80 | (($unich >> 6) & 0x3F));
        $str[$len++] = ord(0x80 | ($unich & 0x3F));
      }
    }

    //if ($length < 0) $str[$len] = '00';
    $str = implode("", $str);
    return 1;
  }

  //fprintf(stderr, "unicode2str: invalid binary unicode data\n");
  return 0;
}

$gfx = new stdClass;

$gfx->blocks = array();

$dump_hex = dirname(__FILE__) . "/{$hexfile}";
$dump_xml = dirname(__FILE__) . "/{$xmlfile}";

if (file_exists($dump_hex)) {
  $dump_hex = file_get_contents(dirname(__FILE__) . "/{$hexfile}");
} else {
  $dump_hex = passVar("ioreg -lw0 -p IODeviceTree -n efi -r -x | grep device-properties | sed 's/.*<//;s/>.*//;'");
}

$bp = explode("|", chunk_split($dump_hex, 2, "|"));

//define("MAX_DEVICE_PATH_LEN", count($bp)); //-1?

$index = 0;

$gfx_header = new stdClass;

$gfx_header->filesize = readint($bp, $index, 4);
$index += 4;
$gfx_header->var1 = readint($bp, $index, 4);
$index += 4;
$gfx_header->countofblocks = readint($bp, $index, 4);
$index += 4;

$gfx->gfx_header = $gfx_header;
//$gfx = (object) array_merge((array) $gfx, (array) $gfx_header);

for ($i=0; $i < $gfx_header->countofblocks; $i++) {
  $gfx_blockheader = new stdClass;

  $gfx_blockheader->blocksize = readint($bp, $index, 4);
  $index += 4;
  $gfx_blockheader->records = readint($bp, $index, 4);
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

  $gfx_blockheader->devpath_len = abs($tmp-$index);

  readbin($bp, $size, $index, $gfx_blockheader->devpath_len, $gfx_blockheader->devpath);

  // -[ MAGIC ]- devpath
  preg_match("/[a-f0-9]{16}([0-9]{8})(.+)[7f]fff0400/i", $gfx_blockheader->devpath, $matches);
  //print_r($matches);

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

  $gfx->blocks[$i] = $gfx_blockheader;

  for($y=1; $y <= $gfx_blockheader->records; $y++) {
    $length = readint($bp, $index, 4);
    $length -= 4; $index += 4; $size -= 4;
    readbin($bp, $size, $index, $length, $bin);

    $key = hex2bin($bin);

    if (uni2str($bin, $length, $str, $str_len)) {
      $key = hex2bin($str);
    }

    $length = readint($bp, $index, 4);
    $length -= 4; $index += 4; $size -=4;
    readbin($bp, $size, $index, $length, $val);

    //read entries
    $gfx_entry = new stdClass;
    $gfx_entry->bkey = $bin;
    $gfx_entry->bkey_len = $length;
    $gfx_entry->key = $key;
    $gfx_entry->key_len = $str_len;
    $gfx_entry->val_type = DATA_BINARY; // set default data type
    $gfx_entry->val = $val;
    $gfx_entry->val_len = $length;

    if ($settings->detect_numbers) { // detect numbers
      if (ishex($val)) {
        switch ($length) {
          case 1: // int8 sizeof(0xFF)
            $gfx_entry->val_type = DATA_INT8;
          break;
          //case:
          case 2: //int16 sizeof(0xFFFF)
            $gfx_entry->val_type = DATA_INT16;
          break;
          case 4: //int32 sizeof(0xFFFFFFFF)
            $gfx_entry->val_type = DATA_INT32;
          break;
          default:
            //$gfx_entry->val = hex2bin($val);
          break;
        }
      } else {
        //$gfx_entry->val = ishex($val) ? flip($val) : hex2bin($val);
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

function indent($isNode, $serviceDepth, $stackOfBits) {
  // stackOfBits representation, given current zero-based depth is n:
  //   bit n+1             = does depth n have children?       1=yes, 0=no
  //   bit [n, .. i .., 0] = does depth i have more siblings?  1=yes, 0=no

  if (!$isNode) $serviceDepth++;
  $op = $isNode ? "<" : "<=";
  $n = $isNode ? "printf(\"+-o \");" : "";

  $e = <<<HTML
    for (\$index = 0; \$index $op \$serviceDepth; \$index++) {
      printf( (\$stackOfBits & (1 << \$index)) ? "| " : "  " );
    }
    $n
HTML;

  eval($e);
}

function print_gfx($gfx) {
  global $settings, $devprop_plist, $dump_xml;

  if ($settings->verbose) {
    $bit = 0;
    $count = $gfx->gfx_header->countofblocks;
    $bit = $count ? 1 : 0;
    printf("o device-properties <size=%d, children=%d>\n", $gfx->gfx_header->filesize, $count);
  }

  if ($settings->ifile_type === FILE_XML) {
    $dict = $devprop_plist->createElement("dict");
  }

  foreach ($gfx->blocks as $gfx_blockheader_tmp) {
    //$devpath_text = ConvertDevicePathToText($gfx_blockheader_tmp->devpath, 1, 1);
    //if($devpath_text  != NULL) printf("%s <size=%d, records=%d>\n",($devpath_text != NULL)?$devpath_text:"???", $gfx_blockheader_tmp->blocksize, $gfx_blockheader_tmp->records);
    $tmp = $gfx_blockheader_tmp->devpathstr_pci ? $gfx_blockheader_tmp->devpathstr_pci : "???";

    if ($settings->verbose) {
      indent(false, 0, $bit);
      printf("\n");

      $bit = ($count-1) ? 1 : 0;

      indent(true, 0, $bit);
      printf("%s <size=%d, records=%d>\n", $tmp, $gfx_blockheader_tmp->blocksize, $gfx_blockheader_tmp->records);

      indent(false, 0, $bit);
      printf("{\n");
    }

    if ($settings->ifile_type === FILE_XML) {
      $tmp = $devprop_plist->createElement("key", $tmp);
      $dict->appendChild($tmp);
      $dict2 = $devprop_plist->createElement("dict");
    }

    foreach ($gfx_blockheader_tmp->entries as $gfx_entry_tmp) {
      if ($settings->verbose) {
        indent(false, 1, $bit);
      }

      $key = $gfx_entry_tmp->key;
      $val = $gfx_entry_tmp->val;
      $b1 = $b2 = "";

      switch ($gfx_entry_tmp->val_type) {
        case DATA_INT8:
        case DATA_INT16:
        case DATA_INT32:
          $val = flip($val);
          $val = sprintf("0x%0" . $gfx_entry_tmp->val_type . "s", $val);
        break;
        case DATA_STRING:
          $val = hex2bin($val);
          $b1 = $b2 = "'";
        break;
        default:
        case DATA_BINARY:
          $b1 = "<"; $b2 = ">";
        break;
      }

      if ($settings->verbose) {
        printf("\"%s\"=$b1%s$b2\n", $key, $val);
      }

      if ($settings->ifile_type === FILE_XML) {
        $tmp = "string";
        if ($gfx_entry_tmp->val_type === DATA_BINARY) {
          $tmp = "data";
          $val =  base64_encode($val);
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

    if ($settings->ifile_type === FILE_XML) {
      $dict->appendChild($dict2);
    }
  }

  if ($settings->ifile_type === FILE_XML) {
    $plist = $devprop_plist->createElement("plist");
    $plist->setAttribute("version", "1.0");
    $plist->appendChild($dict);
    $devprop_plist->appendChild($plist);
    if ($settings->verbose) {
      printf("\n\n%s\n\n", $devprop_plist->saveXML());
    }
    $devprop_plist->save($dump_xml);
  }

  return;
}

if ($settings->verbose || $settings->ifile_type) print_gfx($gfx);
else dump($gfx);
