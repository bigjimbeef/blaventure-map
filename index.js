
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

$(document).ready(function() {

	console.log("Adding panning support...");
	$('svg').svgPan('viewport');

	var defaultScale = 3;
	transformToMid(defaultScale);

	// Show it after moving it.
	$('#viewport').show();
});

function highlight(evt) {

	// Unhighlight others.
	$('rect').each(function() {
		$(this).get(0).classList.remove("highlighted");
	});

	var rect = $(evt.target).get(0);
	rect.classList.add('highlighted');

	// TODO: show info
}