/**
 * Created by spock on 21/4/15.
 */
jQuery(document).ready(function() {

    var wizard;
    var rthdSetup = {
        init: function () {
            rthdSetup.setup_wizard();
        },
        setup_wizard: function(){
            wizard = jQuery( "#wizard" ).steps( {
                headerTag: "h1",
                bodyTag: "fieldset",
                transitionEffect: "slideLeft",
                forceMoveForward: true,
                //enableAllSteps: true,
                onStepChanging: function (event, currentIndex, newIndex)
                {
                    //alert("moving to "+newIndex+" from "+ currentIndex);
                    return true;
                },
                onStepChanged: function (event, currentIndex, priorIndex)
                {
                    //alert("on step changed moved to "+currentIndex+" from "+ priorIndex);
                    return true;
                },
                onFinishing: function (event, currentIndex)
                {
                    //alert("on finishing changed moved to "+currentIndex);
                    return true;
                },
                onFinished: function (event, currentIndex)
                {
                    //alert("Submitted!");
                }
            });
        }
    }
    rthdSetup.init();
});
