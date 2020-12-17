/* 	Cool Javascript Find on this Page - Fixed Position Edition
	(or Version 6.0)

	Written by Jeff Baker on May 20, 2016.
	Copyright 2016 by Jeff Baker -
	Version 6.0 created 5/20/2016
	Version 6.1d updated 8/5/2019
	http://www.seabreezecomputers.com/tips/find6.htm
	Paste the following javascript call in your HTML web page where
	you want a button called "Find" or for newer browsers
	a button with a svg drawing of a magnifying glass:

	<script type="text/javascript" id="cool_find_script" src="find6.js">
	</script>

	NOTE: Or if "lock_button" below is set to 1 then the find button will be
	locked in a fixed position at the bottom right corner of the browser window.

*/

// Create find_settings object
var coolfind = {

/* EDIT THE FOLLOWING VARIABLES */
enable_site_search: 0, // 0 = Don't enable; 1 = Enable site search along with page search
lock_button: 0, // 0 = Don't lock button at bottom of screen; 1 = Lock button in fixed position
find_root_node: null, // Leave as null to search entire doc or put id of div to search (ex: 'content').
test_mode : false,

/* DO NOT EDIT BELOW THIS LINE */
find_button_html: '', // Will be "Find" or svg magnifying glass
highlights: [], // Highlights array to hold each new span element
find_pointer: -1, // Which find are we currently highlighting
find_text: '', // Global variable of searched for text
found_highlight_rule: 0, // whether there is a highlight css rule
found_selected_rule: 0, // whether there is a selected css rule

};


coolfind.create_find_div = function()
{
	// Create the DIV
	var find_div = document.createElement("div");
	var el;
	var find_script = document.getElementById('cool_find_script');
	var find_html = "";
	var find_div_style = "display: inline-block; vertical-align: middle; z-index:200;";
	var button_style = "display: inline-block;  min-height: 1.15em; min-width: 1.5em; max-width: 3em; vertical-align: middle; text-align: center; font-size: 1em;"+ // Version 6.0b - Added font-size: 1em; for "button" element to display properly
		"border: 1px solid black; background: lightgray; cursor: pointer; padding: 1px; margin: 4px; -webkit-user-select:none; -ms-user-select: none;";
	var menu_style = "background-color: #e5e5e5; display: none;";
	var input_style = "display: inline; max-width: 55%;"; // Version 6.0b - changed width: 55% to max-width: 55%
	if (coolfind.lock_button) menu_style += "float: left;";
	coolfind.addCss(".cool_find_btn {"+button_style+"}"); // Comment out this line if you are using your own css for the buttons
	coolfind.addCss(".cool_find_menu {"+menu_style+"}"); // Comment out this line if you are using your own css for the find menu
	coolfind.addCss(".cool_find_input {"+input_style+"}"); // Comment out this line if you are using your own css for the input search box

	// If browser does not support svg
	if (typeof SVGRect == "undefined")
		coolfind.find_button_html = "Find";
	else
		coolfind.find_button_html = '<svg width="1.15em" height="1.15em" viewbox="0 0 30 30">'+
			'<circle cx="18" cy="12" r="8" stroke="black" stroke-width="2" fill="#fff" fill-opacity="0.4" />'+
			'<line x1="13" y1="17" x2="0" y2="30" stroke="black" stroke-width="2" />'+
			'<line x1="10" y1="20" x2="0" y2="30" stroke="black" stroke-width="4" />'+
			'</svg>';


	find_div.id = "cool_find_div";
	find_div.style.cssText = find_div_style;

	find_html += "<button class='cool_find_btn' id='cool_find_btn'"+
		" title='Find on this page' onclick='coolfind.find_menu(this)'>"+
		coolfind.find_button_html+"</button> ";

	if (coolfind.lock_button)
	{
		find_div.style.position = "fixed";
		find_div.style.bottom = "3em";
		find_div.style.right = "1em";
	}
	find_script.parentNode.insertBefore(find_div, find_script.nextSibling);

	find_html += "<span class='cool_find_menu' id='cool_find_menu'>" +
		'<form onsubmit="return false;" style="display: inline">' +
		'<input type="search" class="cool_find_input" id="cool_find_text"' +
		' onchange="coolfind.resettext();" placeholder="Enter text to find">'+
		'<span id="cool_find_msg"> </span>';
	if (coolfind.enable_site_search) { // Version 5.4
		find_html += '<label><input type="radio" name="search_type" value="page" checked>Page</label>'+
		'<label><input type="radio" name="search_type" value="site" id="find_fixed_site_search">Site</label>';
	}

	find_html += '</form>';

	find_html += "<button class='cool_find_btn'"+
		//" style='"+button_style+"'"+
		" title='Find Previous' onclick='coolfind.findprev();'>&#9650;</button>"+
		"<button class='cool_find_btn' id='cool_find_next'"+ // Version 6.0b - Added id='cool_find_next' for accessibility
		//" style='"+button_style+"'"+
		" title='Find Next' onclick='coolfind.findit();'>&#9660;</button> ";

	find_html += "</span>";
	find_div.innerHTML = find_html;

	// Check to see if css rules exist for hightlight and find_selected.
	// var sheets = document.styleSheets;
	// for (var i=0; i < sheets.length; i++)
	// {
	// 	// IE <= 8 uses rules; FF & Chrome and IE 9+ users cssRules
	// 	try { // Version 5.4c - Fix Firefox "InvalidAccessError: A parameter or an operation is not supported by the underlying object" bug
	// 		var rules = (sheets[i].rules) ? sheets[i].rules : sheets[i].cssRules;
	// 		if (rules != null)
	// 		for (var j=0; j < rules.length; j++)
	// 		{
	// 			if (rules[j].selectorText == '.highlight')
	// 				found_highlight_rule = 1;
	// 			else if (rules[j].selectorText == '.find_selected')
	// 				found_selected_rule = 1;
	// 		}
	// 	}
	// 	catch(error) {
	// 		console.error("Caught Firefox CSS loading error: "+error);
	// 	}
	// }
}


