# fedora-livecd-desktop-de_DE.ks
#
# Maintainer(s):
# - Jeroen van Meeuwen <kanarip a fedoraunity.org>

%include guinux-livecd-desktop.ks

lang ca_ES.UTF-8
keyboard es
timezone Europe/Andorra

%packages
-gnome-blog
-evolution*
-cups*
-gimp*
-cheese*
-fedora-logos
-fedora-release*
-solar-backgrounds*
@catalan-support
@spanish-support
generic-logos
generic-release
mrtg
phpMyAdmin
squid
php-gd
cacti
vim
rrdtool
rrdtool-perl
rrdtool-php
drupal*
# webmin
guifi-CNML-graphs
guifi-proxy-passwd
guifi-logos
guifi-release
%end

%post
# system-config-keyboard doesn't really work (missing xorg.conf etc)
cat >>/etc/X11/xorg.conf << EOF
Section "InputDevice"
    Identifier "Keyboard0"
    Driver "kbd"
    Option "XkbModel" "pc105"
    Option "XkbLayout" "es"
EndSection
EOF

%end

