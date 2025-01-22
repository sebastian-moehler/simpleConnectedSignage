# About
This is a simple signage solution which utilizes Raspberry Pis connected with some displays to show slides. These can be images, text/html or web pages. The duration and a start / stop date can be set per slide.

Access http://ip.to.the.pi/signage/api to upload pictures or change the configuration. The default credentials are signage / editor

If you have multiple signage displays, you can also set another pi as the source of the configuration.

# Hardware requirements
Altough I inteded this for Raspberry Pis, there is no reason why it shouldn't work on any pc or mini computer with a linux os. There isn't much to it, it's a simple local web server and browser autostart.

As for the neccesary raspberry circuit boards, i recommend at least a pi 4 with 4 GB RAM. I tested it also with a Pi 4 with 2 GB RAM, which works if the display is only FullHD. A Raspberry 3B may also work, but here you need to make sure the images are no bigger than 5 MB or something like that. I already made sure to pre-load the content, but on my tests it still glitched the display in the browser.

If it's a single Pi, you do not necessarily need (constant) network. You can edit the configuration just fine on the Pi itself. However, a network connection may beat having to plug in a keyboard, mouse and usb stick every time 

# Install
Start with a fresh Raspberry OS image (https://www.raspberrypi.com/software/ - with desktop, without recommended software), finish the installation until you see the desktop. Connect with cable or wifi network. You may want to assign a static IP to the device and also alter the hostname. Both is possible using the GUI.

Open a terminal and get the repository:
```bash
git clone https://github.com/sebastian-moehler/simpleConnectedSignage.git
```

The easy way is to call the installation script
```bash
sudo ./simpleConnectedSignage/install.sh
```

If you distrust scripts from the internet and have some experience with linux, feel free to open it in an editor and only execute the lines you need. There should be nothing complicated there.

# Update
If there's a new version, you can just install it again. The install script won't touch the configuration file or any uploaded images.

# Configure
## Localy on the Pi:
Plug in a keyboard, mouse and a usb drive with the pictures. Press [Alt] + [F4] to exit the full screen mode. Open a browser and navigate to http://localhost/signage/api

## Over network
Open a browser and open http://\[ip.of.the.pi\]/signage/api

If you don't know the IP, your router should have a list of all connected devices. Look for the one with the hostname you set on installation. If you didn't set it then, it's probably something like "raspberry"

## configuration site
### password
The default username / password is signage / editor. To alter these, you need to edit the /var/www/html/api/.htpasswd file. There are online generators for the entries you have to write in the file.

### master / slave settings
Here you can insert the URL of another pi in the form
```
http://\[ip.of.other.pi\]/signage/api
```

Then this device will try load the configuration file and images from the specified Pi instead of using the one locally available. If device can't connect to the remote Pi it will use the local settings as fallback.

> [!WARNING] 
> The remote pi needs to have a static ip or a dns entry for this to work reliably

You can chain the masters (Pi1 gets it from Pi2, Pi2 gets it from Pi3 etc.), but there shouldnt be much cases where this is necessary. The local Pi will abort the chain after 5 redirects to avoid infinite loops.

### local settings
> default slide duration
Set the duration of the slides in seconds. Can be overwritten per slide

> image folder name
Here you can specify a folder under /var/www/html/signage. All images therein are appended to the end of the slide list with the default duration.

This is intended for a network drive where you just need to add images to a folder and they get displayed without a need to add a configuration entry.

There are at leat two ways of doing this, but both need advanced knowledge in linux administration. Because of this, i will only give the rough idea here:
- local share: You can install samba on the Pi and add a share for this directory. Then you can connect from any other device and add / remove images
- external share: You need another machine that's available every time the Pi is on that has a share with the image (i.E. a fileserver, through some routers may offer this functionality with an added usb drive). Then you only need to mount the share in the specified folder.

> [!WARNING] 
> If you use an external share, you should add an entry in /etc/fstab that re-mounts the share every other minute or so. This doesn't happen automatically.

### slides
> slide type
A deeper description of the types comes further down

> duration
sets the duration in seconds for this slide. Use -1 for the default duration. 0 won't show it

> [!WARNING] 
> Refrain from using 0. In the time the current slide is shown, the next one is already loading in the background to make sure it's fully loaded before it's time to show it.
> Using 0 disables that mechanism. If you want to hide a slide without deleting it, consider using 'show from' or 'show until', these don't circumvent the pre-loading.

> show from
Do not show this slide before this date

> show until
Last day the slide should be shown

> [!INFO] 
> If the dates doesn't work as intended: This condition is checked locally. Make sure that the Pi has the correct date. 

#### Text slide
The content gets displayed as text

#### HTML slide
similar to the text slide, but allows arbitrary html. 

#### website slide
Shows the website. 'content' needs to be a valid url:
```
https://github.com/sebastian-moehler/simpleConnectedSignage
```

Can be used to include dynamically changing content like calendars.

> [!INFO] 
> The website is also getting pre-loaded during the previous slide. If you have an animation that needs to be visible, add a 0-duration text slide beforehand - but be aware you will most likely also get the loading process displayes

#### image slide
Shows a previously uploaded image. 'content' needs to be just the filename displayed over the image under 'uploadede images'

### uploaded images
shows the images that are locally available on the Pi. You can upload new images and delete old ones.

## changing slide order
As the configuration GUI is quite rudimentary at the moment, it's not yet possible to change the slide order in he GUI.

For that, you need to change the onfiguration file (/var/www/html/signage/list.json) directly.

Make a backup of it before you change anything, then open it in any editor. Look for the '"data":\[{...slide1...},{...slide2...},...\]' section. Here you can copy / cut / paste the slides. Make sure you include the parantheses in that and the final structure looks like \[{...},{...}, .. ,{...}\]

# About shutting down the Raspberry after usage
If you only use the signage display once in a whlie, you may ask yourself if you should shut down the device or if you can simply unplug it.

I strongly recommend the shut down option in nearly every other case. However, in this case it should be fine to just unplug the Pi. Nothing here causes disk writes in normal operations so there is not much that can go wrong. I know of Pis with a similar mode of operations which gets unplugged regulary for years with no ill effects.