coolfind.find_menu =  function(that)
{
	var textbox = document.getElementById('cool_find_text');
	if (that.nextElementSibling.style.display != "inline-block")
	{
		that.nextElementSibling.style.display = 'inline-block';
		that.innerHTML = "X";
		that.title = "Close";
		// Make document look for enter key and esc key
		if (document.addEventListener) // Chrome, Safari, FF, IE 9+
			document.addEventListener('keydown', coolfind.checkkey, false);
		else // IE < 9
			document.attachEvent('onkeydown', coolfind.checkkey);
		// Put cursor focus in the text box
		textbox.focus();
		textbox.select(); // ver 5.1 - 10/17/2014 - Select the text to search for
		textbox.setSelectionRange(0, 9999); // ver. 5.3 - 5/15/2015 - iOS woould not select without this
	}
	else
	{
		that.nextElementSibling.style.display = 'none';
		that.innerHTML = coolfind.find_button_html;
		that.title = "Find on this page";
		coolfind.unhighlight(); // Remove highlights of any previous finds - ver 5.1 - 10/17/2014
		// Make document no longer look for enter key and esc key
		if (document.removeEventListener) // Chrome, Safari, FF, IE 9+
			document.removeEventListener('keydown', coolfind.checkkey, false);
		else // IE < 9
			document.detachEvent('onkeydown', coolfind.checkkey);
	}
}


coolfind.addCss = function(css)
{
	// Example: addCss(".cool_textchanger_btn { display: inline-block; min-width: 2em; max-width: 3em; }");
	var style = document.createElement('style');
	style.type = 'text/css';
	if (style.styleSheet) // IE < 9
		style.styleSheet.cssText = css;
	else
		style.appendChild(document.createTextNode(css));

	document.getElementsByTagName("head")[0].appendChild(style);

}


