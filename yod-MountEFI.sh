#!/bin/bash

# Mount EFI
# @cecekpawon 10/24/2015 14:13 PM
# thrsh.net

gTITLE="Mount EFI"
gME="@cecekpawon | thrsh.net"

gHEAD=`cat <<EOF
${gTITLE}: ${gME}
=============================================
Auto mount startup disk: ./${0##*/} -a
Inspiration: Clover Configurator, #longlive!
---------------------------------------------\n\n
EOF`

gMSG=`cat <<EOF
Please die!
EOF`

gAuto=false
aDisk=()
aPar=()
aLabel=()
gStr=""
i=0
C_BLUE="\e[38;5;27m"
C_BLACK="\e[0m"

clear && printf "${gHEAD}"

tabs -2

aEFI=($(diskutil list | grep EFI | grep -o -e disk[[:digit:]]s[[:digit:]]))
gEFITotal=${#aEFI[@]}

if [[ $gEFITotal -eq 0 ]]; then
  echo "Zero EFI partition detected!"
  exit
fi

[[ "${1}" == "-a" ]] && gAuto=true

gStartup="$(diskutil info / | awk '/Identifier/' | grep -o -e disk[[:digit:]])"

printf "`cat <<EOF
Getting EFI disks ..

Choose one from available devices:
[${C_BLUE}#${C_BLACK}] is current startup disk!

----+-----------+----------------------------
[#]\t| Partition\t| Label
----+-----------+----------------------------\n
EOF`"

for gArg in "${aEFI[@]}"
do
  gDevice=$(echo "${gArg}" | grep -o -e disk[[:digit:]])
  gInfo=$(diskutil info "${gDevice}" | awk '/Media Name:/' | sed -e 's/^.*://' | sed 's/^ *//;s/ *Media//')

  aPar+=("${gArg}")
  aDisk+=("${gDevice}")
  aLabel+=("${gInfo}")
  C_HI=$C_BLACK

  if [[ "${gStartup}" == "${gDevice}" ]]; then
    C_HI=$C_BLUE
    [[ $gAuto == true ]] && gChoose=$i
  fi

  gStr+="[${C_HI}${i}${C_BLACK}]\t| ${C_HI}${gArg}${C_BLACK}\t\t| ${C_HI}${gInfo}${C_BLACK}\n"

  let i++
done

printf "${gStr}\n"

[[ $gAuto != true ]] && read -p "Choose [#]: " gChoose

if [[ "${gChoose}" =~ ^[[:digit:]]+$ ]] && [[ $gChoose -lt $gEFITotal ]]; then
  gDisk=${aDisk[$gChoose]}
  gPar=${aPar[$gChoose]}
  gLabel=${aLabel[$gChoose]}
  gOEFI=$(df | grep "${gPar}")
  mEFI="/Volumes/EFI(${gPar})"

  [[ $gAuto == true ]] && printf "Auto mount EFI on: ${gDisk} .."
  printf "\nMounting ${gPar} (on ${gLabel}) ..\n\n"

  [[ ! -z "${gOEFI}" ]] && diskutil unmount "${gPar}"
  [[ ! -d "${mEFI}" ]] && mkdir "${mEFI}"

  diskutil mount -mountPoint "${mEFI}" "${gPar}"

  exit
fi

echo "${gMSG}"
