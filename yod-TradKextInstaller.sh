#!/bin/bash

# Trad Kext Installer
# @cecekpawon 10/15/2015 00:13 AM
# thrsh.net

gID=$(id -u)
gKext="${1}"
gTITLE="Trad Kext Installer"
gME="@cecekpawon | thrsh.net"
gUser="$(who am i | awk '{print $1}')"
gDesktopDir="/Users/${gUser}/Desktop"
gKextBackupDir="${gDesktopDir}/KextsBackup"
gDest[1]="/Library/Extensions"
gDest[2]="/System/Library/Extensions"

gHEAD=`cat <<EOF
${gTITLE}: ${gME}
============================================
Backup dir: ${gKextBackupDir}
Note: SIP - <csrutil status>
Filesystem Protections: disabled
Kext Signing: disabled (3rd party)
--------------------------------------------\n\n
EOF`

gMSG=`cat <<EOF
No valid kexts detected!
Usage: ./${0##*/} <kexts> / DRAG <kexts>
EOF`

final() {
  gASOUND=$((( $2 )) && echo "Glass" || echo "Basso")
  osascript -e "display notification \"${1}\" with title \"${gTITLE}\" subtitle \"${gME}\" sound name \"${gASOUND}\""
  echo -e "${1}"

  if (( $2 )); then
    #printf "\nReboot now (y/n)? "
    #read choice
    #case "$choice" in
    #  [yY]) sudo reboot;;
    #esac

    #show restart dialog
    osascript -e 'tell app "loginwindow" to «event aevtrrst»'
  fi

  exit 0
}

valid() {
  [[ -d $1 && "${1##*.}" == "kext" ]] && return 0 || return 1
}

main() {
  printf "${gHEAD}"

  gArgs=("$@")
  gKexts=()

  if [[ "$#" -lt 1 ]]; then
    printf "Drag <kext> here & ENTER: "
    read gVar
    gArgs=($gVar)
  fi

  for arg in "${gArgs[@]}"
  do
    if valid "$arg"; then
      gKexts+=("$arg")
    fi
  done

  if [[ ${#gKexts[@]} -eq 0 ]]; then
    final "$gMSG"
  fi

  read -p "$(printf "`cat <<EOF
Install to:
[1] ${gDest[1]}
[2] ${gDest[2]}

Choice:
EOF`") " uDest

  case $uDest in
    [12])
        dDest="${gDest[${uDest}]}"
        ;;
    *)  final ":("
        ;;
  esac

  printf "\nWorking..\n\n"
  #printf "Start backup & install:\n"

  let i=0

  for gKext in "${gKexts[@]}"
  do
    kDest="${dDest}/${gKext##*/}"
    let i++

    printf "#$i: %s\n" $kDest

    if [[ -d $kDest ]]; then
      #gDate = $(date +"%Y-%m-%d_%H:%M:%S")
      gHashDir="${gKextBackupDir}/$(find $kDest -type f -print0 | xargs -0 md5 -q | md5)"
      if [[ ! -d $gHashDir ]]; then
        mkdir -p $gHashDir
        cp -a $kDest $gHashDir
      fi
      sudo chown -R $gUser $gKextBackupDir
      rm -rf $kDest
    fi

    cp -R $gKext $dDest
    sudo chown -R root:wheel $kDest
    sudo chmod -R 755 $kDest
  done

  printf "\nRepair Permissions & Update Caches..\n"

  sudo touch $dDest
  sudo kextcache -system-prelinked-kernel &>/dev/null
  sudo kextcache -system-caches &>/dev/null

  final ":)" 1
}

clear

if [ $gID -ne 0 ]; then
  sudo "${0}" "$@"
else
  main "$@"
fi