coolfind.highlight = function(word, node)
{
	if (!node)
		node = document.body;

	//var re = new RegExp(word, "i"); // regular expression of the search term // Ver 6.0c - Not using regular expressions search now

	for (node=node.firstChild; node; node=node.nextSibling)
	{
		//console.log(node.nodeName);
		if (node.nodeType == 3) // text node
		{
			var n = node;
			//console.log(n.nodeValue);
			var match_pos = 0;
			//for (match_pos; match_pos > -1; n=after)
			{
				//match_pos = n.nodeValue.search(re); // Ver 5.3b - Now NOT using regular expression because couldn't search for $ or ^
				match_pos = n.nodeValue.toLowerCase().indexOf(word.toLowerCase()); // Ver 5.3b - Using toLowerCase().indexOf instead

				if (match_pos > -1) // if we found a match
				{
					var before = n.nodeValue.substr(0, match_pos); // split into a part before the match
					var middle = n.nodeValue.substr(match_pos, word.length); // the matched word to preserve case
					//var after = n.splitText(match_pos+word.length);
					var after = document.createTextNode(n.nodeValue.substr(match_pos+word.length)); // and the part after the match
					var highlight_span = document.createElement("span"); // create a span in the middle
			        if (coolfind.found_highlight_rule == 1)
						highlight_span.className = "highlight";
					else
						highlight_span.style.backgroundColor = "yellow";

					highlight_span.appendChild(document.createTextNode(middle)); // insert word as textNode in new span
					n.nodeValue = before; // Turn node data into before
					n.parentNode.insertBefore(after, n.nextSibling); // insert after
		            n.parentNode.insertBefore(highlight_span, n.nextSibling); // insert new span
		           	coolfind.highlights.push(highlight_span); // add new span to highlights array
		           	highlight_span.id = "highlight_span"+coolfind.highlights.length;
					node=node.nextSibling; // Advance to next node or we get stuck in a loop because we created a span (child)
				}
			}
		}
		else // if not text node then it must be another element
		{
			// nodeType 1 = element
			if (node.nodeType == 1 && node.nodeName.match(/textarea|input/i) && node.type.match(/textarea|text|number|search|email|url|tel/i) && !coolfind.getStyle(node, "display").match(/none/i))
				coolfind.textarea2pre(node);
			else
			{
			if (node.nodeType == 1 && !coolfind.getStyle(node, "visibility").match(/hidden/i)) // Dont search in hidden elements
			if (node.nodeType == 1 && !coolfind.getStyle(node, "display").match(/none/i)) // Dont search in display:none elements
			coolfind.highlight(word, node);
			}
		}
	}


} // end function highlight(word, node)


coolfind.unhighlight = function()
{
	for (var i = 0; i < coolfind.highlights.length; i++)
	{

		var the_text_node = coolfind.highlights[i].firstChild; // firstChild is the textnode in the highlighted span

		var parent_node = coolfind.highlights[i].parentNode; // the parent element of the highlighted span

		// First replace each span with its text node nodeValue
		if (coolfind.highlights[i].parentNode)
		{
			coolfind.highlights[i].parentNode.replaceChild(the_text_node, coolfind.highlights[i]);
			if (i == coolfind.find_pointer) coolfind.selectElementContents(the_text_node); // ver 5.1 - 10/17/2014 - select current find
			parent_node.normalize(); // The normalize() method removes empty Text nodes, and joins adjacent Text nodes in an element
			coolfind.normalize(parent_node); // Ver 5.2 - 3/10/2015 - normalize() is incorrect in IE. It will combine text nodes but may leave empty text nodes. So added normalize(node) function below
		}
	}
	// Now reset highlights array
	coolfind.highlights = [];
	coolfind.find_pointer = -1; // ver 5.1 - 10/17/2014
} // end function unhighlight()


coolfind.normalize = function(node) {
//http://stackoverflow.com/questions/22337498/why-does-ie11-handle-node-normalize-incorrectly-for-the-minus-symbol
  if (!node) { return; }
  if (node.nodeType == 3) {
    while (node.nextSibling && node.nextSibling.nodeType == 3) {
      node.nodeValue += node.nextSibling.nodeValue;
      node.parentNode.removeChild(node.nextSibling);
    }
  } else {
    coolfind.normalize(node.firstChild);
  }
  coolfind.normalize(node.nextSibling);
}


