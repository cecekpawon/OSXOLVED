#!/bin/bash

# Trad Kext Installer
# @cecekpawon 10/15/2015 00:13 AM
# thrsh.net

gVer=1.0
gID=$(id -u)
gKext="${1}"
gTITLE="Trad Kext Installer v${gVer}"
gME="@cecekpawon | thrsh.net"
gRepo="https://github.com/cecekpawon/OSXOLVED"
gScriptName=${0##*/}
gUser="$(who am i | awk '{print $1}')"
gDesktopDir="/Users/${gUser}/Desktop"
gKextBackupDir="${gDesktopDir}/KextsBackup"
gDest[1]="/Library/Extensions"
gDest[2]="/System/Library/Extensions"
gDest[3]="Other"
bPermissions=1

gHEAD=`cat <<EOF
${gTITLE}: ${gME}
=================================================
Usages\t: ./${gScriptName} [<kexts>] -u
Options\t: -u] update-scripts
Backup\t: ${gKextBackupDir}
Note\t\t: SIP - <csrutil status>
\t\t\t\t\t- Filesystem Protections: disabled
\t\t\t\t\t- Kext Signing: disabled (3rd party)
-------------------------------------------------\n\n
EOF`

gMSG=`cat <<EOF
No valid kexts detected!
EOF`

tabs -2

final() {
  gASOUND=$((( $2 )) && echo "Glass" || echo "Basso")
  osascript -e "display notification \"${1}\" with title \"${gTITLE}\" subtitle \"${gME}\" sound name \"${gASOUND}\""
  echo -e "${1}"

  if (( $3 )); then
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

update() {
  echo "Looking for updates .."

  gTmp=$(curl -sS "${gRepo}/versions.json" | awk '/'$gScriptName'/ {print $2}' | sed -e 's/[^0-9\.]//g')

  if [[ $gTmp > $gVer ]]; then
    echo "Update currently available (v${gTmp}) .."

    if [[ -w "${0}" ]]; then
      gBkp="${0}.bak"
      gTmp="${0}.tmp"

      echo "Create script backup: ${gBkp}"

      curl -sS "${gRepo}/${gScriptName}" -o "${gTmp}"
      gStr=`cat ${gTmp}`

      if [[ "${gStr}" =~ "bash" ]]; then
        echo "Update successfully :))"

        cp "${0}" "${gBkp}" && mv "${gTmp}" "${0}" && chmod +x "${0}"

        read -p "Relaunch script now? [yY] " gChoose
        case "${gChoose}" in
          [yY]) exec "${0}" "${@}";;
        esac

        exit
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
}

gUpdate=0

main() {
  printf "${gHEAD}"

  gArgs=("$@")
  gKexts=()

  if [[ "$#" -lt 1 ]]; then
    printf "Drag <kext> here & ENTER: "
    read gVar
    gArgs=("${gVar}")
  fi

  for arg in "${gArgs[@]}"
  do
    if [[ "$arg" =~ ^\-[uU]$ ]]; then
      update
    elif valid "$arg"; then
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
[3] ${gDest[3]}

Choice:
EOF`") " uDest

  case "${uDest}" in
    [12])
          dDest="${gDest[${uDest}]}"
          ;;
       3)
          read -p "Enter destination directory: " uDest
          if [[ -d "${uDest}" ]]; then
            dDest="${uDest}"
            bPermissions=0
          else
            final "Invalid directory :("
          fi
          ;;
       *) final ":("
          ;;
  esac

  printf "\nWorking..\n\n"

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

    cp -R "${gKext}" $dDest
    sudo chown -R root:wheel $kDest
    sudo chmod -R 755 $kDest
  done

  if [[ $bPermissions -eq 1 ]]; then
    printf "\nUpdating Caches..\n"

    sudo touch $dDest
    sudo kextcache -system-prelinked-kernel &>/dev/null
    sudo kextcache -system-caches &>/dev/null
  fi

  final ":)" 1 $bPermissions
}

clear

if [ $gID -ne 0 ]; then
  sudo "${0}" "$@"
else
  main "$@"
fi
