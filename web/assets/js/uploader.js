// template from http://jqueryboilerplate.com/
// the semi-colon before function invocation is a safety net against concatenated
// scripts and/or other plugins which may not be closed properly.
;
(function($, window, document, undefined) {

    // undefined is used here as the undefined global variable in ECMAScript 3 is
    // mutable (ie. it can be changed by someone else). undefined isn't really being
    // passed in so we can ensure the value of it is truly undefined. In ES5, undefined
    // can no longer be modified.

    // window and document are passed through as local variable rather than global
    // as this (slightly) quickens the resolution process and can be more efficiently
    // minified (especially when both are regularly referenced in your plugin).

    var _URL = window.URL || window.webkitURL;

    // Create the defaults once
    var pluginName = "jqueryCropUploader";
    var defaults = {
        fineuploader: {
            uploaderType: 'basic',
            allowMultipleItems: false,
            request: {
                endpoint: 'crop-image',
                params: {
                }
            }
        },
        jcrop: {
            setSelect: [0, 0, 100, 100]
        },
        resizeWidth: 460,
        finalWidth: 270,
        finalHeight: 203
    };
    
    // The actual plugin constructor
    function JqueryCropUploader(element, options) {
        this.el = element;
        this.$el = $(this.element);
        self = this;

        // jQuery has an extend method which merges the contents of two or
        // more objects, storing the result in the first object. The first object
        // is generally empty as we don't want to alter the default options for
        // future instances of the plugin
        this.options = $.extend({}, defaults, options);
        this.options.fineuploader = $.extend({}, defaults.fineuploader, options.fineuploader);
        this.options.jcrop = $.extend({}, defaults.jcrop, options.jcrop);

        this._defaults = defaults;
        this._name = pluginName;

        // init state
        this.state = {
            busy: false
        }
        this.imgdata = {};

        this.init();
    }

    JqueryCropUploader.prototype = {
        init: function() {
            // Place initialization logic here
            // You already have access to the DOM element and
            // the options via the instance, e.g. this.element
            // and this.options
            // you can add more functions like the one below and
            // call them like so: this.yourOtherFunction(this.element, this.options).
            if (!this.checkReqs()) {
                return;
            }
            this.initUploader(this.el, this.options.fineuploader);
            this.initDnDUI(this.el, this.options);
        },
        checkReqs: function() {
            var ok = true;
            // Check for the various File API support.
            if (window.File && window.FileReader && window.FileList && window.Blob) {
                // Great success! All the File APIs are supported.
            } else {
                alert('The File APIs are not fully supported in this browser.');
                ok = false;
            }
            return ok;
        },
        initUploader: function(el, options) {
            var that = this;
            // set crop params callback
            options.request.params.crop = function() {
                that.getCropData();
            };
            options.request.params.x = function() {return that.getX();}
            options.request.params.y = function() {return that.getY();}
            options.request.params.w = function() {return that.getW();}
            options.request.params.h = function() {return that.getH();}
            options.request.params.rw = this.options.resizeWidth;
            options.request.params.wf = this.options.finalWidth;
            options.request.params.hf = this.options.finalHeight;
            // init uploader
            this.uploader = $(el).fineUploader(options);
                
            // define event handlers
            this.uploader.on('complete', function(event, id, fileName, responseJson) {
                if (responseJson.error) {
                    alert(responseJson.error);
                    return false;
                }
                that.imgdata = responseJson;

                $(el).trigger('uploadComplete', [responseJson]);
                if (that.completePromise != undefined) {
                    that.completePromise.success(responseJson);
                    that.setCompletePromise(null);
                }
            });
            this.uploader.on('submit', function(e, id, name) {
                //return false;
            });
            this.uploader.on('submitted', function() {
                //return false;
            });
            if (typeof(options.callbacks.onValidate) == 'function') {
                this.uploader.on('validate', options.callbacks.onValidate);
            }
            
        },
        initDnDUI: function(el, options) {
            var that = this;
            this.dropZone = $('<div id="myDropZone"><span>Drop file here</span></div>');
            $(el).append(this.dropZone);
            this.dropZone.fineUploaderDnd({
                classes: {
                    dropActive: "cssClassToAddToDropZoneOnEnter"
                }
            }).on('processingDroppedFiles', function(event) {
                //TODO: display some sort of a "processing" or spinner graphic
            }).on('processingDroppedFilesComplete', function(event, files) {
                //TODO: hide spinner/processing graphic
                that.startFetchingLocalFile(files);
            });
            this.initFileUI(this.dropZone, options);
        },
        initFileUI: function(el, options) {
            var that = this;
            this.fileui = $('<input type="file" id="file-select" />');
            $(el).append(this.fileui);
            this.fileui.on('change', function(event) {
                //TODO: hide spinner/processing graphic
                that.startFetchingLocalFile(event.target.files);
            });
        },
        startFetchingLocalFile: function(files) {
            var that = this;
            if (that.isBusy()) {
                return;
            }

            if ((file = files[0])) {
                that.files = files;
                img = new Image();
                img.onload = function() {
                    that.state.originalWidth = this.width;
                    //alert(this.width + " " + this.height);
                };
                img.onerror = function() {
                    alert("not a valid file: " + file.type);
                };
                img.src = _URL.createObjectURL(file);
                img.style.width = this.options.resizeWidth+'px';
                //console.log(img);
                that.setBusy(true);
                var promise = new qq.Promise();
                promise.then(
                        function() {
                            var cpromise = new qq.Promise();
                            that.setCompletePromise(cpromise.then(function(data) {
                                that.showCroppedImage(data);
                            }, null));
                            $(that.el).fineUploader('addFiles', that.files); //this submits the dropped files to Fine Uploader
                            that.setBusy(false);
                            that.cleanUI();
                        },
                        function() {
                            that.setBusy(false);
                            that.cleanUI();
                        }
                );

                // hide dropzone
                that.dropZone.hide();

                // init crop ui
                that.startCropUI(img, promise);
            }
        },
        startCropUI: function(img, endCropPromise) {
            var that = this;

            // trigger event 'start_crop'
            this.$el.trigger('startCropUI', [img]);

            // init UI CROP
            $('#PhotoCrops').append(img);
            this.options.jcrop.onChange = function() {
                that.setCropData();
            }
            $(img).Jcrop(this.options.jcrop, function() {
                that.jcrop_api = this;
            });

            // Button crop
            $(img).after($('<button>').text('Crop').click(function() {
                that.jcrop_api.disable();
                //console.log(endCropPromise, 'crop');
                endCropPromise.success();
            }));

            // trigger event 'end_crop'
            this.$el.trigger('endCropUI', [img]);
        },
        setCropData: function() {
            if (this.jcrop_api) {
                this.state.cropdata = this.jcrop_api.tellSelect();
            }
            //console.log(this.state.cropdata, 'set crop data');
        },
        getCropData: function() {
            if (this.state.cropdata == undefined) {
                this.setCropData();
            }
            return this.state.cropdata;
        },
        getX: function() {
            return this.getCropData().x;
        },
        getY: function() {
            return this.getCropData().y;
        },
        getW: function() {
            return this.getCropData().w;
        },
        getH: function() {
            return this.getCropData().h;
        },
        setCompletePromise: function(promise) {
            this.completePromise = promise;
        },
        isBusy: function(busy) {
            return this.state.busy;
        },
        setBusy: function(busy) {
            this.state.busy = busy;
        },
        cleanUI: function() {
            $('#myDropZone').show();
            this.jcrop_api.destroy();
            this.jcrop_api = null;
            $('#PhotoCrops').empty();
        },
        showCroppedImage: function(data) {
            $('#PhotoCrops').html($('<img>').attr('src', this.options.folder + data.uploadName));
        }
    };

    // A really lightweight plugin wrapper around the constructor,
    // preventing against multiple instantiations
    $.fn[pluginName] = function(options) {
        return this.each(function() {
            if (!$.data(this, "plugin_" + pluginName)) {
                $.data(this, "plugin_" + pluginName, new JqueryCropUploader(this, options));
            }
        });
    };

})(jQuery, window, document);