coolfind.findit = function ()
{
	var cool_find_msg = document.getElementById('cool_find_msg');
	var findwindow = document.getElementById('cool_find_menu');

	// put the value of the textbox in string
	var string = document.getElementById('cool_find_text').value; // xxxxxxxxxxxxxxxxx


	// Version 5.4 - Site search
	if (coolfind.enable_site_search && document.getElementById("find_fixed_site_search").checked) {
		var website = window.location.hostname; // Or replace with your website. Ex: example.com
		var url = "https://www.google.com/search?q=site%3A"+website+"+"+string;
		window.open(url, "coolfind");
		return;
	}

	// 8-9-2010 Turn DIV to hidden just while searching so doesn't find the text in the window
	findwindow.style.visibility = 'hidden';
	//findwindow.style.display = 'none';

	// if the text has not been changed and we have previous finds
	if (coolfind.find_text.toLowerCase() == document.getElementById('cool_find_text').value.toLowerCase() &&
		coolfind.find_pointer >= 0)
	{
		coolfind.findnext(); // Find the next occurrence
	}
	else
	{
		coolfind.unhighlight(); // Remove highlights of any previous finds

		if (string == '') // if empty string
		{
			cool_find_msg.innerHTML = "";
			findwindow.style.visibility = 'visible';
			return;
		}

		coolfind.find_text = string;

		// Ver 5.0a - 7/18/2014. Next four lines because find_root_node won't exist until doc loads
		if (coolfind.find_root_node != null)
			var node = document.getElementById(coolfind.find_root_node);
		else
			var node = null;

		coolfind.highlight(string, node); // highlight all occurrences of search string

		if (coolfind.highlights.length > 0) // if we found occurences
		{
			coolfind.find_pointer = -1;
			coolfind.findnext(); // Find first occurrence
		}
		else
		{
			cool_find_msg.innerHTML = "&nbsp;<b>0 of 0</b>"; // ver 5.1 - 10/17/2014 - changed from "Not Found"
			coolfind.find_pointer = -1;
		}
	}
	findwindow.style.visibility = 'visible';
	//findwindow.style.display = 'block';

}  // end function findit()


coolfind.findnext = function()
{
	var current_find;

	if (coolfind.find_pointer != -1) // if not first find
	{
		current_find = coolfind.highlights[coolfind.find_pointer];

		// Turn current find back to yellow
		if (coolfind.found_highlight_rule == 1)
			current_find.className = "highlight";
		else
			current_find.style.backgroundColor = "yellow";
	}

	coolfind.find_pointer++;

	if (coolfind.find_pointer >= coolfind.highlights.length) // if we reached the end
		coolfind.find_pointer = 0; // go back to first find

	var display_find = coolfind.find_pointer+1;

	cool_find_msg.innerHTML = display_find+" of "+coolfind.highlights.length;

	current_find = coolfind.highlights[coolfind.find_pointer];

	// Turn selected find orange or add .find_selected css class to it
	if (coolfind.found_selected_rule == 1)
			current_find.className = "find_selected";
		else
			current_find.style.backgroundColor = "orange";

	//coolfind.highlights[find_pointer].scrollIntoView(); // Scroll to selected element
	coolfind.scrollToPosition(coolfind.highlights[coolfind.find_pointer]);

} // end coolfind.coolfind.findnext()



// This function is to find backwards by pressing the Prev button
coolfind.findprev = function()
{
	var cool_find_msg = document.getElementById('cool_find_msg');
	var current_find;

	if (coolfind.highlights.length < 1) return;

	if (coolfind.find_pointer != -1) // if not first find
	{
		current_find = coolfind.highlights[coolfind.find_pointer];

		// Turn current find back to yellow
		if (coolfind.found_highlight_rule == 1)
			current_find.className = "highlight";
		else
			current_find.style.backgroundColor = "yellow";
	}

	coolfind.find_pointer--;

	if (coolfind.find_pointer < 0) // if we reached the beginning
			coolfind.find_pointer = coolfind.highlights.length-1; // go back to last find

	var display_find = coolfind.find_pointer+1;

	cool_find_msg.innerHTML = display_find+" of "+coolfind.highlights.length;

	current_find = coolfind.highlights[coolfind.find_pointer];

	// Turn selected find orange or add .find_selected css class to it
	if (coolfind.found_selected_rule == 1)
			current_find.className = "find_selected";
		else
			current_find.style.backgroundColor = "orange";

	//coolfind.highlights[coolfind.find_pointer].scrollIntoView(); // Scroll to selected element
	coolfind.scrollToPosition(coolfind.highlights[coolfind.find_pointer]);

} // end coolfind.coolfind.findprev()


