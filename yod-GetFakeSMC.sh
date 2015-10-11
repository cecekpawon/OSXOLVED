#!/bin/bash

# Extract FakeSMC from HWSensors
# @cecekpawon Thu Aug 13 19:29:21 2015
# thrsh.net

gPKG="${1}"
gTITLE="Extract FakeSMC from HWSensors"
gME="@cecekpawon | thrsh.net"
gDesktopDir="/Users/$(who am i | awk '{print $1}')/Desktop"
gDir="${pwd}"
gNPKG=""

gHEAD=`cat <<EOF
${gTITLE}: ${gME}
=======================================================
Note: Your FakeSMC <pkg> dir: ${gDesktopDir}
Download : http://sourceforge.net/projects/hwsensors/
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

clear && printf "${gHEAD}"

if [[ ! -f $gPKG ]]; then
  printf "Drag <pkg> here & ENTER: "
  read gPKG
fi

if [[ -n $gPKG && -f $gPKG ]]; then
  printf "\nWorking..\n\n"
  cd $gDesktopDir

  gRAW=`basename ${gPKG%.pkg}`
  gNPKG="FakeSMC.kext"
  gFPKG="${gRAW}/FakeSMC.pkg"
  gPYLD="${gFPKG}/Payload"
  gKEXT="${gFPKG}/${gNPKG}"

  pkgutil --expand $gPKG $gRAW
  if [ -f $gPYLD ]; then
    cat $gPYLD | gunzip -dc | cpio -iu --quiet
    if [ -d $gKEXT ]; then cp -R $gKEXT; fi
  fi

  if [ -d $gRAW ]; then rm -rf $gRAW; fi
else
  final "${gMSG}"
fi

if [[ -n $gNPKG && -d $gNPKG ]]; then final ":)" 1; else final ":("; fi
