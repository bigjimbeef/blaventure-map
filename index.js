
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
	var scheme 		= Please.make_scheme(baseColour, {scheme_type: 'ana'});

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

				$('svg rect').show();
				$('svg rect').not('.' + rectClass).hide();

				$('#show-all').show();
			});
		}
		else {

			var colour = coloursByClass[rectClass];

			$(this).css({ fill: colour });
		}
	});

	$('#show-all').click(function() {
		$('svg rect').show();

		$(this).hide();
	});
}

$(document).ready(function() {

	$('svg').svgPan('viewport');

	var defaultScale = 3;
	transformToMid(defaultScale);

	addMapColouringFromClasses();

	// Show it after moving it.
	$('#viewport').show();
});
