#!/bin/bash

# OpenGate
# @cecekpawon Sat Jun 18 13:31:35 2016 WIB
# thrsh.net

gVer=1.1
gTITLE="OpenGate v${gVer}"
gUname="cecekpawon"
gME="@${gUname} | thrsh.net"
gBase="OSXOLVED"
gRepo="https://github.com/${gUname}/${gBase}"
gRepoRAW="https://raw.githubusercontent.com/${gUname}/${gBase}/master"
gScriptName=${0##*/}

gHEAD=`cat <<EOF
===========================================
${gTITLE}: ${gME}
-------------------------------------------
Gatekeeper free your trusted application(s)
===========================================\n\n
EOF`

update() {
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
  exit
}

valid() {
  [[ -d $1 && "${1##*.}" == "app" ]] && return 0 || return 1
}

main() {
  printf "${gHEAD}"

  gArgs=("$@")

  if [[ "$#" -lt 1 ]]; then
    printf "Drag <app(s)> here & ENTER: "
    read -ea gArgs
  fi

  i=0
  app=""

  for arg in "${gArgs[@]}"
  do
    if valid "${arg}"; then
      xattr -d -r com.apple.quarantine "${arg}"
      let i++
      [[ $i -eq 1 ]] && app="${arg}"
    fi
  done

  if [[ $i -eq 0 ]]; then
    echo "No valid app(s) to gate free"
  elif [[ $i -eq 1 && ! -z "${app}" ]]; then
    read -p "Launch this app [yY]? " gChoose
    case "${gChoose}" in
      [yY]) open "${app}";;
    esac
  else
    echo "Operation done"
  fi
}

main
