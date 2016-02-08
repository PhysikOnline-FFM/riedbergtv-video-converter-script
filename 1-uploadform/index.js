$( document ).ready(function() {
	
	var allowed_filetarpathes = {},
	upload_url = 'upload.php',
	showAlert = function(title, text, cssClass='alert-info', parent){
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
				$inp_filename = $li.find('.filename input');
				
			return {'filetarpath':$inp_target.val(), 'filename':$inp_filename.val()};
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
			if (errors.inputs === 0 && errors.mime === 0)
				r.upload();
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
			$('<li class="list-group-item" id="'+file.uniqueIdentifier+'">' +
				'<div class="filetarpath"><select name="filetarpath" class="form-control"><option value="">Auswählen!</option></select></div>' +
				'<div class="filename"><input type="text" name="filename" class="form-control" value="'
					+(file.fileName.substr(0, file.fileName.lastIndexOf('.')) || file.fileName).replace(/[^-0-9A-Z_\.]+/i, '_')
					+'" maxlength="255" /></div>' +
				'<div class="filetype">'+file.file.type+'</div>' +
				'<div class="filesize">'+formatSize(file.size)+'</div>' +
				'<div class="fileactions"><button class="btn btn-danger btn-sm rm"><span class="glyphicon glyphicon-remove"></span></button></div>' +
				'<div class="progress fileprogressbar"><div class="progress-bar" role="progressbar" style="width: 0%;"></div>' +
				'</div></li>');
		// remove from list button
		$template.find('.btn.rm').click(function(e){
			var parent = $(this).parent().parent(),
				identifier = parent.attr('id'),
				file = r.getFromUniqueIdentifier(identifier);
			r.removeFile(file);
			parent.remove();
		});	
		// add Targetpath options
		var $select = $template.find('.filetarpath select');
		$.each(allowed_filetarpathes, function(k,v){
			$select.append('<option value="'+v+'">'+k+'</option>');
		});
		$('#file-list').append($template);
    });
	
    r.on('fileSuccess', function(file, message){
        $('li#' + file.uniqueIdentifier).find('.progress-bar').addClass('progress-bar-success');
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
