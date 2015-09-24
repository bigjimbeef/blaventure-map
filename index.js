
function transformToMid(inScale) {

	var mapMid = Math.floor(mapSize / 2);

	var docwidth = $(document).width();
	var docheight = $(document).height();

	screenMid = {
		x: docwidth / 2,
		y: docheight / 2
	};

	var xTarget = screenMid.x - ( inScale * ( mapMid * tileSize ));
	var yTarget = screenMid.y - ( inScale * ( mapMid * tileSize ));

	var translate = "matrix(" + inScale +", 0, 0, " + inScale + ", " + (xTarget) + ", " + (yTarget) + ")";

	$('#viewport').attr('transform', translate);
}

function getRandomColourArray() {

	var baseColour 	= Please.HEX_to_HSV(Please.make_color());
	var scheme 		= Please.make_scheme(baseColour, {scheme_type: 'double'});

	return scheme;
}

function addMapColouringFromClasses() {

	var coloursByClass		= {};
	var availableColours 	= getRandomColourArray();

	$('svg rect').each(function() {

		var rectClass = $(this).attr('class');

		if ( !coloursByClass.hasOwnProperty(rectClass) ) {

			// If we've run out of colours, make some more.
			if ( availableColours.length == 0 ) {

				availableColours = getRandomColourArray();
			}

			var colour = availableColours[0];

			coloursByClass[rectClass] = colour;
			availableColours.splice(0, 1);

			$(this).css({ fill: colour });

			// Add the legend element.
			var protoClone = $('#info-proto').clone();
			protoClone.removeAttr('id');

			protoClone.children('.image').css({ background: colour });
			protoClone.children('span').html(rectClass);

			$('#show-all').before(protoClone);

			$(protoClone).click(function() {

				localStorage.removeItem('nick');
				localStorage.setItem('nick', rectClass);

				$('svg rect').show();
				$('svg circle').show();

				$('svg rect').not('.' + rectClass).hide();
				$('svg circle').not('[data-owner="' + rectClass + '"]').hide();

				$('#show-all').show();
			});
		}
		else {

			var colour = coloursByClass[rectClass];

			$(this).css({ fill: colour });
		}
	});

	$('#show-all').click(function() {
		
		localStorage.removeItem('nick');

		$('svg rect').show();
		$('svg circle').show();

		$(this).hide();
	});
}

function clickOnNick(target) {

	var element = $('#legend .info').find('*').filter(function() {
		return $(this).text() === target;
	});

	if ( element ) {
		$(element).parent().click();
	}
}

$(document).ready(function() {

	$('svg').svgPan('viewport');

	var defaultScale = 3;
	transformToMid(defaultScale);

	addMapColouringFromClasses();

	$("rect").tooltip({
		items: ":not([hidden])",
		content: 	function() {
			
			var tooltip = false;

			var siblings = $(this).siblings();

			if ( typeof(siblings[0]) != "undefined" ) {

				var circle = siblings[0];

				if ( circle.classList.contains('current') ) {

					tooltip = $(circle).data('owner');
				}
				else if ( circle.classList.contains('monster') ) {

					tooltip = $(this).data('occupant');
				}
			}

			return tooltip;
		},
		track: 		true,
		position: 	{
			my: "left top+20 center",
			at: "right center"
		},
		show: false,
		hide: false
	});
});

function svgLoaded(evt) {

	var nickFromStore = localStorage.getItem('nick');

	if ( typeof(targetNick) != "undefined" ) {

		clickOnNick(targetNick);
	}
	else if ( nickFromStore ) {

		clickOnNick(nickFromStore);
	}
}