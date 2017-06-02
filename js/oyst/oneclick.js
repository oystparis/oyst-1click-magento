function goToThis(url) {
    Element.show('loading-mask');
    window.location.href = url;
    //Element.hide('loading-mask');
}

window.onload = function onLoad() {
    if (!jQuery("#syncbutton button").data("sync")) {
        return;
    }

    var circle = new ProgressBar.Circle('#progress', {
        color: '#555',
        trailColor: '#eee',
        // This has to be the same size as the maximum width to
        // prevent clipping
        strokeWidth: 10,
        trailWidth: 1,
        easing: 'easeInOut',
        duration: 2500,
        text: {
            autoStyleContainer: false
        },
        from: { color: '#aaa', width: 1 },
        to: { color: '#333', width: 4 }//,
    });

    circle.set(0);

    function getImportProgress() {
        jQuery.getJSON(jQuery("#syncbutton button").data("sync-url"), function (data) {
            jQuery("#remaining").html((data['totalCount'] - data['remaining']) + '/');
            jQuery("#totalCount").html(data['totalCount']);
            circle.animate((data['totalCount'] - data['remaining'])/data['totalCount']);
            if (0 < data['remaining']) {
                setTimeout(getImportProgress, 1000);
            }
        });
    }

    getImportProgress();
};
