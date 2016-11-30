#!/bin/bash

# Clover Build Command
# @cecekpawon 10/10/2015 23:52 PM
# thrsh.net

gVer=2.8
gTITLE="Clover Build Command v${gVer}"
gUname="cecekpawon"
gME="@${gUname} | thrsh.net"
gBase="OSXOLVED"
gRepo="https://github.com/${gUname}/${gBase}"
gRepoRAW="https://raw.githubusercontent.com/${gUname}/${gBase}/master"
gScriptName=${0##*/}
gBuildThreads=$(sysctl -n hw.logicalcpu)

dHome="/Users/${USER}"
dDesktop="${dHome}/Desktop"

###########################
## START: user define //-->

## Directory

# HOME
# .
# ├── edk2
# │   ├── Build
# │   ├── Clover
# │   ├── Conf
# ├── opt
# │   └── local

dSrc="${dHome}/src"
dEdk2="${dSrc}/edk2"
dClover="${dEdk2}/Clover"
#dToolchainDir="${dClover}/../../opt/local"
dToolchainDir="${dSrc}/opt/local"

## Toolchain

export GCC5_BIN="${dToolchainDir}/cross/bin/x86_64-clover-linux-gnu-"
export NASM_PREFIX="${dToolchainDir}/bin/"
export CLANG_BIN="${dSrc}/llvm-build/Release/bin/"
export LLVM_BIN="${dSrc}/llvm-build/Release/bin/"
export MTOC_BIN="/usr/local/bin/"

#gGCCVer="49"
gGCCVer="5"
#gToolchain="GCC49"
gToolchain="GCC5"
#gToolchain="XCODE5"
#gToolchain="XCLANG"

## ARCH & Build

gArch="X64" # IA32 / X64
gBuildTarget="RELEASE" # / DEBUG

## Export custom PATH

#CUSTOM_CONF_PATH="${dEdk2}/Conf"
CUSTOM_CONF_PATH="${dClover}/Conf"

export LC_ALL=en_US.UTF-8
#export LANG=en_US.UTF-8

## URL

#uEdk2="svn://svn.code.sf.net/p/edk2/code/branches/UDK2015" # STABLE
uEdk2="svn://svn.code.sf.net/p/edk2/code/trunk/edk2"
uClover="svn://svn.code.sf.net/p/cloverefiboot/code"
uCloverCommits="${uClover}/commit_browser"

## Switch Options

gEdk2Patch=0      # Apply Clover EDK2 Patches
gCleanBuild=0     # Clean up build DIR 1st before compile
gDirectBuild=1    # Compile Clover via ebuild.sh (Wrap) / EDK2 'build' (Direct)
##
gRevTxt="rev.txt"
gEdk2Revision="0000"
gCloverLog="${dClover}/clover.log"
gCloverVersion="2.3k"
gCloverRevision="0000"
gCloverVersion_h="${dClover}/Version.h"
dEdk2Buildtools="${dEdk2}/BaseTools/Source/C"

## Generic Arguments

gGenArgs="-a ${gArch} -n ${gBuildThreads} -b ${gBuildTarget} -j ${gCloverLog}"

## Package

## Clover
gCloverDrivers=("FSInject" "OsxAptioFixDrv") # "OsxAptioFixDrv" "OsxFatBinaryDrv"
gCloverArgs="-DDEBUG_TEXT" # -DDISABLE_LTO -DDBG_APTIO --module=rEFIt_UEFI/refit.inf --edk2shell FullShell
gToolchains=("GCC49" "GCC5" "XCODE5" "XCLANG")

dEdk2Patch="${dClover}/Patches_for_EDK2"
dCloverPkg="${dClover}/CloverPackage"
dCloverPkgBin="${dCloverPkg}/sym"
dCloverBuildDir="${dEdk2}/Build/Clover/RELEASE_${gToolchain}"
dCloverBuildDirArch="${dCloverBuildDir}/${gArch}"

# DIR for post Clover compile action. See: post_compile()

dCloverCopyTarget="/Volumes/XDATA/QVM/DISK/EFI"

## OpenCorePkg
#fEdk2ShellPkg="${dEdk2}/OpenCorePkg/OpenCorePkg.dsc"

## ShellPkg
gEdk2ShellPkgArgs=
gEdk2ShellPkgdCopyTarget="${dCloverCopyTarget}/CLOVER/tools"

## OvmfPkg
gEdk2OvmfPkgArgs="-D SECURE_BOOT_ENABLE=TRUE" # -D DEBUG_ON_SERIAL_PORT -D SMM_REQUIRE=TRUE"

# DIR for post Ovmf compile action.
gEdk2OvmfPkgdCopyTarget="/Volumes/XDATA/QVM/BIOS/"

## END:   user define //<--
###########################

tCleanBuild=0
gBuildError=0

vCloverSVN=""
vCloverSrc=""
vCloverBoot=""

C_NORMAL="\e[0m"
C_MENU="\e[36m"
C_NUM="\e[33m"
C_RED="\e[31m"
C_BLUE="\e[38;5;27m"
C_PURPLE="\e[35m"
C_BOLD="\e[1m"
C_HI=""

menu() {
  tabs -2

  printf "`cat <<EOF
${C_MENU}=============================================================
${C_BLUE}${gTITLE} ${C_MENU}: ${C_RED}${gME}
${C_MENU}=============================================================
Revision SVN: ${C_HI}${vCloverSVN} ${C_MENU}| Src: ${vCloverSrc} ${C_MENU}| Boot: ${vCloverBoot}
${C_MENU}-------------------------------------------------------------
==> Arch: ${gArch} | BuildTarget: ${gBuildTarget} | Toolchain: ${gToolchain}
${C_MENU}-------------------------------------------------------------
\t\t\t ${C_NUM}[0] ${C_MENU}Compile GCC
\t\t\t ${C_NUM}[1] ${C_MENU}Revert SVN
\t\t\t ${C_NUM}[2] ${C_MENU}Update SVN EDK2
\t\t\t ${C_NUM}[3] ${C_MENU}Update SVN Clover
\t\t\t ${C_NUM}[4] ${C_MENU}Browse Clover Commits
\t\t\t ${C_NUM}[5] ${C_MENU}Compile Clover\t\t\t/ ${C_NUM}[c5]: ${C_MENU}CLEAN_BUILD
\t\t\t ${C_NUM}[6] ${C_MENU}Compile ShellPkg\t\t/ ${C_NUM}[c6]: ${C_MENU}CLEAN_BUILD
\t\t\t ${C_NUM}[7] ${C_MENU}Compile OvmfPkg\t\t/ ${C_NUM}[c7]: ${C_MENU}CLEAN_BUILD
\t\t\t ${C_NUM}[8] ${C_MENU}Compile Buildtools\t/ ${C_NUM}[c8]: ${C_MENU}CLEAN_BUILD
\t\t\t ${C_NUM}[9] ${C_MENU}Copy Binary
\t\t\t${C_NUM}[10] ${C_MENU}Open Build Directory
\t\t\t${C_NUM}[11] ${C_MENU}Build PKG Installer
\t\t\t${C_NUM}[12] ${C_MENU}Update Scripts
\t\t\t${C_NUM}[13] ${C_MENU}Browse Scripts Repo
\t\t\t${C_NUM}[14] ${C_MENU}Switch Toolchain
 ${C_NUM}[X|ENTER] ${C_RED}EXIT
${C_MENU}-------------------------------------------------------------
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
  gBuildError=0
  menu
}

boot() {
  log "Initializing"

  [[ -e "${CUSTOM_CONF_PATH}/target.txt" ]] && export CONF_PATH=${CUSTOM_CONF_PATH:-}

  case "${gToolchain}" in
    GCC49|GCC5)
        gMake=$(which make)
        gGNUmake="${GCC5_BIN}make"
        if [[ -x "${gMake}" && ! -x "${gGNUmake}" ]]; then
          ln -s "${gMake}" "${gGNUmake}"
        fi
      ;;
    XCLANG|LLVM)
        dLlvmBin="/usr/bin"
        dLlvmCloverBin="${dSrc}/llvm-build/Release/bin"
        if [[ ! -x "${dLlvmCloverBin}/clang" && -x "${dLlvmBin}/clang" ]]; then
          mkdir -p "${dLlvmCloverBin}"
          ln -s "${dLlvmBin}/clang" "${dLlvmCloverBin}/clang"
        fi
      ;;
    XCODE5)
        gEdk2Patch=0
      ;;
  esac

  vCloverSVN=`svn info $uClover 2>&1 | grep Revision | cut -c11-`
  [[ -z $vCloverSVN ]] && vCloverSVN=0

  C_HI=$C_MENU

  if [[ -d "${dClover}" && -d "${dClover}/.svn" ]]; then
    vCloverSrc=`svn info "${dClover}" 2>&1  | grep Revision | cut -c11-`
  fi

  if [[ ! $vCloverSrc =~ ^[0-9]+$ ]]; then
    vCloverSrc="${C_PURPLE}Undetected"
    C_HI=$C_RED
  elif [[ $vCloverSVN -gt $vCloverSrc ]]; then
    C_HI=$C_RED
  fi

  #CloverUpdaterUtility
  vCloverBoot=$(LC_ALL=C ioreg -l -pIODeviceTree | \
    sed -nE 's@.*boot-log.*<([0-9a-fA-F]*)>.*@\1@p' | \
    xxd -r -p                                       | \
    sed -nE 's/^.*revision: *([0-9]+).*$/\1/p')

  if [[ ! $vCloverBoot =~ ^[0-9]+$ ]]; then
    vCloverBoot="${C_PURPLE}Undetected"
    C_HI=$C_RED
  elif [[ $vCloverSVN -gt $vCloverBoot ]]; then
    C_HI=$C_RED
  fi

  [[ $vCloverSVN -eq 0 ]] && vCloverSVN="${C_PURPLE}Undetected"
}

