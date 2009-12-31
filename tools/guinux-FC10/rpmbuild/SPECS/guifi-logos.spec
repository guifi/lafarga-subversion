Name: guifi-logos
Summary: Icons and pictures
Version: 1.0
Release: 1%{?dist}
Group: System Environment/Base
Source0: guifi-logos-%{version}.tar.bz2
License: GPLv2 and LGPL
BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)
BuildArch: noarch
Obsoletes: redhat-logos
Provides: redhat-logos = %{version}-%{release}
Provides: system-logos = %{version}-%{release}
Requires: generic-logos gdm gnome-session
Conflicts: fedora-logos
Conflicts: kdebase <= 3.1.5
Conflicts: anaconda-images <= 10
Conflicts: redhat-artwork <= 5.0.5
# For _kde4_appsdir macro:
BuildRequires: kde-filesystem


%description
The guifi-logos package contains various image files which can be
used by the bootloader, anaconda, and other related tools. It can
be used as a replacement for the fedora-logos package, if you are
unable for any reason to abide by the trademark restrictions on the
fedora-logos package.

%prep
%setup -q

%build

%install
rm -rf $RPM_BUILD_ROOT
%{__install} -d -m 0755 %{buildroot}/%{_datadir}/%{name}/
%{__cp} -r * %{buildroot}/%{_datadir}/%{name}/
%{__install} -d -m 0755 %{buildroot}/%{_datadir}/%{name}/

# should be ifarch i386
#mkdir -p $RPM_BUILD_ROOT/boot/grub
#install -p -m 644 bootloader/grub-splash.xpm.gz $RPM_BUILD_ROOT/boot/grub/splash.xpm.gz
# end i386 bits

#mkdir -p $RPM_BUILD_ROOT%{_datadir}/firstboot/pixmaps
#for i in firstboot/* ; do
#  install -p -m 644 $i $RPM_BUILD_ROOT%{_datadir}/firstboot/pixmaps
#done
#
#mkdir -p $RPM_BUILD_ROOT%{_datadir}/pixmaps/splash
#for i in gnome-splash/* ; do
#  install -p -m 644 $i $RPM_BUILD_ROOT%{_datadir}/pixmaps/splash
#done

#mkdir -p $RPM_BUILD_ROOT%{_datadir}/pixmaps
#for i in pixmaps/* ; do
#  install -p -m 644 $i $RPM_BUILD_ROOT%{_datadir}/pixmaps
#done

#mkdir -p $RPM_BUILD_ROOT%{_kde4_appsdir}/ksplash/Themes/SolarComet/1280x1024
#install -p -m 644 ksplash/SolarComet-kde.png $RPM_BUILD_ROOT%{_kde4_appsdir}/ksplash/Themes/SolarComet/1280x1024/logo.png


#(cd anaconda; make DESTDIR=$RPM_BUILD_ROOT install)

%post
%{__cp} $RPM_BUILD_ROOT/%_datadir/%name/bootloader/grub-splash.xpm.gz $RPM_BUILD_ROOT/boot/grub/splash.xpm.gz
%{__cp} $RPM_BUILD_ROOT/%_datadir/%name/firstboot/* $RPM_BUILD_ROOT/%{_datadir}/firstboot/pixmaps
%{__cp} $RPM_BUILD_ROOT/%_datadir/%name/gnome-splash/* $RPM_BUILD_ROOT/%{_datadir}/pixmaps/splash
%{__cp} $RPM_BUILD_ROOT/%_datadir/%name/pixmaps/* $RPM_BUILD_ROOT/%{_datadir}/pixmaps
%{__cp} $RPM_BUILD_ROOT/%_datadir/%name/anaconda/syslinux-vesa-splash.jpg /usr/lib/anaconda-runtime/syslinux-vesa-splash.jpg
%{__cp} $RPM_BUILD_ROOT/%_datadir/%name/anaconda/anaconda_header.png $RPM_BUILD_ROOT/%{_datadir}/anaconda/pixmaps/anaconda_header.png
%{__cp} $RPM_BUILD_ROOT/%_datadir/%name/anaconda/progress_first-lowres.png $RPM_BUILD_ROOT/%{_datadir}/anaconda/pixmaps/progress_first-lowres.png
%{__cp} $RPM_BUILD_ROOT/%_datadir/%name/anaconda/progress_first.png $RPM_BUILD_ROOT/%{_datadir}/anaconda/pixmaps/progress_first.png
%{__cp} $RPM_BUILD_ROOT/%_datadir/%name/anaconda/splash.png $RPM_BUILD_ROOT/%{_datadir}/anaconda/pixmaps/splash.png
/usr/bin/gconftool-2 -t str --set /desktop/gnome/background/picture_filename /usr/lib/anaconda-runtime/syslinux-vesa-splash.jpg
#gconftool-2 --direct --config-source xml:readwrite:/var/lib/gdm/.gconf -s --type string /desktop/gnome/background/picture_filename /usr/lib/anaconda-runtime/syslinux-vesa-splash.jpg
#echo "guifi-logos installed succesfully!"

%clean
rm -rf $RPM_BUILD_ROOT

%files
%defattr(-, root, root)
%{_datadir}/%{name}/*
#%doc COPYING COPYING-kde-logo
#%{_datadir}/firstboot/*
#%{_datadir}/anaconda/pixmaps/*
#%{_datadir}/pixmaps/*
#/usr/lib/anaconda-runtime/*.jpg
#%{_kde4_appsdir}/ksplash/Themes/SolarComet/1280x1024/logo.png
# should be ifarch i386
#/boot/grub/splash.xpm.gz
# end i386 bits

%changelog
* Sun Jan 18 2009 Ramon Roca <ramon.roca@guifi.net> 1.0
- spin for guifi Linux

* Tue Oct 28 2008 Bill Nottingham <notting@redhat.com> - 10.0.1-1
- incorporate KDE logo into upstream source distribution
- fix system-logo-white.png for compiz bleeding (#468258)

* Mon Oct 27 2008 Jaroslav Reznik <jreznik@redhat.com> - 10.0.0-3
- Solar Comet generic splash logo redesign

* Sun Oct 26 2008 Kevin Kofler <Kevin@tigcc.ticalc.org> - 10.0.0-2
- Add (current version of) KDE logo for SolarComet KSplash theme

* Thu Oct 23 2008 Bill Nottingham <notting@redhat.com> - 10.0.0-1
- update for current fedora-logos, with Solar theme

* Fri Jul 11 2008 Bill Nottingham <notting@redhat.com> - 9.99.0-1
- add a system logo for plymouth's spinfinity plugin

* Tue Apr 15 2008 Bill Nottingham <notting@redhat.com> - 9.0.0-1
- updates for current fedora-logos (much thanks to <duffy@redhat.com>)
- remove KDE Infinity splash
 
* Mon Oct 29 2007 Bill Nottingham <notting@redhat.com> - 8.0.2-1
- Add Infinity splash screen for KDE

* Thu Sep 13 2007 Bill Nottingham <notting@redhat.com> - 7.92.1-1
- add powered-by logo (#250676)
- updated rhgb logo (<duffy@redhat.com>)

* Tue Sep 11 2007 Bill Nottinghan <notting@redhat.com> - 7.92.0-1
- initial packaging. Forked from fedora-logos, adapted from the Fedora
  Art project's Infinity theme
