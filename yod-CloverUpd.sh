#!/bin/bash

# Clover Build Command
# @cecekpawon 10/10/2015 23:52 PM
# thrsh.net

gVer=2.1
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
dToolchainDir="${dClover}/../../opt/local"

## Toolchain

gGCCVer="4.9"
gToolchain="GCC49"
#gToolchain="XCODE5"
#gToolchain="XCLANG"
#gToolchain="CLANG38"

## ARCH & Build

gArch="X64" # / IA32
gBuildTarget="RELEASE" # / DEBUG

## Export custom PATH

#CUSTOM_CONF_PATH="${dEdk2}/Conf"
CUSTOM_CONF_PATH="${dClover}/Conf"
#CUSTOM_CLANG38_BIN="${dSrc}/clang38/bin/"

## URL
#uEdk2="svn://svn.code.sf.net/p/edk2/code/branches/UDK2015" # STABLE
uEdk2="svn://svn.code.sf.net/p/edk2/code/trunk/edk2"
uClover="svn://svn.code.sf.net/p/cloverefiboot/code"
uCloverCommits="${uClover}/commit_browser"

## Switch Options

gEdk2Patch=0      # Apply Clover EDK2 Patches
gCleanBuild=0     # Clean up build DIR 1st before compile
gDirectBuild=1    # Compile Clover via ebuild.sh (Wrap) / EDK2 'build' (Direct)

## Generic Arguments

gGenArgs="-a ${gArch} -t ${gToolchain} -n ${gBuildThreads} -b ${gBuildTarget}"

## Package

## Clover
gCloverDrivers=("OsxAptioFixDrv") # "FSInject" "OsxAptioFixDrv" "OsxFatBinaryDrv"
gCloverArgs="-D DISABLE_USB_SUPPORT -DDEBUG_TEXT" # -DDBG_APTIO --module=rEFIt_UEFI/refit.inf --edk2shell FullShell

dEdk2Patch="${dClover}/Patches_for_EDK2"
dCloverPkg="${dClover}/CloverPackage"
dCloverPkgBin="${dCloverPkg}/sym"
dCloverBuildDir="${dEdk2}/Build/Clover/RELEASE_${gToolchain}"
dCloverBuildDirArch="${dCloverBuildDir}/${gArch}"

# DIR for post Clover compile action. See: post_compile()

dCloverCopyTarget="${dDesktop}/QVM/DISK/EFI"

## OpenCorePkg
#fEdk2ShellPkg="${dEdk2}/OpenCorePkg/OpenCorePkg.dsc"

## ShellPkg
gEdk2ShellPkgArgs=

## OvmfPkg
gEdk2OvmfPkgArgs="-D SECURE_BOOT_ENABLE=TRUE" # -D DEBUG_ON_SERIAL_PORT -D SMM_REQUIRE=TRUE"

# DIR for post Ovmf compile action.
gEdk2OvmfPkdCopyTarget="${dDesktop}/QVM/BIOS/"

## END:   user define //<--
###########################

tCleanBuild=0

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
\t\t\t ${C_NUM}[5] ${C_MENU}Compile Clover\t\t\t\t\t/ ${C_NUM}[c5] ${C_MENU}for CLEAN Build
\t\t\t ${C_NUM}[6] ${C_MENU}Compile EDK2 ShellPkg\t/ ${C_NUM}[c6] ${C_MENU}for CLEAN Build
\t\t\t ${C_NUM}[7] ${C_MENU}Compile EDK2 OvmfPkg\t\t/ ${C_NUM}[c7] ${C_MENU}for CLEAN Build
\t\t\t ${C_NUM}[8] ${C_MENU}Copy Binary
\t\t\t ${C_NUM}[9] ${C_MENU}Open Build Directory
\t\t\t${C_NUM}[10] ${C_MENU}Build PKG Installer
\t\t\t${C_NUM}[11] ${C_MENU}Update Scripts
\t\t\t${C_NUM}[12] ${C_MENU}Browse Scripts Repo
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
  menu
}

boot() {
  log "Initializing"

  [[ -e "${CUSTOM_CONF_PATH}/target.txt" ]] && export CONF_PATH=${CUSTOM_CONF_PATH:-}
  #[[ -d "${CUSTOM_CLANG38_BIN}" ]] && export CLANG38_BIN=${CUSTOM_CLANG38_BIN:-}

  case "${gToolchain}" in
    XCLANG|LLVM)
        dLlvmBin="/usr/bin"
        dLlvmCloverBin="${dSrc}/llvm-build/Release/bin"
        if [[ ! -x "${dLlvmCloverBin}/clang" && -x  "${dLlvmBin}/clang" ]]; then
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
    grep -Esio 'Clover.revision:.([0-9]+)'              | \
    awk '{print $3}')
    #sed -nE 's/^.*revision: *([0-9]+).*$/\1/p')

  if [[ ! $vCloverBoot =~ ^[0-9]+$ ]]; then
    vCloverBoot="${C_PURPLE}Undetected"
    C_HI=$C_RED
  elif [[ $vCloverSVN -gt $vCloverBoot ]]; then
    C_HI=$C_RED
  fi

  [[ $vCloverSVN -eq 0 ]] && vCloverSVN="${C_PURPLE}Undetected"
}