run_fix() {
  if [[ ! -d "${dToolchainDir}" ]]; then
    log "${dToolchainDir}"
  else
    export TOOLCHAIN_DIR="${dToolchainDir}"
    [[ -d "${dEdk2Patch}" && $gEdk2Patch -eq 1 ]] && cp -R "${dEdk2Patch}"/* "${dEdk2}"
    cd "${dEdk2}" && source ./edksetup.sh "BaseTools"
  fi
}

compile_buildtools() {
  log "Compile Buildtools"

  if [[ -d "${dEdk2Buildtools}" ]]; then
    cd "${dEdk2Buildtools}"
    if [[ $gCleanBuild -eq 1 || $tCleanBuild -eq 1 ]]; then
      log "Clean Buildtools"

      make clean
    fi

    make
  fi
}

compile_gcc() {
  log "Compile GCC (Need Commandlinetools xCode)"

  if [[ -f "${dClover}/buildgcc-${gGCCVer}.sh" ]]; then
    read -p "Are you sure to (re)compile GCC? [yY] " gChoose
    case "${gChoose}" in
      [yY]) cd "${dClover}" && ./buildgcc-$gGCCVer.sh && ./buildnasm.sh && ./buildgettext.sh;;
    esac
  else
    log "No EDK2 / Clover sources. Start cloning"
    update_clover
    compile_gcc
  fi
}

get_rev() {
  [[ ! -d "${1}" ]] && return

  ret="0000"

  cd "${1}"

  if [[ -d .svn ]]; then
    ret="$(svnversion -n | tr -d [:alpha:])"
  elif [[ -d .git ]]; then
    ret="$(git svn find-rev git-svn | tr -cd [:digit:])"
  fi

  echo $ret
}

update_edk2() {
  log "Update EDK2"

  if [[ -f "${dEdk2}/edksetup.sh" ]]; then
    cd "${dEdk2}" && svn up
  else
    svn co "${uEdk2}" "${dEdk2}"
  fi

  gEdk2Revision=$(get_rev ${dEdk2})
  echo $gEdk2Revision > "${dEdk2}/${gRevTxt}"
}

update_clover() {
  if [[ ! -f "${dClover}/upd_locked" ]]; then
    if [[ -f "${dEdk2}/edksetup.sh" ]]; then
      log "Update Clover"

      if [[ -f "${dClover}/ebuild.sh" ]]; then
        cd "${dClover}" && svn up
      else
        svn co "${uClover}" "${dClover}"
      fi

      run_fix
    else
      log "No EDK2 sources. Start cloning"

      update_edk2
      update_clover
    fi
  else
    log "'upd_locked' exist, prevent updating"
  fi
}

browse_clover_commits() {
  log "Browse Clover commits"

  open "$(echo $uCloverCommits | sed -e 's/^svn.*code\./http:\/\//')"
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
      case "${sSvn}" in
        1) cd $dEdk2;;
        2) cd $dClover;;
        *) return 0;;
      esac

      log "Reverting SVN";
      svn up -r  $sRev_u
    fi
  fi
}

post_compile() {
  log "Post Compile Clover"

  if [[ $gBuildError -eq 1 ]]; then
    echo "Build error, abort :((((("
    return
  fi

  if [[ ! -d "${dCloverCopyTarget}" ]]; then
    echo "Cannot copy binary :((((("
  else
    copy_binary
  fi

  iso="${dDesktop}/VM.iso"
  vol="/Volumes/VM"
  efi="${dCloverBuildDirArch}/CLOVER${gArch}.efi"

  if [[ -e "${iso}" ]]; then
    if [[ ! -d "${vol}" ]]; then
      hdiutil mount -quiet "${iso}"
    fi

    [[ -e "$efi" ]] && cp "$efi" "${vol}/CLOVER/BOOT${gArch}.efi"
    hdiutil unmount -quiet "${vol}"
  fi
}

compile_clover() {
  if [[ $gCleanBuild -eq 1 || $tCleanBuild -eq 1 ]]; then
    log "Clean Clover Build Directory"

    [[ -d "${dCloverBuildDir}" ]] && rm -rf "${dCloverBuildDir}"
  fi

  tCleanBuild=0

  log "Compile Clover"

  if [[ $gDirectBuild -eq 1 ]]; then
    fCheck="${dClover}/Clover.dsc"
  else
    fCheck="${dClover}/ebuild.sh"
  fi

  if [[ -f "${fCheck}" ]]; then
    run_fix
    if [[ $gDirectBuild -eq 1 ]]; then
      # Gen Version.h
      gCloverCmd="build -p ${fCheck} ${gGenArgs} -t ${gToolchain} ${gCloverArgs}"
      gCloverCmdStr=""
      for c in $gCloverCmd
      do
        gCloverCmdStr="${gCloverCmdStr} ${c##*/}"
      done
      read -rd '' gCloverCmdStr <<< "${gCloverCmdStr}"
      gDate=$(date '+%Y-%m-%d %H:%M:%S')
      [[ -f "${dEdk2}/${gRevTxt}" ]] && gEdk2Revision=`cat "${dEdk2}/${gRevTxt}"`
      echo "#define EDK2_REVISION \"${gEdk2Revision}\"" > "${gCloverVersion_h}"
      echo "#define CLOVER_VERSION \"${gCloverVersion}\"" >> "${gCloverVersion_h}"
      echo "#define CLOVER_BUILDDATE \"${gDate}\"" >> "${gCloverVersion_h}"
      echo "#define CLOVER_REVISION \"${gCloverRevision}\"" >> "${gCloverVersion_h}"
      echo "#define CLOVER_BUILDINFOS_STR \"${gCloverCmdStr}\"" >> "${gCloverVersion_h}"
      # Original vars, backwards compatibility
      echo "#define FIRMWARE_VERSION CLOVER_VERSION" >> "${gCloverVersion_h}"
      echo "#define FIRMWARE_BUILDDATE CLOVER_BUILDDATE" >> "${gCloverVersion_h}"
      echo "#define FIRMWARE_REVISION CLOVER_REVISION" >> "${gCloverVersion_h}"
      echo "#define REVISION_STR \"Clover revision: ${gCloverRevision}\"" >> "${gCloverVersion_h}"
      echo "#define BUILDINFOS_STR CLOVER_BUILDINFOS_STR" >> "${gCloverVersion_h}"
      eval "${gCloverCmd}"
      gBuildError=`echo $?`
    else
      "${fCheck}" ${gGenArgs} -t ${gToolchain} ${gCloverArgs}
    fi
    post_compile
  else
    log "No Clover sources. Start cloning"

    update_clover
    compile_clover
  fi
}

