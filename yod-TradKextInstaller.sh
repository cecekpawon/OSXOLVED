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
Note: SIP - <csrutil status>
Filesystem Protections: disabled
Kext Signing: disabled (3rd party)
--------------------------------------------\n\n
EOF`

gMSG=`cat <<EOF
Usage: ./${0##*/} <kext> / DRAG <kext>
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

main() {
  printf "${gHEAD}"

  if [[ ! -d $gKext ]]; then
    printf "Drag <kext> here & ENTER: "
    read gKext
  fi

  if [[ -d $gKext ]]; then
    read -p "$(printf "`cat <<EOF

Install to:
[1] ${gDest[1]}
[2] ${gDest[2]}

Choice:
EOF`") " uDest

    case $uDest in
      [12])
          dDest="${gDest[${uDest}]}"
          kDest="${dDest}/${gKext##*/}"

          printf "\nWorking.."

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

          printf "\nRepairing Permissions.."

          sudo chown -R root:wheel $kDest
          sudo chmod -R 755 $kDest
          sudo touch $dDest

          printf "\nUpdating Caches..\n"

          sudo kextcache -system-prelinked-kernel &>/dev/null
          sudo kextcache -system-caches &>/dev/null

          final ":)" 1
          ;;

      *)  final ":(";;
    esac
  fi

  final "$gMSG"
}

clear

if [ $gID -ne 0 ]; then
  sudo "${0}" "$@"
else
  main "$@"
fi
