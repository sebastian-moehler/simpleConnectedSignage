#!/bin/bash

# installs the signage web page

# needs root fpr oblivious reasons
if [[ $(/usr/bin/id -u) -ne 0 ]]; then
    echo "Not running as root. Use"
    echo "sudo $0"
    exit
fi

# make sure we are in the folder where the script is - then we can use relative paths
cd "$(dirname "$0")"

echo "### system update"
apt update
apt upgrade -y

echo "### installing webserver"
apt install apache2 php chromium chromium-browser unclutter -y
a2enmod headers
# TODO make use of unclutter to hide the mouse pointer

# create a new apache subfolder in case there's something already installed
# You may choose to use another folder. In that case you need to update the path to .htpasswd in /var/www/html/.../api/htaccess and the desktop shortcut
mkdir -p /var/www/html/signage/
# fill the new folder. We do not write the config yet!
cp -R webpage/* /var/www/html/signage/
# using cp as root means the permissions are for root only. That's fine -
# there's no need for apache to be able to write to the files - excluding the upload folder and the config file.
sudo chown -R www-data:www-data /var/www/html/signage/img
# do not overwrite config file if it exists
if [ ! -s "/var/www/html/signage/list.json" ]; then
    cp list.json /var/www/html/signage/
    # the default configuration has a slide that should list all the raspberries ips for easier access.
    # we can only write them now as we have no placeholders in the slides
    # you can ommit this step if you do a manual install. If you're doing that i expect you know what you're doing anyway and i do not need to tell you how to access the pi.
    sed -i "s|IPTEMPLATE|$(hostname -I | sed "s|^|http://|; s|\s*$|/signage/api<br/>|")|" /var/www/html/signage/list.json
fi

# make sure apache can overwrite the config else you won't be able to utilize the web configuration
sudo chown  www-data:www-data /var/www/html/signage/list.json

echo "### updating config files"
# allowing file uploads larger than 2MB which should be default
sed -i 's/^.*file_uploads *=.*$/file_uploads = On/i' /etc/php/8.*/apache2/php.ini
sed -i 's/^.*upload_max_filesize *=.*$/upload_max_filesize = 20M/i' /etc/php/8.*/apache2/php.ini

# allow apache to adhere to local htaccess. This is needed for the password protection of the web configuration
# Todo: it isn't necessary to allow the override for all areas
sed -Ei 's/^(\s*AllowOverride )None\s*$/\1All/i' /etc/apache2/apache2.conf

# Create desktop shortcuts
# guess the user name as we have no way of knowing it
name=$(ls /home | head -n 1)
# Create a desktop shortcut
printf "[Desktop Entry]\nName=Signage\nExec=chromium-browser http://localhost/signage --kiosk --noerrdialogs --disable-infobars --no-first-run --start-maximized\nTerminal=false\nType=Application\n" > "/home/$name/Desktop/signage.desktop"

# add it to autostart
sudo cp "/home/$name/Desktop/signage.desktop" /etc/xdg/autostart/

echo "### done. Please reboot."

