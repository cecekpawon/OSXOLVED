#!/bin/bash

# Python Uninstaller
# @cecekpawon Sun Dec 13 17:18:20 2020

# Ref: https://www.techjunkie.com/macos-uninstall-python3/

gVer=1.0
gTITLE="Python Uninstaller v${gVer}"
gUname="cecekpawon"
gME="@${gUname} | ${gUname}.github.io"
gBase="OSXOLVED"
gRepo="https://github.com/${gUname}/${gBase}"
gRepoRAW="https://raw.githubusercontent.com/${gUname}/${gBase}/master"
gScriptName=${0##*/}
gID=$(id -u)

gHEAD=`cat <<EOF
===========================================================
${gTITLE}: ${gME}
-----------------------------------------------------------
Run: ./${gScriptName} [MajorVersion.MinorVersion]"
Ex: ./${gScriptName} 3.9"
===========================================================\n\n
EOF`

main() {
  Python_framework_path="/Library/Frameworks/Python.framework/Versions/${1}"
  # Remove the Framework:
  rm -rf "${Python_framework_path}"
  # Remove the App directory:
  rm -rf "/Applications/Python ${1}"
  # Debug the links:
  #ls -l /usr/local/bin | grep '${Python_framework_path}'
  # Remove the links:
  ls -l /usr/local/bin | grep '${Python_framework_path}' | awk '{print $9}' | tr -d @ | xargs rm
}

clear

printf "${gHEAD}"

if [[ -d "/Applications/Python ${1}" ]]; then
  if [ $gID -ne 0 ]; then
    sudo "${0}" "$@"
  else
    main "$@"
  fi
else
  echo !!! not installed
fi
