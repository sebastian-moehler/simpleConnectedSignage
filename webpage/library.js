// loading images and urls can take some time. We don't want to have black screens between slide changes, so we load the next slide in the background

/**
 * loading slide
 * HTMLElement | null
 */
var slideContainer_loading = null;

/**
 * the html containers of the slides
 * Array<HTMLElement | null>
 */
var slideContainer = new Array(2);
slideContainer[0] = null;
slideContainer[1] = null;

/**
 * keeps track which of the containers is currently in use. Starts with -1 for easier handling after loading
 */
var current_slide_object = -1;

/**
 * config from this machine, keep for fallback
 */
var localConfig = null;

/**
 * the config to be used
 */
var config = null;

/**
 * current redirect path
 */
var currentRedirectURL = undefined;

/**
 * the slide that is already prepared for next display. We need to remember it because we need the duration
 */
var preparedSlideDuration = 15;

/**
 * reset the container references
 */
function setContainerRefs() {
    slideContainer_loading = document.getElementById('slide_loading');
    slideContainer[0] = document.getElementById('slide_0');
    slideContainer[1] = document.getElementById('slide_1');
}

function loadData() {
    // only get the container refs after getting the config - this gives the browser some time to build the site
    getConfig("", true);
}

/**
 * gets the index of the next 
 * @returns 
 */
function nextSlideObjectNumber() {
    return (current_slide_object + 1) % slideContainer.length;
}

/**
 * switches to the next slide container
 */
function nextSlideObject() {
    switchToSlideObject(nextSlideObjectNumber());
}

/**
 * switches to the slide with the specified number
 * @param {number} nr 
 */
function switchToSlideObject(nr) {
    current_slide_object = nr;

    if(slideContainer_loading != undefined) slideContainer_loading.hidden = true;

    slideContainer.forEach((element, index) => {
        if(element != undefined) element.hidden = index != nr;
    });
}

/**
 * loads the config from the given server
 * @param {*} url 
 */
function getConfig(url = "", startRun = false, depth = 0) {
    let path = "./list.json";

    // append slash t ourl in case there's none at the end
    if(url != "") 
        path = "" + url + "/list.json";

    fetch(path, {cache: "no-store"})    // we do not want to cache this list in case there are some changes.
    .then(response => {
        if(slideContainer_loading == null) setContainerRefs();

        if (response.status !== 200) {
            console.warn(`Looks like there was a problem. Status Code: ${response.status}`);
            redirect404(startRun);
            return;
        }
    
        // Examine the text in the response
        response.json().then(function(data) {
            if(url == ""){
                localConfig = configObj.getFromResult(data);
                config = localConfig;
            } else {
                config = configObj.getFromResult(data);
            }
            
            // Check if we need to get the config from another server. Check the current redirect count. We don't want infinite of them...
            if((config.redirect ?? "") != "") {
                if(depth < 5) {
                    currentRedirectURL = config.redirect;
                    // in case of error: we want to fall back to the local config. Easiest way is to delete the config and keep the local config
                    config = undefined;

                    // try / catch won't work here as this is async
                    getConfig(currentRedirectURL, startRun, depth+1);
                    return;
                } else {
                    let s = new slide({"type": "text", "content": "configuration error: to many redirections", "duration": 20, "from": undefined, "to": undefined})
                    config?.slides?.push(s);
                    localConfig?.slides?.push(s);
                }
                
            } 
    
            addExtraImageSlides()
            
            if(startRun) loadNextSlide(true);
            
        });
    })
    .catch(err => {
        console.warn('Fetch Error :-S', err);
        
        redirect404(startRun);
    });
}

function addExtraImageSlides() {
    // if we have an image folder, get these images
    if(config?.imgFolder == undefined) return;
    
    // we can't get a directory listing in js afaik - so we need to ask the server for a list
    let url = (currentRedirectURL ?? ".") + "/additionalImages.php"

    fetch(url, {cache: "no-store"})    // we do not want to cache this list in case there are some changes.
    .then(response => {
        if (response.status !== 200) {
            console.warn(`Looks like there was a problem. Status Code: ${response.status}`);
            return;
        }
    
        // Examine the text in the response
        response.json().then(function(data) {
            if(Array.isArray(data)) {
                data.forEach(path => {
                    config.slides.push(new slide({"type": "img", "content": "../" + path, "duration": undefined, "from": undefined, "to": undefined}));
                });
            }
        });
    })
    .catch(err => {
        console.warn('Fetch Error :-S', err);
    });
}

/**
 * In case of failed config get - fall back to localConfig if possible and display error slide
 * @param {*} startRun 
 */
function redirect404(startRun) {
    if(localConfig != undefined) {
        localConfig.slides.push(new slide({"type": "text", "content": "configuration error: failed to get config from " + currentRedirectURL, "duration": 20, "from": undefined, "to": undefined}));
    }

    currentRedirectURL = undefined;
    config = undefined;

    if(localConfig != undefined) {
        if(startRun) loadNextSlide(true);
    }
}

/**
 * switches to the next slide and pre-Loads the next one
 * @param {slide} s 
 */
function loadNextSlide(prepareFirst = false) {
    let cfg = config ?? localConfig;

    if(prepareFirst) {
        // on the first, after reloading: we need to prepare and directly show it. This may lead to a brief black screen while loading - but that's acceptable
        prepareSlide(cfg.nextSlide);
        preparedSlideDuration = cfg.currentSlide.duration ?? cfg.defaultDuration;
    }

    // show the next slide
    nextSlideObject();
    // already prepare the next one
    prepareSlide(cfg.nextSlide);

    // if we are already at the last slide, reload the config.
    // this way, we can change the config without the need to reload the complete site
    if(cfg.allSlidesShown) this.getConfig();

    // prepare for the next show
    setTimeout(() => {
        loadNextSlide();
    }, preparedSlideDuration * 1000);

    preparedSlideDuration = cfg.currentSlide.duration ?? cfg.defaultDuration;
}

/**
 * Sets the content for the next slide
 * @param {*} s 
 */
function prepareSlide(s) {
    let container = slideContainer[nextSlideObjectNumber()];

    if(container == undefined) return;

    switch (s.type) {
        case "text":
            container.innerHTML = "<h2>" + ((s.content ?? "") == "" ? "No content" : s.content) + "</h2>"
            break;
        case "html":
            container.innerHTML = (s.content ?? "") == "" ? "<h2>No content</h2>" : s.content;
            break;
        case "img":
            container.innerHTML = (s.content ?? "") == "" ? "<h2>No content</h2>" : "<img src='" + (currentRedirectURL ?? ".") + "/img/" + s.content + "'>";
            break;
        case "url":
            container.innerHTML = (s.content ?? "") == "" ? "<h2>No content</h2>" : "<iframe src='" + s.content + "'>";
            break;
        default:
            container.innerHTML = "<h2>Unknown type: " + s.type + "</h2>";
            break;
    }
}