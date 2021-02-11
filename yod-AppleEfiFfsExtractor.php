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
                  "EfiOSInfo" =>
                    array (
                      "2ECED69B-2793-4388-BA3C-823040EBCCD2"
                      ),

                  "EfiDevicePathPropertyDatabase" =>
                    array (
                      "35628CFC-3CFF-444F-99C1-D5F06A069914",
                      "BDFDE060-7E41-4EAE-AD9B-E5BBA7A48A3A"
                      ),

                  "EdkPartition" =>
                    array (
                      "43B93232-AFBE-11D4-BD0F-0080C73C8881",
                      "1FA1F39E-FEFF-4AAE-BD7B-38A070A3B609"
                      ),

                  "ExFatDxe" =>
                    array (
                      "44883EC1-C77C-1749-B73D-30C7B468B556",
                      "714845FE-F8B8-4B45-9AAE-708ECDDFCB77",
                      "C1DBFAE7-D47A-4D0D-83B5-9E6F4162D15C"
                      ),

                  "AppleBootPolicy" =>
                    array (
                      "7064F130-846E-4CE2-83C1-4BBCBCBF1AE5",
                      "4391AA92-6644-4D8A-9A84-DDD405C312F3"
                      ),

                  "Fat" =>
                    array (
                      "961578FE-B6B7-44C3-AF35-6BC705CD2B1F"
                      ),

                  "HfsPlus" =>
                    array (
                      "05DFCA46-141F-11DF-8508-E38C0891C4E2",
                      "4CF484CD-135F-4FDC-BAFB-1AA104B48D36",
                      "AE4C11C8-1D6C-F24E-A183-E1CA36D1A8A9"
                    ),

                  "UserInterfaceThemeDriver" =>
                    array (
                      "BC468182-0C0B-D645-A8AC-FB5D81076AE8"
                      ),

                  "EnglishDxe" =>
                    array (
                      "CD3BAFB6-50FB-4FE8-8E4E-AB74D2C1A600",
                      "8F26EF0A-4F7F-4E4B-9802-8C22B700FFAC"
                      ),

                  "ApfsJumpStart" =>
                      array (
                      "CFFB32F4-C2A8-48BB-A0EB-6C3CCA3FE847"
                      )
                  );

function rm_dir ($path) {
  passthru (((DIRECTORY_SEPARATOR === "\\") ? "rmdir /S /Q" : "rm -rf") . " \"$path\"");
}

function normalise_path ($path) {
  return str_replace ((DIRECTORY_SEPARATOR === "\\") ? "/" : "\\", DIRECTORY_SEPARATOR, $path);
}

function search_body ($folder, $name, $guid, $efi) {
  $iti = new RecursiveDirectoryIterator ($folder);
  foreach (new RecursiveIteratorIterator ($iti) as $file) {
    $found = false;
    if ($efi) {
      if (preg_match ("#[\\x2f\\x5c]\d+\s+PE32\s+image\s+section[\\x2f\\x5c]body.bin$#", $file)) {
        $found = true;
      }
    } else {
      if (preg_match ("#[\\x2f\\x5c]\d+\s+($name|$guid)[\\x2f\\x5c]body.bin$#", $file)) {
        $found = true;
      }
    }
    if ($found) {
      return $file->__toString ();
    }
  }
  return "";
}

$cdir = __DIR__;

chdir ("$cdir");

$outputdir = "$cdir/Output";

$FFCP = false;
if (!file_exists ("$FF")) {
  $FFTMP = normalise_path ("FUTMP/Tools/EFIPayloads/$FF");
  if (file_exists ("$FFTMP")) {
    @copy ("$FFTMP", "$FF");
    $FFCP = true;
  }
}
if (file_exists ("$FF")) {
  $outputdir .= "/$FF";
  $FF = normalise_path (realpath ("$FF"));
} else {
  die ("! $FF not exists!\n");
}

$outputdir = normalise_path ("$outputdir");

@mkdir ("$outputdir", 0777, true);

if (file_exists ("$UEFIExtract")) {
  $UEFIExtract = normalise_path (realpath ("$UEFIExtract"));
} else {
  die ("! $UEFIExtract not exists!\n");
  die ("! Download UEFIExtract (https://github.com/LongSoft/UEFITool/releases)\n");
}

passthru ("cd $cdir");

$aguids = array ();
foreach ($aUEFIExtract as $key => $value) {
  $count = count ($aUEFIExtract[$key]);
  for ($i=0; $i < $count; $i++) {
    array_push ($aguids, $aUEFIExtract[$key][$i]);
  }
}

$aguids = implode (" ", $aguids);

$dump = "$FF.dump";

if (!file_exists ("$dump")) {
  passthru ("$UEFIExtract $FF $aguids");

  if ($FFCP) {
    @unlink ("$FF");
  }
}

if (!file_exists ("$dump")) {
  die ("! Extracting failed\n");
}

foreach ($aUEFIExtract as $key => $value) {
  $name = $key;
  $count = count ($aUEFIExtract[$key]);
  for ($i=0; $i < $count; $i++) {
    $guid = $aUEFIExtract[$key][$i];
    print ("Get $guid ($name)\n");
    $filepath = normalise_path (search_body ($dump, $name, $guid, false));
    if (file_exists ("$filepath")) {
      print ("- Found $name.ffs\n");
      $ddir = dirname ($filepath);
      $header = normalise_path ("$ddir/header.bin");
      $ffs = normalise_path ("$outputdir/$name.ffs");
      $fp = fopen ("$ffs", "wb");
      fputs ($fp, file_get_contents ("$header") . file_get_contents ("$filepath"));
      fclose ($fp);
      $filepath = normalise_path (search_body ($dump, $name, $guid, true));
      if (file_exists ("$filepath")) {
        print ("- Found $name.efi\n");
        $efi = normalise_path ("$outputdir/$name.efi");
        @copy ("$filepath", "$efi");
      }
      break;
    } else {
      print ("! No Bin\n");
    }
  }
}

rm_dir ("$dump");
