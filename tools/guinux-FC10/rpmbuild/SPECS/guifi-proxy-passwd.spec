Name: guifi-proxy-passwd
Version: 0.1
Release: 1%{?dist}
Summary: The federated proxy package for guifi.net for passswd auth

Group: Applications/Internet
License: GPLv3
URL: http://www.guifi.net/
Source0: %{name}-%{version}.tar.gz

BuildRoot: %{_tmppath}/%{name}-%{version}-%{release}-root-%(%{__id_u} -n)

Requires: chkconfig squid
Requires(postun): /sbin/service 
Requires(postun): /sbin/chkconfig 
BuildArch: noarch

%description
guifi.net Federated proxy enables a squid proxy service to be 
synchronized with user authentication provided by the guifi.net
applications and keep synchronized a local passwd file and 
authenticate against it.

%prep
%setup -q
echo "*/5 * * * *	root	%{_bindir}/proxypasswd.sh > /dev/null 2>&1" >guifi-proxy.cron

%install
rm -rf %{buildroot}
%{__mkdir} -p %{buildroot}/%{_sysconfdir}/%{name}
%{__install} -d -m 0755 %{buildroot}/%{_datadir}/%{name}/
%{__install} -m 0644 * %{buildroot}/%{_datadir}/%{name}/
%{__install} -D -m 0644 guifi-proxy.cron %{buildroot}/%{_sysconfdir}/cron.d/guifi-proxy
%{__install} -D -m 0755 proxypasswd.sh %{buildroot}/%{_sbindir}/proxypasswd.sh

%clean
rm -rf %{buildroot}

%pre

%post
%{__cp} -f %_sysconfdir/squid/squid.conf  %_sysconfdir/squid/squid.conf.noguifi
%{__cp} %_datadir/%name/squid.guifi %{_sysconfdir}/squid/squid.conf
if [ "$(uname -i)" = "i386" ]; then
  ln -s %{buildroot}/usr/lib/squid/ncsa_auth %{buildroot}/usr/sbin/guifi_passwd_auth
fi
if [ "$(uname -i)" = "x86_64" ]; then
  ln -s /usr/lib64/squid/ncsa_auth /usr/sbin/guifi_passwd_auth
fi
# perl %_datadir/%name/system-config-guifi-proxy-passwd.pl
#/sbin/chkconfig squid on
#if [ $1 == 1 ]; then
#	/sbin/service squid restart || :
#fi
#echo "To complete the installation, run at the shell as root:"
#echo "%_datadir/%name/system-config-guifi-proxy-passwd.pl"
#echo "to set your proxy service id."

%postun
rm -f /usr/sbin/guifi_passwd_auth
#/sbin/chkconfig squid off

%files
%dir %{_datadir}/%{name}
%{_datadir}/%{name}/*
%config(noreplace) %{_sysconfdir}/cron.d/guifi-proxy
%config(noreplace) %{_sbindir}/proxypasswd.sh
%attr(0777,root,root) %{_sbindir}/proxypasswd.sh
%attr(0722,root,root) %{_datadir}/%{name}/system-config-guifi-proxy-passwd.pl
#%attr(0620,root,squid) %{_sysconfdir}/squid/squid.conf


%changelog
* Fri Jan 9 2009 Ramon Roca <ramon.roca@guifi.net> - 0.1a
- Initial package.

