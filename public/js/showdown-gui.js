jQuery( document ).ready(function () {
    // Globals
    var converter;
    var convertTextTimer;

    var rthd_markdown = {
        init: function () {
            //rthd_markdown.initPreviewLink();
            rthd_markdown.initGloble();
            rthd_markdown.addFollowup();
            rthd_markdown.editFollowup();
            rthd_markdown.editTicketContnent();
            rthd_markdown.supportPage();
        },
        initPreviewLink: function(){
            jQuery('.rthd-markdown-preview').click(function(e){
                var parent_selector = jQuery(this).data('parent');
                if ( jQuery( parent_selector).find('.markdown_preview_container').is(':visible') ) {
                    jQuery( parent_selector).find('.rthd-followup-content-container').show();
                    jQuery( parent_selector).find('.markdown_preview_container').hide();
                    jQuery(this).removeClass( 'active' );
                }else{
                    if ( ! jQuery( parent_selector).find('.markdown_preview_container').html() ){
                        alert( 'Nothing to preview' );
                        return;
                    }
                    jQuery( parent_selector).find('.rthd-followup-content-container').hide();
                    jQuery( parent_selector).find('.markdown_preview_container').show();
                    jQuery(this).addClass( 'active' );
                }
            });
        },
        initGloble:function(){
            converter = new showdown.Converter({literalMidWordUnderscores: true, smoothLivePreview: true, ghCodeBlocks: true, simplifiedAutoLink: true, tables: true, extensions: ['table', 'github', 'prettify']});
        },
        addFollowup: function(){
            jQuery('#rthd-new-followup-preview').click(function(e){
                var parent_selector = '#new-followup-form';
                if ( jQuery( parent_selector).find('.markdown_preview_container').is(':visible') ) {
                    jQuery( parent_selector).find('.rthd-followup-content-container').show();
                    jQuery( parent_selector).find('.markdown_preview_container').hide();
                    jQuery(this).removeClass( 'active' );
                }else{
                    var inputPane = jQuery("#followupcontent");
                    var peviewPane = jQuery("#followupcontent_html");
                    rthd_markdown.onInput( inputPane, peviewPane );

                    if ( ! jQuery( parent_selector).find('.markdown_preview_container').html() ){
                        alert( 'Nothing to preview' );
                        return;
                    }

                    jQuery( parent_selector).find('.rthd-followup-content-container').hide();
                    jQuery( parent_selector).find('.markdown_preview_container').show();
                    jQuery(this).addClass( 'active' );
                }
            });
        },
        editFollowup: function(){
            jQuery('#rthd-edit-followup-preview').click(function(e){
                var parent_selector = '#dialog-form';
                if ( jQuery( parent_selector).find('.markdown_preview_container').is(':visible') ) {
                    jQuery( parent_selector).find('.rthd-followup-content-container').show();
                    jQuery( parent_selector).find('.markdown_preview_container').hide();
                    jQuery(this).removeClass( 'active' );
                }else{
                    var inputPane = jQuery( '#dialog-form').find( '#editedfollowupcontent' );
                    var peviewPane = jQuery( '#dialog-form').find( '#editedfollowupcontent_html' );
                    rthd_markdown.onInput( inputPane, peviewPane );

                    if ( ! jQuery( parent_selector).find('.markdown_preview_container').html() ){
                        alert( 'Nothing to preview' );
                        return;
                    }

                    jQuery( parent_selector).find('.rthd-followup-content-container').hide();
                    jQuery( parent_selector).find('.markdown_preview_container').show();
                    jQuery(this).addClass( 'active' );
                }
            });
        },
        editTicketContnent: function(){
            jQuery('#rthd-edit-ticket-content-preview').click(function(e){
                var parent_selector = '#edit-ticket-data';
                if ( jQuery( parent_selector).find('.markdown_preview_container').is(':visible') ) {
                    jQuery( parent_selector).find('.rthd-followup-content-container').show();
                    jQuery( parent_selector).find('.markdown_preview_container').hide();
                    jQuery(this).removeClass( 'active' );
                }else{
                    var inputPane = jQuery( '#edit-ticket-data').find('#editedticketcontent');
                    var peviewPane = jQuery( '#edit-ticket-data').find( '#editedticketcontent_html' );
                    rthd_markdown.onInput( inputPane, peviewPane );

                    if ( ! jQuery( parent_selector).find('.markdown_preview_container').html() ){
                        alert( 'Nothing to preview' );
                        return;
                    }

                    jQuery( parent_selector).find('.rthd-followup-content-container').hide();
                    jQuery( parent_selector).find('.markdown_preview_container').show();
                    jQuery(this).addClass( 'active' );
                }
            });
        },
        supportPage: function(){
            jQuery('#rthd-ticket-create-preview').click(function(e){
                var parent_selector = '#rt-hd-support-page';
                if ( jQuery( parent_selector).find('.markdown_preview_container').is(':visible') ) {
                    jQuery( parent_selector).find('.rthd-followup-content-container').show();
                    jQuery( parent_selector).find('.markdown_preview_container').hide();
                    jQuery(this).removeClass( 'active' );
                }else{
                    var inputPane = jQuery( '#rt-hd-support-page').find('#post_description_body');
                    var peviewPane = jQuery( '#rt-hd-support-page').find( '#post_description_html' );
                    var outputPane = jQuery( '#rt-hd-support-page').find( '#post_description_html_text' );
                    rthd_markdown.onInput( inputPane, peviewPane, outputPane );

                    if ( ! jQuery( parent_selector).find('.markdown_preview_container').html() ){
                        alert( 'Nothing to preview' );
                        return;
                    }

                    jQuery( parent_selector).find('.rthd-followup-content-container').hide();
                    jQuery( parent_selector).find('.markdown_preview_container').show();
                    jQuery(this).addClass( 'active' );
                }
            });
        },
        convertText: function( inputPane, peviewPane, outputPane ){
            // get input text
            var text = inputPane.val();

            text = converter.makeHtml(text);

            //previewPane.innerHTML = text;
            peviewPane.html( text );

            if ( outputPane ){
                outputPane.val( text );
            }
        },
        onInput: function( inputPane, peviewPane, outputPane ){
            if (convertTextTimer) {
                window.clearTimeout(convertTextTimer);
                convertTextTimer = undefined;
            }
            rthd_markdown.convertText( inputPane, peviewPane, outputPane );
        }
    }
    rthd_markdown.init();
});
