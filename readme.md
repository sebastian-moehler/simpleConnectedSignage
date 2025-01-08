# Install
Start with a fresh raspberry image, finish the installation until you see the desktop. Connect with cable or wifi network.

Update and install necessary packages
```bash
sudo apt update; sudo apt upgrade
sudo apt install apache2 php chromium chromium-browser unclutter
sudo a2enmod headers
```

Allow uploads > 2MB. Please do not turture the smaller Raspis (<= 3) with pictures > 5MB. They may have problems displaying them.
```bash
sudo sed -i 's/^.*file_uploads *=.*$/file_uploads = On/i' /etc/php/8.*/apache2/php.ini
sudo sed -i 's/^.*upload_max_filesize *=.*$/upload_max_filesize = 20M/i' /etc/php/8.*/apache2/php.ini
```

get webpage. 
clone... TODO!
You may choose to use another folder. In that case you need to update the path to .htpasswd in api/htaccess

Make sure apache can read the directory (and write list.json and img/)
```bash
sudo chown -R www-data:www-data /var/www/html/signage/
```

For the password protection on /api to work, go to /etc/apache2/apache2.conf and search for <Directory /var/www/> and change AllowOverride to All
if you don't mind to set it for all sites on the raspberry use
```bash
sudo sed -Ei 's/^(\s*AllowOverride )None\s*$/\1All/i' /etc/apache2/apache2.conf
```

If you didn't do it before, change the name of the pi and make sure the display manager is set to labwc
```bash
sudo raspi-config 
  > Advanced > A6 Wayland > W3 Labwc
  > System Options > S4 Hostname
```

Create a desktop shortcut
```bash
printf "[Desktop Entry]\nName=Signage\nExec=chromium-browser http://localhost/signage --kiosk --noerrdialogs --disable-infobars --no-first-run --start-maximized\nTerminal=false\nType=Application\n" > ~/Desktop/signage.desktop
```

Copy the shortcut to the autostart so you don't have to start it every time after boot
```bash
sudo cp ~/Desktop/signage.desktop /etc/xdg/autostart/
```

And reboot
```bash
sudo reboot
```

# Configure
TODO