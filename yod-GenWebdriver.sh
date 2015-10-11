#!/bin/bash

# Nvidia Generic Webdriver
# @cecekpawon Sat Apr 18 03:22:39 WIB 2015
# thrsh.net

gPKG="${1}"
gTITLE="Nvidia Generic Webdriver"
gME="@cecekpawon | thrsh.net"
gDesktopDir="/Users/$(who am i | awk '{print $1}')/Desktop"
gDir="${pwd}"
gNPKG=""

gHEAD=`cat <<EOF
${gTITLE}: ${gME}
=======================================================
Note: Your generic <pkg> dir: ${gDesktopDir}
Download : http://www.nvidia.com/Download/index.aspx
-------------------------------------------------------\n\n
EOF`

gMSG=`cat <<EOF
Usage: ./${0##*/} <pkg> / DRAG <pkg>
EOF`

final() {
  gASOUND=$((( $2 )) && echo "Glass" || echo "Basso")
  osascript -e "display notification \"${1}\" with title \"${gTITLE}\" subtitle \"${gME}\" sound name \"${gASOUND}\""
  echo -e "${1}"
  cd $gDir
  exit 0
}

clear && printf "$gHEAD"

if [[ ! -f $gPKG ]]; then
  printf "Drag <pkg> here & ENTER: "
  read gPKG
fi

if [[ -n $gPKG && -f $gPKG ]]; then
  printf "\nWorking..\n\n"
  cd $gDesktopDir

  gRAW=`basename ${gPKG%.pkg}`
  gDIST="${gRAW}/Distribution"
  gNPKG="${gRAW}-GENERIC.pkg"

  pkgutil --expand $gPKG $gRAW

  if [ -f $gDIST ]; then
    sed -i "" "/\(if.*validate.*[^}]\)/d" $gDIST
    pkgutil --flatten $gRAW $gNPKG
  fi

  if [ -d $gRAW ]; then rm -rf $gRAW; fi
else
  final "${gMSG}"
fi

if [[ -n $gNPKG && -f $gNPKG ]]; then final ":)" 1; else final ":("; fi
