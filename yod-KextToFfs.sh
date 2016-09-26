#!/bin/bash

# Part of KextToFfs script by PAVO
# http://www.insanelymac.com/forum/topic/301407-guide-insert-ozmosis-into-uefi-bios/?p=2069198
# https://github.com/tuxuser/kext2ffs

gVer=1.0
gTITLE="KextToFfs"
gUname="cecekpawon"
gME="@${gUname} | thrsh.net"
gBase="OSXOLVED"
gRepo="https://github.com/${gUname}/${gBase}"
gRepoRAW="https://raw.githubusercontent.com/${gUname}/${gBase}/master"
gScriptName=${0##*/}

gHEAD=`cat <<EOF
===============================================
Part of ${gTITLE} script by FredWst and STLVNUB
-----------------------------------------------
Mods v${gVer} : ${gME}
-----------------------------------------------
Cruel World! Boring without you THe KiNG!
===============================================\n\n
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

clean() {
  rm NullTerminator 1>/dev/null 2>&1
  rm *.pe32 1>/dev/null 2>&1
  rm *.bin 1>/dev/null 2>&1
  exit
}

bin() {
  a=("GenSec" "GenFfs")
  u=https://raw.githubusercontent.com/tuxuser/kext2ffs/master
  for x in "${a[@]}"
  do
    if [ ! -f bin/$x ]; then
      if [[ $1 -eq 1 ]]; then
        echo "Missing required bin(s): $x" && clean && break
      else
        curl -so bin/$x $u/bin/$x && chmod +x bin/$x
      fi
    fi
  done
}

gen() {
  [[ $2 -gt 15 ]] && echo "kext(s) limit exceeded" && clean
  bin 1

  b=$(basename "$1" .kext)
  c=${b}Compress
  h=$(echo $2 16op | dc)
  guid=DADE100$h-1B31-4FE4-8557-26FCEFC78275

  printf "Processing kext [%d]: %s\n" $(($2-6+1)) "$b"

  cat "$1/Contents/Info.plist" NullTerminator "$1/Contents/MacOS/$b" > "$b.bin" 2>/dev/null

  #bin/GenSec -s EFI_SECTION_PE32 -o "$b.pe32" "$b.bin"
  bin/GenSec -s EFI_SECTION_RAW -o "$b.pe32" "$b.bin"
  bin/GenSec -s EFI_SECTION_USER_INTERFACE -n "$b" -o "$b-1.pe32"
  bin/GenFfs -t EFI_FV_FILETYPE_FREEFORM -g $guid -o "ffs/$b.ffs" -i "$b.pe32" -i "$b-1.pe32"
  bin/GenSec -s EFI_SECTION_COMPRESSION -o "$b-2.pe32" "$b.pe32" "$b-1.pe32"
  bin/GenFfs -t EFI_FV_FILETYPE_FREEFORM -g $guid -o "ffs/$c.ffs" -i "$b-2.pe32"
}

valid() {
  [[ -d $1 && "${1##*.}" == "kext" ]] && return 0 || return 1
}

main() {
  printf "${gHEAD}"

  cd "`dirname "$0"`"
  dd if=/dev/zero of=NullTerminator bs=1 count=1 1>/dev/null 2>&1
  mkdir -p bin && mkdir -p ffs

  echo "Checking required bin(s)"

  bin

  gArgs=("$@")
  gKexts=()
  i=6

  if [[ "$#" -lt 1 ]]; then
    printf "Drag <kext(s)> here & ENTER: "
    read -ea gArgs
  fi

  for arg in "${gArgs[@]}"
  do
    if valid "${arg}"; then
      gen "${arg}" $i
      let i++
    fi
  done

  if [[ $i -eq 6 ]]; then
    echo "No valid kext(s) to convert"
  else
    echo "Operation done, check ffs folder"
  fi

  clean
}

main