#!/usr/bin/php
<?php

/*
---
Apple EFI/FFS Extractor
---
  - chmod+x.script.
  - Place FirmwareFile ($FF) & UEFIExtract into same directory as script.
  - UEFIExtract: https://github.com/LongSoft/UEFITool/releases
---
@cecekpawon  Tue Mar 12 08:53:12 2018
---
*/

$FF = "MBP143.fd";

$UEFIExtract = "UEFIExtract";
if (DIRECTORY_SEPARATOR === '\\') {
  $UEFIExtract .= ".exe";
}

$aUEFIExtract = array (
                    array ("2ECED69B-2793-4388-BA3C-823040EBCCD2", "EfiOSInfo"),
                    array ("35628CFC-3CFF-444F-99C1-D5F06A069914", "EfiDevicePathPropertyDatabase"),
                    array ("43B93232-AFBE-11D4-BD0F-0080C73C8881", "EdkPartition"),
                    array ("44883EC1-C77C-1749-B73D-30C7B468B556", "ExFatDxe"),
                    array ("7064F130-846E-4CE2-83C1-4BBCBCBF1AE5", "AppleBootPolicy"),
                    array ("961578FE-B6B7-44C3-AF35-6BC705CD2B1F", "Fat"),
                    array ("AE4C11C8-1D6C-F24E-A183-E1CA36D1A8A9", "HfsPlus"),
                    array ("BC468182-0C0B-D645-A8AC-FB5D81076AE8", "UserInterfaceThemeDriver"),
                    array ("CD3BAFB6-50FB-4FE8-8E4E-AB74D2C1A600", "EnglishDxe"),
                    array ("CFFB32F4-C2A8-48BB-A0EB-6C3CCA3FE847", "ApfsJumpStart"),
                    //array ("201A92E1-2D0F-48E9-A3AB-93E1695A92F2", "AppleHDA")
                  );

$cdir = __DIR__;

function rsearch ($folder, $arr, $filename, $pattern) {
  $iti = new RecursiveDirectoryIterator ($folder);
  foreach (new RecursiveIteratorIterator ($iti) as $file) {
    if ((strpos ($file, $filename) !== false)
      && ((strpos ($file, $arr[0]) !== false) || (strpos ($file, $arr[1]) !== false))) {
      $f = file_get_contents ($file->__toString ());
      $a = unpack ("H*", $f)[1];
      if (preg_match ($pattern, $a, $c)) {
        return $file->__toString ();
      }
    }
  }
  return "";
}

function normalise_path ($path) {
  return (DIRECTORY_SEPARATOR === '\\')
    ? str_replace('/', '\\', $path)
    : str_replace('\\', '/', $path);
}

chdir ("$cdir");

$outputdir = "$cdir/Output";

if (file_exists ("$FF")) {
  $outputdir .= "/$FF";
  $FF = normalise_path (realpath ("$FF"));
} else {
  die ("! $FF not exists!\n");
}

$outputdir = normalise_path ("$outputdir");

mkdir ("$outputdir", 0777, true);

if (file_exists ("$UEFIExtract")) {
  $UEFIExtract = normalise_path (realpath ("$UEFIExtract"));
} else {
  die ("! Download UEFIExtract (https://github.com/LongSoft/UEFITool/releases)\n");
}

passthru ("cd $cdir");
$count = count ($aUEFIExtract);

$aguids = array ();
for ($i=0; $i < $count; $i++) {
  array_push ($aguids, $aUEFIExtract[$i][0]);
}
$aguids = implode (" ", $aguids);

$dump = "$FF.dump";

if (!file_exists ("$dump")) {
  passthru ("$UEFIExtract $FF $aguids");
}

if (!file_exists ("$dump")) {
  die ("! Extracting failed\n");
}

for ($i=0; $i < $count; $i++) {
  $guid = $aUEFIExtract[$i][0];
  $name = $aUEFIExtract[$i][1];
  print ("Get $name ($guid)\n");
  $filepath = normalise_path (rsearch ($dump, $aUEFIExtract[$i], "body.bin", "#^([a-f0-9]{8})4D5A#i"));
  if (file_exists ("$filepath")) {
    print ("- Found $name.ffs\n");
    $ddir = dirname ($filepath);
    $header = normalise_path ("$ddir/header.bin");
    $ffs = normalise_path ("$outputdir/$name.ffs");
    $fp = fopen ("$ffs", "wb");
    fputs ($fp, file_get_contents ("$header") . file_get_contents ("$filepath"));
    fclose ($fp);
    $filepath = normalise_path (rsearch ($dump, $aUEFIExtract[$i], "body.bin", "#^4D5A#i"));
    if (file_exists ("$filepath")) {
      print ("- Found $name.efi\n");
      $efi = normalise_path ("$outputdir/$name.efi");
      if (DIRECTORY_SEPARATOR === '\\') {
        passthru ("copy /B /Y \"$filepath\" \"$efi\"");
      } else {
        passthru ("cp \"$filepath\" \"$efi\"");
      }
    }
  } else {
    print ("! No Bin\n");
  }
  print ("\n");
}

if (DIRECTORY_SEPARATOR === '\\') {
  passthru ("rmdir /S /Q \"$dump\"");
} else {
  passthru ("rm -rf \"$dump\"");
}
