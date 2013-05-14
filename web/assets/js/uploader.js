(function($) {

    var CrisUploader = function(options) {

        var $t = $(this),
                self = this,
                imgdata = {};
        var _URL = window.URL || window.webkitURL;
        
        
        options = $.extend(true, {}, {
            fineuploader: {
                uploaderType: 'basic',
                allowMultipleItems: false,
                request: {
                    endpoint: 'crop-image',
                    params: {
                        //crop: function() {return self.getCropData();}
                        crop: {
                            original_width: self.originalWidth,
                            x: 0,
                            y: 0,
                            w: 270,
                            h: 203
                        }
                    }
                }
            }, // fineuploader options
            jcrop: {setSelect: [0, 0, 100, 100]}, // jcrop options
            impromptu: {}, // impromptu options
            folder: '',
            cropAction: '',
            onComplete: function() {
            }
        },
        options);

        // STATE
        this.busy = false;
        
        // CONFIGURE UPLOADER
        
        this.uploader = $t.fineUploader(options.fineuploader);
        this.uploader.on('complete', function(event, id, fileName, responseJson) {
            imgdata = responseJson;

            $t.trigger('uploadComplete', [responseJson]);
            
            if (self.completePromise != undefined) {
                self.completePromise.success(responseJson);
                self.completePromise = null;
            }

        });

        this.uploader.on('submit', function(e, id, name) {
            console.log(id, 'submit id');
            console.log(name, 'submit name');
            //return false;
        });
        this.uploader.on('submitted', function() {
            console.log('submitted');
            //return false;
        });

        if (typeof(options.fineuploader.callbacks.onValidate) == 'function') {
            this.uploader.on('validate', options.fineuploader.callbacks.onValidate);
        }

        // EVENTS
        
        this.setCompletePromise = function(promise) {
            this.completePromise = promise;
        };
        
        
        // CROPPED IMAGE
        
        this.showCroppedImage = function(data) {
            console.log(data);
            $('#PhotoCrops').html($('<img>').attr('src', options.folder+data.uploadName));
        };
        
        
        // get CROPPED DATA
        
        this.getCropData = function() {
            var res = {
                original_width: self.originalWidth,
                x: 0,
                y: 0,
                w: 270,
                h: 203
            };
            console.log(res, 'crop data');
            return res;
        }
        
        // JCROP UI
        
        this.initCrop = function(img, endCropPromise) {
            // trigger event 'start_crop'
            $t.trigger('startCropUI',[img]);
            
            // init UI CROP
            $('#PhotoCrops').append(img);
            $(img).Jcrop(options.jcrop, function(){
                self.jcrop_api = this;
            });
            
            // Button crop
            $(img).after($('<button>').text('Crop').click(function(){ 
                self.jcrop_api.disable();
                //console.log(endCropPromise, 'crop');
                endCropPromise.success();
            }));
            
            // trigger event 'end_crop'
            $t.trigger('endCropUI',[img]);
        };



        // CONFIGURE DRAG AND DROP 

        $t.append($('<div id="myDropZone"><span>Drop file here</span></div>'));
        $('#myDropZone').fineUploaderDnd({
            classes: {
                dropActive: "cssClassToAddToDropZoneOnEnter"
            }
        })
        .on('processingDroppedFiles', function(event) {
            //TODO: display some sort of a "processing" or spinner graphic
        })
        .on('processingDroppedFilesComplete', function(event, files) {
            //TODO: hide spinner/processing graphic
            if (self.busy) {
                return;
            }
            
            //console.log(files);
            if ((file = files[0])) {
                img = new Image();
                img.onload = function() {
                    self.originalWidth = this.width;
                    //alert(this.width + " " + this.height);
                };
                img.onerror = function() {
                    alert("not a valid file: " + file.type);
                };
                img.src = _URL.createObjectURL(file);
                img.style.width = '460px';
                //console.log(img);
                self.busy = true;
                var promise = new qq.Promise();
                promise.then(
                        function() {
                            var cpromise = new qq.Promise();
                            self.setCompletePromise(cpromise
                                    .then(self.showCroppedImage, null)
                            );
                            $t.fineUploader('addFiles', files); //this submits the dropped files to Fine Uploader
                            self.busy = false;
                            self.cleanUI();
                        },
                        function() {
                            self.busy = false;
                            self.cleanUI();
                        }                
                );
                
                // hide dropzone
                $('#myDropZone').hide();
                
                // init crop ui
                self.initCrop(img, promise);
            }
        });
        
        this.cleanUI = function() {
            $('#myDropZone').show();
            $('#PhotoPrevs').empty();
        };
        

    };

    $.fn.crisUploader = CrisUploader;


})(jQuery);


