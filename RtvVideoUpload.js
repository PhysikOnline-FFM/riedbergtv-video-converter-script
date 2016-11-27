$( document ).ready(function() {
	$('[data-toggle="popover"]').popover(); 
	var allowed_filetarpathes = {},
	upload_url = window.location.href.split('#', 1)[0],
	showAlert = function(title, text, cssClass, parent){
        if (cssClass === undefined) cssClass = 'alert';
		if (parent === undefined) parent = '#sharedAlertContainerFiles';
		// Create Alert Box
		var $alert = $('<div class="alert">').addClass(cssClass);
		// Fill with content
		$alert.append($('<strong>').html(title)).append($('<span>').html(' '+text));
		// Show it in parent container
		$(parent).prepend($alert);
		// Automatic removement
		setTimeout(function(){
			$alert.remove();
		}, 6000);
	},
	formatSize = function(size){
        if(size<1024){return size + ' bytes'} 
		else if(size<1024*1024){return (size/1024.0).toFixed(0) + ' KB'}
		else if(size<1024*1024*1024){return (size/1024.0/1024.0).toFixed(1) + ' MB'} 
		else {return (size/1024.0/1024.0/1024.0).toFixed(1) + ' GB'}
    },
	r = new Resumable({
        target: upload_url,			// target url to server script
        testChunks: true,			// Check if chunk has uploaded already before
		chunkSize: 4*1024*1024,
		simultaneousUploads: 2,
		query: function(file){
			var $li = $('li#' + file.uniqueIdentifier),
				$inp_target = $li.find('.filetarpath select'),
				$inp_filename = $li.find('.filename input'),
				$inp_thumbtime = $li.find('.filethumbtime input'),
                $inp_wikititel = $li.find('.filewikititel input');
                $inp_vidertitel = $li.find('.filevideotitel input');
                $inp_videruntertitel = $li.find('.filevideountertitel input');
				
			return {'filetarpath': $inp_target.val(), 
			        'filename': $inp_filename.val(), 
					'filethumbtime': $inp_thumbtime.val(), 
					'filewikititel': $inp_wikititel.val(),
					'filevideotitel': $inp_vidertitel.val(),
					'filevideountertitel': $inp_videruntertitel.val(),
					};
		}, 	//Extra parameters to include in the multipart POST with data. This can be an object or a function. If a function, it will be passed a ResumableFile object
		minFileSize: 500*1024, 	// 500KB+
		minFileSizeErrorCallback:function(file, errorCount) {
			showAlert('Minimalgröße unterschritten', 'Die Datei "'+ (file.fileName||file.name) +'" ist zu klein. Videodateien werden größer als ' + formatSize(r.getOpt('minFileSize')) + ' erwartet.', 'alert-danger');
		},
		fileType: ['mp4'], 		// allowed file extensions
		fileTypeErrorCallback: function(file, errorCount) {
			showAlert('Unerlaubter Dateityp', 'Die Datei "'+ (file.fileName||file.name) +'" hat einen falschen Dateityp. Es können nur ' + r.getOpt('fileType') + '-Dateien hochgeladen werden.', 'alert-danger');
      }
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
			var errors = {'inputs':0, 'mime':0},
				$inputs = $('#file-list input, #file-list select');
			// Fehlende Angaben
			$inputs.each(function(){
				$(this).parent().removeClass('has-error');
				if (!$(this).val()){
					$(this).parent().addClass('has-error');
					errors.inputs++;
				}
			});
			// Falscher MimeType
			$('ul#file-list li .filetype').removeClass('text-danger');
			$.each(r.files, function(k, file){
				if (file.file.type !== 'video/mp4'){
					$('li#' + file.uniqueIdentifier).find('.filetype').addClass('text-danger');
					errors.mime++;
				}
			});
			if (errors.inputs === 0 && errors.mime === 0) {
        
				r.upload();
      }
			else {
				if (errors.inputs !== 0)
					showAlert('Fehlende Angaben', 'Vor dem Upload müssen alle Felder ausgefüllt/-wählt werden.', 'alert-danger');
				if (errors.mime !== 0)
					showAlert('Falscher Dateityp', 'Er dürfen nur MP4-Videos hochgeladen werden.', 'alert-danger');
			} 
		}
		else
			showAlert('Wo nichts ist, kann auch nicht\'s werden.', 'Bitte wähle erst eine Datei aus, die hochgeladen werden soll.', 'alert-warning');
    });
    $('#pause-upload-btn').click(function(){
        if (r.files.length>0) {
            if (r.isUploading())
				return r.pause();
			else
				return r.upload();
        }
    });
	
	/*
	* Drag & Drop Events
	*/
	$(document).on('drop dragover', function (e){
		e.preventDefault();
	});
	$(document).on('dragover', function (e){
		$('#dropzone').addClass('highlightDropzone');
	})
	$('#dropzone').on('dragover', function (e){
		$('#dropzone').addClass('targetDropzone');
	});
	$(document, '#dropzone').on('dragleave drop', function (e){
		$('#dropzone').removeClass('highlightDropzone').removeClass('targetDropzone');
	})
	
	/*
	* Resumable.JS Events
	*/
    r.on('fileAdded', function(file, event){
        //progressBar.fileAdded();
		var $template = 
			$('<li class="list-group-item" id="'+file.uniqueIdentifier+'">'
                +'<div class="row">'
                    +'<div class="col-md-3 col-lg-2 pull-right text-right fileactions">'
                        +'<button class="btn btn-danger btn-sm rm"><span class="glyphicon glyphicon-remove"></span></button>'
                    +'</div>'
					+'<div class="col-md-4 col-lg-3">'
                        +'<label>Um was gehts?</label>'
                    +'</div>'
					+'<div class="col-md-5 col-lg-7 filetarpath"><select name="filetarpath" class="form-control">'
                        +'<option value="">Auswählen!</option></select>'
                    +'</div>'
				+'</div>'
                +'<div class="row">'
					+'<div class="col-md-4 col-lg-3">'
                        +'<label>Wiki-Seitentitel</label>'
                        +'<a href="#" class="upload-info-popup" data-toggle="popover" data-trigger="focus" data-content="Dies ist der Titel, der auf der Videoseite angezeigt wird.">'
                            +'<i class="glyphicon glyphicon-info-sign"></i>'
                        +'</a>'
                    +'</div>'
					+'<div class="col-md-8 col-lg-9 filewikititel">'
                        +'<input type="text" name="filewikititel" class="form-control" value="" maxlength="60" placeholder="z.B. Kurzinterview PhysikOnline"/>'
                    +'</div>'
				+'</div>'
                +'<div class="row">'
					+'<div class="col-md-4 col-lg-3">'
                        +'<label>Videotitel</label>'
                        +'<a href="#" class="upload-info-popup" data-toggle="popover" data-trigger="focus" data-content="Dies ist der Titel, der auf der Hauptseite angezeigt wird.">'
                            +'<i class="glyphicon glyphicon-info-sign"></i>'
                        +'</a>'
                    +'</div>'
					+'<div class="col-md-8 col-lg-9 filevideotitel">'
                        +'<input type="text" name="filevideotitel" class="form-control" value="" maxlength="60" placeholder="z.B. Prof. Dr. Galileo Galilei"/>'
                +'</div>'
				+'</div>' 
                    +'<div class="row">'
					+'<div class="col-md-4 col-lg-3">'
                        +'<label>Videountertitel</label>'
                        +'<a href="#" class="upload-info-popup" data-toggle="popover" data-trigger="focus" data-content="Dieser Titel wird unterhalb des Videotitels auf der Hauptseite angezeigt.">'
                            +'<i class="glyphicon glyphicon-info-sign"></i>'
                        +'</a>'
                    +'</div>'
					+'<div class="col-md-8 col-lg-9 filevideountertitel">'
                        +'<input type="text" name="filevideountertitel" class="form-control" value="" maxlength="60" placeholder="z.B. Sein zwei Planetensystem" />'
                    +'</div>'
				+'</div>' 
				+'<div class="row">'
					+'<div class="col-md-4 col-lg-3">'
                        +'<label>(Zielort-)Dateiname</label>'
                    +'</div>'
					+'<div class="col-md-8 col-lg-9 filename">'
                        +'<input type="text" name="filename" class="form-control" value="'+file.fileName+'" maxlength="80" />'
                    +'</div>'
				+'</div>' 
				+'<div class="row">'
					+'<div class="col-md-4 col-lg-3">'
                        +'<label>Zeitpunkt für Vorschaubild</label>'
                        +'<a href="#" class="upload-info-popup" data-toggle="popover" data-trigger="focus" data-content="Nach dem Hochladen wird ein Bild an dieser Stelle aus dem Video extrahiert und als Thumbnail verwendet.">'
                            +'<i class="glyphicon glyphicon-info-sign"></i>'
                        +'</a>'
                    +'</div>'
					+'<div class="col-md-8 col-lg-9 filethumbtime">'
                        +'<input type="text" name="filethumbtime" class="form-control" value="00:02:00" placeholder="hh:mm:ss" title="Jeweils zweistellig Stunde:Minute:Sekunde (hh:mm:ss)" pattern="[0-9]{2}\:[0-9]{2}\:[0-9]{2}" maxlength="8" />'
                    +'</div>'
				+'</div>' 
				+'<div class="row">'
					+'<div class="col-md-4 col-lg-3">'
                        +'<label>Dateigröße</label>'
                    +'</div>'
					+'<div class="col-md-8 col-lg-9 filesize">'
                        +'<span class="form-control-static">'+formatSize(file.size)+'</span>'
                    +'</div>'
				+'</div>' 
				/*'<div class="row">'+
					'<div class="col-md-4 col-lg-3"><label>Dateityp</label></div>'+
					'<div class="col-md-8 col-lg-9 filetype"><span class="form-control-static">'+file.file.type+'</span></div>'+
				'</div>' +*/
				+'<div class="progress fileprogressbar">'
                    +'<div class="progress-bar" role="progressbar" style="width: 0%;"></div>'
                +'</div>' 
				+'<div class="row">'
					+'<div class="col-md-4 col-lg-3">'
                        +'<label></label>'
                    +'</div>'
					+'<div class="col-md-8 col-lg-9 videopreview"></div>'
				+'</div>'
				+'</li>');
		// remove from list button
		$template.find('.btn.rm').click(function(e){
			var parent = $(this).parents('li.list-group-item'),
				identifier = parent.attr('id'),
				file = r.getFromUniqueIdentifier(identifier);
			r.removeFile(file);
			parent.remove();
		});
		// change event 
		$template.find('.filename input').on('change keyup', function(e){
			var val = this.value;
			this.value = (val.substr(0, val.lastIndexOf('.')) || val).replace(/[^-0-9A-Z_\.]+/i, '_');
		}).change();
		$template.find('.filethumbtime input').on('keyup', function(e){
			// FIX for Chrome, aber noch nicht optimal bzgl Usability
            var new_val = this.value.replace(/[^0-9\:]/g,'');
            if (new_val !== this.value)
                this.value = new_val;
		});		
		// add Targetpath options
		var $select = $template.find('.filetarpath select');
		$.each(allowed_filetarpathes, function(k,v){
			$select.append('<option value="'+v+'">'+k+'</option>');
		});
		$('#file-list').append($template);
        $('[data-toggle="popover"]').popover(); 
        
        // add preview
        /* Wegen Fehler "Kein Video mit unterstützem Format" auskommentiert.
        if (window.File && window.FileReader && window.FileList && window.Blob) {
            renderVideoPreview(file.file, $template.find('.videopreview'));
        }
        */
    });
	
    r.on('fileSuccess', function(file, message){
		$('li#' + file.uniqueIdentifier).find('.progress-bar').addClass('progress-bar-success');

		data = getJSONstring(message);
		if(!data) {
			showAlert('Finaler Uploadfehler', 'Beim Hochladen der Datei "'+(file.fileName||file.name)+'" ist die Rückgabe kein JSON: <pre>'+message+'</pre>', 'alert-danger');
		}
	
		// show HTML success message
		console.log("Success uploading file!", file, message);
		container = $("#" + file.uniqueIdentifier);
		wikilink = '<a href="'+data.wikipage_link+'">'+data.wikipage_title+'</a>';
		container.prepend('<div class="row alert alert-success">'
			+ '<div class="col-md-4 col-lg-3"><a href="'+data.thumbnail_webpath+'" title="Pfad zum Vorschaubild"><img src="'+data.thumbnail_webpath+'" /><br><small>Vorschaubild Pfad</small></a></div>'
			+ '<div class="col-md-8 col-lg-9 filesize"><h3>Videoseite '+wikilink+'</h3>'
			+ '<p>'+data.username+', dein hochgeladenes Video wird derzeit konvertiert und du erhältst eine E-Mail an die Addresse <em>'+data.emailaddr+'</em> sobald es fertig konvertiert ist. '
            +'<br /><strong>In der Zwischenzeit kannst du bereits die Videoseite <a href="'+data.wikipage_editlink+'" title="Wikiseite bearbeiten">'+data.wikipage_title+'" ergänzen und bearbeiten</a>.</strong>'
			+ '</div>'
			+ '</div>');
	});
	
	r.on('fileError', function(file, message){
		$('li#' + file.uniqueIdentifier).find('.progress-bar').addClass('progress-bar-danger').css('width', '100%').css('color','white').html(message);
		showAlert('Uploadfehler', 'Beim Hochladen von Datei "'+(file.fileName||file.name)+'" ist ein Fehler aufgetreten: <pre>'+message+'</pre>', 'alert-danger');
	});
	
	r.on('fileProgress', function (file) {
        var progress = Math.floor(file.progress() * 100);
        $('li#' + file.uniqueIdentifier).find('.progress-bar').css('width', progress + '%').html('&nbsp;' + progress + '%');
    });
	
    r.on('progress', function(){
        $('#pause-upload-btn').find('.glyphicon').removeClass('glyphicon-play').addClass('glyphicon-pause');
		$('#pause-upload-btn').find('.text').text('Pausieren');
		$('#pause-upload-btn').removeClass('hide');
		$('#file-list input').prop('readonly', true);
		$('#file-list select').prop('disabled', true);
    });
 
    r.on('pause', function(){
        $('#pause-upload-btn').find('.glyphicon').removeClass('glyphicon-pause').addClass('glyphicon-play');
		$('#pause-upload-btn').find('.text').text('Fortsetzen');
		$('#file-list input').prop('readonly', false);
		$('#file-list select').prop('disabled', false);
    });
	
	// Request allowed_filetarpathes
	$.ajax({url: upload_url + '?allowed_filetarpathes',}).done(function(data) {
		//if ( console && console.log ) {console.log(data);}
		allowed_filetarpathes = data;
	});
});

function getJSONstring(str) {
    try {
        return JSON.parse(str);
    } catch (e) {
        return false;
    }
}

//this function is called when the input loads a video
function renderVideoPreview(file, container){
    var reader = new FileReader();
    reader.onload = function(event){
        the_url = event.target.result
        $(container).html("<video width='400' controls><source src='"+the_url+"' type='video/mp4'></video>");
    }
    //when the file is read it triggers the onload event above.
    reader.readAsDataURL(file);
}