// This function looks for the ENTER key (13)
// while the find window is open, so that if the user
// presses ENTER it will do the find next
coolfind.checkkey = function(e)
{
	var keycode;
	if (window.event)  // if ie
		keycode = window.event.keyCode;
	else // if Firefox or Netscape
		keycode = e.which;

	//cool_find_msg.innerHTML = keycode;

	if (keycode == 13) // if ENTER key
	{
		// ver 5.1 - 10/17/2014 - Blur on search so keyboard closes on iphone and android
		if (window.event && event.srcElement.id.match(/cool_find_text/i)) { event.srcElement.blur(); document.getElementById("cool_find_next").focus(); } // Version 6.0b - Added focus to find_next btn
		else if (e && e.target.id.match(/cool_find_text/i)) { e.target.blur(); document.getElementById("cool_find_next").focus(); } // Version 6.0b - Added focus to find_next btn
		if (document.activeElement.className != "cool_find_btn") // Version 6.0b - For accessibility, let find_next and find_prev buttons work with keyboard
			coolfind.findit(); // call findit() function (like pressing NEXT)
	}
	else if (keycode == 27) // ESC key // Ver 5.1 - 10/17/2014
	{
		coolfind.find_menu(document.getElementById('cool_find_btn')); // Close find window on escape key pressed
	}
} // end function coolfind.checkkey()



// This function resets the txt selection pointer to the
// beginning of the body so that we can search from the
// beginning for the new search string when somebody
// enters new text in the find box
coolfind.resettext = function()
{
	if (coolfind.find_text.toLowerCase() != document.getElementById('cool_find_text').value.toLowerCase())
		coolfind.unhighlight(); // Remove highlights of any previous finds

} // end function resettext()


coolfind.isOnScreen = function(el) // Version 5.4d
{
	/* This checks to see if an element is within the current user viewport or not */
	var scrollLeft = document.body.scrollLeft || document.documentElement.scrollLeft;
	var scrollTop = document.body.scrollTop || document.documentElement.scrollTop;
	var screenHeight = window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight; // Version 1.2.0
	var screenWidth = window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth; // Version 1.2.0
	var scrollBottom = (window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight) + scrollTop;
	var scrollRight = (window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth) + scrollLeft;
	var onScreen = false;

	/* New way: el.getBoundingClientRect always returns
		left, top, right, bottom of
		an element relative to the current screen viewport */
	var rect = el.getBoundingClientRect();
	if (rect.bottom >= 0 && rect.right >= 0 &&
		rect.top <= screenHeight && rect.left <= screenWidth) // Version 1.2.0 - Changed from scrollBottom and scrollRight
		return true;
	else {
		// Verison 1.0.2 - Calculate how many pixels it is offscreen
		var distance = Math.min(Math.abs(rect.bottom), Math.abs(rect.right), Math.abs(rect.top - screenHeight), Math.abs(rect.left - screenWidth));

		return -Math.abs(distance); // Version 1.0.2 - Return distance as a negative. Used to return false if off screen
	}
}


coolfind.scrollToPosition = function(field)
{
   // This function scrolls to the DIV called 'edited'
   // It is called with onload.  'edited' only exists if
   // they just edited a comment or the last comment
   // if they just sent a comment
	var scrollLeft = document.body.scrollLeft || document.documentElement.scrollLeft;
	var scrollTop = document.body.scrollTop || document.documentElement.scrollTop;
	var scrollBottom = (window.innerHeight || document.documentElement.clientHeight || document.body.clientHeight) + scrollTop;
	var scrollRight = (window.innerWidth || document.documentElement.clientWidth || document.body.clientWidth) + scrollLeft;


   if (field)
   {
		if (coolfind.isOnScreen(field) != true) // Version 5.4d
		{
			//window.scrollTo(elemPosX ,elemPosY);
			var isSmoothScrollSupported = 'scrollBehavior' in document.documentElement.style;
			if(isSmoothScrollSupported) {
	   			field.scrollIntoView({
			     behavior: "smooth",
			     block: "center"
			   });
			} else {
			   //fallback to prevent browser crashing
			   field.scrollIntoView(false);
			}
		}
		//window.scrollTo((field.getBoundingClientRect().left + scrollLeft) - ((scrollRight-scrollLeft)/2), (field.getBoundingClientRect().top + scrollTop) - ((scrollBottom-scrollTop)/2));
	}
}  // end function scrollToPosition()


