#!/bin/sh -e


vrouter_addons()
{
  apk add keepalived
  rc-update add keepalived boot
}


vm_tools()
{
  apk add php5-cli
  apk add nano
  apk add rsync
  apk add udev
  apk add iptables
  apk add ip6tables
  apk add open-vm-tools
  apk add sfdisk
  apk add e2fsprogs-extra
  apk add util-linux
}


vm_services()
{
  rc-update add udev boot
  rc-update add iptables boot
  rc-update add ip6tables boot
  rc-update add open-vm-tools boot
  rc-update add acpid boot
  rc-update add one-context boot
}


deploy_files()
{
  rsync -ar etc /
  rsync -ar usr /
}


cleanup()
{
  service iptables stop
  service ip6tables stop
  echo '' > /etc/resolv.conf
  echo '' > /etc/iptables/rules-save
  echo '' > /etc/iptables/rules6-save
  echo 'auto lo' > /etc/network/interfaces
  echo 'iface lo inet loopback' >> /etc/network/interfaces
  rm -rf /tmp/*
  apk cache clean
}


main()
{
  # start, just fetch fresh info
  apk update
  
  vm_tools
  vrouter_addons
  deploy_files
  vm_services
  
  cleanup
}

main
