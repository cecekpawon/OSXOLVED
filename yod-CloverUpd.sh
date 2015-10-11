#!/bin/bash

# Clover Build Command
# @cecekpawon 10/10/2015 23:52 PM
# thrsh.net

dHome="/Users/$(who am i | awk '{print $1}')"

## START: user defined //--

dSrc="${dHome}/src"
dDesktop="${dHome}/Desktop"
dEdk2="${dSrc}/edk2"
dClover="${dEdk2}/Clover"
gCloverDrivers=("CLOVERX64" "FSInject" "OsxAptioFixDrv" "OsxFatBinaryDrv")
gARCH="x64"

## END: user defined --//

dEdk2Patch="${dClover}/Patches_for_EDK2"
dCloverPkg="${dClover}/CloverPackage"
dCloverBoot="${dCloverPkg}/CloverV2/EFI/BOOT"
dClover64="${dEdk2}/Build/Clover/RELEASE_GCC49/X64"

uEdk2="svn://svn.code.sf.net/p/edk2/code/trunk/edk2"
uClover="svn://svn.code.sf.net/p/cloverefiboot/code/"

vCloverSVN=""
vCloverCurrent=""

C_NORMAL="\e[0m"
C_MENU="\e[36m"
C_NUM="\e[33m"
C_RED="\e[31m"
C_BOLD="\e[1m"
C_HI=""


menu() {
  tabs -2

  printf "`cat <<EOF
${C_MENU}**********************************
Clover Build Command ${C_NORMAL}| ${C_RED}@cecekpawon
${C_MENU}----------------------------------
Revision SVN: ${C_HI}${vCloverSVN} ${C_MENU}| Current: ${vCloverCurrent}
${C_MENU}**********************************
\t\t\t${C_NUM}[0] ${C_MENU}Compile GCC
\t\t\t${C_NUM}[1] ${C_MENU}Revert SVN
\t\t\t${C_NUM}[2] ${C_MENU}Update SVN EDK2
\t\t\t${C_NUM}[3] ${C_MENU}Update SVN Clover
\t\t\t${C_NUM}[4] ${C_MENU}Compile Clover
\t\t\t${C_NUM}[5] ${C_MENU}Copy Binary
\t\t\t${C_NUM}[6] ${C_MENU}Open Build DIR
\t\t\t${C_NUM}[7] ${C_MENU}Build PKG Installer
${C_NUM}[X|ENTER] ${C_RED}EXIT
${C_MENU}**********************************
${C_RED}Pick an option from the menu: ${C_NORMAL}
EOF`"

  read opt
}

function log() {
  MESSAGE_ERR="Error: No message passed"
  MESSAGE=`echo ${@:-${MESSAGE_ERR}}`
  printf "${C_BOLD}${C_RED}${MESSAGE} ..${C_NORMAL}\n"
}

go() {
  clear
  if [[ $1 ]]; then
    [[ ! -d "${dClover}" ]] && mkdir -p "${dClover}"
    eval $1
    printf "\nDone!"
  fi
  printf "\n\n"
  menu
}

boot() {
  log "Initializing"

  vCloverSVN=$(svn info $uClover | grep Revision: | cut -c11-)

  #CloverUpdaterUtility
  vCloverCurrent=$(LC_ALL=C ioreg -l -pIODeviceTree  | \
    sed -nE 's@.*boot-log.*<([0-9a-fA-F]*)>.*@\1@p'  | \
    xxd -r -p                                        | \
    sed -nE 's/^.*revision: *([0-9]+).*$/\1/p')

  C_HI=$((($vCloverSVN > $vCloverCurrent)) && echo $C_RED || echo $C_MENU)
}

compile_gcc() {
  log "Compiling GCC (Need Commandlinetools xCode)"
  if [[ -d "${dClover}" && -ef "${dClover}/buildgcc-4.9.sh" ]]; then
    cd "${dEdk2}" && ./edksetup.sh && make -C BaseTools/Source/C
    cd "${dClover}" && ./buildgcc-4.9.sh && ./buildnasm.sh && ./buildgettext.sh
  else
    log "No EDK2 / Clover sources. Start cloning"
    update_clover
    compile_gcc
  fi
}

update_edk2() {
  log "Updating EDK2"
  svn co "${uEdk2}" "${dEdk2}"
}

update_clover() {
  if [[ -d "${dEdk2}" && -ef "${dEdk2}/edksetup.sh" ]]; then
    log "Updating Clover"
    svn checkout "${uClover}" "${dClover}"
    cp -R "${dEdk2Patch}"/* "${dEdk2}"
    cd "${dEdk2}" && source ./edksetup.sh "BaseTools" &>/dev/null
  else
    log "No EDK2 sources. Start cloning"
    update_edk2
    update_clover
  fi
}

revert_svn() {
  read -p "$(printf "`cat <<EOF
${C_RED}SVN to revert:
${C_NUM}[1] ${C_MENU}EDK2
${C_NUM}[2] ${C_MENU}Clover
${C_NORMAL}Choose SVN:
EOF`") " sSvn

  if [[ $sSvn =~ ^[12]$ ]]; then
    read -p "Type revision: " sRev_u

    if [[ $sRev_u ]]; then
      case "$sSvn" in
        1) cd $dEdk2;;
        2) cd $dClover;;
        *) return 0;;
      esac

      log "Reverting SVN";
      svn update -r  $sRev_u
    fi
  fi
}

compile_clover() {
  log "Compiling Clover";
  if [[ -d "${dClover}" && -ef "${dClover}/ebuild.sh" ]]; then
    "${dClover}"/ebuild.sh -"${gARCH}"
  else
    log "No Clover sources. Start cloning"
    update_clover
    compile_clover
  fi
}

copy_binary() {
  log "Copy binary to ${dDesktop}"
  for drv in "${gCloverDrivers[@]}"
  do
    [[ -ef "${dClover64}/$drv.efi" ]] && cp "${dClover64}/$drv.efi" "${dDesktop}/$drv-64.efi"
  done
  [[ -ef "${dDesktop}/CLOVERX64-64.efi" ]] && mv "${dDesktop}/CLOVERX64-64.efi" "${dDesktop}/BOOTX64.efi"
}

open_build_dir() {
  log "Open Build Directory"
  [[ -d "${dCloverBoot}" ]] && open "${dCloverBoot}"
  [[ -d "${dClover64}" ]] && open "${dClover64}"
}

build_pkg() {
  log "Build PKG Installer";
  [[ -ef "${dCloverPkg}/makepkg" ]] && "${dCloverPkg}"/makepkg
}

go boot true

while true; do
  case "$opt" in
       0) go compile_gcc;;
       1) go revert_svn;;
       2) go update_edk2;;
       3) go update_clover;;
       4) go compile_clover;;
       5) go copy_binary;;
       6) go open_build_dir;;
       7) go build_pkg;;
    [xX]) break 1;;
       *) [[ -z $opt ]] && exit || go;; #"$0"
  esac
done
