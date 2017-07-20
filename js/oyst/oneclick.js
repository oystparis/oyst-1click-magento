function goToThis(url) {
    Element.show('loading-mask');
    window.location.href = url;
    //Element.hide('loading-mask');
}

window.onload = function onLoad() {
    var syncBtn = $$('#syncbutton button')[0];
    if (!syncBtn.readAttribute('data-sync')) {
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
        var url = syncBtn.readAttribute('data-sync-url');
        var request = new XMLHttpRequest();

        request.open('GET', url, true);

        request.onreadystatechange = function() {
            if (this.readyState === 4) {
                if (this.status >= 200 && this.status < 400) {
                    // Success!
                    var data = JSON.parse(this.responseText);
                    var remainingEl = $('remaining');
                    var totalCountEl = $('totalCount');

                    remainingEl.update((data['totalCount'] - data['remaining']) + '/');
                    totalCountEl.update(data['totalCount']);

                    circle.animate((data['totalCount'] - data['remaining']) / data['totalCount']);
                    if (0 < data['remaining']) {
                        setTimeout(getImportProgress, 1000);
                    }
                    if (!data['remaining']) {
                        syncBtn.disable();
                        syncBtn.setStyle({background: ''});
                        var msg = $('syncbutton').readAttribute('data-msg-success');
                        showMessage(msg, "success");
                    }
                } else {
                    // Error :(
                    var msg = $('syncbutton').readAttribute('data-msg-error');
                    showMessage(msg, "error");
                }
            }
        };

        request.send();
        request = null;
    }

    getImportProgress();
};

function showMessage(txt, type) {
    var html = '<ul class="messages"><li class="' + type + '-msg"><ul><li>' + txt + '</li></ul></li></ul>';
    $("messages").update(html);
    var url = location.href;
    var n = url.indexOf("#");
    url = url.substring(0, n != -1 ? n : url.length);
    location.replace(url + "#html-body");
}
