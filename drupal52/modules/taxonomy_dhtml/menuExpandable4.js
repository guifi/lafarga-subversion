/*
 * menuExpandable4.js - implements an expandable menu based on a HTML list
 * rewritten for Drupal 5 using jQuery
 * Author: subspaceeddy / subspaceeddy@yahoo.co.uk
 */

function initializeMenu(modpath) {
var iefix;

	$(".menubar").css("background-image", "url(/" + modpath + "/plus.gif)");
	$(".submenu").click(
		function() {
			// stop i.e. from causing problems with the function below...
			iefix = true;
			// pass on the click
			return true;
		}
	);
	$(".menubar").click(
					
		function() {
			if (iefix) {
				// pass on the click. the user is using IE and this function should not be called right now.
				return true;
			}
			// get the id of the clicked object.
			// append this to 'menu'
			// find that target
			// and toggle it...
			// testing the new status
			// and setting the + to - and vice versa
			if ($(document).find('#menu' + $(this).id()).toggle().css("display") == "block") {
				$(this).css("background-image", "url(/" + modpath + "/minus.gif)");
			} else {
				$(this).css("background-image", "url(/" + modpath + "/plus.gif)");
			}
			return false;		// don't pass on the click...
		});
	return this;
}

