/**
 * Holds data for one slide
 */
class slide {
    type = "text";
    content = "";
    duration = undefined;
    from = undefined;
    to = undefined;

    constructor(obj) {
        this.type = obj.type ?? "text";
        this.content = obj.content ?? "Kein Inhalt";
        this.duration = obj.duration;   // undefined - use default from config
        this.from = obj.from == undefined ? undefined : new Date(obj.from);
        this.to = obj.to == undefined ? undefined : new Date(obj.to);
    }

    /**
     * checks if the slide should be shown, based on the dates in from and to
     */
    get isValid() {
        let now = new Date();

        return (this.from == undefined || this.from < now) && (this.to == undefined || now < this.to);
    }
}

/**
 * object from list.json
 */
class configObj {
    redirect = undefined;
    imgFolder = undefined;
    defaultDuration = 15;

    slides = [];

    /**
     * index of slides that is currently shown
     * we start at -1 so we can call nextSlide() at the start while getting the first slide
     */
    currentSlideIndex = -1;

    constructor(
        redirect,
        imgFolder,
        defaultDuration
    ) {
        this.redirect = redirect ?? undefined;
        this.imgFolder = imgFolder ?? undefined;
        this.defaultDuration = defaultDuration ?? 20;
    }

    /**
     * gives the current slide back
     */
    get currentSlide() { return this.slides[this.currentSlideIndex]; }

    /**
     * sets the currentSlide to the next one and gives it back
     */
    get nextSlide() { 
        this.currentSlideIndex = (1 + this.currentSlideIndex) % this.slides.length;
        return this.currentSlide; 
    }

    /**
     * indicates if we should reload the list because we currently show the last slide
     */
    get allSlidesShown() { return this.currentSlideIndex >= this.slides.length - 1; }

    /**
     * builds the object based on the json object we got from the webserver
     * @param {*} obj 
     * @returns 
     */
    static getFromResult(obj) {
        let cfg = new configObj(obj.redirect, obj['img-folder'], obj['default-duration']);  // js doesn't like '-' in property names

        // parse the slides. This is not an array, but an object with properties 1, 2, etc
        for (var key in obj.data) {
            // we get some properties derived from object - we don't need those
            if (!obj.data.hasOwnProperty(key)) continue;

            // add new slides only if the given dates allow it
            let s = new slide(obj.data[key]);
            if(s.isValid) cfg.slides.push(s);
        }

        if(cfg.slides.length == 0) {
            cfg.slides.push(new slide({"type": "text", "content": "No slides prepared", "duration": undefined, "from": undefined, "to": undefined}));
        }
        
        return cfg;
    }
}