#!/bin/bash

# installs neccessary software

if [[ $(/usr/bin/id -u) -ne 0 ]]; then
    echo "Not running as root"
    exit
fi

# make sure we are in the folder where the script is
cd "$(dirname "$0")"

echo "### system update"
apt update
apt upgrade -y

echo "### installing webserver"
apt install apache2 php chromium chromium-browser unclutter -y
a2enmod headers

mkdir -p /var/www/html/signage/
cp -R webpage/* /var/www/html/signage/
# there's no need for apache to be able to write to the files - excluding the upload folder and the config file.
sudo chown -R www-data:www-data /var/www/html/signage/img
# do not overwrite config file if it exists
if [ ! -s "/var/www/html/signage/list.json"]; then
    cp list.json /var/www/html/signage/
    # give a list of the ips the pi has
    sed -i "s|IPTEMPLATE|$(hostname -I | sed "s|^|http://|; s|\s*$|/signage/api<br/>|")|" /var/www/html/signage/
fi

sudo chown  www-data:www-data /var/www/html/signage/list.json

echo "### updating config files"
sed -i 's/^.*file_uploads *=.*$/file_uploads = On/i' /etc/php/8.*/apache2/php.ini
sed -i 's/^.*upload_max_filesize *=.*$/upload_max_filesize = 20M/i' /etc/php/8.*/apache2/php.ini
# Todo: it isn't necessary to allow the override for all areas
sed -Ei 's/^(\s*AllowOverride )None\s*$/\1All/i' /etc/apache2/apache2.conf

# guess the user name
name=$(ls /home | head -n 1)
# Create a desktop shortcut
printf "[Desktop Entry]\nName=Signage\nExec=chromium-browser http://localhost/signage --kiosk --noerrdialogs --disable-infobars --no-first-run --start-maximized\nTerminal=false\nType=Application\n" > "/home/$name/Desktop/signage.desktop"

sudo cp "/home/$name/Desktop/signage.desktop" /etc/xdg/autostart/


