/**
 * Created by spock on 3/4/15.
 */

jQuery( document ).ready( function ( $ ) {
	var uploadedfiles= [];
	//$accesscode = jQuery('#rthd_support_nonce' ).val();
	var uploader = new plupload.Uploader({
		 // General settings
		 runtimes : 'html5,flash,silverlight,html4',
		 browse_button : 'attachemntlist', // you can pass in id...
		 url : ajaxurl,
		 multipart : true,
		 multipart_params : {'action': 'rthd_upload_attachment' },
		 container: document.getElementById('attachment-container'), // ... or DOM Element itself

		 // Resize images on client-side if we can
		 //resize : { width : 320, height : 240, quality : 90 },

		 filters : {
		     max_file_size : '10mb'

		     // Specify what files to browse for
		     //mime_types: [
		     //    {title : "Image files", extensions : "jpg,gif,png"},
		     //    {title : "Zip files", extensions : "zip"}
		     //]
		 },

		 flash_swf_url : 'Moxie.swf',
		 silverlight_xap_url : 'Moxie.xap',

		 // PreInit events, bound before the internal events

		 init: {
		     PostInit: function() {
		         document.getElementById('support-filelist').innerHTML = '';

		         document.getElementById('sumit-support-form').onclick = function(e) {
			         e.preventDefault();
		             uploader.start();
		             return false;
		         };
		     },

		     FilesAdded: function(up, files) {
		         plupload.each(files, function(file) {
		             document.getElementById('support-filelist').innerHTML += '<div id="' + file.id + '"><a href="#" class="followup-attach-remove"> x </a> ' + file.name + ' (' + plupload.formatSize(file.size) + ') <b></b></div>';
		         });
		     },

		     FilesRemoved: function(up, files) {
		         plupload.each(files, function(file) {
		             jQuery('#'+file.id ).remove();
		         });
		     },

		     UploadProgress: function(up, file) {
		         document.getElementById(file.id).getElementsByTagName('b')[0].innerHTML = '<span>' + file.percent + "%</span>";
		     },

		     Error: function(up, err) {
		         document.getElementById('console').innerHTML += "\nError #" + err.code + ": " + err.message;
		     },

		     UploadComplete: function(){
		         jQuery('#support-form-filelist').html('');
			     jQuery('#rthd_support_attach_ids' ).val(uploadedfiles);
			     jQuery('.rthd_support_from' ).submit();
		     },

		     FileUploaded: function(up, file, info) {
		         // Called when file has finished uploading
		         var response = jQuery.parseJSON(info.response);
		         if ( response.status ){
		             uploadedfiles = uploadedfiles.concat(response.attach_ids);
		         }
			     console.log(response);
		     }
		 }
		});
	uploader.init();

	jQuery(document).on('click','.followup-attach-remove', function( e ){
		e.preventDefault();
		uploader.removeFile(jQuery(this ).parent().attr("id"));
	});
});