compile_gcc() {
  log "Compiling GCC (Need Commandlinetools xCode)"

  if [[ -d "${dClover}" && -f "${dClover}/buildgcc-${gGCCVer}.sh" ]]; then
    run_fix && sudo make -C BaseTools/Source/C
    cd "${dClover}" && sudo ./buildgcc-$gGCCVer.sh && sudo ./buildnasm.sh && sudo ./buildgettext.sh
    sudo -k
  else
    log "No EDK2 / Clover sources. Start cloning"
    update_clover
    compile_gcc
  fi
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

update_edk2() {
  log "Updating EDK2"

  svn co "${uEdk2}" "${dEdk2}"
}

update_clover() {
  if [[ -f "${dEdk2}/edksetup.sh" ]]; then
    log "Updating Clover"

    svn co "${uClover}" "${dClover}"
    run_fix
  else
    log "No EDK2 sources. Start cloning"

    update_edk2
    update_clover
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
      svn update -r  $sRev_u
    fi
  fi
}

post_compile() {
  log "Post Compile Clover"

  if [[ ! -d "${dCloverCopyTarget}" ]]; then
    read -p "Cannot copy binary :((((("
  else
    copy_binary
  fi

  iso="${dDesktop}/Untitled.iso"
  vol="/Volumes/Untitled"
  efi="${dCloverBuildDirArch}/CLOVER${gArch}.efi"

  if [[ -e "$iso" ]]; then
    if [[ ! -d "$vol" ]]; then
      hdiutil mount -quiet "$iso"
    fi

    [[ -e "$efi" ]] && cp "$efi" "$vol"
    hdiutil unmount -quiet "$vol"
  fi
}

compile_clover() {
  if [[ $gCleanBuild -eq 1 || $tCleanBuild -eq 1 ]]; then
    log "Clean Clover Build Directory"

    [[ -d "${dCloverBuildDir}" ]] && rm -rf "${dCloverBuildDir}"
  fi

  tCleanBuild=0

  log "Compiling Clover"

  if [[ $gDirectBuild -eq 1 ]]; then
    fCheck="${dClover}/Clover.dsc"
  else
    fCheck="${dClover}/ebuild.sh"
  fi

  if [[ -f "${fCheck}" ]]; then
    run_fix
    if [[ $gDirectBuild -eq 1 ]]; then
      build -p "${fCheck}" ${gGenArgs} ${gCloverArgs}
    else
      "${fCheck}" ${gGenArgs} ${gCloverArgs}
    fi
    post_compile
  else
    log "No Clover sources. Start cloning"

    update_clover
    compile_clover
  fi
}

compile_shellpkg() {
  dBuild="${dEdk2}/Build/Shell/${gBuildTarget}_${gToolchain}"

  if [[ $gCleanBuild -eq 1 || $tCleanBuild -eq 1 ]]; then
    log "Clean EDK2 ShellPkg Build Directory"

    [[ -d "${dBuild}" ]] && rm -rf "${dBuild}"
  fi

  tCleanBuild=0

  log "Compiling EDK2 ShellPkg"

  fDsc="${dEdk2}/ShellPkg/ShellPkg.dsc"

  if [[ -f "${fDsc}" ]]; then
    run_fix
    build -p "${fDsc}" ${gGenArgs} ${gEdk2ShellPkgArgs}
  fi
}

compile_ovmfpkg() {
  if [[ "${gToolchain}" != "GCC49" ]]; then
    log "Unsupported Toolchain: '${gToolchain}' to build this pkg. Switch to GCC49."
    return
  fi

  dBuild="${dEdk2}/Build/Ovmf${gArch}/${gBuildTarget}_${gToolchain}"

  if [[ $gCleanBuild -eq 1 || $tCleanBuild -eq 1 ]]; then
    log "Clean EDK2 OvmfPkg Build Directory"

    [[ -d "${dBuild}" ]] && rm -rf "${dBuild}"
  fi

  tCleanBuild=0

  log "Compiling EDK2 OvmfPkg"

  fDsc="${dEdk2}/OvmfPkg/OvmfPkg${gArch}.dsc"

  if [[ -f "${fDsc}" ]]; then
    run_fix
    build -p "${fDsc}" ${gGenArgs} ${gEdk2OvmfPkgArgs}
  fi

  [[ -f "${dBuild}/FV/OVMF.fd" && -d "${gEdk2OvmfPkdCopyTarget}" ]] && cp "${dBuild}/FV/OVMF.fd" "${gEdk2OvmfPkdCopyTarget}"
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
      c5) tCleanBuild=1 && go compile_clover;;
       6) go compile_shellpkg;;
      c6) tCleanBuild=1 && go compile_shellpkg;;
       7) go compile_ovmfpkg;;
      c7) tCleanBuild=1 && go compile_ovmfpkg;;
       8) go copy_binary;;
       9) go open_build_dir;;
      10) go build_pkg;;
      11) go update_scripts;;
      12) go browse_scripts_repo;;
    [xX]) break 1;;
       *) [[ -z $opt ]] && exit || go;; #"${0}"
  esac
done
