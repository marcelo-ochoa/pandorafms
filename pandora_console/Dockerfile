FROM centos:centos6 AS develop
LABEL maintainer="Pandora FMS Team <info@pandorafms.com>"

#Clone the repo and perform initial install
RUN yum install -y git && \
	mkdir -p /tmp/pandorafms && cd /tmp/pandorafms && git init && \
	git remote add origin https://github.com/pandorafms/pandorafms.git && git config core.sparsecheckout true && \
	echo "pandora_console/" >> .git/info/sparse-checkout && echo "pandora_server/" >> .git/info/sparse-checkout && \
	git pull --depth=1 origin develop

FROM centos:centos6
LABEL maintainer="Pandora FMS Team <info@pandorafms.com>"

RUN yum -y update && \
	yum install -y \ 
	httpd \
	cronie \
	ntp \
	openldap \
	nfdump \
	wget \
	curl \
	plymouth \
	xterm \
	php \ 
	php-gd \ 
	graphviz \ 
	php-mysql \ 
	php-pear-DB \ 
	php-pear \
	php-pdo \
	php-mbstring \ 
	php-ldap \ 
	php-snmp \ 
	php-ldap \ 
	php-common \ 
	php-zip \ 
	nmap \
	net-snmp-utils \
	mod_ssl \
	xprobe2 && \
	yum clean all

COPY --from=develop /tmp/pandorafms/pandora_console /var/www/html/pandora_console

#Exposing ports for: HTTP, SNMP Traps, Tentacle protocol
EXPOSE 80 162/udp 443 41121

# Simple startup script to avoid some issues observed with container restart
ADD docker_entrypoint.sh /entrypoint.sh
RUN rm -rf /tmp/pandorafms && rm -rf /tmp/* && rm -rf /var/tmp/* && \
    chmod -v +x /entrypoint.sh

CMD ["/entrypoint.sh"]

