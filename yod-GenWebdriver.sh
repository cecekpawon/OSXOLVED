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
  exit
}

clear && printf "${gHEAD}"

if [[ ! -ef $gPKG ]]; then
  printf "Drag <pkg> here & ENTER: "
  read gPKG
fi

[[ ! -ef $gPKG ]] && final "${gMSG}"

printf "\nWorking..\n\n"
cd $gDesktopDir

gRAW=`basename ${gPKG%.pkg}`
gDRAW=`dirname ${gPKG%.pkg}`
gDIST="${gRAW}/Distribution"
gNPKG="${gRAW}-GENERIC.pkg"

pkgutil --expand $gPKG $gRAW

if [[ -ef $gDIST ]]; then
  sed -i "" "/\(if.*validate.*[^}]\)/d" $gDIST
  pkgutil --flatten $gRAW $gNPKG
fi

[[ -d $gRAW ]] && rm -rf $gRAW

[[ ! -ef $gNPKG ]] && final ":("

mv $gNPKG $gDRAW &>/dev/null && cd $gDir
printf "Check new generated <file> in: <target> / \"${gDesktopDir}\" dir\n\n"

final ":)" 1
