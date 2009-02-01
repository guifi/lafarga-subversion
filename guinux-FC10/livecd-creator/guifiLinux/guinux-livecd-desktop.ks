# Maintained by the Fedora Desktop SIG:
# http://fedoraproject.org/wiki/SIGs/Desktop
# mailto:fedora-desktop-list@redhat.com

%include guinux-base.ks

%packages
#@games
@graphical-internet
@graphics
#@sound-and-video
@gnome-desktop
nss-mdns
NetworkManager-vpnc
NetworkManager-openvpn
# we don't include @office so that we don't get OOo.  but some nice bits
#abiword
#gnumeric
#planner
#inkscape

# avoid weird case where we pull in more festival stuff than we need
festival
festvox-slt-arctic-hts

# dictionaries are big
-aspell-*
-hunspell-*
-man-pages-*
-scim-tables-*
-wqy-bitmap-fonts
-dejavu-fonts-experimental
-words

# more fun with space saving
-scim-lang-chinese
-scim-python*
scim-chewing
scim-pinyin

# save some space
-gnome-user-docs
-gimp-help
-gimp-help-browser
-evolution-help
-gnome-games
-gnome-games-help
totem-gstreamer
-totem-xine
-nss_db
-vino
-isdn4k-utils
-dasher
-evince-dvi
-evince-djvu
# not needed for gnome
-acpid

# these pull in excessive dependencies
-ekiga
-tomboy
-f-spot

# hack to deal with conditionals + multiarch blargh
-scim-bridge-gtk.i386
%end

%post
cp /usr/share/guifi-logos/bootloader/grub-splash.xpm.gz /boot/grub/splash.xpm.gz
cat >> /etc/gconf/gconf.xml.system/%gconf-tree.xml << EOF
<?xml version="1.0"?>
<gconf>
        <dir name="desktop">
                <dir name="gnome">
                        <dir name="background">
                                <entry name="primary_color" mtime="1232402343" type="string">
                                        <stringvalue>#000000000000</stringvalue>
                                </entry>
                                <entry name="picture_options" mtime="1232402343" type="string">
                                        <stringvalue>scaled</stringvalue>
                                </entry>
                                <entry name="secondary_color" mtime="1232402343" type="string">
                                        <stringvalue>#14142c2c3d3d</stringvalue>
                                </entry>
                                <entry name="color_shading_type" mtime="1232402343" type="string">
                                        <stringvalue>solid</stringvalue>
                                </entry>
                                <entry name="picture_filename" mtime="1232402343" type="string">
                                        <stringvalue>/usr/lib/anaconda-runtime/syslinux-vesa-splash.jpg</stringvalue>
                                </entry>
                        </dir>
                </dir>
        </dir>
</gconf>
EOF
cat >> /etc/rc.d/init.d/livesys << EOF
# disable screensaver locking
gconftool-2 --direct --config-source=xml:readwrite:/etc/gconf/gconf.xml.defaults -s -t bool /apps/gnome-screensaver/lock_enabled false >/dev/null
gconftool-2 --direct --config-source=xml:readwrite:/etc/gconf/gconf.xml.defaults -s -t str --set /desktop/gnome/background/picture_filename /usr/lib/anaconda-runtime/syslinux-vesa-splash.jpg >/dev/null
# gconftool-2 --direct -t str --set /desktop/gnome/background/picture_filename /usr/lib/anaconda-runtime/syslinux-vesa-splash.jpg
# set up timed auto-login for after 60 seconds
cat >> /etc/gdm/custom.conf << FOE
[daemon]
TimedLoginEnable=true
TimedLogin=liveuser
TimedLoginDelay=60
FOE

EOF

%end
