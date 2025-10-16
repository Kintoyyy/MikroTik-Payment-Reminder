/container mounts
add dst="/uploads" name=MOUNT_PAYMENT_UPLOADS src="/var/www/html/uploads"
add dst="/database.sqlite" name=MOUNT_SQLITE src="/var/www/html/database.sqlite"

/interface bridge
add name=containers

/interface veth
add address=172.17.0.2/30 gateway=172.17.0.1 name=veth-payment-reminder

/interface bridge port
add bridge=containers interface=veth-payment-reminder

/container config
set registry-url="https://registry-1.docker.io" tmpdir="disk1/tmp"

/container
add interface=veth-payment-reminder logging=yes mounts=MOUNT_PAYMENT_UPLOADS,MOUNT_SQLITE name="kintoyyy/mikrotik-payment-reminder:latest" root-dir="disk1/images/payment-reminder" workdir="/"





/ip address
add address=172.17.0.1/30 interface=containers network=172.17.0.0

/ip firewall nat
add action=masquerade chain=srcnat src-address=172.17.0.0/30
add action=dst-nat chain=dstnat dst-port=80 protocol=tcp to-addresses=172.17.0.2 to-ports=80

/ip pool
add name=EXPIRED ranges=10.254.0.10-10.254.0.250

/ppp profile
add address-list=EXPIRED dns-server=172.17.0.1 local-address=10.254.0.1 name=EXPIRED rate-limit=128k/128k remote-address=EXPIRED

/ip firewall address-list
add address=172.17.0.2 list=WHITELIST

/ip firewall nat
add action=redirect chain=dstnat dst-address-list=!WHITELIST dst-port=80,443 protocol=tcp src-address-list=EXPIRED to-ports=8082

/ip firewall filter
add action=drop chain=forward dst-address-list=!WHITELIST dst-port=80,443 protocol=tcp src-address-list=EXPIRED

/ip proxy
set enabled=yes port=8082

/ip proxy access
add dst-port=8082 src-address=10.254.0.0/24
add action=redirect action-data=172.17.0.2:80 dst-address=!172.17.0.1 dst-host=!172.17.0.2 dst-port=80,443 src-address=10.254.0.0/24
add action=deny src-address=10.254.0.0/24