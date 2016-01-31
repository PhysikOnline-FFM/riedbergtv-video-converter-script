$( document ).ready(function() {
    var r = new Resumable({
        target: 'upload.php',
        testChunks: true,
		chunkSize: 4*1024*1024,
		simultaneousUploads: 2,
		query: {}, //Extra parameters to include in the multipart POST with data. This can be an object or a function. If a function, it will be passed a ResumableFile object
    });
 
    r.assignBrowse(document.getElementById('add-file-btn'));
	r.assignDrop(document.getElementById('dropzone'));
 
	// if resumable is not supported show warning, aka IE
    if (!r.support) $('#notSupported').removeClass('hide');
	
	/*
	* Button Events
	*/
    $('#start-upload-btn').click(function(e){
		if (r.files.length > 0){
			$('#pause-upload-btn').removeClass('hide');
			r.upload();
        } 
		else {
			$('#nothingToUpload').removeClass('hide');
			setTimeout(function(){$('#nothingToUpload').addClass('hide')}, 3500);
        }
    });
 
    $('#pause-upload-btn').click(function(){
        if (r.files.length>0) {
            if (r.isUploading()) {
				return r.pause();
            }
            return r.upload();
        }
    });
 
	/*
	* Resumable.JS Events
	*/
    r.on('fileAdded', function(file, event){
        //progressBar.fileAdded();
		var $template = 
			$('<li class="list-group-item" id="'+file.uniqueIdentifier+'">' +
				'<div class="filename">'+file.fileName+'</div>' +
				'<div class="filesize">'+filesize(file.size)+'</div>' +
				'<div class="filetype">'+file.file.type+'</div>' +
				'<div class="fileactions"><button class="btn btn-danger btn-sm rm"><span class="glyphicon glyphicon-trash"></span></button></div>' +
				'<div class="progress fileprogressbar"><div class="progress-bar" role="progressbar" style="width: 0%;"></div>' +
				'</div></li>');
		// remove from list
		$template.find('.btn.rm').click(function(e){
			var parent = $(this).parent().parent(),
				identifier = parent.attr('id'),
				file = r.getFromUniqueIdentifier(identifier);
			r.removeFile(file);
			parent.remove();
		});		
		$('#file-list').append($template);
    });
	
    r.on('fileSuccess', function(file, message){
        $('li#' + file.uniqueIdentifier).find('.progress-bar').addClass('progress-bar-success');
    });
	
	r.on('fileError', function(file, message){
		$('li#' + file.uniqueIdentifier).find('.progress-bar').addClass('progress-bar-danger').css('width', '100%').css('color','white').html(message);
	});
	
	r.on('fileProgress', function (file) {
        var progress = Math.floor(file.progress() * 100);
        $('li#' + file.uniqueIdentifier).find('.progress-bar').css('width', progress + '%').html('&nbsp;' + progress + '%');
    });
	
    r.on('progress', function(){
        $('#pause-upload-btn').find('.glyphicon').removeClass('glyphicon-play').addClass('glyphicon-pause');
		$('#pause-upload-btn').find('.text').text('Pausieren');
    });
 
    r.on('pause', function(){
        $('#pause-upload-btn').find('.glyphicon').removeClass('glyphicon-pause').addClass('glyphicon-play');
		$('#pause-upload-btn').find('.text').text('Fortsetzen');
    });
});

/*
 2016 
 http://cdn.filesizejs.com/filesize.min.js
 @version 3.2.1
 */
"use strict";!function(a){function b(a){var b=arguments.length<=1||void 0===arguments[1]?{}:arguments[1],e=[],f=0,g=void 0,h=void 0,i=void 0,j=void 0,k=void 0,l=void 0,m=void 0,n=void 0,o=void 0,p=void 0,q=void 0;if(isNaN(a))throw new Error("Invalid arguments");return i=b.bits===!0,o=b.unix===!0,h=b.base||2,n=void 0!==b.round?b.round:o?1:2,p=void 0!==b.spacer?b.spacer:o?"":" ",q=b.symbols||b.suffixes||{},m=b.output||"string",g=void 0!==b.exponent?b.exponent:-1,l=Number(a),k=0>l,j=h>2?1e3:1024,k&&(l=-l),0===l?(e[0]=0,e[1]=o?"":i?"b":"B"):((-1===g||isNaN(g))&&(g=Math.floor(Math.log(l)/Math.log(j)),0>g&&(g=0)),g>8&&(g=8),f=2===h?l/Math.pow(2,10*g):l/Math.pow(1e3,g),i&&(f=8*f,f>j&&8>g&&(f/=j,g++)),e[0]=Number(f.toFixed(g>0?n:0)),e[1]=10===h&&1===g?i?"kb":"kB":d[i?"bits":"bytes"][g],o&&(e[1]=e[1].charAt(0),c.test(e[1])&&(e[0]=Math.floor(e[0]),e[1]=""))),k&&(e[0]=-e[0]),e[1]=q[e[1]]||e[1],"array"===m?e:"exponent"===m?g:"object"===m?{value:e[0],suffix:e[1],symbol:e[1]}:e.join(p)}var c=/^(b|B)$/,d={bits:["b","Kb","Mb","Gb","Tb","Pb","Eb","Zb","Yb"],bytes:["B","KB","MB","GB","TB","PB","EB","ZB","YB"]};"undefined"!=typeof exports?module.exports=b:"function"==typeof define&&define.amd?define(function(){return b}):a.filesize=b}("undefined"!=typeof window?window:global);
//# sourceMappingURL=filesize.min.js.map
