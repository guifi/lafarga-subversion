Name: guifi-release
Version: 1.0
Release: 1%{?dist}
Summary: Sets system for guifi.net use

Group: Applications/Internet
License: GPLv3
URL: http://www.guifi.net/
Source0: %{name}-%{version}.tar.gz

BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

Requires: guifi-CNML-graphs guifi-proxy-passwd guifi-logos
Requires(postun): /sbin/service 
Requires(postun): /sbin/chkconfig 
BuildArch: noarch

%description
Setup the sistem for guifi.net livecd

%prep
%setup -q

%install
rm -rf %{buildroot}
%{__install} -d -m 0755 %{buildroot}/%{_datadir}/%{name}/
%{__cp} -r * %{buildroot}/%{_datadir}/%{name}/

%clean
rm -rf %{buildroot}

%pre

%post

%postun

%files
%dir %{_datadir}/%{name}
%{_datadir}/%{name}/*


%changelog
* Sat Jan 22 2009 Ramon Roca <ramon.roca@guifi.net> - 0.1a
- Initial package.

