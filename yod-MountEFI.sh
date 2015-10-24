#!/bin/bash

# Mount EFI
# @cecekpawon 10/24/2015 14:13 PM
# thrsh.net

gTITLE="Mount EFI"
gME="@cecekpawon | thrsh.net"

gHEAD=`cat <<EOF
${gTITLE}: ${gME}
============================================
Inspiration: Clover Configurator, #longlive!
--------------------------------------------\n\n
EOF`

gMSG=`cat <<EOF
Please die!
EOF`

clear && printf "${gHEAD}"

tabs -2

aEFI=($(diskutil list | grep EFI | grep -o -e disk[[:digit:]]s[[:digit:]]))
gEFITotal=${#aEFI[@]}

if [[ $gEFITotal -eq 0 ]]; then
  echo "Zero EFI partition detected!"
  exit
fi

#aDisk=()
aPar=()
aLabel=()
gStr=""
i=0

printf "`cat <<EOF
Getting EFI disks ..
Choose one from available devices:

----+-----------+---------------------------
[#]\t| Partition\t| Label
----+-----------+---------------------------\n
EOF`"

for gArg in "${aEFI[@]}"
do
  gDevice=$(echo "${gArg}" | grep -o -e disk[[:digit:]])
  gInfo=$(diskutil info "${gDevice}" | awk '/Media Name:/' | sed -e 's/^.*://' | sed 's/^ *//;s/ *Media//')

  aPar+=("${gArg}")
  #aDisk+=("$gDevice")
  aLabel+=("${gInfo}")

  gStr+="[${i}]\t| ${gArg}\t\t| ${gInfo}\n"
  let i++
done

printf "${gStr}\nChoose [#]: "
read gChoose

if [[ "${gChoose}" =~ ^[[:digit:]]+$ ]] && [[ $gChoose -lt $gEFITotal ]]; then
  gPar=${aPar[$gChoose]}
  gLabel=${aLabel[$gChoose]}
  gOEFI=$(df | grep "${gPar}")
  mEFI="/Volumes/EFI(${gPar})"

  printf "\nMounting ${gPar} (on ${gLabel}}) ..\n\n"

  [[ ! -z "${gOEFI}" ]] && diskutil unmount "${gPar}"
  [[ ! -d "${mEFI}" ]] && mkdir "${mEFI}"

  diskutil mount -mountPoint "${mEFI}" "${gPar}"

  exit
fi

echo "${gMSG}"
