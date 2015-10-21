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
gNPKG="FakeSMC.kext"
gFPKG="${gRAW}/FakeSMC.pkg"
gPYLD="${gFPKG}/Payload"
gKEXT="${gFPKG}/${gNPKG}"

pkgutil --expand $gPKG $gRAW

if [[ -ef $gPYLD ]]; then
  cat $gPYLD | gunzip -dc | cpio -iu --quiet
  [[ -d $gKEXT ]] && cp -R $gKEXT
fi

[[ -d $gRAW ]] && rm -rf $gRAW

[[ ! -ef $gNPKG ]] && final ":("

mv $gNPKG $gDRAW &>/dev/null && cd $gDir
printf "Check new generated <file> in: <target> / \"${gDesktopDir}\" dir\n\n"

final ":)" 1