/* It is not possible to get certain styles set in css such as display using
the normal javascript.  So we have to use this function taken from:
http://www.quirksmode.org/dom/getstyles.html */
coolfind.getStyle = function(el,styleProp)
{
	// if el is a string of the id or the actual object of the element
	var x = (document.getElementById(el)) ? document.getElementById(el) : el;
	if (x.currentStyle) // IE
		var y = x.currentStyle[styleProp];
	else if (window.getComputedStyle)  // FF
		var y = document.defaultView.getComputedStyle(x,null).getPropertyValue(styleProp);
	return y;
}


coolfind.textarea2pre = function(el)
{
	// el is the textarea element

	// If a pre has already been created for this textarea element then use it
	if (el.nextSibling && el.nextSibling.id && el.nextSibling.id.match(/pre_/i))
		var pre = el.nextsibling;
	else
		var pre = document.createElement("pre");

	var the_text = el.value; // All the text in the textarea

	// replace <>" with entities
	the_text = the_text.replace(/>/g,'&gt;').replace(/</g,'&lt;').replace(/"/g,'&quot;');
	//var text_node = document.createTextNode(the_text); // create text node for pre with text in it
	//pre.appendChild(text_node); // add text_node to pre
	pre.innerHTML = the_text;

	// Copy the complete HTML style from the textarea to the pre
	var completeStyle = "";
	if (typeof getComputedStyle !== 'undefined') // webkit
	{
		completeStyle = window.getComputedStyle(el, null).cssText;
		if (completeStyle != "") // Verison 6.0e - Is empty in IE 10 and Firefox
			pre.style.cssText = completeStyle; // Everything copies fine in Chrome
		else { // Version 6.0e - Because cssText is empty in IE 10 and Firefox
			var style = window.getComputedStyle(el, null);
			for (var i = 0; i < style.length; i++) {
    			completeStyle += style[i] + ": " + style.getPropertyValue(style[i]) + "; ";
    		}
    		pre.style.cssText = completeStyle;
		}
	}
	else if (el.currentStyle) // IE
	{
		var elStyle = el.currentStyle;
	    for (var k in elStyle) { completeStyle += k + ":" + elStyle[k] + ";"; }
	    //pre.style.cssText = completeStyle;
	    pre.style.border = "1px solid black"; // border not copying correctly in IE
	}

	el.parentNode.insertBefore(pre, el.nextSibling); // insert pre after textarea

	// If textarea blur then turn pre back on and textarea off
	el.onblur = function() { this.style.display = "none"; pre.style.display = "block"; };
	// If textarea changes then put new value back in pre
	el.onchange = function() { pre.innerHTML = el.value.replace(/>/g,'&gt;').replace(/</g,'&lt;').replace(/"/g,'&quot;'); };

	el.style.display = "none"; // hide textarea
	pre.id = "pre_"+coolfind.highlights.length; // Add id to pre

	// Set onclick to turn pre off and turn textarea back on and perform a click on the textarea
	// for a possible onclick="this.select()" for the textarea
	pre.onclick = function() {this.style.display = "none"; el.style.display = "block"; el.focus(); el.click()};

	// this.parentNode.removeChild(this); // old remove pre in onclick function above

} // end function textarea2pre(el)


// ver 5.1 - 10/17/2014
coolfind.selectElementContents = function(el)
{
    /* http://stackoverflow.com/questions/8019534/how-can-i-use-javascript-to-select-text-in-a-pre-node-block */
	if (window.getSelection && document.createRange) {
        // IE 9 and non-IE
        var range = document.createRange();
        range.selectNodeContents(el);
        var sel = window.getSelection();
        sel.removeAllRanges();
        sel.addRange(range);
    } else if (document.body.createTextRange) {
        // IE < 9
        var textRange = document.body.createTextRange();
        textRange.moveToElementText(el);
        textRange.select();
        //textRange.execCommand("Copy");
    }
} // end function selectElementContents(el)


coolfind.create_find_div();