compile_shellpkg() {
  dShellBuild="${dEdk2}/Build/Shell/${gBuildTarget}_${gToolchain}"

  if [[ $gCleanBuild -eq 1 || $tCleanBuild -eq 1 ]]; then
    log "Clean EDK2 ShellPkg Build Directory"

    [[ -d "${dShellBuild}" ]] && rm -rf "${dShellBuild}"
  fi

  tCleanBuild=0

  log "Compile EDK2 ShellPkg"

  fDsc="${dEdk2}/ShellPkg/ShellPkg.dsc"

  if [[ -f "${fDsc}" ]]; then
    run_fix
    build -p "${fDsc}" ${gGenArgs} -t ${gToolchain} ${gEdk2ShellPkgArgs}
  fi

  [[ -f "${dShellBuild}/${gArch}/Shell.efi" && -d "${gEdk2ShellPkgdCopyTarget}" ]] && cp "${dShellBuild}/${gArch}/Shell.efi" "${gEdk2ShellPkgdCopyTarget}"
}

compile_ovmfpkg() {
  if [[ "${gToolchain}" != *"GCC"* ]];then
    log "Unsupported Toolchain: '${gToolchain}' to build this pkg. Switch to GCC*."
    return
  fi

  dOvmfBuild="${dEdk2}/Build/Ovmf${gArch}/${gBuildTarget}_${gToolchain}"

  if [[ $gCleanBuild -eq 1 || $tCleanBuild -eq 1 ]]; then
    log "Clean EDK2 OvmfPkg Build Directory"

    [[ -d "${dOvmfBuild}" ]] && rm -rf "${dOvmfBuild}"
  fi

  tCleanBuild=0

  log "Compile EDK2 OvmfPkg"

  fDsc="${dEdk2}/OvmfPkg/OvmfPkg${gArch}.dsc"

  if [[ -f "${fDsc}" ]]; then
    run_fix
    build -p "${fDsc}" ${gGenArgs} -t ${gToolchain} ${gEdk2OvmfPkgArgs}
  fi

  [[ -f "${dOvmfBuild}/${gArch}/Shell.efi" && -d "${gEdk2ShellPkgdCopyTarget}" ]] && cp "${dOvmfBuild}/${gArch}/Shell.efi" "${gEdk2ShellPkgdCopyTarget}"
  [[ -f "${dOvmfBuild}/FV/OVMF.fd" && -d "${gEdk2OvmfPkgdCopyTarget}" ]] && cp "${dOvmfBuild}/FV/OVMF.fd" "${gEdk2OvmfPkgdCopyTarget}"
}

