#!/bin/bash

# Mount EFI
# @cecekpawon 10/24/2015 14:13 PM
# thrsh.net

gVer=1.6
gTITLE="Mount EFI v${gVer}"
gUname="cecekpawon"
gME="@${gUname} | thrsh.net"
gBase="OSXOLVED"
gRepo="https://github.com/${gUname}/${gBase}"
gRepoRAW="https://raw.githubusercontent.com/${gUname}/${gBase}/master"
gScriptName=${0##*/}

isdone() {
  exit
}

C_MENU="\e[36m"
C_BLUE="\e[38;5;27m"
C_BLACK="\e[0m"
C_RED="\e[31m"
C_NUM="\e[33m"

gHEAD=`cat <<EOF
${C_MENU}============================================
${C_BLUE}${gTITLE} ${C_MENU}: ${C_RED}${gME}
${C_MENU}============================================
${C_MENU}Usages\t: ${C_BLUE}./${gScriptName} ${C_MENU}[${C_NUM}-Options${C_MENU}]
${C_MENU}\t\t: ${C_BLUE}./${gScriptName} ${C_NUM}-aou
${C_MENU}Options\t: ${C_NUM}-a${C_MENU}] auto-mount
${C_MENU}\t\t: ${C_NUM}-o${C_MENU}] auto-open
${C_MENU}\t\t: ${C_NUM}-u${C_MENU}] update-scripts
${C_MENU}--------------------------------------------
${C_MENU}Inspiration: Clover Configurator, #longlive!
${C_MENU}--------------------------------------------${C_BLACK}\n\n
EOF`

gMSG=`cat <<EOF
Please die!\n
EOF`

gAuto=0
gOpen=0
gUpdate=0
aDisk=()
aPar=()
aLabel=()
i=0

tabs -6

clear && printf "${gHEAD}"

aEFI=($(diskutil list | grep EFI | grep -o -e disk[[:digit:]]*s[[:digit:]]*))
gEFITotal=${#aEFI[@]}

if [[ $gEFITotal -eq 0 ]]; then
  echo "Zero EFI partition detected!"
  isdone
fi

while getopts :aAuUoO gOpt; do
  case "${gOpt}" in
    [aA]) gAuto=1;;
    [uU]) gUpdate=1;;
    [oO]) gOpen=1;;
  esac
done

if [[ $gUpdate -ne 0 ]]; then
  echo "Looking for updates .."

  gTmp=$(curl -sS "${gRepoRAW}/versions.json" | awk '/'$gScriptName'/ {print $2}' | sed -e 's/[^0-9\.]//g')

  if [[ $gTmp > $gVer ]]; then
    echo "Update currently available (v${gTmp}) .."

    if [[ -w "${0}" ]]; then
      gBkp="${0}.bak"
      gTmp="${0}.tmp"

      echo "Create script backup: ${gBkp}"

      curl -sS "${gRepoRAW}/${gScriptName}" -o "${gTmp}"
      gStr=`cat ${gTmp}`

      if [[ "${gStr}" =~ "bash" ]]; then
        echo "Update successfully :))"

        cp "${0}" "${gBkp}" && mv "${gTmp}" "${0}" && chmod +x "${0}"

        read -p "Relaunch script now? [yY] " gChoose
        case "${gChoose}" in
          [yY]) exec "${0}" "${@}";;
        esac

        isdone
      else
        echo "Update failed :(("
      fi
    else
      echo "Scripts read-only :(("
    fi
  else
    echo "Scripts up-to-date! :))"
  fi

  printf "\n"
fi

gStr=""
gStartup="$(diskutil info / | awk '/Identifier/' | grep -o -e disk[[:digit:]]*)"

printf "`cat <<EOF
Getting EFI disks ..

Choose one from available devices:
[${C_BLUE}#${C_BLACK}] is current startup disk!

------+-----------+--------------------------
[#]\t| Partition\t| Label
------+-----------+--------------------------\n
EOF`"

for gArg in "${aEFI[@]}"
do
  gDevice=$(echo "${gArg}" | grep -o -e disk[[:digit:]]*)
  gInfo=$(diskutil info "${gDevice}" | grep 'Media Name:' | sed -e 's/.*://;s/^ *//')

  aPar+=("${gArg}")
  aDisk+=("${gDevice}")
  aLabel+=("${gInfo}")
  C_HI=$C_BLACK

  if [[ "${gStartup}" == "${gDevice}" ]]; then
    C_HI=$C_BLUE
    (($gAuto)) && gChoose=$i
  fi

  gStr+="[${C_HI}${i}${C_BLACK}]\t| ${C_HI}${gArg}${C_BLACK}\t| ${C_HI}${gInfo}${C_BLACK}\n"

  let i++
done

printf "${gStr}\n"

((!$gAuto)) && read -p "Choose [#]: " gChoose

if [[ "${gChoose}" =~ ^[[:digit:]]+$ ]] && [[ $gChoose -lt $gEFITotal ]]; then
  gDisk=${aDisk[$gChoose]}
  gPar=${aPar[$gChoose]}
  gLabel=${aLabel[$gChoose]}
  gOEFI=$(df | grep "${gPar}")

  (($gAuto)) && printf "Auto mount EFI on: ${gDisk} .."
  printf "\nMounting: ${gPar} (on ${gLabel}) ..\n"

  [[ ! -z "${gOEFI}" ]] && diskutil unmount "${gPar}"

  diskutil mount "${gPar}"

  mEFI=$(diskutil info "${gPar}" | grep 'Mount Point:' | sed -e 's/.*://;s/^ *//')

  printf "Mounted on: ${mEFI} ..\n"

  if [[ $gOpen -ne 0 ]]; then
    printf "Auto open EFI directory ..\n"
    open "${mEFI}"
  fi
fi

#printf "${gMSG}"
isdone