switch_toolchain() {
  i=0
  mtc=""

  for tc in "${gToolchains[@]}"
  do
    ((i=i+1))
    mtc="`printf "${mtc}${C_NUM}[$i] ${C_MENU}${tc}"`\n"
  done

  mtc="`printf "${mtc}${C_NORMAL}Choose Toolchain:"`"

  read -p "$(printf "$mtc") " stc

  if [[ $stc -ge 1 && $stc -le $i ]]; then
    ((stc=stc-1))
    gToolchain="${gToolchains[$stc]}"
  fi
}

copy_binary() {
  log "Copy binary to ${dCloverCopyTarget}"

  dBuild="${dEdk2}/Build/Clover/${gBuildTarget}_${gToolchain}"
  iArch=`echo ${gArch} | sed 's/[^3264]//g'`

  if [[ -d "${dCloverCopyTarget}" && ${#gCloverDrivers[@]} -ne 0 ]]; then
    for drv in "${gCloverDrivers[@]}"
    do
      [[ -f "${dBuild}/${gArch}/${drv}.efi" ]] && cp "${dBuild}/${gArch}/${drv}.efi" "${dCloverCopyTarget}/CLOVER/drivers${iArch}UEFI/${drv}-${iArch}.efi"
    done
  fi

  [[ -f "${dBuild}/${gArch}/CLOVER${gArch}.efi" ]] && cp "${dBuild}/${gArch}/CLOVER${gArch}.efi" "${dCloverCopyTarget}/BOOT/BOOT${gArch}.efi"
}

open_build_dir() {
  log "Open Build Directory"

  dBuild="${dEdk2}/Build"

  [[ -d "${dBuild}" ]] && open "${dBuild}"
}

build_pkg() {
  log "Build PKG Installer"

  [[ -f "${dCloverPkg}/makepkg" ]] && "${dCloverPkg}"/makepkg
  ls "${dCloverPkgBin}/"Clover*.pkg &>/dev/null && open "${dCloverPkgBin}"
}

update_scripts() {
  log "Looking for updates"

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
}

browse_scripts_repo() {
  log "Browse scripts repo"

  open ${gRepo}
}

go boot true

while true; do
  case "${opt}" in
       0) go compile_gcc;;
       1) go revert_svn;;
       2) go update_edk2;;
       3) go update_clover;;
       4) go browse_clover_commits;;
       5) go compile_clover;;
   c5|C5) tCleanBuild=1 && go compile_clover;;
       6) go compile_shellpkg;;
   c6|C6) tCleanBuild=1 && go compile_shellpkg;;
       7) go compile_ovmfpkg;;
   c7|C7) tCleanBuild=1 && go compile_ovmfpkg;;
       8) go compile_buildtools;;
   c8|C8) tCleanBuild=1 && go compile_buildtools;;
       9) go copy_binary;;
      10) go open_build_dir;;
      11) go build_pkg;;
      12) go update_scripts;;
      13) go browse_scripts_repo;;
      14) go switch_toolchain;;
    [xX]) break 1;;
       *) [[ -z $opt ]] && exit || go;; #"${0}"
  esac
done
